<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
require_once dirname(__DIR__, 3) . '/backend/api/mail.php';

$mailboxId = max(0, (int)($_GET['id'] ?? 0));
$boxStmt = db()->prepare(
    'SELECT m.*, p.name AS package_name, h.name AS host_name, h.webmail_url
     FROM mail_boxes m
     JOIN mail_packages p ON p.id = m.package_id
     JOIN mail_hosts h ON h.id = m.host_id
     WHERE m.id = ? AND m.tenant_id = ? AND m.user_id = ? AND m.status != "deleted"
     LIMIT 1'
);
$boxStmt->execute([$mailboxId, tenantId(), $user['id']]);
$box = $boxStmt->fetch();
if (!$box) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ไม่พบกล่องอีเมลนี้'];
    legacy_redirect('frontend/products/mail-rental/');
}

$query = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id, from_addr, from_name, subject, otp_code, received_at, is_read
        FROM mail_messages WHERE mailbox_id = ? AND tenant_id = ?';
$params = [$mailboxId, tenantId()];
if ($query !== '') {
    $sql .= ' AND (subject LIKE ? OR from_addr LIKE ? OR from_name LIKE ?)';
    $like = '%' . $query . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY received_at DESC';
$messagesStmt = db()->prepare($sql);
$messagesStmt->execute($params);
$messages = $messagesStmt->fetchAll();
$password = mail_decrypt((string)$box['secret']);

$pageTitle = 'กล่องเมล ' . $box['email'];
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div>
      <a class="muted" href="./">← กลับรายการกล่องเมล</a>
      <h1><?= e($box['email']) ?></h1>
      <p class="muted"><?= e($box['host_name']) ?> · หมดอายุ <?= e($box['expires_at']) ?></p>
    </div>
    <form method="post" action="refresh.php"><?= csrf_field() ?><input type="hidden" name="mailbox_id" value="<?= $mailboxId ?>"><button class="btn btn-primary" type="submit">ดึงอีเมลใหม่</button></form>
  </div>

  <details class="card" style="padding:1rem;margin-bottom:1rem">
    <summary style="cursor:pointer;font-weight:700">ข้อมูลเข้าสู่ระบบเว็บเมล</summary>
    <div class="form-row" style="margin-top:1rem">
      <div><div class="muted">อีเมล</div><div class="code-box"><?= e($box['email']) ?></div></div>
      <div><div class="muted">รหัสผ่าน</div><div class="code-box"><?= e($password) ?></div></div>
    </div>
    <?php if ((string)$box['webmail_url'] !== ''): ?><a class="btn btn-ghost" style="margin-top:1rem" href="<?= e($box['webmail_url']) ?>" target="_blank" rel="noopener">เปิดเว็บเมล</a><?php endif; ?>
  </details>

  <form class="filters" method="get">
    <input type="hidden" name="id" value="<?= $mailboxId ?>">
    <input class="field" type="search" name="q" value="<?= e($query) ?>" placeholder="ค้นหาหัวข้อหรือผู้ส่ง">
    <button class="btn btn-ghost" type="submit">ค้นหา</button>
  </form>

  <?php if ($messages): ?>
    <div class="legacy-table-wrap">
      <table class="legacy-table">
        <thead><tr><th>ผู้ส่ง</th><th>หัวข้อ</th><th>OTP</th><th>เวลา</th></tr></thead>
        <tbody>
        <?php foreach ($messages as $message): ?>
          <tr>
            <td><?= e($message['from_name'] ?: $message['from_addr']) ?></td>
            <td><a href="message.php?box=<?= $mailboxId ?>&id=<?= (int)$message['id'] ?>"><strong><?= e($message['subject'] ?: '(ไม่มีหัวข้อ)') ?></strong></a></td>
            <td><?= e($message['otp_code'] ?: '-') ?></td>
            <td><?= e($message['received_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card empty-state"><p class="muted">ยังไม่มีข้อความในกล่องนี้</p></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
