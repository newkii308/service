<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin_page();
$stmt = db()->prepare(
    "SELECT o.*, u.username, COALESCE(p.type, 'code') AS product_type
     FROM orders o
     JOIN users u ON u.id = o.user_id AND u.tenant_id = o.tenant_id
     LEFT JOIN products p ON p.id = o.product_id AND p.tenant_id = o.tenant_id
     WHERE o.tenant_id = ? ORDER BY o.id DESC LIMIT 300"
);
$stmt->execute([tenantId()]);
$orders = $stmt->fetchAll();
$pageTitle = 'จัดการคำสั่งซื้อ';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>คำสั่งซื้อ</h1><p class="muted">อัปเดตสถานะออเดอร์ของร้านปัจจุบัน</p></div></div>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>เลขที่</th><th>สมาชิก</th><th>สินค้า</th><th>ยอด</th><th>สถานะ</th><th>วันที่</th></tr></thead><tbody>
  <?php foreach ($orders as $order): ?><tr><td>#<?= (int)$order['id'] ?></td><td><?= e($order['username']) ?></td><td><strong><?= e($order['product_name']) ?></strong><div class="muted"><?= e($order['product_type']) ?></div></td><td>฿<?= money($order['price']) ?></td><td><form class="split-actions" method="post" action="update-status.php"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><select class="field" name="status"><?php foreach (['pending','processing','completed','cancelled'] as $status): ?><option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select><button class="btn btn-ghost" type="submit">บันทึก</button></form></td><td><?= e($order['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
