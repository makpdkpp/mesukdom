@extends('layouts.adminlte')

@section('content')
@php($checkoutPlan = $selectedPlan ?? $currentPlan)
@php($currentPlanIsCustomRoomPricing = $currentPlan?->usesCustomRoomPricing() ?? false)
@php($isCustomRoomPricing = $checkoutPlan?->usesCustomRoomPricing() ?? false)
@php($minimumRoomCount = $isCustomRoomPricing && $checkoutPlan ? $checkoutPlan->minimumRoomCount() : 1)
@php($selectedRoomCount = max($minimumRoomCount, (int) old('room_count', $tenant?->subscribed_room_limit ?: $minimumRoomCount)))
@php($selectedSlipOkAddon = old('slipok_addon_enabled', $tenant?->subscribed_slipok_enabled ? '1' : '0') === '1')
@php($billingOptionLabels = ['prepaid_annual' => 'ชำระล่วงหน้ารายปี', 'subscription_annual' => 'สมาชิกรายปี', 'subscription_yearly' => 'สมาชิกรายปี'])
@php($currentBillingOption = match ((string) ($tenant?->billing_option ?? 'subscription_annual')) {
    'prepaid_annual' => 'prepaid_annual',
    'subscription_yearly' => 'subscription_annual',
    'subscription_annual' => 'subscription_annual',
    default => 'subscription_annual',
})
@php($selectedBillingOption = match ((string) old('billing_option', $tenant?->billing_option ?? 'subscription_annual')) {
    'prepaid_annual' => 'prepaid_annual',
    'subscription_yearly' => 'subscription_annual',
    'subscription_annual' => 'subscription_annual',
    default => 'subscription_annual',
})
@php($currentPlanRoomCount = $currentPlanIsCustomRoomPricing && $currentPlan ? max($currentPlan->minimumRoomCount(), (int) ($tenant?->subscribed_room_limit ?: $currentPlan->minimumRoomCount())) : 1)
@php($currentPlanSlipOkAddon = $currentPlanIsCustomRoomPricing ? (bool) ($tenant?->subscribed_slipok_enabled ?? false) : false)
@php($currentPackageBaseline = $currentPlan ? [
    'id' => $currentPlan->id,
    'name' => $currentPlan->name,
    'billing_option' => $currentBillingOption,
    'billing_option_label' => $billingOptionLabels[$currentBillingOption] ?? 'สมาชิกรายปี',
    'amount' => $currentPlan->computedBillingPriceFor($currentBillingOption, $currentPlanRoomCount, $currentPlanSlipOkAddon),
] : null)
@php($selectedPackageBaselineAmount = $checkoutPlan ? $checkoutPlan->computedBillingPriceFor($selectedBillingOption, $selectedRoomCount, $selectedSlipOkAddon) : null)
@php($selectedPackageDifference = ($currentPackageBaseline && $selectedPackageBaselineAmount !== null) ? $selectedPackageBaselineAmount - (float) $currentPackageBaseline['amount'] : null)
@php($customPricingDataAttributes = $isCustomRoomPricing && $checkoutPlan ? 'data-custom-pricing-form data-room-price=&quot;'.$checkoutPlan->roomPriceMonthly().'&quot; data-addon-price=&quot;'.$checkoutPlan->slipAddonPriceMonthly().'&quot;' : '')
@php($invoiceStatusLabels = ['paid' => 'ชำระแล้ว', 'open' => 'รอชำระ', 'draft' => 'แบบร่าง', 'void' => 'ยกเลิก', 'uncollectible' => 'เก็บเงินไม่ได้'])
@php($invoiceStatusClasses = ['paid' => 'success', 'open' => 'warning', 'draft' => 'secondary', 'void' => 'secondary', 'uncollectible' => 'danger'])
@if($tenant && $tenant->status === 'pending_checkout')
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Waiting for Payment</h4>
        <p>Your package is ready but requires payment to activate. Please complete the checkout process to start using all features.</p>
        <p class="mb-3">Use the Change Package menu below if you want to switch to another package before checkout.</p>
        @if($checkoutPlan && count($availableBillingOptions) > 0)
            <form method="POST" action="{{ route('app.billing.checkout') }}" class="row g-2 align-items-end" {!! $customPricingDataAttributes !!}>
                @csrf
                <input type="hidden" name="plan_id" value="{{ $checkoutPlan->id }}">
                <div class="col-md-4">
                    <label class="form-label">Billing option</label>
                    <select name="billing_option" class="form-control form-select">
                        @foreach($availableBillingOptions as $option)
                            <option value="{{ $option['value'] }}" @selected(old('billing_option', $tenant->billing_option ?? 'subscription_annual') === $option['value'])>
                                {{ $isCustomRoomPricing ? $option['label'] : $option['label'].' - '.number_format($option['price'], 2).' THB' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($isCustomRoomPricing)
                    <div class="col-md-12 small font-weight-semibold text-dark">Custom room pricing package • minimum {{ number_format($minimumRoomCount) }} rooms • annual checkout only</div>
                    <div class="col-md-2">
                        <label class="form-label">Rooms</label>
                        <input type="number" min="{{ $minimumRoomCount }}" max="10000" name="room_count" class="form-control" value="{{ $selectedRoomCount }}" data-room-count-input>
                    </div>
                    @if($checkoutPlan->supportsSlipOk())
                        <div class="col-md-3">
                            <label class="form-label d-block">Slip verification addon</label>
                            <div class="form-check mt-2">
                                <input type="hidden" name="slipok_addon_enabled" value="0">
                                <input type="checkbox" class="form-check-input" id="pending_slipok_addon_enabled" name="slipok_addon_enabled" value="1" @checked($selectedSlipOkAddon) data-slipok-addon-input>
                                <label class="form-check-label" for="pending_slipok_addon_enabled">Enable slip verification addon</label>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-12">
                        <div class="small text-muted" data-custom-price-summary>
                            {{ number_format($checkoutPlan->roomPriceMonthly(), 2) }} THB x {{ $selectedRoomCount }} room(s) x 12 months
                            @if($checkoutPlan->supportsSlipOk())
                                + {{ number_format($checkoutPlan->slipAddonPriceMonthly(), 2) }} THB x {{ $selectedRoomCount }} room(s) x 12 months for slip verification
                            @endif
                            = {{ number_format($checkoutPlan->computedBillingPriceFor('subscription_annual', $selectedRoomCount, $selectedSlipOkAddon), 2) }} THB / year
                        </div>
                    </div>
                @endif
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-warning">Proceed to Checkout</button>
                </div>
            </form>
        @else
            <div class="small text-muted">No Stripe billing option is configured for this package yet. Please contact Platform Admin.</div>
        @endif
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <p class="mb-0">{{ session('warning') }}</p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <p class="mb-0">{{ session('success') }}</p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Billing Error</h4>
        <p class="mb-0">{{ $errors->first() }}</p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="small-box bg-info">
            <div class="inner">
                <p class="mb-1 d-flex align-items-center gap-2">แพ็กเกจปัจจุบัน <span class="badge bg-light text-dark">ปัจจุบัน</span></p>
                <h3>{{ $currentPlan?->name ?? ucfirst((string) $tenant?->plan) ?: '-' }}</h3>
                <p class="mb-0">
                    @if($currentPlan?->usesCustomRoomPricing())
                        เริ่มต้น {{ number_format($currentPlan->roomPriceMonthly(), 2) }} THB / ห้อง / เดือน
                    @else
                        {{ $currentPlan ? number_format((float) $currentPlan->price_monthly, 2) : '0.00' }} THB / เดือน
                    @endif
                </p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="small-box bg-success">
            <div class="inner">
                <p class="mb-1">สถานะการใช้งาน</p>
                <h3>{{ ucfirst(str_replace('_', ' ', (string) ($tenant?->billing_option ?? $tenant?->subscription_status ?? $tenant?->status ?? '-'))) }}</h3>
                <p class="mb-0">สถานะ Tenant: {{ ucfirst((string) ($tenant?->status ?? '-')) }}</p>
            </div>
            <div class="icon"><i class="fas fa-credit-card"></i></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <p class="mb-1">ใช้งานได้ถึง</p>
                <h3>{{ optional($tenant?->access_expires_at ?? $tenant?->subscription_current_period_end)->format('d/m/Y') ?: '-' }}</h3>
                <p class="mb-0">
                    @if($tenant?->billing_option === 'prepaid_annual')
                        {{ optional($tenant?->access_expires_at)->format('H:i') ?: 'รอ Stripe ยืนยัน' }}
                    @else
                        {{ optional($tenant?->subscription_current_period_end)->format('H:i') ?: 'รอ Stripe ยืนยัน' }}
                    @endif
                </p>
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
                <button class="btn btn-sm btn-outline-primary" @disabled(! $billingPortalAvailable)>จัดการสมาชิก</button>
            </form>
        </div>
    </div>

    <div class="card-body border-bottom">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="mb-3 mb-md-0">
                <div class="text-muted small text-uppercase">เปลี่ยนแพ็กเกจ</div>
                <div class="font-weight-semibold mt-1">เลือกแพ็กเกจใหม่จากหน้ารวมแพ็กเกจ แล้วระบบจะกลับมาที่ Billing หลังชำระเงินสำเร็จ</div>
            </div>
            <a href="{{ route('app.billing.packages') }}" class="btn btn-primary">
                <i class="fas fa-box-open mr-1"></i> เปลี่ยนแพ็กเกจ
            </a>
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
                <div class="font-weight-semibold">{{ $tenant?->stripe_subscription_id ?: ($tenant?->billing_option === 'prepaid_annual' ? 'ชำระล่วงหน้า' : '-') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small text-uppercase">จำนวนห้องที่ใช้งานได้</div>
                <div class="font-weight-semibold">
                    @if($currentPlanIsCustomRoomPricing && ($tenant?->effectiveRoomsLimit() ?? 0) === 0)
                        เลือกตอน checkout
                    @else
                        {{ $tenant?->effectiveRoomsLimit() ?: 'ไม่จำกัด' }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-header border-top d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-1">ประวัติใบแจ้งหนี้ SaaS</h3>
            <div class="small text-muted">
                ใบแจ้งหนี้ที่เก็บไว้: {{ number_format($invoiceCount) }}
                @if($invoiceSyncMetadataAvailable)
                    • ซิงก์ล่าสุด {{ optional($tenant?->stripe_invoice_last_synced_at)->format('d/m/Y H:i') ?: 'ยังไม่เคยซิงก์' }}
                    @if(! is_null($tenant?->stripe_invoice_last_sync_count))
                        • รอบล่าสุด {{ number_format((int) $tenant->stripe_invoice_last_sync_count) }} ใบ
                    @endif
                @endif
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#invoiceSyncSettingsModal" @disabled(! $invoiceSyncAvailable) aria-label="Invoice sync settings">
            <i class="fas fa-cog"></i>
        </button>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>สถานะ</th>
                    <th>ยอดเรียกเก็บ</th>
                    <th>รอบบิล</th>
                    <th>วันที่ออก</th>
                    <th>วันที่ชำระ</th>
                    <th>เอกสาร</th>
                </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->stripe_invoice_id }}</td>
                    <td><span class="badge badge-{{ $invoiceStatusClasses[$invoice->status] ?? 'secondary' }}">{{ $invoiceStatusLabels[$invoice->status] ?? ($invoice->status ?? '-') }}</span></td>
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
                        <div class="mb-1">ยังไม่มีประวัติใบแจ้งหนี้ SaaS</div>
                        <div class="small">ใบแจ้งหนี้จาก Stripe จะแสดงหลังชำระเงินครั้งแรก หรือหลังใช้ปุ่มซิงก์จาก Stripe</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="invoiceSyncSettingsModal" tabindex="-1" role="dialog" aria-labelledby="invoiceSyncSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceSyncSettingsModalLabel">ตั้งค่าการซิงก์ Stripe invoices</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('app.billing.invoices.sync') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="small text-muted mb-3">ใช้สำหรับดึงประวัติ invoice จาก Stripe มาเก็บในระบบ โดยไม่กระทบ invoice ที่ออกให้ผู้เช่าภายในหอพัก</div>
                        <label class="form-label">ช่วงเวลาย้อนหลัง</label>
                        <div class="btn-group btn-group-sm d-flex flex-wrap" data-invoice-sync-presets>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-invoice-sync-preset-days="30">30 วัน</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-invoice-sync-preset-days="90">90 วัน</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-invoice-sync-preset-days="365">1 ปี</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-invoice-sync-preset-days="">ทั้งหมด</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">เริ่มตั้งแต่วันที่</label>
                        <input type="date" name="since_date" class="form-control" value="{{ $invoiceSyncDefaultSinceDate }}" data-invoice-sync-since-date>
                        <div class="form-text">เลือกวันที่เริ่มต้นหรือใช้ preset ด้านบนเพื่อกรอกให้อัตโนมัติ</div>
                    </div>
                    <div>
                        <label class="form-label">จำนวน invoice สูงสุด</label>
                        <input type="number" min="1" max="1000" name="max_invoices" class="form-control" value="{{ $invoiceSyncDefaultLimit }}">
                        <div class="form-text">ระบบจะหยุดเมื่อถึงจำนวนสูงสุดหรือเจอ invoice ก่อนวันที่ที่เลือก</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">ปิด</button>
                    <button class="btn btn-primary" @disabled(! $invoiceSyncAvailable)>ซิงก์จาก Stripe</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var packageConfigs = @json($packageCheckoutConfigs);
    var currentPlanId = @json($currentPlan?->id);
    var currentPackageBaseline = @json($currentPackageBaseline);
    var packageSwitchSelect = document.querySelector('[data-package-switch-select]');
    var invoiceSyncSinceDateInput = document.querySelector('[data-invoice-sync-since-date]');
    var packageChangeConfirmationMessage = 'คุณกำลังเลือกแพ็กเกจใหม่ที่ต่างจากแพ็กเกจปัจจุบัน ต้องการไปต่อที่ checkout เพื่อเปลี่ยนแพ็กเกจหลังยืนยันการชำระเงินหรือไม่?';
    var billingOptionLabels = {
        prepaid_annual: 'ชำระล่วงหน้ารายปี',
        subscription_annual: 'สมาชิกรายปี',
        subscription_yearly: 'สมาชิกรายปี'
    };

    var formatCurrency = function (value) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    };

    var billingOptionLabel = function (value) {
        return billingOptionLabels[value] || value;
    };

    var priceWithPeriodText = function (amount) {
        return formatCurrency(amount) + ' THB / ปี';
    };

    var selectedPackagePriceText = function (config) {
        return config.isCustomRoomPricing
            ? 'เริ่มต้น ' + formatCurrency(config.roomPriceMonthly || 0) + ' THB / ห้อง / เดือน'
            : formatCurrency(config.monthlyPrice || 0) + ' THB / เดือน';
    };

    var computeCheckoutAmount = function (form, config) {
        var billingOption = form.querySelector('select[name="billing_option"]');
        var roomCountInput = form.querySelector('[data-room-count-input]');
        var slipokAddonInput = form.querySelector('[data-slipok-addon-input]');
        var activeBillingOption = billingOption ? billingOption.value : 'subscription_annual';

        if (!config.isCustomRoomPricing) {
            var matchedOption = (config.billingOptions || []).find(function (option) {
                return option.value === activeBillingOption;
            }) || (config.billingOptions || [])[0];

            return matchedOption ? Number(matchedOption.price || 0) : 0;
        }

        var minimumRoomCount = Number(config.minimumRoomCount || 1);
        var roomCount = roomCountInput
            ? Math.max(minimumRoomCount, parseInt(roomCountInput.value || String(minimumRoomCount), 10) || minimumRoomCount)
            : minimumRoomCount;
        var addonEnabled = !!(slipokAddonInput && slipokAddonInput.checked && config.supportsSlipOk);
        var roomTotal = Number(config.roomPriceMonthly || 0) * roomCount * 12;
        var addonTotal = addonEnabled ? Number(config.addonPriceMonthly || 0) * roomCount * 12 : 0;

        return roomTotal + addonTotal;
    };

    var updatePackageDifferenceSummary = function (form, config) {
        var summaryNode = document.querySelector('[data-package-difference-summary]');
        var billingOption = form.querySelector('select[name="billing_option"]');

        if (!summaryNode || !currentPackageBaseline || !currentPackageBaseline.id) {
            return;
        }

        if (String(currentPackageBaseline.id) === String(config.id)) {
            summaryNode.classList.add('d-none');
            summaryNode.textContent = '';

            return;
        }

        var selectedAmount = computeCheckoutAmount(form, config);
        var difference = selectedAmount - Number(currentPackageBaseline.amount || 0);
        var differenceText = difference === 0
            ? 'ราคาเท่ากับแพ็กเกจปัจจุบัน'
            : (difference > 0
                ? 'เพิ่มขึ้น ' + priceWithPeriodText(Math.abs(difference))
                : 'ประหยัดลง ' + priceWithPeriodText(Math.abs(difference)));

        summaryNode.textContent = 'เทียบกับแพ็กเกจปัจจุบัน ' + currentPackageBaseline.name
            + ' (' + currentPackageBaseline.billing_option_label + ' ' + priceWithPeriodText(Number(currentPackageBaseline.amount || 0)) + ')'
            + ' -> ' + config.name
            + ' (' + billingOptionLabel(billingOption ? billingOption.value : 'subscription_annual') + ' ' + priceWithPeriodText(selectedAmount) + ')'
            + ' • ' + differenceText;
        summaryNode.classList.remove('d-none');
    };

    var renderBillingOptions = function (select, config, preferredValue) {
        if (!select) {
            return;
        }

        var options = Array.isArray(config.billingOptions) ? config.billingOptions : [];
        var nextValue = preferredValue && options.some(function (option) {
            return option.value === preferredValue;
        }) ? preferredValue : (options[0] ? options[0].value : '');

        select.innerHTML = '';

        options.forEach(function (option) {
            var optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = config.isCustomRoomPricing
                ? billingOptionLabel(option.value)
                : billingOptionLabel(option.value) + ' - ' + formatCurrency(option.price) + ' THB';
            optionElement.selected = option.value === nextValue;
            select.appendChild(optionElement);
        });
    };

    var updateCustomSummary = function (form, config) {
        var billingOption = form.querySelector('select[name="billing_option"]');
        var roomCountInput = form.querySelector('[data-room-count-input]');
        var slipokAddonInput = form.querySelector('[data-slipok-addon-input]');
        var summary = form.querySelector('[data-custom-price-summary]');
        var roomLine = form.querySelector('[data-custom-room-line]');
        var addonLineWrapper = form.querySelector('[data-custom-addon-line-wrapper]');
        var addonLine = form.querySelector('[data-custom-addon-line]');
        var totalLine = form.querySelector('[data-custom-total-line]');
        var customPanel = form.querySelector('[data-custom-package-panel]');

        if (!billingOption || !roomCountInput || !summary || !customPanel || customPanel.classList.contains('d-none')) {
            return;
        }

        var roomPrice = Number(config.roomPriceMonthly || form.dataset.roomPrice || 0);
        var addonPrice = Number(config.addonPriceMonthly || form.dataset.addonPrice || 0);
        var minimumRoomCount = Number(config.minimumRoomCount || roomCountInput.getAttribute('min') || 1);
        var roomCount = Math.max(minimumRoomCount, parseInt(roomCountInput.value || String(minimumRoomCount), 10) || minimumRoomCount);
        var addonEnabled = slipokAddonInput ? slipokAddonInput.checked : false;
        var multiplier = billingOption.value === 'prepaid_annual' || billingOption.value === 'subscription_annual' ? 12 : 1;
        var roomTotal = roomPrice * roomCount * multiplier;
        var addonTotal = addonEnabled ? addonPrice * roomCount * multiplier : 0;
        var total = roomTotal + addonTotal;

        roomCountInput.value = roomCount;

        if (roomLine && totalLine) {
            roomLine.textContent = formatCurrency(roomPrice) + ' x ' + roomCount + ' ห้อง x ' + multiplier + ' เดือน = ' + formatCurrency(roomTotal) + ' THB';

            if (addonLineWrapper) {
                addonLineWrapper.classList.toggle('d-none', !addonEnabled);
            }

            if (addonLine) {
                addonLine.textContent = formatCurrency(addonPrice) + ' x ' + roomCount + ' ห้อง x ' + multiplier + ' เดือน = ' + formatCurrency(addonTotal) + ' THB';
            }

            totalLine.textContent = formatCurrency(total) + ' THB';

            return;
        }

        summary.textContent = formatCurrency(total) + ' THB';
    };

    var updateCheckoutSurface = function (surface, config) {
        if (!surface || !config) {
            return;
        }

        var form = surface.querySelector('[data-billing-checkout-form]');
        var noOptionsMessage = surface.querySelector('[data-no-billing-options-message]');

        if (!form || !noOptionsMessage) {
            return;
        }

        var billingOptionSelect = form.querySelector('[data-billing-option-select]');
        var planIdInput = form.querySelector('[data-checkout-plan-id-input]');
        var customPanel = form.querySelector('[data-custom-package-panel]');
        var customHeadline = form.querySelector('[data-custom-package-headline]');
        var roomCountInput = form.querySelector('[data-room-count-input]');
        var slipokAddonWrapper = form.querySelector('[data-slipok-addon-wrapper]');
        var slipokAddonInput = form.querySelector('[data-slipok-addon-input]');
        var hasOptions = Array.isArray(config.billingOptions) && config.billingOptions.length > 0;
        var preferredValue = billingOptionSelect ? billingOptionSelect.value : null;

        if (planIdInput) {
            planIdInput.value = config.id;
        }

        form.dataset.roomPrice = String(config.roomPriceMonthly || 0);
        form.dataset.addonPrice = String(config.addonPriceMonthly || 0);

        if (billingOptionSelect) {
            renderBillingOptions(billingOptionSelect, config, preferredValue);
        }

        form.classList.toggle('d-none', !hasOptions);
        noOptionsMessage.classList.toggle('d-none', hasOptions);

        if (!customPanel || !roomCountInput) {
            return;
        }

        customPanel.classList.toggle('d-none', !config.isCustomRoomPricing);

        if (customHeadline) {
            customHeadline.textContent = 'แพ็กเกจกำหนดเอง • ขั้นต่ำ ' + config.minimumRoomCount + ' ห้อง • ชำระรายปีเท่านั้น';
        }

        roomCountInput.min = String(config.minimumRoomCount || 1);
        roomCountInput.value = Math.max(Number(config.minimumRoomCount || 1), parseInt(roomCountInput.value || String(config.minimumRoomCount || 1), 10) || Number(config.minimumRoomCount || 1));

        if (slipokAddonWrapper) {
            slipokAddonWrapper.classList.toggle('d-none', !config.supportsSlipOk);
        }

        if (slipokAddonInput && !config.supportsSlipOk) {
            slipokAddonInput.checked = false;
        }

        updateCustomSummary(form, config);
        updatePackageDifferenceSummary(form, config);
    };

    var updateSelectedPackageDetails = function (config) {
        if (!config) {
            return;
        }

        document.querySelectorAll('[data-selected-package-name]').forEach(function (node) {
            node.textContent = config.name;
        });

        document.querySelectorAll('[data-selected-package-price]').forEach(function (node) {
            node.textContent = selectedPackagePriceText(config);
        });

        document.querySelectorAll('[data-selected-package-relation]').forEach(function (node) {
            node.textContent = String(currentPlanId) === String(config.id)
                ? 'ตรงกับแพ็กเกจปัจจุบัน'
                : 'ระบบจะเปลี่ยนแพ็กเกจหลังชำระสำเร็จ';
        });
    };

    var switchPackage = function (planId) {
        var config = packageConfigs[String(planId)];

        if (!config) {
            return;
        }

        document.querySelectorAll('[data-billing-checkout-surface]').forEach(function (surface) {
            updateCheckoutSurface(surface, config);
        });

        updateSelectedPackageDetails(config);

        if (window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('plan_id', config.id);
            window.history.replaceState({}, '', url.toString());
        }
    };

    document.querySelectorAll('[data-billing-checkout-form]').forEach(function (form) {
        var billingOption = form.querySelector('[data-billing-option-select]');
        var roomCountInput = form.querySelector('[data-room-count-input]');
        var slipokAddonInput = form.querySelector('[data-slipok-addon-input]');
        var planIdInput = form.querySelector('[data-checkout-plan-id-input]');

        form.addEventListener('submit', function (event) {
            if (!planIdInput || currentPlanId === null || currentPlanId === undefined) {
                return;
            }

            if (String(planIdInput.value) === String(currentPlanId)) {
                return;
            }

            if (!window.confirm(packageChangeConfirmationMessage)) {
                event.preventDefault();
            }
        });

        if (billingOption) {
            billingOption.addEventListener('change', function () {
                var activePlanIdInput = form.querySelector('[data-checkout-plan-id-input]');
                var activeConfig = activePlanIdInput ? packageConfigs[String(activePlanIdInput.value)] : null;

                if (activeConfig) {
                    updateCustomSummary(form, activeConfig);
                    updatePackageDifferenceSummary(form, activeConfig);
                }
            });
        }

        if (roomCountInput) {
            roomCountInput.addEventListener('input', function () {
                var activePlanIdInput = form.querySelector('[data-checkout-plan-id-input]');
                var activeConfig = activePlanIdInput ? packageConfigs[String(activePlanIdInput.value)] : null;

                if (activeConfig) {
                    updateCustomSummary(form, activeConfig);
                    updatePackageDifferenceSummary(form, activeConfig);
                }
            });
        }

        if (slipokAddonInput) {
            slipokAddonInput.addEventListener('change', function () {
                var activePlanIdInput = form.querySelector('[data-checkout-plan-id-input]');
                var activeConfig = activePlanIdInput ? packageConfigs[String(activePlanIdInput.value)] : null;

                if (activeConfig) {
                    updateCustomSummary(form, activeConfig);
                    updatePackageDifferenceSummary(form, activeConfig);
                }
            });
        }
    });

    if (packageSwitchSelect) {
        packageSwitchSelect.addEventListener('change', function () {
            switchPackage(packageSwitchSelect.value);
        });

        switchPackage(packageSwitchSelect.value);
    } else {
        document.querySelectorAll('[data-billing-checkout-form]').forEach(function (form) {
            var activePlanIdInput = form.querySelector('[data-checkout-plan-id-input]');
            var activeConfig = activePlanIdInput ? packageConfigs[String(activePlanIdInput.value)] : null;

            if (activeConfig) {
                updateCustomSummary(form, activeConfig);
            }
        });
    }

    document.querySelectorAll('[data-invoice-sync-preset-days]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!invoiceSyncSinceDateInput) {
                return;
            }

            var days = parseInt(button.getAttribute('data-invoice-sync-preset-days') || '0', 10);

            if (!days) {
                invoiceSyncSinceDateInput.value = '';
                return;
            }

            var selectedDate = new Date();
            selectedDate.setHours(0, 0, 0, 0);
            selectedDate.setDate(selectedDate.getDate() - days);
            invoiceSyncSinceDateInput.value = selectedDate.toISOString().split('T')[0];
        });
    });
});
</script>
@endpush
