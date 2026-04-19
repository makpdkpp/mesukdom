<?php

namespace Tests\Feature;

use App\Console\Commands\ExpireContracts;
use App\Console\Commands\GenerateMonthlyInvoices;
use App\Console\Commands\SendInvoiceLinks;
use App\Console\Commands\SendContractExpiryReminders;
use App\Console\Commands\SendOverdueWarnings;
use App\Console\Commands\SendPaymentReminders;
use App\Console\Commands\SendUtilityFeeEntryReminders;
use App\Mail\InvoiceLinkNotification;
use App\Mail\UtilityFeeEntryReminder;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\UtilityRecord;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_scheduled_commands_are_configured_without_overlapping(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertCount(9, $events);
        $this->assertTrue(collect($events)->every(fn ($event): bool => $event->withoutOverlapping === true));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GenerateMonthlyInvoices
    // ─────────────────────────────────────────────────────────────────────────

    public function test_generate_monthly_invoices_creates_invoice_for_active_contract(): void
    {
        Carbon::setTestNow('2026-04-01 08:00:00');

        $tenant = Tenant::create(['name' => 'Monthly Dorm', 'domain' => 'monthly.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'M-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Monthly Resident']);
        Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class, ['--month' => '2026-04'])->assertSuccessful();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'total_amount' => 5000,
            'status' => 'sent',
        ]);
    }

    public function test_generate_monthly_invoices_applies_room_price_utility_units_and_other_charges(): void
    {
        Carbon::setTestNow('2026-04-01 08:00:00');

        $tenant = Tenant::create([
            'name' => 'Fees Dorm',
            'domain' => 'fees.local',
            'plan' => 'trial',
            'status' => 'active',
            'default_water_fee' => 12,
            'default_electricity_fee' => 7,
        ]);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'F-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Fees Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);
        UtilityRecord::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'billing_month' => '2026-04',
            'water_units' => 10,
            'electricity_units' => 20,
            'other_amount' => 50,
            'other_description' => 'Common area',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class)->assertSuccessful();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'room_fee' => 5000,
            'total_amount' => 5310,
            'water_fee' => 120,
            'electricity_fee' => 140,
            'service_fee' => 50,
        ]);
    }

    public function test_generate_monthly_invoices_can_use_tenant_due_day_in_next_month(): void
    {
        Carbon::setTestNow('2026-04-26 08:00:00');

        $tenant = Tenant::create([
            'name' => 'Next Month Due Dorm',
            'domain' => 'next-due.local',
            'plan' => 'trial',
            'status' => 'active',
            'invoice_generate_day' => 26,
            'invoice_send_day' => 26,
            'invoice_due_day' => 4,
        ]);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'ND-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Due Resident']);
        Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class, ['--month' => '2026-04'])->assertSuccessful();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'due_date' => '2026-05-04 00:00:00',
        ]);
    }

    public function test_generate_monthly_invoices_skips_duplicate_for_same_month(): void
    {
        $tenant = Tenant::create(['name' => 'Dup Dorm', 'domain' => 'dup.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'D-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Dup Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        // Create invoice for April 2026 already
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => '2026-04-05',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class, ['--month' => '2026-04'])->assertSuccessful();

        // Should still be exactly 1 invoice (not duplicated)
        $count = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $this->assertSame(1, $count);
    }

    public function test_generate_monthly_invoices_skips_suspended_tenants(): void
    {
        $tenant = Tenant::create(['name' => 'Suspended Sched Dorm', 'domain' => 'susp-sched.local', 'plan' => 'trial', 'status' => 'suspended']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'SS-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Susp Resident']);
        Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class, ['--month' => '2026-04'])->assertSuccessful();

        // No invoice should be created for suspended tenant
        $this->assertDatabaseMissing('invoices', ['tenant_id' => $tenant->id]);
    }

    public function test_generate_monthly_invoices_dry_run_does_not_persist(): void
    {
        $tenant = Tenant::create(['name' => 'Dry Run Dorm', 'domain' => 'dryrun.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'DR-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Dry Resident']);
        Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        $this->artisan(GenerateMonthlyInvoices::class, ['--month' => '2026-04', '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseMissing('invoices', ['tenant_id' => $tenant->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SendPaymentReminders
    // ─────────────────────────────────────────────────────────────────────────

    public function test_send_reminders_logs_notification_for_due_soon_invoice(): void
    {
        $tenant = Tenant::create(['name' => 'Remind Dorm', 'domain' => 'remind.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'RM-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Remind Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0,
            'status' => 'sent', 'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan(SendPaymentReminders::class, ['--days' => 3])->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'reminder_sent',
            'channel' => 'line',
        ]);
    }

    public function test_send_reminders_does_not_log_for_already_paid_invoice(): void
    {
        $tenant = Tenant::create(['name' => 'Paid Remind Dorm', 'domain' => 'paidremind.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'PR-201', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Paid Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0,
            'status' => 'paid', 'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan(SendPaymentReminders::class, ['--days' => 3])->assertSuccessful();

        $this->assertDatabaseMissing('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'reminder_sent',
        ]);
    }

    public function test_send_overdue_warnings_logs_notification_for_overdue_invoice(): void
    {
        Carbon::setTestNow('2026-04-18 10:00:00');

        $tenant = Tenant::create(['name' => 'Overdue Dorm', 'domain' => 'overdue.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'OV-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Overdue Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0,
            'status' => 'sent', 'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan(SendOverdueWarnings::class, ['--days' => 1])->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'overdue_warning_sent',
            'channel' => 'line',
        ]);
    }

    public function test_send_invoice_links_sends_email_on_configured_day(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-04-02 08:30:00');

        $tenant = Tenant::create([
            'name' => 'Invoice Mail Dorm',
            'domain' => 'invoice-mail.local',
            'plan' => 'trial',
            'status' => 'active',
            'invoice_send_day' => 2,
            'invoice_send_channels' => 'email',
        ]);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'IM-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Invoice Resident', 'email' => 'invoice@test.local']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'issued_at' => '2026-04-01',
            'due_date' => '2026-04-05',
        ]);

        $this->artisan(SendInvoiceLinks::class)->assertSuccessful();

        Mail::assertSent(InvoiceLinkNotification::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->invoice->is($invoice));
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'invoice_link_sent',
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_send_overdue_warnings_can_send_email_notifications(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-04-18 10:00:00');

        $tenant = Tenant::create([
            'name' => 'Overdue Email Dorm',
            'domain' => 'overdue-email.local',
            'plan' => 'trial',
            'status' => 'active',
            'overdue_reminder_channels' => 'email',
            'overdue_reminder_after_days' => 2,
        ]);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'OE-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Overdue Email Resident', 'email' => 'overdue@test.local']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan(SendOverdueWarnings::class)->assertSuccessful();

        Mail::assertSent(InvoiceLinkNotification::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->invoice->is($invoice) && $mail->notificationType === 'overdue_warning');
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'overdue_warning_sent',
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_send_utility_fee_entry_reminders_emails_tenant_owner_on_configured_day(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-04-25 07:30:00');

        $tenant = Tenant::create([
            'name' => 'Utility Reminder Dorm',
            'domain' => 'utility-reminder.local',
            'plan' => 'trial',
            'status' => 'active',
            'utility_entry_reminder_day' => 25,
        ]);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email' => 'owner@test.local',
            'email_verified_at' => now(),
        ]);

        $this->artisan(SendUtilityFeeEntryReminders::class)->assertSuccessful();

        Mail::assertSent(UtilityFeeEntryReminder::class, fn ($mail) => $mail->hasTo($owner->email) && $mail->tenant->is($tenant));
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'utility_fee_entry_reminder_sent',
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_send_contract_expiry_reminders_logs_notification_for_expiring_contract(): void
    {
        $tenant = Tenant::create(['name' => 'Renew Dorm', 'domain' => 'renew.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'RN-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Renew Resident']);
        Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2026-01-01', 'end_date' => now()->addDays(30)->toDateString(), 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);

        $this->artisan(SendContractExpiryReminders::class, ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'contract_expiry_reminder_sent',
            'channel' => 'line',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ExpireContracts
    // ─────────────────────────────────────────────────────────────────────────

    public function test_expire_contracts_marks_past_due_contracts_as_expired(): void
    {
        $tenant = Tenant::create(['name' => 'Expire Dorm', 'domain' => 'expire.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'EX-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Expire Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2025-01-01', 'end_date' => '2026-03-31',
            'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);

        $this->artisan(ExpireContracts::class)->assertSuccessful();

        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => 'expired']);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'status' => 'vacant']);
    }

    public function test_expire_contracts_does_not_touch_future_contracts(): void
    {
        $tenant = Tenant::create(['name' => 'Future Dorm', 'domain' => 'future.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'FU-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Future Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31',
            'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);

        $this->artisan(ExpireContracts::class)->assertSuccessful();

        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => 'active']);
    }

    public function test_expire_contracts_dry_run_does_not_persist(): void
    {
        $tenant = Tenant::create(['name' => 'DryExp Dorm', 'domain' => 'dryexp.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'DE-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'DryExp Resident']);
        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id,
            'start_date' => '2025-01-01', 'end_date' => '2026-03-31',
            'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active',
        ]);

        $this->artisan(ExpireContracts::class, ['--dry-run' => true])->assertSuccessful();

        // Should still be active (dry run didn't persist)
        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'status' => 'active']);
    }
}
