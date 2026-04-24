@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bell mr-2"></i>ค่าเริ่มต้นการแจ้งเตือนเจ้าของหอ (Platform Defaults)</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">ค่าเหล่านี้จะใช้กับทุก tenant ที่ <strong>ไม่ได้ override</strong> ใน <code>/app/settings → การแจ้งเตือนเจ้าของหอ</code>.</p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.notifications.update') }}">
                    @csrf
                    @php
                        $events = [
                            'payment_received'      => ['ผู้เช่าชำระเงินสำเร็จ', 'ส่งทันทีหลังเจ้าของอนุมัติ slip หรือบันทึกการชำระ'],
                            'utility_reminder_day'  => ['วันแจ้งเตือนบันทึกค่าน้ำ-ไฟ', 'ตามวันใน Utility Entry Reminder Day ของแต่ละ tenant'],
                            'invoice_create_day'    => ['วันสร้างใบแจ้งหนี้ประจำเดือน', 'ตามวันใน Invoice Generate Day'],
                            'invoice_send_day'      => ['วันส่งใบแจ้งหนี้ให้ผู้เช่า', 'ตามวันใน Invoice Send Day'],
                            'overdue_digest'        => ['สรุปบิลค้างชำระประจำวัน', 'รายการผู้ค้างชำระต่อ tenant'],
                        ];
                    @endphp

                    @foreach($events as $key => [$label, $hint])
                        <div class="form-group row align-items-center mb-2">
                            <label class="col-md-6 col-form-label mb-0">
                                {{ $label }}
                                <small class="d-block text-muted">{{ $hint }}</small>
                            </label>
                            <div class="col-md-6">
                                <div class="custom-control custom-switch custom-switch-on-success">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="default_notify_owner_{{ $key }}"
                                           name="default_notify_owner_{{ $key }}"
                                           value="1"
                                           @checked(old("default_notify_owner_{$key}", $platformSetting->{"default_notify_owner_{$key}"}))>
                                    <label class="custom-control-label" for="default_notify_owner_{{ $key }}">เปิดเป็น default</label>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <hr>

                    <div class="form-group row align-items-center mb-2">
                        <label class="col-md-6 col-form-label mb-0">ช่องทางเริ่มต้น</label>
                        <div class="col-md-6">
                            @php($currentChannel = old('default_notify_owner_channels', $platformSetting->default_notify_owner_channels ?? 'line'))
                            <select name="default_notify_owner_channels" class="form-control form-control-sm">
                                <option value="line"  @selected($currentChannel === 'line')>LINE</option>
                                <option value="email" @selected($currentChannel === 'email')>Email</option>
                                <option value="both"  @selected($currentChannel === 'both')>LINE + Email</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label class="col-md-6 col-form-label mb-0">
                            เปิดใช้ Platform OA Broadcast
                            <small class="d-block text-muted">อนุญาตให้แอดมิน broadcast ไปยังเจ้าของหอทุก tenant ผ่าน Platform LINE OA</small>
                        </label>
                        <div class="col-md-6">
                            <div class="custom-control custom-switch custom-switch-on-warning">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="platform_line_owner_broadcast_enabled"
                                       name="platform_line_owner_broadcast_enabled"
                                       value="1"
                                       @checked(old('platform_line_owner_broadcast_enabled', $platformSetting->platform_line_owner_broadcast_enabled))>
                                <label class="custom-control-label" for="platform_line_owner_broadcast_enabled">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> บันทึกค่าเริ่มต้น</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
