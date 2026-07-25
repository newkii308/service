<?php
declare(strict_types=1);

$query = trim((string)($_GET['q'] ?? ''));
$categoryId = max(0, (int)($_GET['category'] ?? 0));
$allProducts = fetch_products($productType, $query, $categoryId);
$perPage = 48;
$totalProducts = count($allProducts);
$totalPages = max(1, (int)ceil($totalProducts / $perPage));
$currentPage = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$products = array_slice($allProducts, ($currentPage - 1) * $perPage, $perPage);

$categoryStmt = db()->prepare(
    'SELECT DISTINCT c.id, c.name
     FROM categories c
     JOIN products p ON p.category_id = c.id AND p.tenant_id = c.tenant_id
     WHERE c.tenant_id = ? AND p.type = ? AND p.is_active = 1
     ORDER BY c.sort_order, c.name'
);
$categoryStmt->execute([tenantId(), $productType]);
$categories = $categoryStmt->fetchAll();

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div>
      <h1><?= e($productHeading) ?></h1>
      <p class="muted"><?= e($productLead) ?></p>
    </div>
    <span class="chip"><?= $totalProducts ?> รายการ</span>
  </div>

  <form class="filters" method="get">
    <input class="field" type="search" name="q" value="<?= e($query) ?>" placeholder="ค้นหาใน <?= e($productHeading) ?>">
    <?php if ($categories): ?>
      <select class="field" name="category">
        <option value="0">ทุกหมวดหมู่</option>
        <?php foreach ($categories as $category): ?>
          <option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <button class="btn btn-primary" type="submit">ค้นหา</button>
  </form>

  <?php if ($products): ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="card card-hover legacy-product-card">
          <a class="cover" href="<?= e(product_detail_url($product)) ?>">
            <?php if ((string)$product['cover_image'] !== ''): ?>
              <img src="<?= e($product['cover_image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
            <?php else: ?>
              <span class="cover-placeholder">◎</span>
            <?php endif; ?>
          </a>
          <div class="product-body">
            <div class="muted" style="font-size:.75rem"><?= e($product['category_name'] ?: $productHeading) ?></div>
            <h3><a href="<?= e(product_detail_url($product)) ?>"><?= e($product['name']) ?></a></h3>
            <div class="product-meta">
              <span class="price">฿<?= money($product['price']) ?></span>
              <?php if ($productType === 'code'): ?>
                <span class="stock">เหลือ <?= (int)$product['stock'] ?></span>
              <?php else: ?>
                <span class="stock">พร้อมให้บริการ</span>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card empty-state">
      <h2>ไม่พบรายการ</h2>
      <p class="muted">ลองเปลี่ยนคำค้นหาหรือหมวดหมู่</p>
    </div>
  <?php endif; ?>
  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="แบ่งหน้าสินค้า">
      <?php for ($number = 1; $number <= $totalPages; $number++): ?>
        <a class="btn <?= $number === $currentPage ? 'btn-primary' : 'btn-ghost' ?>" href="?<?= e(http_build_query(['q' => $query, 'category' => $categoryId ?: null, 'page' => $number])) ?>"><?= $number ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
