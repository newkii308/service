<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin_page();
$pageTitle = 'ตั้งค่าร้าน';
require dirname(__DIR__) . '/includes/header.php';
$toggles = [
    'sales_enabled' => 'เปิดการขายทั้งหมด',
    'code_sales_enabled' => 'โค้ดและไอดีเกม',
    'refill_sales_enabled' => 'เติมเกม',
    'streaming_enabled' => 'Streaming',
    'social_enabled' => 'Social Boost',
    'sms_otp_enabled' => 'SMS OTP',
    'otp_enabled' => 'OTP แอป',
];
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ตั้งค่าร้าน</h1><p class="muted">ข้อมูลแบรนด์ ช่องทางติดต่อ การขาย และการเชื่อมต่อ</p></div></div>
  <form class="card form-card form-stack" method="post" action="save.php" style="max-width:860px">
    <?= csrf_field() ?>
    <div class="form-row">
      <div class="field-group"><label>ชื่อร้าน</label><input class="field" name="site_name" value="<?= e(setting('site_name', '')) ?>" required></div>
      <div class="field-group"><label>คำโปรย</label><input class="field" name="site_tagline" value="<?= e(setting('site_tagline', '')) ?>"></div>
    </div>
    <div class="form-row">
      <div class="field-group"><label>โลโก้ URL</label><input class="field" name="site_logo" type="url" value="<?= e(setting('site_logo', '')) ?>"></div>
      <div class="field-group"><label>แบนเนอร์ URL</label><input class="field" name="site_banner" type="url" value="<?= e(setting('site_banner', '')) ?>"></div>
    </div>
    <div class="form-row">
      <div class="field-group"><label>อีเมลติดต่อ</label><input class="field" name="contact_email" type="email" value="<?= e(setting('contact_email', '')) ?>"></div>
      <div class="field-group"><label>โทรศัพท์</label><input class="field" name="contact_phone" value="<?= e(setting('contact_phone', '')) ?>"></div>
      <div class="field-group"><label>LINE</label><input class="field" name="contact_line" value="<?= e(setting('contact_line', '')) ?>"></div>
      <div class="field-group"><label>Facebook URL</label><input class="field" name="contact_facebook" value="<?= e(setting('contact_facebook', '')) ?>"></div>
    </div>
    <div class="form-row">
      <div class="field-group"><label>เบอร์รับซอง TrueMoney</label><input class="field" name="truemoney_phone" value="<?= e(setting('truemoney_phone', '')) ?>"></div>
      <div class="field-group"><label>Supplier API Key</label><input class="field" name="supplier_api_key" type="password" value="<?= e(setting('supplier_api_key', '')) ?>"></div>
      <div class="field-group"><label>MeeLike API Key</label><input class="field" name="meelike_api_key" type="password" value="<?= e(setting('meelike_api_key', '')) ?>"></div>
    </div>
    <div class="card" style="padding:1rem">
      <strong>สถานะการขาย</strong>
      <div class="form-row" style="margin-top:.75rem">
        <?php foreach ($toggles as $key => $label): ?><label><input type="hidden" name="<?= e($key) ?>" value="0"><input type="checkbox" name="<?= e($key) ?>" value="1" <?= setting($key, '1') === '1' ? 'checked' : '' ?>> <?= e($label) ?></label><?php endforeach; ?>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">บันทึกการตั้งค่า</button>
  </form>

  <form class="card form-card form-stack" method="post" action="change-pin.php" style="max-width:560px;margin-top:1.5rem">
    <h2 style="margin:0">เปลี่ยน PIN หลังบ้าน</h2>
    <?= csrf_field() ?>
    <div class="field-group"><label>PIN ปัจจุบัน</label><input class="field" name="current_pin" inputmode="numeric" pattern="\d{6}" required></div>
    <div class="field-group"><label>PIN ใหม่ 6 หลัก</label><input class="field" name="new_pin" inputmode="numeric" pattern="\d{6}" required></div>
    <button class="btn btn-ghost" type="submit">เปลี่ยน PIN</button>
  </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
