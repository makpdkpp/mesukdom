<x-public-layout title="Pricing • MesukDom" description="เลือกแพ็กเกจสำหรับระบบบริหารหอพักแบบ SaaS พร้อม sign up flow ที่สร้าง tenant ได้ทันที">
    <section class="px-4 pb-8 pt-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl text-center">
            <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Pricing Plans</p>
            <h1 class="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">เลือกแพ็กเกจที่เหมาะกับขนาดหอของคุณ</h1>
            <p class="mx-auto mt-5 max-w-3xl text-lg leading-8 text-slate-600">หน้า pricing นี้ดึงข้อมูลจากฐานข้อมูลจริง และส่ง plan ที่เลือกต่อไปยังหน้า sign up โดยตรง เพื่อให้ owner เริ่ม onboarding ได้ทันที</p>
        </div>
    </section>

    <section class="px-4 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            @forelse($plans as $plan)
                <article class="mesh-card rounded-[2rem] p-6 {{ $loop->iteration === 2 ? 'relative scale-[1.01] ring-2 ring-slate-950/10' : '' }}">
                    @if($loop->iteration === 2)
                        <span class="absolute right-6 top-6 rounded-full bg-slate-950 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-white">Popular</span>
                    @endif
                    <p class="text-sm font-bold uppercase tracking-[0.24em] {{ $loop->iteration === 3 ? 'text-teal-700' : 'text-amber-700' }}">{{ $plan->slug }}</p>
                    <h2 class="mt-4 text-3xl font-bold text-slate-950">{{ $plan->name }}</h2>
                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-extrabold tracking-tight text-slate-950">{{ number_format((float) $plan->price_monthly, 0) }}</span>
                        <span class="pb-2 text-base font-semibold text-slate-500">บาท / เดือน</span>
                    </div>

                    @if($plan->description)
                        <p class="mt-5 text-base leading-7 text-slate-600">{{ $plan->description }}</p>
                    @endif

                    @php($limits = (array) ($plan->limits ?? []))
                    @if(count($limits))
                        <ul class="mt-6 space-y-3 text-sm font-semibold text-slate-700">
                            @foreach($limits as $key => $value)
                                <li class="flex items-center justify-between rounded-2xl bg-white/80 px-4 py-3">
                                    <span>{{ ucfirst((string) $key) }}</span>
                                    <span class="text-slate-950">{{ $value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-8 space-y-3">
                        @guest
                            <a href="{{ route('register', ['plan' => $plan->id]) }}" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">เลือกแพ็กเกจนี้</a>
                            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-300 bg-white/90 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">มีบัญชีอยู่แล้ว</a>
                        @else
                            <a href="{{ route('app.dashboard') }}" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">ไปที่ Dashboard</a>
                        @endguest
                    </div>
                </article>
            @empty
                <div class="glass-panel rounded-[2rem] px-6 py-10 text-center lg:col-span-3">
                    <p class="text-lg font-bold text-slate-950">ยังไม่มีแพ็กเกจในระบบ</p>
                    <p class="mt-3 text-base text-slate-600">กรุณา seed หรือเพิ่มข้อมูล plans ก่อนเปิดขายบน public site</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            <div class="glass-panel rounded-[2rem] p-6">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">What You Get</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">พร้อมใช้งานกับ flow ปัจจุบัน</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">ทุกแพ็กเกจเชื่อมเข้ากับ registration, login, forgot password, email verification และ tenant dashboard แล้ว</p>
            </div>
            <div class="glass-panel rounded-[2rem] p-6">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Current Scope</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">เหมาะกับ MVP เปิดใช้งานจริง</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">แพ็กเกจยังเป็นแผน subscription ภายในระบบ ยังไม่ได้ต่อ payment gateway สำหรับเก็บเงิน SaaS จริง</p>
            </div>
            <div class="glass-panel rounded-[2rem] p-6">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Need Help</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">อยากเริ่มจาก Trial</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">เลือก Trial เพื่อเริ่ม onboarding และให้ระบบตั้งค่า trial end date อัตโนมัติตาม plan ปัจจุบัน</p>
            </div>
        </div>
    </section>
</x-public-layout>
