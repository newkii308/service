<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin_page();
$stmt = db()->prepare('SELECT t.*, u.username FROM topups t JOIN users u ON u.id = t.user_id WHERE t.tenant_id = ? ORDER BY t.id DESC LIMIT 300');
$stmt->execute([tenantId()]);
$topups = $stmt->fetchAll();
$pageTitle = 'ประวัติเติมเงิน';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ประวัติเติมเงิน</h1><p class="muted">ซอง TrueMoney และรายการปรับยอดโดยแอดมิน</p></div></div>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>เลขที่</th><th>สมาชิก</th><th>ยอด</th><th>สถานะ</th><th>รายละเอียด</th><th>วันที่</th></tr></thead><tbody>
  <?php foreach ($topups as $topup): ?><tr><td>#<?= (int)$topup['id'] ?></td><td><?= e($topup['username']) ?></td><td>฿<?= money($topup['amount']) ?></td><td><span class="status status-<?= e($topup['status']) ?>"><?= e($topup['status']) ?></span></td><td><?= e($topup['message']) ?></td><td><?= e($topup['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
