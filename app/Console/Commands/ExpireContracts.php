<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class ExpireContracts extends Command
{
    protected $signature = 'contracts:expire
                            {--dry-run : Show what would be expired without persisting}';

    protected $description = 'Mark active contracts as expired when their end_date has passed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today = now()->toDateString();

        $this->info(sprintf(
            '%sChecking contracts expired as of %s…',
            $dryRun ? '[DRY RUN] ' : '',
            $today
        ));

        $expired = 0;

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            app(TenantContext::class)->set($tenant);

            $contracts = Contract::query()
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today)
                ->with(['room', 'customer'])
                ->get();

            foreach ($contracts as $contract) {
                if (! $dryRun) {
                    $contract->update(['status' => 'expired']);

                    // Free up the room
                    $contract->room?->update(['status' => 'vacant']);
                }

                $this->line(sprintf(
                    '  [%s] %sContract #%d (Room %s, %s) expired on %s',
                    $tenant->name,
                    $dryRun ? 'Would expire: ' : 'Expired: ',
                    $contract->id,
                    $contract->room?->room_number ?? '-',
                    $contract->customer?->name ?? '-',
                    optional($contract->end_date)->format('d/m/Y')
                ));

                $expired++;
            }
        }

        app(TenantContext::class)->set(null);

        $label = $dryRun ? 'to expire' : 'expired';
        $this->info("Done. Contracts {$label}: {$expired}");

        return self::SUCCESS;
    }
}
