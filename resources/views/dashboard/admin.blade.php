@extends('layouts.adminlte')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Global SlipOK Settings</h3></div>
            <form method="POST" action="{{ route('admin.slipok.settings.update') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group form-check">
                        <input type="hidden" name="slipok_enabled" value="0">
                        <input type="checkbox" class="form-check-input" id="slipok_enabled" name="slipok_enabled" value="1" @checked($platformSetting->slipok_enabled)>
                        <label class="form-check-label" for="slipok_enabled">Enable SlipOK globally</label>
                    </div>
                    <div class="form-group">
                        <label>API URL</label>
                        <input name="slipok_api_url" class="form-control" value="{{ old('slipok_api_url', $platformSetting->slipok_api_url) }}" placeholder="https://connect.slip2go.com/api/verify-slip/qr-base64/info">
                    </div>
                    <div class="form-group">
                        <label>Secret Header Name</label>
                        <input class="form-control" value="Authorization" readonly>
                        <small class="form-text text-muted">ล็อกเป็น Authorization ตามคู่มือปัจจุบัน และระบบจะเติม Bearer ให้อัตโนมัติ</small>
                    </div>
                    <div class="form-group">
                        <label>API Secret</label>
                        <input name="slipok_api_secret" type="password" class="form-control" value="{{ old('slipok_api_secret', $platformSetting->slipok_api_secret) }}" placeholder="Leave unchanged to keep current secret">
                        <small class="form-text text-muted">เก็บเป็น global secret ระดับแพลตฟอร์ม ไม่ผูกกับ tenant</small>
                    </div>
                    <div class="form-group mb-0">
                        <label>Timeout (seconds)</label>
                        <input name="slipok_timeout_seconds" type="number" min="3" max="60" class="form-control" value="{{ old('slipok_timeout_seconds', $platformSetting->slipok_timeout_seconds) }}">
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save Platform Settings</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Package SlipOK Addon Limits</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Plan</th><th>Price</th><th>Addon</th><th>Monthly Limit</th><th></th></tr></thead>
                    <tbody>
                    @foreach($plans as $plan)
                        <tr>
                            <form method="POST" action="{{ route('admin.plans.slipok.update', $plan) }}">
                                @csrf @method('PATCH')
                                <td>{{ $plan->name }}</td>
                                <td>{{ number_format((float) $plan->price_monthly, 0) }}/mo</td>
                                <td>
                                    <input type="hidden" name="slipok_enabled" value="0">
                                    <input type="checkbox" name="slipok_enabled" value="1" @checked($plan->supportsSlipOk())>
                                </td>
                                <td><input type="number" name="slipok_monthly_limit" min="0" class="form-control form-control-sm" value="{{ $plan->slipOkMonthlyLimit() }}"></td>
                                <td><button class="btn btn-xs btn-outline-primary">Save</button></td>
                            </form>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Tenant Management</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead><tr><th>Name</th><th>Domain</th><th>Plan</th><th>SlipOK Usage</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($tenants as $tenant)
                @php($subscriptionPlan = $tenant->resolvedPlan())
                <tr>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->domain }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.tenants.plan.update', $tenant) }}" class="form-inline">
                            @csrf @method('PATCH')
                            <select name="plan_id" class="form-control form-control-sm mr-2">
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected((string) $tenant->plan_id === (string) $plan->id)>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-xs btn-outline-primary">Update</button>
                        </form>
                    </td>
                    <td>
                        @php($used = (int) ($slipOkUsageByTenant[$tenant->id] ?? 0))
                        @php($limit = $subscriptionPlan?->slipOkMonthlyLimit() ?? 0)
                        @if($subscriptionPlan?->supportsSlipOk())
                            <span class="badge badge-info">{{ $used }} / {{ $limit > 0 ? $limit : 'Unlimited' }}</span>
                        @else
                            <span class="badge badge-secondary">Not included</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->status === 'suspended')
                            <span class="badge badge-danger">Suspended</span>
                        @else
                            <span class="badge badge-success">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($tenant->status === 'suspended')
                            <form method="POST" action="{{ route('admin.tenants.unsuspend', $tenant) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-xs btn-success" onclick="return confirm('Reactivate this tenant?')">Unsuspend</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-xs btn-danger" onclick="return confirm('Suspend this tenant?')">Suspend</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">No tenants yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">System Monitoring & Notification Logs</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead><tr><th>Channel</th><th>Event</th><th>Target</th><th>Status</th><th>Message</th></tr></thead>
            <tbody>
            @forelse($notificationLogs as $log)
                <tr>
                    <td>{{ strtoupper($log->channel) }}</td>
                    <td>{{ $log->event }}</td>
                    <td>{{ $log->target }}</td>
                    <td>{{ ucfirst($log->status) }}</td>
                    <td>{{ $log->message }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No logs yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
