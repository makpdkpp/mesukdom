<x-public-layout
    title="MesukDorm • ระบบบริหารหอพักและอพาร์ตเมนต์"
    description="ระบบบริหารหอพักสมัยใหม่ จัดการห้อง ผู้เช่า บิล สัญญา และแจ้งซ่อม ครบจบในที่เดียว พร้อม LINE แจ้งเตือนอัตโนมัติ"
    :auth-modals="true"
>

    {{-- ══════════════════════════════════════════════════
        HERO
    ══════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden px-4 pb-20 pt-16 sm:px-6 lg:px-8 lg:pb-28 lg:pt-24">
        <div class="mx-auto max-w-7xl">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                {{-- Text --}}
                <div class="reveal-soft" style="--enter-delay:.05s">
                    <p class="inline-block rounded-full bg-amber-100 px-4 py-1.5 text-sm font-semibold text-amber-700">
                        ทดลองใช้ฟรี — ไม่ต้องผูกบัตร
                    </p>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        ระบบบริหารจัดการ<br class="hidden sm:block">
                        <span class="text-amber-600">หอพักและอพาร์ตเมนต์</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                        MesukDorm ช่วยให้คุณจัดการห้องพัก ผู้เช่า สัญญา บิลค่าเช่า และแจ้งซ่อม ได้ครบจบในที่เดียว พร้อมส่งบิลผ่าน LINE อัตโนมัติ
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @guest
                            <button type="button" data-open-auth-modal="signup"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                เริ่มใช้งานฟรี
                            </button>
                            <a href="#features"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-7 py-3.5 text-base font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                                ดูฟีเจอร์ทั้งหมด
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                        @else
                            <a href="{{ route('app.dashboard') }}"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                                เข้าสู่ Dashboard
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </a>
                        @endguest
                    </div>

                    {{-- Trust badges --}}
                    <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-slate-500">
                        <span class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            ข้อมูลแยกตาม Tenant
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                            ใช้งานผ่านเว็บได้เลย
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            LINE แจ้งเตือนอัตโนมัติ
                        </span>
                    </div>
                </div>

                {{-- Hero visual: Dashboard preview mockup --}}
                <div class="reveal-up relative" style="--enter-delay:.18s">
                    <div class="relative rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xl shadow-slate-900/10 sm:p-6">
                        {{-- Fake browser bar --}}
                        <div class="mb-4 flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-red-400"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                            <span class="h-3 w-3 rounded-full bg-green-400"></span>
                            <span class="ml-3 flex-1 rounded-md bg-slate-100 px-3 py-1 text-xs text-slate-400">mesukdorm.com/app/dashboard</span>
                        </div>
                        {{-- Dashboard mockup --}}
                        <div class="space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-xl bg-amber-50 p-4 text-center">
                                    <p class="text-2xl font-extrabold text-amber-600">{{ number_format($publicStats['tenants_total']) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">โครงการ</p>
                                </div>
                                <div class="rounded-xl bg-teal-50 p-4 text-center">
                                    <p class="text-2xl font-extrabold text-teal-600">{{ number_format($publicStats['rooms_total']) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ห้องทั้งหมด</p>
                                </div>
                                <div class="rounded-xl bg-emerald-50 p-4 text-center">
                                    <p class="text-2xl font-extrabold text-emerald-600">{{ $publicStats['occupancy_rate'] }}%</p>
                                    <p class="mt-1 text-xs text-slate-500">อัตราเข้าพัก</p>
                                </div>
                            </div>
                            {{-- Fake room grid --}}
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="mb-2 text-xs font-semibold text-slate-400">ผังห้องพัก</p>
                                <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-8">
                                    @for($i = 0; $i < 16; $i++)
                                        <div class="aspect-square rounded-md {{ $i < round(16 * $publicStats['occupancy_rate'] / 100) ? 'bg-teal-400' : 'bg-slate-200' }}"></div>
                                    @endfor
                                </div>
                                <div class="mt-2 flex items-center gap-4 text-[10px] text-slate-400">
                                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-teal-400"></span> มีผู้เช่า</span>
                                    <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-slate-200"></span> ว่าง</span>
                                </div>
                            </div>
                            {{-- Fake chart bar --}}
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <p class="mb-2 text-xs font-semibold text-slate-400">รายได้รายเดือน</p>
                                <div class="flex items-end gap-1.5" style="height:60px">
                                    @foreach([40,65,55,80,70,90,75,85,95,60,78,88] as $h)
                                        <div class="flex-1 rounded-t bg-amber-400" style="height:{{ $h }}%; opacity:{{ $loop->last ? '0.9' : ($h > 75 ? '0.7' : '0.4') }}"></div>
                                    @endforeach
                                </div>
                                <div class="mt-1 flex justify-between text-[9px] text-slate-400">
                                    <span>ม.ค.</span><span>มิ.ย.</span><span>ธ.ค.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Decorative dots --}}
                    <div class="absolute -right-4 -top-4 -z-10 h-24 w-24 rounded-full bg-amber-200/40 blur-2xl"></div>
                    <div class="absolute -bottom-4 -left-4 -z-10 h-32 w-32 rounded-full bg-teal-200/40 blur-2xl"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        STATS BAR
    ══════════════════════════════════════════════════ --}}
    <section class="border-y border-slate-200/60 bg-white/70 px-4 py-10 backdrop-blur sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-5xl grid-cols-2 gap-8 text-center lg:grid-cols-4">
            <div>
                <p class="text-3xl font-extrabold text-slate-900">{{ number_format($publicStats['tenants_total']) }}</p>
                <p class="mt-1 text-sm text-slate-500">โครงการที่ใช้งาน</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-slate-900">{{ number_format($publicStats['rooms_total']) }}</p>
                <p class="mt-1 text-sm text-slate-500">ห้องในระบบ</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-slate-900">{{ $publicStats['occupancy_rate'] }}%</p>
                <p class="mt-1 text-sm text-slate-500">อัตราเข้าพักเฉลี่ย</p>
            </div>
            <div>
                <p class="text-3xl font-extrabold text-slate-900">฿{{ number_format((float) $publicStats['monthly_revenue'], 0) }}</p>
                <p class="mt-1 text-sm text-slate-500">รายได้เดือนนี้</p>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FEATURES (8 items)
    ══════════════════════════════════════════════════ --}}
    <section id="features" class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">ฟีเจอร์ครบวงจร</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">ครบทุกเรื่องที่เจ้าของหอพักต้องใช้</h2>
                <p class="mt-4 text-lg text-slate-600">จัดการทุกอย่างในที่เดียว ตั้งแต่ห้องพัก สัญญา บิล ไปจนถึงแจ้งซ่อมและแจ้งเตือนผ่าน LINE</p>
            </div>

            <div class="mt-16 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Feature 01 --}}
                <div class="reveal-up group" style="--enter-delay:.05s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3H21"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-amber-600">01</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">จัดการห้องพักและผังห้อง</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">กำหนดจำนวนชั้น ห้อง และสถานะการเช่า ดูผังห้องแบบภาพรวมได้ทันที</p>
                </div>

                {{-- Feature 02 --}}
                <div class="reveal-up group" style="--enter-delay:.1s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 transition group-hover:bg-teal-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-teal-600">02</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">จัดเก็บข้อมูลผู้เช่า</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">บันทึกข้อมูลผู้เช่าอย่างครบถ้วน เบอร์โทร สัญญา วันเข้า-ออก ค้นหาง่ายในที่เดียว</p>
                </div>

                {{-- Feature 03 --}}
                <div class="reveal-up group" style="--enter-delay:.15s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-amber-600">03</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">ทำสัญญาเช่าออนไลน์</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">สร้างสัญญาเช่า กำหนดวันเริ่ม-สิ้นสุด ค่ามัดจำ เก็บประวัติสัญญาทุกฉบับในระบบ</p>
                </div>

                {{-- Feature 04 --}}
                <div class="reveal-up group" style="--enter-delay:.2s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 transition group-hover:bg-teal-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-teal-600">04</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">บันทึกค่าน้ำ-ค่าไฟ</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">จดมิเตอร์ค่าน้ำ ค่าไฟผ่านระบบ คำนวณยอดอัตโนมัติ ลดความผิดพลาดจากการคิดเอง</p>
                </div>

                {{-- Feature 05 --}}
                <div class="reveal-up group" style="--enter-delay:.25s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-amber-600">05</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">สร้างใบแจ้งหนี้อัตโนมัติ</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">สรุปยอดค่าเช่า ค่าน้ำ ค่าไฟ และค่าบริการเป็นบิลรายเดือน ส่งถึงผู้เช่าได้ทันที</p>
                </div>

                {{-- Feature 06 --}}
                <div class="reveal-up group" style="--enter-delay:.3s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 transition group-hover:bg-teal-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 14.625v2.25m0 2.25h2.25m2.25 0h-2.25m0 0v-2.25m0 0h-2.25"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-teal-600">06</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">สแกนจ่ายผ่าน QR Code</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">รองรับ PromptPay QR Code ผู้เช่าสแกนจ่ายได้ทันที ตรวจสอบสถานะการชำระแบบเรียลไทม์</p>
                </div>

                {{-- Feature 07 --}}
                <div class="reveal-up group" style="--enter-delay:.35s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 transition group-hover:bg-amber-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.1-3.4a1.5 1.5 0 01-.42-2.08l.72-1.08a1.5 1.5 0 012.08-.42l5.1 3.4m-2.38 3.58l5.1 3.4a1.5 1.5 0 002.08-.42l.72-1.08a1.5 1.5 0 00-.42-2.08l-5.1-3.4m-2.38 3.58l2.38-3.58"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-amber-600">07</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">บันทึกแจ้งซ่อม</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">รับแจ้งซ่อมจากผู้เช่า ติดตามสถานะงาน เก็บประวัติการซ่อมของแต่ละห้องอย่างเป็นระบบ</p>
                </div>

                {{-- Feature 08 --}}
                <div class="reveal-up group" style="--enter-delay:.4s">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-100 text-teal-600 transition group-hover:bg-teal-500 group-hover:text-white">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                    </div>
                    <p class="mt-5 text-xs font-bold text-teal-600">08</p>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">ส่งบิลผ่าน LINE อัตโนมัติ</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">เชื่อม LINE OA ส่งบิลค่าเช่า แจ้งเตือนค่าค้าง และ broadcast ข่าวสารถึงผู้เช่าได้ในคลิกเดียว</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        HOW IT WORKS
    ══════════════════════════════════════════════════ --}}
    <section class="bg-slate-50 px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-teal-600">เริ่มต้นง่าย</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">ใช้งานได้ใน 3 ขั้นตอน</h2>
            </div>

            <div class="mt-16 grid gap-8 lg:grid-cols-3">
                <div class="reveal-up relative rounded-2xl border border-slate-200 bg-white p-8 text-center" style="--enter-delay:.05s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500 text-2xl font-extrabold text-white shadow-lg shadow-amber-500/25">1</div>
                    <h3 class="mt-6 text-xl font-bold text-slate-900">สมัครบัญชี</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">กรอกชื่อหอพัก เลือกแพ็กเกจ สร้างบัญชีเสร็จใน 1 นาที ทดลองใช้ฟรีไม่มีค่าใช้จ่าย</p>
                </div>
                <div class="reveal-up relative rounded-2xl border border-slate-200 bg-white p-8 text-center" style="--enter-delay:.15s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500 text-2xl font-extrabold text-white shadow-lg shadow-amber-500/25">2</div>
                    <h3 class="mt-6 text-xl font-bold text-slate-900">ตั้งค่าหอพัก</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">เพิ่มห้องพัก ตั้งราคาค่าเช่า นำเข้าข้อมูลผู้เช่าเดิม พร้อมใช้งานจริงได้เลย</p>
                </div>
                <div class="reveal-up relative rounded-2xl border border-slate-200 bg-white p-8 text-center" style="--enter-delay:.25s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500 text-2xl font-extrabold text-white shadow-lg shadow-amber-500/25">3</div>
                    <h3 class="mt-6 text-xl font-bold text-slate-900">เริ่มบริหาร</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">ออกบิล ส่ง LINE แจ้งเตือน รับแจ้งซ่อม ติดตามรายได้ ทุกอย่างจัดการจากหน้าจอเดียว</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        MID CTA
    ══════════════════════════════════════════════════ --}}
    @guest
    <section class="px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl rounded-3xl bg-gradient-to-br from-amber-500 to-amber-600 px-8 py-12 text-center text-white shadow-xl shadow-amber-500/20 sm:px-12 sm:py-16">
            <h2 class="text-3xl font-extrabold sm:text-4xl">พร้อมเปลี่ยนการบริหารหอพัก<br>ให้เป็นเรื่องง่ายแล้วหรือยัง?</h2>
            <p class="mx-auto mt-4 max-w-xl text-lg text-amber-100">เริ่มต้นฟรี ไม่ต้องผูกบัตรเครดิต ตั้งค่าเสร็จใน 5 นาที</p>
            <button type="button" data-open-auth-modal="signup"
                class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-amber-600 shadow-lg transition hover:bg-amber-50">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                ทดลองใช้ฟรี
            </button>
        </div>
    </section>
    @endguest

    {{-- ══════════════════════════════════════════════════
        PRICING
    ══════════════════════════════════════════════════ --}}
    <section id="pricing" class="bg-slate-50 px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">แพ็กเกจและราคา</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">เลือกแพ็กเกจที่เหมาะกับหอพักของคุณ</h2>
                <p class="mt-4 text-lg text-slate-600">ทุกแพ็กเกจรวมฟีเจอร์หลักครบ — จัดการห้อง บิล สัญญา และ LINE แจ้งเตือน</p>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                @forelse($plans->take(3) as $plan)
                    <article class="reveal-up relative flex flex-col rounded-2xl border {{ $loop->iteration === 2 ? 'border-amber-400 bg-white ring-2 ring-amber-400' : 'border-slate-200 bg-white' }} p-7 shadow-sm" style="--enter-delay:{{ 0.05 + $loop->index * 0.1 }}s">

                        @if($loop->iteration === 2)
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-1 text-xs font-bold text-white shadow-lg">แนะนำ</span>
                        @endif

                        <p class="text-sm font-bold uppercase tracking-wider {{ $loop->iteration === 2 ? 'text-amber-600' : 'text-slate-500' }}">{{ $plan->slug ?: $plan->name }}</p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-900">{{ $plan->name }}</h3>

                        <p class="mt-5">
                            <span class="text-4xl font-extrabold text-slate-900">{{ number_format((float) $plan->price_monthly, 0) }}</span>
                            <span class="ml-1 text-base text-slate-500">บาท/เดือน</span>
                        </p>

                        @if($plan->description)
                            <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $plan->description }}</p>
                        @endif

                        @php($limits = (array) ($plan->limits ?? []))
                        <ul class="mt-6 flex-1 space-y-3">
                            @forelse($limits as $key => $value)
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <svg class="h-5 w-5 flex-shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ ucfirst((string) $key) }}: {{ $value }}
                                </li>
                            @empty
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <svg class="h-5 w-5 flex-shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    ครบทุกฟีเจอร์หลัก
                                </li>
                            @endforelse
                        </ul>

                        <div class="mt-8">
                            @guest
                                <button type="button" data-open-auth-modal="signup" data-plan-id="{{ $plan->id }}"
                                    class="flex w-full items-center justify-center rounded-xl {{ $loop->iteration === 2 ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/25 hover:bg-amber-600' : 'bg-slate-900 text-white hover:bg-slate-800' }} px-5 py-3 text-sm font-bold transition">
                                    เลือกแพ็กเกจนี้
                                </button>
                            @else
                                <a href="{{ route('app.dashboard') }}"
                                    class="flex w-full items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                    ไปที่ Dashboard
                                </a>
                            @endguest
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center">
                        <p class="text-lg font-bold text-slate-900">ยังไม่มีแพ็กเกจในระบบ</p>
                        <p class="mt-2 text-sm text-slate-600">เพิ่มข้อมูล plans แล้วราคาจะแสดงอัตโนมัติ</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FINAL CTA
    ══════════════════════════════════════════════════ --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">เริ่มบริหารหอพักอย่างมืออาชีพ<br>ตั้งแต่วันนี้</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-slate-600">ไม่ว่าจะหอพัก 10 ห้อง หรืออพาร์ตเมนต์ 200 ห้อง MesukDorm ช่วยให้คุณจัดการได้ครบจบในที่เดียว</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @guest
                    <button type="button" data-open-auth-modal="signup"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        ทดลองใช้ฟรี
                    </button>
                    <button type="button" data-open-auth-modal="login"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-8 py-4 text-base font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                        เข้าสู่ระบบ
                    </button>
                @else
                    <a href="{{ route('app.dashboard') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                        เข้าสู่ Dashboard
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                @endguest
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        AUTH MODALS (logic preserved)
    ══════════════════════════════════════════════════ --}}
    @guest
        <div id="auth-modal-backdrop" class="pointer-events-none fixed inset-0 z-40 hidden bg-slate-950/35 opacity-0 transition duration-200"></div>

        {{-- LOGIN MODAL --}}
        <div id="login-modal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center px-4 opacity-0 transition duration-200 sm:px-6">
            <div class="absolute inset-0" data-close-auth-modal></div>
            <div class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-8">
                <button type="button" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-close-auth-modal>
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <h3 class="text-2xl font-bold text-slate-900">เข้าสู่ระบบ</h3>
                <p class="mt-2 text-sm text-slate-600">ใช้บัญชีเดิมเพื่อเข้าจัดการข้อมูลห้อง ผู้เช่า บิล และการแจ้งเตือน</p>

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="landing_login_email" class="text-sm font-medium text-slate-700">อีเมล</label>
                        <input id="landing_login_email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                    </div>
                    <div>
                        <label for="landing_login_password" class="text-sm font-medium text-slate-700">รหัสผ่าน</label>
                        <input id="landing_login_password" name="password" type="password" required autocomplete="current-password" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                    </div>
                    <label for="landing_remember" class="flex items-center gap-2 text-sm text-slate-600">
                        <input id="landing_remember" name="remember" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500" />
                        จดจำการเข้าสู่ระบบ
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-amber-600 hover:text-amber-700">ลืมรหัสผ่าน?</a>
                        @endif
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-amber-600">เข้าสู่ระบบ</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SIGNUP MODAL --}}
        <div id="signup-modal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center px-4 opacity-0 transition duration-200 sm:px-6">
            <div class="absolute inset-0" data-close-auth-modal></div>
            <div class="relative max-h-[90vh] w-full max-w-lg overflow-auto rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl sm:p-8">
                <button type="button" class="absolute right-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" data-close-auth-modal>
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <h3 class="text-2xl font-bold text-slate-900">สร้างบัญชีใหม่</h3>
                <p class="mt-2 text-sm text-slate-600">กรอกข้อมูลด้านล่างเพื่อเริ่มใช้งาน MesukDorm สำหรับหอพักของคุณ</p>

                <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="landing_register_name" class="text-sm font-medium text-slate-700">ชื่อ-นามสกุล</label>
                        <input id="landing_register_name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                    </div>
                    <div>
                        <label for="landing_register_tenant_name" class="text-sm font-medium text-slate-700">ชื่อหอพัก / อพาร์ตเมนต์</label>
                        <input id="landing_register_tenant_name" name="tenant_name" type="text" value="{{ old('tenant_name') }}" required autocomplete="organization" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                    </div>
                    <div>
                        <label for="landing_register_plan_id" class="text-sm font-medium text-slate-700">เลือกแพ็กเกจ</label>
                        <select id="landing_register_plan_id" name="plan_id" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" required>
                            @foreach(($plans ?? []) as $plan)
                                <option value="{{ $plan->id }}" @selected((string) old('plan_id', request('plan')) === (string) $plan->id)>
                                    {{ $plan->name }} ({{ number_format((float) $plan->price_monthly, 0) }} บาท/เดือน)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="landing_register_email" class="text-sm font-medium text-slate-700">อีเมล</label>
                        <input id="landing_register_email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="landing_register_password" class="text-sm font-medium text-slate-700">รหัสผ่าน</label>
                            <input id="landing_register_password" name="password" type="password" required autocomplete="new-password" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                        </div>
                        <div>
                            <label for="landing_register_password_confirmation" class="text-sm font-medium text-slate-700">ยืนยันรหัสผ่าน</label>
                            <input id="landing_register_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 block w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500" />
                        </div>
                    </div>

                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <label for="landing_register_terms" class="flex items-start gap-2 text-sm text-slate-600">
                            <input name="terms" id="landing_register_terms" type="checkbox" required class="mt-1 rounded border-slate-300 text-amber-500 focus:ring-amber-500" />
                            <span>
                                {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                    'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-semibold text-amber-600 underline hover:text-amber-700">'.__('Terms of Service').'</a>',
                                    'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-semibold text-amber-600 underline hover:text-amber-700">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </span>
                        </label>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" data-open-auth-modal="login" class="text-sm text-amber-600 hover:text-amber-700">มีบัญชีอยู่แล้ว?</button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-amber-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-amber-600">สร้างบัญชี</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const backdrop = document.getElementById('auth-modal-backdrop');
                const loginModal = document.getElementById('login-modal');
                const signupModal = document.getElementById('signup-modal');
                const planSelect = document.getElementById('landing_register_plan_id');
                const modals = { login: loginModal, signup: signupModal };

                function setModalState(name) {
                    Object.entries(modals).forEach(([key, modal]) => {
                        const active = key === name;
                        modal.classList.toggle('hidden', !active);
                        modal.classList.toggle('opacity-100', active);
                        modal.classList.toggle('pointer-events-auto', active);
                        modal.classList.toggle('opacity-0', !active);
                        modal.classList.toggle('pointer-events-none', !active);
                    });

                    const isOpen = Boolean(name);
                    backdrop.classList.toggle('hidden', !isOpen);
                    backdrop.classList.toggle('opacity-100', isOpen);
                    backdrop.classList.toggle('pointer-events-auto', isOpen);
                    backdrop.classList.toggle('opacity-0', !isOpen);
                    backdrop.classList.toggle('pointer-events-none', !isOpen);
                    document.body.classList.toggle('overflow-hidden', isOpen);
                }

                document.querySelectorAll('[data-open-auth-modal]').forEach((button) => {
                    button.addEventListener('click', function () {
                        const target = this.getAttribute('data-open-auth-modal');
                        const planId = this.getAttribute('data-plan-id');
                        if (target === 'signup' && planId && planSelect) {
                            planSelect.value = planId;
                        }
                        setModalState(target);
                    });
                });

                document.querySelectorAll('[data-close-auth-modal]').forEach((button) => {
                    button.addEventListener('click', function () {
                        setModalState(null);
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') {
                        setModalState(null);
                    }
                });
            });
        </script>
    @endguest

</x-public-layout>