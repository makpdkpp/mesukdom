@extends('layouts.adminlte', ['title' => 'Business Analyst Board', 'heading' => 'Business Analyst Board'])

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $tenantCount }}</h3>
                <p>Total Tenants</p>
            </div>
            <div class="icon"><i class="fas fa-city"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format((float) $collectedRevenue, 0) }}</h3>
                <p>Collected Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format((float) $netRevenue, 0) }}</h3>
                <p>Net Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-dark">
            <div class="inner">
                <h3>{{ $marginPercent }}%</h3>
                <p>Gross Margin</p>
            </div>
            <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Users</span>
                <span class="info-box-number">{{ number_format($activeUsers) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-teal"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Paid Tenants</span>
                <span class="info-box-number">{{ number_format($paidTenants) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-hourglass-half"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Trial Tenants</span>
                <span class="info-box-number">{{ number_format($trialTenants) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Churn Risk Tenants</span>
                <span class="info-box-number">{{ number_format($atRiskTenantCount) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header"><h3 class="card-title">Revenue Quality</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span>Projected MRR</span><strong>{{ number_format((float) $projectedMonthlyRevenue, 2) }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Collected Revenue</span><strong>{{ number_format((float) $collectedRevenue, 2) }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>ARPU</span><strong>{{ number_format((float) $arpu, 2) }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span>Period</span><strong>{{ $periodLabel }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-danger h-100">
            <div class="card-header"><h3 class="card-title">Cost Breakdown</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                    @forelse($costBreakdown as $cost)
                        <tr>
                            <td>{{ $cost['label'] }}</td>
                            <td class="text-right">{{ number_format((float) $cost['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted text-center py-4">No active cost settings yet.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr><th>Total Costs</th><th class="text-right">{{ number_format((float) $totalCosts, 2) }}</th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-info h-100">
            <div class="card-header"><h3 class="card-title">Usage Signals</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span>Slip verification calls this month</span><strong>{{ number_format($slipOkUsageTotal) }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>Stripe Paid Invoices</span><strong>{{ number_format($stripeTransactionCount) }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span>Active Tenants</span><strong>{{ number_format($activeTenants) }}</strong></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Collected Revenue by Plan</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Plan</th><th class="text-right">Tenants</th><th class="text-right">Revenue</th></tr></thead>
                    <tbody>
                    @forelse($revenueByPlan as $planRow)
                        <tr>
                            <td>{{ $planRow['name'] }}</td>
                            <td class="text-right">{{ number_format($planRow['tenants']) }}</td>
                            <td class="text-right">{{ number_format((float) $planRow['revenue'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No plans yet</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Trial Expiring Soon</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Tenant</th><th>Plan</th><th>Trial Ends</th></tr></thead>
                    <tbody>
                    @forelse($trialExpiringTenants as $tenant)
                        <tr>
                            <td>{{ $tenant->name }}</td>
                            <td>{{ $tenant->subscriptionPlan?->name ?? '-' }}</td>
                            <td>{{ $tenant->trial_ends_at?->format('Y-m-d') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No trials expiring in 14 days</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">High-Cost Tenants</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Tenant</th><th class="text-right">Slip verification</th><th class="text-right">Cost</th></tr></thead>
                    <tbody>
                    @forelse($highCostTenants as $tenantCost)
                        <tr>
                            <td>{{ $tenantCost['name'] }}</td>
                            <td class="text-right">{{ number_format($tenantCost['usage']) }}</td>
                            <td class="text-right">{{ number_format((float) $tenantCost['cost'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">No slip verification usage this month</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
