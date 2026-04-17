<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TrialExpiryReminder;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendTrialExpiryReminders extends Command
{
    protected $signature = 'tenants:send-trial-expiry-reminders
                            {--days=3 : Send reminders for trials ending in this many days}';

    protected $description = 'Send email reminders to tenant owners when trial is about to expire';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $targetDate = now()->addDays($days)->toDateString();

        $this->info("Sending trial expiry reminders for trials ending on {$targetDate} (in {$days} days)…");

        $sent = 0;

        foreach (Tenant::query()->where('status', 'active')->whereNotNull('trial_ends_at')->get() as $tenant) {
            app(TenantContext::class)->set($tenant);

            if ($tenant->trial_ends_at?->toDateString() !== $targetDate) {
                continue;
            }

            $owners = User::query()
                ->where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->get();

            foreach ($owners as $owner) {
                $email = (string) ($owner->email ?? '');

                if ($email === '') {
                    continue;
                }

                $status = 'skipped';

                try {
                    Mail::to($email)->send(new TrialExpiryReminder($tenant, $owner, $days));
                    $status = 'sent';
                    $sent++;
                } catch (\Throwable) {
                    $status = 'failed';
                }

                NotificationLog::query()->create([
                    'tenant_id' => $tenant->id,
                    'channel' => 'email',
                    'event' => 'trial_expiry_reminder_sent',
                    'target' => $email,
                    'message' => "Trial expiry reminder ({$days} days)",
                    'status' => $status,
                    'payload' => [
                        'days' => $days,
                        'trial_ends_at' => $tenant->trial_ends_at?->toDateString(),
                        'user_id' => $owner->id,
                    ],
                ]);
            }
        }

        app(TenantContext::class)->set(null);

        $this->info("Done. Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
