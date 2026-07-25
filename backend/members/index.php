<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$admin = require_admin_page();
$stmt = db()->prepare('SELECT id, username, email, balance, role, is_active, created_at FROM users WHERE tenant_id = ? ORDER BY id DESC');
$stmt->execute([tenantId()]);
$members = $stmt->fetchAll();
$pageTitle = 'จัดการสมาชิก';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>สมาชิก</h1><p class="muted">ปรับยอดและสถานะ เฉพาะผู้ใช้ในร้านปัจจุบัน</p></div></div>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>สมาชิก</th><th>ยอดเงิน</th><th>สถานะ</th><th>ปรับยอด</th><th></th></tr></thead><tbody>
  <?php foreach ($members as $member): ?><tr><td><strong><?= e($member['username']) ?></strong><div class="muted"><?= e($member['email']) ?> · <?= e($member['role']) ?></div></td><td>฿<?= money($member['balance']) ?></td><td><span class="status <?= (int)$member['is_active'] === 1 ? 'status-active' : 'status-cancelled' ?>"><?= (int)$member['is_active'] === 1 ? 'ใช้งาน' : 'ระงับ' ?></span></td><td><form class="split-actions" method="post" action="adjust.php"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$member['id'] ?>"><input class="field" style="width:120px" name="amount" type="number" step="0.01" placeholder="+ / -" required><button class="btn btn-ghost" type="submit">ปรับยอด</button></form></td><td><?php if ($member['role'] !== 'admin' && (int)$member['id'] !== (int)$admin['id']): ?><form method="post" action="toggle.php"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$member['id'] ?>"><button class="btn btn-ghost" type="submit">เปิด/ระงับ</button></form><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
