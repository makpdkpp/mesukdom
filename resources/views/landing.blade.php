<x-public-layout
    title="MesukDorm • ระบบบริหารหอพักและอพาร์ตเมนต์"
    description="ระบบบริหารหอพักสมัยใหม่ จัดการห้อง ผู้เช่า บิล สัญญา และแจ้งซ่อม ครบจบในที่เดียว พร้อม LINE แจ้งเตือนอัตโนมัติ"
    :auth-modals="true"
>

    {{-- ══════════════════════════════════════════════════
        HERO
    ══════════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden px-4 pb-16 pt-14 sm:px-6 lg:px-8 lg:pb-24 lg:pt-20">
        <div class="mx-auto max-w-7xl">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                <div class="reveal-soft" style="--enter-delay:.05s">
                    <p class="inline-block rounded-full bg-amber-100 px-4 py-1.5 text-sm font-semibold text-amber-700">
                        ทดลองใช้ฟรี — ไม่ต้องผูกบัตร
                    </p>

                    <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        จัดการหอพัก ออกบิล ส่ง LINE<br class="hidden lg:block">
                        <span class="text-amber-600">ครบในระบบเดียว</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-600">
                        MesukDorm ช่วยเจ้าของหอพักและอพาร์ตเมนต์จัดการห้อง ผู้เช่า สัญญา ค่าน้ำค่าไฟ ใบแจ้งหนี้ การชำระเงิน และงานแจ้งซ่อม โดยไม่ต้องพึ่ง Excel หลายไฟล์หรือไล่แชททีละห้อง
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        @guest
                            <button type="button" data-open-auth-modal="signup"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-7 py-3.5 text-base font-bold text-white shadow-lg shadow-amber-500/25 transition hover:bg-amber-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                เริ่มใช้งานฟรี
                            </button>
                            <a href="#pricing"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-7 py-3.5 text-base font-bold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                                ดูแพ็กเกจและราคา
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

                <div class="reveal-up relative" style="--enter-delay:.18s">
                    <div class="relative rounded-2xl border border-slate-200/80 bg-white p-4 shadow-2xl shadow-slate-900/10 sm:p-6">
                        <div class="mb-4 flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-red-400"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-400"></span>
                            <span class="h-3 w-3 rounded-full bg-green-400"></span>
                            <span class="ml-3 flex-1 rounded-md bg-slate-100 px-3 py-1 text-xs text-slate-400">mesukdorm.com/app/dashboard</span>
                        </div>
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
                            <div class="grid gap-3 lg:grid-cols-[0.95fr,1.05fr]">
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-400">ผังห้องพัก</p>
                                    <div class="grid grid-cols-6 gap-1.5">
                                        @for($i = 0; $i < 18; $i++)
                                            <div class="aspect-square rounded-md {{ $i < round(18 * $publicStats['occupancy_rate'] / 100) ? 'bg-teal-400' : 'bg-slate-200' }}"></div>
                                        @endfor
                                    </div>
                                    <div class="mt-2 flex items-center gap-4 text-[10px] text-slate-400">
                                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-teal-400"></span> มีผู้เช่า</span>
                                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-sm bg-slate-200"></span> ว่าง</span>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-slate-400">งานเดือนนี้</p>
                                        <span class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700">Auto billing</span>
                                    </div>
                                    <div class="mt-3 space-y-2 text-xs">
                                        <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2"><span>บิลรอชำระ</span><strong class="text-amber-600">{{ number_format($publicStats['pending_payments']) }}</strong></div>
                                        <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2"><span>บิลเกินกำหนด</span><strong class="text-rose-600">{{ number_format($publicStats['overdue_invoices']) }}</strong></div>
                                        <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2"><span>แจ้งซ่อมเปิดอยู่</span><strong class="text-teal-600">{{ number_format($publicStats['open_repairs']) }}</strong></div>
                                    </div>
                                </div>
                            </div>
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
                <p class="text-3xl font-extrabold text-slate-900">{{ number_format($publicStats['open_repairs']) }}</p>
                <p class="mt-1 text-sm text-slate-500">งานแจ้งซ่อมเปิดอยู่</p>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        BEFORE / AFTER
    ══════════════════════════════════════════════════ --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-teal-600">แก้ปัญหางานประจำทุกเดือน</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">จากงานเอกสารกระจัดกระจาย สู่ระบบเดียวที่มองเห็นทั้งหอ</h2>
                <p class="mt-4 text-lg text-slate-600">หน้าเดียวช่วยให้เจ้าของหอรู้ว่าห้องไหนว่าง ใครค้างชำระ บิลไหนส่งแล้ว และงานซ่อมไหนยังไม่ปิด</p>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-rose-100 bg-white p-7 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-widest text-rose-600">ก่อนใช้ระบบ</p>
                    <ul class="mt-6 space-y-4 text-sm leading-relaxed text-slate-700">
                        @foreach(['จดค่าน้ำค่าไฟในสมุดหรือ Excel หลายไฟล์', 'ออกบิลทีละห้องและส่งแชทซ้ำทุกเดือน', 'ตามยอดค้างผ่าน LINE ส่วนตัวจนข้อมูลปนกัน', 'ค้นหาสัญญา เอกสาร และประวัติผู้เช่าย้อนหลังยาก', 'ไม่เห็นภาพรวมห้องว่าง รายได้ และงานแจ้งซ่อมทันที'] as $item)
                            <li class="flex gap-3"><span class="mt-1 h-2 w-2 flex-shrink-0 rounded-full bg-rose-400"></span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-2xl border border-teal-100 bg-teal-50/60 p-7 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-widest text-teal-700">หลังใช้ MesukDorm</p>
                    <ul class="mt-6 space-y-4 text-sm leading-relaxed text-slate-700">
                        @foreach(['บันทึกมิเตอร์และคำนวณยอดรายเดือนอัตโนมัติ', 'สร้างใบแจ้งหนี้และส่งผ่าน LINE OA ได้ในไม่กี่คลิก', 'ติดตามยอดชำระ ยอดค้าง และบิลเกินกำหนดจาก dashboard', 'เก็บข้อมูลผู้เช่า สัญญา และเอกสารไว้ในระบบเดียว', 'ดูรายได้ ห้องว่าง และงานซ่อมที่ต้องจัดการได้ทันที'] as $item)
                            <li class="flex gap-3"><svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FEATURES
    ══════════════════════════════════════════════════ --}}
    <section id="features" class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">Workflow ครบวงจร</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">จัดกลุ่มตามงานจริงของเจ้าของหอ</h2>
                <p class="mt-4 text-lg text-slate-600">ไม่ใช่แค่รายการฟีเจอร์ แต่เป็น flow ตั้งแต่รับผู้เช่า ออกบิล รับชำระ ไปจนถึงสื่อสารและดูภาพรวม</p>
            </div>

            <div class="mt-16 grid gap-6 lg:grid-cols-4">
                @foreach([
                    ['title' => 'จัดการห้องและผู้เช่า', 'body' => 'ผังห้อง สถานะห้อง ข้อมูลผู้เช่า สัญญาเช่า และเอกสารสำคัญรวมไว้ในที่เดียว', 'items' => ['ผังห้องและสถานะว่าง/มีผู้เช่า', 'ข้อมูลผู้เช่าและวันเข้าออก', 'สัญญาเช่าและค่ามัดจำ', 'เอกสารและประวัติย้อนหลัง']],
                    ['title' => 'ออกบิลและรับชำระ', 'body' => 'ลดเวลางานปลายเดือนด้วยการคำนวณบิล ค่าน้ำค่าไฟ QR และสถานะชำระเงิน', 'items' => ['บันทึกมิเตอร์น้ำไฟ', 'ใบแจ้งหนี้รายเดือน', 'PromptPay QR Code', 'ติดตามยอดชำระและยอดค้าง']],
                    ['title' => 'สื่อสารกับผู้เช่า', 'body' => 'ส่งบิล แจ้งเตือน และจัดการเรื่องซ่อมผ่าน workflow ที่ค้นหาย้อนหลังได้', 'items' => ['ส่งบิลผ่าน LINE OA', 'แจ้งเตือนยอดค้าง', 'Broadcast ข่าวสาร', 'รับแจ้งซ่อมและติดตามสถานะ']],
                    ['title' => 'ภาพรวมสำหรับเจ้าของ', 'body' => 'เห็นตัวเลขสำคัญของหอพักโดยไม่ต้องรวมข้อมูลเองจากหลายแหล่ง', 'items' => ['Dashboard รายได้', 'ห้องว่างและอัตราเข้าพัก', 'รายงานการชำระเงิน', 'รองรับหลายอาคาร/หลายหอ']],
                ] as $index => $group)
                    <article class="reveal-up rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" style="--enter-delay:{{ 0.05 + $index * 0.08 }}s">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $index % 2 === 0 ? 'bg-amber-100 text-amber-600' : 'bg-teal-100 text-teal-600' }} text-lg font-extrabold">{{ $index + 1 }}</div>
                        <h3 class="mt-5 text-xl font-bold text-slate-900">{{ $group['title'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $group['body'] }}</p>
                        <ul class="mt-5 space-y-3 text-sm text-slate-700">
                            @foreach($group['items'] as $item)
                                <li class="flex gap-2"><svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FIT
    ══════════════════════════════════════════════════ --}}
    <section id="fit" class="bg-white/70 px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-10 lg:grid-cols-[0.85fr,1.15fr] lg:items-start">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-widest text-teal-600">เหมาะกับใคร</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">เจ้าของหอที่อยากลดงานซ้ำทุกเดือน</h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-600">เริ่มได้ตั้งแต่หอพักขนาดเล็กไปจนถึงอพาร์ตเมนต์หลายอาคาร โดยเลือกแพ็กเกจตามจำนวนห้องและ workflow ที่ต้องใช้จริง</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach([
                        ['title' => 'หอพัก 10-30 ห้อง', 'body' => 'เริ่มจัดระบบห้อง ผู้เช่า และบิลให้เป็นระเบียบ โดยไม่ต้องใช้โปรแกรมซับซ้อน'],
                        ['title' => 'อพาร์ตเมนต์ 30-100 ห้อง', 'body' => 'ลดเวลาการออกบิล ติดตามยอดค้าง และดูภาพรวมรายได้ทุกเดือน'],
                        ['title' => 'หลายอาคารหรือหลายสาขา', 'body' => 'แยกข้อมูลตามโครงการ เห็นสถานะห้องและงานค้างจากที่เดียว'],
                        ['title' => 'ทีมที่ยังใช้ Excel / LINE / สมุดจด', 'body' => 'ย้ายงานประจำเข้าระบบ ลดการตกหล่นและค้นหาข้อมูลย้อนหลังได้ง่ายขึ้น'],
                    ] as $index => $fit)
                        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-widest text-amber-600">Use case {{ $index + 1 }}</p>
                            <h3 class="mt-3 text-lg font-bold text-slate-900">{{ $fit['title'] }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $fit['body'] }}</p>
                        </article>
                    @endforeach
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
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">กรอกชื่อหอพัก เลือกแพ็กเกจ และเริ่มทดลองใช้ฟรีโดยไม่ต้องผูกบัตร</p>
                </div>
                <div class="reveal-up relative rounded-2xl border border-slate-200 bg-white p-8 text-center" style="--enter-delay:.15s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500 text-2xl font-extrabold text-white shadow-lg shadow-amber-500/25">2</div>
                    <h3 class="mt-6 text-xl font-bold text-slate-900">ตั้งค่าหอพัก</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">เพิ่มอาคาร ชั้น ห้อง ค่าเช่า ค่าน้ำค่าไฟ และเตรียมนำเข้าข้อมูลผู้เช่าเดิม</p>
                </div>
                <div class="reveal-up relative rounded-2xl border border-slate-200 bg-white p-8 text-center" style="--enter-delay:.25s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-500 text-2xl font-extrabold text-white shadow-lg shadow-amber-500/25">3</div>
                    <h3 class="mt-6 text-xl font-bold text-slate-900">เริ่มบริหาร</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">สร้างใบแจ้งหนี้ ส่ง LINE ติดตามยอดชำระ และดูภาพรวมจาก dashboard</p>
                </div>
            </div>

            <p class="mx-auto mt-8 max-w-2xl rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-center text-sm leading-relaxed text-amber-800">ต้องการย้ายข้อมูลเดิมจาก Excel? เตรียมไฟล์ห้องและผู้เช่าไว้ แล้วใช้เป็นจุดตั้งต้นสำหรับการตั้งค่าระบบได้</p>
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
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">เลือกตามจำนวนห้องและรูปแบบการใช้งาน</h2>
                <p class="mt-4 text-lg text-slate-600">ทุกแพ็กเกจมีฟีเจอร์หลักสำหรับจัดการห้อง ผู้เช่า บิล และการแจ้งเตือน เลือกตามขนาดหอและบริการเสริมที่ต้องใช้</p>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-3">
                @php
                    $pricingPlans = $plans->take(3)->values();
                    $recommendedPlan = $pricingPlans->first(fn ($candidate) => $candidate->isRecommended());

                    if ($recommendedPlan) {
                        $otherPlans = $pricingPlans
                            ->reject(fn ($candidate) => $candidate->id === $recommendedPlan->id)
                            ->sortBy('price_monthly')
                            ->values();

                        $orderedPlans = collect();

                        if ($otherPlans->count() > 0) {
                            $orderedPlans->push($otherPlans->first());
                        }

                        $orderedPlans->push($recommendedPlan);

                        if ($otherPlans->count() > 1) {
                            $orderedPlans->push($otherPlans->last());
                        }
                    } else {
                        $orderedPlans = $pricingPlans->sortBy('price_monthly')->values();
                    }
                @endphp

                @forelse($orderedPlans as $plan)
                    @php($isFeaturedPlan = $recommendedPlan && $plan->id === $recommendedPlan->id)
                    <article class="reveal-up relative flex flex-col rounded-2xl border {{ $isFeaturedPlan ? 'border-amber-400 bg-white ring-2 ring-amber-400' : 'border-slate-200 bg-white' }} p-7 shadow-sm" style="--enter-delay:{{ 0.05 + $loop->index * 0.1 }}s">

                        @if($isFeaturedPlan)
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-4 py-1 text-xs font-bold text-white shadow-lg">แนะนำ</span>
                        @endif

                        <p class="text-sm font-bold uppercase tracking-wider {{ $isFeaturedPlan ? 'text-amber-600' : 'text-slate-500' }}">{{ $plan->slug ?: $plan->name }}</p>
                        <h3 class="mt-3 text-2xl font-bold text-slate-900">{{ $plan->name }}</h3>

                        <p class="mt-5">
                            <span class="text-4xl font-extrabold text-slate-900">{{ number_format((float) $plan->price_monthly, 0) }}</span>
                            <span class="ml-1 text-base text-slate-500">บาท/ห้อง/เดือน</span>
                        </p>

                        @if($plan->description)
                            <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $plan->description }}</p>
                        @endif

                        <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-relaxed text-slate-600">
                            เหมาะสำหรับ {{ $loop->first ? 'หอพักเริ่มต้นหรือทีมที่เพิ่งย้ายจาก Excel' : ($isFeaturedPlan ? 'หอพักที่ต้องการใช้ billing และ LINE เป็น workflow หลัก' : 'อพาร์ตเมนต์ที่มีจำนวนห้องมากขึ้นและต้องการโควตาเพิ่ม') }}
                        </p>

                        @php($limits = $plan->displayLimits())
                        <ul class="mt-6 flex-1 space-y-3">
                            @forelse($limits as $key => $value)
                                <li class="flex items-center gap-3 text-sm text-slate-700">
                                    <svg class="h-5 w-5 flex-shrink-0 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    {{ $key }}: {{ $value }}
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
                                    class="flex w-full items-center justify-center rounded-xl {{ $isFeaturedPlan ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/25 hover:bg-amber-600' : 'bg-slate-900 text-white hover:bg-slate-800' }} px-5 py-3 text-sm font-bold transition">
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

            <div class="mt-10 text-center">
                <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-900 hover:text-slate-900">ดูรายละเอียดแพ็กเกจทั้งหมด</a>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        TRUST
    ══════════════════════════════════════════════════ --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.05fr,0.95fr] lg:items-center">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-teal-600">Trust & Support</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">ออกแบบสำหรับข้อมูลหอพักที่ต้องปลอดภัยและตรวจสอบได้</h2>
                <p class="mt-4 text-lg leading-relaxed text-slate-600">ข้อมูลผู้เช่า บิล และการชำระเงินเป็นหัวใจของธุรกิจหอพัก MesukDorm จึงจัดข้อมูลแยกตาม tenant และทำให้เจ้าของหอควบคุม workflow ได้จากระบบเดียว</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach(['ข้อมูลแต่ละหอพักแยกจากกัน', 'กำหนดสิทธิ์เจ้าของและพนักงานได้', 'เก็บประวัติบิล การชำระเงิน และสัญญา', 'ใช้งานผ่านเว็บ ไม่ต้องติดตั้งโปรแกรม', 'รองรับ LINE OA สำหรับแจ้งเตือนผู้เช่า', 'มี flow เริ่มต้นสำหรับตั้งค่าหอแรก'] as $item)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm font-semibold leading-relaxed text-slate-700 shadow-sm">
                        <svg class="mb-3 h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        {{ $item }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FAQ
    ══════════════════════════════════════════════════ --}}
    <section id="faq" class="bg-slate-50 px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-4xl">
            <div class="text-center">
                <p class="text-sm font-semibold uppercase tracking-widest text-amber-600">FAQ</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">คำถามก่อนเริ่มใช้งาน</h2>
            </div>
            <div class="mt-12 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white shadow-sm">
                @foreach([
                    ['q' => 'ทดลองใช้ฟรีกี่วัน?', 'a' => 'เริ่มใช้งานฟรีตามเงื่อนไขแพ็กเกจทดลอง โดยไม่ต้องผูกบัตรในขั้นตอนสมัคร'],
                    ['q' => 'ต้องติดตั้งโปรแกรมไหม?', 'a' => 'ไม่ต้องติดตั้ง ใช้งานผ่านเว็บเบราว์เซอร์ได้เลยทั้งบนคอมพิวเตอร์และอุปกรณ์พกพา'],
                    ['q' => 'ย้ายข้อมูลจาก Excel ได้ไหม?', 'a' => 'สามารถเตรียมข้อมูลห้อง ผู้เช่า และค่าเช่าปัจจุบันเป็นจุดตั้งต้นสำหรับการนำเข้าหรือตั้งค่าในระบบได้'],
                    ['q' => 'ใช้ LINE ส่วนตัวได้ไหม?', 'a' => 'แนะนำให้ใช้ LINE Official Account เพื่อส่งบิล แจ้งเตือน และ broadcast ถึงผู้เช่าอย่างเป็นระบบ'],
                    ['q' => 'เหมาะกับหอพักกี่ห้อง?', 'a' => 'เริ่มใช้ได้ตั้งแต่หอพักขนาดเล็ก และเลือกแพ็กเกจตามจำนวนห้องหรือโควตาที่ต้องการจัดการ'],
                    ['q' => 'ข้อมูลผู้เช่าปลอดภัยไหม?', 'a' => 'ระบบแยกข้อมูลตาม tenant และจำกัดการเข้าถึงตามบัญชีผู้ใช้งาน เพื่อลดการปะปนของข้อมูลแต่ละหอ'],
                    ['q' => 'มีค่า setup เพิ่มไหม?', 'a' => 'การเริ่มต้นด้วยตนเองไม่มีขั้นตอนซับซ้อน หากมีบริการ setup หรือ support เพิ่มเติมควรอ้างอิงเงื่อนไขแพ็กเกจที่เลือก'],
                    ['q' => 'ถ้าเลิกใช้ จะเอาข้อมูลออกได้ไหม?', 'a' => 'ควรวางแผน export ข้อมูลสำคัญ เช่น ห้อง ผู้เช่า บิล และประวัติการชำระเงินก่อนปิดการใช้งาน'],
                ] as $faq)
                    <details class="group p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-bold text-slate-900">
                            {{ $faq['q'] }}
                            <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════════════
        FINAL CTA
    ══════════════════════════════════════════════════ --}}
    <section class="px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-3xl text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">เริ่มจัดการหอพักให้เป็นระบบตั้งแต่เดือนนี้</h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-slate-600">ลดเวลาการออกบิล ติดตามยอดค้าง และเก็บข้อมูลผู้เช่าไว้ในที่เดียว พร้อมเริ่มทดลองใช้ฟรี</p>

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
                                    {{ $plan->name }} ({{ number_format((float) $plan->price_monthly, 0) }} บาท/ห้อง/เดือน)
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
                                    'terms_of_service' => '<a target="_blank" rel="noopener noreferrer" href="'.route('terms.show').'" class="font-semibold text-amber-600 underline hover:text-amber-700">'.__('Terms of Service').'</a>',
                                    'privacy_policy' => '<a target="_blank" rel="noopener noreferrer" href="'.route('policy.show').'" class="font-semibold text-amber-600 underline hover:text-amber-700">'.__('Privacy Policy').'</a>',
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
