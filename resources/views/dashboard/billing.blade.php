@extends('layouts.adminlte')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Billing</h3>
        <div>
            <form method="POST" action="{{ route('app.billing.portal') }}" class="d-inline">
                @csrf
                <button class="btn btn-sm btn-outline-primary">Manage Subscription</button>
            </form>
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
                    <td colspan="7" class="text-center text-muted">No SaaS invoices yet</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
