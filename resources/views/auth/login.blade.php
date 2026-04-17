<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-slot name="eyebrow">
            Sign In
        </x-slot>

        <x-slot name="title">
            เข้าสู่ระบบเพื่อจัดการหอพักของคุณ
        </x-slot>

        <x-slot name="description">
            สำหรับ owner, staff หรือทีม support ที่ได้รับสิทธิ์ ใช้อีเมลเดิมเพื่อเข้าสู่ dashboard และระบบจัดการ tenant ของคุณ
        </x-slot>

        <x-slot name="asideTitle">
            จาก landing page เข้าสู่ portal ได้ทันที
        </x-slot>

        <x-slot name="asideDescription">
            หน้านี้เป็นส่วนหนึ่งของ onboarding flow เดียวกับ pricing, register, forgot password และ email verification
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between gap-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button class="ms-4">
                    {{ __('Log in') }}
                </x-button>
            </div>

            <div class="mt-6 text-sm text-slate-500">
                ยังไม่มีบัญชี? <a href="{{ route('register') }}" class="font-semibold text-slate-700 underline decoration-amber-400 decoration-2 underline-offset-4">สมัครใช้งาน MesukDorm</a>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
