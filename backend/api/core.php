<?php
/**
 * core.php — ฟังก์ชันแกนกลาง: ฐานข้อมูล, เซสชัน, สิทธิ์, การตอบกลับ JSON
 */
require_once __DIR__ . '/config.php';

// เผื่อโฮสต์บางที่ไม่ได้เปิด mbstring — ใส่ fallback ให้ระบบยังทำงานได้
if (!function_exists('mb_strlen')) {
    function mb_strlen($s, $enc = null) { return strlen((string)$s); }
}

/* ---------------------------------------------------------------
 *  ฐานข้อมูล (PDO แบบ singleton)
 * --------------------------------------------------------------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            jsonError('เชื่อมต่อฐานข้อมูลไม่สำเร็จ', 500,
                APP_DEBUG ? $e->getMessage() : null);
        }
    }
    return $pdo;
}

/* ---------------------------------------------------------------
 *  Multi-tenant (ปล่อยเช่าเว็บ) — แยกร้านตาม host ของคำขอ
 *  ทุก query ในระบบต้อง scope ด้วย tenantId() เพื่อไม่ให้ข้อมูลข้ามร้าน
 * --------------------------------------------------------------- */
function currentTenant(): array {
    static $t = null;
    if ($t !== null) return $t;

    // host ปัจจุบัน (ตัด port ออก) — เช่น shop1.example.com
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\d+$/', '', $host);

    $row = null;
    try {
        $stmt = db()->prepare('SELECT * FROM tenants WHERE host = ? LIMIT 1');
        $stmt->execute([$host]);
        $row = $stmt->fetch() ?: null;
        if (!$row) {
            // ไม่พบ host ที่ตรง → ใช้ร้านหลัก (id = 1)
            $row = db()->query('SELECT * FROM tenants WHERE id = 1 LIMIT 1')->fetch() ?: null;
        }
    } catch (Throwable $e) {
        // ยังไม่ได้ migrate ตาราง tenants → ถือว่าเป็นร้านหลัก
        $row = null;
    }
    $t = $row ?: ['id' => 1, 'name' => '', 'host' => null, 'status' => 'active', 'expires_at' => null];
    return $t;
}

/** id ของร้านปัจจุบัน — ใช้ scope ทุก query */
function tenantId(): int { return (int)currentTenant()['id']; }

/** บล็อกร้านที่ถูกระงับ/หมดอายุ (เรียกก่อน dispatch) */
function requireActiveTenant(): void {
    $t = currentTenant();
    if (($t['status'] ?? 'active') === 'suspended') {
        jsonError('ร้านค้านี้ถูกระงับการใช้งานชั่วคราว', 403);
    }
    if (!empty($t['expires_at']) && strtotime($t['expires_at']) < time()) {
        jsonError('ร้านค้านี้หมดอายุการใช้งาน กรุณาต่ออายุ', 402);
    }
}

/* ---------------------------------------------------------------
 *  ตอบกลับเป็น JSON
 * --------------------------------------------------------------- */
function jsonResponse($data, int $code = 200): void {
    // Procedural page actions reuse the proven business handlers, then return
    // to their own PHP page with a session flash instead of exposing JSON.
    if (!empty($GLOBALS['LEGACY_FORM_REDIRECT'])) {
        startSession();
        $ok = is_array($data) && !empty($data['ok']);
        $message = is_array($data)
            ? (string)($data['message'] ?? $data['error'] ?? '')
            : '';
        $_SESSION['flash'] = [
            'type' => $ok ? 'success' : 'danger',
            'message' => $message !== '' ? $message : ($ok ? 'ทำรายการสำเร็จ' : 'ทำรายการไม่สำเร็จ'),
        ];
        if ($ok && is_array($data) && isset($data['data'])) {
            $_SESSION['last_action_result'] = $data['data'];
        }
        header('Location: ' . (string)$GLOBALS['LEGACY_FORM_REDIRECT'], true, 303);
        exit;
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function jsonOk($data = [], string $message = ''): void {
    jsonResponse(['ok' => true, 'message' => $message, 'data' => $data]);
}
function jsonError(string $message, int $code = 400, $detail = null): void {
    $out = ['ok' => false, 'error' => $message];
    if ($detail !== null) $out['detail'] = $detail;
    jsonResponse($out, $code);
}

/* ---------------------------------------------------------------
 *  รับข้อมูล input (JSON body หรือ form)
 * --------------------------------------------------------------- */
function input(): array {
    static $data = null;
    if ($data === null) {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        $data = is_array($json) ? $json : $_POST;
    }
    return $data;
}
function field(string $key, $default = null) {
    $d = input();
    return array_key_exists($key, $d) ? $d[$key] : $default;
}

/* ---------------------------------------------------------------
 *  เซสชัน / การยืนยันตัวตน
 * --------------------------------------------------------------- */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    // ออก CSRF token แบบ double-submit cookie (ถ้ายังไม่มี)
    if (empty($_COOKIE['csrf'])) {
        $token = bin2hex(random_bytes(16));
        setcookie('csrf', $token, [
            'path'     => '/',
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => false, // ต้องให้ JS อ่านได้เพื่อส่งกลับใน header
        ]);
        $_COOKIE['csrf'] = $token; // ใช้ได้ทันทีในคำขอเดียวกัน
    }
}

/** ตรวจ CSRF สำหรับคำขอที่เปลี่ยนแปลงข้อมูล (POST) */
function requireCsrf(): void {
    $cookie = $_COOKIE['csrf'] ?? '';
    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($cookie === '' || $header === '' || !hash_equals($cookie, $header)) {
        jsonError('คำขอไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองใหม่', 419);
    }
}

/** IP ผู้ใช้ (รองรับ Cloudflare) */
function clientIp(): string {
    return $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

/** จำกัดอัตราการเรียก: คืน false ถ้าเกินโควตาในหน้าต่างเวลา */
function rateLimit(string $key, int $max, int $seconds): bool {
    $key = tenantId() . ':' . $key; // แยกโควตารายร้าน
    try {
        $db = db();
        $db->prepare('DELETE FROM rate_events WHERE created_at < (NOW() - INTERVAL ? SECOND)')->execute([$seconds]);
        $stmt = $db->prepare('SELECT COUNT(*) FROM rate_events WHERE rk = ?');
        $stmt->execute([$key]);
        if ((int)$stmt->fetchColumn() >= $max) return false;
        $db->prepare('INSERT INTO rate_events (rk) VALUES (?)')->execute([$key]);
        return true;
    } catch (Throwable $e) {
        return true; // ถ้าตารางยังไม่มี อย่าบล็อกผู้ใช้
    }
}

function adminPinVerified(): bool {
    startSession();
    return !empty($_SESSION['admin_pin_verified']) && (int)($_SESSION['admin_pin_tenant'] ?? 0) === tenantId();
}

function verifyAdminPinValue(string $pin): bool {
    if (!preg_match('/^\d{6}$/', $pin)) return false;
    $hash = (string)setting('admin_pin_hash', '');
    if ($hash !== '') return password_verify($pin, $hash);
    return hash_equals('123456', $pin);
}

function markAdminPinVerified(): void {
    startSession();
    $_SESSION['admin_pin_verified'] = true;
    $_SESSION['admin_pin_tenant'] = tenantId();
}

function clearAdminPinVerified(): void {
    startSession();
    unset($_SESSION['admin_pin_verified'], $_SESSION['admin_pin_tenant']);
}

function currentUser(): ?array {
    startSession();
    if (empty($_SESSION['uid'])) return null;
    $stmt = db()->prepare('SELECT id, username, email, balance, role, is_active FROM users WHERE id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['uid'], tenantId()]);
    $u = $stmt->fetch();
    if (!$u || (int)$u['is_active'] !== 1) return null;
    $u['balance'] = (float)$u['balance'];
    if ($u['role'] === 'admin') $u['admin_pin_verified'] = adminPinVerified();
    return $u;
}

function requireAuth(): array {
    $u = currentUser();
    if (!$u) jsonError('กรุณาเข้าสู่ระบบก่อน', 401);
    return $u;
}

function requireAdmin(bool $requirePin = true): array {
    $u = requireAuth();
    if ($u['role'] !== 'admin') jsonError('ต้องเป็นผู้ดูแลระบบเท่านั้น', 403);
    if ($requirePin && !adminPinVerified()) jsonError('กรุณายืนยัน PIN หลังบ้านก่อน', 423);
    return $u;
}

/* ---------------------------------------------------------------
 *  ตั้งค่าระบบ (settings key/value)
 * --------------------------------------------------------------- */
function getSettings(): array {
    static $cache = [];
    $tid = tenantId();
    if (!array_key_exists($tid, $cache)) {
        $cache[$tid] = [];
        $stmt = db()->prepare('SELECT k, v FROM settings WHERE tenant_id = ?');
        $stmt->execute([$tid]);
        foreach ($stmt as $row) {
            $cache[$tid][$row['k']] = $row['v'];
        }
    }
    return $cache[$tid];
}
function setting(string $key, $default = '') {
    $s = getSettings();
    return $s[$key] ?? $default;
}
function saveSetting(string $key, $value): void {
    $stmt = db()->prepare('INSERT INTO settings (tenant_id, k, v) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    $stmt->execute([tenantId(), $key, (string)$value]);
}

function requireTurnstile(): void {
    $secret = trim((string)setting('turnstile_secret_key', ''));
    $siteKey = trim((string)setting('turnstile_site_key', ''));
    if ($secret === '' || $siteKey === '') return;

    $token = trim((string)field('turnstile_token', ''));
    if ($token === '') jsonError('กรุณายืนยัน Cloudflare ก่อนดำเนินการ');

    $payload = http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => clientIp(),
    ]);

    $body = false;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);
        $body = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    }

    $json = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($json) || empty($json['success'])) {
        jsonError('ตรวจสอบ Cloudflare ไม่สำเร็จ กรุณาลองใหม่');
    }
}

/* ---------------------------------------------------------------
 *  ตัวช่วยเล็ก ๆ
 * --------------------------------------------------------------- */
function slugify(string $text): string {
    $text = trim($text);
    $text = preg_replace('/\s+/u', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text);
    $text = strtolower($text);
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . substr(md5(uniqid('', true)), 0, 6);
}

/** จำนวนสต็อกคงเหลือ (นับโค้ดที่ยัง available) */
function stockOf(int $productId): int {
    $stmt = db()->prepare("SELECT COUNT(*) FROM product_codes WHERE product_id = ? AND tenant_id = ? AND status = 'available'");
    $stmt->execute([$productId, tenantId()]);
    return (int)$stmt->fetchColumn();
}
