# DOM.md

## Dormitory Management SaaS System

**Tech Stack:**

* Backend: PHP Laravel
* Template: AdminLTE v3
* Database: MySQL
* Architecture: Multi-Tenant SaaS

---

# 1. Project Overview

ระบบบริหารจัดการหอพักแบบ **Software as a Service (SaaS)** รองรับเจ้าของหอหลายรายภายในระบบเดียว (Multi-Tenant)

ผู้ใช้งานแบ่งออกเป็น 3 กลุ่มหลัก:

1. **Platform Admin (Super Admin)**
2. **Tenant Owner / Staff (User Portal)**
3. **End User (ผู้เช่า / ลูกห้อง)**

ระบบถูกออกแบบเพื่อช่วย:

* จัดการห้องพัก
* บริหารผู้เช่า
* จัดการสัญญาเช่า
* ออกบิลค่าเช่า
* รับชำระเงินออนไลน์
* แจ้งเตือนบิลอัตโนมัติ

---

# 2. System Architecture

## Multi-Tenant Concept

ระบบรองรับเจ้าของหอหลายรายในระบบเดียว

### Tenant Isolation Strategy

* Shared Database
* Shared Schema
* ทุก Table มี `tenant_id`
* Global Scope ใน Laravel เพื่อแยกข้อมูลแต่ละ Tenant

Example:

```
tenants
users
rooms
tenants_customers
contracts
invoices
payments
```

---

# 3. System Modules

---

## 3.1 Landing Page (Public Site)

หน้าสำหรับผู้ใช้งานทั่วไป

### Features

* Landing Page แนะนำบริการ
* สมัครใช้งาน SaaS
* Login
* Pricing Plan
* Forgot Password
* Email Verification

### User Flow

1. Owner สมัครใช้งาน
2. ระบบสร้าง Tenant ใหม่
3. สร้าง Admin User ของ Tenant
4. เข้าใช้งาน User Portal

---

## 3.2 User Portal (Tenant Owner Dashboard)

Template: **AdminLTE v3**

### Modules

#### Dashboard

* จำนวนห้องทั้งหมด
* ห้องว่าง / ห้องเต็ม
* รายรับเดือนปัจจุบัน
* บิลค้างชำระ
* กราฟรายได้

---

### Room Management

* เพิ่ม / แก้ไข / ลบ ห้อง
* ประเภทห้อง
* ราคาเช่า
* สถานะห้อง

Fields:

```
room_number
floor
room_type
price
status
tenant_id
```

---

### Tenant Management (ผู้เช่า)

* เพิ่มผู้เช่า
* แนบเอกสาร
* ประวัติการเช่า
* ช่องทางติดต่อ

Fields:

```
name
phone
email
line_id
id_card
room_id
```

---

### Rental Contract

* สร้างสัญญาเช่า
* วันเริ่มสัญญา
* วันหมดสัญญา
* เงินประกัน
* ค่าเช่ารายเดือน

Status:

* Active
* Expired
* Cancelled

---

### Billing & Invoice System

* สร้างบิลค่าเช่าอัตโนมัติ
* ค่าน้ำ
* ค่าไฟ
* ค่าบริการอื่น
* ใบแจ้งหนี้ PDF

Invoice Status:

* Draft
* Sent
* Paid
* Overdue

---

### Payment Management

รองรับ:

* Upload Slip
* Online Payment Gateway
* Manual Payment

Payment Status:

* Pending
* Approved
* Rejected

---

### Notification System

ระบบแจ้งเตือนผู้เช่า

Channels:

* Email
* LINE OA
* SMS

Trigger:

* สร้างบิลใหม่
* ใกล้ครบกำหนด
* ค้างชำระ

---

## 3.3 End User Portal (ลูกห้อง)

ผู้เช่าไม่ต้อง Login ระบบหลัก

Access ผ่าน:

* Email Link
* LINE OA
* SMS Link

### Features

* ดูใบแจ้งหนี้
* ดูประวัติการจ่าย
* ชำระเงินออนไลน์
* ดาวน์โหลดใบเสร็จ

---

## 3.4 Admin Portal (Platform Admin)

### Admin Dashboard — Business View

* จำนวน Tenant ทั้งหมด
* จำนวน User Active
* รายรับ SaaS
* Subscription Plan
* Monthly Revenue Graph

---

### Admin Dashboard — System Monitoring

* Server Status
* Queue Status
* Failed Jobs
* API Usage
* Notification Logs
* Payment Logs

---

# 4. User Roles

## Platform Roles

* Super Admin
* Support Admin

## Tenant Roles

* Owner
* Staff

## End User

* Resident (Tenant Customer)

---

# 5. Database Core Structure

## tenants

```
id
name
domain
plan
status
created_at
```

## users

```
id
tenant_id
name
email
password
role
```

## rooms

```
id
tenant_id
room_number
price
status
```

## customers

```
id
tenant_id
name
phone
email
```

## contracts

```
id
tenant_id
customer_id
room_id
start_date
end_date
deposit
```

## invoices

```
id
tenant_id
contract_id
invoice_no
total_amount
status
due_date
```

## payments

```
id
tenant_id
invoice_id
amount
payment_date
method
status
```

---

# 6. SaaS Features

* Multi-Tenant Isolation
* Subscription Plan
* Trial Period
* Usage Limit
* Billing Cycle
* Tenant Suspension

---

# 7. Technology Stack

Backend:

* Laravel
* Laravel Queue
* Laravel Notification
* Laravel Scheduler

Frontend:

* AdminLTE v3
* Blade Template
* AJAX / API

Database:

* MySQL

Infrastructure:

* Redis (Queue / Cache)
* Supervisor
* Nginx
* Docker (Optional)

---

# 8. Background Jobs

Scheduled Jobs:

* Generate Monthly Invoice
* Send Reminder Notification
* Expire Contract Check
* Subscription Billing

---

# 9. Security

* Tenant Data Isolation
* Role Permission
* CSRF Protection
* Rate Limiting
* Signed URL for Invoice Payment

---

# 10. Future Roadmap

* Mobile Application
* Smart Meter Integration
* Auto Meter Reading
* Accounting Export
* AI Revenue Prediction
* Multi Language Support

---

# 11. Development Convention

* Repository Pattern
* Service Layer
* Policy Authorization
* Form Request Validation
* RESTful API Standard
* Clean Controller (Thin Controller)

---

# 12. Success Goal

ระบบต้องสามารถ:

* รองรับหลายพันหอพัก
* ส่งบิลอัตโนมัติ
* ลดงาน Admin เจ้าของหอ
* ขยายเป็น PropTech SaaS ได้

---
