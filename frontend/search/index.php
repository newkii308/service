<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$query = trim((string)($_GET['q'] ?? ''));
$products = [];
if ($query !== '') {
    $stmt = db()->prepare(
        "SELECT p.id, p.type, p.name, p.price, p.cover_image, c.name AS category_name,
                (SELECT COUNT(*) FROM product_codes pc WHERE pc.product_id = p.id AND pc.status = 'available') AS stock
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.tenant_id = ? AND p.is_active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
         ORDER BY p.sort_order, p.id DESC LIMIT 100"
    );
    $like = '%' . $query . '%';
    $stmt->execute([tenantId(), $like, $like]);
    $products = $stmt->fetchAll();
}
$pageTitle = 'ค้นหาสินค้า';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ค้นหาสินค้าทุกโมดูล</h1><p class="muted">ผลลัพธ์จะพาไปยังโฟลเดอร์ของสินค้าประเภทนั้นโดยตรง</p></div></div>
  <form class="filters" method="get"><input class="field" type="search" name="q" value="<?= e($query) ?>" required autofocus placeholder="ชื่อสินค้า บริการ หรือคำสำคัญ"><button class="btn btn-primary" type="submit">ค้นหา</button></form>
  <?php if ($query !== '' && $products): ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="card card-hover legacy-product-card">
          <a class="cover" href="<?= e(product_detail_url($product)) ?>"><?php if ($product['cover_image']): ?><img src="<?= e($product['cover_image']) ?>" alt="" loading="lazy"><?php else: ?><span class="cover-placeholder">◎</span><?php endif; ?></a>
          <div class="product-body"><div class="muted" style="font-size:.75rem"><?= e($product['category_name'] ?: product_folder($product['type'])) ?></div><h3><a href="<?= e(product_detail_url($product)) ?>"><?= e($product['name']) ?></a></h3><div class="product-meta"><span class="price">฿<?= money($product['price']) ?></span><a class="btn btn-ghost" href="<?= e(product_detail_url($product)) ?>">ดูสินค้า</a></div></div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php elseif ($query !== ''): ?>
    <div class="card empty-state"><h2>ไม่พบผลลัพธ์</h2><p class="muted">ลองใช้คำค้นหาที่สั้นลง</p></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
