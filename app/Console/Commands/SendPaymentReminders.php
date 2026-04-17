<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLineMessageJob;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'invoices:send-reminders
                            {--days=3 : Send reminders for invoices due in this many days}';

    protected $description = 'Send LINE reminders for invoices due soon (default: 3 days)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $this->info("Sending reminders for invoices due on {$targetDate} (in {$days} days)…");

        $sent = 0;

        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->set($tenant);

            $invoices = Invoice::query()
                ->whereIn('status', ['sent', 'overdue'])
                ->whereDate('due_date', $targetDate)
                ->with(['customer', 'room'])
                ->get();

            foreach ($invoices as $invoice) {
                $this->sendReminder($invoice, $tenant);
                $sent++;
            }
        }

        app(TenantContext::class)->set(null);

        $this->info("Done. Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    private function sendReminder(Invoice $invoice, Tenant $tenant): void
    {
        $message = sprintf(
            '[REMINDER] Invoice %s for room %s is due on %s. Total: %s THB. Please pay to avoid late fees.',
            $invoice->invoice_no,
            $invoice->room?->room_number ?? '-',
            optional($invoice->due_date)->format('d/m/Y'),
            number_format((float) $invoice->total_amount, 2)
        );

        SendLineMessageJob::dispatch(
            $tenant->id,
            'reminder_sent',
            $invoice->customer?->line_user_id,
            $message,
            $invoice->customer?->name,
            $invoice->customer?->id,
            ['invoice_id' => $invoice->id]
        );

        $this->line(sprintf(
            '  [%s] Reminder queued for %s (invoice %s)',
            $tenant->name,
            $invoice->customer?->name ?? '-',
            $invoice->invoice_no
        ));
    }
}
