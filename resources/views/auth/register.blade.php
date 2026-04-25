<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-slot name="eyebrow">
            Create Tenant
        </x-slot>

        <x-slot name="title">
            สมัครใช้งานและเปิด tenant ใหม่
        </x-slot>

        <x-slot name="description">
            กรอกข้อมูลเจ้าของหอ ชื่อหอพัก และแพ็กเกจที่ต้องการ ระบบจะสร้าง owner account และ tenant ให้ในขั้นตอนเดียว
        </x-slot>

        <x-slot name="asideTitle">
            Owner onboarding พร้อม plan selection
        </x-slot>

        <x-slot name="asideDescription">
            เลือกแพ็กเกจจาก pricing แล้วส่งต่อมายังหน้าสมัครได้ทันที เพื่อให้ระบบสร้าง tenant context และผูก owner role ให้โดยอัตโนมัติ
        </x-slot>

        <x-validation-errors class="mb-4" />

        @php($minimumCustomRoomCount = collect($plans ?? [])->first(fn ($plan) => $plan->usesCustomRoomPricing())?->minimumRoomCount() ?? 10)
        @php($selectedPlanId = (string) old('plan_id', request('plan')))
        @php($prefilledRoomCount = old('room_count', request('room_count', $minimumCustomRoomCount)))
        @php($prefilledSlipOkAddon = old('slipok_addon_enabled', request('slipok_addon_enabled', '0')))

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="mt-4">
                <x-label for="tenant_name" value="{{ __('Dormitory / Tenant Name') }}" />
                <x-input id="tenant_name" class="block mt-1 w-full" type="text" name="tenant_name" :value="old('tenant_name')" required autocomplete="organization" />
            </div>

            <div class="mt-4">
                <x-label for="plan_id" value="{{ __('Plan') }}" />
                <select id="plan_id" name="plan_id" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach(($plans ?? []) as $plan)
                        <option
                            value="{{ $plan->id }}"
                            data-custom-room-pricing="{{ $plan->usesCustomRoomPricing() ? '1' : '0' }}"
                            data-slipok-enabled="{{ $plan->supportsSlipOk() ? '1' : '0' }}"
                            data-min-room-count="{{ $plan->minimumRoomCount() }}"
                            data-room-price="{{ $plan->roomPriceMonthly() }}"
                            data-addon-price="{{ $plan->slipAddonPriceMonthly() }}"
                            data-rights-per-room="{{ $plan->slipAddonRightsPerRoom() }}"
                            @selected($selectedPlanId === (string) $plan->id)
                        >
                            {{ $plan->name }} ({{ number_format((float) $plan->price_monthly, 0) }}/mo)
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="custom-room-package-fields" class="mt-4 rounded-2xl border border-amber-200 bg-amber-50/70 p-4" style="display:none;">
                <div class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Custom package</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">ลูกค้ากำหนดจำนวนห้องได้เอง</div>
                <p class="mt-1 text-sm text-slate-600">เริ่มต้นขั้นต่ำ {{ number_format($minimumCustomRoomCount) }} ห้อง และ checkout จะเป็นแบบรายปีเท่านั้น</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-label for="room_count" value="{{ __('Rooms to subscribe') }}" />
                        <x-input id="room_count" class="block mt-1 w-full" type="number" min="{{ $minimumCustomRoomCount }}" max="10000" name="room_count" :value="$prefilledRoomCount" />
                    </div>
                    <div id="custom-room-slipok-addon" class="pt-6" style="display:none;">
                        <input type="hidden" name="slipok_addon_enabled" value="0">
                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700">
                            <input id="slipok_addon_enabled" type="checkbox" name="slipok_addon_enabled" value="1" class="rounded border-slate-300 text-amber-500 shadow-sm focus:ring-amber-500" @checked((string) $prefilledSlipOkAddon === '1')>
                            <span>Enable SlipOK addon</span>
                        </label>
                    </div>
                </div>
                <div id="custom-room-package-summary" class="mt-4 text-sm text-slate-600"></div>
            </div>

            <div class="mt-4">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div class="mt-4">
                    <x-label for="terms">
                        <div class="flex items-center">
                            <x-checkbox name="terms" id="terms" required />

                            <div class="ms-2">
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                        'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Terms of Service').'</a>',
                                        'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </div>
                        </div>
                    </x-label>
                </div>
            @endif

            <div class="mt-6 flex items-center justify-between gap-4">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-button class="ms-4">
                    {{ __('Register') }}
                </x-button>
            </div>
        </form>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var planSelect = document.getElementById('plan_id');
                var customFields = document.getElementById('custom-room-package-fields');
                var roomCountInput = document.getElementById('room_count');
                var slipOkAddonWrapper = document.getElementById('custom-room-slipok-addon');
                var slipOkAddonInput = document.getElementById('slipok_addon_enabled');
                var summary = document.getElementById('custom-room-package-summary');

                if (!planSelect || !customFields || !roomCountInput || !summary) {
                    return;
                }

                var formatCurrency = function (value) {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(value);
                };

                var updateCustomPackageFields = function () {
                    var selectedOption = planSelect.options[planSelect.selectedIndex];
                    var isCustomRoomPricing = selectedOption && selectedOption.dataset.customRoomPricing === '1';
                    var slipOkEnabled = selectedOption && selectedOption.dataset.slipokEnabled === '1';
                    var minimumRoomCount = Number(selectedOption ? selectedOption.dataset.minRoomCount : roomCountInput.getAttribute('min') || 1);
                    var roomPrice = Number(selectedOption ? selectedOption.dataset.roomPrice : 0);
                    var addonPrice = Number(selectedOption ? selectedOption.dataset.addonPrice : 0);
                    var roomCount = Math.max(minimumRoomCount, parseInt(roomCountInput.value || String(minimumRoomCount), 10) || minimumRoomCount);
                    var addonSelected = !!(slipOkAddonInput && slipOkAddonInput.checked && slipOkEnabled);
                    var total = ((roomPrice * roomCount) + (addonSelected ? addonPrice * roomCount : 0)) * 12;

                    customFields.style.display = isCustomRoomPricing ? 'block' : 'none';
                    roomCountInput.min = String(minimumRoomCount);
                    roomCountInput.value = roomCount;

                    if (slipOkAddonWrapper) {
                        slipOkAddonWrapper.style.display = isCustomRoomPricing && slipOkEnabled ? 'block' : 'none';
                    }

                    if (!isCustomRoomPricing) {
                        summary.textContent = '';
                        return;
                    }

                    summary.textContent = formatCurrency(roomPrice) + ' THB x ' + roomCount + ' room(s) x 12 months'
                        + (addonSelected ? ' + ' + formatCurrency(addonPrice) + ' THB x ' + roomCount + ' room(s) x 12 months' : '')
                        + ' = ' + formatCurrency(total) + ' THB / year';
                };

                planSelect.addEventListener('change', updateCustomPackageFields);
                roomCountInput.addEventListener('input', updateCustomPackageFields);

                if (slipOkAddonInput) {
                    slipOkAddonInput.addEventListener('change', updateCustomPackageFields);
                }

                updateCustomPackageFields();
            });
        </script>
    </x-authentication-card>
</x-guest-layout>
