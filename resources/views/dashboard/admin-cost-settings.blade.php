@extends('layouts.adminlte', ['title' => 'Cost Settings', 'heading' => 'Cost Settings'])

@section('content')
@php
    $costTypeGuide = [
        [
            'title' => 'ต่อหน่วย',
            'summary' => 'คิดต้นทุนต่อครั้งหรือต่อหน่วยใช้งาน',
            'examples' => 'เหมาะกับ slip verification, LINE ต่อข้อความ, Email ต่อฉบับ',
            'formula' => 'จำนวนใช้งาน x Unit Cost',
        ],
        [
            'title' => 'ตามเปอร์เซ็นต์',
            'summary' => 'คิดตามยอดเงิน และ/หรือ ค่าคงที่ต่อรายการ',
            'examples' => 'เหมาะกับ Stripe เช่น 3.65% + 10 บาท/รายการ หรือ 0.28 บาท/รายการ',
            'formula' => '(ยอดเงิน x %) + (จำนวนรายการ x Fixed Fee)',
        ],
        [
            'title' => 'รายเดือนคงที่',
            'summary' => 'คิดต้นทุนคงที่ต่อเดือน',
            'examples' => 'เหมาะกับ Hosting, Server, SaaS tools, ค่า support รายเดือน',
            'formula' => 'Fixed Fee ต่อเดือน',
        ],
        [
            'title' => 'ผสมหลายเงื่อนไข',
            'summary' => 'ใช้เมื่อมีหลายเงื่อนไขรวมกัน',
            'examples' => 'เช่นมีทั้งค่าต่อหน่วยและเปอร์เซ็นต์ หรือ % + ค่าคงที่ต่อรายการ',
            'formula' => 'Unit Cost + Percentage + Fixed Fee',
        ],
    ];

    $recommendedPresets = [
        [
            'label' => 'Hosting รายปี 1,200 บาท',
            'hint' => 'แปลงเป็น 100 บาท/เดือน ก่อนบันทึกเข้าระบบ',
            'provider' => 'hosting',
            'cost_type' => 'fixed_monthly',
            'unit_cost' => '0',
            'percentage_rate' => '0',
            'fixed_fee' => '100',
            'included_quota' => '0',
            'overage_unit_cost' => '0',
            'currency' => 'THB',
            'notes' => 'ค่า Hosting รายปี 1,200 บาท กระจายเป็นต้นทุนรายเดือน 100 บาท',
        ],
        [
            'label' => 'Slip verification API 1 บาท/ครั้ง',
            'hint' => 'คิดต้นทุนตรงตามจำนวนการตรวจสลิป',
            'provider' => 'slipok',
            'cost_type' => 'per_unit',
            'unit_cost' => '1',
            'percentage_rate' => '0',
            'fixed_fee' => '0',
            'included_quota' => '0',
            'overage_unit_cost' => '0',
            'currency' => 'THB',
            'notes' => 'ต้นทุน slip verification API ที่ 1 บาทต่อการตรวจสลิป 1 ครั้ง',
        ],
        [
            'label' => 'Stripe 0.28 บาท/รายการ',
            'hint' => 'ถ้าคิดต่อ transaction อย่างเดียว ให้ใช้ Percentage แล้วตั้ง % = 0',
            'provider' => 'stripe',
            'cost_type' => 'percentage',
            'unit_cost' => '0',
            'percentage_rate' => '0',
            'fixed_fee' => '0.28',
            'included_quota' => '0',
            'overage_unit_cost' => '0',
            'currency' => 'THB',
            'notes' => 'ค่าธรรมเนียม Stripe ที่ 0.28 บาทต่อรายการที่ชำระสำเร็จ',
        ],
        [
            'label' => 'LINE 0.15 บาท/ข้อความ',
            'hint' => 'ตัวอย่าง baseline สำหรับต้นทุนต่อข้อความ',
            'provider' => 'line',
            'cost_type' => 'per_unit',
            'unit_cost' => '0.15',
            'percentage_rate' => '0',
            'fixed_fee' => '0',
            'included_quota' => '0',
            'overage_unit_cost' => '0',
            'currency' => 'THB',
            'notes' => 'ต้นทุนข้อความ LINE OA ตั้งต้นที่ 0.15 บาทต่อข้อความ',
        ],
        [
            'label' => 'Email 0.05 บาท/ฉบับ',
            'hint' => 'ตัวอย่าง baseline สำหรับ transactional email',
            'provider' => 'email',
            'cost_type' => 'per_unit',
            'unit_cost' => '0.05',
            'percentage_rate' => '0',
            'fixed_fee' => '0',
            'included_quota' => '0',
            'overage_unit_cost' => '0',
            'currency' => 'THB',
            'notes' => 'ต้นทุนส่งอีเมลตั้งต้นที่ 0.05 บาทต่อฉบับ',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">วิธีบันทึกต้นทุน</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-4">
                    <strong>ค่าใช้จ่ายรายปีต้องแปลงเป็นรายเดือนก่อนบันทึกเสมอ</strong>
                    ตัวอย่าง: Hosting จ่ายปีละ 1,200 บาท ควรบันทึกเป็น 100 บาทใน <strong>Fixed Monthly</strong> ไม่ใช่ 1,200 บาท
                </div>
                <div class="row">
                    @foreach($costTypeGuide as $guide)
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="border rounded h-100 p-3 bg-light">
                                <h4 class="h6 font-weight-bold mb-2">{{ $guide['title'] }}</h4>
                                <p class="text-sm mb-2">{{ $guide['summary'] }}</p>
                                <p class="text-muted small mb-2">{{ $guide['examples'] }}</p>
                                <div class="small"><strong>Formula:</strong> {{ $guide['formula'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title">ชุดค่าตั้งต้นแนะนำ</h3></div>
            <div class="card-body">
                <p class="text-muted">กดใช้ preset เพื่อเติมค่าลงฟอร์มก่อนแก้ไข หรือกดบันทึกได้ทันทีด้วยค่าตั้งต้นชุดนั้น</p>
                <div class="mb-2 small text-muted">มีชุดแนะนำสำหรับ Hosting, slip verification, Stripe, LINE และ Email</div>
                @foreach($recommendedPresets as $preset)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div class="pr-3 mb-2">
                                <div class="font-weight-bold">{{ $preset['label'] }}</div>
                                <div class="small text-muted">{{ $preset['hint'] }}</div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                    data-cost-preset
                                    data-provider="{{ $preset['provider'] }}"
                                    data-cost-type="{{ $preset['cost_type'] }}"
                                    data-unit-cost="{{ $preset['unit_cost'] }}"
                                    data-percentage-rate="{{ $preset['percentage_rate'] }}"
                                    data-fixed-fee="{{ $preset['fixed_fee'] }}"
                                    data-included-quota="{{ $preset['included_quota'] }}"
                                    data-overage-unit-cost="{{ $preset['overage_unit_cost'] }}"
                                    data-currency="{{ $preset['currency'] }}"
                                    data-notes="{{ $preset['notes'] }}"
                                >ใช้ค่าชุดนี้</button>
                                <form method="POST" action="{{ route('admin.cost-settings.store') }}" class="mb-2">
                                    @csrf
                                    <input type="hidden" name="provider" value="{{ $preset['provider'] }}">
                                    <input type="hidden" name="cost_type" value="{{ $preset['cost_type'] }}">
                                    <input type="hidden" name="unit_cost" value="{{ $preset['unit_cost'] }}">
                                    <input type="hidden" name="percentage_rate" value="{{ $preset['percentage_rate'] }}">
                                    <input type="hidden" name="fixed_fee" value="{{ $preset['fixed_fee'] }}">
                                    <input type="hidden" name="included_quota" value="{{ $preset['included_quota'] }}">
                                    <input type="hidden" name="overage_unit_cost" value="{{ $preset['overage_unit_cost'] }}">
                                    <input type="hidden" name="currency" value="{{ $preset['currency'] }}">
                                    <input type="hidden" name="effective_from" value="{{ now()->toDateString() }}">
                                    <input type="hidden" name="is_active" value="1">
                                    <input type="hidden" name="notes" value="{{ $preset['notes'] }}">
                                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('บันทึก preset นี้ทันที?');">บันทึก preset นี้ทันที</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Add Cost Input</h3></div>
            <form method="POST" action="{{ route('admin.cost-settings.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="provider">Provider</label>
                        <select id="provider" name="provider" class="form-control" required>
                            @foreach($providers as $provider)
                                <option value="{{ $provider }}" @selected(old('provider') === $provider)>{{ str($provider)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cost_type">Cost Type</label>
                        <select id="cost_type" name="cost_type" class="form-control" required>
                            @foreach($costTypes as $costType)
                                <option value="{{ $costType }}" @selected(old('cost_type') === $costType)>{{ str($costType)->headline() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="unit_cost">Unit Cost</label>
                            <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.0001" value="{{ old('unit_cost', 0) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="percentage_rate">Percentage Rate</label>
                            <input id="percentage_rate" name="percentage_rate" type="number" min="0" max="100" step="0.0001" value="{{ old('percentage_rate', 0) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="fixed_fee">Fixed Fee</label>
                            <input id="fixed_fee" name="fixed_fee" type="number" min="0" step="0.0001" value="{{ old('fixed_fee', 0) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="included_quota">Included Quota</label>
                            <input id="included_quota" name="included_quota" type="number" min="0" step="1" value="{{ old('included_quota', 0) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="overage_unit_cost">Overage Unit Cost</label>
                            <input id="overage_unit_cost" name="overage_unit_cost" type="number" min="0" step="0.0001" value="{{ old('overage_unit_cost', 0) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="currency">Currency</label>
                            <input id="currency" name="currency" value="{{ old('currency', 'THB') }}" maxlength="10" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="effective_from">Effective From</label>
                            <input id="effective_from" name="effective_from" type="date" value="{{ old('effective_from', now()->toDateString()) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="effective_to">Effective To</label>
                            <input id="effective_to" name="effective_to" type="date" value="{{ old('effective_to') }}" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active" checked>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save Cost Setting</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Current Cost Settings</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Type</th>
                            <th class="text-right">Unit</th>
                            <th class="text-right">%</th>
                            <th class="text-right">Fixed</th>
                            <th>Effective</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($costSettings as $setting)
                        <tr>
                            <td>{{ $setting->providerLabel() }}</td>
                            <td>{{ str($setting->cost_type)->headline() }}</td>
                            <td class="text-right">{{ number_format((float) $setting->unit_cost, 4) }}</td>
                            <td class="text-right">{{ number_format((float) $setting->percentage_rate, 4) }}</td>
                            <td class="text-right">{{ number_format((float) $setting->fixed_fee, 4) }}</td>
                            <td>{{ $setting->effective_from?->format('Y-m-d') }} - {{ $setting->effective_to?->format('Y-m-d') ?? 'open' }}</td>
                            <td><span class="badge badge-{{ $setting->is_active ? 'success' : 'secondary' }}">{{ $setting->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-right">
                                @if($setting->is_active)
                                    <form method="POST" action="{{ route('admin.cost-settings.deactivate', $setting) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-xs btn-outline-danger" onclick="return confirm('Deactivate this cost setting?');">Deactivate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">No cost settings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formFields = {
        provider: document.getElementById('provider'),
        costType: document.getElementById('cost_type'),
        unitCost: document.getElementById('unit_cost'),
        percentageRate: document.getElementById('percentage_rate'),
        fixedFee: document.getElementById('fixed_fee'),
        includedQuota: document.getElementById('included_quota'),
        overageUnitCost: document.getElementById('overage_unit_cost'),
        currency: document.getElementById('currency'),
        notes: document.getElementById('notes'),
    };

    document.querySelectorAll('[data-cost-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            formFields.provider.value = button.dataset.provider;
            formFields.costType.value = button.dataset.costType;
            formFields.unitCost.value = button.dataset.unitCost;
            formFields.percentageRate.value = button.dataset.percentageRate;
            formFields.fixedFee.value = button.dataset.fixedFee;
            formFields.includedQuota.value = button.dataset.includedQuota;
            formFields.overageUnitCost.value = button.dataset.overageUnitCost;
            formFields.currency.value = button.dataset.currency;
            formFields.notes.value = button.dataset.notes;
        });
    });
});
</script>
@endsection