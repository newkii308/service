# GameStore — Legacy Procedural PHP / Script-per-Page

เว็บถูกแยกจาก Vue SPA เดิมเป็น PHP แบบ procedural โดยแต่ละหน้าและแต่ละ action
มีไฟล์ PHP เป็น entry point ของตัวเอง เหมาะกับ DirectAdmin, cPanel และ shared hosting
ที่ใช้ PHP 8 + MySQL/MariaDB

## โครงสร้างหลัก

```text
public_html/
├── index.php                         หน้าแรกของเว็บไซต์
├── frontend/                         ทุกอย่างที่ผู้ใช้เห็น
│   ├── home/
│   ├── auth/
│   ├── account/
│   ├── contact/
│   ├── search/
│   ├── includes/                     layout/helper ที่ใช้ร่วมกัน
│   ├── assets/
│   └── products/
│       ├── game-codes/               โค้ดและไอดีเกม
│       ├── game-refills/             เติมเกม
│       ├── streaming-accounts/       บัญชี Streaming
│       ├── social-boost/             บริการ Social
│       ├── sms-otp/                  เช่าเบอร์รับ SMS
│       ├── app-otp/                  OTP แอปความบันเทิง
│       └── mail-rental/              เช่าอีเมล/กล่องเมล
├── backend/                          หลังบ้านและระบบภายใน
│   ├── dashboard/
│   ├── auth/
│   ├── categories/
│   ├── members/
│   ├── orders/
│   ├── settings/
│   ├── topups/
│   ├── includes/
│   ├── api/                          REST compatibility สำหรับ client เดิม
│   ├── database/schema.sql
│   ├── cron/
│   ├── tools/
│   └── products/
│       ├── game-codes/
│       ├── game-refills/
│       ├── streaming-accounts/
│       ├── social-boost/
│       ├── sms-otp/
│       ├── app-otp/
│       └── mail-rental/
└── uploads/                          ไฟล์อัปโหลดร่วมของร้าน
```

แต่ละโฟลเดอร์สินค้ามีหน้า `index.php`, หน้ารายละเอียด/แก้ไข และไฟล์ action
เช่น `purchase.php`, `save.php`, `toggle.php` หรือ `delete.php` ของโมดูลนั้นเอง
จึงไม่มีหน้า Vue router หรือ JavaScript component รวมทุกสินค้าไว้ไฟล์เดียวอีกต่อไป

## การตั้งค่า

1. ใช้ PHP 8.0 ขึ้นไป พร้อม `pdo_mysql`; โมดูลเมลใช้ `imap` และ `curl`
2. Import `backend/database/schema.sql`
3. ตั้งค่าฐานข้อมูลใน `backend/api/config.php`
4. เปลี่ยน `MAIL_ENCRYPT_KEY` ในไฟล์เดียวกันเป็นค่าสุ่มยาวของร้านและเก็บค่านี้ไว้
5. ให้ Apache เปิด `mod_rewrite` และอนุญาต `.htaccess`
6. โฟลเดอร์ `uploads/` ต้องเขียนไฟล์ได้
7. ตั้ง cron สำหรับ `backend/cron/mail_cleanup.php` ตามรอบที่ต้องการ

ไฟล์ `.htaccess` รองรับ URL เดิม เช่น `/products`, `/streaming`, `/otp`,
`/mail`, `/admin` และ `/api/...` โดยส่งต่อไปยังสคริปต์ PHP ใหม่

## ความปลอดภัย

- แบบฟอร์มที่เปลี่ยนข้อมูลตรวจ CSRF
- รหัสผ่านใช้ `password_hash`/`password_verify`
- Query ที่เพิ่มในโครงสร้างใหม่ใช้ PDO prepared statements และ tenant scope
- `backend/api` เปิดผ่าน HTTP ได้เฉพาะ `index.php`
- `backend/includes`, `database`, `cron` และ `tools` ถูกปิดการเข้าถึงตรง
- หลังบ้านต้องล็อกอินด้วย role `admin` และยืนยัน PIN 6 หลัก

ก่อนขึ้น production ให้ปิด `APP_DEBUG` ใน `backend/api/config.php` และใช้ข้อมูล
ฐานข้อมูล/กุญแจที่มาจาก environment หรือ secret ของโฮสต์แทนค่าทดสอบ
