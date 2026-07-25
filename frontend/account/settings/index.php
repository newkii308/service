<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
$pageTitle = 'ตั้งค่าบัญชี';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ตั้งค่าบัญชี</h1><p class="muted">อัปเดตอีเมลและรหัสผ่านด้วยคนละสคริปต์</p></div></div>
  <div class="form-row">
    <form class="card form-card form-stack" method="post" action="update-profile.php">
      <h2 style="margin:0">เปลี่ยนอีเมล</h2>
      <?= csrf_field() ?>
      <div class="field-group"><label for="email">อีเมล</label><input class="field" id="email" name="email" type="email" value="<?= e($user['email']) ?>" required></div>
      <button class="btn btn-primary" type="submit">บันทึกอีเมล</button>
    </form>
    <form class="card form-card form-stack" method="post" action="change-password.php">
      <h2 style="margin:0">เปลี่ยนรหัสผ่าน</h2>
      <?= csrf_field() ?>
      <div class="field-group"><label for="current">รหัสผ่านปัจจุบัน</label><input class="field" id="current" name="current" type="password" required></div>
      <div class="field-group"><label for="new">รหัสผ่านใหม่</label><input class="field" id="new" name="new" type="password" minlength="6" required></div>
      <button class="btn btn-primary" type="submit">เปลี่ยนรหัสผ่าน</button>
    </form>
  </div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
