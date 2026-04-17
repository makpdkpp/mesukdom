@extends('layouts.adminlte')

@section('content')
<div class="card card-warning">
    <div class="card-header"><h3 class="card-title">Checkout Cancelled</h3></div>
    <div class="card-body">
        <p>Checkout was cancelled. You can try again whenever you're ready.</p>
    </div>
    <div class="card-footer">
        <a href="{{ route('pricing') }}" class="btn btn-outline-secondary">View Plans</a>
        <a href="{{ route('app.dashboard') }}" class="btn btn-warning">Back</a>
    </div>
</div>
@endsection
