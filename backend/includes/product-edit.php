<?php
declare(strict_types=1);
$config = backend_product_config($productType);
$id = max(0, (int)($_GET['id'] ?? 0));
$product = [
    'id' => 0, 'name' => '', 'category_id' => '', 'price' => '0.00',
    'description' => '', 'cover_image' => '', 'api_type_id' => '',
    'otp_service' => '', 'otp_type' => '', 'sort_order' => 0, 'is_active' => 1,
];
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ? AND tenant_id = ? AND type = ? LIMIT 1');
    $stmt->execute([$id, tenantId(), $productType]);
    $found = $stmt->fetch();
    if (!$found) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ไม่พบสินค้าในโมดูลนี้'];
        legacy_redirect('backend/products/' . $config['folder'] . '/');
    }
    $product = array_merge($product, $found);
}
$categoryStmt = db()->prepare('SELECT id, name FROM categories WHERE tenant_id = ? ORDER BY sort_order, name');
$categoryStmt->execute([tenantId()]);
$categories = $categoryStmt->fetchAll();
$pageTitle = ($id > 0 ? 'แก้ไข ' : 'เพิ่ม ') . $config['title'];
require __DIR__ . '/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><a class="muted" href="./">← กลับรายการ</a><h1><?= e($pageTitle) ?></h1></div></div>
  <form class="card form-card form-stack" method="post" action="save.php" style="max-width:760px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
    <div class="form-row">
      <div class="field-group"><label for="name">ชื่อสินค้า</label><input class="field" id="name" name="name" value="<?= e($product['name']) ?>" required></div>
      <div class="field-group"><label for="price">ราคา</label><input class="field" id="price" name="price" type="number" min="0" step="0.01" value="<?= e($product['price']) ?>" required></div>
    </div>
    <div class="form-row">
      <div class="field-group"><label for="category_id">หมวดหมู่</label><select class="field" id="category_id" name="category_id"><option value="">ไม่ระบุ</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field-group"><label for="sort_order">ลำดับ</label><input class="field" id="sort_order" name="sort_order" type="number" value="<?= (int)$product['sort_order'] ?>"></div>
    </div>
    <?php if (in_array($productType, ['streaming','social'], true)): ?>
      <div class="field-group"><label for="api_type_id">รหัสสินค้า/บริการคู่ค้า</label><input class="field" id="api_type_id" name="api_type_id" value="<?= e($product['api_type_id']) ?>"></div>
    <?php endif; ?>
    <?php if ($productType === 'otp'): ?>
      <div class="form-row">
        <div class="field-group"><label for="otp_service">บริการ</label><select class="field" id="otp_service" name="otp_service"><option value="">เลือกบริการ</option><?php foreach (['netflix','disney','youku'] as $service): ?><option value="<?= $service ?>" <?= $product['otp_service'] === $service ? 'selected' : '' ?>><?= e(ucfirst($service)) ?></option><?php endforeach; ?></select></div>
        <div class="field-group"><label for="otp_type">ประเภท OTP</label><input class="field" id="otp_type" name="otp_type" value="<?= e($product['otp_type']) ?>"></div>
      </div>
    <?php endif; ?>
    <div class="field-group"><label for="cover_image">URL รูปปก</label><input class="field" id="cover_image" name="cover_image" type="url" value="<?= e($product['cover_image']) ?>"></div>
    <div class="field-group"><label for="description">รายละเอียด</label><textarea class="field" id="description" name="description"><?= e($product['description']) ?></textarea></div>
    <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" <?= (int)$product['is_active'] === 1 ? 'checked' : '' ?>> เปิดขาย</label>
    <button class="btn btn-primary" type="submit">บันทึก <?= e($config['title']) ?></button>
  </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
