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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class AdminPortalController extends Controller
{
    public function dashboard(): View
    {
        $queueConnection = (string) config('queue.default', 'sync');
        $hasJobsTable = Schema::hasTable('jobs');
        $hasFailedJobsTable = Schema::hasTable('failed_jobs');
        $pendingJobs = $hasJobsTable ? (int) DB::table('jobs')->count() : null;
        $failedJobsCount = $hasFailedJobsTable ? (int) DB::table('failed_jobs')->count() : 0;
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
            'apiUsageTotal' => $slipOkUsageTotal,
            'notificationLogs' => NotificationLog::withoutGlobalScopes()->latest()->take(10)->get(),
            'paymentLogs' => Payment::withoutGlobalScopes()->latest()->take(10)->get(),
        ]);
    }

    public function index(): View
    {
        $plans = Plan::query()->orderBy('sort_order')->get();
        $tenants = Tenant::query()->with('subscriptionPlan')->orderBy('name')->get();
        $usageMap = SlipVerificationUsage::query()
            ->selectRaw('tenant_id, count(*) as total')
            ->where('provider', 'slipok')
            ->where('usage_month', now()->format('Y-m'))
            ->groupBy('tenant_id')
            ->get()
            ->mapWithKeys(fn (SlipVerificationUsage $usage): array => [
                (int) ($usage->tenant_id ?? 0) => is_numeric($usage->getAttribute('total')) ? (int) $usage->getAttribute('total') : 0,
            ]);

        return view('dashboard.admin', [
            'failedJobs' => Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0,
            'notificationLogs' => NotificationLog::query()->latest()->take(12)->get(),
            'tenants' => $tenants,
            'plans' => $plans,
            'platformSetting' => PlatformSetting::current(),
            'slipOkUsageTotal' => SlipVerificationUsage::withoutGlobalScopes()
                ->where('provider', 'slipok')
                ->where('usage_month', now()->format('Y-m'))
                ->count(),
            'slipOkUsageByTenant' => $usageMap,
        ]);
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
            'stripe_price_id' => ['nullable', 'string', 'max:120'],
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
            'stripe_price_id' => ['nullable', 'string', 'max:120'],
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
}