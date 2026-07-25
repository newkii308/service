<?php
/**
 * config.php — ตั้งค่าการเชื่อมต่อฐานข้อมูล
 * แก้ค่าให้ตรงกับ DirectAdmin ของคุณ
 */

// ---------- ฐานข้อมูล MySQL ----------
// >>> LOCAL TEST (XAMPP) — ตอนอัปขึ้น production ให้สลับกลับไปใช้บล็อกด้านล่าง <<<
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'vbepscappsho_n');   // << local
define('DB_USER', 'vbepscappsho_n');        // << local (XAMPP)
define('DB_PASS', 'newkii77');            // << local (XAMPP ไม่มีรหัส)
define('DB_CHARSET', 'utf8mb4');

// ---------- ค่า PRODUCTION เดิม (DirectAdmin) — กู้คืนตอนอัปจริง ----------
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'b6mcmaxhubst_arena');      // << ชื่อฐานข้อมูล
// define('DB_USER', 'b6mcmaxhubst_arena');      // << ผู้ใช้ฐานข้อมูล
// define('DB_PASS', 'BwekChmKYX8sN35pJZ88');  // << รหัสผ่านฐานข้อมูล

// ---------- โซนเวลา ----------
date_default_timezone_set('Asia/Bangkok');

// ---------- โหมด debug ----------
// เปิด (true) เฉพาะตอนพัฒนา แล้วปิด (false) บนเครื่องจริง
define('APP_DEBUG', true);   // LOCAL TEST — ปิดเป็น false ตอนอัปขึ้น production

// ---------- ระบบเมล (Mail Module) ----------
// กุญแจเข้ารหัสรหัสผ่านกล่องเมล / รหัสผ่าน API ของโฮสเมลที่เก็บใน DB (AES-256-CBC)
// *** สำคัญ: เปลี่ยนเป็นค่าสุ่มยาวๆ ของร้านคุณเอง แล้วอย่าทำหาย ไม่งั้นถอดรหัสผ่านเก่าไม่ได้ ***
define('MAIL_ENCRYPT_KEY', 'K9mP2vX7qL4wR8tY3nB6zC1jD5hF0sA9eG2iM7kO4uW');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
