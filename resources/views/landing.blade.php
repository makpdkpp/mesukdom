<x-public-layout
    title="MesukDom • ระบบบริหารหอพักแบบ SaaS"
    description="ระบบบริหารหอพักสมัยใหม่สำหรับเจ้าของหอและทีมงาน พร้อมแดชบอร์ดเรียลไทม์ การติดตามผู้เช่า บิล และ LINE automation"
    :auth-modals="true"
>
    <section class="relative overflow-hidden px-4 pb-14 pt-8 sm:px-6 lg:px-8 lg:pt-10">
        <div class="ambient-ring left-[-4rem] top-[9rem] h-48 w-48"></div>
        <div class="ambient-ring right-[12%] top-[3rem] h-24 w-24"></div>

        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[1.02fr,0.98fr] lg:items-center">
            <div class="reveal-soft" style="--enter-delay: 0.05s;">
                <div class="inline-flex items-center gap-3 rounded-full border border-white/70 bg-white/65 px-4 py-2 text-xs font-bold uppercase tracking-[0.24em] text-slate-700 shadow-lg shadow-slate-200/40 backdrop-blur">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    Premium dorm operations suite
                </div>

                <h1 class="mt-6 max-w-5xl text-5xl font-bold leading-[0.98] tracking-tight text-slate-950 sm:text-6xl lg:text-7xl">
                    Landing page สำหรับหอพักที่ต้องการ
                    <span class="block text-slate-500">ภาพลักษณ์น่าเชื่อถือ และ operations ที่ดูพรีเมียมตั้งแต่แรกเห็น</span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                    MesukDom วาง positioning ให้เจ้าของหอและทีมงานดูเป็นมืออาชีพตั้งแต่ public site ไปจนถึง dashboard ด้วยโครงสร้างที่ชัดเจน ข้อมูลที่ดูเชื่อถือได้ และ flow ทดลองใช้งานที่ไม่สะดุด
                </p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center">
                    @guest
                        <button type="button" data-open-auth-modal="signup" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-base font-bold text-white shadow-2xl shadow-slate-900/20 transition hover:bg-slate-800">
                            Try MesukDom free
                        </button>
                        <a href="#pricing" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/80 px-6 py-3 text-base font-bold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">
                            View pricing
                        </a>
                    @else
                        <a href="{{ route('app.dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3 text-base font-bold text-white shadow-2xl shadow-slate-900/20 transition hover:bg-slate-800">
                            ไปที่ Dashboard
                        </a>
                    @endguest
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.14s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Properties live</p>
                        <p class="mt-3 text-3xl font-extrabold text-slate-950">{{ number_format($publicStats['tenants_total']) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">จำนวนโครงการที่อยู่ในระบบและพร้อมถูกบริหารจาก flow เดียว</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.22s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Approved this month</p>
                        <p class="mt-3 text-3xl font-extrabold text-slate-950">฿{{ number_format((float) $publicStats['monthly_revenue'], 0) }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">ยอดชำระที่อนุมัติแล้วในเดือนปัจจุบันจากข้อมูล payment จริง</p>
                    </div>
                    <div class="glass-panel reveal-up rounded-[1.75rem] p-5" style="--enter-delay: 0.3s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Occupancy rate</p>
                        <p class="mt-3 text-3xl font-extrabold text-slate-950">{{ $publicStats['occupancy_rate'] }}%</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">คิดจากจำนวนห้องที่ occupied เทียบกับ inventory ทั้งหมดในระบบ</p>
                    </div>
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-3 text-sm font-semibold text-slate-500">
                    <span class="rounded-full border border-white/70 bg-white/55 px-4 py-2 backdrop-blur">Multi-tenant architecture</span>
                    <span class="rounded-full border border-white/70 bg-white/55 px-4 py-2 backdrop-blur">Premium resident touchpoints</span>
                    <span class="rounded-full border border-white/70 bg-white/55 px-4 py-2 backdrop-blur">LINE OA automation</span>
                </div>
            </div>

            <div class="premium-panel reveal-up float-glow relative overflow-hidden rounded-[2.5rem] p-6 text-white sm:p-8" style="--enter-delay: 0.12s;">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,11,0.18),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(20,184,166,0.14),transparent_32%)]"></div>
                <div class="relative space-y-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/55">Featured preview</p>
                            <p class="mt-2 text-2xl font-bold">The owner dashboard, framed like a premium control room</p>
                        </div>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-emerald-200 ring-1 ring-white/15">Live status</span>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.08fr,0.92fr]">
                        <div class="rounded-[1.75rem] bg-white/8 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                            <div class="flex items-center justify-between text-sm text-white/65">
                                <span>Portfolio overview</span>
                                <span>Live system snapshot</span>
                            </div>
                            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                                    <p class="text-sm text-white/55">Properties</p>
                                    <p class="mt-1 text-3xl font-extrabold">{{ number_format($publicStats['tenants_total']) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                                    <p class="text-sm text-white/55">Rooms online</p>
                                    <p class="mt-1 text-3xl font-extrabold">{{ number_format($publicStats['rooms_total']) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                                    <p class="text-sm text-white/55">Pending payments</p>
                                    <p class="mt-1 text-3xl font-extrabold">{{ number_format($publicStats['pending_payments']) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10">
                                    <p class="text-sm text-white/55">Overdue invoices</p>
                                    <p class="mt-1 text-3xl font-extrabold">{{ number_format($publicStats['overdue_invoices']) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-4 ring-1 ring-white/10 sm:col-span-2">
                                    <div class="flex items-end justify-between gap-4">
                                        <div>
                                            <p class="text-sm text-white/55">Approved revenue this month</p>
                                            <p class="mt-1 text-3xl font-extrabold">฿{{ number_format((float) $publicStats['monthly_revenue'], 0) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-right ring-1 ring-white/10">
                                            <p class="text-xs uppercase tracking-[0.22em] text-white/50">Open repairs</p>
                                            <p class="mt-1 text-lg font-bold text-amber-200">{{ number_format($publicStats['open_repairs']) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[1.75rem] bg-white/8 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/50">Property cards</p>
                                <div class="mt-4 space-y-3">
                                    @forelse($propertyHighlights as $property)
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                                            <div class="flex items-center justify-between gap-4 text-sm font-semibold">
                                                <span>{{ $property['name'] }}</span>
                                                <span class="{{ $property['occupancy_rate'] >= 80 ? 'text-emerald-200' : 'text-amber-200' }}">{{ $property['occupancy_rate'] }}% occupied</span>
                                            </div>
                                            <p class="mt-2 text-sm text-white/60">{{ number_format($property['rooms_total']) }} rooms, {{ number_format($property['pending_payments']) }} pending payments, {{ number_format($property['open_repairs']) }} open repairs</p>
                                        </div>
                                    @empty
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/70 ring-1 ring-white/10">
                                            ยังไม่มี property data ในระบบสำหรับแสดง featured cards
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-[1.75rem] bg-white/8 p-5 ring-1 ring-white/10 backdrop-blur-sm">
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/50">Featured property</p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                                    @if($featuredProperty)
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                                            <p class="text-sm text-white/60">{{ $featuredProperty['name'] }}</p>
                                            <p class="mt-1 text-2xl font-extrabold">฿{{ number_format((float) $featuredProperty['monthly_revenue'], 0) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                                            <p class="text-sm text-white/60">Occupied rooms</p>
                                            <p class="mt-1 text-2xl font-extrabold">{{ number_format($featuredProperty['rooms_occupied']) }}/{{ number_format($featuredProperty['rooms_total']) }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                                            <p class="text-sm text-white/60">Overdue invoices</p>
                                            <p class="mt-1 text-2xl font-extrabold">{{ number_format($featuredProperty['overdue_invoices']) }}</p>
                                        </div>
                                    @else
                                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/70 ring-1 ring-white/10 sm:col-span-3 lg:col-span-1">
                                            เมื่อมี tenant และธุรกรรมในระบบ การ์ดนี้จะสรุป property ที่เด่นที่สุดให้อัตโนมัติ
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="trust" class="px-4 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2.25rem] border border-white/60 bg-white/58 px-6 py-6 shadow-xl shadow-slate-200/30 backdrop-blur sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Trust layer</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Premium feel ต้องมาพร้อมความไว้ใจที่มองเห็นได้ทันที</h2>
                </div>
                <div class="flex flex-wrap gap-3 text-xs font-bold uppercase tracking-[0.22em] text-slate-500">
                    <span class="rounded-full bg-slate-950 px-4 py-3 text-white">Owner + staff access</span>
                    <span class="rounded-full bg-white px-4 py-3 text-slate-700 ring-1 ring-slate-200">Signed payment links</span>
                    <span class="rounded-full bg-white px-4 py-3 text-slate-700 ring-1 ring-slate-200">LINE webhook logs</span>
                    <span class="rounded-full bg-white px-4 py-3 text-slate-700 ring-1 ring-slate-200">Tenant-safe data isolation</span>
                </div>
            </div>
        </div>
    </section>

    <section id="preview" class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-5 lg:grid-cols-[0.88fr,1.12fr]">
                <article class="mesh-card reveal-up rounded-[2.25rem] p-7" style="--enter-delay: 0.08s;">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Property cards</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Glassmorphism cards ที่ทำให้แต่ละโครงการดูมีระดับ</h2>
                    <p class="mt-4 text-lg leading-8 text-slate-600">แทนการเล่าระบบแบบ generic หน้า landing นี้โชว์ property preview, occupancy signal และ service quality ให้ผู้เข้าชมเข้าใจคุณค่าของระบบเร็วขึ้น</p>

                    <div class="mt-8 space-y-4">
                        <div class="glass-panel reveal-soft rounded-[1.75rem] p-5" style="--enter-delay: 0.12s;">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Featured property</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-950">{{ $featuredProperty['name'] ?? 'Portfolio overview' }}</h3>
                                </div>
                                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-700">Data-driven</span>
                            </div>
                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl bg-white/80 px-4 py-4 ring-1 ring-slate-200/70">
                                    <p class="text-sm text-slate-500">Occupancy</p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-950">{{ $featuredProperty['occupancy_rate'] ?? $publicStats['occupancy_rate'] }}%</p>
                                </div>
                                <div class="rounded-2xl bg-white/80 px-4 py-4 ring-1 ring-slate-200/70">
                                    <p class="text-sm text-slate-500">Collection</p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-950">฿{{ number_format((float) ($featuredProperty['monthly_revenue'] ?? $publicStats['monthly_revenue']), 0) }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/80 px-4 py-4 ring-1 ring-slate-200/70">
                                    <p class="text-sm text-slate-500">Open repairs</p>
                                    <p class="mt-1 text-2xl font-extrabold text-slate-950">{{ number_format($featuredProperty['open_repairs'] ?? $publicStats['open_repairs']) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-panel reveal-soft rounded-[1.75rem] p-5" style="--enter-delay: 0.18s;">
                            <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Resident experience</p>
                            <p class="mt-3 text-lg font-bold text-slate-950">โปรไฟล์แต่ละหอสามารถสื่อสารมาตรฐานบริการได้ชัดขึ้น</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">ทั้งหน้า public และ dashboard พูดภาษาเดียวกัน: เรียบ หรู มั่นใจ และอ้างอิงตัวเลขจริงจาก tenant, rooms, invoices และ payments</p>
                        </div>
                    </div>
                </article>

                <div class="grid gap-5 sm:grid-cols-2">
                    <article class="glass-panel reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.14s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">01</p>
                        <h3 class="mt-4 text-2xl font-bold text-slate-950">Executive occupancy view</h3>
                        <p class="mt-3 text-base leading-7 text-slate-600">มองเห็นห้องว่าง ห้องพร้อมปล่อย และพื้นที่ที่ควรเร่งทำรายได้แบบไม่ต้องเปิดหลายหน้าจอ</p>
                    </article>

                    <article class="mesh-card reveal-up rounded-[2rem] p-6" style="--enter-delay: 0.2s;">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-amber-700">02</p>
                        <h3 class="mt-4 text-2xl font-bold text-slate-950">Billing, but still elegant</h3>
                        <p class="mt-3 text-base leading-7 text-slate-600">สถานะบิล, สลิป, ยอดค้าง และ resident payment links ถูกจัดวางแบบอ่านง่ายและดู premium</p>
                    </article>

                    <article class="mesh-card reveal-up rounded-[2rem] p-6 sm:col-span-2" style="--enter-delay: 0.26s;">
                        <div class="grid gap-5 lg:grid-cols-[1fr,0.9fr] lg:items-center">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-teal-700">03</p>
                                <h3 class="mt-4 text-3xl font-bold text-slate-950">Communication flow ที่น่าเชื่อถือ ไม่ใช่แค่ส่งข้อความได้</h3>
                                <p class="mt-3 text-base leading-7 text-slate-600">เชื่อม LINE OA, broadcast ตาม segment และเก็บ logs เพื่อให้ทีมงานตอบคำถามผู้เช่าได้ด้วยข้อมูลจริงทุกครั้ง</p>
                            </div>
                            <div class="rounded-[1.75rem] bg-white/80 p-5 ring-1 ring-slate-200/70">
                                <div class="space-y-3 text-sm font-semibold text-slate-700">
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                        <span>Rooms occupied</span>
                                        <span class="text-slate-950">{{ number_format($publicStats['rooms_occupied']) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                        <span>Pending payments</span>
                                        <span class="text-slate-950">{{ number_format($publicStats['pending_payments']) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                                        <span>Overdue invoices</span>
                                        <span class="text-slate-950">{{ number_format($publicStats['overdue_invoices']) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-700">Why it converts</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">โครงสร้างหน้าออกแบบให้ผู้ชมรู้สึกทั้งเชื่อถือและอยากทดลองต่อทันที</h2>
                    <p class="mt-4 text-lg leading-8 text-slate-600">แทนการขายด้วยคำกว้าง ๆ หน้าใหม่นี้ใช้ proof points, property previews และ trial CTA ที่พาผู้ใช้ไปยัง signup flow เดิมโดยตรง</p>
                </div>
                @guest
                    <button type="button" data-open-auth-modal="signup" class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/80 px-5 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-950 hover:text-slate-950">
                        ทดลองใช้งานจากหน้านี้
                    </button>
                @endguest
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                <article class="glass-panel rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">One clear story</p>
                    <p class="mt-4 text-4xl font-extrabold text-slate-950">1 flow</p>
                    <p class="mt-3 text-base leading-7 text-slate-600">Public site, pricing, trial signup และ owner onboarding เชื่อมกันในทางเดียว ไม่หลุดบริบท</p>
                </article>

                <article class="glass-panel rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Trust by design</p>
                    <p class="mt-4 text-4xl font-extrabold text-slate-950">Tenant-safe</p>
                    <p class="mt-3 text-base leading-7 text-slate-600">ย้ำเรื่อง data separation, audit trail และ signed links ตั้งแต่ก่อนผู้ใช้กดสมัคร</p>
                </article>

                <article class="glass-panel rounded-[2rem] p-6">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">Premium tone</p>
                    <p class="mt-4 text-4xl font-extrabold text-slate-950">Calm UI</p>
                    <p class="mt-3 text-base leading-7 text-slate-600">พื้นผิวแก้ว สีอบอุ่น และจังหวะเนื้อหาที่นิ่งกว่า ช่วยให้แบรนด์ดูแพงขึ้นโดยไม่เกินจริง</p>
                </article>
            </div>
        </div>
    </section>

    <section id="pricing" class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-teal-700">Pricing</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Pricing ที่อ่านง่าย และพร้อมต่อเข้ากับ trial flow ทันที</h2>
                    <p class="mt-4 text-lg leading-8 text-slate-600">การ์ดราคายังดึงจากข้อมูลจริงในระบบเหมือนเดิม แต่ถูกจัดให้รู้สึก premium มากขึ้น และเชื่อมการเลือก plan ไปยัง signup modal ได้ตรง ๆ</p>
                </div>
                <div class="rounded-[1.5rem] border border-white/60 bg-white/60 px-5 py-4 text-sm font-semibold text-slate-600 shadow-lg shadow-slate-200/30 backdrop-blur">
                    ทุกแพ็กเกจพร้อม billing, room management และ resident communication workflow
                </div>
            </div>

            <div class="mt-10 grid gap-5 lg:grid-cols-3">
                @forelse($plans->take(3) as $plan)
                    <article class="{{ $loop->iteration === 2 ? 'premium-panel text-white' : 'glass-panel' }} rounded-[2.2rem] p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-[0.24em] {{ $loop->iteration === 2 ? 'text-white/60' : 'text-amber-700' }}">{{ $plan->slug ?: 'Plan' }}</p>
                                <h3 class="mt-4 text-2xl font-bold {{ $loop->iteration === 2 ? 'text-white' : 'text-slate-950' }}">{{ $plan->name }}</h3>
                            </div>
                            @if($loop->iteration === 2)
                                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-amber-200 ring-1 ring-white/10">Featured</span>
                            @endif
                        </div>

                        <p class="mt-5 text-4xl font-extrabold tracking-tight {{ $loop->iteration === 2 ? 'text-white' : 'text-slate-950' }}">
                            {{ number_format((float) $plan->price_monthly, 0) }}
                            <span class="ml-1 text-lg font-semibold {{ $loop->iteration === 2 ? 'text-white/60' : 'text-slate-500' }}">บาท/เดือน</span>
                        </p>

                        @if($plan->description)
                            <p class="mt-4 text-base leading-7 {{ $loop->iteration === 2 ? 'text-white/70' : 'text-slate-600' }}">{{ $plan->description }}</p>
                        @endif

                        @php($limits = (array) ($plan->limits ?? []))
                        <div class="mt-6 space-y-3">
                            @forelse($limits as $key => $value)
                                <div class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-semibold {{ $loop->parent->iteration === 2 ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">
                                    <span>{{ ucfirst((string) $key) }}</span>
                                    <span class="{{ $loop->parent->iteration === 2 ? 'text-white' : 'text-slate-950' }}">{{ $value }}</span>
                                </div>
                            @empty
                                <div class="rounded-2xl px-4 py-3 text-sm font-semibold {{ $loop->iteration === 2 ? 'bg-white/10 text-white ring-1 ring-white/10' : 'bg-white/80 text-slate-700 ring-1 ring-slate-200/70' }}">พร้อมใช้งานกับ billing, room management และ LINE workflow</div>
                            @endforelse
                        </div>

                        <div class="mt-8 space-y-3">
                            @guest
                                <button type="button" data-open-auth-modal="signup" data-plan-id="{{ $plan->id }}" class="inline-flex w-full items-center justify-center rounded-full {{ $loop->iteration === 2 ? 'bg-white text-slate-950 hover:bg-white/90' : 'bg-slate-950 text-white hover:bg-slate-800' }} px-5 py-3 text-sm font-bold transition">
                                    Start this plan
                                </button>
                                <button type="button" data-open-auth-modal="login" class="inline-flex w-full items-center justify-center rounded-full border {{ $loop->iteration === 2 ? 'border-white/20 bg-white/5 text-white hover:bg-white/10' : 'border-slate-300 bg-white/80 text-slate-700 hover:border-slate-950 hover:text-slate-950' }} px-5 py-3 text-sm font-bold transition">
                                    มีบัญชีอยู่แล้ว
                                </button>
                            @else
                                <a href="{{ route('app.dashboard') }}" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">
                                    ไปที่ Dashboard
                                </a>
                            @endguest
                        </div>
                    </article>
                @empty
                    <div class="glass-panel rounded-[2rem] px-6 py-10 text-center lg:col-span-3">
                        <p class="text-lg font-bold text-slate-950">ยังไม่มีแพ็กเกจในระบบ</p>
                        <p class="mt-3 text-base text-slate-600">เพิ่มข้อมูล plans แล้ว pricing cards จะใช้ข้อมูลจริงทันที</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="try" class="px-4 pb-14 pt-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[2.5rem] bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-900/20 sm:px-8 sm:py-10">
            <div class="grid gap-6 lg:grid-cols-[1fr,auto] lg:items-center">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.24em] text-amber-200">Try</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">ทดลองวางแบรนด์หอของคุณบน flow ที่ดูน่าเชื่อถือกว่าทันที</h2>
                    <p class="mt-4 text-lg leading-8 text-white/70">กดเริ่มทดลองแล้วใช้ signup modal เดิมเพื่อสร้าง owner account, tenant และ plan selection ต่อได้ทันที โดยไม่ต้องสร้าง flow ใหม่เพิ่ม</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col lg:items-end">
                    @guest
                        <button type="button" data-open-auth-modal="signup" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-base font-bold text-slate-950 transition hover:bg-white/90">
                            Start free trial
                        </button>
                        <button type="button" data-open-auth-modal="login" class="inline-flex items-center justify-center rounded-full border border-white/20 bg-white/5 px-6 py-3 text-base font-bold text-white transition hover:bg-white/10">
                            Owner login
                        </button>
                    @else
                        <a href="{{ route('app.dashboard') }}" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-base font-bold text-slate-950 transition hover:bg-white/90">
                            เปิด dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>

    @guest
        <div id="auth-modal-backdrop" class="pointer-events-none fixed inset-0 z-40 hidden bg-slate-950/35 opacity-0 transition duration-200"></div>

        <div id="login-modal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center px-4 opacity-0 transition duration-200 sm:px-6">
            <div class="absolute inset-0" data-close-auth-modal></div>
            <div class="relative max-h-[90vh] w-full max-w-md overflow-auto rounded-[2rem] border border-white/60 bg-white/82 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-xl sm:p-7">
                <button type="button" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:text-slate-950" data-close-auth-modal>
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12"/><path d="M18 6 6 18"/></svg>
                </button>

                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-500">Login</p>
                <h3 class="mt-3 text-3xl font-bold text-slate-950">เข้าสู่ระบบเพื่อเปิด dashboard ของคุณ</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">ใช้บัญชีเดิมของ owner หรือ staff เพื่อเข้าไปจัดการข้อมูลห้อง ผู้เช่า บิล และการแจ้งเตือน</p>

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="landing_login_email" class="text-sm font-semibold text-slate-700">Email</label>
                        <input id="landing_login_email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                    </div>
                    <div>
                        <label for="landing_login_password" class="text-sm font-semibold text-slate-700">Password</label>
                        <input id="landing_login_password" name="password" type="password" required autocomplete="current-password" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                    </div>
                    <label for="landing_remember" class="flex items-center gap-3 text-sm text-slate-600">
                        <input id="landing_remember" name="remember" type="checkbox" class="rounded border-slate-300 text-slate-950 focus:ring-slate-400" />
                        Remember me
                    </label>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-slate-500 underline decoration-slate-300 decoration-2 underline-offset-4">Forgot your password?</a>
                        @endif
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">Log in</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="signup-modal" class="pointer-events-none fixed inset-0 z-50 hidden items-center justify-center px-4 opacity-0 transition duration-200 sm:px-6">
            <div class="absolute inset-0" data-close-auth-modal></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-auto rounded-[2rem] border border-white/60 bg-white/84 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur-xl sm:p-8">
                <button type="button" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:text-slate-950" data-close-auth-modal>
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12"/><path d="M18 6 6 18"/></svg>
                </button>

                <div class="grid gap-8 lg:grid-cols-[0.82fr,1.18fr] lg:items-start">
                    <div class="rounded-[1.75rem] bg-slate-950 p-6 text-white shadow-xl shadow-slate-950/25">
                        <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/60">Sign up</p>
                        <h3 class="mt-4 text-3xl font-bold">เปิด tenant ใหม่ใน flow เดียว</h3>
                        <p class="mt-4 text-sm leading-7 text-white/75">ฟอร์มนี้ยังส่งไปยัง registration route เดิมทั้งหมด เพื่อสร้าง owner account, tenant และ plan selection ตาม flow เดิมของระบบ</p>
                        <div class="mt-6 space-y-3 text-sm font-semibold">
                            <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">Owner account + tenant</div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">Plan selection from pricing cards</div>
                            <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">Ready for dashboard onboarding</div>
                        </div>
                    </div>

                    <div>
                        <form method="POST" action="{{ route('register') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="landing_register_name" class="text-sm font-semibold text-slate-700">Name</label>
                                <input id="landing_register_name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                            </div>
                            <div>
                                <label for="landing_register_tenant_name" class="text-sm font-semibold text-slate-700">Dormitory / Tenant Name</label>
                                <input id="landing_register_tenant_name" name="tenant_name" type="text" value="{{ old('tenant_name') }}" required autocomplete="organization" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                            </div>
                            <div>
                                <label for="landing_register_plan_id" class="text-sm font-semibold text-slate-700">Plan</label>
                                <select id="landing_register_plan_id" name="plan_id" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" required>
                                    @foreach(($plans ?? []) as $plan)
                                        <option value="{{ $plan->id }}" @selected((string) old('plan_id', request('plan')) === (string) $plan->id)>
                                            {{ $plan->name }} ({{ number_format((float) $plan->price_monthly, 0) }}/mo)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="landing_register_email" class="text-sm font-semibold text-slate-700">Email</label>
                                <input id="landing_register_email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="landing_register_password" class="text-sm font-semibold text-slate-700">Password</label>
                                    <input id="landing_register_password" name="password" type="password" required autocomplete="new-password" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                                </div>
                                <div>
                                    <label for="landing_register_password_confirmation" class="text-sm font-semibold text-slate-700">Confirm Password</label>
                                    <input id="landing_register_password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-slate-400 focus:ring-0" />
                                </div>
                            </div>

                            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                                <label for="landing_register_terms" class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-4 text-sm leading-6 text-slate-600">
                                    <input name="terms" id="landing_register_terms" type="checkbox" required class="mt-1 rounded border-slate-300 text-slate-950 focus:ring-slate-400" />
                                    <span>
                                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="font-semibold text-slate-900 underline decoration-amber-400 decoration-2 underline-offset-4">'.__('Terms of Service').'</a>',
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="font-semibold text-slate-900 underline decoration-amber-400 decoration-2 underline-offset-4">'.__('Privacy Policy').'</a>',
                                        ]) !!}
                                    </span>
                                </label>
                            @endif

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <button type="button" data-open-auth-modal="login" class="text-left text-sm font-semibold text-slate-500 underline decoration-slate-300 decoration-2 underline-offset-4">Already registered?</button>
                                <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-800">Create account</button>
                            </div>
                        </form>
                    </div>
                </div>
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
