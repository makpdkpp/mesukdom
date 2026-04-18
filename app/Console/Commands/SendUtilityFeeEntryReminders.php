<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\UtilityFeeEntryReminder;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendUtilityFeeEntryReminders extends Command
{
    protected $signature = 'invoices:send-utility-entry-reminders';

    protected $description = 'Send email reminders to owners to record utility units and other charges';

    public function handle(): int
    {
        $sent = 0;
        $today = now();

        foreach (Tenant::query()->where('status', 'active')->get() as $tenant) {
            if (! $tenant->shouldSendUtilityEntryReminderOn($today)) {
                continue;
            }

            app(TenantContext::class)->set($tenant);

            $owners = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->get();

            foreach ($owners as $owner) {
                $alreadySentToday = NotificationLog::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('event', 'utility_fee_entry_reminder_sent')
                    ->where('target', $owner->email)
                    ->whereDate('created_at', $today->toDateString())
                    ->exists();

                if ($alreadySentToday) {
                    continue;
                }

                $email = trim((string) $owner->email);
                if ($email === '') {
                    NotificationLog::query()->create([
                        'tenant_id' => $tenant->id,
                        'channel' => 'email',
                        'event' => 'utility_fee_entry_reminder_sent',
                        'target' => $owner->name,
                        'message' => 'Utility fee entry reminder',
                        'status' => 'skipped',
                        'payload' => ['user_id' => $owner->id],
                    ]);
                    continue;
                }

                $status = 'sent';

                try {
                    Mail::to($email)->send(new UtilityFeeEntryReminder($tenant, $owner));
                    $sent++;
                } catch (\Throwable) {
                    $status = 'failed';
                }

                NotificationLog::query()->create([
                    'tenant_id' => $tenant->id,
                    'channel' => 'email',
                    'event' => 'utility_fee_entry_reminder_sent',
                    'target' => $email,
                    'message' => 'Utility fee entry reminder',
                    'status' => $status,
                    'payload' => ['user_id' => $owner->id],
                ]);
            }
        }

        app(TenantContext::class)->set(null);
        $this->info('Done. Utility fee reminders sent: '.$sent);

        return self::SUCCESS;
    }
}
