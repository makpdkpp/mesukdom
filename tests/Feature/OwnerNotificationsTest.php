<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\GenerateMonthlyInvoices;
use App\Console\Commands\SendInvoiceLinks;
use App\Console\Commands\SendOverdueWarnings;
use App\Console\Commands\SendUtilityFeeEntryReminders;
use App\Jobs\SendLineMessageJob;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class OwnerNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        PlatformSetting::current()->update([
            'default_notify_owner_payment_received' => true,
            'default_notify_owner_utility_reminder_day' => true,
            'default_notify_owner_invoice_create_day' => true,
            'default_notify_owner_invoice_send_day' => true,
            'default_notify_owner_overdue_digest' => true,
            'default_notify_owner_channels' => 'line',
        ]);

        $this->tenant = Tenant::factory()->create([
            'name' => 'Notify Dorm',
            'line_channel_access_token' => 'tenant-token',
            'line_channel_secret' => 'tenant-secret',
            'utility_entry_reminder_day' => 1,
            'invoice_generate_day' => 1,
            'invoice_send_day' => 1,
            'invoice_due_day' => 5,
        ]);

        $this->owner = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
            'line_user_id' => 'U-owner-notify',
            'line_user_id_hash' => hash('sha256', 'U-owner-notify'),
            'line_linked_at' => now(),
        ]);
    }

    public function test_utility_reminder_dispatches_owner_flex_on_configured_day(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 7, 30));

        Artisan::call(SendUtilityFeeEntryReminders::class);

        Queue::assertPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $this->jobEventIs($job, 'owner_utility_reminder_day')
                && $this->jobHasFlex($job);
        });

        Carbon::setTestNow();
    }

    public function test_invoice_create_day_dispatches_owner_flex(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 0));

        $building = Building::query()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'A',
            'floor_count' => 3,
        ]);
        $room = Room::query()->create([
            'tenant_id' => $this->tenant->id,
            'building_id' => $building->id,
            'room_number' => 'A-101',
            'price' => 3000,
            'status' => 'occupied',
        ]);
        $customer = Customer::query()->create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident A',
            'phone' => '0810000001',
        ]);
        Contract::query()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'monthly_rent' => 3000,
            'deposit' => 6000,
            'status' => 'active',
        ]);

        Artisan::call(GenerateMonthlyInvoices::class);

        Queue::assertPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $this->jobEventIs($job, 'owner_invoice_create_day')
                && $this->jobHasFlex($job);
        });

        Carbon::setTestNow();
    }

    public function test_invoice_send_day_dispatches_owner_flex(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 30));

        $room = Room::query()->create([
            'tenant_id' => $this->tenant->id,
            'room_number' => 'B-101',
            'price' => 3000,
            'status' => 'occupied',
        ]);
        $customer = Customer::query()->create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident B',
            'phone' => '0810000002',
            'line_user_id' => 'U-resident-B',
        ]);
        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'status' => 'sent',
            'total_amount' => 3200,
            'issued_at' => now(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        Artisan::call(SendInvoiceLinks::class);

        Queue::assertPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $this->jobEventIs($job, 'owner_invoice_send_day')
                && $this->jobHasFlex($job);
        });

        Carbon::setTestNow();
    }

    public function test_overdue_warnings_dispatches_owner_digest_flex(): void
    {
        Queue::fake();

        $room = Room::query()->create([
            'tenant_id' => $this->tenant->id,
            'room_number' => 'C-101',
            'price' => 3000,
            'status' => 'occupied',
        ]);
        $customer = Customer::query()->create([
            'tenant_id' => $this->tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident C',
            'phone' => '0810000003',
        ]);
        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'status' => 'overdue',
            'total_amount' => 3500,
            'issued_at' => now()->subDays(30),
            'due_date' => now()->subDays(10)->toDateString(),
        ]);

        Artisan::call(SendOverdueWarnings::class, ['--days' => 1]);

        Queue::assertPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $this->jobEventIs($job, 'owner_overdue_digest')
                && $this->jobHasFlex($job);
        });
    }

    public function test_owner_notifications_skipped_when_tenant_disables_event(): void
    {
        Queue::fake();
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 7, 30));

        $this->tenant->update(['notify_owner_utility_reminder_day' => false]);

        Artisan::call(SendUtilityFeeEntryReminders::class);

        Queue::assertNotPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $this->jobEventIs($job, 'owner_utility_reminder_day');
        });

        Carbon::setTestNow();
    }

    private function jobEventIs(SendLineMessageJob $job, string $event): bool
    {
        $reflection = new \ReflectionClass($job);
        $prop = $reflection->getProperty('event');
        $prop->setAccessible(true);

        return $prop->getValue($job) === $event;
    }

    private function jobHasFlex(SendLineMessageJob $job): bool
    {
        $reflection = new \ReflectionClass($job);
        $prop = $reflection->getProperty('flex');
        $prop->setAccessible(true);
        $flex = $prop->getValue($job);

        return is_array($flex) && ($flex['type'] ?? null) === 'flex';
    }
}
