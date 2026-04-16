<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly
                            {--month= : Target month in YYYY-MM format (default: current month)}
                            {--dry-run : Show what would be created without persisting}';

    protected $description = 'Generate monthly invoices for all active contracts across all tenants';

    public function handle(): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->startOfMonth();

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%sGenerating invoices for %s…',
            $dryRun ? '[DRY RUN] ' : '',
            $month->format('F Y')
        ));

        $created = 0;
        $skipped = 0;

        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->set($tenant);

            $contracts = Contract::query()
                ->where('status', 'active')
                ->where('start_date', '<=', $month->endOfMonth())
                ->where(function ($q) use ($month): void {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $month->startOfMonth());
                })
                ->with(['customer', 'room'])
                ->get();

            foreach ($contracts as $contract) {
                $alreadyExists = Invoice::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('contract_id', $contract->id)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->exists();

                if ($alreadyExists) {
                    $skipped++;
                    continue;
                }

                $dueDate = $month->copy()->day(min(5, $month->daysInMonth));

                if (! $dryRun) {
                    $invoice = Invoice::create([
                        'tenant_id' => $tenant->id,
                        'contract_id' => $contract->id,
                        'customer_id' => $contract->customer_id,
                        'room_id' => $contract->room_id,
                        'total_amount' => (float) $contract->monthly_rent,
                        'water_fee' => 0,
                        'electricity_fee' => 0,
                        'service_fee' => 0,
                        'status' => 'sent',
                        'due_date' => $dueDate->toDateString(),
                    ]);

                    $this->line(sprintf(
                        '  [%s] Created %s for room %s — %s THB',
                        $tenant->name,
                        $invoice->invoice_no,
                        $contract->room?->room_number ?? '-',
                        number_format((float) $contract->monthly_rent, 2)
                    ));
                } else {
                    $this->line(sprintf(
                        '  [%s] Would create invoice for room %s — %s THB (due %s)',
                        $tenant->name,
                        $contract->room?->room_number ?? '-',
                        number_format((float) $contract->monthly_rent, 2),
                        $dueDate->toDateString()
                    ));
                }

                $created++;
            }
        }

        app(TenantContext::class)->set(null);

        $this->info(sprintf('Done. Created: %d, Skipped (duplicate): %d', $created, $skipped));

        return self::SUCCESS;
    }
}
