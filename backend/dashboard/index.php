<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$admin = require_admin_page();

$stats = [];
foreach ([
    'members' => "SELECT COUNT(*) FROM users WHERE tenant_id = ? AND role = 'member'",
    'orders' => 'SELECT COUNT(*) FROM orders WHERE tenant_id = ?',
    'revenue' => "SELECT COALESCE(SUM(price),0) FROM orders WHERE tenant_id = ? AND status != 'cancelled'",
    'topups' => "SELECT COALESCE(SUM(amount),0) FROM topups WHERE tenant_id = ? AND status = 'success'",
    'products' => 'SELECT COUNT(*) FROM products WHERE tenant_id = ?',
] as $key => $sql) {
    $stmt = db()->prepare($sql);
    $stmt->execute([tenantId()]);
    $stats[$key] = $stmt->fetchColumn();
}
$byTypeStmt = db()->prepare('SELECT type, COUNT(*) AS total FROM products WHERE tenant_id = ? GROUP BY type');
$byTypeStmt->execute([tenantId()]);
$byType = [];
foreach ($byTypeStmt as $row) $byType[$row['type']] = (int)$row['total'];

$pageTitle = 'ภาพรวมหลังบ้าน';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ภาพรวมหลังบ้าน</h1><p class="muted">ข้อมูลของร้านปัจจุบัน แยกตาม tenant</p></div><span class="status status-active">PIN ยืนยันแล้ว</span></div>
  <div class="module-grid">
    <article class="card module-card"><div class="module-icon">฿</div><div><h3>ยอดขาย</h3><div class="price">฿<?= money($stats['revenue']) ?></div></div></article>
    <article class="card module-card"><div class="module-icon">#</div><div><h3>คำสั่งซื้อ</h3><div class="price"><?= number_format((int)$stats['orders']) ?></div></div></article>
    <article class="card module-card"><div class="module-icon">U</div><div><h3>สมาชิก</h3><div class="price"><?= number_format((int)$stats['members']) ?></div></div></article>
    <article class="card module-card"><div class="module-icon">+</div><div><h3>ยอดเติมเงิน</h3><div class="price">฿<?= money($stats['topups']) ?></div></div></article>
  </div>
  <div class="page-heading" style="margin-top:2.5rem"><div><h2>โมดูลสินค้า</h2><p class="muted">จัดการแต่ละประเภทในโฟลเดอร์ของตัวเอง</p></div></div>
  <div class="module-grid">
    <?php foreach (['code','refill','streaming','social','otp'] as $type): $config = backend_product_config($type); ?>
      <a class="card card-hover module-card" href="<?= e(app_url('backend/products/' . $config['folder'] . '/')) ?>"><div class="module-icon"><?= e(strtoupper(substr($type, 0, 1))) ?></div><div><h3><?= e($config['title']) ?></h3><p class="muted"><?= (int)($byType[$type] ?? 0) ?> รายการ</p></div></a>
    <?php endforeach; ?>
    <a class="card card-hover module-card" href="<?= e(app_url('backend/products/sms-otp/')) ?>"><div class="module-icon">S</div><div><h3>SMS OTP</h3><p class="muted">ตั้งค่าราคาและคู่ค้า</p></div></a>
    <a class="card card-hover module-card" href="<?= e(app_url('backend/products/mail-rental/')) ?>"><div class="module-icon">M</div><div><h3>เช่าอีเมล</h3><p class="muted">โฮส แพ็กเกจ และกล่องเมล</p></div></a>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
