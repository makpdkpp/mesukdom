<?php

declare(strict_types=1);

namespace App\Services\Business;

use App\Models\PlatformCostSetting;
use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class AdminBusinessMetrics
{
    public function __construct(
        private readonly PlatformCostCalculator $costCalculator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardPayload(?Carbon $date = null): array
    {
        $date ??= now();
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        $projectedMonthlyRevenue = $this->projectedMonthlyRevenue();
        $collectedRevenue = $this->paidSaasRevenue($monthStart, $monthEnd);
        $payingTenants = $this->paidSaasTenantCount($monthStart, $monthEnd);
        $stripeTransactionCount = $this->paidSaasInvoiceCount($monthStart, $monthEnd);
        $slipOkUsageTotal = $this->slipOkUsageTotal($date);
        $stripeCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_STRIPE, 0, $collectedRevenue, $stripeTransactionCount, $date);
        $slipOkCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_SLIPOK, $slipOkUsageTotal, 0.0, 0, $date);
        $lineCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_LINE, 0, 0.0, 0, $date);
        $emailCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_EMAIL, 0, 0.0, 0, $date);
        $hostingCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_HOSTING, 0, 0.0, 0, $date);
        $storageCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_STORAGE, 0, 0.0, 0, $date);
        $supportCost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_SUPPORT_OPS, 0, 0.0, 0, $date);
        $costBreakdown = collect([$slipOkCost, $stripeCost, $lineCost, $emailCost, $hostingCost, $storageCost, $supportCost])
            ->filter(fn (array $cost): bool => $cost['amount'] > 0 || $cost['unit_count'] > 0 || $cost['transaction_count'] > 0)
            ->values();
        $totalCosts = round($costBreakdown->sum('amount'), 2);
        $netRevenue = round($collectedRevenue - $totalCosts, 2);
        $marginPercent = $collectedRevenue > 0 ? round(($netRevenue / $collectedRevenue) * 100, 1) : 0.0;
        $activeTenants = Tenant::query()->where('status', 'active')->count();

        return [
            'periodLabel' => $date->format('F Y'),
            'tenantCount' => Tenant::count(),
            'activeTenants' => $activeTenants,
            'paidTenants' => $payingTenants,
            'trialTenants' => Tenant::query()->where('status', 'active')->whereNotNull('trial_ends_at')->whereDate('trial_ends_at', '>=', $date)->count(),
            'activeUsers' => User::count(),
            'projectedMonthlyRevenue' => round($projectedMonthlyRevenue, 2),
            'paidRevenue' => round($collectedRevenue, 2),
            'collectedRevenue' => round($collectedRevenue, 2),
            'netRevenue' => $netRevenue,
            'totalCosts' => $totalCosts,
            'marginPercent' => $marginPercent,
            'arpu' => $payingTenants > 0 ? round($collectedRevenue / $payingTenants, 2) : 0.0,
            'slipOkUsageTotal' => $slipOkUsageTotal,
            'stripeTransactionCount' => $stripeTransactionCount,
            'costBreakdown' => $costBreakdown,
            'revenueByPlan' => $this->revenueByPlan($monthStart, $monthEnd),
            'trialExpiringTenants' => $this->trialExpiringTenants($date),
            'highCostTenants' => $this->highCostTenants($date),
            'atRiskTenantCount' => $this->atRiskTenantCount($date),
        ];
    }

    private function projectedMonthlyRevenue(): float
    {
        return (float) Tenant::query()
            ->join('plans', 'tenants.plan_id', '=', 'plans.id')
            ->where('tenants.status', 'active')
            ->sum('plans.price_monthly');
    }

    private function paidSaasRevenue(Carbon $monthStart, Carbon $monthEnd): float
    {
        $amountPaid = (int) SaasInvoice::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount_paid');

        return $amountPaid / 100;
    }

    private function paidSaasInvoiceCount(Carbon $monthStart, Carbon $monthEnd): int
    {
        return SaasInvoice::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->where('amount_paid', '>', 0)
            ->count();
    }

    private function paidSaasTenantCount(Carbon $monthStart, Carbon $monthEnd): int
    {
        return SaasInvoice::query()
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->where('amount_paid', '>', 0)
            ->distinct('tenant_id')
            ->count('tenant_id');
    }

    private function slipOkUsageTotal(Carbon $date): int
    {
        return SlipVerificationUsage::withoutGlobalScopes()
            ->where('provider', 'slipok')
            ->where('usage_month', $date->format('Y-m'))
            ->count();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, tenants: int, revenue: float}>
     */
    private function revenueByPlan(Carbon $monthStart, Carbon $monthEnd): \Illuminate\Support\Collection
    {
        return SaasInvoice::query()
            ->join('plans', 'saas_invoices.plan_id', '=', 'plans.id')
            ->whereBetween('saas_invoices.paid_at', [$monthStart, $monthEnd])
            ->where('saas_invoices.amount_paid', '>', 0)
            ->select('plans.name', DB::raw('COUNT(DISTINCT saas_invoices.tenant_id) as tenant_count'), DB::raw('SUM(saas_invoices.amount_paid) as paid_revenue'))
            ->groupBy('plans.id', 'plans.name')
            ->orderByDesc('paid_revenue')
            ->get()
            ->map(fn ($plan): array => [
                'name' => (string) $plan->name,
                'tenants' => (int) $plan->getAttribute('tenant_count'),
                'revenue' => round(((int) $plan->getAttribute('paid_revenue')) / 100, 2),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Tenant>
     */
    private function trialExpiringTenants(Carbon $date): \Illuminate\Support\Collection
    {
        return Tenant::query()
            ->with('subscriptionPlan')
            ->where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$date->copy()->startOfDay(), $date->copy()->addDays(14)->endOfDay()])
            ->orderBy('trial_ends_at')
            ->take(8)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{name: string, usage: int, cost: float}>
     */
    private function highCostTenants(Carbon $date): \Illuminate\Support\Collection
    {
        return SlipVerificationUsage::query()
            ->withoutGlobalScopes()
            ->select('tenant_id', DB::raw('COUNT(*) as usage_count'))
            ->where('provider', 'slipok')
            ->where('usage_month', $date->format('Y-m'))
            ->groupBy('tenant_id')
            ->orderByDesc('usage_count')
            ->take(8)
            ->get()
            ->map(function (SlipVerificationUsage $usage) use ($date): array {
                $usageCount = (int) $usage->getAttribute('usage_count');
                $tenant = Tenant::query()->find($usage->tenant_id);
                $cost = $this->costCalculator->providerCost(PlatformCostSetting::PROVIDER_SLIPOK, $usageCount, 0.0, 0, $date);

                return [
                    'name' => $tenant?->name ?? 'Unknown Tenant',
                    'usage' => $usageCount,
                    'cost' => (float) $cost['amount'],
                ];
            });
    }

    private function atRiskTenantCount(Carbon $date): int
    {
        return Tenant::query()
            ->where('status', 'active')
            ->where(function ($query) use ($date): void {
                $query
                    ->whereBetween('trial_ends_at', [$date->copy()->startOfDay(), $date->copy()->addDays(14)->endOfDay()])
                    ->orWhere('subscription_status', 'past_due')
                    ->orWhere('subscription_status', 'unpaid')
                    ->orWhere('subscription_status', 'canceled');
            })
            ->count();
    }
}