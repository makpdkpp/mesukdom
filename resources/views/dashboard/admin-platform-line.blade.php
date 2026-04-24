@extends('layouts.adminlte')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card card-success card-outline">
            <div class="card-header"><h3 class="card-title"><i class="fab fa-line mr-2"></i>Platform LINE OA Credentials</h3></div>
            <form method="POST" action="{{ route('admin.platform-line.settings.update') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Channel ID</label>
                        <input name="platform_line_channel_id" class="form-control" value="{{ old('platform_line_channel_id', $platformSetting->platform_line_channel_id) }}">
                    </div>
                    <div class="form-group">
                        <label>Basic ID</label>
                        <input name="platform_line_basic_id" class="form-control" value="{{ old('platform_line_basic_id', $platformSetting->platform_line_basic_id) }}" placeholder="@meesukdorm-admin">
                    </div>
                    <div class="form-group">
                        <label>Access Token</label>
                        <input name="platform_line_channel_access_token" type="password" class="form-control" placeholder="Leave unchanged to keep current token">
                    </div>
                    <div class="form-group">
                        <label>Channel Secret</label>
                        <input name="platform_line_channel_secret" type="password" class="form-control" placeholder="Leave unchanged to keep current secret">
                    </div>
                    <div class="form-group mb-0">
                        <label>Webhook URL</label>
                        <input class="form-control" value="{{ route('api.line.platform-webhook') }}" readonly>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-success">Save Platform LINE Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-warning card-outline">
            <div class="card-header"><h3 class="card-title">ผูก LINE ของแอดมิน</h3></div>
            <div class="card-body">
                @if(auth()->user()?->hasLinkedPlatformLine())
                    <div class="mb-3">
                        <span class="badge badge-success">ผูกแล้ว</span>
                        <small class="text-muted ml-2">{{ auth()->user()->platform_line_linked_at?->format('d/m/Y H:i') }}</small>
                    </div>
                    <form method="POST" action="{{ route('admin.platform-line.unlink') }}">
                        @csrf
                        <button class="btn btn-outline-danger btn-sm">ยกเลิกการผูก</button>
                    </form>
                @elseif(session('platform_line_link'))
                    @php($link = session('platform_line_link'))
                    <div class="alert alert-warning mb-3">
                        <div><strong>รหัสผูก:</strong> ADMIN:{{ $link['token'] }}</div>
                        <div>{{ $link['instruction'] }}</div>
                        <div class="small">หมดอายุ {{ $link['expires_at'] }}</div>
                        @if(!empty($link['add_friend_url']))
                            <div class="mt-2"><a href="{{ $link['add_friend_url'] }}" class="btn btn-success btn-sm" target="_blank">เพิ่มเพื่อน Platform OA</a></div>
                        @endif
                    </div>
                @elseif($platformActiveLink)
                    <div class="alert alert-info">มีรหัสที่ยังไม่ถูกใช้: ADMIN:{{ $platformActiveLink->link_token }} (หมดอายุ {{ $platformActiveLink->expired_at->format('d/m/Y H:i') }})</div>
                @endif

                <form method="POST" action="{{ route('admin.platform-line.link-token') }}">
                    @csrf
                    <button class="btn btn-warning" @disabled(auth()->user()?->hasLinkedPlatformLine())>สร้างรหัสผูก Platform LINE</button>
                </form>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">Broadcast to Owners</h3></div>
            <form method="POST" action="{{ route('admin.platform-line.broadcast') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Recipient Filter</label>
                        <select name="recipient_filter" class="form-control">
                            <option value="all">All owners</option>
                            <option value="plan">By plan</option>
                            <option value="status">By tenant status</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Plan ID (when filter=plan)</label>
                        <input name="plan_id" type="number" class="form-control" value="{{ old('plan_id') }}">
                    </div>
                    <div class="form-group">
                        <label>Tenant Status (when filter=status)</label>
                        <select name="tenant_status" class="form-control">
                            <option value="">-</option>
                            <option value="active">active</option>
                            <option value="suspended">suspended</option>
                            <option value="trial">trial</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label>Message</label>
                        <textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Queue Broadcast</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Recent Platform LINE Logs</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead><tr><th>Event</th><th>Target</th><th>Status</th><th>Message</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->event }}</td>
                    <td>{{ $log->target }}</td>
                    <td>{{ $log->status }}</td>
                    <td>{{ $log->message }}</td>
                    <td>{{ $log->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No platform LINE logs yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection