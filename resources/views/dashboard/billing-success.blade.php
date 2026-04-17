@extends('layouts.adminlte')

@section('content')
<div class="card card-success">
    <div class="card-header"><h3 class="card-title">Subscription Activated</h3></div>
    <div class="card-body">
        <p>Your subscription has been activated. You can now continue using the system with full access based on your package.</p>
        @if($tenant)
            <div class="mt-3">
                <div><strong>Tenant:</strong> {{ $tenant->name }}</div>
                <div><strong>Status:</strong> {{ $tenant->subscription_status ?? '-' }}</div>
                <div><strong>Period end:</strong> {{ optional($tenant->subscription_current_period_end)->format('d/m/Y H:i') }}</div>
            </div>
        @endif
    </div>
    <div class="card-footer">
        <a href="{{ route('app.dashboard') }}" class="btn btn-success">Go to Dashboard</a>
    </div>
</div>
@endsection
