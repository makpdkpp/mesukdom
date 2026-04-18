@extends('layouts.adminlte')

@section('content')
@if($tenant && $tenant->status === 'pending_checkout')
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Waiting for Payment</h4>
        <p>Your package is ready but requires payment to activate. Please complete the checkout process to start using all features.</p>
        <form method="POST" action="{{ route('app.billing.checkout') }}" class="d-inline">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $tenant->plan_id }}">
            <button type="submit" class="btn btn-sm btn-warning">Proceed to Checkout</button>
        </form>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Billing Error</h4>
        <p class="mb-0">{{ $errors->first() }}</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="small-box bg-info">
            <div class="inner">
                <p class="mb-1">Current Package</p>
                <h3>{{ $currentPlan?->name ?? ucfirst((string) $tenant?->plan) ?: '-' }}</h3>
                <p class="mb-0">{{ $currentPlan ? number_format((float) $currentPlan->price_monthly, 2) : '0.00' }} THB / month</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="small-box bg-success">
            <div class="inner">
                <p class="mb-1">Subscription Status</p>
                <h3>{{ ucfirst((string) ($tenant?->subscription_status ?? $tenant?->status ?? '-')) }}</h3>
                <p class="mb-0">Tenant status: {{ ucfirst((string) ($tenant?->status ?? '-')) }}</p>
            </div>
            <div class="icon"><i class="fas fa-credit-card"></i></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <p class="mb-1">Next Renewal</p>
                <h3>{{ optional($tenant?->subscription_current_period_end)->format('d/m/Y') ?: '-' }}</h3>
                <p class="mb-0">{{ optional($tenant?->subscription_current_period_end)->format('H:i') ?: 'Waiting for Stripe confirmation' }}</p>
            </div>
            <div class="icon"><i class="fas fa-calendar-alt"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Billing</h3>
        <div>
            <form method="POST" action="{{ route('app.billing.portal') }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-primary" @disabled(! $billingPortalAvailable)>Manage Subscription</button>
            </form>
        </div>
    </div>

    <div class="card-body border-bottom bg-light">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="text-muted small text-uppercase">Stripe Customer</div>
                <div class="font-weight-semibold">{{ $tenant?->stripe_customer_id ?: '-' }}</div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="text-muted small text-uppercase">Stripe Subscription</div>
                <div class="font-weight-semibold">{{ $tenant?->stripe_subscription_id ?: '-' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase">Included Rooms</div>
                <div class="font-weight-semibold">{{ $currentPlan?->roomsLimit() ?: 'Unlimited' }}</div>
            </div>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Status</th>
                    <th>Amount Due</th>
                    <th>Period</th>
                    <th>Issued</th>
                    <th>Paid</th>
                    <th>Links</th>
                </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->stripe_invoice_id }}</td>
                    <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'secondary' }}">{{ $invoice->status ?? '-' }}</span></td>
                    <td>{{ is_null($invoice->amount_due) ? '-' : number_format($invoice->amount_due / 100, 2) }} {{ strtoupper($invoice->currency ?? '') }}</td>
                    <td>
                        {{ optional($invoice->period_start)->format('d/m/Y') }}
                        -
                        {{ optional($invoice->period_end)->format('d/m/Y') }}
                    </td>
                    <td>{{ optional($invoice->issued_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($invoice->paid_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($invoice->hosted_invoice_url)
                            <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" class="btn btn-xs btn-outline-secondary">Hosted</a>
                        @endif
                        @if($invoice->invoice_pdf_url)
                            <a href="{{ $invoice->invoice_pdf_url }}" target="_blank" class="btn btn-xs btn-outline-secondary">PDF</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <div class="mb-1">No SaaS invoices yet</div>
                        <div class="small">Invoices from Stripe will appear here after the first billing cycle or once Stripe finalizes an invoice.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
