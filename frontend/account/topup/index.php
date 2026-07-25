<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
$stmt = db()->prepare('SELECT id, amount, status, message, created_at FROM topups WHERE user_id = ? AND tenant_id = ? ORDER BY id DESC LIMIT 50');
$stmt->execute([$user['id'], tenantId()]);
$topups = $stmt->fetchAll();
$pageTitle = 'เติมเงิน';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>เติมเงินเข้ากระเป๋า</h1><p class="muted">วางลิงก์ซองอั่งเปาทรูมันนี่ ระบบตรวจสอบและบวกยอดให้อัตโนมัติ</p></div><span class="balance-pill chip">ยอดปัจจุบัน ฿<?= money($user['balance']) ?></span></div>
  <form class="card form-card form-stack" method="post" action="redeem.php" style="margin-bottom:2rem">
    <?= csrf_field() ?>
    <div class="field-group"><label for="voucher">ลิงก์ซองอั่งเปา</label><input class="field" id="voucher" name="voucher" type="url" required placeholder="https://gift.truemoney.com/campaign/?v=..."></div>
    <button class="btn btn-primary btn-block" type="submit">เติมเงิน</button>
  </form>
  <h2>ประวัติการเติมเงิน</h2>
  <div class="legacy-table-wrap">
    <table class="legacy-table"><thead><tr><th>เลขที่</th><th>ยอด</th><th>สถานะ</th><th>รายละเอียด</th><th>วันที่</th></tr></thead>
      <tbody><?php foreach ($topups as $topup): ?><tr><td>#<?= (int)$topup['id'] ?></td><td>฿<?= money($topup['amount']) ?></td><td><span class="status status-<?= e($topup['status']) ?>"><?= e($topup['status']) ?></span></td><td><?= e($topup['message']) ?></td><td><?= e($topup['created_at']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
  </div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
