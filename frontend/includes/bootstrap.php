<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/api/core.php';

startSession();
requireActiveTenant();

function app_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/frontend/', '/backend/'] as $marker) {
        $position = strpos($script, $marker);
        if ($position !== false) {
            $base = rtrim(substr($script, 0, $position), '/');
            return $base;
        }
    }

    $directory = str_replace('\\', '/', dirname($script));
    $base = $directory === '/' || $directory === '.' ? '' : rtrim($directory, '/');
    return $base;
}

function app_url(string $path = ''): string
{
    return app_base_path() . '/' . ltrim($path, '/');
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($value): string
{
    return number_format((float)$value, 2);
}

function csrf_token(): string
{
    return (string)($_COOKIE['csrf'] ?? '');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function legacy_redirect(string $path): void
{
    header('Location: ' . app_url($path), true, 303);
    exit;
}

function require_member_page(): array
{
    $user = currentUser();
    if (!$user) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'กรุณาเข้าสู่ระบบก่อน'];
        $next = (string)($_SERVER['REQUEST_URI'] ?? app_url('frontend/home/'));
        header('Location: ' . app_url('frontend/auth/login/?redirect=' . rawurlencode($next)), true, 303);
        exit;
    }
    return $user;
}

function require_admin_page(bool $requirePin = true): array
{
    $user = currentUser();
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ต้องเข้าสู่ระบบด้วยบัญชีผู้ดูแล'];
        legacy_redirect('frontend/auth/login/');
    }
    if ($requirePin && !adminPinVerified()) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'กรุณายืนยัน PIN หลังบ้าน'];
        legacy_redirect('backend/auth/pin.php');
    }
    return $user;
}

function pull_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function product_folder(string $type): string
{
    return [
        'code' => 'game-codes',
        'refill' => 'game-refills',
        'streaming' => 'streaming-accounts',
        'social' => 'social-boost',
        'otp' => 'app-otp',
    ][$type] ?? 'game-codes';
}

function product_detail_url(array $product): string
{
    return app_url('frontend/products/' . product_folder((string)($product['type'] ?? 'code')) . '/detail.php?id=' . (int)$product['id']);
}

function fetch_products(string $type, string $query = '', int $categoryId = 0): array
{
    $sql = "SELECT p.id, p.type, p.name, p.slug, p.price, p.cover_image, p.category_id,
                   p.api_type_id, p.otp_service, p.otp_type, p.description,
                   c.name AS category_name,
                   (SELECT COUNT(*) FROM product_codes pc
                    WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id
                      AND pc.status = 'available') AS stock
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
            WHERE p.tenant_id = ? AND p.is_active = 1 AND p.type = ?";
    $params = [tenantId(), $type];
    if ($query !== '') {
        $sql .= ' AND p.name LIKE ?';
        $params[] = '%' . $query . '%';
    }
    if ($categoryId > 0) {
        $sql .= ' AND p.category_id = ?';
        $params[] = $categoryId;
    }
    $sql .= ' ORDER BY p.sort_order ASC, p.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_product(int $id, string $type): ?array
{
    $stmt = db()->prepare(
        "SELECT p.*, c.name AS category_name,
                (SELECT COUNT(*) FROM product_codes pc
                 WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id
                   AND pc.status = 'available') AS stock
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
         WHERE p.id = ? AND p.tenant_id = ? AND p.type = ? AND p.is_active = 1
         LIMIT 1"
    );
    $stmt->execute([$id, tenantId(), $type]);
    return $stmt->fetch() ?: null;
}

function page_title(string $title = ''): string
{
    $siteName = (string)setting('site_name', 'GameStore');
    return $title === '' ? $siteName : $title . ' · ' . $siteName;
}

function safe_hex(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}
