# สรุปงานที่ทำไปแล้ว

## สถานะรวม
- [x] สร้างโครงสร้างโปรเจกต์ Laravel เรียบร้อย
- [x] ตั้งค่าฐานข้อมูลและ seed ข้อมูลตัวอย่าง
- [x] ทำระบบ Dormitory Management SaaS แบบ MVP ตามเอกสาร dom.md
- [x] เพิ่ม flow สำหรับ LINE OA ตามเอกสาร domlineoa.md
- [x] รันและตรวจสอบระบบเรียบร้อย

## งานที่ทำเสร็จแล้ว

### 1) โครงสร้างระบบหลัก
- [x] สร้างหน้า Landing Page สำหรับแนะนำระบบ
- [x] ปรับ public site ให้มี marketing sections, CTA และ layout กลางสำหรับหน้า Landing/Pricing
- [x] ปรับ auth pages ให้ใช้ visual language เดียวกับ public site
- [x] เพิ่ม social proof / testimonial section สำหรับ public demo
- [x] ตั้งค่าเลย์เอาต์ฝั่ง Dashboard แนว AdminLTE
- [x] เพิ่ม routing หลักของระบบทั้ง public, tenant portal และ admin portal

### 2) Multi-Tenant SaaS
- [x] เพิ่มตาราง tenants และความสัมพันธ์ tenant_id ในข้อมูลหลัก
- [x] สร้าง Tenant Context และ middleware สำหรับแยกข้อมูลตาม tenant
- [x] เพิ่ม global tenant scope ให้ model ที่เกี่ยวข้อง

### 3) โมดูลฝั่งเจ้าของหอ / พนักงาน
- [x] Dashboard ภาพรวมจำนวนห้อง รายได้ และบิลค้างชำระ
- [x] Room Management สำหรับเพิ่มและดูรายการห้องพัก
- [x] Resident Management สำหรับเพิ่มและดูข้อมูลผู้เช่า
- [x] Contract Management สำหรับสร้างสัญญาเช่า
- [x] Invoice Management สำหรับออกบิลค่าเช่า ค่าน้ำ ค่าไฟ และค่าบริการ
- [x] Payment Management สำหรับบันทึกการชำระเงิน

### 4) End User Portal
- [x] สร้างหน้า Resident Invoice Portal สำหรับให้ผู้เช่าดูบิลและประวัติการจ่าย

### 5) Admin Portal
- [x] สร้างหน้า Platform Admin Dashboard
- [x] แสดงข้อมูล tenant, active users, revenue และ notification logs

### 6) LINE OA Integration
- [x] เพิ่ม endpoint webhook สำหรับ LINE OA
- [x] บันทึก event จาก LINE ลง notification logs
- [x] รองรับการส่งแจ้งเตือนบิลและ reminder ผ่าน LINE flow
- [x] เตรียม config สำหรับ channel access token และ channel secret

## การตรวจสอบและทดสอบ
- [x] ทดสอบด้วยชุด Feature Tests และ Unit Tests
- [x] ผลการทดสอบล่าสุดผ่าน 37 tests และมี 4 tests ถูก skip ตาม feature flag/API support
- [x] ยืนยันว่า dashboard เปิดใช้งานได้จริงด้วยสถานะ 200
- [x] เปิด dev server และเข้าใช้งานระบบได้

## พร้อมใช้งานตอนนี้
- URL สำหรับทดสอบในเครื่อง: http://127.0.0.1:8000
- มีข้อมูลตัวอย่างในระบบแล้ว สามารถเปิดดู flow ต่าง ๆ ได้ทันที

## งานที่สามารถทำต่อได้

### 1) Landing Page (Public Site)
- [x] เพิ่มหน้า Pricing Plan
- [x] เพิ่ม flow สมัครใช้งาน SaaS (Owner สมัคร)
- [x] สมัครแล้วสร้าง Tenant ใหม่ + สร้าง Admin User ของ Tenant อัตโนมัติ
- [x] เพิ่มหน้า Login
- [x] เพิ่ม Forgot Password
- [x] เพิ่ม Email Verification

### 2) Authentication / Authorization / Roles
- [ ] เพิ่มระบบ Authentication (เช่น Laravel Breeze/Jetstream หรือ custom) ให้ครบ flow
- [ ] เพิ่ม Role Permission ตามที่ระบุ (Super Admin / Support Admin / Owner / Staff)
- [ ] เพิ่ม Policy Authorization สำหรับโมดูลหลัก
- [ ] ผูกการเลือก tenant กับ user login (แทน query/session แบบ demo)

### ความคืบหน้าเพิ่มเติมล่าสุด
- [x] เพิ่ม role-based route guard เบื้องต้นสำหรับ tenant portal (`owner`, `staff`) และ admin portal (`super_admin`, `support_admin`)
- [x] เพิ่ม Gate สำหรับ `accessAdminPortal` และ `accessTenantPortal`
- [x] เพิ่มชุดทดสอบ authorization ของ admin/tenant portal

### 3) Multi-Tenant แบบใช้งานจริง
- [ ] Tenant resolve จาก domain/subdomain (ใช้ field `tenants.domain`)
- [ ] ป้องกันการข้าม tenant ให้ครบทุก entry point (รวมถึง webhook, resident portal)
- [ ] เพิ่ม Tenant Suspension ตามสถานะ/หมด trial

### 4) SaaS Subscription
- [ ] เพิ่ม Subscription Plan management
- [ ] เพิ่ม Trial Period enforcement ตาม `trial_ends_at`
- [ ] เพิ่ม Usage Limit
- [ ] เพิ่ม Billing Cycle / Subscription Billing

### 5) Billing & Invoice
- [ ] Generate ใบแจ้งหนี้ PDF
- [ ] Generate ใบเสร็จรับเงิน (Receipt) PDF
- [ ] ปรับ flow สถานะ invoice ให้ครบ (Draft/Sent/Paid/Overdue) + กติกาเปลี่ยนสถานะอัตโนมัติ

### 6) Payments
- [ ] เพิ่มการอัปโหลดสลิปจริง (file upload) + จัดเก็บไฟล์ (storage + validation)
- [ ] เพิ่ม Online Payment Gateway integration จริง
- [ ] เพิ่มหน้า/flow อนุมัติหรือปฏิเสธสลิป (ถ้าต้องการให้ staff ตรวจ)

### 7) Notification System
- [ ] แยก Notification เป็น service/queue job (ไม่ส่งใน request โดยตรง)
- [ ] เพิ่ม channel Email/SMS ตามเอกสาร (ปัจจุบันเน้น LINE)
- [ ] ทำ Notification Logs/Monitoring ให้ละเอียดขึ้น (เช่น response code, retry)
- [ ] เชื่อมต่อ LINE Messaging API ด้วย token จริงของโปรเจกต์ + ตรวจ signature webhook

### 8) Background Jobs / Scheduler
- [ ] สร้าง job: Generate Monthly Invoice
- [ ] สร้าง job: Send Reminder Notification
- [ ] สร้าง job: Expire Contract Check
- [ ] สร้าง job: Subscription Billing
- [ ] ตั้งค่า scheduler ให้รันตามเวลา (พร้อม queue worker/supervisor)

### 9) Security
- [ ] CSRF/Rate limiting สำหรับ endpoint ที่เสี่ยง (เช่น webhook)
- [ ] Signed URL สำหรับ invoice payment link (ให้ครบ flow)
- [ ] Audit/logging ที่จำเป็นสำหรับ SaaS (admin actions, payment actions)
