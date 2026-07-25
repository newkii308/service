<?php
error_reporting(E_ALL & ~E_DEPRECATED);
/**
 * index.php — Front Controller / Router
 * เรียกใช้แบบ:  api/index.php?r=<route>   (เช่น ?r=auth/login)
 * หรือแบบ pretty url ผ่าน .htaccess (PATH_INFO): api/auth/login
 */
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/truemoney.php';
require_once __DIR__ . '/supplier.php';
require_once __DIR__ . '/mail.php';

startSession();
header('Content-Type: application/json; charset=utf-8');

// ---- หาเส้นทาง (route) ----
$route = $_GET['r'] ?? '';
if ($route === '' && !empty($_SERVER['PATH_INFO'])) {
    $route = trim($_SERVER['PATH_INFO'], '/');
}
$route  = trim($route, '/');
$method = $_SERVER['REQUEST_METHOD'];

// ป้องกัน CSRF สำหรับทุกคำขอที่เปลี่ยนแปลงข้อมูล
if ($method === 'POST') requireCsrf();

// บล็อกร้านที่ถูกระงับ/หมดอายุ (multi-tenant)
requireActiveTenant();

try {
    dispatch($route, $method);
    jsonError('ไม่พบเส้นทาง API: ' . $route, 404);
} catch (Throwable $e) {
    jsonError('เกิดข้อผิดพลาดในระบบ', 500, APP_DEBUG ? $e->getMessage() : null);
}

/* =============================================================
 *  ตัวจัดเส้นทาง
 * ============================================================= */
function dispatch(string $route, string $method): void {
    switch ($route) {
        /* ---------- Auth ---------- */
        case 'auth/register': onlyPost($method); h_register(); return;
        case 'auth/login':    onlyPost($method); h_login(); return;
        case 'auth/logout':   onlyPost($method); h_logout(); return;
        case 'auth/me':       h_me(); return;
        case 'auth/change_password': onlyPost($method); h_change_password(); return;
        case 'auth/update_profile':  onlyPost($method); h_update_profile(); return;

        /* ---------- สาธารณะ ---------- */
        case 'settings/public': h_public_settings(); return;
        case 'stats/public':    h_public_stats(); return;
        case 'categories':      h_categories(); return;
        case 'products':        h_products(); return;
        case 'product':         h_product(); return;
        case 'collections':     h_collections(); return;

        /* ---------- สมาชิก ---------- */
        case 'order/buy':     onlyPost($method); h_buy(); return;
        case 'orders':        h_orders(); return;
        case 'wallet/topup':  onlyPost($method); h_topup(); return;
        case 'wallet/topups': h_topups(); return;

        /* ---------- หลังบ้าน ---------- */
        case 'admin/stats':          h_admin_stats(); return;
        case 'admin/products':       h_admin_products(); return;
        case 'admin/product/save':   onlyPost($method); h_admin_product_save(); return;
        case 'admin/product/delete': onlyPost($method); h_admin_product_delete(); return;
        case 'admin/product/toggle': onlyPost($method); h_admin_product_toggle(); return;
        case 'admin/categories':     h_admin_categories(); return;
        case 'admin/category/save':  onlyPost($method); h_admin_category_save(); return;
        case 'admin/category/delete':onlyPost($method); h_admin_category_delete(); return;
        case 'admin/collections':          h_admin_collections(); return;
        case 'admin/collection/save':      onlyPost($method); h_admin_collection_save(); return;
        case 'admin/collection/delete':    onlyPost($method); h_admin_collection_delete(); return;
        case 'admin/collection/items':     h_admin_collection_items(); return;
        case 'admin/collection/item/add':    onlyPost($method); h_admin_collection_item_add(); return;
        case 'admin/collection/item/remove': onlyPost($method); h_admin_collection_item_remove(); return;
        case 'admin/collection/products/available': h_admin_collection_products_available(); return;
        case 'admin/codes':          h_admin_codes(); return;
        case 'admin/codes/add':      onlyPost($method); h_admin_codes_add(); return;
        case 'admin/code/delete':    onlyPost($method); h_admin_code_delete(); return;
        case 'admin/members':        h_admin_members(); return;
        case 'admin/member/adjust':  onlyPost($method); h_admin_member_adjust(); return;
        case 'admin/member/toggle':  onlyPost($method); h_admin_member_toggle(); return;
        case 'admin/orders':         h_admin_orders(); return;
        case 'admin/order/update_status': onlyPost($method); h_admin_order_update_status(); return;
        case 'admin/topups':         h_admin_topups(); return;
        case 'admin/pin/status':     h_admin_pin_status(); return;
        case 'admin/pin/verify':     onlyPost($method); h_admin_pin_verify(); return;
        case 'admin/pin/change':     onlyPost($method); h_admin_pin_change(); return;
        case 'admin/settings':       h_admin_settings(); return;
        case 'admin/settings/save':  onlyPost($method); h_admin_settings_save(); return;
        case 'admin/media':          h_admin_media_list(); return;
        case 'admin/media/upload':   onlyPost($method); h_admin_media_upload(); return;
        case 'admin/media/delete':   onlyPost($method); h_admin_media_delete(); return;

        /* ---------- ระบบเชื่อมต่อ Supplier API ---------- */
        case 'order/otp':            h_order_otp(); return;
        case 'order/otp/request':    onlyPost($method); h_order_otp_request(); return;
        case 'order/claim':          onlyPost($method); h_order_claim(); return;
        case 'order/claim/status':   h_order_claim_status(); return;
        case 'admin/supplier/status':   h_admin_supplier_status(); return;
        case 'admin/supplier/products': h_admin_supplier_products(); return;
        case 'admin/supplier/import':   onlyPost($method); h_admin_supplier_import(); return;
        case 'admin/meelike/status':     h_admin_meelike_status(); return;
        case 'admin/meelike/services':   h_admin_meelike_services(); return;
        case 'admin/meelike/import':     onlyPost($method); h_admin_meelike_import(); return;
        case 'admin/meelike/order_status': h_admin_meelike_order_status(); return;

        /* ---------- ระบบ SMS OTP (otp.md) ---------- */
        case 'sms/products':            h_sms_products(); return;
        case 'sms/buy':                 onlyPost($method); h_sms_buy(); return;
        case 'sms/status':              h_sms_status(); return;
        case 'sms/cancel':              onlyPost($method); h_sms_cancel(); return;
        case 'sms/buyagain':            onlyPost($method); h_sms_buyagain(); return;
        case 'admin/sms/save-markup':   onlyPost($method); h_admin_sms_save_markup(); return;

        /* ---------- ระบบเมล (เช่าอีเมล/กล่องเมล) ---------- */
        case 'mail/packages':        h_mail_packages(); return;
        case 'mail/boxes':           h_mail_boxes(); return;
        case 'mail/buy':             onlyPost($method); h_mail_buy(); return;
        case 'mail/box/delete':      onlyPost($method); h_mail_box_delete(); return;
        case 'mail/box/reveal':      h_mail_box_reveal(); return;
        case 'mail/refresh':         onlyPost($method); h_mail_refresh(); return;
        case 'mail/messages':        h_mail_messages(); return;
        case 'mail/message':         h_mail_message(); return;
        case 'mail/message/delete':  onlyPost($method); h_mail_message_delete(); return;
        case 'mail/webmail_hosts':   h_mail_webmail_hosts(); return;

        /* ---------- หลังบ้าน: ระบบเมล ---------- */
        case 'admin/mail/hosts':         h_admin_mail_hosts(); return;
        case 'admin/mail/host/save':      onlyPost($method); h_admin_mail_host_save(); return;
        case 'admin/mail/host/delete':    onlyPost($method); h_admin_mail_host_delete(); return;
        case 'admin/mail/host/test':      onlyPost($method); h_admin_mail_host_test(); return;
        case 'admin/mail/packages':       h_admin_mail_packages(); return;
        case 'admin/mail/package/save':   onlyPost($method); h_admin_mail_package_save(); return;
        case 'admin/mail/package/delete': onlyPost($method); h_admin_mail_package_delete(); return;
        case 'admin/mail/boxes':          h_admin_mail_boxes(); return;
    }
}

function onlyPost(string $method): void {
    if ($method !== 'POST') jsonError('ต้องเรียกด้วยเมธอด POST', 405);
}

/* =============================================================
 *  Auth
 * ============================================================= */
function h_register(): void {
    requireTurnstile();
    $username = trim((string)field('username'));
    $email    = trim((string)field('email'));
    $password = (string)field('password');

    if (mb_strlen($username) < 3)  jsonError('ชื่อผู้ใช้ต้องยาวอย่างน้อย 3 ตัวอักษร');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('อีเมลไม่ถูกต้อง');
    if (strlen($password) < 6)     jsonError('รหัสผ่านต้องยาวอย่างน้อย 6 ตัวอักษร');

    $stmt = db()->prepare('SELECT id FROM users WHERE tenant_id = ? AND (username = ? OR email = ?) LIMIT 1');
    $stmt->execute([tenantId(), $username, $email]);
    if ($stmt->fetch()) jsonError('ชื่อผู้ใช้หรืออีเมลนี้ถูกใช้แล้ว');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = db()->prepare('INSERT INTO users (tenant_id, username, email, password_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([tenantId(), $username, $email, $hash]);

    session_regenerate_id(true);
    $_SESSION['uid'] = (int)db()->lastInsertId();
    jsonOk(currentUser(), 'สมัครสมาชิกสำเร็จ');
}

function h_login(): void {
    if (!rateLimit('login:' . clientIp(), 8, 300)) {
        jsonError('พยายามเข้าสู่ระบบบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่', 429);
    }
    requireTurnstile();
    $login    = trim((string)field('username'));
    $password = (string)field('password');

    $stmt = db()->prepare('SELECT * FROM users WHERE tenant_id = ? AND (username = ? OR email = ?) LIMIT 1');
    $stmt->execute([tenantId(), $login, $login]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($password, $u['password_hash'])) {
        jsonError('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง', 401);
    }
    if ((int)$u['is_active'] !== 1) jsonError('บัญชีนี้ถูกระงับการใช้งาน', 403);

    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    jsonOk(currentUser(), 'เข้าสู่ระบบสำเร็จ');
}

function h_logout(): void {
    startSession();
    clearAdminPinVerified();
    $_SESSION = [];
    session_destroy();
    jsonOk([], 'ออกจากระบบแล้ว');
}

function h_me(): void {
    jsonOk(currentUser());
}

function h_change_password(): void {
    $u   = requireAuth();
    $cur = (string)field('current');
    $new = (string)field('new');
    if (strlen($new) < 6) jsonError('รหัสผ่านใหม่ต้องยาวอย่างน้อย 6 ตัวอักษร');

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$u['id'], tenantId()]);
    $hash = $stmt->fetchColumn();
    if (!password_verify($cur, $hash)) jsonError('รหัสผ่านปัจจุบันไม่ถูกต้อง');

    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND tenant_id = ?')
        ->execute([password_hash($new, PASSWORD_BCRYPT), $u['id'], tenantId()]);
    jsonOk([], 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
}

function h_update_profile(): void {
    $u     = requireAuth();
    $email = trim((string)field('email'));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('อีเมลไม่ถูกต้อง');

    $stmt = db()->prepare('SELECT id FROM users WHERE tenant_id = ? AND email = ? AND id <> ? LIMIT 1');
    $stmt->execute([tenantId(), $email, $u['id']]);
    if ($stmt->fetch()) jsonError('อีเมลนี้ถูกใช้แล้ว');

    db()->prepare('UPDATE users SET email = ? WHERE id = ? AND tenant_id = ?')->execute([$email, $u['id'], tenantId()]);
    jsonOk(currentUser(), 'บันทึกโปรไฟล์แล้ว');
}

/* =============================================================
 *  สาธารณะ
 * ============================================================= */
function h_public_stats(): void {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE tenant_id = ? AND role = ?');
    $stmt->execute([tenantId(), 'member']);
    $members = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM topups WHERE tenant_id = ? AND status = 'success'");
    $stmt->execute([tenantId()]);
    $topup = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_codes WHERE tenant_id = ? AND status = 'available'");
    $stmt->execute([tenantId()]);
    $stock = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE tenant_id = ?');
    $stmt->execute([tenantId()]);
    $sales = (int)$stmt->fetchColumn();

    jsonOk([
        'users' => $members,
        'topup' => $topup,
        'stock' => $stock,
        'sales' => $sales,
    ]);
}

function h_public_settings(): void {
    jsonOk([
        'site_name'              => setting('site_name', 'GameStore'),
        'site_tagline'           => setting('site_tagline', ''),
        'sales_enabled'          => setting('sales_enabled', '1') === '1',
        'code_sales_enabled'     => setting('code_sales_enabled', '1') === '1',
        'refill_sales_enabled'   => setting('refill_sales_enabled', '1') === '1',
        'streaming_enabled'      => setting('streaming_enabled', '1') === '1',
        'otp_enabled'            => setting('otp_enabled', '1') === '1',
        'sms_otp_enabled'        => setting('sms_otp_enabled', '1') === '1',
        'social_enabled'         => setting('social_enabled', '1') === '1',
        'contact_line'           => setting('contact_line', ''),
        'contact_facebook'       => setting('contact_facebook', ''),
        'contact_email'          => setting('contact_email', ''),
        'contact_phone'          => setting('contact_phone', ''),
        'site_announcement'      => setting('site_announcement', ''),
        'site_footer'            => setting('site_footer', '© 2026 GameStore. All Rights Reserved.'),
        'site_logo'              => setting('site_logo', ''),
        'site_favicon'           => setting('site_favicon', ''),
        'site_banner'            => setting('site_banner', ''),
        'turnstile_site_key'     => setting('turnstile_site_key', ''),
        'color_primary'          => setting('color_primary', '#6366f1'),
        'color_primary_hover'    => setting('color_primary_hover', '#4f46e5'),
        'color_accent'           => setting('color_accent', '#06b6d4'),
        'color_bg'               => setting('color_bg', '#f5f6fa'),
        'color_bg_card'          => setting('color_bg_card', '#ffffff'),
        'border_radius'          => (int)setting('border_radius', '18'),
        'nav_show_home'          => setting('nav_show_home', '1') === '1',
        'nav_show_products'      => setting('nav_show_products', '1') === '1',
        'nav_show_refills'       => setting('nav_show_refills', '1') === '1',
        'nav_show_streaming'     => setting('nav_show_streaming', '1') === '1',
        'nav_show_otp'           => setting('nav_show_otp', '1') === '1',
        'nav_show_mail'          => setting('nav_show_mail', '1') === '1',
        'nav_show_social'        => setting('nav_show_social', '1') === '1',
        'nav_show_topup'         => setting('nav_show_topup', '1') === '1',
        'nav_show_contact'       => setting('nav_show_contact', '1') === '1',
        'quick_menu_1_img'       => setting('quick_menu_1_img', 'assets/images/menu-product.jpg'),
        'quick_menu_1_label'     => setting('quick_menu_1_label', 'สินค้าทั้งหมด'),
        'quick_menu_1_link'      => setting('quick_menu_1_link', '/products'),
        'quick_menu_2_img'       => setting('quick_menu_2_img', 'assets/images/menu-topup.jpg'),
        'quick_menu_2_label'     => setting('quick_menu_2_label', 'เติมเงินเข้าระบบ'),
        'quick_menu_2_link'      => setting('quick_menu_2_link', '/topup'),
        'quick_menu_3_img'       => setting('quick_menu_3_img', 'assets/images/menu-history.jpg'),
        'quick_menu_3_label'     => setting('quick_menu_3_label', 'ประวัติการซื้อ'),
        'quick_menu_3_link'      => setting('quick_menu_3_link', '/orders'),
        'quick_menu_4_img'       => setting('quick_menu_4_img', 'assets/images/menu-contact.jpg'),
        'quick_menu_4_label'     => setting('quick_menu_4_label', 'ติดต่อทีมงาน'),
        'quick_menu_4_link'      => setting('quick_menu_4_link', '/contact'),
    ]);
}

function h_categories(): void {
    // คืนรูปแบนเนอร์ + จำนวนสินค้า + ช่วงราคา (นับเฉพาะสินค้าที่เปิดขาย)
    $stmt = db()->prepare(
        "SELECT c.id, c.name, c.slug, c.cover_image,
                COUNT(p.id)               AS product_count,
                COALESCE(MIN(p.price), 0) AS min_price,
                COALESCE(MAX(p.price), 0) AS max_price
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.tenant_id = c.tenant_id
         WHERE c.tenant_id = ?
         GROUP BY c.id, c.name, c.slug, c.cover_image, c.sort_order
         ORDER BY c.sort_order, c.name"
    );
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['product_count'] = (int)$r['product_count'];
        $r['min_price']     = (float)$r['min_price'];
        $r['max_price']     = (float)$r['max_price'];
    }
    jsonOk($rows);
}

function h_products(): void {
    $cat  = isset($_GET['category']) ? (int)$_GET['category'] : 0;
    $q    = trim((string)($_GET['q'] ?? ''));
    $type = trim((string)($_GET['type'] ?? ''));

    $sql = "SELECT p.id, p.type, p.name, p.slug, p.price, p.cover_image, p.category_id,
                   p.api_type_id, p.otp_service, p.otp_type, p.description,
                   c.name AS category_name,
                   (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id AND pc.status='available') AS stock
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
            WHERE p.is_active = 1 AND p.tenant_id = ?";
    $args = [tenantId()];
    if ($cat > 0) { $sql .= ' AND p.category_id = ?'; $args[] = $cat; }
    if ($q !== '') { $sql .= ' AND p.name LIKE ?'; $args[] = '%' . $q . '%'; }
    if ($type !== '' && in_array($type, ['code', 'refill', 'streaming', 'otp', 'social'])) { $sql .= ' AND p.type = ?'; $args[] = $type; }
    $sql .= ' ORDER BY p.sort_order, p.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['price'] = (float)$r['price']; $r['stock'] = (int)$r['stock']; }
    jsonOk($rows);
}

function h_collections(): void {
    // กลุ่มสินค้าที่แอดมินจัดไว้ (สินค้าแนะนำ/ยอดนิยม/กลุ่มที่สร้างเอง) — ใช้แสดงหน้าแรก
    $slug = trim((string)($_GET['slug'] ?? ''));
    $sql = "SELECT id, name, slug, sort_order FROM product_collections WHERE tenant_id = ? AND is_active = 1";
    $args = [tenantId()];
    if ($slug !== '') { $sql .= ' AND slug = ?'; $args[] = $slug; }
    $sql .= ' ORDER BY sort_order, id';
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    $collections = $stmt->fetchAll();

    $pstmt = db()->prepare(
        "SELECT p.id, p.type, p.name, p.slug, p.price, p.cover_image, p.category_id,
                (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id AND pc.status='available') AS stock
         FROM product_collection_items ci
         JOIN products p ON p.id = ci.product_id AND p.tenant_id = ci.tenant_id
         WHERE ci.collection_id = ? AND ci.tenant_id = ? AND p.is_active = 1
         ORDER BY ci.sort_order, ci.id"
    );
    foreach ($collections as &$c) {
        $pstmt->execute([$c['id'], tenantId()]);
        $items = $pstmt->fetchAll();
        foreach ($items as &$it) { $it['price'] = (float)$it['price']; $it['stock'] = (int)$it['stock']; }
        $c['products'] = $items;
    }
    jsonOk($collections);
}


function h_product(): void {
    $id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $slug = trim((string)($_GET['slug'] ?? ''));

    if ($id > 0) {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$id, tenantId()]);
    } else {
        $stmt = db()->prepare('SELECT * FROM products WHERE slug = ? AND tenant_id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug, tenantId()]);
    }
    $p = $stmt->fetch();
    if (!$p) jsonError('ไม่พบสินค้า', 404);

    $p['price'] = (float)$p['price'];
    $p['stock'] = stockOf((int)$p['id']);
    jsonOk($p);
}

/* =============================================================
 *  ซื้อสินค้า
 * ============================================================= */
function h_buy(): void {
    $user = requireAuth();
    if (setting('sales_enabled', '1') !== '1') jsonError('ขณะนี้ปิดการขายชั่วคราว');

    $productId = (int)field('product_id');
    if ($productId <= 0) jsonError('ไม่พบสินค้าที่ต้องการซื้อ');

    $refillUid    = trim((string)field('refill_uid'));
    $refillServer = trim((string)field('refill_server'));
    $socialLink   = trim((string)field('social_link'));
    $socialQty    = (int)field('social_quantity');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // ล็อกสินค้า + ตรวจสอบสถานะเปิดขาย
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND tenant_id = ? FOR UPDATE');
        $stmt->execute([$productId, tenantId()]);
        $product = $stmt->fetch();
        if (!$product || (int)$product['is_active'] !== 1) {
            $pdo->rollBack(); jsonError('สินค้านี้ไม่พร้อมจำหน่าย');
        }

        $type = $product['type'] ?? 'code';

        // เช็คการเปิด/ปิดแต่ละระบบ
        if ($type === 'code' && setting('code_sales_enabled', '1') !== '1') {
            $pdo->rollBack(); jsonError('ขณะนี้ปิดระบบขายโค้ดชั่วคราว');
        }
        if ($type === 'refill' && setting('refill_sales_enabled', '1') !== '1') {
            $pdo->rollBack(); jsonError('ขณะนี้ปิดระบบเติมเกมชั่วคราว');
        }
        if ($type === 'streaming' && setting('streaming_enabled', '1') !== '1') {
            $pdo->rollBack(); jsonError('ขณะนี้ปิดบริการ Streaming ชั่วคราว');
        }
        if ($type === 'otp' && setting('otp_enabled', '1') !== '1') {
            $pdo->rollBack(); jsonError('ขณะนี้ปิดบริการ OTP ชั่วคราว');
        }
        if ($type === 'social' && setting('social_enabled', '1') !== '1') {
            $pdo->rollBack(); jsonError('ขณะนี้ปิดบริการโซเชียลชั่วคราว');
        }

        // ตรวจสอบ API key สำหรับ streaming, otp และ social
        $apiKey = '';
        $meelikeApiKey = '';
        $apiTypeId = '';
        $otpService = '';
        $otpType = '';
        if ($type === 'streaming' || $type === 'otp') {
            $apiKey = setting('supplier_api_key', '');
            if (!$apiKey) { $pdo->rollBack(); jsonError('ระบบเชื่อมต่อคู่ค้ายังไม่ได้รับการตั้งค่า'); }
        }
        if ($type === 'streaming') {
            $apiTypeId = $product['api_type_id'] ?? '';
            if (!$apiTypeId) { $pdo->rollBack(); jsonError('สินค้า Streaming นี้ไม่พบรหัสอ้างอิงคู่ค้า'); }
        }
        if ($type === 'otp') {
            $otpService = $product['otp_service'] ?? '';
            $otpType = $product['otp_type'] ?? '';
            if (!$otpService) { $pdo->rollBack(); jsonError('สินค้า OTP นี้ไม่ได้ระบุประเภทบริการ'); }
        }
        if ($type === 'social') {
            $apiTypeId = $product['api_type_id'] ?? '';
            $meelikeApiKey = setting('meelike_api_key', '');
            if (!$meelikeApiKey) { $pdo->rollBack(); jsonError('ยังไม่ได้ตั้งค่า MeeLike API Key'); }
            if (!$apiTypeId) { $pdo->rollBack(); jsonError('บริการโซเชียลนี้ยังไม่ได้ระบุ Service ID'); }
            if ($socialLink === '' || !preg_match('/^https?:\/\//i', $socialLink)) {
                $pdo->rollBack(); jsonError('กรุณากรอกลิงก์เป้าหมายให้ถูกต้อง');
            }
            if ($socialQty <= 0) {
                $pdo->rollBack(); jsonError('กรุณากรอกจำนวนที่ต้องการสั่งซื้อ');
            }
        }

        $code = null;
        if ($type === 'code') {
            // ล็อกโค้ดที่ว่างหนึ่งชิ้น
            $stmt = $pdo->prepare("SELECT * FROM product_codes WHERE product_id = ? AND tenant_id = ? AND status='available' ORDER BY id LIMIT 1 FOR UPDATE");
            $stmt->execute([$productId, tenantId()]);
            $code = $stmt->fetch();
            if (!$code) { $pdo->rollBack(); jsonError('สินค้าหมดสต็อก'); }
        } else if ($type === 'refill') {
            if ($refillUid === '') {
                $pdo->rollBack(); jsonError('กรุณากรอก Player ID (UID) สำหรับการเติมเกม');
            }
        }

        // ล็อกผู้ใช้ + ตรวจสอบยอดเงิน
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
        $stmt->execute([$user['id'], tenantId()]);
        $balance = (float)$stmt->fetchColumn();
        $price   = (float)$product['price'];
        if ($type === 'social') {
            $price = round(((float)$product['price'] * $socialQty) / 1000, 2);
            if ($price <= 0) {
                $pdo->rollBack(); jsonError('ราคาคำสั่งซื้อไม่ถูกต้อง');
            }
        }
        if ($balance < $price) {
            $pdo->rollBack();
            jsonError('ยอดเงินไม่เพียงพอ กรุณาเติมเงินก่อนสั่งซื้อ');
        }

        // หักเงิน
        $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND tenant_id = ?')
            ->execute([$price, $user['id'], tenantId()]);

        if ($type === 'code') {
            $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_id, product_name, price, status, code_content) VALUES (?,?,?,?,?,?,?)')
                ->execute([tenantId(), $user['id'], $productId, $product['name'], $price, 'completed', $code['content']]);
            $orderId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE product_codes SET status='sold', order_id=?, sold_at=NOW() WHERE id=?")
                ->execute([$orderId, $code['id']]);
        } else if ($type === 'refill') {
            $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_id, product_name, price, status, refill_uid, refill_server, code_content) VALUES (?,?,?,?,?,?,?,?,NULL)')
                ->execute([tenantId(), $user['id'], $productId, $product['name'], $price, 'pending', $refillUid, $refillServer]);
            $orderId = (int)$pdo->lastInsertId();
        } else if ($type === 'streaming') {
            // ตั้งออเดอร์ชั่วคราวก่อนยิง API
            $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_id, product_name, price, status, code_content) VALUES (?,?,?,?,?,?,?)')
                ->execute([tenantId(), $user['id'], $productId, $product['name'], $price, 'processing', 'กำลังดำเนินการดึงสินค้าจากคู่ค้า...']);
            $orderId = (int)$pdo->lastInsertId();
        } else if ($type === 'otp') {
            // OTP product — บันทึกออเดอร์รอรับ OTP (user กด "ขอ OTP" ภายหลัง)
            $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_id, product_name, price, status, code_content) VALUES (?,?,?,?,?,?,?)')
                ->execute([tenantId(), $user['id'], $productId, $product['name'], $price, 'completed', 'กดปุ่ม "ขอรับ OTP" เพื่อรับรหัสเข้าใช้งาน']);
            $orderId = (int)$pdo->lastInsertId();
        } else if ($type === 'social') {
            $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_id, product_name, price, status, refill_uid, refill_server, code_content) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([tenantId(), $user['id'], $productId, $product['name'], $price, 'processing', $socialLink, (string)$socialQty, 'กำลังส่งคำสั่งซื้อไปยัง MeeLike...']);
            $orderId = (int)$pdo->lastInsertId();
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonError('ทำรายการไม่สำเร็จ', 500, APP_DEBUG ? $e->getMessage() : null);
    }

    // หากเป็นประเภท 'streaming' -> ดำเนินการยิงสั่งซื้อจากคู่ค้าหลังปล่อย transaction lock
    if ($type === 'streaming') {
        $apiRes = supplier_buy_product($apiKey, $apiTypeId, $user['username']);
        
        if (isset($apiRes['ok']) && $apiRes['ok'] === true) {
            $supplierData = $apiRes['data'] ?? [];
            $supplierUid = $supplierData['uid'] ?? '';
            $deliveredContent = $supplierData['textdb'] ?? $supplierData['details'] ?? 'ซื้อสำเร็จแล้ว';
            
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE orders SET status = ?, code_content = ?, api_order_id = ? WHERE id = ?')
                    ->execute(['completed', $deliveredContent, $supplierUid, $orderId]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); }
        } else {
            $errorMsg = $apiRes['message'] ?? $apiRes['msg'] ?? 'คู่ค้าปฏิเสธรายการสั่งซื้อ';
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE orders SET status = ?, code_content = ? WHERE id = ?')
                    ->execute(['cancelled', 'ทำรายการล้มเหลว: ' . $errorMsg, $orderId]);
                $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')
                    ->execute([$price, $user['id'], tenantId()]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); }
            jsonError('คู่ค้าขัดข้อง: ' . $errorMsg);
        }
    }
    if ($type === 'social') {
        $apiRes = meelike_add_order($meelikeApiKey, $apiTypeId, $socialLink, $socialQty);
        $partnerOrder = (string)($apiRes['order'] ?? '');
        if ($partnerOrder !== '') {
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE orders SET status = ?, code_content = ?, api_order_id = ? WHERE id = ?')
                    ->execute(['processing', 'ส่งคำสั่งซื้อไปยัง MeeLike แล้ว กำลังดำเนินการ', $partnerOrder, $orderId]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); }
        } else {
            $errorMsg = $apiRes['error'] ?? $apiRes['message'] ?? 'MeeLike ปฏิเสธรายการสั่งซื้อ';
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE orders SET status = ?, code_content = ? WHERE id = ?')
                    ->execute(['cancelled', 'ทำรายการล้มเหลว: ' . $errorMsg, $orderId]);
                $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')
                    ->execute([$price, $user['id'], tenantId()]);
                $pdo->commit();
            } catch (Throwable $e) { $pdo->rollBack(); }
            jsonError('MeeLike ขัดข้อง: ' . $errorMsg);
        }
    }

    $stmt = db()->prepare('SELECT status, code_content FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $orderFinal = $stmt->fetch();

    jsonOk([
        'order_id'     => $orderId,
        'product_name' => $product['name'],
        'price'        => (float)$price,
        'type'         => $type,
        'status'       => $orderFinal['status'] ?? 'pending',
        'refill_uid'    => $refillUid ?: null,
        'refill_server' => $refillServer ?: null,
        'code_content' => $orderFinal['code_content'] ?? null,
        'balance'      => currentUser()['balance'],
    ], 'ซื้อสินค้าสำเร็จ');
}

function h_orders(): void {
    $user = requireAuth();
    $stmt = db()->prepare('SELECT o.id, o.product_name, o.price, o.status, o.refill_uid, o.refill_server, o.code_content, o.api_order_id, o.created_at, COALESCE(p.type, \'code\') AS product_type FROM orders o LEFT JOIN products p ON p.id = o.product_id WHERE o.user_id = ? AND o.tenant_id = ? ORDER BY o.id DESC');
    $stmt->execute([$user['id'], tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['price'] = (float)$r['price'];
    jsonOk($rows);
}

/* =============================================================
 *  เติมเงิน — ซองอั่งเปาทรูมันนี่
 * ============================================================= */
function h_topup(): void {
    $user    = requireAuth();
    if (!rateLimit('topup:' . $user['id'], 10, 60)) {
        jsonError('เติมเงินบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่', 429);
    }
    $voucher = trim((string)field('voucher'));
    if ($voucher === '') jsonError('กรุณาวางลิงก์ซองอั่งเปา');

    $ownerPhone = setting('truemoney_phone', '');
    $result = tmRedeem($voucher, $ownerPhone);
    $hash   = $result['hash'];

    // ป้องกันการรับซ้ำ: ถ้า hash นี้เคยเติมสำเร็จแล้ว ไม่บวกซ้ำ
    if ($result['ok'] && $hash) {
        $stmt = db()->prepare("SELECT id FROM topups WHERE tenant_id = ? AND voucher_hash = ? AND status='success' LIMIT 1");
        $stmt->execute([tenantId(), $hash]);
        if ($stmt->fetch()) {
            $result['ok'] = false;
            $result['message'] = 'ซองนี้ถูกใช้เติมเงินไปแล้ว';
        }
    }

    if ($result['ok']) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')
                ->execute([$result['amount'], $user['id'], tenantId()]);
            $pdo->prepare('INSERT INTO topups (tenant_id, user_id, voucher_hash, amount, status, message) VALUES (?,?,?,?,?,?)')
                ->execute([tenantId(), $user['id'], $hash, $result['amount'], 'success', $result['message']]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonError('บันทึกการเติมเงินไม่สำเร็จ', 500, APP_DEBUG ? $e->getMessage() : null);
        }
        jsonOk([
            'amount'  => $result['amount'],
            'balance' => currentUser()['balance'],
        ], 'เติมเงินสำเร็จ +' . number_format($result['amount'], 2) . ' บาท');
    }

    // บันทึกความล้มเหลว (เพื่อประวัติ) แล้วแจ้ง error
    $stmt = db()->prepare('INSERT INTO topups (tenant_id, user_id, voucher_hash, amount, status, message) VALUES (?,?,?,?,?,?)');
    $stmt->execute([tenantId(), $user['id'], $hash, 0, 'failed', $result['message']]);
    jsonError($result['message']);
}

function h_topups(): void {
    $user = requireAuth();
    $stmt = db()->prepare('SELECT id, amount, status, message, created_at FROM topups WHERE user_id = ? ORDER BY id DESC');
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['amount'] = (float)$r['amount'];
    jsonOk($rows);
}

/* =============================================================
 *  หลังบ้าน — Dashboard
 * ============================================================= */
function h_admin_stats(): void {
    requireAdmin();
    $pdo = db();
    $scalar = static function (string $sql) use ($pdo) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([tenantId()]);
        return $stmt->fetchColumn();
    };
    $members   = (int)$scalar("SELECT COUNT(*) FROM users WHERE tenant_id = ? AND role='member'");
    $orders    = (int)$scalar('SELECT COUNT(*) FROM orders WHERE tenant_id = ?');
    $revenue   = (float)$scalar("SELECT COALESCE(SUM(price),0) FROM orders WHERE tenant_id = ? AND status != 'cancelled'");
    $topupSum  = (float)$scalar("SELECT COALESCE(SUM(amount),0) FROM topups WHERE tenant_id = ? AND status='success'");
    $products  = (int)$scalar('SELECT COUNT(*) FROM products WHERE tenant_id = ?');
    $lowStockStmt = $pdo->prepare(
        "SELECT p.id, p.name,
                (SELECT COUNT(*) FROM product_codes pc
                 WHERE pc.product_id=p.id AND pc.tenant_id=p.tenant_id AND pc.status='available') AS stock
         FROM products p WHERE p.tenant_id = ? AND p.type='code'
         HAVING stock <= 3 ORDER BY stock ASC LIMIT 10");
    $lowStockStmt->execute([tenantId()]);
    $lowStock = $lowStockStmt->fetchAll();
    $recentStmt = $pdo->prepare(
        "SELECT o.id, o.product_name, o.price, o.created_at, u.username
         FROM orders o JOIN users u ON u.id=o.user_id AND u.tenant_id=o.tenant_id
         WHERE o.tenant_id = ? ORDER BY o.id DESC LIMIT 8");
    $recentStmt->execute([tenantId()]);
    $recentOrders = $recentStmt->fetchAll();

    jsonOk([
        'members'  => $members,
        'orders'   => $orders,
        'revenue'  => $revenue,
        'topupSum' => $topupSum,
        'products' => $products,
        'lowStock' => $lowStock,
        'recentOrders' => $recentOrders,
    ]);
}

/* ---------- สินค้า ---------- */
function h_admin_products(): void {
    requireAdmin();
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name,
                (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id=p.id AND pc.tenant_id=p.tenant_id AND pc.status='available') AS stock
         FROM products p LEFT JOIN categories c ON c.id=p.category_id AND c.tenant_id=p.tenant_id
         WHERE p.tenant_id = ?
         ORDER BY p.sort_order, p.id DESC");
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['price']=(float)$r['price']; $r['stock']=(int)$r['stock']; }
    jsonOk($rows);
}

function h_admin_product_save(): void {
    requireAdmin();
    $id          = (int)field('id');
    $name        = trim((string)field('name'));
    $type        = trim((string)field('type')) ?: 'code';
    $categoryId  = (int)field('category_id') ?: null;
    $price       = (float)field('price');
    $description = (string)field('description');
    $cover       = trim((string)field('cover_image'));
    $apiTypeId   = trim((string)field('api_type_id'));
    $otpService  = trim((string)field('otp_service')) ?: null;
    $otpType     = trim((string)field('otp_type')) ?: null;
    $sort        = (int)field('sort_order');
    $active      = (int)field('is_active', 1) ? 1 : 0;

    if ($name === '') jsonError('กรุณากรอกชื่อสินค้า');
    if ($price < 0)   jsonError('ราคาไม่ถูกต้อง');
    if (!in_array($type, ['code', 'refill', 'streaming', 'otp', 'social'])) $type = 'code';

    if ($id > 0) {
        db()->prepare('UPDATE products SET category_id=?, type=?, api_type_id=?, otp_service=?, otp_type=?, name=?, price=?, description=?, cover_image=?, sort_order=?, is_active=? WHERE id=? AND tenant_id=?')
            ->execute([$categoryId, $type, $apiTypeId, $otpService, $otpType, $name, $price, $description, $cover, $sort, $active, $id, tenantId()]);
    } else {
        $slug = unique_product_slug($name);
        db()->prepare('INSERT INTO products (tenant_id, category_id, type, api_type_id, otp_service, otp_type, name, slug, price, description, cover_image, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([tenantId(), $categoryId, $type, $apiTypeId, $otpService, $otpType, $name, $slug, $price, $description, $cover, $sort, $active]);
        $id = (int)db()->lastInsertId();
    }
    jsonOk(['id'=>$id], 'บันทึกสินค้าแล้ว');
}

function h_admin_product_delete(): void {
    requireAdmin();
    $id = (int)field('id');
    db()->prepare('DELETE FROM products WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'ลบสินค้าแล้ว');
}

function h_admin_product_toggle(): void {
    requireAdmin();
    $id = (int)field('id');
    db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'อัปเดตสถานะการขายแล้ว');
}

/* ---------- หมวดหมู่ ---------- */
function h_admin_categories(): void {
    requireAdmin();
    $stmt = db()->prepare('SELECT * FROM categories WHERE tenant_id = ? ORDER BY sort_order, name');
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_admin_category_save(): void {
    requireAdmin();
    $id    = (int)field('id');
    $name  = trim((string)field('name'));
    $sort  = (int)field('sort_order');
    $cover = trim((string)field('cover_image')) ?: null;
    if ($name === '') jsonError('กรุณากรอกชื่อหมวดหมู่');

    if ($id > 0) {
        db()->prepare('UPDATE categories SET name=?, cover_image=?, sort_order=? WHERE id=? AND tenant_id=?')->execute([$name, $cover, $sort, $id, tenantId()]);
    } else {
        db()->prepare('INSERT INTO categories (tenant_id, name, slug, cover_image, sort_order) VALUES (?,?,?,?,?)')
            ->execute([tenantId(), $name, slugify($name), $cover, $sort]);
    }
    jsonOk([], 'บันทึกหมวดหมู่แล้ว');
}

function h_admin_category_delete(): void {
    requireAdmin();
    $id = (int)field('id');
    db()->prepare('UPDATE products SET category_id = NULL WHERE category_id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    db()->prepare('DELETE FROM categories WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'ลบหมวดหมู่แล้ว');
}

/* ---------- กลุ่มสินค้า (สินค้าแนะนำ / ยอดนิยม / กลุ่มที่สร้างเอง) ---------- */
function h_admin_collections(): void {
    requireAdmin();
    $stmt = db()->prepare(
        "SELECT c.id, c.name, c.slug, c.sort_order, c.is_active,
                (SELECT COUNT(*) FROM product_collection_items i WHERE i.collection_id = c.id AND i.tenant_id = c.tenant_id) AS item_count
         FROM product_collections c
         WHERE c.tenant_id = ?
         ORDER BY c.sort_order, c.id"
    );
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['item_count'] = (int)$r['item_count']; $r['is_active'] = (int)$r['is_active']; }
    jsonOk($rows);
}

function unique_collection_slug(string $name, int $excludeId = 0): string {
    $base = slugify($name) ?: 'group';
    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT id FROM product_collections WHERE tenant_id = ? AND slug = ?';
        $args = [tenantId(), $slug];
        if ($excludeId > 0) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
        $stmt = db()->prepare($sql);
        $stmt->execute($args);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}

function h_admin_collection_save(): void {
    requireAdmin();
    $id     = (int)field('id');
    $name   = trim((string)field('name'));
    $sort   = (int)field('sort_order');
    $active = (int)field('is_active', 1) ? 1 : 0;
    if ($name === '') jsonError('กรุณากรอกชื่อกลุ่มสินค้า');

    if ($id > 0) {
        db()->prepare('UPDATE product_collections SET name=?, sort_order=?, is_active=? WHERE id=? AND tenant_id=?')
            ->execute([$name, $sort, $active, $id, tenantId()]);
    } else {
        $slug = unique_collection_slug($name);
        db()->prepare('INSERT INTO product_collections (tenant_id, name, slug, sort_order, is_active) VALUES (?,?,?,?,?)')
            ->execute([tenantId(), $name, $slug, $sort, $active]);
        $id = (int)db()->lastInsertId();
    }
    jsonOk(['id' => $id], 'บันทึกกลุ่มสินค้าแล้ว');
}

function h_admin_collection_delete(): void {
    requireAdmin();
    $id = (int)field('id');
    db()->prepare('DELETE FROM product_collection_items WHERE collection_id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    db()->prepare('DELETE FROM product_collections WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'ลบกลุ่มสินค้าแล้ว');
}

// สินค้าที่อยู่ในกลุ่มนี้แล้ว (แสดงในหน้าจัดการ พร้อมปุ่มลบออก)
function h_admin_collection_items(): void {
    requireAdmin();
    $cid = (int)($_GET['collection_id'] ?? 0);
    $stmt = db()->prepare(
        "SELECT i.id AS item_id, p.id, p.name, p.type, p.price, p.cover_image, p.is_active,
                (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id AND pc.status='available') AS stock
         FROM product_collection_items i
         JOIN products p ON p.id = i.product_id AND p.tenant_id = i.tenant_id
         WHERE i.collection_id = ? AND i.tenant_id = ?
         ORDER BY i.sort_order, i.id"
    );
    $stmt->execute([$cid, tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['price'] = (float)$r['price']; $r['stock'] = (int)$r['stock']; $r['is_active'] = (int)$r['is_active']; }
    jsonOk($rows);
}

// รายการสินค้าทั้งหมด (ทุกประเภท รวมสินค้าจาก API/ตัวแทน) สำหรับป็อบอัพเลือกเพิ่มเข้ากลุ่ม
// ธงว่ารายการไหนถูกเพิ่มในกลุ่มนี้ไปแล้ว เพื่อกันเพิ่มซ้ำในฝั่งหน้าบ้าน
function h_admin_collection_products_available(): void {
    requireAdmin();
    $cid = (int)($_GET['collection_id'] ?? 0);
    $stmt = db()->prepare(
        "SELECT p.id, p.name, p.type, p.price, p.cover_image, p.is_active, c.name AS category_name,
                (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id AND pc.status='available') AS stock,
                EXISTS(SELECT 1 FROM product_collection_items i WHERE i.collection_id = ? AND i.product_id = p.id AND i.tenant_id = p.tenant_id) AS in_collection
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
         WHERE p.tenant_id = ?
         ORDER BY p.sort_order, p.id DESC"
    );
    $stmt->execute([$cid, tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) { $r['price'] = (float)$r['price']; $r['stock'] = (int)$r['stock']; $r['is_active'] = (int)$r['is_active']; $r['in_collection'] = (int)$r['in_collection']; }
    jsonOk($rows);
}

function h_admin_collection_item_add(): void {
    requireAdmin();
    $cid = (int)field('collection_id');
    $pid = (int)field('product_id');
    if ($cid <= 0 || $pid <= 0) jsonError('ข้อมูลไม่ถูกต้อง');
    $stmt = db()->prepare('INSERT IGNORE INTO product_collection_items (tenant_id, collection_id, product_id, sort_order) VALUES (?,?,?,0)');
    $stmt->execute([tenantId(), $cid, $pid]);
    jsonOk([], 'เพิ่มสินค้าเข้ากลุ่มแล้ว');
}

function h_admin_collection_item_remove(): void {
    requireAdmin();
    $cid = (int)field('collection_id');
    $pid = (int)field('product_id');
    db()->prepare('DELETE FROM product_collection_items WHERE collection_id = ? AND product_id = ? AND tenant_id = ?')
        ->execute([$cid, $pid, tenantId()]);
    jsonOk([], 'นำสินค้าออกจากกลุ่มแล้ว');
}

/* ---------- คลังโค้ด/สต็อก ---------- */
function h_admin_codes(): void {
    requireAdmin();
    $pid = (int)($_GET['product_id'] ?? 0);
    $stmt = db()->prepare('SELECT id, content, status, order_id, created_at, sold_at FROM product_codes WHERE product_id = ? AND tenant_id = ? ORDER BY id DESC');
    $stmt->execute([$pid, tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_admin_codes_add(): void {
    requireAdmin();
    $pid  = (int)field('product_id');
    $bulk = (string)field('codes');
    if ($pid <= 0)   jsonError('ไม่พบสินค้า');
    if (trim($bulk) === '') jsonError('กรุณาใส่โค้ด (บรรทัดละ 1 โค้ด)');
    $product = db()->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ? AND type = 'code' LIMIT 1");
    $product->execute([$pid, tenantId()]);
    if (!$product->fetch()) jsonError('ไม่พบสินค้าในร้านนี้');

    $lines = preg_split('/\r\n|\r|\n/', $bulk);
    $stmt  = db()->prepare("INSERT INTO product_codes (tenant_id, product_id, content, status) VALUES (?, ?, ?, 'available')");
    $count = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $stmt->execute([tenantId(), $pid, $line]);
        $count++;
    }
    jsonOk(['added'=>$count], "เพิ่มโค้ดเข้าคลัง {$count} รายการ");
}

function h_admin_code_delete(): void {
    requireAdmin();
    $id = (int)field('id');
    db()->prepare("DELETE FROM product_codes WHERE id = ? AND tenant_id = ? AND status = 'available'")->execute([$id, tenantId()]);
    jsonOk([], 'ลบโค้ดแล้ว');
}

/* ---------- สมาชิก ---------- */
function h_admin_members(): void {
    requireAdmin();
    $stmt = db()->prepare('SELECT id, username, email, balance, role, is_active, created_at FROM users WHERE tenant_id = ? ORDER BY id DESC');
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['balance'] = (float)$r['balance'];
    jsonOk($rows);
}

function h_admin_member_adjust(): void {
    requireAdmin();
    $uid    = (int)field('user_id');
    $amount = (float)field('amount'); // เป็นบวก=เพิ่ม, ลบ=หัก
    if ($uid <= 0) jsonError('ไม่พบสมาชิก');

    $member = db()->prepare('SELECT id FROM users WHERE id = ? AND tenant_id = ? LIMIT 1');
    $member->execute([$uid, tenantId()]);
    if (!$member->fetch()) jsonError('ไม่พบสมาชิกในร้านนี้');
    db()->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')->execute([$amount, $uid, tenantId()]);
    $note = $amount >= 0 ? 'ปรับเพิ่มยอดโดยแอดมิน' : 'ปรับลดยอดโดยแอดมิน';
    db()->prepare('INSERT INTO topups (tenant_id, user_id, voucher_hash, amount, status, message) VALUES (?,?,?,?,?,?)')
        ->execute([tenantId(), $uid, null, $amount, 'success', $note]);
    jsonOk([], 'ปรับยอดเงินแล้ว');
}

function h_admin_member_toggle(): void {
    $admin = requireAdmin();
    $uid = (int)field('user_id');
    if ($uid <= 0) jsonError('ไม่พบสมาชิก');
    if ($uid === (int)$admin['id']) jsonError('ไม่สามารถระงับบัญชีตัวเองได้');

    $stmt = db()->prepare('SELECT role FROM users WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$uid, tenantId()]);
    $role = $stmt->fetchColumn();
    if ($role === false) jsonError('ไม่พบสมาชิก');
    if ($role === 'admin') jsonError('ไม่สามารถระงับบัญชีผู้ดูแลระบบได้');

    db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ? AND tenant_id = ?')->execute([$uid, tenantId()]);
    jsonOk([], 'อัปเดตสถานะสมาชิกแล้ว');
}

/* ---------- ประวัติ ---------- */
function h_admin_orders(): void {
    requireAdmin();
    $stmt = db()->prepare(
        "SELECT o.id, o.product_name, o.price, o.status, o.refill_uid, o.refill_server, o.api_order_id, o.created_at, u.username, COALESCE(p.type, 'code') AS product_type
         FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN products p ON p.id=o.product_id
         WHERE o.tenant_id = ?
         ORDER BY o.id DESC LIMIT 200");
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['price'] = (float)$r['price'];
    jsonOk($rows);
}

function h_admin_order_update_status(): void {
    requireAdmin();
    $orderId = (int)field('order_id');
    $status  = trim((string)field('status'));

    if ($orderId <= 0) jsonError('ไม่พบคำสั่งซื้อ');
    if (!in_array($status, ['pending', 'processing', 'completed', 'cancelled'])) {
        jsonError('สถานะไม่ถูกต้อง');
    }

    db()->prepare('UPDATE orders SET status = ? WHERE id = ? AND tenant_id = ?')->execute([$status, $orderId, tenantId()]);
    jsonOk([], 'อัปเดตสถานะคำสั่งซื้อสำเร็จ');
}

function h_admin_topups(): void {
    requireAdmin();
    $stmt = db()->prepare(
        "SELECT t.id, t.amount, t.status, t.message, t.created_at, u.username
         FROM topups t JOIN users u ON u.id=t.user_id
         WHERE t.tenant_id = ? ORDER BY t.id DESC LIMIT 200");
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) $r['amount'] = (float)$r['amount'];
    jsonOk($rows);
}

/* ---------- ตั้งค่า ---------- */
function h_admin_pin_status(): void {
    requireAdmin(false);
    jsonOk([
        'verified' => adminPinVerified(),
        'has_custom_pin' => setting('admin_pin_hash', '') !== '',
    ]);
}

function h_admin_pin_verify(): void {
    requireAdmin(false);
    if (!rateLimit('admin_pin:' . clientIp(), 8, 300)) {
        jsonError('กรอก PIN ผิดบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่', 429);
    }
    requireTurnstile();
    $pin = trim((string)field('pin'));
    if (!verifyAdminPinValue($pin)) jsonError('PIN ไม่ถูกต้อง', 401);
    markAdminPinVerified();
    jsonOk(['verified' => true], 'ยืนยัน PIN สำเร็จ');
}

function h_admin_pin_change(): void {
    requireAdmin();
    $current = trim((string)field('current_pin'));
    $new = trim((string)field('new_pin'));
    if (!verifyAdminPinValue($current)) jsonError('PIN ปัจจุบันไม่ถูกต้อง');
    if (!preg_match('/^\d{6}$/', $new)) jsonError('PIN ใหม่ต้องเป็นตัวเลข 6 หลัก');
    saveSetting('admin_pin_hash', password_hash($new, PASSWORD_BCRYPT));
    markAdminPinVerified();
    jsonOk([], 'เปลี่ยน PIN หลังบ้านเรียบร้อยแล้ว');
}

function h_admin_settings(): void {
    requireAdmin();
    jsonOk([
        'site_name'            => setting('site_name', ''),
        'site_tagline'         => setting('site_tagline', ''),
        'truemoney_phone'      => setting('truemoney_phone', ''),
        'contact_line'         => setting('contact_line', ''),
        'contact_facebook'     => setting('contact_facebook', ''),
        'contact_email'        => setting('contact_email', ''),
        'contact_phone'        => setting('contact_phone', ''),
        'sales_enabled'        => setting('sales_enabled', '1'),
        'code_sales_enabled'   => setting('code_sales_enabled', '1'),
        'refill_sales_enabled' => setting('refill_sales_enabled', '1'),
        'streaming_enabled'    => setting('streaming_enabled', '1'),
        'otp_enabled'          => setting('otp_enabled', '1'),
        'sms_otp_enabled'      => setting('sms_otp_enabled', '1'),
        'social_enabled'       => setting('social_enabled', '1'),
        'supplier_api_key'     => setting('supplier_api_key', ''),
        'meelike_api_key'      => setting('meelike_api_key', ''),
        'r2_account_id'        => setting('r2_account_id', ''),
        'r2_bucket_name'       => setting('r2_bucket_name', ''),
        'r2_access_key_id'     => setting('r2_access_key_id', ''),
        'r2_secret_access_key' => setting('r2_secret_access_key', ''),
        'r2_public_url'        => setting('r2_public_url', ''),
        'turnstile_site_key'   => setting('turnstile_site_key', ''),
        'turnstile_secret_key' => setting('turnstile_secret_key', ''),
        'site_announcement'    => setting('site_announcement', ''),
        'site_footer'          => setting('site_footer', '© 2026 GameStore. All Rights Reserved.'),
        'site_logo'            => setting('site_logo', ''),
        'site_favicon'         => setting('site_favicon', ''),
        'site_banner'          => setting('site_banner', ''),
        'color_primary'        => setting('color_primary', '#6366f1'),
        'color_primary_hover'  => setting('color_primary_hover', '#4f46e5'),
        'color_accent'         => setting('color_accent', '#06b6d4'),
        'color_bg'             => setting('color_bg', '#f5f6fa'),
        'color_bg_card'        => setting('color_bg_card', '#ffffff'),
        'border_radius'        => setting('border_radius', '18'),
        'nav_show_home'        => setting('nav_show_home', '1'),
        'nav_show_products'    => setting('nav_show_products', '1'),
        'nav_show_refills'     => setting('nav_show_refills', '1'),
        'nav_show_streaming'   => setting('nav_show_streaming', '1'),
        'nav_show_otp'         => setting('nav_show_otp', '1'),
        'nav_show_mail'        => setting('nav_show_mail', '1'),
        'nav_show_social'      => setting('nav_show_social', '1'),
        'nav_show_topup'       => setting('nav_show_topup', '1'),
        'nav_show_contact'     => setting('nav_show_contact', '1'),
        'quick_menu_1_img'     => setting('quick_menu_1_img', 'assets/images/menu-product.jpg'),
        'quick_menu_1_label'   => setting('quick_menu_1_label', 'สินค้าทั้งหมด'),
        'quick_menu_1_link'    => setting('quick_menu_1_link', '/products'),
        'quick_menu_2_img'     => setting('quick_menu_2_img', 'assets/images/menu-topup.jpg'),
        'quick_menu_2_label'   => setting('quick_menu_2_label', 'เติมเงินเข้าระบบ'),
        'quick_menu_2_link'    => setting('quick_menu_2_link', '/topup'),
        'quick_menu_3_img'     => setting('quick_menu_3_img', 'assets/images/menu-history.jpg'),
        'quick_menu_3_label'   => setting('quick_menu_3_label', 'ประวัติการซื้อ'),
        'quick_menu_3_link'    => setting('quick_menu_3_link', '/orders'),
        'quick_menu_4_img'     => setting('quick_menu_4_img', 'assets/images/menu-contact.jpg'),
        'quick_menu_4_label'   => setting('quick_menu_4_label', 'ติดต่อทีมงาน'),
        'quick_menu_4_link'    => setting('quick_menu_4_link', '/contact'),
    ]);
}

function h_admin_settings_save(): void {
    requireAdmin();
    $keys = ['site_name','site_tagline','truemoney_phone','contact_line',
             'contact_facebook','contact_email','contact_phone','sales_enabled',
             'code_sales_enabled','refill_sales_enabled','streaming_enabled','otp_enabled',
             'sms_otp_enabled','social_enabled','supplier_api_key','meelike_api_key',
             'r2_account_id','r2_bucket_name','r2_access_key_id','r2_secret_access_key','r2_public_url',
             'turnstile_site_key','turnstile_secret_key',
             'site_announcement','site_footer','site_logo','site_favicon','site_banner',
             'color_primary','color_primary_hover','color_accent','color_bg','color_bg_card','border_radius',
             'nav_show_home','nav_show_products','nav_show_refills','nav_show_streaming','nav_show_otp','nav_show_mail','nav_show_social','nav_show_topup','nav_show_contact',
             'quick_menu_1_img','quick_menu_1_label','quick_menu_1_link',
             'quick_menu_2_img','quick_menu_2_label','quick_menu_2_link',
             'quick_menu_3_img','quick_menu_3_label','quick_menu_3_link',
             'quick_menu_4_img','quick_menu_4_label','quick_menu_4_link'];
    foreach ($keys as $k) {
        $v = field($k);
        if ($v !== null) saveSetting($k, is_bool($v) ? ($v ? '1':'0') : $v);
    }
    jsonOk([], 'บันทึกการตั้งค่าแล้ว');
}

/* =============================================================
 *  Reseller Supplier API Endpoints
 * ============================================================= */

function h_order_otp(): void {
    $user = requireAuth();
    $orderId = (int)field('order_id');
    $type    = trim((string)field('type')); // Netflix: 4code|6code|household
    $phone   = trim((string)field('phone')); // Disney+
    $email   = trim((string)field('email')); // YouKu

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $stmt = db()->prepare('SELECT o.*, p.otp_service, p.otp_type FROM orders o LEFT JOIN products p ON p.id = o.product_id WHERE o.id = ? AND o.user_id = ?');
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    if (!$order) jsonError('ไม่พบประวัติการทำรายการนี้');
    
    $supplierOrderId = $order['api_order_id'];
    if (!$supplierOrderId) jsonError('ออเดอร์นี้ไม่ใช่สินค้าตัวแทนจำหน่ายคู่ค้า หรือไม่มีสิทธิ์ขอรับ OTP');

    $apiKey = setting('supplier_api_key', '');
    $prodName = mb_strtolower($order['product_name']);
    
    if (strpos($prodName, 'netflix') !== false || $type !== '') {
        $res = supplier_get_netflix_otp($apiKey, $supplierOrderId, $type ?: '6code');
    } else if (strpos($prodName, 'disney') !== false || $phone !== '') {
        if ($phone === '') jsonError('กรุณากรอกเบอร์โทรศัพท์สำหรับ Disney+');
        $res = supplier_get_disney_otp($apiKey, $phone);
    } else if (strpos($prodName, 'youku') !== false || $email !== '') {
        if ($email === '') jsonError('กรุณากรอกอีเมลสำหรับ YouKu');
        $res = supplier_get_youku_otp($email);
    } else {
        jsonError('ไม่สามารถวิเคราะห์ประเภทเพื่อขอ OTP สำหรับสินค้านี้ได้');
    }

    if (isset($res['status']) && ($res['status'] === 'ok' || $res['status'] === 'success')) {
        jsonOk($res);
    } else {
        jsonError($res['msg'] ?? $res['message'] ?? 'ไม่พบรหัส OTP ในขณะนี้ กรุณาร้องขออีกครั้งภายหลัง');
    }
}

// สำหรับสินค้าประเภท 'otp' — ลูกค้าซื้อบริการแล้วกดปุ่มเพื่อขอ OTP พร้อม input ที่ต้องการ
// Netflix: order_id + type (4code|6code|household)
// Disney+: phone number
// YouKu: email address
function h_order_otp_request(): void {
    $user = requireAuth();
    $orderId    = (int)field('order_id');
    $otpService = trim((string)field('otp_service')); // netflix|disney|youku
    $otpType    = trim((string)field('otp_type')); // Netflix: 4code|6code|household
    $phone      = trim((string)field('phone')); // Disney+
    $email      = trim((string)field('email')); // YouKu
    $netflixOid = trim((string)field('netflix_order_id')); // Netflix order_id

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    // ยืนยันว่าเป็นออเดอร์ของลูกค้า และเป็นประเภท otp
    $stmt = db()->prepare('SELECT o.*, p.otp_service, p.otp_type FROM orders o LEFT JOIN products p ON p.id = o.product_id WHERE o.id = ? AND o.user_id = ? AND o.tenant_id = ?');
    $stmt->execute([$orderId, $user['id'], tenantId()]);
    $order = $stmt->fetch();
    if (!$order) jsonError('ไม่พบออเดอร์นี้');

    $apiKey = setting('supplier_api_key', '');
    if (!$apiKey) jsonError('ระบบเชื่อมต่อคู่ค้ายังไม่ได้รับการตั้งค่า');

    // ใช้ otp_service จาก DB เป็นหลัก (ถ้าไม่มี ใช้ field ที่ส่งมา)
    $service = $order['otp_service'] ?: $otpService;
    $svcType = $order['otp_type'] ?: $otpType;

    if ($service === 'netflix') {
        if ($netflixOid === '') jsonError('กรุณาระบุ Order ID Netflix ของคุณ');
        $res = supplier_get_netflix_otp($apiKey, $netflixOid, $svcType ?: '6code');
    } else if ($service === 'disney') {
        if ($phone === '') jsonError('กรุณาระบุเบอร์โทรที่ผูกกับ Disney+');
        $res = supplier_get_disney_otp($apiKey, $phone);
    } else if ($service === 'youku') {
        if ($email === '') jsonError('กรุณาระบุอีเมล YouKu ของคุณ');
        $res = supplier_get_youku_otp($email);
    } else {
        jsonError('ประเภทบริการ OTP ไม่ยังรองรับ (รองรับ: netflix, disney, youku)');
    }

    if (isset($res['status']) && ($res['status'] === 'ok' || $res['status'] === 'success')) {
        jsonOk(['otp' => $res['otp'] ?? $res['code'] ?? '', 'service' => $service, 'raw' => $res]);
    } else {
        jsonError($res['msg'] ?? $res['message'] ?? 'ไม่พบรหัส OTP กรุณาลองอีกครั้ง');
    }
}


function h_order_claim(): void {
    $user = requireAuth();
    $orderId = (int)field('order_id');
    $reason  = trim((string)field('reason'));

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');
    if ($reason === '') jsonError('กรุณากรอกสาเหตุการส่งเคลม');

    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    if (!$order) jsonError('ไม่พบประวัติการทำรายการนี้');

    $supplierOrderId = $order['api_order_id'];
    if (!$supplierOrderId) jsonError('ออเดอร์นี้ไม่ใช่สินค้าคู่ค้า ไม่สามารถส่งเคลมได้');

    $apiKey = setting('supplier_api_key', '');
    $res = supplier_submit_claim($apiKey, $supplierOrderId, $reason);

    if (isset($res['status']) && $res['status'] === 'success') {
        jsonOk($res, 'ส่งเคลมสำเร็จแล้ว');
    } else {
        jsonError($res['msg'] ?? $res['message'] ?? 'ส่งเคลมล้มเหลว');
    }
}

function h_order_claim_status(): void {
    $user = requireAuth();
    $orderId = (int)($_GET['order_id'] ?? field('order_id'));

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $stmt->execute([$orderId, $user['id']]);
    $order = $stmt->fetch();
    if (!$order) jsonError('ไม่พบประวัติการทำรายการนี้');

    $supplierOrderId = $order['api_order_id'];
    if (!$supplierOrderId) jsonError('ออเดอร์นี้ไม่ใช่สินค้าคู่ค้า');

    $apiKey = setting('supplier_api_key', '');
    $res = supplier_check_claim($apiKey, $supplierOrderId);

    if (isset($res['status']) && $res['status'] === 'success') {
        $claimData = $res['data'] ?? [];
        if (isset($claimData['claim_status']) && $claimData['claim_status'] === '3' && isset($claimData['details'])) {
            $newDetails = $claimData['details'];
            db()->prepare('UPDATE orders SET code_content = ? WHERE id = ?')->execute([$newDetails, $orderId]);
        }
        jsonOk($res);
    } else {
        jsonError($res['msg'] ?? $res['message'] ?? 'ไม่สามารถตรวจสอบสถานะได้');
    }
}

function h_admin_supplier_status(): void {
    requireAdmin();
    $apiKey = setting('supplier_api_key', '');
    if (!$apiKey) jsonError('ยังไม่ได้ระบุ Supplier API Key');

    $balanceRes = supplier_get_balance($apiKey);
    $stockRes   = supplier_get_stock_updates(50, 0);

    jsonOk([
        'balance' => $balanceRes,
        'stock_updates' => $stockRes
    ]);
}

function h_admin_supplier_products(): void {
    requireAdmin();
    $res = supplier_get_products();
    if (isset($res['ok']) && $res['ok'] === true) {
        jsonOk($res['data'] ?? []);
    } else {
        jsonError($res['msg'] ?? 'ดึงข้อมูลไม่สำเร็จ');
    }
}

function h_admin_supplier_import(): void {
    requireAdmin();
    $typeId   = trim((string)field('type_id'));
    $name     = trim((string)field('name'));
    $price    = (float)field('price');
    $details  = trim((string)field('details'));
    $image    = trim((string)field('imageapi'));
    $category = (int)field('category_id');

    if ($typeId === '') jsonError('ไม่พบรหัสสินค้าอ้างอิงคู่ค้า');
    if ($name === '') jsonError('กรุณากรอกชื่อสินค้า');

    $db = db();

    // ป้องกันนำเข้าซ้ำ: ถ้าเคยนำเข้าสินค้ารหัสนี้ไปแล้ว ให้ข้ามแทนการสร้างซ้ำ
    $existsStmt = $db->prepare('SELECT id FROM products WHERE tenant_id = ? AND api_type_id = ? LIMIT 1');
    $existsStmt->execute([tenantId(), $typeId]);
    if ($existsStmt->fetch()) {
        jsonOk(['skipped' => true], 'มีสินค้านี้ในระบบอยู่แล้ว ข้ามการนำเข้า');
        return;
    }

    $slug = slugify($name) . '-' . rand(100, 999);

    $stmt = $db->prepare('INSERT INTO products (tenant_id, category_id, type, api_type_id, name, slug, description, price, cover_image, is_active) VALUES (?,?,?,?,?,?,?,?,?,1)');
    $stmt->execute([tenantId(), $category ?: null, 'streaming', $typeId, $name, $slug, $details, $price, $image]);

    jsonOk(['skipped' => false], 'นำเข้าสินค้า Streaming จากคู่ค้าสำเร็จ');
}

function h_admin_meelike_status(): void {
    requireAdmin();
    $apiKey = setting('meelike_api_key', '');
    if (!$apiKey) jsonOk(['ok' => false, 'error' => 'ยังไม่ได้ตั้งค่า MeeLike API Key']);
    jsonOk(['balance' => meelike_get_balance($apiKey)]);
}

function h_admin_meelike_services(): void {
    requireAdmin();
    $apiKey = setting('meelike_api_key', '');
    if (!$apiKey) jsonError('ยังไม่ได้ตั้งค่า MeeLike API Key');
    $res = meelike_get_services($apiKey);
    if (isset($res['error'])) jsonError((string)$res['error']);
    if (isset($res['data']) && is_array($res['data'])) $res = $res['data'];
    if (isset($res['services']) && is_array($res['services'])) $res = $res['services'];
    jsonOk(is_array($res) ? array_values($res) : []);
}

function meelike_platform_name(string $name, string $category = ''): string {
    $text = strtolower($name . ' ' . $category);
    $groups = [
        'Facebook'  => ['facebook', 'fb', 'เฟส', 'เฟสบุ๊ค', 'เฟซบุ๊ก', 'page', 'reels'],
        'Instagram' => ['instagram', 'ig', 'อินสตาแกรม', 'ไอจี'],
        'TikTok'    => ['tiktok', 'tik tok', 'ติ๊กต็อก'],
        'YouTube'   => ['youtube', 'yt', 'ยูทูป', 'ยูทูป'],
        'LINE'      => ['line', 'ไลน์'],
        'Twitter / X' => ['twitter', ' x ', 'x.com', 'ทวิต', 'ทวิตเตอร์'],
        'Telegram'  => ['telegram', 'เทเลแกรม'],
        'Shopee'    => ['shopee', 'ช้อปปี้'],
        'Lazada'    => ['lazada', 'ลาซาด้า'],
    ];
    foreach ($groups as $platform => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) return $platform;
        }
    }
    return 'Social Other';
}

function meelike_platform_image(string $platform): string {
    $images = [
        'Facebook'    => 'https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=800&q=80',
        'Instagram'   => 'https://images.unsplash.com/photo-1611224885990-ab7363d1f2a9?w=800&q=80',
        'TikTok'      => 'https://images.unsplash.com/photo-1598128558393-70ff21433be0?w=800&q=80',
        'YouTube'     => 'https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?w=800&q=80',
        'LINE'        => 'https://images.unsplash.com/photo-1579869847557-1f67382cc158?w=800&q=80',
        'Twitter / X' => 'https://images.unsplash.com/photo-1611605698335-8b15d27e03f9?w=800&q=80',
        'Telegram'    => 'https://images.unsplash.com/photo-1562157873-818bc0726f68?w=800&q=80',
        'Shopee'      => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=800&q=80',
        'Lazada'      => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=800&q=80',
        'Social Other'=> 'https://images.unsplash.com/photo-1611162618071-b39a2ec055fb?w=800&q=80',
    ];
    return $images[$platform] ?? $images['Social Other'];
}

function unique_product_slug(string $name, int $excludeId = 0): string {
    $base = slugify($name);
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM products WHERE tenant_id = ? AND slug = ? AND id <> ? LIMIT 1');
        $stmt->execute([tenantId(), $slug, $excludeId]);
        if (!$stmt->fetchColumn()) return $slug;
        $slug = $base . '-' . $i;
        $i++;
    }
}

function ensure_meelike_category(string $platform): int {
    $stmt = db()->prepare('SELECT id FROM categories WHERE tenant_id = ? AND name = ? LIMIT 1');
    $stmt->execute([tenantId(), $platform]);
    $id = (int)($stmt->fetchColumn() ?: 0);
    if ($id > 0) return $id;

    db()->prepare('INSERT INTO categories (tenant_id, name, slug, sort_order) VALUES (?, ?, ?, ?)')
        ->execute([tenantId(), $platform, slugify('social-' . $platform), 50]);
    return (int)db()->lastInsertId();
}

function h_admin_meelike_import(): void {
    requireAdmin();
    $serviceId = trim((string)field('service'));
    $name = trim((string)field('name'));
    $category = (int)field('category_id') ?: null;
    $price = (float)field('price');
    $description = trim((string)field('description'));
    $serviceCategory = trim((string)field('service_category'));

    if ($serviceId === '') jsonError('กรุณาระบุ Service ID');
    if ($name === '') jsonError('กรุณาระบุชื่อบริการ');
    if ($price <= 0) jsonError('กรุณาระบุราคาขายต่อ 1,000 หน่วย');

    $platform = meelike_platform_name($name, $serviceCategory);
    if (!$category) {
        $category = ensure_meelike_category($platform);
    }
    $coverImage = meelike_platform_image($platform);

    $existing = db()->prepare("SELECT id, cover_image FROM products WHERE tenant_id = ? AND type = 'social' AND api_type_id = ? LIMIT 1");
    $existing->execute([tenantId(), $serviceId]);
    $existingRow = $existing->fetch();
    $existingId = $existingRow ? (int)$existingRow['id'] : 0;

    if ($existingId > 0) {
        $currentCover = trim((string)($existingRow['cover_image'] ?? ''));
        if ($currentCover === '') {
            db()->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, cover_image=?, is_active=1 WHERE id=? AND tenant_id=?')
                ->execute([$category, $name, $description, $price, $coverImage, $existingId, tenantId()]);
        } else {
            db()->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, is_active=1 WHERE id=? AND tenant_id=?')
                ->execute([$category, $name, $description, $price, $existingId, tenantId()]);
        }
        jsonOk(['id' => $existingId, 'updated' => true], 'อัปเดตบริการ MeeLike สำเร็จ');
    }

    $slug = unique_product_slug($name);
    $stmt = db()->prepare('INSERT INTO products (tenant_id, category_id, type, api_type_id, name, slug, description, price, cover_image, is_active) VALUES (?,?,?,?,?,?,?,?,?,1)');
    $stmt->execute([tenantId(), $category, 'social', $serviceId, $name, $slug, $description, $price, $coverImage]);
    jsonOk(['id' => (int)db()->lastInsertId()], 'นำเข้าบริการ MeeLike สำเร็จ');
}

function h_admin_meelike_order_status(): void {
    requireAdmin();
    $orderId = (int)($_GET['order_id'] ?? field('order_id'));
    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $stmt = db()->prepare("SELECT o.* FROM orders o LEFT JOIN products p ON p.id=o.product_id WHERE o.id=? AND o.tenant_id=? AND p.type='social' LIMIT 1");
    $stmt->execute([$orderId, tenantId()]);
    $order = $stmt->fetch();
    if (!$order) jsonError('ไม่พบออเดอร์ MeeLike นี้');
    if (empty($order['api_order_id'])) jsonError('ออเดอร์นี้ยังไม่มีเลขอ้างอิง MeeLike');

    $apiKey = setting('meelike_api_key', '');
    if (!$apiKey) jsonError('ยังไม่ได้ตั้งค่า MeeLike API Key');
    $res = meelike_order_status($apiKey, (string)$order['api_order_id']);

    if (!isset($res['error'])) {
        $statusText = (string)($res['status'] ?? $order['status']);
        $mapped = in_array(strtolower($statusText), ['completed', 'complete']) ? 'completed' : (in_array(strtolower($statusText), ['canceled', 'cancelled', 'partial']) ? 'cancelled' : 'processing');
        $details = "MeeLike Status: " . $statusText;
        if (isset($res['start_count'])) $details .= "\nStart: " . $res['start_count'];
        if (isset($res['remains'])) $details .= "\nRemains: " . $res['remains'];
        if (isset($res['charge'])) $details .= "\nCharge: " . $res['charge'];
        db()->prepare('UPDATE orders SET status=?, code_content=? WHERE id=? AND tenant_id=?')
            ->execute([$mapped, $details, $orderId, tenantId()]);
    }
    jsonOk($res);
}

/* =============================================================
 *  SMS OTP API Controller Endpoints (otp.md)
 * ============================================================= */

// 1. ดึงรายการสินค้า OTP ทั้งหมด
function h_sms_products(): void {
    $res = supplier_get_sms_products();
    if (is_array($res)) {
        $globalMarkup = (float)setting('sms_otp_markup', '2.00');
        $overridesJson = setting('sms_otp_markup_overrides', '{}');
        $overrides = json_decode($overridesJson, true) ?: [];
        
        $mapped = [];
        foreach ($res as $item) {
            if (isset($item['product'])) {
                $name = $item['product'];
                $supplierPrice = (float)($item['point'] ?? 0);
                $markup = isset($overrides[$name]) ? (float)$overrides[$name] : $globalMarkup;
                $finalPrice = $supplierPrice + $markup;
                
                $mapped[] = [
                    'id' => $item['id'] ?? 0,
                    'name' => $name,
                    'price' => $item['point'] ?? '0.00',
                    'markup' => $markup,
                    'final_price' => number_format($finalPrice, 2, '.', ''),
                    'location' => $item['location'] ?? '',
                    'stock' => $item['stock'] ?? 0
                ];
            }
        }
        jsonOk($mapped);
    } else {
        jsonError('ไม่สามารถดึงข้อมูลรายการสินค้า OTP ได้');
    }
}

// 2. ซื้อเบอร์รับ OTP
function h_sms_buy(): void {
    $user = requireAuth();
    $productName = trim((string)field('product'));
    $location    = trim((string)field('location'));

    if ($productName === '') jsonError('กรุณาระบุชื่อบริการ (เช่น Facebook, Line)');
    if ($location === '') jsonError('กรุณาระบุประเทศ (เช่น Thailand)');

    // 1. โหลดข้อมูลสินค้าเพื่อหาตรวจสอบราคาคู่ค้า
    $catalog = supplier_get_sms_products();
    if (!is_array($catalog)) {
        jsonError('ไม่สามารถดึงข้อมูลราคาสินค้าจากคู่ค้าได้');
    }

    $smsProduct = null;
    foreach ($catalog as $p) {
        if (isset($p['product']) && strcasecmp($p['product'], $productName) === 0 && isset($p['location']) && strcasecmp($p['location'], $location) === 0) {
            $smsProduct = $p;
            break;
        }
    }

    if (!$smsProduct) {
        jsonError('ขออภัย บริการนี้หรือประเทศนี้ไม่มีสินค้าหรือสินค้าหมดชั่วคราว');
    }

    // คิดราคาตามมาร์คอัปที่ตั้งค่าไว้
    $globalMarkup = (float)setting('sms_otp_markup', '2.00');
    $overridesJson = setting('sms_otp_markup_overrides', '{}');
    $overrides = json_decode($overridesJson, true) ?: [];
    
    $supplierPrice = (float)($smsProduct['point'] ?? 0);
    $markup = isset($overrides[$productName]) ? (float)$overrides[$productName] : $globalMarkup;
    $finalPrice = $supplierPrice + $markup;

    $apiKey = setting('supplier_api_key', '');
    if (!$apiKey) jsonError('ระบบเชื่อมต่อคู่ค้ายังไม่ได้รับการตั้งค่า API Key');

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // ล็อกผู้ใช้เพื่อเช็คยอดเงิน
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
        $stmt->execute([$user['id'], tenantId()]);
        $balance = (float)$stmt->fetchColumn();

        if ($balance < $finalPrice) {
            $pdo->rollBack();
            jsonError('ยอดเงินของคุณไม่เพียงพอสำหรับการซื้อเบอร์ OTP นี้ (ราคา ' . $finalPrice . ' บาท)');
        }

        // หักเงิน
        $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND tenant_id = ?')
            ->execute([$finalPrice, $user['id'], tenantId()]);

        // สั่งซื้อเบอร์รับ OTP จากคู่ค้า
        $apiRes = supplier_buy_sms_number($apiKey, $productName, $location);

        if (isset($apiRes['status']) && $apiRes['status'] === 'success') {
            $partnerOrderId = $apiRes['order'];
            
            // สร้างออเดอร์ในระบบ
            $productTitle = "บริการรับ OTP: " . $productName . " (" . $location . ")";
            $stmt = $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_name, price, status, api_order_id, code_content) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                tenantId(),
                $user['id'],
                $productTitle,
                $finalPrice,
                'processing', // กำลังรอ OTP
                $partnerOrderId,
                'สั่งซื้อเบอร์เรียบร้อยแล้ว กำลังรอรับ OTP...'
            ]);
            $localOrderId = $pdo->lastInsertId();

            $pdo->commit();
            
            jsonOk([
                'order_id' => $localOrderId,
                'partner_order' => $partnerOrderId,
                'price' => $finalPrice,
                'balance' => $balance - $finalPrice
            ], 'สั่งซื้อเบอร์รับ OTP สำเร็จ');
        } else {
            $pdo->rollBack();
            jsonError($apiRes['msg'] ?? 'คู่ค้าปฏิเสธรายการสั่งซื้อเบอร์');
        }
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonError('ทำรายการไม่สำเร็จ', 500, APP_DEBUG ? $e->getMessage() : null);
    }
}

// 3. ตรวจสอบสถานะ SMS OTP
function h_sms_status(): void {
    $user = requireAuth();
    $orderId = (int)($_GET['order_id'] ?? field('order_id'));

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? AND tenant_id = ?');
    $stmt->execute([$orderId, $user['id'], tenantId()]);
    $order = $stmt->fetch();

    if (!$order) jsonError('ไม่พบข้อมูลคำสั่งซื้อนี้');
    if (!$order['api_order_id']) jsonError('ออเดอร์นี้ไม่ใช่สินค้าเชื่อมต่อระบบรับ SMS');

    // เรียกดึงสถานะจากคู่ค้า
    $res = supplier_check_sms_status($order['api_order_id']);

    if (isset($res['status']) && $res['status'] === 'success') {
        $statusApi = $res['status_api'] ?? '';
        $smsText = $res['sms'] ?? '';
        $statusSms = $res['statussms'] ?? '';

        $pdo->beginTransaction();
        try {
            if ($statusApi === 'STATUS_OK') {
                // ได้รหัส OTP สำเร็จ
                $pdo->prepare('UPDATE orders SET status = ?, code_content = ? WHERE id = ?')
                    ->execute(['completed', $smsText ?: 'ได้รับ OTP สำเร็จ', $orderId]);
            } else if ($statusApi === 'STATUS_CANCEL' || $statusApi === 'STATUS_EXPIRED') {
                // ถูกยกเลิก / หมดอายุ -> คืนพอยต์ให้ลูกค้า
                if ($order['status'] !== 'cancelled') {
                    $pdo->prepare('UPDATE orders SET status = ?, code_content = ? WHERE id = ?')
                        ->execute(['cancelled', 'ระบบยกเลิกและคืนยอดเงินเรียบร้อยแล้ว (' . $statusSms . ')', $orderId]);
                    $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')
                        ->execute([(float)$order['price'], $user['id'], tenantId()]);
                }
            } else {
                // กำลังดึงหรือรอ OTP -> อัปเดตข้อความในออเดอร์
                $pdo->prepare('UPDATE orders SET code_content = ? WHERE id = ?')
                    ->execute(['สถานะ: ' . $statusSms . "\n" . ($smsText ?: 'กำลังดึงข้อความ OTP ....'), $orderId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
        }

        // คืนสถานะปัจจุบัน
        jsonOk([
            'status' => $res['status'],
            'statussms' => $statusSms,
            'sms' => $smsText,
            'status_api' => $statusApi
        ]);
    } else {
        jsonError($res['msg'] ?? 'ไม่สามารถตรวจสอบสถานะได้ในขณะนี้');
    }
}

// 4. ยกเลิกเบอร์
function h_sms_cancel(): void {
    $user = requireAuth();
    $orderId = (int)field('order_id');

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? AND tenant_id = ?');
    $stmt->execute([$orderId, $user['id'], tenantId()]);
    $order = $stmt->fetch();

    if (!$order) jsonError('ไม่พบข้อมูลคำสั่งซื้อนี้');
    if (!$order['api_order_id']) jsonError('ออเดอร์นี้ไม่ใช่สินค้าเชื่อมต่อระบบรับ SMS');
    if ($order['status'] === 'completed') jsonError('ออเดอร์นี้ทำรายการสำเร็จและได้รับ OTP ไปแล้ว ไม่สามารถยกเลิกได้');
    if ($order['status'] === 'cancelled') jsonError('ออเดอร์นี้ถูกยกเลิกไปแล้ว');

    $apiKey = setting('supplier_api_key', '');
    if (!$apiKey) jsonError('ระบบเชื่อมต่อคู่ค้ายังไม่ได้รับการตั้งค่า API Key');

    // ส่งคำขอยกเลิกเบอร์ไปยังคู่ค้า
    $res = supplier_cancel_sms_number($apiKey, $order['api_order_id']);

    if (isset($res['status']) && $res['status'] === 'success') {
        $pdo->beginTransaction();
        try {
            // อัปเดตสถานะออเดอร์
            $pdo->prepare('UPDATE orders SET status = ?, code_content = ? WHERE id = ?')
                ->execute(['cancelled', 'ยกเลิกออเดอร์และคืนเงินเรียบร้อยแล้ว', $orderId]);
            // คืนเงิน
            $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND tenant_id = ?')
                ->execute([(float)$order['price'], $user['id'], tenantId()]);
            $pdo->commit();
            jsonOk([], 'ยกเลิกออเดอร์และคืนเงินเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonError('ยกเลิกสำเร็จที่คู่ค้า แต่ระบบบันทึกฐานข้อมูลล้มเหลว กรุณาแจ้งแอดมิน');
        }
    } else {
        jsonError($res['msg'] ?? 'ไม่สามารถยกเลิกออเดอร์ได้ในขณะนี้');
    }
}

// 5. ซื้อเบอร์เดิม (ขอ OTP ใหม่)
function h_sms_buyagain(): void {
    $user = requireAuth();
    $orderId = (int)field('order_id');

    if ($orderId <= 0) jsonError('รหัสออเดอร์ไม่ถูกต้อง');

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? AND tenant_id = ?');
    $stmt->execute([$orderId, $user['id'], tenantId()]);
    $order = $stmt->fetch();

    if (!$order) jsonError('ไม่พบข้อมูลคำสั่งซื้อนี้');
    if (!$order['api_order_id']) jsonError('ออเดอร์นี้ไม่ใช่สินค้าเชื่อมต่อระบบรับ SMS');

    // คิดราคา 50% ของออเดอร์เดิมตามคู่มือ
    $retryPrice = (float)$order['price'] * 0.5;

    $apiKey = setting('supplier_api_key', '');
    if (!$apiKey) jsonError('ระบบเชื่อมต่อคู่ค้ายังไม่ได้รับการตั้งค่า API Key');

    $pdo->beginTransaction();
    try {
        // ล็อกผู้ใช้เพื่อเช็คยอดเงิน
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
        $stmt->execute([$user['id'], tenantId()]);
        $balance = (float)$stmt->fetchColumn();

        if ($balance < $retryPrice) {
            $pdo->rollBack();
            jsonError('ยอดเงินของคุณไม่เพียงพอสำหรับการซื้อเบอร์เดิมใหม่ (ราคา ' . $retryPrice . ' บาท)');
        }

        // หักเงิน
        $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND tenant_id = ?')
            ->execute([$retryPrice, $user['id'], tenantId()]);

        // ส่งคำสั่งซื้อเบอร์เดิมใหม่
        $res = supplier_buyagain_sms_number($apiKey, $order['api_order_id']);

        if (isset($res['status']) && $res['status'] === 'success') {
            $newPartnerOrderId = $res['order'];
            
            // สร้างออเดอร์ใหม่ในระบบ
            $productTitle = "ขอ OTP ใหม่เบอร์เดิม: " . $order['product_name'];
            $stmt = $pdo->prepare('INSERT INTO orders (tenant_id, user_id, product_name, price, status, api_order_id, code_content) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                tenantId(),
                $user['id'],
                $productTitle,
                $retryPrice,
                'processing',
                $newPartnerOrderId,
                'สั่งซื้อเบอร์เดิมเรียบร้อยแล้ว กำลังรอรับ OTP...'
            ]);
            $newLocalOrderId = $pdo->lastInsertId();

            $pdo->commit();
            jsonOk([
                'order_id' => $newLocalOrderId,
                'partner_order' => $newPartnerOrderId,
                'price' => $retryPrice,
                'balance' => $balance - $retryPrice
            ], 'ซื้อเบอร์เดิม (ขอ OTP ใหม่) สำเร็จ');
        } else {
            $pdo->rollBack();
            jsonError($res['msg'] ?? 'ไม่สามารถซื้อเบอร์เดิมใหม่ได้');
        }
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonError('ทำรายการไม่สำเร็จ', 500, APP_DEBUG ? $e->getMessage() : null);
    }
}

// 6. บันทึกราคา/กำไร SMS OTP
function h_admin_sms_save_markup(): void {
    requireAdmin();
    $globalMarkup = field('sms_otp_markup');
    $overrides = field('overrides');
    
    if ($globalMarkup !== null) {
        saveSetting('sms_otp_markup', (string)$globalMarkup);
    }
    if ($overrides !== null) {
        saveSetting('sms_otp_markup_overrides', json_encode($overrides));
    }
    jsonOk([], 'บันทึกราคา/กำไร SMS OTP สำเร็จ');
}

/* =============================================================
 *  คลังรูปภาพและสื่อ (Media Gallery) ต่อ Cloudflare R2 / Local
 * ============================================================= */

function check_media_table(): void {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS `media` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `tenant_id` INT UNSIGNED NOT NULL DEFAULT 1,
        `filename` VARCHAR(190) NOT NULL,
        `file_url` VARCHAR(500) NOT NULL,
        `file_type` VARCHAR(50) NOT NULL,
        `file_size` INT UNSIGNED NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_media_tenant` (`tenant_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}

function h_admin_media_list(): void {
    requireAdmin();
    check_media_table();
    $stmt = db()->prepare('SELECT * FROM media WHERE tenant_id = ? ORDER BY id DESC');
    $stmt->execute([tenantId()]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['id'] = (int)$r['id'];
        $r['file_size'] = (int)$r['file_size'];
    }
    jsonOk($rows);
}

function h_admin_media_upload(): void {
    requireAdmin();
    check_media_table();
    
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonError('กรุณาเลือกไฟล์ที่ต้องการอัปโหลด หรือเกิดข้อผิดพลาดในการรับไฟล์');
    }
    
    $file = $_FILES['file'];
    $originalName = $file['name'];
    $tmpPath = $file['tmp_name'];
    $size = $file['size'];
    $type = $file['type'];
    
    // จำกัดประเภทไฟล์รูปภาพ วิดีโอ และ GIF
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'video/mp4', 'video/webm', 'video/quicktime'];
    if (!in_array($type, $allowedTypes)) {
        jsonError('ประเภทไฟล์ไม่ได้รับอนุญาต (รองรับเฉพาะ รูปภาพ, GIF, และ วิดีโอ MP4/WebM)');
    }
    
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '', pathinfo($originalName, PATHINFO_FILENAME));
    $uniqueName = 't' . tenantId() . '_' . time() . '_' . uniqid() . '.' . ($ext ?: 'bin');
    
    $fileData = file_get_contents($tmpPath);
    if ($fileData === false) {
        jsonError('ไม่สามารถอ่านไฟล์ได้');
    }
    
    // โหลดการตั้งค่า Cloudflare R2
    $accountId = setting('r2_account_id', '');
    $bucket = setting('r2_bucket_name', '');
    $accessKey = setting('r2_access_key_id', '');
    $secretKey = setting('r2_secret_access_key', '');
    $publicDomain = setting('r2_public_url', '');
    
    $fileUrl = '';
    $uploadToR2 = (!empty($accountId) && !empty($bucket) && !empty($accessKey) && !empty($secretKey));
    
    if ($uploadToR2) {
        // อัปโหลดขึ้น Cloudflare R2
        $r2Url = r2_s3_upload($bucket, $accountId, $accessKey, $secretKey, $uniqueName, $fileData, $type, $publicDomain);
        if ($r2Url) {
            $fileUrl = $r2Url;
        } else {
            jsonError('อัปโหลดไฟล์ขึ้น Cloudflare R2 ล้มเหลว กรุณาตรวจสอบข้อมูลการตั้งค่า API');
        }
    } else {
        // อัปโหลดแบบ Local Fallback
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $destPath = $uploadDir . '/' . $uniqueName;
        if (move_uploaded_file($tmpPath, $destPath)) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
            $fileUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $uniqueName;
        } else {
            jsonError('อัปโหลดไฟล์เข้าระบบเซิร์ฟเวอร์หลัก (Local) ล้มเหลว');
        }
    }
    
    // บันทึกเข้าตารางฐานข้อมูล
    $stmt = db()->prepare('INSERT INTO media (tenant_id, filename, file_url, file_type, file_size) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([tenantId(), $originalName, $fileUrl, $type, $size]);
    $newId = db()->lastInsertId();
    
    jsonOk([
        'id' => (int)$newId,
        'filename' => $originalName,
        'file_url' => $fileUrl,
        'file_type' => $type,
        'file_size' => $size,
        'storage' => $uploadToR2 ? 'cloudflare_r2' : 'local'
    ], 'อัปเดตไฟล์สื่อเข้าระบบสำเร็จ');
}

function h_admin_media_delete(): void {
    requireAdmin();
    $id = (int)field('id');
    if ($id <= 0) jsonError('รหัสสื่อไม่ถูกต้อง');
    
    $stmt = db()->prepare('SELECT * FROM media WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$id, tenantId()]);
    $media = $stmt->fetch();
    if (!$media) jsonError('ไม่พบข้อมูลไฟล์สื่อที่ต้องการลบ');
    
    $fileUrl = $media['file_url'];
    
    // โหลดการตั้งค่า Cloudflare R2
    $accountId = setting('r2_account_id', '');
    $bucket = setting('r2_bucket_name', '');
    $accessKey = setting('r2_access_key_id', '');
    $secretKey = setting('r2_secret_access_key', '');
    
    $deletedStorage = false;
    $uploadToR2 = (!empty($accountId) && !empty($bucket) && !empty($accessKey) && !empty($secretKey));
    
    if ($uploadToR2 && strpos($fileUrl, 'cloudflarestorage.com') !== false || ($uploadToR2 && !empty(setting('r2_public_url', '')) && strpos($fileUrl, setting('r2_public_url', '')) !== false)) {
        // ลบจาก R2
        $fileName = basename(parse_url($fileUrl, PHP_URL_PATH));
        r2_s3_delete($bucket, $accountId, $accessKey, $secretKey, $fileName);
        $deletedStorage = true;
    } else {
        // ลบจาก Local
        $fileName = basename($fileUrl);
        $localPath = __DIR__ . '/../uploads/' . $fileName;
        if (file_exists($localPath)) {
            unlink($localPath);
            $deletedStorage = true;
        }
    }
    
    // ลบจากฐานข้อมูล
    $stmt = db()->prepare('DELETE FROM media WHERE id = ? AND tenant_id = ?');
    $stmt->execute([$id, tenantId()]);
    
    jsonOk([], 'ลบไฟล์ออกจากคลังสื่อเรียบร้อยแล้ว');
}

/* =============================================================
 *  Cloudflare R2 (S3-Compatible) Signature Version 4 Helpers
 * ============================================================= */

function r2_s3_upload(string $bucket, string $accountId, string $accessKey, string $secretKey, string $fileName, string $fileData, string $contentType, string $customDomain = ''): string|bool {
    $host = "$accountId.r2.cloudflarestorage.com";
    $service = "s3";
    $region = "auto";
    $method = "PUT";
    $timestamp = time();
    $amzDate = gmdate('Ymd\THis\Z', $timestamp);
    $date = gmdate('Ymd', $timestamp);
    
    $uri = '/' . $bucket . '/' . ltrim($fileName, '/');
    $contentHash = hash('sha256', $fileData);
    
    $headers = [
        'host' => $host,
        'x-amz-content-sha256' => $contentHash,
        'x-amz-date' => $amzDate,
        'content-type' => $contentType,
    ];
    ksort($headers);
    
    $canonicalHeaders = '';
    $signedHeaders = '';
    foreach ($headers as $k => $v) {
        $canonicalHeaders .= $k . ':' . trim($v) . "\n";
        $signedHeaders .= $k . ';';
    }
    $signedHeaders = rtrim($signedHeaders, ';');
    
    $canonicalRequest = implode("\n", [
        $method,
        $uri,
        "", // Query String
        $canonicalHeaders,
        $signedHeaders,
        $contentHash
    ]);
    
    $credentialScope = "$date/$region/$service/aws4_request";
    $stringToSign = implode("\n", [
        "AWS4-HMAC-SHA256",
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest)
    ]);
    
    $kDate = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    $authorization = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
    $url = "https://$host$uri";
    
    $ch = curl_init();
    $httpHeaders = [
        "Authorization: $authorization",
        "x-amz-content-sha256: $contentHash",
        "x-amz-date: $amzDate",
        "Content-Type: $contentType",
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($statusCode === 200) {
        if (!empty($customDomain)) {
            $prefix = (strpos($customDomain, 'http') === 0) ? '' : 'https://';
            return rtrim($prefix . $customDomain, '/') . '/' . ltrim($fileName, '/');
        } else {
            return "https://pub-$accountId.r2.dev/$bucket/" . ltrim($fileName, '/'); // public R2 subdev domain format
        }
    }
    return false;
}

function r2_s3_delete(string $bucket, string $accountId, string $accessKey, string $secretKey, string $fileName): bool {
    $host = "$accountId.r2.cloudflarestorage.com";
    $service = "s3";
    $region = "auto";
    $method = "DELETE";
    $timestamp = time();
    $amzDate = gmdate('Ymd\THis\Z', $timestamp);
    $date = gmdate('Ymd', $timestamp);
    
    $uri = '/' . $bucket . '/' . ltrim($fileName, '/');
    $contentHash = hash('sha256', '');
    
    $headers = [
        'host' => $host,
        'x-amz-content-sha256' => $contentHash,
        'x-amz-date' => $amzDate,
    ];
    ksort($headers);
    
    $canonicalHeaders = '';
    $signedHeaders = '';
    foreach ($headers as $k => $v) {
        $canonicalHeaders .= $k . ':' . trim($v) . "\n";
        $signedHeaders .= $k . ';';
    }
    $signedHeaders = rtrim($signedHeaders, ';');
    
    $canonicalRequest = implode("\n", [
        $method,
        $uri,
        "",
        $canonicalHeaders,
        $signedHeaders,
        $contentHash
    ]);
    
    $credentialScope = "$date/$region/$service/aws4_request";
    $stringToSign = implode("\n", [
        "AWS4-HMAC-SHA256",
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest)
    ]);
    
    $kDate = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    $authorization = "AWS4-HMAC-SHA256 Credential=$accessKey/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";
    $url = "https://$host$uri";
    
    $ch = curl_init();
    $httpHeaders = [
        "Authorization: $authorization",
        "x-amz-content-sha256: $contentHash",
        "x-amz-date: $amzDate",
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    return ($statusCode === 204 || $statusCode === 200);
}

/* =============================================================
 *  ระบบเมล (เช่าอีเมล / กล่องเมล) — ฝั่งสมาชิก
 * ============================================================= */

function h_mail_packages(): void {
    $stmt = db()->prepare(
        "SELECT p.*, h.name AS host_name, h.webmail_url
         FROM mail_packages p JOIN mail_hosts h ON h.id = p.host_id
         WHERE p.tenant_id = ? AND p.is_active = 1 AND h.is_active = 1
         ORDER BY p.sort_order ASC, p.id ASC"
    );
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_mail_boxes(): void {
    $user = requireAuth();
    $stmt = db()->prepare(
        "SELECT m.id, m.email, m.status, m.expires_at, m.last_synced_at, m.created_at,
                p.name AS package_name, h.webmail_url, h.name AS host_name
         FROM mail_boxes m
         JOIN mail_packages p ON p.id = m.package_id
         JOIN mail_hosts h ON h.id = m.host_id
         WHERE m.tenant_id = ? AND m.user_id = ? AND m.status != 'deleted'
         ORDER BY m.created_at DESC"
    );
    $stmt->execute([tenantId(), $user['id']]);
    jsonOk($stmt->fetchAll());
}

function h_mail_webmail_hosts(): void {
    // รายชื่อโฮสที่มีเว็บเมลให้ลูกค้ากดเข้าไปล็อกอินตรง (รองรับหลายโฮส/หลายผู้ให้บริการ)
    $stmt = db()->prepare(
        "SELECT id, name, webmail_url FROM mail_hosts
         WHERE tenant_id = ? AND is_active = 1 AND webmail_url IS NOT NULL AND webmail_url != ''
         ORDER BY name ASC"
    );
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_mail_buy(): void {
    $user = requireAuth();
    $packageId = (int)field('package_id', 0);
    if ($packageId <= 0) jsonError('กรุณาเลือกแพ็กเกจ');

    $stmt = db()->prepare('SELECT * FROM mail_packages WHERE id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$packageId, tenantId()]);
    $pkg = $stmt->fetch();
    if (!$pkg) jsonError('ไม่พบแพ็กเกจนี้ หรือแพ็กเกจถูกปิดใช้งาน');

    $hStmt = db()->prepare('SELECT * FROM mail_hosts WHERE id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1');
    $hStmt->execute([$pkg['host_id'], tenantId()]);
    $host = $hStmt->fetch();
    if (!$host) jsonError('โฮสเมลของแพ็กเกจนี้ไม่พร้อมใช้งานในขณะนี้');

    $price = (float)$pkg['price'];
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $bStmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
        $bStmt->execute([$user['id'], tenantId()]);
        $balance = (float)$bStmt->fetchColumn();

        if ($balance < $price) {
            $pdo->rollBack();
            jsonError('ยอดเงินของคุณไม่เพียงพอสำหรับการเช่าอีเมลนี้ (ราคา ' . number_format($price, 2) . ' บาท)');
        }

        $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND tenant_id = ?')
            ->execute([$price, $user['id'], tenantId()]);

        // สร้างชื่อกล่องเมลแบบสุ่ม ไม่ซ้ำ
        $localPart = 'mail' . $user['id'] . strtolower(bin2hex(random_bytes(3)));
        $email = $localPart . '@' . $pkg['domain'];
        $password = mail_random_password();

        try {
            mail_host_create_email($host, $localPart, $pkg['domain'], $password, (int)$pkg['quota_mb']);
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonError('สร้างอีเมลบนโฮสไม่สำเร็จ: ' . $e->getMessage());
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)$pkg['days'] . ' days'));
        $ins = $pdo->prepare(
            'INSERT INTO mail_boxes (tenant_id, user_id, host_id, package_id, email, secret, status, expires_at)
             VALUES (?,?,?,?,?,?,"active",?)'
        );
        $ins->execute([tenantId(), $user['id'], $host['id'], $packageId, $email, mail_encrypt($password), $expiresAt]);
        $mailboxId = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO mail_wallet_log (tenant_id, user_id, mailbox_id, amount, note) VALUES (?,?,?,?,?)')
            ->execute([tenantId(), $user['id'], $mailboxId, $price, 'เช่าอีเมล ' . $email]);

        $pdo->commit();
        jsonOk([
            'id' => $mailboxId, 'email' => $email, 'expires_at' => $expiresAt, 'balance' => $balance - $price,
        ], 'เช่าอีเมล ' . $email . ' สำเร็จ');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonError('ทำรายการไม่สำเร็จ', 500, APP_DEBUG ? $e->getMessage() : null);
    }
}

/** ดึงกล่องเมล + ตรวจสิทธิ์เจ้าของ (tenant + user) */
function mail_owned_box(int $mailboxId, int $userId): array {
    $stmt = db()->prepare('SELECT * FROM mail_boxes WHERE id = ? AND tenant_id = ? AND user_id = ? AND status != "deleted" LIMIT 1');
    $stmt->execute([$mailboxId, tenantId(), $userId]);
    $row = $stmt->fetch();
    if (!$row) jsonError('ไม่พบกล่องอีเมลนี้ หรือไม่มีสิทธิ์เข้าถึง', 404);
    return $row;
}

function h_mail_box_delete(): void {
    $user = requireAuth();
    $mailboxId = (int)field('mailbox_id', 0);
    $mailbox = mail_owned_box($mailboxId, $user['id']);
    $host = mail_get_host_for_mailbox($mailbox);
    [$local, $domain] = explode('@', $mailbox['email'], 2);
    mail_host_delete_email($host, $local, $domain);

    $pdo = db();
    $pdo->prepare('DELETE FROM mail_messages WHERE mailbox_id = ?')->execute([$mailboxId]);
    $pdo->prepare('UPDATE mail_boxes SET status = "deleted" WHERE id = ? AND tenant_id = ?')->execute([$mailboxId, tenantId()]);
    jsonOk([], 'ลบกล่องอีเมลเรียบร้อยแล้ว');
}

/** เปิดเผยอีเมล/รหัสผ่านกล่องเมล (สำหรับกดคัดลอกไปล็อกอินเว็บเมลของโฮสเอง) */
function h_mail_box_reveal(): void {
    $user = requireAuth();
    $mailboxId = (int)($_GET['mailbox_id'] ?? 0);
    $mailbox = mail_owned_box($mailboxId, $user['id']);
    jsonOk([
        'email'    => $mailbox['email'],
        'password' => mail_decrypt($mailbox['secret']),
    ]);
}

function h_mail_refresh(): void {
    $user = requireAuth();
    $mailboxId = (int)field('mailbox_id', 0);
    $mailbox = mail_owned_box($mailboxId, $user['id']);
    $host = mail_get_host_for_mailbox($mailbox);
    try {
        $newCount = mail_sync_inbox($mailbox, $host);
        jsonOk(['new_count' => $newCount], $newCount > 0 ? "มีอีเมลใหม่ $newCount ฉบับ" : 'ยังไม่มีอีเมลใหม่');
    } catch (Throwable $e) {
        jsonError('ดึงอีเมลใหม่ไม่สำเร็จ: ' . $e->getMessage());
    }
}

function h_mail_messages(): void {
    $user = requireAuth();
    $mailboxId = (int)($_GET['mailbox_id'] ?? 0);
    $q = trim((string)($_GET['q'] ?? ''));
    mail_owned_box($mailboxId, $user['id']);

    $sql = 'SELECT id, uid, from_addr, from_name, subject, otp_code, received_at, is_read
            FROM mail_messages WHERE mailbox_id = ? AND tenant_id = ?';
    $params = [$mailboxId, tenantId()];
    if ($q !== '') {
        $sql .= ' AND (subject LIKE ? OR from_addr LIKE ? OR from_name LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    $sql .= ' ORDER BY received_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    jsonOk($stmt->fetchAll());
}

function h_mail_message(): void {
    $user = requireAuth();
    $mailboxId = (int)($_GET['mailbox_id'] ?? 0);
    $messageId = (int)($_GET['message_id'] ?? 0);
    mail_owned_box($mailboxId, $user['id']);

    $stmt = db()->prepare('SELECT * FROM mail_messages WHERE id = ? AND mailbox_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$messageId, $mailboxId, tenantId()]);
    $row = $stmt->fetch();
    if (!$row) jsonError('ไม่พบอีเมลฉบับนี้', 404);

    db()->prepare('UPDATE mail_messages SET is_read = 1 WHERE id = ?')->execute([$messageId]);
    jsonOk($row);
}

function h_mail_message_delete(): void {
    $user = requireAuth();
    $mailboxId = (int)field('mailbox_id', 0);
    $messageId = (int)field('message_id', 0);
    $mailbox = mail_owned_box($mailboxId, $user['id']);

    $stmt = db()->prepare('SELECT uid FROM mail_messages WHERE id = ? AND mailbox_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$messageId, $mailboxId, tenantId()]);
    $msg = $stmt->fetch();
    if (!$msg) jsonError('ไม่พบอีเมลฉบับนี้', 404);

    try {
        $host = mail_get_host_for_mailbox($mailbox);
        mail_delete_message_on_server($mailbox, $host, $msg['uid']);
    } catch (Throwable $e) {
        // ลบในระบบต่อได้แม้ลบบน server ไม่สำเร็จ (เช่น host ถูกลบไปแล้ว)
    }
    db()->prepare('DELETE FROM mail_messages WHERE id = ?')->execute([$messageId]);
    jsonOk([], 'ลบอีเมลฉบับนี้เรียบร้อยแล้ว');
}

/* =============================================================
 *  ระบบเมล — ฝั่งหลังบ้าน (จัดการโฮส/แพ็กเกจ/ดูกล่องเมลทั้งหมด)
 * ============================================================= */

function h_admin_mail_hosts(): void {
    requireAdmin();
    $stmt = db()->prepare('SELECT id, tenant_id, name, driver, api_url, api_username, verify_ssl, imap_host, imap_port, imap_flags, webmail_url, is_active, created_at FROM mail_hosts WHERE tenant_id = ? ORDER BY id DESC');
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_admin_mail_host_save(): void {
    requireAdmin();
    $id       = (int)field('id', 0);
    $name     = trim((string)field('name', ''));
    $driver   = field('driver', 'directadmin') === 'cpanel' ? 'cpanel' : 'directadmin';
    $apiUrl   = trim((string)field('api_url', ''));
    $apiUser  = trim((string)field('api_username', ''));
    $apiSecret = (string)field('api_secret', '');
    $verifySsl = field('verify_ssl', true) ? 1 : 0;
    $imapHost = trim((string)field('imap_host', ''));
    $imapPort = (int)field('imap_port', 993);
    $imapFlags = trim((string)field('imap_flags', '/imap/ssl/novalidate-cert'));
    $webmailUrl = trim((string)field('webmail_url', ''));
    $isActive = field('is_active', true) ? 1 : 0;

    if ($name === '' || $apiUrl === '' || $apiUser === '' || $imapHost === '') {
        jsonError('กรุณากรอกข้อมูลโฮสให้ครบ (ชื่อ, API URL, Username, IMAP Host)');
    }

    $pdo = db();
    if ($id > 0) {
        // แก้ไข — ถ้าไม่กรอก secret ใหม่ ให้คงรหัสเดิมไว้
        if ($apiSecret !== '') {
            $stmt = $pdo->prepare(
                'UPDATE mail_hosts SET name=?, driver=?, api_url=?, api_username=?, api_secret=?, verify_ssl=?, imap_host=?, imap_port=?, imap_flags=?, webmail_url=?, is_active=?
                 WHERE id=? AND tenant_id=?'
            );
            $stmt->execute([$name, $driver, $apiUrl, $apiUser, mail_encrypt($apiSecret), $verifySsl, $imapHost, $imapPort, $imapFlags, $webmailUrl ?: null, $isActive, $id, tenantId()]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE mail_hosts SET name=?, driver=?, api_url=?, api_username=?, verify_ssl=?, imap_host=?, imap_port=?, imap_flags=?, webmail_url=?, is_active=?
                 WHERE id=? AND tenant_id=?'
            );
            $stmt->execute([$name, $driver, $apiUrl, $apiUser, $verifySsl, $imapHost, $imapPort, $imapFlags, $webmailUrl ?: null, $isActive, $id, tenantId()]);
        }
    } else {
        if ($apiSecret === '') jsonError('กรุณากรอก Login Key/รหัสผ่าน/API Token ของโฮส');
        $stmt = $pdo->prepare(
            'INSERT INTO mail_hosts (tenant_id, name, driver, api_url, api_username, api_secret, verify_ssl, imap_host, imap_port, imap_flags, webmail_url, is_active)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([tenantId(), $name, $driver, $apiUrl, $apiUser, mail_encrypt($apiSecret), $verifySsl, $imapHost, $imapPort, $imapFlags, $webmailUrl ?: null, $isActive]);
    }
    jsonOk([], 'บันทึกข้อมูลโฮสเมลแล้ว');
}

function h_admin_mail_host_delete(): void {
    requireAdmin();
    $id = (int)field('id', 0);
    $cnt = db()->prepare('SELECT COUNT(*) FROM mail_packages WHERE host_id = ? AND tenant_id = ?');
    $cnt->execute([$id, tenantId()]);
    if ((int)$cnt->fetchColumn() > 0) {
        jsonError('ลบไม่ได้ เพราะมีแพ็กเกจผูกกับโฮสนี้อยู่ กรุณาลบ/ย้ายแพ็กเกจก่อน');
    }
    db()->prepare('DELETE FROM mail_hosts WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'ลบโฮสเมลแล้ว');
}

function h_admin_mail_host_test(): void {
    requireAdmin();
    $id = (int)field('id', 0);
    $stmt = db()->prepare('SELECT * FROM mail_hosts WHERE id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$id, tenantId()]);
    $host = $stmt->fetch();
    if (!$host) jsonError('ไม่พบโฮสนี้');
    $res = mail_host_test($host);
    if ($res['ok']) jsonOk([], $res['message']);
    jsonError($res['message']);
}

function h_admin_mail_packages(): void {
    requireAdmin();
    $stmt = db()->prepare(
        'SELECT p.*, h.name AS host_name FROM mail_packages p JOIN mail_hosts h ON h.id = p.host_id
         WHERE p.tenant_id = ? ORDER BY p.sort_order ASC, p.id ASC'
    );
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}

function h_admin_mail_package_save(): void {
    requireAdmin();
    $id       = (int)field('id', 0);
    $hostId   = (int)field('host_id', 0);
    $name     = trim((string)field('name', ''));
    $domain   = trim((string)field('domain', ''));
    $days     = max(1, (int)field('days', 7));
    $price    = max(0, (float)field('price', 0));
    $quotaMb  = max(10, (int)field('quota_mb', 100));
    $desc     = trim((string)field('description', ''));
    $isActive = field('is_active', true) ? 1 : 0;
    $sortOrder = (int)field('sort_order', 0);

    if ($hostId <= 0 || $name === '' || $domain === '') jsonError('กรุณากรอกชื่อแพ็กเกจ, เลือกโฮส และระบุโดเมนให้ครบ');

    $hStmt = db()->prepare('SELECT id FROM mail_hosts WHERE id = ? AND tenant_id = ?');
    $hStmt->execute([$hostId, tenantId()]);
    if (!$hStmt->fetch()) jsonError('ไม่พบโฮสที่เลือก');

    $pdo = db();
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'UPDATE mail_packages SET host_id=?, name=?, domain=?, days=?, price=?, quota_mb=?, description=?, is_active=?, sort_order=?
             WHERE id=? AND tenant_id=?'
        );
        $stmt->execute([$hostId, $name, $domain, $days, $price, $quotaMb, $desc, $isActive, $sortOrder, $id, tenantId()]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO mail_packages (tenant_id, host_id, name, domain, days, price, quota_mb, description, is_active, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([tenantId(), $hostId, $name, $domain, $days, $price, $quotaMb, $desc, $isActive, $sortOrder]);
    }
    jsonOk([], 'บันทึกแพ็กเกจอีเมลแล้ว');
}

function h_admin_mail_package_delete(): void {
    requireAdmin();
    $id = (int)field('id', 0);
    db()->prepare('UPDATE mail_packages SET is_active = 0 WHERE id = ? AND tenant_id = ?')->execute([$id, tenantId()]);
    jsonOk([], 'ปิดการขายแพ็กเกจนี้แล้ว');
}

function h_admin_mail_boxes(): void {
    requireAdmin();
    $stmt = db()->prepare(
        "SELECT m.id, m.email, m.status, m.expires_at, m.created_at, u.username, p.name AS package_name, h.name AS host_name
         FROM mail_boxes m
         JOIN users u ON u.id = m.user_id
         JOIN mail_packages p ON p.id = m.package_id
         JOIN mail_hosts h ON h.id = m.host_id
         WHERE m.tenant_id = ? AND m.status != 'deleted'
         ORDER BY m.created_at DESC LIMIT 500"
    );
    $stmt->execute([tenantId()]);
    jsonOk($stmt->fetchAll());
}
