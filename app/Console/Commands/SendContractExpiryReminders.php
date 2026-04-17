<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLineMessageJob;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendContractExpiryReminders extends Command
{
    protected $signature = 'contracts:send-expiry-reminders
                            {--days=30 : Send reminders for contracts ending in this many days}';

    protected $description = 'Send LINE reminders for contracts nearing their end date';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $targetDate = now()->addDays($days)->toDateString();
        $event = $days <= 7 ? 'contract_renewal_reminder_sent' : 'contract_expiry_reminder_sent';
        $sent = 0;

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            app(TenantContext::class)->set($tenant);

            $contracts = Contract::query()
                ->with(['customer', 'room'])
                ->where('status', 'active')
                ->whereDate('end_date', $targetDate)
                ->get();

            foreach ($contracts as $contract) {
                $room = $contract->room_id ? Room::query()->find($contract->room_id) : null;
                $customer = $contract->customer_id ? Customer::query()->find($contract->customer_id) : null;
                $endDate = Carbon::parse((string) $contract->end_date)->format('d/m/Y');

                $message = $days <= 7
                    ? sprintf(
                        'สัญญาเช่าห้อง %s จะสิ้นสุดในอีก %d วัน กรุณาติดต่อเจ้าของหอเพื่อยืนยันการต่อสัญญา',
                        $room?->room_number ?? '-',
                        $days,
                    )
                    : sprintf(
                        'แจ้งเตือนสัญญาเช่าห้อง %s จะสิ้นสุดวันที่ %s หากต้องการต่อสัญญา กรุณาเตรียมติดต่อเจ้าของหอ',
                        $room?->room_number ?? '-',
                        $endDate,
                    );

                SendLineMessageJob::dispatch(
                    $tenant->id,
                    $event,
                    $customer?->line_user_id,
                    $message,
                    $customer?->name,
                    $customer?->id,
                    ['contract_id' => $contract->id, 'days' => $days]
                );

                $sent++;
            }
        }

        app(TenantContext::class)->set(null);
        $this->info('Done. Contract reminders queued: '.$sent);

        return self::SUCCESS;
    }
}
