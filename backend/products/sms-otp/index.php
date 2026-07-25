<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin_page();
$pageTitle = 'ตั้งค่า SMS OTP';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>SMS OTP</h1><p class="muted">โมดูลแยกสำหรับราคา สถานะ และการเชื่อมต่อคู่ค้า</p></div><span class="status <?= setting('sms_otp_enabled', '1') === '1' ? 'status-active' : 'status-cancelled' ?>"><?= setting('sms_otp_enabled', '1') === '1' ? 'เปิดบริการ' : 'ปิดบริการ' ?></span></div>
  <div class="form-row">
    <form class="card form-card form-stack" method="post" action="save-markup.php">
      <h2 style="margin:0">กำไรต่อรายการ</h2>
      <?= csrf_field() ?>
      <div class="field-group"><label for="sms_otp_markup">บวกเพิ่มจากราคาคู่ค้า (บาท)</label><input class="field" id="sms_otp_markup" name="sms_otp_markup" type="number" min="0" step="0.01" value="<?= e(setting('sms_otp_markup', '2.00')) ?>"></div>
      <button class="btn btn-primary" type="submit">บันทึกราคา</button>
    </form>
    <article class="card form-card">
      <h2 style="margin:0 0 .7rem">การเชื่อมต่อ</h2>
      <p class="muted">API Key ของคู่ค้าเก็บในหน้าตั้งค่าหลัก เพื่อไม่ปะปนกับสินค้าประเภทอื่น</p>
      <a class="btn btn-ghost" href="<?= e(app_url('backend/settings/')) ?>">เปิดหน้าตั้งค่า API</a>
      <a class="btn btn-ghost" href="<?= e(app_url('frontend/products/sms-otp/')) ?>">ดูหน้าลูกค้า</a>
    </article>
  </div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
