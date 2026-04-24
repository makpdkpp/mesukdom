<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLineMessageJob;
use App\Mail\InvoiceLinkNotification;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\Line\ResidentFlexBuilder;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final class SendOverdueWarnings extends Command
{
    protected $signature = 'invoices:send-overdue-warnings
                            {--days=1 : Warn for invoices overdue by at least this many days}';

    protected $description = 'Send overdue warnings for unpaid resident invoices via tenant-configured channels';

    public function handle(): int
    {
        Invoice::markDueInvoicesAsOverdue();

        $sent = 0;
        $overdueDigestByTenant = [];

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            app(TenantContext::class)->set($tenant);

            $days = max(1, (int) ($this->option('days') ?: $tenant->overdueReminderAfterDays()));
            $cutoffDate = now()->subDays($days)->toDateString();

            $invoices = Invoice::query()
                ->with(['customer', 'room'])
                ->where('status', 'overdue')
                ->whereDate('due_date', '<=', $cutoffDate)
                ->get();

            $overdueDigestByTenant[$tenant->id] = collect();

            foreach ($invoices as $invoice) {
                $room = $invoice->room_id ? Room::query()->find($invoice->room_id) : null;
                $customer = $invoice->customer_id ? Customer::query()->find($invoice->customer_id) : null;
                $dueDate = Carbon::parse((string) $invoice->due_date)->format('d/m/Y');
                $daysOverdue = (int) max(0, now()->diffInDays(Carbon::parse((string) $invoice->due_date), false) * -1);

                $overdueDigestByTenant[$tenant->id]->push([
                    'customer' => $customer?->name ?? '-',
                    'room' => $room?->room_number ?? '-',
                    'amount' => (float) $invoice->total_amount,
                    'days_overdue' => $daysOverdue,
                ]);

                $alreadySentToday = NotificationLog::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('event', 'overdue_warning_sent')
                    ->whereDate('created_at', today())
                    ->whereJsonContains('payload->invoice_id', $invoice->id)
                    ->exists();

                if ($alreadySentToday) {
                    continue;
                }

                $message = sprintf(
                    'แจ้งเตือนบิลค้างชำระ %s ห้อง %s ยอด %s บาท ครบกำหนด %s กรุณาชำระโดยเร็ว %s',
                    $invoice->invoice_no,
                    $room?->room_number ?? '-',
                    number_format((float) $invoice->total_amount, 2),
                    $dueDate,
                    $invoice->signedResidentUrl(),
                );

                foreach ($tenant->overdueReminderChannels() as $channel) {
                    if ($channel === 'line') {
                        if (! $customer?->line_user_id) {
                            $this->logSkipped($tenant, 'line', $customer?->name, $message, $invoice->id);
                            continue;
                        }

                        SendLineMessageJob::dispatch(
                            $tenant->id,
                            'overdue_warning_sent',
                            $customer->line_user_id,
                            $message,
                            $customer->name,
                            $customer->id,
                            ['invoice_id' => $invoice->id],
                            app(ResidentFlexBuilder::class)->overdueWarning($invoice, $daysOverdue, 'บิล '.$invoice->invoice_no.' ค้างชำระ'),
                        );

                        $sent++;
                        continue;
                    }

                    $email = trim((string) ($customer?->email ?? ''));
                    if ($email === '') {
                        $this->logSkipped($tenant, 'email', $customer?->name, $message, $invoice->id);
                        continue;
                    }

                    $status = 'sent';

                    try {
                        Mail::to($email)->send(new InvoiceLinkNotification($invoice, $customer, 'overdue_warning'));
                        $sent++;
                    } catch (\Throwable) {
                        $status = 'failed';
                    }

                    NotificationLog::query()->create([
                        'tenant_id' => $tenant->id,
                        'channel' => 'email',
                        'event' => 'overdue_warning_sent',
                        'target' => $email,
                        'message' => $message,
                        'status' => $status,
                        'payload' => ['invoice_id' => $invoice->id],
                    ]);
                }
            }
        }

        app(TenantContext::class)->set(null);

        // Owner LINE digest per tenant (once per day, only if there are overdue items)
        foreach ($overdueDigestByTenant as $tenantId => $entries) {
            if ($entries->isEmpty()) {
                continue;
            }
            $tenant = Tenant::query()->find($tenantId);
            if (! $tenant) {
                continue;
            }
            \App\Support\OwnerNotifier::pushLineToOwners(
                $tenant,
                'overdue_digest',
                app(\App\Services\Line\MessageBuilder::class)->ownerOverdueDigest($tenant->name, $entries, route('app.invoices')),
                ['count' => $entries->count(), 'day' => today()->toDateString()],
                null,
                'overdue_digest:'.today()->toDateString(),
                app(\App\Services\Line\OwnerFlexBuilder::class)->overdueDigest($tenant->name, $entries, route('app.invoices')),
            );
        }

        $this->info('Done. Overdue warnings queued: '.$sent);

        return self::SUCCESS;
    }

    private function logSkipped(Tenant $tenant, string $channel, ?string $target, string $message, int $invoiceId): void
    {
        NotificationLog::query()->create([
            'tenant_id' => $tenant->id,
            'channel' => $channel,
            'event' => 'overdue_warning_sent',
            'target' => $target,
            'message' => $message,
            'status' => 'skipped',
            'payload' => ['invoice_id' => $invoiceId],
        ]);
    }
}
