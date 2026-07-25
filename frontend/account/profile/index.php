<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
$pageTitle = 'โปรไฟล์';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>โปรไฟล์</h1><p class="muted">ข้อมูลบัญชีและทางลัดส่วนตัว</p></div></div>
  <div class="form-row">
    <article class="card detail-panel">
      <div class="muted">ชื่อผู้ใช้</div><h2><?= e($user['username']) ?></h2>
      <div class="muted">อีเมล</div><p><?= e($user['email']) ?></p>
      <div class="muted">ยอดเงินคงเหลือ</div><div class="price" style="font-size:2rem">฿<?= money($user['balance']) ?></div>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(app_url('frontend/account/topup/')) ?>">เติมเงิน</a>
        <a class="btn btn-ghost" href="<?= e(app_url('frontend/account/settings/')) ?>">ตั้งค่าบัญชี</a>
      </div>
    </article>
    <article class="card detail-panel">
      <h2>บริการของฉัน</h2>
      <div class="form-stack">
        <a class="btn btn-ghost btn-block" href="<?= e(app_url('frontend/account/orders/')) ?>">ประวัติคำสั่งซื้อ</a>
        <a class="btn btn-ghost btn-block" href="<?= e(app_url('frontend/products/mail-rental/')) ?>">กล่องอีเมล</a>
        <a class="btn btn-ghost btn-block" href="<?= e(app_url('frontend/products/sms-otp/')) ?>">เช่าเบอร์ SMS OTP</a>
      </div>
    </article>
  </div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
