<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
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

    public function storePackage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:plans,slug'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'stripe_price_id' => ['nullable', 'string', 'max:120', 'regex:/^price_[A-Za-z0-9_]+$/'],
            'rooms_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'recommended' => ['nullable', 'boolean'],
            'slipok_enabled' => ['nullable', 'boolean'],
            'slipok_monthly_limit' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if ($slug === '') {
            $slug = 'plan-'.now()->timestamp;
        }

        Plan::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'price_monthly' => $validated['price_monthly'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'stripe_price_id' => $validated['stripe_price_id'] ?? null,
            'limits' => [
                'rooms' => (int) $validated['rooms_limit'],
                'recommended' => (bool) $request->boolean('recommended'),
                'slipok_enabled' => (bool) $request->boolean('slipok_enabled'),
                'slipok_monthly_limit' => (int) $validated['slipok_monthly_limit'],
            ],
        ]);

        return back()->with('success', 'Package created successfully.');
    }

    public function updatePackage(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:plans,slug,'.$plan->id],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'stripe_price_id' => ['nullable', 'string', 'max:120', 'regex:/^price_[A-Za-z0-9_]+$/'],
            'rooms_limit' => ['required', 'integer', 'min:0', 'max:10000'],
            'recommended' => ['nullable', 'boolean'],
            'slipok_enabled' => ['nullable', 'boolean'],
            'slipok_monthly_limit' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        $limits = (array) ($plan->limits ?? []);
        $limits['rooms'] = (int) $validated['rooms_limit'];
        $limits['recommended'] = (bool) $request->boolean('recommended');
        $limits['slipok_enabled'] = (bool) $request->boolean('slipok_enabled');
        $limits['slipok_monthly_limit'] = (int) $validated['slipok_monthly_limit'];

        $plan->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price_monthly' => $validated['price_monthly'],
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'stripe_price_id' => $validated['stripe_price_id'] ?? null,
            'limits' => $limits,
        ]);

        return back()->with('success', "Package '{$plan->name}' updated.");
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