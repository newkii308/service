<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin_page();
$productId = max(0, (int)($_GET['id'] ?? 0));
$pstmt = db()->prepare("SELECT id, name FROM products WHERE id = ? AND tenant_id = ? AND type = 'code' LIMIT 1");
$pstmt->execute([$productId, tenantId()]);
$product = $pstmt->fetch();
if (!$product) legacy_redirect('backend/products/game-codes/');
$stmt = db()->prepare('SELECT id, content, status, order_id, created_at, sold_at FROM product_codes WHERE product_id = ? AND tenant_id = ? ORDER BY id DESC');
$stmt->execute([$productId, tenantId()]);
$codes = $stmt->fetchAll();
$pageTitle = 'คลังโค้ด ' . $product['name'];
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><a class="muted" href="./">← กลับสินค้า</a><h1>คลังโค้ด: <?= e($product['name']) ?></h1></div></div>
  <form class="card form-card form-stack" method="post" action="add-stock.php" style="max-width:none;margin-bottom:1.5rem">
    <?= csrf_field() ?><input type="hidden" name="product_id" value="<?= $productId ?>">
    <div class="field-group"><label for="codes">เพิ่มโค้ด บรรทัดละ 1 รายการ</label><textarea class="field" id="codes" name="codes" required></textarea></div>
    <button class="btn btn-primary" type="submit">เพิ่มเข้าคลัง</button>
  </form>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>ID</th><th>ข้อมูล</th><th>สถานะ</th><th>ออเดอร์</th><th></th></tr></thead><tbody>
  <?php foreach ($codes as $code): ?><tr><td>#<?= (int)$code['id'] ?></td><td><div class="code-box"><?= e($code['content']) ?></div></td><td><span class="status status-<?= e($code['status']) ?>"><?= e($code['status']) ?></span></td><td><?= $code['order_id'] ? '#' . (int)$code['order_id'] : '-' ?></td><td><?php if ($code['status'] === 'available'): ?><form method="post" action="delete-stock.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$code['id'] ?>"><input type="hidden" name="product_id" value="<?= $productId ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันลบโค้ดนี้?">ลบ</button></form><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
