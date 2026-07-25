<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();

$packagesStmt = db()->prepare(
    'SELECT p.*, h.name AS host_name, h.webmail_url
     FROM mail_packages p
     JOIN mail_hosts h ON h.id = p.host_id AND h.tenant_id = p.tenant_id
     WHERE p.tenant_id = ? AND p.is_active = 1 AND h.is_active = 1
     ORDER BY p.sort_order, p.id'
);
$packagesStmt->execute([tenantId()]);
$packages = $packagesStmt->fetchAll();

$boxesStmt = db()->prepare(
    'SELECT m.id, m.email, m.status, m.expires_at, m.last_synced_at, m.created_at,
            p.name AS package_name, h.name AS host_name, h.webmail_url
     FROM mail_boxes m
     JOIN mail_packages p ON p.id = m.package_id
     JOIN mail_hosts h ON h.id = m.host_id
     WHERE m.tenant_id = ? AND m.user_id = ? AND m.status != "deleted"
     ORDER BY m.created_at DESC'
);
$boxesStmt->execute([tenantId(), $user['id']]);
$boxes = $boxesStmt->fetchAll();

$pageTitle = 'เช่าอีเมล';
$pageDescription = 'เช่ากล่องอีเมล รับ OTP และข้อความยืนยัน';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div>
      <h1>เช่าอีเมล</h1>
      <p class="muted">แพ็กเกจ กล่องเมล และการจัดการทั้งหมดอยู่ในโฟลเดอร์ mail-rental</p>
    </div>
    <span class="balance-pill chip">ยอดเงิน ฿<?= money($user['balance']) ?></span>
  </div>

  <h2>แพ็กเกจที่พร้อมให้บริการ</h2>
  <?php if ($packages): ?>
    <div class="module-grid" style="margin-bottom:2.5rem">
      <?php foreach ($packages as $package): ?>
        <article class="card module-card">
          <div>
            <span class="chip"><?= e($package['host_name']) ?></span>
            <h3><?= e($package['name']) ?></h3>
            <p class="muted">@<?= e($package['domain']) ?> · <?= (int)$package['days'] ?> วัน · <?= (int)$package['quota_mb'] ?> MB</p>
          </div>
          <form method="post" action="purchase.php">
            <?= csrf_field() ?>
            <input type="hidden" name="package_id" value="<?= (int)$package['id'] ?>">
            <button class="btn btn-primary btn-block" type="submit" data-confirm="ยืนยันเช่าอีเมลแพ็กเกจ <?= e($package['name']) ?>?">เช่า ฿<?= money($package['price']) ?></button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="card empty-state" style="margin-bottom:2rem"><p class="muted">ยังไม่มีแพ็กเกจที่เปิดขาย</p></div>
  <?php endif; ?>

  <div class="page-heading">
    <div><h2>กล่องอีเมลของฉัน</h2></div>
    <a class="btn btn-ghost" href="webmail.php">เว็บเมลภายนอก</a>
  </div>
  <?php if ($boxes): ?>
    <div class="legacy-table-wrap">
      <table class="legacy-table">
        <thead><tr><th>อีเมล</th><th>แพ็กเกจ</th><th>หมดอายุ</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($boxes as $box): ?>
          <tr>
            <td><strong><?= e($box['email']) ?></strong><div class="muted"><?= e($box['host_name']) ?></div></td>
            <td><?= e($box['package_name']) ?></td>
            <td><?= e($box['expires_at']) ?></td>
            <td><span class="status status-<?= e($box['status']) ?>"><?= e($box['status']) ?></span></td>
            <td>
              <div class="split-actions">
                <a class="btn btn-ghost" href="inbox.php?id=<?= (int)$box['id'] ?>">เปิดกล่อง</a>
                <form method="post" action="refresh.php"><?= csrf_field() ?><input type="hidden" name="mailbox_id" value="<?= (int)$box['id'] ?>"><button class="btn btn-ghost" type="submit">รีเฟรช</button></form>
                <form method="post" action="delete.php"><?= csrf_field() ?><input type="hidden" name="mailbox_id" value="<?= (int)$box['id'] ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันลบกล่องอีเมลนี้?">ลบ</button></form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card empty-state"><p class="muted">คุณยังไม่มีกล่องอีเมล</p></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
