<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
if (currentUser()) {
    legacy_redirect('frontend/home/');
}
$pageTitle = 'เข้าสู่ระบบ';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <form class="card form-card form-stack" method="post" action="../login.php">
    <div><h1 style="margin:0">เข้าสู่ระบบ</h1><p class="muted">ใช้ชื่อผู้ใช้หรืออีเมล</p></div>
    <?= csrf_field() ?>
    <div class="field-group"><label for="username">ชื่อผู้ใช้หรืออีเมล</label><input class="field" id="username" name="username" autocomplete="username" required></div>
    <div class="field-group"><label for="password">รหัสผ่าน</label><input class="field" id="password" name="password" type="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary btn-block" type="submit">เข้าสู่ระบบ</button>
    <p class="muted" style="text-align:center">ยังไม่มีบัญชี? <a href="<?= e(app_url('frontend/auth/register/')) ?>" style="color:var(--primary)">สมัครสมาชิก</a></p>
  </form>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
