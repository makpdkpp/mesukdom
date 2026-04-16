# Landing Page (Public Site) Plan

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
- ติดตั้ง Jetstream + Livewire ให้เข้ากับ Laravel version ที่ใช้
- เปิดใช้ features ที่ต้องใช้:
  - registration
  - login
  - password reset
  - email verification
- กำหนด redirect หลัง login ให้ไปหน้า tenant dashboard (`/app/dashboard`)
- ตรวจให้แน่ใจว่าหน้า auth ไม่ชนกับ layout เดิม และใช้งานได้ (UI จะ optimize ทีหลัง)

**Deliverables**
- Auth routes/views/components พร้อมใช้งาน
- ผู้ใช้สมัคร/ล็อกอิน/ขอ reset password/verify email ได้จริง

### 2) SaaS Signup Flow (Create Tenant + Owner)
- ออกแบบ form สมัคร (อย่างน้อย: dorm/tenant name, owner name, email, password, plan)
- สร้าง flow หลัง registration:
  - create `tenants` record (default `plan=trial`, `status=active`, set `trial_ends_at`)
  - ผูก `users.tenant_id` กับ tenant ที่สร้าง
  - ตั้ง `users.role = owner`
  - set tenant context/session สำหรับ user ที่เพิ่งสมัคร (หรือหลัง login)
- ปรับ `SetCurrentTenant` ให้รองรับการเลือก tenant จาก user ที่ login (แทนการสุ่ม/ตัวแรก) โดยยังคง fallback แบบเดิมสำหรับ demo/กรณีไม่มี auth

**Deliverables**
- สมัครแล้วได้ tenant ใหม่ + owner user พร้อมเข้า `/app/dashboard` ใน tenant ของตัวเอง

### 3) Pricing (DB-driven)
- เพิ่มตาราง `plans` (หรือ `subscription_plans`) + seed เริ่มต้น 2-3 แผน
- สร้างหน้า `/pricing` แสดง plan cards (ชื่อ/ราคา/limits แบบ placeholder)
- ในหน้า signup ให้เลือก plan และบันทึกลง `tenants.plan` หรือ FK ไปที่ `plans.id` (เลือกแบบใดแบบหนึ่งให้ชัดเจน)

**Deliverables**
- Pricing page ใช้ข้อมูลจาก DB + signup เลือก plan ได้

### 4) Public Landing UX + Navigation
- อัปเดตหน้า `/` ให้มี CTA:
  - “ดูราคา” → `/pricing`
  - “สมัครใช้งาน” → `/register`
  - “เข้าสู่ระบบ” → `/login`
- ปรับข้อความ/ลิงก์ให้ไม่ชี้ไป dashboard โดยตรงถ้ายังไม่ login

**Deliverables**
- Landing page flow ครบ: landing → pricing → register/login → dashboard

### 5) Hardening / Tests (ขั้นต่ำ)
- เพิ่ม feature tests ขั้นต่ำสำหรับ:
  - register creates tenant + owner
  - verified email requirement (ถ้าบังคับ)
  - unauthenticated cannot access `/app/*`

**Deliverables**
- test suite เพิ่มขึ้นและผ่าน

## Open Questions (ต้อง confirm ก่อนลงมือ implement)
- จะ **บังคับ** email verification ก่อนเข้า `/app/*` ไหม?
  - ถ้าบังคับ: ต้องใส่ middleware `verified` ให้ routes `/app/*`
- ฟิลด์ plan จะเก็บแบบไหน?
  - simple: `tenants.plan` เป็น string
  - scalable: `tenants.plan_id` FK ไป `plans`

## Risk / Notes
- โปรเจกต์ใช้ `laravel/framework:^13.0` ซึ่งอาจมีความเข้ากันได้กับ Jetstream ต่างจากเวอร์ชันที่พบทั่วไป
  - ถ้าติดข้อจำกัด dependency: fallback ทางเลือกคือ Laravel Breeze (Blade) เพื่อให้ milestone auth เดินต่อได้
