# Landing Page (Public Site) Plan

## Current Status
- Status: **Completed for the planned MVP scope**
- Verified in code/runtime:
  - Pricing page ใช้งานจริงจาก DB (`plans`)
  - Signup flow สร้าง `tenant` + `owner user` อัตโนมัติ
  - Login / Forgot Password / Email Verification ใช้งานจริงผ่าน Laravel Fortify
  - `/app/*` ถูกป้องกันด้วย `auth` + `verified` + role guard แล้ว
  - Landing → Pricing → Register/Login → Dashboard flow ใช้งานได้จริง
- Remaining items are outside this page plan and belong to later phases:
  - SaaS billing/payment gateway จริง
  - tenant resolve จาก domain/subdomain
  - usage limits / subscription enforcement เชิงธุรกิจ

แผนนี้เพิ่มโมดูล Public Site ให้ครบตาม `dom.md` โดยใช้ Laravel Jetstream (Livewire) เพื่อให้ได้ Register/Login/Forgot/Email Verify อย่างเป็นมาตรฐาน และต่อยอด flow สมัคร SaaS ที่สร้าง `tenant` + `owner user` อัตโนมัติ รวมถึงหน้า Pricing แบบ DB-driven (เตรียมต่อ payment/subscription ภายหลัง)

## Scope (ตาม checklist หมวด 1)
- Pricing Plan page
- SaaS signup (Owner สมัคร)
- สมัครแล้วสร้าง Tenant + Admin/Owner user อัตโนมัติ
- Login
- Forgot Password
- Email Verification

## Assumptions / Decisions
- Jetstream stack: **Livewire** (เข้ากับ Blade/AdminLTE ที่มีอยู่)
- Signup flow: **Single owner per tenant** + (invite staff ทำต่อภายหลัง)
- Pricing: **DB-driven plans** + “Stripe-ready later” (ยังไม่ทำตัดเงินจริงในเฟสนี้)
- ตอนนี้ระบบมี `/app/*` dashboard routes แบบไม่บังคับ auth → จะปรับให้ protected ภายหลังในเฟสนี้

## Milestones
### 1) Integrate Auth Scaffolding (Jetstream)
- [x] ติดตั้ง Jetstream + Livewire ให้เข้ากับ Laravel version ที่ใช้
- [x] เปิดใช้ features ที่ต้องใช้:
  - registration
  - login
  - password reset
  - email verification
- [x] กำหนด redirect หลัง login ให้ไปหน้า tenant dashboard (`/app/dashboard`) และ admin ไป `/admin`
- [x] ตรวจให้แน่ใจว่าหน้า auth ไม่ชนกับ layout เดิม และใช้งานได้

**Deliverables**
- Auth routes/views/components พร้อมใช้งาน
- ผู้ใช้สมัคร/ล็อกอิน/ขอ reset password/verify email ได้จริง

### 2) SaaS Signup Flow (Create Tenant + Owner)
- [x] ออกแบบ form สมัคร (อย่างน้อย: dorm/tenant name, owner name, email, password, plan)
- [x] สร้าง flow หลัง registration:
  - create `tenants` record (default `plan=trial`, `status=active`, set `trial_ends_at`)
  - ผูก `users.tenant_id` กับ tenant ที่สร้าง
  - ตั้ง `users.role = owner`
  - set tenant context/session สำหรับ user ที่เพิ่งสมัคร (หรือหลัง login)
- [x] ปรับ `SetCurrentTenant` ให้รองรับการเลือก tenant จาก user ที่ login (แทนการสุ่ม/ตัวแรก) โดยยังคง fallback แบบเดิมสำหรับ demo/กรณีไม่มี auth

**Deliverables**
- สมัครแล้วได้ tenant ใหม่ + owner user พร้อมเข้า `/app/dashboard` ใน tenant ของตัวเอง

### 3) Pricing (DB-driven)
- [x] เพิ่มตาราง `plans` (หรือ `subscription_plans`) + seed เริ่มต้น 2-3 แผน
- [x] สร้างหน้า `/pricing` แสดง plan cards (ชื่อ/ราคา/limits แบบ placeholder)
- [x] ในหน้า signup ให้เลือก plan และบันทึกลง `tenants.plan_id` พร้อมเก็บ `tenants.plan` เป็น slug เพื่อ compatibility

**Deliverables**
- Pricing page ใช้ข้อมูลจาก DB + signup เลือก plan ได้

### 4) Public Landing UX + Navigation
- [x] อัปเดตหน้า `/` ให้มี CTA:
  - “ดูราคา” → `/pricing`
  - “สมัครใช้งาน” → `/register`
  - “เข้าสู่ระบบ” → `/login`
- [x] ปรับข้อความ/ลิงก์ให้ไม่ชี้ไป dashboard โดยตรงถ้ายังไม่ login

**Deliverables**
- Landing page flow ครบ: landing → pricing → register/login → dashboard

### 5) Hardening / Tests (ขั้นต่ำ)
- [x] เพิ่ม feature tests ขั้นต่ำสำหรับ:
  - register creates tenant + owner
  - verified email requirement (บังคับแล้วผ่าน middleware `verified`)
  - unauthenticated cannot access `/app/*`
  - tenant dashboard resolves current tenant from authenticated user

**Deliverables**
- test suite เพิ่มขึ้นและผ่าน

## Open Questions (ต้อง confirm ก่อนลงมือ implement)
- จะ **บังคับ** email verification ก่อนเข้า `/app/*` ไหม?
  - ตอบแล้ว: **บังคับ** และใส่ middleware `verified` ให้ routes `/app/*` แล้ว
- ฟิลด์ plan จะเก็บแบบไหน?
  - ตอบแล้ว: ใช้ `tenants.plan_id` เป็นหลัก และคง `tenants.plan` slug ไว้เพื่อ compatibility

## Risk / Notes
- โปรเจกต์ใช้ `laravel/framework:^13.0` ซึ่งอาจมีความเข้ากันได้กับ Jetstream ต่างจากเวอร์ชันที่พบทั่วไป
  - ถ้าติดข้อจำกัด dependency: fallback ทางเลือกคือ Laravel Breeze (Blade) เพื่อให้ milestone auth เดินต่อได้
