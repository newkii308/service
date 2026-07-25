<?php
declare(strict_types=1);
$config = backend_product_config($productType);
$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name,
            (SELECT COUNT(*) FROM product_codes pc
             WHERE pc.product_id = p.id AND pc.tenant_id = p.tenant_id AND pc.status = 'available') AS stock
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id AND c.tenant_id = p.tenant_id
     WHERE p.tenant_id = ? AND p.type = ?
     ORDER BY p.sort_order, p.id DESC"
);
$stmt->execute([tenantId(), $productType]);
$allProducts = $stmt->fetchAll();
$perPage = 60;
$totalProducts = count($allProducts);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$products = array_slice($allProducts, ($currentPage - 1) * $perPage, $perPage);
$pageTitle = 'จัดการ ' . $config['title'];
require __DIR__ . '/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div><h1><?= e($config['title']) ?></h1><p class="muted">จัดการเฉพาะสินค้า type: <?= e($productType) ?></p></div>
    <a class="btn btn-primary" href="edit.php">เพิ่มรายการ</a>
  </div>
  <?php if ($products): ?>
    <div class="legacy-table-wrap">
      <table class="legacy-table">
        <thead><tr><th>ID</th><th>สินค้า</th><th>ราคา</th><?php if ($productType === 'code'): ?><th>สต็อก</th><?php endif; ?><th>สถานะ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
          <tr>
            <td>#<?= (int)$product['id'] ?></td>
            <td><strong><?= e($product['name']) ?></strong><div class="muted"><?= e($product['category_name'] ?: '-') ?></div></td>
            <td>฿<?= money($product['price']) ?></td>
            <?php if ($productType === 'code'): ?><td><a href="stock.php?id=<?= (int)$product['id'] ?>"><?= (int)$product['stock'] ?> ชิ้น</a></td><?php endif; ?>
            <td><span class="status <?= (int)$product['is_active'] === 1 ? 'status-active' : 'status-cancelled' ?>"><?= (int)$product['is_active'] === 1 ? 'เปิดขาย' : 'ปิดขาย' ?></span></td>
            <td>
              <div class="split-actions">
                <a class="btn btn-ghost" href="edit.php?id=<?= (int)$product['id'] ?>">แก้ไข</a>
                <form method="post" action="toggle.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button class="btn btn-ghost" type="submit">เปิด/ปิด</button></form>
                <form method="post" action="delete.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันลบสินค้านี้และข้อมูลที่เกี่ยวข้อง?">ลบ</button></form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card empty-state"><h2>ยังไม่มีรายการในโมดูลนี้</h2><a class="btn btn-primary" href="edit.php">เพิ่มรายการแรก</a></div>
  <?php endif; ?>
  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="แบ่งหน้าสินค้า"><?php for ($number = 1; $number <= $totalPages; $number++): ?><a class="btn <?= $number === $currentPage ? 'btn-primary' : 'btn-ghost' ?>" href="?page=<?= $number ?>"><?= $number ?></a><?php endfor; ?></nav>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
