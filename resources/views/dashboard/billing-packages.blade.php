@extends('layouts.adminlte', ['title' => 'เลือกแพ็กเกจ', 'heading' => 'เลือกแพ็กเกจ'])

@section('content')
@php($billingOptionLabels = ['prepaid_annual' => 'ชำระล่วงหน้ารายปี', 'subscription_annual' => 'สมาชิกรายปี', 'subscription_yearly' => 'สมาชิกรายปี'])

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">ไม่สามารถเริ่ม checkout ได้</h4>
        <p class="mb-0">{{ $errors->first() }}</p>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4 billing-package-hero">
    <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
        <div class="mb-3 mb-lg-0">
            <div class="text-uppercase small font-weight-bold text-muted">Package Checkout</div>
            <h3 class="mb-2">เลือกแพ็กเกจที่เหมาะกับหอพักของคุณ</h3>
            <div class="text-muted">แพ็กเกจจะเปลี่ยนหลังชำระเงินสำเร็จ และระบบจะพากลับมาที่หน้า Billing อัตโนมัติ</div>
        </div>
        <a href="{{ route('app.billing') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> กลับ Billing
        </a>
    </div>
</div>

@if(! $stripeReady)
    <div class="alert alert-warning">Stripe ยังไม่พร้อมใช้งาน กรุณาตั้งค่า Stripe ก่อนเริ่ม checkout</div>
@endif

<div class="row">
    @forelse($packagePlans as $plan)
        @php($isCurrentPlan = $currentPlan?->id === $plan->id)
        @php($isCustom = $plan->usesCustomRoomPricing())
        @php($minimumRoomCount = $plan->minimumRoomCount())
        @php($defaultRoomCount = $isCustom ? max($minimumRoomCount, (int) ($tenant?->subscribed_room_limit ?: $minimumRoomCount)) : 1)
        @php($defaultAddon = $isCustom && (bool) ($tenant?->subscribed_slipok_enabled ?? false))
        @php($billingOptions = $packageCheckoutConfigs[(string) $plan->id]['billingOptions'] ?? [])
        @php($defaultBillingOption = $billingOptions[0]['value'] ?? 'subscription_annual')
        @php($defaultTotal = $plan->computedBillingPriceFor($defaultBillingOption, $defaultRoomCount, $defaultAddon))
        <div class="col-xl-4 col-lg-6 mb-4">
            <article class="card h-100 border-0 shadow-sm billing-package-card {{ $isCurrentPlan ? 'is-current' : '' }}" data-package-card data-plan-id="{{ $plan->id }}">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <div class="text-uppercase small font-weight-bold text-warning">{{ $plan->slug }}</div>
                            <h3 class="mt-2 mb-0">{{ $plan->name }}</h3>
                        </div>
                        @if($isCurrentPlan)
                            <span class="badge badge-success">ปัจจุบัน</span>
                        @endif
                    </div>

                    <div class="d-flex align-items-end mb-3">
                        <span class="display-4 font-weight-bold mb-0">{{ number_format($isCustom ? $plan->roomPriceMonthly() : (float) $plan->price_monthly, 0) }}</span>
                        <span class="text-muted font-weight-semibold pb-2 ml-2">บาท / เดือน</span>
                    </div>

                    @if($plan->description)
                        <p class="text-muted mb-3">{{ $plan->description }}</p>
                    @elseif($isCustom)
                        <p class="text-muted mb-3">สามารถเลือกจำนวนห้องได้ตามต้องการ</p>
                    @else
                        <p class="text-muted mb-3">แพ็กเกจตายตัวสำหรับการใช้งานรายปี</p>
                    @endif

                    <div class="mb-4">
                        @foreach($plan->displayLimits() as $label => $value)
                            <div class="d-flex justify-content-between align-items-center rounded bg-light border px-3 py-2 mb-2 small">
                                <span class="text-muted">{{ $label }}</span>
                                <span class="font-weight-semibold text-dark text-right">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('app.billing.checkout') }}" class="mt-auto" data-package-checkout-form data-room-price="{{ $plan->roomPriceMonthly() }}" data-addon-price="{{ $plan->slipAddonPriceMonthly() }}" data-custom-pricing="{{ $isCustom ? '1' : '0' }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                        @if(count($billingOptions) > 0)
                            <div class="form-group">
                                <label class="small text-muted mb-1">รูปแบบการชำระเงิน</label>
                                <select name="billing_option" class="form-control" data-package-billing-option>
                                    @foreach($billingOptions as $option)
                                        <option value="{{ $option['value'] }}" data-price="{{ $option['price'] }}" @selected($option['value'] === $defaultBillingOption)>
                                            {{ $billingOptionLabels[$option['value']] ?? $option['label'] }}
                                            @unless($isCustom)
                                                - {{ number_format((float) $option['price'], 2) }} THB
                                            @endunless
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @if($isCustom)
                                <div class="rounded border bg-light p-3 mb-3">
                                    <div class="text-uppercase small font-weight-bold text-warning">แพ็กเกจกำหนดเอง</div>
                                    <div class="small font-weight-semibold mt-1">ลูกค้ากำหนดจำนวนห้องได้เอง</div>
                                    <div class="small text-muted mt-1">ขั้นต่ำ {{ number_format($minimumRoomCount) }} ห้อง • ชำระหรือสมัครแบบรายปีเท่านั้น</div>
                                </div>
                                <div class="form-group">
                                    <label class="small text-muted mb-1">จำนวนห้อง</label>
                                    <input type="number" min="{{ $minimumRoomCount }}" max="10000" name="room_count" value="{{ $defaultRoomCount }}" class="form-control" data-room-count-input>
                                </div>
                                @if($plan->supportsSlipOk())
                                    <div class="custom-control custom-checkbox border rounded px-4 py-3 mb-3 bg-white">
                                        <input type="hidden" name="slipok_addon_enabled" value="0">
                                        <input type="checkbox" class="custom-control-input" id="slipok_addon_{{ $plan->id }}" name="slipok_addon_enabled" value="1" @checked($defaultAddon) data-slipok-addon-input>
                                        <label class="custom-control-label font-weight-semibold" for="slipok_addon_{{ $plan->id }}">เปิดใช้ slip verification addon</label>
                                    </div>
                                @endif
                            @endif

                            <div class="rounded border px-3 py-3 mb-3 bg-white">
                                <div class="text-uppercase small text-muted font-weight-bold">Estimated annual total</div>
                                <div class="h5 font-weight-bold mb-1" data-package-price-total>{{ number_format($defaultTotal, 2) }} THB / year</div>
                                <div class="small text-muted" data-package-price-summary>
                                    @if($isCustom)
                                        {{ number_format($plan->roomPriceMonthly(), 2) }} THB x {{ $defaultRoomCount }} room(s) x 12 months
                                        @if($defaultAddon)
                                            + {{ number_format($plan->slipAddonPriceMonthly(), 2) }} THB x {{ $defaultRoomCount }} room(s) x 12 months
                                        @endif
                                    @else
                                        {{ $billingOptionLabels[$defaultBillingOption] ?? 'สมาชิกรายปี' }}
                                    @endif
                                </div>
                            </div>

                            <button class="btn btn-dark btn-block rounded-pill" @disabled(! $stripeReady)>
                                {{ $isCurrentPlan ? 'ต่ออายุ / ชำระแพ็กเกจนี้' : 'เลือกแพ็กเกจนี้' }}
                            </button>
                        @else
                            <div class="alert alert-light border small mb-0">แพ็กเกจนี้ยังไม่ได้ตั้งค่า checkout ใน Stripe</div>
                        @endif
                    </form>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body text-center text-muted">ยังไม่มีแพ็กเกจที่เปิดใช้งาน</div></div>
        </div>
    @endforelse
</div>
@endsection

@push('head')
<style>
    .billing-package-hero,
    .billing-package-card {
        border-radius: 8px;
    }

    .billing-package-card {
        border-top: 3px solid transparent !important;
    }

    .billing-package-card.is-current {
        border-top-color: #28a745 !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var packageConfigs = @json($packageCheckoutConfigs);
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

    var updatePackageForm = function (form) {
        var planIdInput = form.querySelector('input[name="plan_id"]');
        var billingOption = form.querySelector('[data-package-billing-option]');
        var roomCountInput = form.querySelector('[data-room-count-input]');
        var addonInput = form.querySelector('[data-slipok-addon-input]');
        var totalOutput = form.querySelector('[data-package-price-total]');
        var summaryOutput = form.querySelector('[data-package-price-summary]');

        if (!planIdInput || !billingOption || !totalOutput || !summaryOutput) {
            return;
        }

        var config = packageConfigs[String(planIdInput.value)] || {};
        var optionValue = billingOption.value;
        var isCustom = form.dataset.customPricing === '1';

        if (!isCustom) {
            var selectedOption = billingOption.options[billingOption.selectedIndex];
            var price = Number(selectedOption ? selectedOption.dataset.price : 0);
            totalOutput.textContent = formatCurrency(price) + ' THB / year';
            summaryOutput.textContent = billingOptionLabels[optionValue] || optionValue;

            return;
        }

        var minimumRoomCount = Number(config.minimumRoomCount || roomCountInput.getAttribute('min') || 1);
        var roomCount = Math.max(minimumRoomCount, parseInt(roomCountInput.value || String(minimumRoomCount), 10) || minimumRoomCount);
        var roomPrice = Number(config.roomPriceMonthly || form.dataset.roomPrice || 0);
        var addonPrice = Number(config.addonPriceMonthly || form.dataset.addonPrice || 0);
        var addonEnabled = !!(addonInput && addonInput.checked && config.supportsSlipOk);
        var roomTotal = roomPrice * roomCount * 12;
        var addonTotal = addonEnabled ? addonPrice * roomCount * 12 : 0;
        var total = roomTotal + addonTotal;
        var summary = formatCurrency(roomPrice) + ' THB x ' + roomCount + ' room(s) x 12 months';

        roomCountInput.value = roomCount;

        if (addonEnabled) {
            summary += ' + ' + formatCurrency(addonPrice) + ' THB x ' + roomCount + ' room(s) x 12 months';
        }

        totalOutput.textContent = formatCurrency(total) + ' THB / year';
        summaryOutput.textContent = summary;
    };

    document.querySelectorAll('[data-package-checkout-form]').forEach(function (form) {
        form.querySelector('[data-package-billing-option]')?.addEventListener('change', function () {
            updatePackageForm(form);
        });
        form.querySelector('[data-room-count-input]')?.addEventListener('input', function () {
            updatePackageForm(form);
        });
        form.querySelector('[data-slipok-addon-input]')?.addEventListener('change', function () {
            updatePackageForm(form);
        });

        updatePackageForm(form);
    });
});
</script>
@endpush
