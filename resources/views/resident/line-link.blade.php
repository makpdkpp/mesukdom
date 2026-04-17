<x-public-layout>
    <section class="px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <div class="glass-panel rounded-[2rem] px-6 py-8 sm:px-8">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">LINE Resident Portal</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">ยืนยันการเชื่อมต่อห้องพัก</h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">กรอกรหัส 6 หลักที่ได้รับจากเจ้าของหอเพื่อเชื่อม LINE กับข้อมูลผู้เช่า โดยไม่ต้องสมัครหรือล็อกอินเพิ่มเติม</p>

                @if ($linkedCustomer)
                    <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-5">
                        <div class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Linked</div>
                        <div class="mt-2 text-2xl font-bold text-slate-950">เชื่อม LINE สำเร็จแล้ว</div>
                        <p class="mt-2 text-sm text-slate-700">บัญชีนี้เชื่อมกับ {{ $linkedCustomer->name }} ห้อง {{ $linkedCustomer->room?->room_number ?? '-' }} เรียบร้อยแล้ว</p>
                    </div>
                @else
                    @if ($errors->any())
                        <div class="mt-6 rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="link_token" class="text-sm font-semibold text-slate-700">Link Code</label>
                            <input
                                id="link_token"
                                name="link_token"
                                type="text"
                                maxlength="6"
                                autocomplete="off"
                                value="{{ old('link_token', $prefilledToken) }}"
                                class="mt-2 w-full rounded-2xl border border-slate-300 bg-white px-4 py-4 text-center text-3xl font-bold uppercase tracking-[0.48em] text-slate-950 outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-100"
                                placeholder="ABC123"
                                required
                            >
                            <p class="mt-2 text-xs leading-6 text-slate-500">ถ้ายังไม่มีรหัส ให้ติดต่อเจ้าของหอเพื่อสร้างรหัสเชื่อมต่อใหม่ในระบบ</p>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">ยืนยันห้องพัก</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
