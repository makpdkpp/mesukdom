<x-public-layout title="MesukDom • ระบบบริหารหอพักแบบ SaaS" description="ระบบบริหารหอพักแบบ Multi-Tenant สำหรับเจ้าของหอที่ต้องการจัดการห้อง ผู้เช่า สัญญา บิล และการแจ้งเตือนในหน้าจอเดียว">
    <section class="px-4 pb-12 pt-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.1fr,0.9fr] lg:items-center">
            <div>
                <div class="inline-flex items-center rounded-full border border-amber-200 bg-white/80 px-4 py-2 text-sm font-semibold text-amber-700 shadow-sm">
                    Public Site + SaaS Onboarding พร้อมใช้งาน
                </div>
                <h1 class="mt-6 max-w-3xl text-5xl font-bold leading-tight tracking-tight text-slate-950 sm:text-6xl">
                    จัดการหอพักครบตั้งแต่ห้องว่างจนถึงบิลค้างชำระ
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                    MesukDom รวมงานหลักของเจ้าของหอไว้ในระบบเดียว ทั้งข้อมูลห้อง ผู้เช่า สัญญาเช่า ออกบิล รับชำระ และส่งแจ้งเตือนผ่าน LINE OA โดยเปิดให้แต่ละหอใช้งานแบบ SaaS ได้ทันที
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-amber-500 px-6 py-3 text-base font-bold text-white shadow-xl shadow-amber-500/25 transition hover:bg-amber-600">
                        เริ่มทดลองใช้งาน
                    </a>
                    <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/90 px-6 py-3 text-base font-bold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">
                        ดูแพ็กเกจและราคา
                    </a>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="mesh-card rounded-[1.5rem] p-5">
                        <p class="text-3xl font-extrabold text-slate-950">1 flow</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">สมัครสมาชิก สร้าง tenant และเข้า dashboard ได้ในขั้นตอนเดียว</p>
                    </div>
                    <div class="mesh-card rounded-[1.5rem] p-5">
                        <p class="text-3xl font-extrabold text-slate-950">Multi-tenant</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">รองรับเจ้าของหอหลายรายในระบบเดียวพร้อมแยกข้อมูลตาม tenant</p>
                    </div>
                    <div class="mesh-card rounded-[1.5rem] p-5">
                        <p class="text-3xl font-extrabold text-slate-950">LINE ready</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">ออกบิลและส่ง reminder ผ่าน LINE OA flow ได้แล้วใน MVP</p>
                    </div>
                </div>
            </div>

            <div class="glass-panel relative overflow-hidden rounded-[2rem] p-6 sm:p-8">
                <div class="absolute inset-x-0 top-0 h-28 bg-gradient-to-r from-amber-400/20 via-orange-200/20 to-teal-300/20"></div>
                <div class="relative">
                    <div class="flex items-center justify-between rounded-[1.5rem] bg-slate-950 px-5 py-4 text-white shadow-xl shadow-slate-950/20">
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-300">Owner View</p>
                            <p class="mt-1 text-lg font-bold">Dashboard Snapshot</p>
                        </div>
                        <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">Live Modules</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="mesh-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold text-slate-500">Room Status</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-950">42 ห้อง</p>
                            <p class="mt-2 text-sm text-emerald-700">ว่าง 8 ห้อง, เต็ม 34 ห้อง</p>
                        </div>
                        <div class="mesh-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold text-slate-500">Monthly Revenue</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-950">฿186k</p>
                            <p class="mt-2 text-sm text-sky-700">สรุปรายรับรายเดือนพร้อมบิลค้าง</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white p-5 shadow-lg shadow-slate-200/50 ring-1 ring-slate-200/70 sm:col-span-2">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-500">Billing + Notifications</p>
                                    <p class="mt-2 text-xl font-bold text-slate-950">ออกบิลค่าเช่า, ค่าน้ำ, ค่าไฟ และส่งแจ้งเตือนอัตโนมัติ</p>
                                </div>
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Reminder Flow</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">Draft / Sent / Paid / Overdue</div>
                                <div class="rounded-2xl bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-700">Manual / Slip / Online</div>
                                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">Resident Link Portal</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Core Modules</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">ฟีเจอร์ที่ผูกกับระบบหลังบ้านที่มีอยู่แล้ว</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">หน้า public ไม่ได้เป็นแค่หน้าแนะนำ แต่เชื่อมต่อกับระบบที่มีอยู่แล้วใน MVP ทั้ง onboarding, pricing, dashboard, resident portal และ LINE notification flow</p>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">01</p>
                    <h3 class="mt-4 text-2xl font-bold text-slate-950">Room & Resident</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">เพิ่มห้องพัก ดูสถานะห้อง วางประวัติผู้เช่า และเชื่อมห้องกับข้อมูลลูกห้องใน dashboard เดียว</p>
                </article>
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">02</p>
                    <h3 class="mt-4 text-2xl font-bold text-slate-950">Contract, Invoice, Payment</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">สร้างสัญญา ออกบิลค่าเช่าและค่าบริการ บันทึกชำระเงิน และเช็กสถานะค้างชำระได้ทันที</p>
                </article>
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-rose-700">03</p>
                    <h3 class="mt-4 text-2xl font-bold text-slate-950">Resident Link + LINE OA</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">ผู้เช่าเปิดดูบิลผ่าน public link ได้ และระบบพร้อมส่ง invoice/reminder ผ่าน LINE OA integration</p>
                </article>
            </div>
        </div>
    </section>

    <section id="workflow" class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2rem] bg-slate-950 px-6 py-10 text-white shadow-2xl shadow-slate-950/15 sm:px-8 lg:px-10">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-300">User Flow</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Owner สมัครใช้งานแล้วเข้า user portal ต่อได้เลย</h2>
                </div>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-amber-50">ไปที่หน้าสมัครสมาชิก</a>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-4">
                <div class="rounded-[1.75rem] bg-white/10 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-slate-300">Step 1</p>
                    <p class="mt-3 text-xl font-bold">เลือกแพ็กเกจ</p>
                    <p class="mt-2 text-sm leading-7 text-slate-300">เริ่มจากหน้า pricing แล้วส่ง plan ไปหน้า sign up ได้ทันที</p>
                </div>
                <div class="rounded-[1.75rem] bg-white/10 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-slate-300">Step 2</p>
                    <p class="mt-3 text-xl font-bold">กรอกชื่อหอและผู้ดูแล</p>
                    <p class="mt-2 text-sm leading-7 text-slate-300">ฟอร์มสมัครเก็บ tenant name, owner name, email และรหัสผ่าน</p>
                </div>
                <div class="rounded-[1.75rem] bg-white/10 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-slate-300">Step 3</p>
                    <p class="mt-3 text-xl font-bold">สร้าง tenant อัตโนมัติ</p>
                    <p class="mt-2 text-sm leading-7 text-slate-300">ระบบสร้าง tenant พร้อม owner user และผูก plan ใน transaction เดียว</p>
                </div>
                <div class="rounded-[1.75rem] bg-white/10 p-5 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-slate-300">Step 4</p>
                    <p class="mt-3 text-xl font-bold">เข้า Dashboard</p>
                    <p class="mt-2 text-sm leading-7 text-slate-300">เมื่อยืนยันตัวตนครบแล้ว จะเข้า tenant portal ที่ `/app/dashboard`</p>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Pricing Preview</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">แพ็กเกจที่เปิดขายบน public site</h2>
                </div>
                <a href="{{ route('pricing') }}" class="text-sm font-bold text-slate-700 underline decoration-amber-400 decoration-2 underline-offset-8 transition hover:text-slate-950">ดูรายละเอียดแพ็กเกจทั้งหมด</a>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @foreach($plans->take(3) as $plan)
                    <article class="mesh-card rounded-[2rem] p-6 {{ $loop->first ? 'ring-2 ring-amber-300/60' : '' }}">
                        @if($loop->first)
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-amber-800">Starter Path</span>
                        @endif
                        <h3 class="mt-4 text-2xl font-bold text-slate-950">{{ $plan->name }}</h3>
                        <p class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">{{ number_format((float) $plan->price_monthly, 0) }}<span class="ml-1 text-lg font-semibold text-slate-500">บาท/เดือน</span></p>
                        @if($plan->description)
                            <p class="mt-4 text-base leading-7 text-slate-600">{{ $plan->description }}</p>
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

                        <a href="{{ route('register', ['plan' => $plan->id]) }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">เลือกแพ็กเกจนี้</a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="faq" class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[0.9fr,1.1fr]">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-rose-700">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">คำถามก่อนเริ่มใช้งาน</h2>
            </div>
            <div class="space-y-4">
                <article class="glass-panel rounded-[1.5rem] p-6">
                    <h3 class="text-lg font-bold text-slate-950">สมัครแล้วได้อะไรทันที</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">ระบบจะสร้าง tenant ใหม่ พร้อม owner user และพาเข้าสู่ tenant dashboard หลังผ่าน auth flow</p>
                </article>
                <article class="glass-panel rounded-[1.5rem] p-6">
                    <h3 class="text-lg font-bold text-slate-950">รองรับ forgot password และ email verification หรือยัง</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">เปิดใช้งานแล้วผ่าน Laravel Fortify และมีหน้า public สำหรับแต่ละ flow ครบ</p>
                </article>
                <article class="glass-panel rounded-[1.5rem] p-6">
                    <h3 class="text-lg font-bold text-slate-950">ผู้เช่าเข้าระบบอย่างไร</h3>
                    <p class="mt-3 text-base leading-7 text-slate-600">ตอนนี้มี resident invoice portal ผ่าน public link สำหรับเปิดดูบิลและประวัติการจ่าย โดยไม่ต้อง login ระบบหลัก</p>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2rem] bg-white/80 px-6 py-10 shadow-xl shadow-slate-200/40 ring-1 ring-slate-200/70 backdrop-blur sm:px-8">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Operator Feedback</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">เหตุผลที่ owner ใช้ MesukDom เพื่อเริ่มจัดระบบหอ</h2>
                </div>
                <div class="text-sm font-semibold text-slate-500">ตัวอย่าง testimonial สำหรับหน้า public demo</div>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-base leading-8 text-slate-700">“เดิมต้องตามบิลจากไลน์ส่วนตัวกับ Excel หลายไฟล์ พอรวมไว้ในระบบเดียวเลยเห็นสถานะห้อง สัญญา และค้างชำระง่ายขึ้นมาก”</p>
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <p class="font-bold text-slate-950">นภัส เจ้าของหอ 48 ห้อง</p>
                        <p class="mt-1 text-sm text-slate-500">ใช้ flow ห้องพัก + บิล + reminder</p>
                    </div>
                </article>
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-base leading-8 text-slate-700">“ชอบตรงที่สมัครแล้วระบบสร้าง tenant ให้ทันที ทีมงานเข้ามาจัดการห้องและผู้เช่าได้เลย ไม่ต้องรอ setup หลายขั้น”</p>
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <p class="font-bold text-slate-950">พิมพ์ชนก ผู้ดูแลหอพักหลายอาคาร</p>
                        <p class="mt-1 text-sm text-slate-500">ใช้ owner onboarding + dashboard</p>
                    </div>
                </article>
                <article class="mesh-card rounded-[2rem] p-6">
                    <p class="text-base leading-8 text-slate-700">“resident link ช่วยลดคำถามซ้ำ ๆ เพราะลูกห้องเปิดดูบิลและประวัติการจ่ายเองได้ ส่วนฝั่งเราก็เก็บ log การแจ้งเตือนไว้ครบ”</p>
                    <div class="mt-6 border-t border-slate-200 pt-4">
                        <p class="font-bold text-slate-950">ศิวกร เจ้าหน้าที่หน้าสำนักงาน</p>
                        <p class="mt-1 text-sm text-slate-500">ใช้ resident portal + LINE OA</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="px-4 pb-8 pt-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2rem] bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 px-6 py-10 text-white shadow-2xl shadow-orange-500/20 sm:px-8 lg:flex lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-white/80">Ready To Launch</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">เปิดใช้งาน MesukDom สำหรับหอของคุณภายในไม่กี่นาที</h2>
                <p class="mt-4 text-base leading-8 text-white/90">เริ่มจากแผนทดลองใช้ฟรี หรือเลือกแพ็กเกจที่ตรงกับจำนวนห้องและทีมงานของคุณได้เลย</p>
            </div>
            <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-amber-50">สมัครใช้งาน</a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-white/50 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">เข้าสู่ระบบ</a>
            </div>
        </div>
    </section>
</x-public-layout>
