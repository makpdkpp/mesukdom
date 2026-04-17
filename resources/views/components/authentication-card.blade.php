<div class="px-4 pb-16 pt-4 sm:px-6 lg:px-8">
    <div class="auth-shell mx-auto grid max-w-6xl overflow-hidden rounded-[2rem] lg:grid-cols-[0.9fr,1.1fr]">
        <div class="auth-aside hidden px-8 py-10 text-white lg:block xl:px-10">
            <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-300">SaaS Access Flow</p>
            <h2 class="font-display mt-4 text-4xl font-bold tracking-tight">{{ isset($asideTitle) ? trim((string) $asideTitle) : 'เริ่มจัดการหอพักใน flow เดียว' }}</h2>
            <p class="mt-5 max-w-md text-base leading-8 text-slate-200">{{ isset($asideDescription) ? trim((string) $asideDescription) : 'เชื่อม public site, pricing, owner onboarding และ tenant portal เข้าหากันด้วย auth flow ชุดเดียว' }}</p>

            @isset($aside)
                <div class="mt-8">
                    {{ $aside }}
                </div>
            @else
                <div class="mt-8 space-y-4">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-semibold text-amber-200">Owner Onboarding</p>
                        <p class="mt-2 text-sm leading-7 text-slate-200">สมัครสมาชิก เลือกแพ็กเกจ และสร้าง tenant ใหม่ได้จากหน้าเดียว</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-semibold text-emerald-200">Secure Access</p>
                        <p class="mt-2 text-sm leading-7 text-slate-200">รองรับ email verification, password reset และ two-factor challenge ผ่าน Fortify</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5">
                        <p class="text-sm font-semibold text-sky-200">Tenant Portal</p>
                        <p class="mt-2 text-sm leading-7 text-slate-200">หลังเข้าใช้งานแล้ว ระบบจะพาเข้าหน้า dashboard ที่แยกตาม role และ tenant context</p>
                    </div>
                </div>
            @endisset
        </div>

        <div class="bg-white/90 px-6 py-8 sm:px-10 sm:py-10">
            <div class="mb-8">
                {{ $logo }}
            </div>

            <div class="max-w-xl">
                @isset($eyebrow)
                    <div class="mb-3 text-xs font-bold uppercase tracking-[0.24em] text-amber-700">{{ $eyebrow }}</div>
                @endisset

                <h1 class="font-display text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ isset($title) ? trim((string) $title) : 'Welcome back' }}</h1>
                <p class="mt-3 text-base leading-8 text-slate-600">{{ isset($description) ? trim((string) $description) : 'ยืนยันตัวตนเพื่อเข้าใช้งาน MesukDorm และเชื่อมต่อกับ tenant portal ของคุณ' }}</p>
            </div>

            <div class="mt-8 max-w-xl">
                {{ $slot }}
            </div>

            <div class="mt-8 border-t border-slate-200 pt-5 text-sm text-slate-500">
                <p>ต้องการดูภาพรวมระบบก่อนตัดสินใจ? <a href="{{ route('landing') }}" class="font-semibold text-slate-700 underline decoration-amber-400 decoration-2 underline-offset-4">กลับไปหน้า Landing</a></p>
            </div>
        </div>
    </div>
</div>
