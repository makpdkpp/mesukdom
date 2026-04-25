<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\UtilityRecord;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly
                            {--month= : Target month in YYYY-MM format (default: current month)}
                            {--dry-run : Show what would be created without persisting}';

    protected $description = 'Generate monthly invoices for all active contracts across all tenants';

    public function handle(): int
    {
        $manualMonth = $this->option('month');
        $month = $manualMonth
            ? Carbon::createFromFormat('Y-m', (string) $manualMonth)->startOfMonth()
            : now()->startOfMonth();

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            '%sGenerating invoices for %s…',
            $dryRun ? '[DRY RUN] ' : '',
            $month->format('F Y')
        ));

        $created = 0;
        $skipped = 0;
        $createdByTenant = [];

        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            if (! $manualMonth && ! $tenant->shouldGenerateInvoicesOn(now())) {
                continue;
            }

            app(TenantContext::class)->set($tenant);
            $createdByTenant[$tenant->id] = $createdByTenant[$tenant->id] ?? 0;

            $contracts = Contract::query()
                ->where('status', 'active')
                ->where('start_date', '<=', $month->endOfMonth())
                ->where(function ($q) use ($month): void {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $month->startOfMonth());
                })
                ->with(['customer', 'room'])
                ->get();

            foreach ($contracts as $contract) {
                $utilityRecord = UtilityRecord::query()
                    ->where('contract_id', $contract->id)
                    ->where('billing_month', $month->format('Y-m'))
                    ->first();

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

                $roomFee = $this->resolveRoomFee($contract);
                $waterFee = (float) ($utilityRecord?->water_units ?? 0) * $tenant->defaultWaterFeeAmount();
                $electricityFee = (float) ($utilityRecord?->electricity_units ?? 0) * $tenant->defaultElectricityFeeAmount();
                $serviceFee = (float) ($utilityRecord?->other_amount ?? 0);
                $totalAmount = $roomFee + $waterFee + $electricityFee + $serviceFee;
                $dueDate = $this->resolveDueDate($tenant, $month);

                if (! $dryRun) {
                    $invoice = Invoice::create([
                        'tenant_id' => $tenant->id,
                        'contract_id' => $contract->id,
                        'customer_id' => $contract->customer_id,
                        'room_id' => $contract->room_id,
                        'room_fee' => $roomFee,
                        'total_amount' => $totalAmount,
                        'water_fee' => $waterFee,
                        'electricity_fee' => $electricityFee,
                        'service_fee' => $serviceFee,
                        'status' => 'sent',
                        'due_date' => $dueDate->toDateString(),
                    ]);

                    $this->line(sprintf(
                        '  [%s] Created %s for room %s — %s THB',
                        $tenant->name,
                        $invoice->invoice_no,
                        $contract->room?->room_number ?? '-',
                        number_format($totalAmount, 2)
                    ));
                } else {
                    $this->line(sprintf(
                        '  [%s] Would create invoice for room %s — %s THB (due %s)',
                        $tenant->name,
                        $contract->room?->room_number ?? '-',
                        number_format($totalAmount, 2),
                        $dueDate->toDateString()
                    ));
                }

                $created++;
                $createdByTenant[$tenant->id] = ($createdByTenant[$tenant->id] ?? 0) + 1;
            }
        }

        // Owner LINE notification per tenant (only when not dry-run)
        if (! $dryRun) {
            foreach ($createdByTenant as $tenantId => $countCreated) {
                if ($countCreated <= 0) {
                    continue;
                }
                $tenant = Tenant::query()->find($tenantId);
                if (! $tenant) {
                    continue;
                }
                \App\Support\OwnerNotifier::pushLineToOwners(
                    $tenant,
                    'invoice_create_day',
                    app(\App\Services\Line\MessageBuilder::class)->ownerInvoiceCreateDay($tenant->name, $countCreated, route('app.invoices')),
                    ['count' => $countCreated, 'month' => $month->format('Y-m')],
                    null,
                    'invoice_create_day:'.$month->format('Y-m'),
                    app(\App\Services\Line\OwnerFlexBuilder::class)->invoiceCreateDay($tenant->name, $countCreated, route('app.invoices')),
                );
            }
        }

        app(TenantContext::class)->set(null);

        $this->info(sprintf('Done. Created: %d, Skipped (duplicate): %d', $created, $skipped));

        return self::SUCCESS;
    }

    private function resolveRoomFee(Contract $contract): float
    {
        $contractRent = (float) $contract->monthly_rent;

        if ($contractRent > 0) {
            return $contractRent;
        }

        return (float) ($contract->room?->price ?? 0);
    }

    private function resolveDueDate(Tenant $tenant, Carbon $month): Carbon
    {
        $configuredDueDay = $tenant->invoiceDueDayOfNextMonth();

        if ($configuredDueDay !== null) {
            $nextMonth = $month->copy()->addMonthNoOverflow()->startOfMonth();

            return $nextMonth->day(min($configuredDueDay, $nextMonth->daysInMonth));
        }

        $generateDay = min($tenant->invoiceGenerateDayOfMonth(), $month->daysInMonth);
        $sendDay = min($tenant->invoiceSendDayOfMonth(), $month->daysInMonth);
        $minimumDueDay = max(5, $generateDay + 4, $sendDay + 3);

        return $month->copy()->day(min($minimumDueDay, $month->daysInMonth));
    }
}
