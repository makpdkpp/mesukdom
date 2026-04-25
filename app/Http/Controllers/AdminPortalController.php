<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SendPlatformLineMessageJob;
use App\Models\NotificationLog;
use App\Models\OwnerLineLink;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OwnerLineLinkService;
use App\Services\StripePackagePricingService;
use App\Support\ApiMonitorMetrics;
use App\Support\SettingAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Throwable;

final class AdminPortalController extends Controller
{
    public function dashboard(): View
    {
        $queueConnection = (string) config('queue.default', 'sync');
        $hasJobsTable = Schema::hasTable('jobs');
        $hasFailedJobsTable = Schema::hasTable('failed_jobs');

        $pendingJobs = $hasJobsTable ? (int) DB::table('jobs')->count() : null;
        $failedJobsCount = $hasFailedJobsTable ? (int) DB::table('failed_jobs')->count() : 0;
        $redisHealth = $this->redisHealthPayload();
        $slipOkUsageTotal = SlipVerificationUsage::withoutGlobalScopes()
            ->where('provider', 'slipok')
            ->where('usage_month', now()->format('Y-m'))
            ->count();

        return view('dashboard.admin-dashboard', [
            'tenantCount' => Tenant::count(),
            'activeUsers' => User::count(),
            'saasRevenue' => Tenant::query()
                ->join('plans', 'tenants.plan_id', '=', 'plans.id')
                ->where('tenants.status', 'active')
                ->sum('plans.price_monthly'),
            'slipOkUsageTotal' => $slipOkUsageTotal,
            'serverStatus' => 'Online',
            'queueConnection' => $queueConnection,
            'pendingJobs' => $pendingJobs,
            'failedJobsCount' => $failedJobsCount,
            'redisHealth' => $redisHealth,
            'apiUsageTotal' => $slipOkUsageTotal,
            'notificationLogs' => NotificationLog::withoutGlobalScopes()->latest()->take(10)->get(),
            'paymentLogs' => Payment::withoutGlobalScopes()->latest()->take(10)->get(),
        ]);
    }

    public function profile(): View
    {
        return view('profile.show', [
            'isAdminProfile' => true,
            'profileRouteName' => 'admin.profile',
            'showBillingLink' => false,
        ]);
    }

    public function apiMonitor(ApiMonitorMetrics $metrics): View
    {
        return view('dashboard.admin-api-monitor', $metrics->dashboardPayload());
    }

    public function migrate(Request $request): RedirectResponse
    {
        $token = (string) $request->input('migrate_token', '');
        $expected = (string) config('app.migrate_token', '');

        if ($expected === '') {
            abort(403, 'Migrations are disabled.');
        }

        if (! hash_equals($expected, $token)) {
            abort(403, 'Invalid token.');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return back()->with([
                'success' => 'Migrations executed.',
                'migrateOutput' => $output,
            ]);
        } catch (Throwable $e) {
            return back()->with([
                'error' => 'Migration failed: '.class_basename($e).' - '.$e->getMessage(),
                'migrateOutput' => $e->getTraceAsString(),
            ]);
        }
    }

    public function dbMigration(): View
    {
        $hasTimestamps = Schema::hasTable('migrations') && Schema::hasColumn('migrations', 'created_at');

        $migrationRows = Schema::hasTable('migrations')
            ? DB::table('migrations')
                ->select($hasTimestamps ? ['migration', 'batch', 'created_at'] : ['migration', 'batch'])
                ->orderBy('migration')
                ->get()
            : collect();

        $completedMap = $migrationRows->mapWithKeys(fn ($row): array => [
            (string) $row->migration => [
                'batch'      => is_numeric($row->batch) ? (int) $row->batch : null,
                'run_date'   => $hasTimestamps ? ($row->created_at ?? null) : null,
            ],
        ]);

        $files = app('migrator')->getMigrationFiles(database_path('migrations'));
        ksort($files);

        $items = [];
        foreach ($files as $migration => $path) {
            $completed = $completedMap->has($migration);
            $rowData   = $completed ? $completedMap->get($migration) : [];
            $items[] = [
                'migration' => $migration,
                'path'      => (string) $path,
                'completed' => $completed,
                'batch'     => $completed ? (int) ($rowData['batch'] ?? 0) : null,
                'run_date'  => $completed ? ($rowData['run_date'] ?? null) : null,
            ];
        }

        $total     = count($items);
        $completed = collect($items)->where('completed', true)->count();
        $pending   = $total - $completed;

        $maxBatch = Schema::hasTable('migrations') ? (int) DB::table('migrations')->max('batch') : 0;

        // Database schema: list of tables with their columns
        $dbSchema = [];
        try {
            $tables = DB::select('SHOW TABLES');
            $dbKey  = 'Tables_in_' . DB::getDatabaseName();
            foreach ($tables as $tableRow) {
                $tableName = $tableRow->$dbKey ?? array_values((array) $tableRow)[0];
                $columns   = DB::select('SHOW COLUMNS FROM `' . $tableName . '`');
                $dbSchema[(string) $tableName] = array_map(fn ($col): array => [
                    'field'   => $col->Field,
                    'type'    => $col->Type,
                    'null'    => $col->Null,
                    'key'     => $col->Key,
                    'default' => $col->Default,
                ], $columns);
            }
        } catch (Throwable) {
            $dbSchema = [];
        }

        return view('dashboard.admin-dbmigration', [
            'totalMigrations'     => $total,
            'completedMigrations' => $completed,
            'pendingMigrations'   => $pending,
            'maxBatch'            => $maxBatch,
            'migrationItems'      => $items,
            'dbSchema'            => $dbSchema,
        ]);
    }

    public function rollback(Request $request): RedirectResponse
    {
        $token = (string) $request->input('migrate_token', '');
        $expected = (string) config('app.migrate_token', '');

        if ($expected === '') {
            abort(403, 'Migrations are disabled.');
        }

        if (! hash_equals($expected, $token)) {
            abort(403, 'Invalid token.');
        }

        try {
            Artisan::call('migrate:rollback', ['--force' => true]);
            $output = Artisan::output();

            return back()->with([
                'success' => 'Rollback executed.',
                'migrateOutput' => $output,
            ]);
        } catch (Throwable $e) {
            return back()->with([
                'error' => 'Rollback failed: '.class_basename($e).' - '.$e->getMessage(),
                'migrateOutput' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @return array{client: string, connections: array<string, array{ok: bool, ping: string|null, error: string|null}>, ok: bool}
     */
    private function redisHealthPayload(): array
    {
        $client = (string) config('database.redis.client', 'unknown');
        $connections = [];

        foreach (['default', 'line'] as $connectionName) {
            try {
                $connection = Redis::connection($connectionName);
                $pingResult = method_exists($connection, 'ping')
                    ? $connection->ping()
                    : $connection->command('ping');
                $ping = is_string($pingResult) ? $pingResult : (is_scalar($pingResult) ? (string) $pingResult : null);
                $pongLike = $ping !== null && str_contains(strtoupper($ping), 'PONG');

                $connections[$connectionName] = [
                    'ok' => $pongLike,
                    'ping' => $ping,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                $connections[$connectionName] = [
                    'ok' => false,
                    'ping' => null,
                    'error' => class_basename($e).' - '.$e->getMessage(),
                ];
            }
        }

        $overallOk = collect($connections)->every(fn (array $item): bool => (bool) ($item['ok'] ?? false));

        return [
            'client' => $client,
            'connections' => $connections,
            'ok' => $overallOk,
        ];
    }

    public function index(): View
    {
        $platformSetting = PlatformSetting::current();
        $stripeReadiness = $platformSetting->stripeReadinessPayload();
        $plans = Plan::query()->orderBy('sort_order')->get();
        $plansMissingStripePrice = $plans
            ->filter(fn (Plan $plan): bool => filled($plan->getAttribute('is_active')) && blank($plan->stripe_price_id))
            ->values();

        return view('dashboard.admin', [
            'failedJobs' => Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0,
            'notificationLogs' => NotificationLog::query()->latest()->take(12)->get(),
            'plans' => $plans,
            'platformSetting' => $platformSetting,
            'stripeReadiness' => $stripeReadiness,
            'plansMissingStripePrice' => $plansMissingStripePrice,
            'slipOkUsageTotal' => SlipVerificationUsage::withoutGlobalScopes()
                ->where('provider', 'slipok')
                ->where('usage_month', now()->format('Y-m'))
                ->count(),
        ]);
    }

    public function tenants(Request $request): View
    {
        return view('dashboard.admin-tenants', $this->tenantManagementPayload($request));
    }

    public function packages(): View
    {
        return view('dashboard.admin-packages', [
            'plans' => Plan::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function notifications(): View
    {
        return view('dashboard.admin-notifications', [
            'platformSetting' => PlatformSetting::current(),
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_notify_owner_payment_received'      => ['nullable'],
            'default_notify_owner_utility_reminder_day'  => ['nullable'],
            'default_notify_owner_invoice_create_day'    => ['nullable'],
            'default_notify_owner_invoice_send_day'      => ['nullable'],
            'default_notify_owner_overdue_digest'        => ['nullable'],
            'default_notify_owner_channels'              => ['required', \Illuminate\Validation\Rule::in(['line', 'email', 'both'])],
            'platform_line_owner_broadcast_enabled'      => ['nullable'],
        ]);

        $setting = PlatformSetting::current();
        $before = $setting->only([
            'default_notify_owner_payment_received',
            'default_notify_owner_utility_reminder_day',
            'default_notify_owner_invoice_create_day',
            'default_notify_owner_invoice_send_day',
            'default_notify_owner_overdue_digest',
            'default_notify_owner_channels',
            'platform_line_owner_broadcast_enabled',
        ]);
        $after = [
            'default_notify_owner_payment_received' => (bool) ($validated['default_notify_owner_payment_received'] ?? false),
            'default_notify_owner_utility_reminder_day' => (bool) ($validated['default_notify_owner_utility_reminder_day'] ?? false),
            'default_notify_owner_invoice_create_day' => (bool) ($validated['default_notify_owner_invoice_create_day'] ?? false),
            'default_notify_owner_invoice_send_day' => (bool) ($validated['default_notify_owner_invoice_send_day'] ?? false),
            'default_notify_owner_overdue_digest' => (bool) ($validated['default_notify_owner_overdue_digest'] ?? false),
            'default_notify_owner_channels' => $validated['default_notify_owner_channels'],
            'platform_line_owner_broadcast_enabled' => (bool) ($validated['platform_line_owner_broadcast_enabled'] ?? false),
        ];

        $setting->fill([
            'default_notify_owner_payment_received'      => (bool) ($validated['default_notify_owner_payment_received'] ?? false),
            'default_notify_owner_utility_reminder_day'  => (bool) ($validated['default_notify_owner_utility_reminder_day'] ?? false),
            'default_notify_owner_invoice_create_day'    => (bool) ($validated['default_notify_owner_invoice_create_day'] ?? false),
            'default_notify_owner_invoice_send_day'      => (bool) ($validated['default_notify_owner_invoice_send_day'] ?? false),
            'default_notify_owner_overdue_digest'        => (bool) ($validated['default_notify_owner_overdue_digest'] ?? false),
            'default_notify_owner_channels'              => $validated['default_notify_owner_channels'],
            'platform_line_owner_broadcast_enabled'      => (bool) ($validated['platform_line_owner_broadcast_enabled'] ?? false),
        ])->save();

        /** @var User|null $actor */
        $actor = auth()->user();
        SettingAuditLogger::log('platform_notification_defaults', null, $actor, $before, $after);

        return back()->with('success', 'อัปเดตค่าเริ่มต้นการแจ้งเตือนเรียบร้อย');
    }

    public function platformLine(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $linkService = app(OwnerLineLinkService::class);

        return view('dashboard.admin-platform-line', [
            'platformSetting' => PlatformSetting::current(),
            'platformActiveLink' => $linkService->findActiveTokenFor($user, OwnerLineLink::SCOPE_PLATFORM),
            'logs' => NotificationLog::query()->where('channel', 'line:platform')->latest()->limit(20)->get(),
        ]);
    }

    public function updatePlatformLineSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_line_channel_id' => ['nullable', 'string', 'max:100'],
            'platform_line_basic_id' => ['nullable', 'string', 'max:100'],
            'platform_line_channel_access_token' => ['nullable', 'string', 'max:500'],
            'platform_line_channel_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = PlatformSetting::current();
        $before = $setting->only([
            'platform_line_channel_id',
            'platform_line_basic_id',
            'platform_line_webhook_url',
        ]);
        $after = [
            'platform_line_channel_id' => $validated['platform_line_channel_id'] ?? null,
            'platform_line_basic_id' => $validated['platform_line_basic_id'] ?? null,
            'platform_line_webhook_url' => route('api.line.platform-webhook'),
        ];

        $setting->platform_line_channel_id = $validated['platform_line_channel_id'] ?? null;
        $setting->platform_line_basic_id = $validated['platform_line_basic_id'] ?? null;
        $setting->platform_line_webhook_url = route('api.line.platform-webhook');

        if (filled($validated['platform_line_channel_access_token'] ?? null)) {
            $setting->platform_line_channel_access_token = $validated['platform_line_channel_access_token'];
        }

        if (filled($validated['platform_line_channel_secret'] ?? null)) {
            $setting->platform_line_channel_secret = $validated['platform_line_channel_secret'];
        }

        $setting->save();

        /** @var User|null $actor */
        $actor = auth()->user();
        SettingAuditLogger::log('platform_line_credentials', null, $actor, $before, $after);

        return back()->with('success', 'Platform LINE settings updated.');
    }

    public function createPlatformLineLink(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $link = app(OwnerLineLinkService::class)->createForUser($user, OwnerLineLink::SCOPE_PLATFORM);

        return back()->with('platform_line_link', [
            'token' => $link->link_token,
            'expires_at' => $link->expired_at->format('d/m/Y H:i'),
            'instruction' => 'เพิ่มเพื่อน Platform LINE OA แล้วพิมพ์ ADMIN:'.$link->link_token,
            'add_friend_url' => PlatformSetting::current()->platform_line_basic_id
                ? 'https://line.me/R/ti/p/'.(str_starts_with((string) PlatformSetting::current()->platform_line_basic_id, '@') ? PlatformSetting::current()->platform_line_basic_id : '@'.PlatformSetting::current()->platform_line_basic_id)
                : null,
        ]);
    }

    public function unlinkPlatformLine(): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        app(OwnerLineLinkService::class)->unlink($user, OwnerLineLink::SCOPE_PLATFORM);

        return back()->with('success', 'ยกเลิกการผูก Platform LINE แล้ว');
    }

    public function sendPlatformBroadcast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'recipient_filter' => ['required', 'in:all,plan,status'],
            'plan_id' => ['nullable', 'integer'],
            'tenant_status' => ['nullable', 'in:active,suspended,trial'],
        ]);

        $setting = PlatformSetting::current();

        if (! $setting->platform_line_owner_broadcast_enabled) {
            return back()->with('error', 'Platform owner broadcast is disabled.');
        }

        $query = User::query()
            ->with('tenant')
            ->where('role', 'owner')
            ->whereNotNull('platform_line_user_id_hash');

        if (($validated['recipient_filter'] ?? 'all') === 'plan' && filled($validated['plan_id'] ?? null)) {
            $query->whereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('plan_id', (int) $validated['plan_id']));
        }

        if (($validated['recipient_filter'] ?? 'all') === 'status' && filled($validated['tenant_status'] ?? null)) {
            $query->whereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('status', (string) $validated['tenant_status']));
        }

        $owners = $query->get();
        $dispatched = 0;

        foreach ($owners->chunk(50) as $chunkIndex => $chunk) {
            foreach ($chunk as $owner) {
                SendPlatformLineMessageJob::dispatch(
                    $owner->id,
                    $owner->tenant_id,
                    $owner->platform_line_user_id,
                    'platform_owner_broadcast',
                    $validated['message'],
                    ['recipient_filter' => $validated['recipient_filter']]
                )->delay(now()->addSeconds($chunkIndex * 2));
                $dispatched++;
            }
        }

        return back()->with('success', 'Queued '.$dispatched.' platform LINE broadcast messages.');
    }

    public function updateSlipOkSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slipok_enabled' => ['nullable', 'boolean'],
            'slipok_api_url' => ['nullable', 'url', 'max:255'],
            'slipok_api_secret' => ['nullable', 'string', 'max:500'],
            'slipok_timeout_seconds' => ['required', 'integer', 'min:3', 'max:60'],
        ]);

        $setting = PlatformSetting::current();
        $secret = $validated['slipok_api_secret'] ?? null;

        $setting->slipok_enabled = (bool) $request->boolean('slipok_enabled');
        $setting->slipok_api_url = $validated['slipok_api_url'] ?? $setting->slipok_api_url;
        $setting->slipok_api_secret = filled($secret) ? $secret : $setting->slipok_api_secret;
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = (int) $validated['slipok_timeout_seconds'];
        $setting->save();

        return back()->with('success', 'Platform SlipOK settings updated.');
    }

    public function updateStripeSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'stripe_enabled' => ['nullable', 'boolean'],
            'stripe_mode' => ['required', 'in:test,live'],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $setting = PlatformSetting::current();

        $setting->stripe_enabled = (bool) $request->boolean('stripe_enabled');
        $setting->stripe_mode = (string) ($validated['stripe_mode'] ?? 'test');
        $setting->stripe_publishable_key = $validated['stripe_publishable_key'] ?? null;

        $secret = $validated['stripe_secret_key'] ?? null;
        if (filled($secret)) {
            $setting->stripe_secret_key = $secret;
        }

        $webhookSecret = $validated['stripe_webhook_secret'] ?? null;
        if (filled($webhookSecret)) {
            $setting->stripe_webhook_secret = $webhookSecret;
        }

        $setting->save();

        return back()->with('success', 'Stripe settings updated.');
    }

    public function storePackage(Request $request, StripePackagePricingService $stripePackagePricing): RedirectResponse
    {
        $validated = $this->validatePackageRequest($request);
        $customRoomPricing = $request->boolean('custom_room_pricing');
        $priceMonthly = $customRoomPricing
            ? $this->validatedRoomPriceMonthly($validated)
            : (float) $validated['price_monthly'];

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if ($slug === '') {
            $slug = 'plan-'.now()->timestamp;
        }

        $stripePriceId = $validated['stripe_price_id'] ?? null;
        $stripeProductId = null;
        $platformSetting = PlatformSetting::current();

        if (! $customRoomPricing && blank($stripePriceId) && ! $platformSetting->hasStripeCredentials()) {
            return back()
                ->withInput()
                ->withErrors([
                    'stripe_price_id' => 'Stripe is not ready for automatic price creation yet. Configure Stripe settings first or enter a Stripe Price ID manually.',
                ]);
        }

        if (! $customRoomPricing && blank($stripePriceId) && $platformSetting->hasStripeCredentials()) {
            try {
                $stripeCatalog = $stripePackagePricing->createMonthlyCatalog(
                    $platformSetting,
                    (string) $validated['name'],
                    $slug,
                    $priceMonthly,
                    $validated['description'] ?? null,
                );
                $stripePriceId = $stripeCatalog['price_id'];
                $stripeProductId = $stripeCatalog['product_id'];
            } catch (ApiErrorException $e) {
                Log::warning('Stripe price auto-creation failed for package', [
                    'package_name' => $validated['name'],
                    'package_slug' => $slug,
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->withErrors([
                        'stripe_price_id' => 'Unable to create Stripe Price automatically. Please verify Stripe settings or enter a Stripe Price ID manually.',
                    ]);
            }
        }

        Plan::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'price_monthly' => $priceMonthly,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'stripe_price_id' => $customRoomPricing ? null : $stripePriceId,
            'stripe_product_id' => $stripeProductId,
            'limits' => $this->packageLimitsPayload($request, $validated),
        ]);

        return back()->with('success', 'Package created successfully.');
    }

    public function updatePackage(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $this->validatePackageRequest($request, $plan);
        $customRoomPricing = $request->boolean('custom_room_pricing');
        $priceMonthly = $customRoomPricing
            ? $this->validatedRoomPriceMonthly($validated)
            : (float) $validated['price_monthly'];

        $plan->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price_monthly' => $priceMonthly,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'stripe_price_id' => $customRoomPricing ? null : ($validated['stripe_price_id'] ?? null),
            'limits' => $this->packageLimitsPayload($request, $validated),
        ]);

        return back()->with('success', "Package '{$plan->name}' updated.");
    }

    public function destroyPackage(Plan $plan, StripePackagePricingService $stripePackagePricing): RedirectResponse
    {
        $name = $plan->name;
        $platformSetting = PlatformSetting::current();

        if (filled($plan->stripe_price_id) || filled($plan->stripe_product_id)) {
            if (! $platformSetting->hasStripeCredentials()) {
                return back()->withErrors([
                    'stripe_price_id' => 'Stripe cleanup is unavailable because Stripe settings are not ready. Please configure Stripe first or archive the Stripe product manually before deleting this package.',
                ]);
            }

            try {
                $stripePackagePricing->archiveCatalog(
                    $platformSetting,
                    $plan->stripe_price_id ? (string) $plan->stripe_price_id : null,
                    $plan->stripe_product_id ? (string) $plan->stripe_product_id : null,
                );
            } catch (ApiErrorException $e) {
                Log::warning('Stripe catalog cleanup failed for package deletion', [
                    'plan_id' => $plan->id,
                    'plan_name' => $name,
                    'stripe_price_id' => $plan->stripe_price_id,
                    'stripe_product_id' => $plan->stripe_product_id,
                    'error' => $e->getMessage(),
                ]);

                return back()->withErrors([
                    'stripe_price_id' => 'Unable to archive the Stripe price/product for this package. The local package was not deleted.',
                ]);
            }
        }

        $plan->delete();

        return back()->with('success', "Package '{$name}' deleted. Related Stripe catalog has been archived.");
    }

    public function updatePlanSlipOkSettings(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'slipok_enabled' => ['nullable', 'boolean'],
            'slipok_monthly_limit' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $limits = (array) ($plan->limits ?? []);
        $limits['slipok_enabled'] = (bool) $request->boolean('slipok_enabled');
        $limits['slipok_monthly_limit'] = (int) $validated['slipok_monthly_limit'];

        $plan->update(['limits' => $limits]);

        return back()->with('success', "Updated SlipOK addon quota for {$plan->name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackageRequest(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => array_filter([
                $plan ? 'required' : 'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                'unique:plans,slug'.($plan ? ','.$plan->id : ''),
            ]),
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'stripe_price_id' => ['nullable', 'string', 'max:120', 'regex:/^price_[A-Za-z0-9_]+$/'],
            'rooms_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'recommended' => ['nullable', 'boolean'],
            'slipok_enabled' => ['nullable', 'boolean'],
            'slipok_monthly_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'custom_room_pricing' => ['nullable', 'boolean'],
            'room_price_monthly' => ['nullable', 'numeric', 'min:0'],
            'slipok_addon_price_monthly' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($request->boolean('custom_room_pricing')) {
            if (! array_key_exists('room_price_monthly', $validated) || ! is_numeric($validated['room_price_monthly'])) {
                throw ValidationException::withMessages([
                    'room_price_monthly' => 'Room price per month is required for custom room pricing packages.',
                ]);
            }

            if ($request->boolean('slipok_enabled') && (! array_key_exists('slipok_addon_price_monthly', $validated) || ! is_numeric($validated['slipok_addon_price_monthly']))) {
                throw ValidationException::withMessages([
                    'slipok_addon_price_monthly' => 'SlipOK addon price per room is required when SlipOK addon is enabled for a custom package.',
                ]);
            }

            return $validated;
        }

        if (! array_key_exists('rooms_limit', $validated) || ! is_numeric($validated['rooms_limit'])) {
            throw ValidationException::withMessages([
                'rooms_limit' => 'Room limit is required for fixed packages.',
            ]);
        }

        if (! array_key_exists('slipok_monthly_limit', $validated) || ! is_numeric($validated['slipok_monthly_limit'])) {
            throw ValidationException::withMessages([
                'slipok_monthly_limit' => 'SlipOK monthly limit is required for fixed packages.',
            ]);
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function validatedRoomPriceMonthly(array $validated): float
    {
        return max(0, (float) $validated['room_price_monthly']);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function packageLimitsPayload(Request $request, array $validated): array
    {
        $customRoomPricing = $request->boolean('custom_room_pricing');
        $slipOkEnabled = (bool) $request->boolean('slipok_enabled');

        return [
            'pricing_mode' => $customRoomPricing ? 'per_room' : 'fixed',
            'rooms' => $customRoomPricing ? 0 : (int) ($validated['rooms_limit'] ?? 0),
            'room_price_monthly' => $customRoomPricing ? $this->validatedRoomPriceMonthly($validated) : null,
            'recommended' => (bool) $request->boolean('recommended'),
            'slipok_enabled' => $slipOkEnabled,
            'slipok_monthly_limit' => $customRoomPricing ? 0 : (int) ($validated['slipok_monthly_limit'] ?? 0),
            'slipok_addon_price_monthly' => $customRoomPricing && $slipOkEnabled
                ? max(0, (float) ($validated['slipok_addon_price_monthly'] ?? 0))
                : 0,
            'slipok_rights_per_room' => $customRoomPricing && $slipOkEnabled ? 3 : null,
        ];
    }

    public function updateTenantPlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);

        $tenant->update([
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
        ]);

        return back()->with('success', "Tenant '{$tenant->name}' moved to {$plan->name}.");
    }

    public function destroyTenant(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()
            ->route('admin.tenants')
            ->with('success', "Tenant '{$tenant->name}' archived.");
    }

    public function restoreTenant(int $tenantId): RedirectResponse
    {
        if (! Schema::hasColumn('tenants', 'deleted_at')) {
            return redirect()
                ->route('admin.tenants')
                ->with('error', 'Soft delete is unavailable because tenants.deleted_at is missing. Run migrations first.');
        }

        $tenant = Tenant::onlyTrashed()->findOrFail($tenantId);

        $tenant->restore();

        return redirect()
            ->route('admin.tenants', ['status' => 'deleted'])
            ->with('success', "Tenant '{$tenant->name}' restored.");
    }

    /**
    * @return array{plans: \Illuminate\Database\Eloquent\Collection<int, Plan>, tenants: \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Tenant>, slipOkUsageByTenant: \Illuminate\Support\Collection<int, int>, filters: array{q: string, plan_id: string, status: string}}
     */
    private function tenantManagementPayload(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'status' => ['nullable', 'in:all,active,pending_checkout,suspended,deleted'],
        ]);

        $plans = Plan::query()->orderBy('sort_order')->get();
        $status = (string) ($validated['status'] ?? 'all');
        $hasTenantSoftDeletes = Schema::hasColumn('tenants', 'deleted_at');

        $tenantsQuery = match ($status) {
            'deleted' => $hasTenantSoftDeletes ? Tenant::onlyTrashed() : Tenant::query()->whereRaw('1 = 0'),
            default => Tenant::query(),
        };

        $tenants = $tenantsQuery
            ->with('subscriptionPlan')
            ->withCount('users')
            ->when(($validated['q'] ?? null) !== null && $validated['q'] !== '', function ($query) use ($validated): void {
                $query->where(function ($subQuery) use ($validated): void {
                    $search = '%'.$validated['q'].'%';

                    $subQuery->where('name', 'like', $search)
                        ->orWhere('domain', 'like', $search);
                });
            })
            ->when(isset($validated['plan_id']), fn ($query): mixed => $query->where('plan_id', (int) $validated['plan_id']))
            ->when(in_array($status, ['active', 'pending_checkout', 'suspended'], true), fn ($query): mixed => $query->where('status', $status))
            ->orderBy($status === 'deleted' && $hasTenantSoftDeletes ? 'deleted_at' : 'name', $status === 'deleted' && $hasTenantSoftDeletes ? 'desc' : 'asc')
            ->paginate(10)
            ->withQueryString();
        $usageMap = SlipVerificationUsage::query()
            ->selectRaw('tenant_id, count(*) as total')
            ->where('provider', 'slipok')
            ->where('usage_month', now()->format('Y-m'))
            ->groupBy('tenant_id')
            ->get()
            ->mapWithKeys(fn (SlipVerificationUsage $usage): array => [
                (int) ($usage->tenant_id ?? 0) => is_numeric($usage->getAttribute('total')) ? (int) $usage->getAttribute('total') : 0,
            ]);

        return [
            'plans' => $plans,
            'tenants' => $tenants,
            'slipOkUsageByTenant' => $usageMap,
            'filters' => [
                'q' => (string) ($validated['q'] ?? ''),
                'plan_id' => isset($validated['plan_id']) ? (string) $validated['plan_id'] : '',
                'status' => $status,
            ],
        ];
    }
}