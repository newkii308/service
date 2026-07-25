<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$id = max(0, (int)($_GET['id'] ?? 0));
$stmt = db()->prepare('SELECT id, type FROM products WHERE id = ? AND tenant_id = ? AND is_active = 1 LIMIT 1');
$stmt->execute([$id, tenantId()]);
$product = $stmt->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = 'ไม่พบสินค้า';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<section class="container-x page-shell"><div class="card empty-state"><h1>ไม่พบสินค้า</h1></div></section>';
    require dirname(__DIR__) . '/includes/footer.php';
    exit;
}
header('Location: ' . product_detail_url($product), true, 302);
exit;
