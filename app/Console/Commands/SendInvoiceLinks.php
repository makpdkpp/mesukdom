<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLineMessageJob;
use App\Mail\InvoiceLinkNotification;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendInvoiceLinks extends Command
{
    protected $signature = 'invoices:send-links';

    protected $description = 'Send invoice links to residents using tenant-configured channels';

    public function handle(): int
    {
        $sent = 0;
        $today = now();

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            if (! $tenant->shouldSendInvoicesOn($today)) {
                continue;
            }

            app(TenantContext::class)->set($tenant);

            $invoices = Invoice::query()
                ->with(['customer', 'room'])
                ->whereIn('status', ['sent', 'overdue'])
                ->whereYear('issued_at', $today->year)
                ->whereMonth('issued_at', $today->month)
                ->get();

            foreach ($invoices as $invoice) {
                $alreadySentToday = NotificationLog::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('event', 'invoice_link_sent')
                    ->whereDate('created_at', $today->toDateString())
                    ->whereJsonContains('payload->invoice_id', $invoice->id)
                    ->exists();

                if ($alreadySentToday) {
                    continue;
                }

                $customer = $invoice->customer;
                $message = sprintf(
                    'บิล %s พร้อมแล้ว ยอด %s บาท ครบกำหนด %s ดูรายละเอียดและชำระเงินได้ที่ %s',
                    $invoice->invoice_no,
                    number_format((float) $invoice->total_amount, 2),
                    optional($invoice->due_date)->format('d/m/Y') ?? '-',
                    $invoice->signedResidentUrl(),
                );

                foreach ($tenant->invoiceSendChannels() as $channel) {
                    if ($channel === 'line') {
                        if (! $customer?->line_user_id) {
                            $this->logSkipped($tenant, 'line', $customer?->name, $message, $invoice->id);
                            continue;
                        }

                        SendLineMessageJob::dispatch(
                            $tenant->id,
                            'invoice_link_sent',
                            $customer->line_user_id,
                            $message,
                            $customer->name,
                            $customer->id,
                            ['invoice_id' => $invoice->id]
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
                        Mail::to($email)->send(new InvoiceLinkNotification($invoice, $customer));
                        $sent++;
                    } catch (\Throwable) {
                        $status = 'failed';
                    }

                    NotificationLog::query()->create([
                        'tenant_id' => $tenant->id,
                        'channel' => 'email',
                        'event' => 'invoice_link_sent',
                        'target' => $email,
                        'message' => $message,
                        'status' => $status,
                        'payload' => ['invoice_id' => $invoice->id],
                    ]);
                }
            }
        }

        app(TenantContext::class)->set(null);
        $this->info('Done. Invoice links queued/sent: '.$sent);

        return self::SUCCESS;
    }

    private function logSkipped(Tenant $tenant, string $channel, ?string $target, string $message, int $invoiceId): void
    {
        NotificationLog::query()->create([
            'tenant_id' => $tenant->id,
            'channel' => $channel,
            'event' => 'invoice_link_sent',
            'target' => $target,
            'message' => $message,
            'status' => 'skipped',
            'payload' => ['invoice_id' => $invoiceId],
        ]);
    }
}
