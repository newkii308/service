<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'หลังบ้าน';
$admin = currentUser();
$flash = pull_flash();
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(page_title($pageTitle)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Kanit:wght@600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(app_url('frontend/assets/css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(app_url('frontend/assets/css/legacy.css')) ?>">
</head>
<body class="backend-body">
<header class="legacy-header">
  <div class="legacy-nav container-x">
    <a class="brand" href="<?= e(app_url('backend/dashboard/')) ?>"><span class="admin-mark">A</span><span>หลังบ้าน <?= e((string)setting('site_name', 'GameStore')) ?></span></a>
    <button class="nav-toggle" type="button" aria-label="เปิดเมนู" aria-expanded="false" data-nav-toggle>☰</button>
    <nav class="nav-links" data-nav>
      <a href="<?= e(app_url('backend/dashboard/')) ?>">ภาพรวม</a>
      <details>
        <summary>สินค้าแยกประเภท</summary>
        <div class="nav-dropdown">
          <a href="<?= e(app_url('backend/products/game-codes/')) ?>">โค้ดและไอดีเกม</a>
          <a href="<?= e(app_url('backend/products/game-refills/')) ?>">เติมเกม</a>
          <a href="<?= e(app_url('backend/products/streaming-accounts/')) ?>">Streaming</a>
          <a href="<?= e(app_url('backend/products/social-boost/')) ?>">Social Boost</a>
          <a href="<?= e(app_url('backend/products/sms-otp/')) ?>">SMS OTP</a>
          <a href="<?= e(app_url('backend/products/app-otp/')) ?>">OTP แอป</a>
          <a href="<?= e(app_url('backend/products/mail-rental/')) ?>">เช่าอีเมล</a>
        </div>
      </details>
      <a href="<?= e(app_url('backend/categories/')) ?>">หมวดหมู่</a>
      <a href="<?= e(app_url('backend/orders/')) ?>">ออเดอร์</a>
      <a href="<?= e(app_url('backend/members/')) ?>">สมาชิก</a>
      <a href="<?= e(app_url('backend/settings/')) ?>">ตั้งค่า</a>
      <a href="<?= e(app_url('frontend/home/')) ?>">ดูหน้าร้าน</a>
      <span class="chip"><?= e($admin['username'] ?? '') ?></span>
    </nav>
  </div>
</header>
<?php if ($flash): ?><div class="container-x"><div class="flash flash-<?= e($flash['type'] ?? 'info') ?>"><?= e($flash['message'] ?? '') ?></div></div><?php endif; ?>
<main class="legacy-main">
