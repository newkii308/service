<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
if (currentUser()) {
    legacy_redirect('frontend/home/');
}
$pageTitle = 'สมัครสมาชิก';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <form class="card form-card form-stack" method="post" action="../register.php">
    <div><h1 style="margin:0">สมัครสมาชิก</h1><p class="muted">สร้างบัญชีเพื่อซื้อสินค้าและใช้กระเป๋าเงิน</p></div>
    <?= csrf_field() ?>
    <div class="field-group"><label for="username">ชื่อผู้ใช้</label><input class="field" id="username" name="username" minlength="3" autocomplete="username" required></div>
    <div class="field-group"><label for="email">อีเมล</label><input class="field" id="email" name="email" type="email" autocomplete="email" required></div>
    <div class="field-group"><label for="password">รหัสผ่าน</label><input class="field" id="password" name="password" type="password" minlength="6" autocomplete="new-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">สร้างบัญชี</button>
  </form>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
