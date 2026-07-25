<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = '';
$pageDescription = (string)setting('site_tagline', 'บริการออนไลน์ครบวงจร');
$banner = (string)setting('site_banner', '');
$countsStmt = db()->prepare('SELECT type, COUNT(*) AS total FROM products WHERE tenant_id = ? AND is_active = 1 GROUP BY type');
$countsStmt->execute([tenantId()]);
$counts = [];
foreach ($countsStmt as $row) {
    $counts[(string)$row['type']] = (int)$row['total'];
}
$modules = [
    ['folder' => 'game-codes', 'icon' => '🎮', 'title' => 'โค้ดและไอดีเกม', 'desc' => 'ส่งมอบโค้ดอัตโนมัติจากสต็อก', 'count' => $counts['code'] ?? 0],
    ['folder' => 'game-refills', 'icon' => '⚡', 'title' => 'เติมเกม', 'desc' => 'เติมด้วย UID แยกตามเซิร์ฟเวอร์', 'count' => $counts['refill'] ?? 0],
    ['folder' => 'streaming-accounts', 'icon' => '▶', 'title' => 'Streaming', 'desc' => 'บัญชีและแพ็กเกจพรีเมียม', 'count' => $counts['streaming'] ?? 0],
    ['folder' => 'social-boost', 'icon' => '↗', 'title' => 'Social Boost', 'desc' => 'ผู้ติดตาม ไลก์ วิว และเอนเกจเมนต์', 'count' => $counts['social'] ?? 0],
    ['folder' => 'sms-otp', 'icon' => '#', 'title' => 'SMS OTP', 'desc' => 'เช่าเบอร์รับข้อความชั่วคราว', 'count' => null],
    ['folder' => 'app-otp', 'icon' => '🔐', 'title' => 'OTP แอป', 'desc' => 'Netflix, Disney+ และ YouKu', 'count' => $counts['otp'] ?? 0],
    ['folder' => 'mail-rental', 'icon' => '✉', 'title' => 'เช่าอีเมล', 'desc' => 'กล่องเมลสำหรับรับรหัสยืนยัน', 'count' => null],
];

require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x hero-legacy">
  <div>
    <span class="chip">พร้อมให้บริการตลอด 24 ชั่วโมง</span>
    <h1><?= e((string)setting('site_name', 'GameStore')) ?></h1>
    <p class="muted" style="font-size:1.1rem;max-width:650px"><?= e((string)setting('site_tagline', 'บริการออนไลน์ครบวงจร ส่งมอบอัตโนมัติ')) ?></p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= e(app_url('frontend/products/game-codes/')) ?>">เลือกซื้อสินค้า</a>
      <a class="btn btn-ghost" href="<?= e(app_url('frontend/account/topup/')) ?>">เติมเงินเข้าระบบ</a>
    </div>
  </div>
  <div class="card hero-card">
    <?php if ($banner !== ''): ?>
      <img src="<?= e($banner) ?>" alt="<?= e((string)setting('site_name', 'GameStore')) ?>">
    <?php else: ?>
      <div class="cover-placeholder" style="aspect-ratio:16/10">STORE</div>
    <?php endif; ?>
  </div>
</section>

<section class="container-x">
  <div class="page-heading">
    <div>
      <h2>สินค้าแยกเป็นโมดูลชัดเจน</h2>
      <p class="muted">แต่ละบริการมีหน้า รายละเอียด และการทำรายการของตัวเอง</p>
    </div>
  </div>
  <div class="module-grid">
    <?php foreach ($modules as $module): ?>
      <a class="card card-hover module-card" href="<?= e(app_url('frontend/products/' . $module['folder'] . '/')) ?>">
        <div class="module-icon"><?= e($module['icon']) ?></div>
        <div>
          <h3><?= e($module['title']) ?></h3>
          <p class="muted"><?= e($module['desc']) ?></p>
          <?php if ($module['count'] !== null): ?><span class="chip" style="margin-top:.7rem"><?= (int)$module['count'] ?> รายการ</span><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
