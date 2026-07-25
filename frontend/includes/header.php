<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? (string)setting('site_tagline', 'ร้านค้าออนไลน์ ส่งมอบอัตโนมัติ');
$currentUser = currentUser();
$siteName = (string)setting('site_name', 'GameStore');
$siteLogo = (string)setting('site_logo', '');
$flash = pull_flash();
$primary = safe_hex((string)setting('color_primary', '#6366f1'), '#6366f1');
$primaryHover = safe_hex((string)setting('color_primary_hover', '#4f46e5'), '#4f46e5');
$accent = safe_hex((string)setting('color_accent', '#06b6d4'), '#06b6d4');
$radius = max(8, min(32, (int)setting('border_radius', '18')));
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e(page_title($pageTitle)) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="theme-color" content="<?= e($primary) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Kanit:wght@600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(app_url('frontend/assets/css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(app_url('frontend/assets/css/legacy.css')) ?>">
  <?php if ((string)setting('site_favicon', '') !== ''): ?>
    <link rel="icon" href="<?= e((string)setting('site_favicon')) ?>">
  <?php endif; ?>
  <style>:root{--primary:<?= e($primary) ?>;--primary-2:<?= e($primaryHover) ?>;--accent:<?= e($accent) ?>;--radius:<?= $radius ?>px}</style>
</head>
<body>
<header class="legacy-header">
  <div class="legacy-nav container-x">
    <a class="brand" href="<?= e(app_url('frontend/home/')) ?>">
      <?php if ($siteLogo !== ''): ?><img src="<?= e($siteLogo) ?>" alt="" width="38" height="38"><?php endif; ?>
      <span><?= e($siteName) ?></span>
    </a>
    <button class="nav-toggle" type="button" aria-label="เปิดเมนู" aria-expanded="false" data-nav-toggle>☰</button>
    <nav class="nav-links" data-nav>
      <a href="<?= e(app_url('frontend/home/')) ?>">หน้าแรก</a>
      <details>
        <summary>สินค้าและบริการ</summary>
        <div class="nav-dropdown">
          <a href="<?= e(app_url('frontend/products/game-codes/')) ?>">โค้ดและไอดีเกม</a>
          <a href="<?= e(app_url('frontend/products/game-refills/')) ?>">เติมเกม</a>
          <a href="<?= e(app_url('frontend/products/streaming-accounts/')) ?>">Streaming</a>
          <a href="<?= e(app_url('frontend/products/social-boost/')) ?>">Social Boost</a>
          <a href="<?= e(app_url('frontend/products/sms-otp/')) ?>">SMS OTP</a>
          <a href="<?= e(app_url('frontend/products/app-otp/')) ?>">OTP แอป</a>
          <a href="<?= e(app_url('frontend/products/mail-rental/')) ?>">เช่าอีเมล</a>
        </div>
      </details>
      <a href="<?= e(app_url('frontend/search/')) ?>">ค้นหา</a>
      <a href="<?= e(app_url('frontend/contact/')) ?>">ติดต่อ</a>
      <?php if ($currentUser): ?>
        <a href="<?= e(app_url('frontend/account/orders/')) ?>">คำสั่งซื้อ</a>
        <a class="balance-pill" href="<?= e(app_url('frontend/account/topup/')) ?>">฿<?= money($currentUser['balance']) ?></a>
        <a href="<?= e(app_url('frontend/account/profile/')) ?>"><?= e($currentUser['username']) ?></a>
        <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
          <a href="<?= e(app_url('backend/dashboard/')) ?>">หลังบ้าน</a>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('frontend/auth/logout.php')) ?>" class="inline-form">
          <?= csrf_field() ?>
          <button class="link-button" type="submit">ออกจากระบบ</button>
        </form>
      <?php else: ?>
        <a href="<?= e(app_url('frontend/auth/login/')) ?>">เข้าสู่ระบบ</a>
        <a class="btn btn-primary" href="<?= e(app_url('frontend/auth/register/')) ?>">สมัครสมาชิก</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($flash): ?>
  <div class="container-x"><div class="flash flash-<?= e($flash['type'] ?? 'info') ?>" role="status"><?= e($flash['message'] ?? '') ?></div></div>
<?php endif; ?>
<main class="legacy-main">
