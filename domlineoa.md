# LINEOA.md

## LINE Official Account Integration

### Dormitory Management SaaS (Laravel Multi-Tenant)

---

# 1. Overview

LINE Official Account (LINE OA) ทำหน้าที่เป็น **Resident Portal** สำหรับผู้เช่า
โดยผู้เช่า **ไม่จำเป็นต้องสมัครสมาชิกหรือ Login ระบบ**

LINE OA จะกลายเป็น:

* Identity ของผู้เช่า
* ช่องทางแจ้งเตือนบิล
* ช่องทางชำระเงิน
* ช่องทางติดต่อหอพัก
* Self-Service Application

แนวคิดหลัก:

> **LINE = Login + Notification + Payment + Self Service**

---

# 2. Core Concept

## Resident Identity Model

ผู้เช่าถูกระบุตัวตนด้วย **LINE User ID**

```
LINE User ID → Resident Identity
```

ไม่ใช้:

* Username
* Password
* Mobile App Login

---

# 3. System Architecture

```
Resident
   ↓
LINE OA
   ↓ (Webhook)
Laravel LINE Controller
   ↓
Tenant Resolver
   ↓
Customer Resolver (line_user_id)
   ↓
Dormitory Business Logic
```

---

# 4. Multi-Tenant LINE Architecture

## Recommended Model ⭐⭐⭐⭐⭐

### 1 LINE OA ต่อ 1 หอพัก (Tenant)

Owner ใช้ LINE OA ของตัวเอง

Benefits:

* แยก Brand
* Owner เป็นเจ้าของลูกค้า
* Broadcast แยกหอได้
* Scale SaaS ได้ง่าย

---

### Tenant LINE Configuration

```
tenants
---------
id
name
line_channel_id
line_channel_secret
line_channel_access_token
line_webhook_url
```

---

# 5. Database Design

## customers

```
id
tenant_id
name
phone
room_id
line_user_id
line_linked_at
```

`line_user_id` คือ Primary Identity ของผู้เช่า

---

## customer_line_links

```
id
tenant_id
customer_id
link_token
expired_at
used_at
```

---

## line_webhook_logs

```
id
tenant_id
event_type
line_user_id
payload
created_at
```

---

## line_messages

```
id
tenant_id
customer_id
direction (inbound/outbound)
message_type
payload
sent_at
```

---

# 6. LINE Account Linking (Best Practice)

ผู้เช่าต้อง **Bind LINE กับห้องพัก**

---

## STEP 1 — Owner เพิ่มผู้เช่า

Owner สร้างข้อมูลผู้เช่าในระบบ

ระบบ Generate:

```
link_token = random_string()
```

สร้าง URL:

```
https://line.me/R/ti/p/@LINEOA?link_token=ABC123
```

หรือ QR Code

---

## STEP 2 — Resident Add Friend

ผู้เช่ากด Add LINE OA

LINE Bot ส่งข้อความ:

```
ยินดีต้อนรับ 👋
กรุณายืนยันตัวตนเพื่อเชื่อมต่อห้องพัก

[ยืนยันห้องพัก]
```

---

## STEP 3 — User Press Confirm

เปิด Web Linking Page

Laravel รับข้อมูล:

```
line_user_id
link_token
```

---

## STEP 4 — Bind Account

System Action:

```
customer.line_user_id = line_user_id
customer.line_linked_at = now()
```

Link Token ถูกปิดการใช้งานทันที

✅ เชื่อม LINE สำเร็จ

---

# 7. LINE Webhook Handling

Webhook Endpoint:

```
/api/line/webhook
```

Events ที่ใช้:

* follow
* unfollow
* message
* postback

---

## Webhook Flow

```
Receive Event
   ↓
Validate Signature
   ↓
Resolve Tenant (Channel ID)
   ↓
Resolve Customer (line_user_id)
   ↓
Route Command
```

---

# 8. Rich Menu (Resident Self Service)

LINE Rich Menu ทำหน้าที่เหมือน Mobile App

Recommended Menu:

```
📄 ดูบิล
💰 ชำระเงิน
🧾 ประวัติการจ่าย
🔧 แจ้งซ่อม
📢 ประกาศหอ
☎ ติดต่อเจ้าของ
```

---

# 9. Billing Notification

Trigger Events:

* Invoice Created
* Due Date Reminder
* Overdue Warning
* Payment Success

Example Message:

```
บิลค่าเช่าห้อง A203
ยอด 4,500 บาท
ครบกำหนด 5 มิ.ย.

[ดูบิล]
[ชำระเงิน]
```

---

# 10. Payment Flow via LINE

```
Invoice Generated
   ↓
Send LINE Notification
   ↓
Resident Open Payment Page
   ↓
PromptPay QR
   ↓
Upload Slip
   ↓
SlipOK Verification
   ↓
Send Payment Success Message
```

---

# 11. Chatbot Command System

ระบบอ่านข้อความจากผู้เช่า

Example Commands:

| Message  | Action               |
| -------- | -------------------- |
| บิล      | แสดงบิลล่าสุด        |
| จ่าย     | เปิดหน้าชำระเงิน     |
| ประวัติ  | แสดงประวัติการจ่าย   |
| แจ้งซ่อม | เปิดแบบฟอร์มแจ้งซ่อม |

---

## Command Router Concept

```
Incoming Message
   ↓
Command Parser
   ↓
Command Handler
   ↓
Response Builder
```

---

# 12. Broadcast Messaging

Owner สามารถส่งข้อความ:

* ทั้งหอ
* เฉพาะตึก
* เฉพาะชั้น
* เฉพาะห้อง

Use Cases:

* แจ้งดับน้ำ
* แจ้งซ่อม
* ประกาศสำคัญ

---

# 13. Automation & Reminder

Auto Jobs:

* เตือนก่อนครบกำหนด 3 วัน
* แจ้งค้างชำระ
* แจ้งต่อสัญญา
* แจ้งเตือนสัญญาใกล้หมด

---

# 14. Security Design

* Validate LINE Signature ทุกครั้ง
* Link Token มี Expiration
* 1 Token ใช้ได้ครั้งเดียว
* Encrypt Channel Token
* Tenant Isolation

---

# 15. Recommended Laravel Structure

```
app/
 ├── Services/
 │     └── Line/
 │          ├── LineService.php
 │          ├── LineWebhookHandler.php
 │          ├── CommandRouter.php
 │          └── MessageBuilder.php
 │
 ├── Jobs/
 │     └── SendLineMessageJob.php
```

---

# 16. Background Queue Jobs

* SendBillingNotificationJob
* SendReminderJob
* BroadcastMessageJob
* ProcessWebhookEventJob

ใช้ Redis Queue + Supervisor

---

# 17. Success Goal

ระบบต้องสามารถ:

* ใช้ LINE แทน Mobile App
* ผู้เช่าไม่ต้อง Login
* แจ้งเตือนบิลอัตโนมัติ
* ชำระเงินผ่าน LINE ได้ทันที
* รองรับหลายพัน Tenant (Multi-Tenant SaaS)

---

## Final Concept

```
Laravel SaaS Core
        +
Stripe Subscription
        +
PromptPay Payment
        +
SlipOK Verification
        +
LINE OA = Resident Super App
```

---
