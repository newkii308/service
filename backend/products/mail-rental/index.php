<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin_page();

$hostsStmt = db()->prepare('SELECT id, name, driver, api_url, api_username, verify_ssl, imap_host, imap_port, imap_flags, webmail_url, is_active FROM mail_hosts WHERE tenant_id = ? ORDER BY id DESC');
$hostsStmt->execute([tenantId()]);
$hosts = $hostsStmt->fetchAll();
$packagesStmt = db()->prepare('SELECT p.*, h.name AS host_name FROM mail_packages p JOIN mail_hosts h ON h.id = p.host_id WHERE p.tenant_id = ? ORDER BY p.sort_order, p.id');
$packagesStmt->execute([tenantId()]);
$packages = $packagesStmt->fetchAll();
$boxesStmt = db()->prepare("SELECT m.id, m.email, m.status, m.expires_at, u.username, p.name AS package_name FROM mail_boxes m JOIN users u ON u.id = m.user_id JOIN mail_packages p ON p.id = m.package_id WHERE m.tenant_id = ? AND m.status != 'deleted' ORDER BY m.id DESC LIMIT 200");
$boxesStmt->execute([tenantId()]);
$boxes = $boxesStmt->fetchAll();

$editHost = null;
if (!empty($_GET['host'])) {
    foreach ($hosts as $host) if ((int)$host['id'] === (int)$_GET['host']) $editHost = $host;
}
$editPackage = null;
if (!empty($_GET['package'])) {
    foreach ($packages as $package) if ((int)$package['id'] === (int)$_GET['package']) $editPackage = $package;
}
$editHost = $editHost ?: ['id'=>0,'name'=>'','driver'=>'directadmin','api_url'=>'','api_username'=>'','verify_ssl'=>1,'imap_host'=>'','imap_port'=>993,'imap_flags'=>'/imap/ssl/novalidate-cert','webmail_url'=>'','is_active'=>1];
$editPackage = $editPackage ?: ['id'=>0,'host_id'=>'','name'=>'','domain'=>'','days'=>7,'price'=>'0.00','quota_mb'=>100,'description'=>'','is_active'=>1,'sort_order'=>0];

$pageTitle = 'จัดการเช่าอีเมล';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>เช่าอีเมล</h1><p class="muted">โฮส แพ็กเกจ และกล่องลูกค้าอยู่ในโมดูล mail-rental เท่านั้น</p></div><a class="btn btn-ghost" href="<?= e(app_url('frontend/products/mail-rental/')) ?>">ดูหน้าลูกค้า</a></div>
  <div class="form-row">
    <form class="card form-card form-stack" method="post" action="save-host.php">
      <h2 style="margin:0"><?= (int)$editHost['id'] > 0 ? 'แก้ไขโฮส' : 'เพิ่มโฮสเมล' ?></h2>
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$editHost['id'] ?>">
      <div class="form-row"><div class="field-group"><label>ชื่อโฮส</label><input class="field" name="name" value="<?= e($editHost['name']) ?>" required></div><div class="field-group"><label>Driver</label><select class="field" name="driver"><option value="directadmin" <?= $editHost['driver'] === 'directadmin' ? 'selected' : '' ?>>DirectAdmin</option><option value="cpanel" <?= $editHost['driver'] === 'cpanel' ? 'selected' : '' ?>>cPanel</option></select></div></div>
      <div class="field-group"><label>API URL</label><input class="field" name="api_url" type="url" value="<?= e($editHost['api_url']) ?>" required></div>
      <div class="form-row"><div class="field-group"><label>API Username</label><input class="field" name="api_username" value="<?= e($editHost['api_username']) ?>" required></div><div class="field-group"><label>API Secret <?= (int)$editHost['id'] > 0 ? '(เว้นว่างเพื่อใช้ค่าเดิม)' : '' ?></label><input class="field" name="api_secret" type="password" <?= (int)$editHost['id'] < 1 ? 'required' : '' ?>></div></div>
      <div class="form-row"><div class="field-group"><label>IMAP Host</label><input class="field" name="imap_host" value="<?= e($editHost['imap_host']) ?>" required></div><div class="field-group"><label>Port</label><input class="field" name="imap_port" type="number" value="<?= (int)$editHost['imap_port'] ?>"></div></div>
      <div class="field-group"><label>IMAP Flags</label><input class="field" name="imap_flags" value="<?= e($editHost['imap_flags']) ?>"></div>
      <div class="field-group"><label>Webmail URL</label><input class="field" name="webmail_url" type="url" value="<?= e($editHost['webmail_url']) ?>"></div>
      <label><input type="hidden" name="verify_ssl" value="0"><input type="checkbox" name="verify_ssl" value="1" <?= (int)$editHost['verify_ssl'] === 1 ? 'checked' : '' ?>> ตรวจสอบ SSL</label>
      <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" <?= (int)$editHost['is_active'] === 1 ? 'checked' : '' ?>> เปิดใช้งาน</label>
      <button class="btn btn-primary" type="submit">บันทึกโฮส</button>
    </form>

    <form class="card form-card form-stack" method="post" action="save-package.php">
      <h2 style="margin:0"><?= (int)$editPackage['id'] > 0 ? 'แก้ไขแพ็กเกจ' : 'เพิ่มแพ็กเกจ' ?></h2>
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$editPackage['id'] ?>">
      <div class="field-group"><label>โฮส</label><select class="field" name="host_id" required><option value="">เลือกโฮส</option><?php foreach ($hosts as $host): ?><option value="<?= (int)$host['id'] ?>" <?= (int)$editPackage['host_id'] === (int)$host['id'] ? 'selected' : '' ?>><?= e($host['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-row"><div class="field-group"><label>ชื่อแพ็กเกจ</label><input class="field" name="name" value="<?= e($editPackage['name']) ?>" required></div><div class="field-group"><label>โดเมน</label><input class="field" name="domain" value="<?= e($editPackage['domain']) ?>" required></div></div>
      <div class="form-row"><div class="field-group"><label>จำนวนวัน</label><input class="field" name="days" type="number" min="1" value="<?= (int)$editPackage['days'] ?>"></div><div class="field-group"><label>ราคา</label><input class="field" name="price" type="number" min="0" step="0.01" value="<?= e($editPackage['price']) ?>"></div></div>
      <div class="form-row"><div class="field-group"><label>Quota MB</label><input class="field" name="quota_mb" type="number" min="10" value="<?= (int)$editPackage['quota_mb'] ?>"></div><div class="field-group"><label>ลำดับ</label><input class="field" name="sort_order" type="number" value="<?= (int)$editPackage['sort_order'] ?>"></div></div>
      <div class="field-group"><label>รายละเอียด</label><textarea class="field" name="description"><?= e($editPackage['description']) ?></textarea></div>
      <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" <?= (int)$editPackage['is_active'] === 1 ? 'checked' : '' ?>> เปิดขาย</label>
      <button class="btn btn-primary" type="submit">บันทึกแพ็กเกจ</button>
    </form>
  </div>

  <h2 style="margin-top:2.5rem">โฮสเมล</h2>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>โฮส</th><th>API/IMAP</th><th>สถานะ</th><th></th></tr></thead><tbody><?php foreach ($hosts as $host): ?><tr><td><strong><?= e($host['name']) ?></strong><div class="muted"><?= e($host['driver']) ?></div></td><td><?= e($host['api_url']) ?><div class="muted"><?= e($host['imap_host']) ?>:<?= (int)$host['imap_port'] ?></div></td><td><span class="status <?= (int)$host['is_active'] === 1 ? 'status-active' : 'status-cancelled' ?>"><?= (int)$host['is_active'] === 1 ? 'active' : 'inactive' ?></span></td><td><div class="split-actions"><a class="btn btn-ghost" href="?host=<?= (int)$host['id'] ?>">แก้ไข</a><form method="post" action="test-host.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$host['id'] ?>"><button class="btn btn-ghost" type="submit">ทดสอบ</button></form><form method="post" action="delete-host.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$host['id'] ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันลบโฮสนี้?">ลบ</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>

  <h2 style="margin-top:2.5rem">แพ็กเกจ</h2>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>แพ็กเกจ</th><th>โฮส/โดเมน</th><th>อายุ</th><th>ราคา</th><th></th></tr></thead><tbody><?php foreach ($packages as $package): ?><tr><td><strong><?= e($package['name']) ?></strong></td><td><?= e($package['host_name']) ?><div class="muted">@<?= e($package['domain']) ?></div></td><td><?= (int)$package['days'] ?> วัน</td><td>฿<?= money($package['price']) ?></td><td><div class="split-actions"><a class="btn btn-ghost" href="?package=<?= (int)$package['id'] ?>">แก้ไข</a><form method="post" action="delete-package.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$package['id'] ?>"><button class="btn btn-ghost" type="submit">ปิดขาย</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>

  <h2 style="margin-top:2.5rem">กล่องเมลลูกค้า</h2>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>อีเมล</th><th>สมาชิก</th><th>แพ็กเกจ</th><th>หมดอายุ</th><th>สถานะ</th></tr></thead><tbody><?php foreach ($boxes as $box): ?><tr><td><?= e($box['email']) ?></td><td><?= e($box['username']) ?></td><td><?= e($box['package_name']) ?></td><td><?= e($box['expires_at']) ?></td><td><span class="status status-<?= e($box['status']) ?>"><?= e($box['status']) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
