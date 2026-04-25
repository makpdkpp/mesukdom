<x-public-layout title="แพ็กเกจและราคา • MesukDorm" description="เลือกแพ็กเกจ MesukDorm ตามจำนวนห้องและ workflow ที่ต้องใช้ พร้อมระบบออกบิล LINE แจ้งเตือน และจัดการผู้เช่าในที่เดียว">
    <section class="px-4 pb-12 pt-12 sm:px-6 lg:px-8 lg:pb-16 lg:pt-16">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr,0.82fr] lg:items-center">
            <div class="reveal-soft" style="--enter-delay: 0.04s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">แพ็กเกจและราคา</p>
                <h1 class="mt-3 max-w-4xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">เลือกแพ็กเกจให้พอดีกับจำนวนห้องและวิธีทำงานของหอพักคุณ</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-600">ทุกแพ็กเกจออกแบบให้เจ้าของหอเริ่มจากงานจำเป็นก่อน: จัดการห้อง ผู้เช่า สัญญา ออกบิล ติดตามยอดชำระ และส่งแจ้งเตือนผ่าน LINE เมื่อพร้อมใช้งานจริง</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.12s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Properties</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($publicStats['tenants_total']) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">โครงการที่มีข้อมูลในระบบ</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.2s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Rooms</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($publicStats['rooms_total']) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">จำนวนห้องที่จัดการอยู่</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.28s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Occupancy</p>
                        <p class="mt-2 text-3xl font-extrabold text-slate-950">{{ $publicStats['occupancy_rate'] }}%</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">อัตราเข้าพักจากข้อมูลห้อง</p>
                    </div>
                </div>
            </div>

            <aside class="premium-panel reveal-up rounded-[2.4rem] p-6 text-white sm:p-8" style="--enter-delay: 0.14s;">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/50">Quick guide</p>
                <h2 class="mt-3 text-3xl font-bold">เลือกจากขนาดหอและงานที่อยากลดก่อน</h2>
                <div class="mt-6 space-y-4 text-sm leading-7 text-white/75">
                    <p><strong class="text-white">หอเล็ก:</strong> เริ่มจากจัดห้อง ผู้เช่า และออกบิลให้เป็นระบบ</p>
                    <p><strong class="text-white">หอกำลังโต:</strong> เพิ่ม workflow ส่ง LINE ติดตามยอดค้าง และดูรายงานรายเดือน</p>
                    <p><strong class="text-white">หลายอาคาร:</strong> เลือกแพ็กเกจที่รองรับจำนวนห้อง โควตา และทีมงานมากขึ้น</p>
                </div>
                <div class="mt-7 rounded-2xl bg-white/10 p-4 ring-1 ring-white/10">
                    <p class="text-sm font-semibold text-white">ยังไม่แน่ใจ?</p>
                    <p class="mt-2 text-sm leading-6 text-white/65">เริ่มจากแพ็กเกจทดลองหรือแพ็กเกจที่ใกล้จำนวนห้องปัจจุบัน แล้วปรับขึ้นเมื่อ workflow เริ่มชัดเจน</p>
                </div>
            </aside>
        </div>
    </section>

    <section id="plans" class="px-4 pb-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            @forelse($plans as $plan)
                @php($isFeaturedPlan = $plan->isRecommended() || $loop->iteration === 2)
                <article class="{{ $isFeaturedPlan ? 'premium-panel reveal-up text-white' : 'glass-panel reveal-up' }} flex flex-col rounded-[2.2rem] p-6" style="--enter-delay: {{ 0.08 + ($loop->iteration * 0.08) }}s;">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.24em] {{ $isFeaturedPlan ? 'text-white/55' : ($loop->iteration === 3 ? 'text-teal-700' : 'text-amber-700') }}">{{ $plan->slug ?: 'Plan' }}</p>
                            <h2 class="mt-4 text-3xl font-bold {{ $isFeaturedPlan ? 'text-white' : 'text-slate-950' }}">{{ $plan->name }}</h2>
                        </div>
                        @if($isFeaturedPlan)
                            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-amber-200 ring-1 ring-white/10">แนะนำ</span>
                        @endif
                    </div>

                    <div class="mt-4 flex items-end gap-2">
                        <span class="text-5xl font-extrabold tracking-tight {{ $isFeaturedPlan ? 'text-white' : 'text-slate-950' }}">{{ number_format((float) $plan->price_monthly, 0) }}</span>
                        <span class="pb-2 text-base font-semibold {{ $isFeaturedPlan ? 'text-white/55' : 'text-slate-500' }}">บาท / ห้อง / เดือน</span>
                    </div>

                    @if($plan->description)
                        <p class="mt-5 text-base leading-7 {{ $isFeaturedPlan ? 'text-white/70' : 'text-slate-600' }}">{{ $plan->description }}</p>
                    @endif

                    <p class="mt-5 rounded-2xl px-4 py-3 text-sm font-semibold leading-6 {{ $isFeaturedPlan ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">
                        เหมาะสำหรับ {{ $loop->first ? 'หอพักเริ่มต้นที่ต้องการจัดบิลและผู้เช่าให้เป็นระบบ' : ($isFeaturedPlan ? 'หอพักที่ต้องใช้ LINE และติดตามยอดชำระเป็นประจำ' : 'อพาร์ตเมนต์ที่มีจำนวนห้องมากขึ้นและต้องการโควตาเพิ่ม') }}
                    </p>

                    @php($limits = $plan->displayLimits())
                    <div class="mt-6 flex-1 space-y-3 text-sm font-semibold">
                        @forelse($limits as $key => $value)
                            <div class="flex items-center justify-between gap-4 rounded-2xl px-4 py-3 {{ $isFeaturedPlan ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">
                                <span>{{ $key }}</span>
                                <span class="text-right {{ $isFeaturedPlan ? 'text-white' : 'text-slate-950' }}">{{ $value }}</span>
                            </div>
                        @empty
                            <div class="rounded-2xl px-4 py-3 {{ $isFeaturedPlan ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">พร้อมใช้งานกับห้อง ผู้เช่า บิล และ LINE workflow</div>
                        @endforelse
                    </div>

                    <div class="mt-8 space-y-3">
                        @guest
                            <a href="{{ route('register', ['plan' => $plan->id]) }}" class="inline-flex w-full items-center justify-center rounded-full {{ $isFeaturedPlan ? 'bg-white text-slate-950 hover:bg-white/90' : 'bg-slate-950 text-white hover:bg-slate-800' }} px-5 py-3 text-sm font-bold transition">เลือกแพ็กเกจนี้</a>
                            <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-full border {{ $isFeaturedPlan ? 'border-white/20 bg-white/5 text-white hover:bg-white/10' : 'border-slate-300 bg-white/90 text-slate-700 hover:border-slate-950 hover:text-slate-950' }} px-5 py-3 text-sm font-bold transition">มีบัญชีอยู่แล้ว</a>
                        @else
                            <a href="{{ route('app.billing') }}" class="inline-flex w-full items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-white/90">จัดการแพ็กเกจใน Billing</a>
                        @endguest
                    </div>
                </article>
            @empty
                <div class="glass-panel rounded-[2rem] px-6 py-10 text-center lg:col-span-3">
                    <p class="text-lg font-bold text-slate-950">ยังไม่มีแพ็กเกจในระบบ</p>
                    <p class="mt-3 text-base text-slate-600">กรุณาเพิ่มข้อมูล plans ก่อนเปิดขายบน public site</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2.2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="grid gap-8 lg:grid-cols-[0.8fr,1.2fr] lg:items-start">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Package checklist</p>
                    <h2 class="mt-3 text-3xl font-bold text-slate-950">ก่อนเลือกแพ็กเกจ ให้ดู 4 เรื่องนี้</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['title' => 'จำนวนห้องที่ต้องจัดการ', 'body' => 'เลือกแพ็กเกจที่รองรับจำนวนห้องปัจจุบันและเผื่อการขยายใน 6-12 เดือน'],
                        ['title' => 'ทีมที่เข้าระบบ', 'body' => 'ถ้ามีพนักงานช่วยจดมิเตอร์หรือรับชำระ ควรดูจำนวน staff ที่รองรับ'],
                        ['title' => 'LINE workflow', 'body' => 'ถ้าต้องส่งบิลและแจ้งเตือนผู้เช่าเป็นประจำ ให้เลือกแพ็กเกจที่รองรับการใช้งาน LINE ตามต้องการ'],
                        ['title' => 'การตรวจสลิป', 'body' => 'ถ้าต้องการลด manual review ให้ดูเงื่อนไข SlipOK addon หรือโควตาตรวจสลิป'],
                    ] as $item)
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <h3 class="text-base font-bold text-slate-950">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 lg:grid-cols-3">
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.1s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Included</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">ฟีเจอร์หลักครบตั้งแต่เริ่ม</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">จัดการห้อง ผู้เช่า สัญญา บิล ค่าน้ำค่าไฟ และแจ้งซ่อมในระบบเดียว</p>
            </div>
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.18s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Billing</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">เลือกตามจำนวนห้องได้ง่าย</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">แพ็กเกจแสดง limit สำคัญจากระบบจริง เพื่อให้ตัดสินใจจาก usage ที่ต้องใช้</p>
            </div>
            <div class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.26s;">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Support</p>
                <h3 class="mt-4 text-2xl font-bold text-slate-950">เริ่มจาก trial ได้</h3>
                <p class="mt-3 text-base leading-7 text-slate-600">ทดลองตั้งค่าหอแรก ตรวจ workflow ออกบิล แล้วค่อยเลือกแพ็กเกจที่พอดีกับงานจริง</p>
            </div>
        </div>
    </section>

    <section class="px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl rounded-[2.2rem] bg-slate-950 px-8 py-12 text-center text-white shadow-2xl shadow-slate-900/20 sm:px-12">
            <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">พร้อมเริ่มจัดระบบหอพักของคุณแล้วหรือยัง?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-300">เริ่มจากแพ็กเกจที่เหมาะกับจำนวนห้องปัจจุบัน แล้วปรับเพิ่มเมื่อธุรกิจและ workflow เติบโตขึ้น</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-bold text-slate-950 transition hover:bg-slate-100">เริ่มใช้งานฟรี</a>
                    <a href="{{ route('landing') }}#faq" class="inline-flex items-center justify-center rounded-full border border-white/20 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">อ่านคำถามที่พบบ่อย</a>
                @else
                    <a href="{{ route('app.billing') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-bold text-slate-950 transition hover:bg-slate-100">ไปที่ Billing</a>
                @endguest
            </div>
        </div>
    </section>
</x-public-layout>