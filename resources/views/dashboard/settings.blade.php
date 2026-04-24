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
                        <label>LINE Basic ID</label>
                        <input name="line_basic_id"
                               type="text"
                               class="form-control @error('line_basic_id') is-invalid @enderror"
                               placeholder="@mesukdome"
                               value="{{ old('line_basic_id', $tenant?->line_basic_id) }}">
                        <small class="form-text text-muted">ใช้สร้างปุ่ม Add Friend และ QR สำหรับ onboarding ผู้เช่า ควรเป็น Basic ID ที่ขึ้นต้นด้วย @</small>
                        @error('line_basic_id')
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
                        <input type="text" class="form-control" value="{{ old('line_webhook_url', $tenant?->line_webhook_url ?? route('api.line.webhook')) }}" readonly>
                        <small class="form-text text-muted">นำ URL นี้ไปใส่ใน LINE Developers Console -> Messaging API -> Webhook URL</small>
                    </div>

                    @if($tenant?->lineAddFriendUrl())
                        <div class="border rounded bg-light mt-3 p-3">
                            <div class="font-weight-bold">Resident Add Friend Preview</div>
                            <div class="small text-muted mt-1">ลิงก์นี้ใช้สำหรับ Step 1 ให้ผู้เช่าเพิ่มเพื่อนก่อนเริ่มขั้นตอนยืนยันห้องพัก</div>
                            <div class="mt-2">
                                <input type="text" class="form-control" value="{{ $tenant->lineAddFriendUrl() }}" readonly>
                            </div>
                            <div class="mt-3 d-flex flex-wrap align-items-center" style="gap:16px;">
                                <div class="border rounded bg-white p-2" style="width:160px;">
                                    {!! app(\App\Services\QrCodeService::class)->generateSvg($tenant->lineAddFriendUrl(), 140) !!}
                                </div>
                                <div>
                                    <a href="{{ $tenant->lineAddFriendUrl() }}" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fab fa-line mr-1"></i> Open Add Friend Link
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="alert alert-light border mt-3 mb-0 small">
                        LINE credentials จะถูกเข้ารหัสเมื่อบันทึกลงฐานข้อมูล และระบบจะบันทึก Webhook URL ล่าสุดให้อัตโนมัติ
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>Support Contact Name</label>
                        <input name="support_contact_name"
                               type="text"
                               class="form-control @error('support_contact_name') is-invalid @enderror"
                               placeholder="ชื่อผู้ดูแล / เจ้าของหอ"
                               value="{{ old('support_contact_name', $tenant?->support_contact_name) }}">
                    </div>
                    <div class="form-group">
                        <label>Support Phone</label>
                        <input name="support_contact_phone"
                               type="text"
                               class="form-control @error('support_contact_phone') is-invalid @enderror"
                               placeholder="เบอร์โทรติดต่อ"
                               value="{{ old('support_contact_phone', $tenant?->support_contact_phone) }}">
                    </div>
                    <div class="form-group mb-0">
                        <label>Support LINE ID</label>
                        <input name="support_line_id"
                               type="text"
                               class="form-control @error('support_line_id') is-invalid @enderror"
                               placeholder="LINE ID สำหรับติดต่อ"
                               value="{{ old('support_line_id', $tenant?->support_line_id) }}">
                        <small class="form-text text-muted">ใช้ตอบกลับเมื่อผู้เช่ากดเมนู ติดต่อเจ้าของ ใน LINE Rich Menu</small>
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-tint mr-2"></i>Default Utilities</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Default Water Fee / Unit</label>
                                <input name="default_water_fee"
                                       type="number"
                                       min="0"
                                       step="0.01"
                                       class="form-control @error('default_water_fee') is-invalid @enderror"
                                       value="{{ old('default_water_fee', $tenant?->default_water_fee ?? 0) }}">
                                <small class="form-text text-muted">ราคาค่าน้ำต่อ 1 หน่วย</small>
                                @error('default_water_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Default Electricity Fee / Unit</label>
                                <input name="default_electricity_fee"
                                       type="number"
                                       min="0"
                                       step="0.01"
                                       class="form-control @error('default_electricity_fee') is-invalid @enderror"
                                       value="{{ old('default_electricity_fee', $tenant?->default_electricity_fee ?? 0) }}">
                                <small class="form-text text-muted">ราคาค่าไฟต่อ 1 หน่วย</small>
                                @error('default_electricity_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Billing Automation</h3></div>
                <div class="card-body">

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="d-block mb-2" style="min-height: 2.2rem; font-size: 0.85rem; line-height: 1.2;">Utility Reminder Day</label>
                                <input name="utility_entry_reminder_day"
                                       type="number"
                                       min="1"
                                       max="31"
                                       class="form-control @error('utility_entry_reminder_day') is-invalid @enderror"
                                       value="{{ old('utility_entry_reminder_day', $tenant?->utility_entry_reminder_day ?? 25) }}">
                                <small class="form-text text-muted">วันที่ระบบเตือนเจ้าของให้บันทึกหน่วยค่าน้ำ ค่าไฟ และค่าใช้จ่ายอื่นๆ</small>
                                @error('utility_entry_reminder_day')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="d-block mb-2" style="min-height: 2.2rem; font-size: 0.85rem; line-height: 1.2;">Invoice Create Day</label>
                                <input name="invoice_generate_day"
                                       type="number"
                                       min="1"
                                       max="31"
                                       class="form-control @error('invoice_generate_day') is-invalid @enderror"
                                       value="{{ old('invoice_generate_day', $tenant?->invoice_generate_day ?? 1) }}">
                                <small class="form-text text-muted">วันที่ระบบสร้างบิลอัตโนมัติให้ทุกสัญญาที่ active</small>
                                @error('invoice_generate_day')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="d-block mb-2" style="min-height: 2.2rem; font-size: 0.85rem; line-height: 1.2;">Invoice Send Day</label>
                                <input name="invoice_send_day"
                                       type="number"
                                       min="1"
                                       max="31"
                                       class="form-control @error('invoice_send_day') is-invalid @enderror"
                                       value="{{ old('invoice_send_day', $tenant?->invoice_send_day ?? 2) }}">
                                <small class="form-text text-muted">วันที่ระบบส่งลิงก์บิลให้ผู้เช่า</small>
                                @error('invoice_send_day')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="d-block mb-2" style="min-height: 2.2rem; font-size: 0.85rem; line-height: 1.2;">Invoice Due Day</label>
                                <input name="invoice_due_day"
                                       type="number"
                                       min="1"
                                       max="31"
                                       class="form-control @error('invoice_due_day') is-invalid @enderror"
                                       value="{{ old('invoice_due_day', $tenant?->invoice_due_day) }}"
                                       placeholder="เช่น 4">
                                <small class="form-text text-muted">ถ้าระบุ ระบบจะใช้เป็นวันครบกำหนดของเดือนถัดไปสำหรับ tenant นี้</small>
                                @error('invoice_due_day')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Invoice Send Channel</label>
                                <select name="invoice_send_channels" class="form-control @error('invoice_send_channels') is-invalid @enderror">
                                    <option value="line" @selected(old('invoice_send_channels', $tenant?->invoice_send_channels ?? 'line') === 'line')>LINE</option>
                                    <option value="email" @selected(old('invoice_send_channels', $tenant?->invoice_send_channels ?? 'line') === 'email')>Email</option>
                                    <option value="both" @selected(old('invoice_send_channels', $tenant?->invoice_send_channels ?? 'line') === 'both')>LINE + Email</option>
                                </select>
                                @error('invoice_send_channels')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Overdue Reminder Delay</label>
                                <div class="input-group">
                                    <input name="overdue_reminder_after_days"
                                           type="number"
                                           min="1"
                                           max="31"
                                           class="form-control @error('overdue_reminder_after_days') is-invalid @enderror"
                                           value="{{ old('overdue_reminder_after_days', $tenant?->overdue_reminder_after_days ?? 1) }}">
                                    <div class="input-group-append"><span class="input-group-text">days overdue</span></div>
                                    @error('overdue_reminder_after_days')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>Overdue Reminder Channel</label>
                        <select name="overdue_reminder_channels" class="form-control @error('overdue_reminder_channels') is-invalid @enderror">
                            <option value="line" @selected(old('overdue_reminder_channels', $tenant?->overdue_reminder_channels ?? 'line') === 'line')>LINE</option>
                            <option value="email" @selected(old('overdue_reminder_channels', $tenant?->overdue_reminder_channels ?? 'line') === 'email')>Email</option>
                            <option value="both" @selected(old('overdue_reminder_channels', $tenant?->overdue_reminder_channels ?? 'line') === 'both')>LINE + Email</option>
                        </select>
                        <small class="form-text text-muted">ใช้ช่องทางนี้เมื่อบิลเลยกำหนดชำระตามจำนวนวันที่ตั้งไว้</small>
                        @error('overdue_reminder_channels')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- การแจ้งเตือนเจ้าของหอ --}}
            @php
                $platformDefaults = $platformDefaults ?? \App\Models\PlatformSetting::current();
                $notifyEvents = [
                    'payment_received'      => ['ผู้เช่าชำระเงินสำเร็จ', 'ส่งทันทีหลังเจ้าของอนุมัติ slip หรือบันทึกการชำระ'],
                    'utility_reminder_day'  => ['วันแจ้งเตือนบันทึกค่าน้ำ-ไฟ', 'ตามวันใน Utility Entry Reminder Day'],
                    'invoice_create_day'    => ['วันสร้างใบแจ้งหนี้ประจำเดือน', 'ตามวันใน Invoice Generate Day'],
                    'invoice_send_day'      => ['วันส่งใบแจ้งหนี้ให้ผู้เช่า', 'ตามวันใน Invoice Send Day'],
                    'overdue_digest'        => ['สรุปบิลค้างชำระประจำวัน', 'ส่งรายการผู้ค้างชำระ ≤10 รายการต่อวัน'],
                ];
                $overrideToValue = function (?bool $v): string {
                    if ($v === null) return 'inherit';
                    return $v ? 'on' : 'off';
                };
            @endphp
            <div class="card card-outline card-warning mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-bell mr-2"></i>การแจ้งเตือนเจ้าของหอ (LINE)</h3></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        เลือก <strong>"ใช้ค่าจากแอดมิน"</strong> เพื่อใช้ default จากระบบ. ถ้าเลือก เปิด/ปิด จะ override เฉพาะหอนี้.
                    </p>
                    @foreach($notifyEvents as $eventKey => [$label, $hint])
                        @php
                            $current = old("notify_owner_{$eventKey}", $overrideToValue($tenant?->{"notify_owner_{$eventKey}"}));
                            $defaultEnabled = (bool) ($platformDefaults->{"default_notify_owner_{$eventKey}"} ?? true);
                        @endphp
                        <div class="form-group row align-items-center mb-2">
                            <label class="col-md-5 col-form-label mb-0">
                                {{ $label }}
                                <small class="d-block text-muted">{{ $hint }}</small>
                            </label>
                            <div class="col-md-7">
                                <select name="notify_owner_{{ $eventKey }}" class="form-control form-control-sm">
                                    <option value="inherit" @selected($current === 'inherit')>ใช้ค่าจากแอดมิน ({{ $defaultEnabled ? 'เปิด' : 'ปิด' }})</option>
                                    <option value="on"      @selected($current === 'on')>เปิดเฉพาะหอนี้</option>
                                    <option value="off"     @selected($current === 'off')>ปิดเฉพาะหอนี้</option>
                                </select>
                            </div>
                        </div>
                    @endforeach

                    @php
                        $channelCurrent = old('notify_owner_channels', $tenant?->notify_owner_channels ?? 'inherit') ?: 'inherit';
                        $channelDefault = (string) ($platformDefaults->default_notify_owner_channels ?? 'line');
                    @endphp
                    <div class="form-group row align-items-center mb-0">
                        <label class="col-md-5 col-form-label mb-0">ช่องทางการแจ้งเตือน</label>
                        <div class="col-md-7">
                            <select name="notify_owner_channels" class="form-control form-control-sm">
                                <option value="inherit" @selected($channelCurrent === 'inherit')>ใช้ค่าจากแอดมิน ({{ $channelDefault }})</option>
                                <option value="line"    @selected($channelCurrent === 'line')>LINE</option>
                                <option value="email"   @selected($channelCurrent === 'email')>Email</option>
                                <option value="both"    @selected($channelCurrent === 'both')>LINE + Email</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i> Save Settings</button>
            </div>
        </form>

        {{-- ผูก LINE ของเจ้าของหอ --}}
        <div class="card card-outline card-success mb-4">
            <div class="card-header"><h3 class="card-title"><i class="fab fa-line mr-2"></i>ผูก LINE ของเจ้าของหอ (สำหรับรับการแจ้งเตือน)</h3></div>
            <div class="card-body">
                @if(auth()->user()?->hasLinkedTenantLine())
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <span class="badge badge-success"><i class="fas fa-check"></i> ผูกแล้ว</span>
                            <small class="text-muted ml-2">ผูกเมื่อ {{ auth()->user()->line_linked_at?->format('d/m/Y H:i') }}</small>
                        </div>
                        <form method="POST" action="{{ route('app.owner-line.unlink') }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('ยกเลิกการผูก LINE?')"><i class="fas fa-unlink mr-1"></i> ยกเลิกการผูก</button>
                        </form>
                    </div>
                @elseif(session('owner_line_link'))
                    @php($linkInfo = session('owner_line_link'))
                    <div class="alert alert-warning mb-2">
                        <h5 class="mb-2"><i class="fas fa-key mr-1"></i> รหัสผูก LINE:</h5>
                        <div class="d-inline-flex align-items-center px-3 py-2 rounded" style="background:#1f2937; border:2px solid #f59e0b;">
                            <code class="mb-0 h4 font-weight-bold" style="color:#fef3c7; letter-spacing:0.08em; background:transparent;">OWNER:{{ $linkInfo['token'] }}</code>
                        </div>
                        <p class="mb-1">{{ $linkInfo['instruction'] }}</p>
                        <small>หมดอายุ: {{ $linkInfo['expires_at'] }}</small>
                        @if(!empty($linkInfo['add_friend_url']))
                            <div class="mt-2">
                                <a href="{{ $linkInfo['add_friend_url'] }}" target="_blank" class="btn btn-success btn-sm"><i class="fab fa-line mr-1"></i> เพิ่มเพื่อน LINE OA</a>
                            </div>
                        @endif
                    </div>
                @elseif($ownerActiveLink ?? null)
                    <div class="alert alert-info mb-2">
                        <small class="d-block mb-2">มีรหัสผูกที่ยังไม่ได้ใช้:</small>
                        <div class="d-inline-flex align-items-center px-3 py-2 rounded" style="background:#0f172a; border:2px solid #38bdf8;">
                            <code class="mb-0 h5 font-weight-bold" style="color:#e0f2fe; letter-spacing:0.08em; background:transparent;">OWNER:{{ $ownerActiveLink->link_token }}</code>
                        </div>
                        <small class="d-block mt-2">หมดอายุ {{ $ownerActiveLink->expired_at->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <p class="text-muted small mb-2">กดปุ่มด้านล่างเพื่อสร้างรหัส 6 หลัก แล้วพิมพ์ลง LINE OA ของหอเพื่อผูก LINE ของคุณ (เจ้าของหอ) สำหรับรับการแจ้งเตือน</p>
                @endif
                <form method="POST" action="{{ route('app.owner-line.link-token') }}">
                    @csrf
                    <button class="btn btn-success" @disabled(auth()->user()?->hasLinkedTenantLine())>
                        <i class="fas fa-link mr-1"></i>
                        @if(auth()->user()?->hasLinkedTenantLine()) ผูกแล้ว @else สร้างรหัสผูก LINE ({{ $ownerLinkTtlMinutes ?? 30 }} นาที) @endif
                    </button>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('app.settings.line-rich-menu.sync') }}" class="mb-4">
            @csrf
            <button class="btn btn-outline-success btn-lg"><i class="fab fa-line mr-1"></i> Sync Resident Rich Menu</button>
            @if($tenant?->line_rich_menu_id)
                <div class="small text-muted mt-2">Current rich menu: {{ $tenant->line_rich_menu_id }}</div>
            @endif
        </form>
    </div>
</div>
@endsection

