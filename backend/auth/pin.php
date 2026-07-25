<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$admin = require_admin_page(false);
if (adminPinVerified()) legacy_redirect('backend/dashboard/');
$pageTitle = 'ยืนยัน PIN หลังบ้าน';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <form class="card form-card form-stack" method="post" action="verify-pin.php">
    <div><h1 style="margin:0">ยืนยัน PIN หลังบ้าน</h1><p class="muted">กรอกรหัสตัวเลข 6 หลักก่อนจัดการข้อมูลสำคัญ</p></div>
    <?= csrf_field() ?>
    <div class="field-group"><label for="pin">PIN</label><input class="field" id="pin" name="pin" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus></div>
    <button class="btn btn-primary" type="submit">ยืนยัน</button>
  </form>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
