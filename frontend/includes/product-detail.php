<?php
declare(strict_types=1);

$productId = max(0, (int)($_GET['id'] ?? 0));
$product = fetch_product($productId, $productType);
if (!$product) {
    http_response_code(404);
    $pageTitle = 'ไม่พบสินค้า';
    require dirname(__DIR__) . '/includes/header.php';
    echo '<section class="container-x page-shell"><div class="card empty-state"><h1>ไม่พบสินค้า</h1><a class="btn btn-primary" href="' . e(app_url('frontend/products/' . product_folder($productType) . '/')) . '">กลับหน้ารายการ</a></div></section>';
    require dirname(__DIR__) . '/includes/footer.php';
    return;
}

$pageTitle = (string)$product['name'];
$pageDescription = trim(strip_tags((string)$product['description'])) ?: $productLead;
$user = currentUser();
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="detail-grid">
    <div class="card detail-media">
      <?php if ((string)$product['cover_image'] !== ''): ?>
        <img src="<?= e($product['cover_image']) ?>" alt="<?= e($product['name']) ?>">
      <?php else: ?>
        <div class="cover-placeholder detail-placeholder">◎</div>
      <?php endif; ?>
    </div>
    <article class="card detail-panel">
      <div class="muted"><?= e($product['category_name'] ?: $productHeading) ?></div>
      <h1><?= e($product['name']) ?></h1>
      <div class="price" style="font-size:1.65rem">฿<?= money($product['price']) ?></div>
      <?php if ($productType === 'social'): ?>
        <div class="help">ราคาต่อ 1,000 หน่วย</div>
      <?php elseif ($productType === 'code'): ?>
        <div class="stock">สต็อกคงเหลือ <?= (int)$product['stock'] ?> ชิ้น</div>
      <?php endif; ?>

      <div class="description"><?= nl2br(e(strip_tags((string)$product['description']))) ?></div>

      <div class="purchase-box">
        <?php if (!$user): ?>
          <p>กรุณาเข้าสู่ระบบก่อนสั่งซื้อ</p>
          <a class="btn btn-primary btn-block" href="<?= e(app_url('frontend/auth/login/?redirect=' . rawurlencode((string)($_SERVER['REQUEST_URI'] ?? '')))) ?>">เข้าสู่ระบบ</a>
        <?php elseif ($productType === 'code' && (int)$product['stock'] < 1): ?>
          <button class="btn btn-ghost btn-block" type="button" disabled>สินค้าหมด</button>
        <?php else: ?>
          <form class="form-stack" method="post" action="purchase.php">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <?php if ($productType === 'refill'): ?>
              <div class="field-group">
                <label for="refill_uid">Player ID (UID)</label>
                <input class="field" id="refill_uid" name="refill_uid" required>
              </div>
              <div class="field-group">
                <label for="refill_server">เซิร์ฟเวอร์ (ถ้ามี)</label>
                <input class="field" id="refill_server" name="refill_server">
              </div>
            <?php elseif ($productType === 'social'): ?>
              <div class="field-group">
                <label for="social_link">ลิงก์เป้าหมาย</label>
                <input class="field" id="social_link" name="social_link" type="url" required placeholder="https://">
              </div>
              <div class="field-group">
                <label for="social_quantity">จำนวน</label>
                <input class="field" id="social_quantity" name="social_quantity" type="number" min="1" value="1000" required>
              </div>
            <?php endif; ?>
            <button class="btn btn-primary btn-block" type="submit" data-confirm="ยืนยันการสั่งซื้อ <?= e($product['name']) ?>?">สั่งซื้อสินค้า</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  </div>
</section>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'image' => $product['cover_image'] ?: null,
    'description' => strip_tags((string)$product['description']),
    'offers' => [
        '@type' => 'Offer',
        'price' => (float)$product['price'],
        'priceCurrency' => 'THB',
        'availability' => $productType !== 'code' || (int)$product['stock'] > 0
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
