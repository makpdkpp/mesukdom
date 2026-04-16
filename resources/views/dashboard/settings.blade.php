@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('app.settings.update') }}">
            @csrf

            @if(session('status'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('status') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            {{-- PromptPay --}}
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-qrcode mr-2"></i>PromptPay Settings</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>PromptPay Phone / National ID</label>
                        <input name="promptpay_number"
                               class="form-control @error('promptpay_number') is-invalid @enderror"
                               placeholder="e.g. 0812345678 or 0000000000000"
                               value="{{ old('promptpay_number', $tenant?->promptpay_number) }}">
                        <small class="form-text text-muted">Phone number (10 digits) or National ID (13 digits). Leave blank to disable PromptPay QR.</small>
                        @error('promptpay_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($tenant?->promptpay_number)
                    <div class="mt-2">
                        <label class="d-block">Preview QR</label>
                        <img src="data:image/svg+xml;base64,{{ base64_encode(app(\App\Services\PromptPayService::class)->generateSvg($tenant->promptpay_number)) }}"
                             alt="PromptPay QR" width="180" height="180" class="border rounded p-2">
                    </div>
                    @endif
                </div>
            </div>

            {{-- LINE OA --}}
            <div class="card card-success">
                <div class="card-header"><h3 class="card-title"><i class="fab fa-line mr-2"></i>LINE OA Settings</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Channel ID</label>
                        <input name="line_channel_id"
                               type="text"
                               class="form-control @error('line_channel_id') is-invalid @enderror"
                               placeholder="LINE Channel ID"
                               value="{{ old('line_channel_id', $tenant?->line_channel_id) }}">
                        <small class="form-text text-muted">ใช้สำหรับจัดการ LINE Messaging API ของ tenant นี้</small>
                        @error('line_channel_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Channel Access Token</label>
                        <textarea name="line_channel_access_token"
                                  rows="3"
                                  class="form-control @error('line_channel_access_token') is-invalid @enderror"
                                  placeholder="Paste your LINE Messaging API Channel Access Token here">{{ old('line_channel_access_token', $tenant?->line_channel_access_token) }}</textarea>
                        <small class="form-text text-muted">
                            ได้จาก <a href="https://developers.line.biz/console/" target="_blank">LINE Developers Console</a>
                            → Messaging API → Channel access token
                        </small>
                        @error('line_channel_access_token')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <label>Channel Secret</label>
                        <input name="line_channel_secret"
                               type="text"
                               class="form-control @error('line_channel_secret') is-invalid @enderror"
                               placeholder="Channel Secret (32 hex chars)"
                               value="{{ old('line_channel_secret', $tenant?->line_channel_secret) }}">
                        <small class="form-text text-muted">
                            ใช้ verify webhook signature จาก LINE
                        </small>
                        @error('line_channel_secret')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group mb-0 mt-3">
                        <label>Webhook URL</label>
                        <input type="text" class="form-control" value="{{ route('api.line.webhook') }}" readonly>
                        <small class="form-text text-muted">นำ URL นี้ไปใส่ใน LINE Developers Console -> Messaging API -> Webhook URL</small>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i> Save Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection

