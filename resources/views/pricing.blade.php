<x-public-layout title="Pricing • MesukDorm" description="เลือกแพ็กเกจสำหรับระบบบริหารหอพักแบบ SaaS พร้อมหน้าราคาโทนพรีเมียมที่เชื่อม onboarding ได้ทันที">
    <section class="px-4 pb-10 pt-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr,0.92fr] lg:items-center">
            <div class="reveal-soft" style="--enter-delay: 0.04s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Pricing Plans</p>
                <h1 class="mt-3 max-w-4xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">Pricing ที่ดูพรีเมียมพอสำหรับหน้าเสนอขาย และชัดพอสำหรับการตัดสินใจทันที</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">ทุกแพ็กเกจยังเชื่อมกับ registration flow เดิม แต่โครงสร้างหน้าและการจัดวางข้อมูลถูกปรับให้รู้สึกมั่นใจขึ้น เหมาะกับการขายระบบบริหารหอแบบจริงจัง</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.12s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Properties</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($publicStats['tenants_total']) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">จำนวน tenant ที่ใช้ระบบอยู่จริง</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.2s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Occupancy</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ $publicStats['occupancy_rate'] }}%</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">คิดจากห้องที่ occupied เทียบ inventory ทั้งหมด</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.28s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Approved revenue</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">฿{{ number_format((float) $publicStats['monthly_revenue'], 0) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">ยอดชำระที่ approve แล้วของเดือนนี้</p>
                    </div>
                </div>
            </div>

            <aside class="premium-panel reveal-up rounded-[2.4rem] p-6 text-white sm:p-8" style="--enter-delay: 0.14s;">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/50">Featured property snapshot</p>
                @if($featuredProperty)
                    <h2 class="mt-3 text-3xl font-bold">{{ $featuredProperty['name'] }}</h2>
                    <p class="mt-3 text-base leading-7 text-white/70">ใช้ข้อมูลจริงจาก tenant ที่มีจำนวนห้องมากสุดในระบบเพื่อทำให้ pricing page มีแรงยืนยันจาก product usage ไม่ใช่แค่ข้อความขาย</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                            <p class="text-sm text-white/55">Rooms occupied</p>
                            <p class="mt-1 text-2xl font-extrabold">{{ number_format($featuredProperty['rooms_occupied']) }}/{{ number_format($featuredProperty['rooms_total']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                            <p class="text-sm text-white/55">Occupancy rate</p>
                            <p class="mt-1 text-2xl font-extrabold">{{ $featuredProperty['occupancy_rate'] }}%</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                            <p class="text-sm text-white/55">Pending payments</p>
                            <p class="mt-1 text-2xl font-extrabold">{{ number_format($featuredProperty['pending_payments']) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                            <p class="text-sm text-white/55">Monthly approved</p>
                            <p class="mt-1 text-2xl font-extrabold">฿{{ number_format((float) $featuredProperty['monthly_revenue'], 0) }}</p>
                        </div>
                    </div>
                @else
                    <h2 class="mt-3 text-3xl font-bold">Pricing พร้อมสำหรับข้อมูลจริง</h2>
                    <p class="mt-3 text-base leading-7 text-white/70">เมื่อมี tenant และธุรกรรมในระบบ พื้นที่นี้จะสรุป featured property โดยอัตโนมัติ เพื่อเพิ่ม trust ให้หน้า pricing โดยไม่ต้องใช้ placeholder metrics</p>
                @endif
            </aside>
        </div>
    </section>

    <section class="px-4 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            @forelse($plans as $plan)
                <article class="{{ $loop->iteration === 2 ? 'premium-panel reveal-up text-white' : 'glass-panel reveal-up' }} rounded-[2.2rem] p-6" style="--enter-delay: {{ 0.08 + ($loop->iteration * 0.08) }}s;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.24em] {{ $loop->iteration === 2 ? 'text-white/55' : ($loop->iteration === 3 ? 'text-teal-700' : 'text-amber-700') }}">{{ $plan->slug ?: 'Plan' }}</p>
                            <h2 class="mt-4 text-3xl font-bold {{ $loop->iteration === 2 ? 'text-white' : 'text-slate-950' }}">{{ $plan->name }}</h2>
                        </div>
                        @if($loop->iteration === 2)
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-amber-200 ring-1 ring-white/10">Recommended</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-extrabold tracking-tight {{ $loop->iteration === 2 ? 'text-white' : 'text-slate-950' }}">{{ number_format((float) $plan->price_monthly, 0) }}</span>
                        <span class="pb-2 text-base font-semibold {{ $loop->iteration === 2 ? 'text-white/55' : 'text-slate-500' }}">บาท / เดือน</span>
                    </div>

                    @if($plan->description)
                        <p class="mt-5 text-base leading-7 {{ $loop->iteration === 2 ? 'text-white/70' : 'text-slate-600' }}">{{ $plan->description }}</p>
                    @endif

                    @php($limits = $plan->displayLimits())
                    <div class="mt-6 space-y-3 text-sm font-semibold">
                        @forelse($limits as $key => $value)
                            <div class="flex items-center justify-between rounded-2xl px-4 py-3 {{ $loop->parent->iteration === 2 ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">
                                <span>{{ $key }}</span>
                                <span class="{{ $loop->parent->iteration === 2 ? 'text-white' : 'text-slate-950' }}">{{ $value }}</span>
                            </div>
                        @empty
                            <div class="rounded-2xl px-4 py-3 {{ $loop->iteration === 2 ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">พร้อมใช้งานกับ billing, room management และ LINE workflow</div>
                        @endforelse
                    </div>

                    <div class="mt-8 space-y-3">
                        @guest
                            <a href="{{ route('register', ['plan' => $plan->id]) }}" class="inline-flex w-full items-center justify-center rounded-full {{ $loop->iteration === 2 ? 'bg-white text-slate-950 hover:bg-white/90' : 'bg-slate-950 text-white hover:bg-slate-800' }} px-5 py-3 text-sm font-bold transition">เลือกแพ็กเกจนี้</a>
                            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-full border {{ $loop->iteration === 2 ? 'border-white/20 bg-white/5 text-white hover:bg-white/10' : 'border-slate-300 bg-white/90 text-slate-700 hover:border-slate-950 hover:text-slate-950' }} px-5 py-3 text-sm font-bold transition">มีบัญชีอยู่แล้ว</a>
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
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.1s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">What You Get</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">พร้อมใช้งานกับ flow ปัจจุบัน</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">ทุกแพ็กเกจเชื่อมเข้ากับ registration, login, forgot password, email verification และ tenant dashboard แล้ว</p>
            </div>
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.18s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Operational trust</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">ไม่ได้ขายแค่ feature list</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">หน้านี้อ้างอิงข้อมูลจริงจาก rooms, invoices, payments และ property snapshot เพื่อเพิ่มน้ำหนักให้การตัดสินใจ</p>
            </div>
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.26s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Need Help</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">อยากเริ่มจาก Trial</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">เลือก Trial เพื่อเริ่ม onboarding และให้ระบบตั้งค่า trial end date อัตโนมัติตาม plan ปัจจุบัน</p>
            </div>
        </div>
    </section>
</x-public-layout>
