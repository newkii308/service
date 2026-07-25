<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
$boxId = max(0, (int)($_GET['box'] ?? 0));
$messageId = max(0, (int)($_GET['id'] ?? 0));
$stmt = db()->prepare(
    'SELECT mm.*, mb.email
     FROM mail_messages mm
     JOIN mail_boxes mb ON mb.id = mm.mailbox_id
     WHERE mm.id = ? AND mm.mailbox_id = ? AND mm.tenant_id = ?
       AND mb.user_id = ? AND mb.status != "deleted"
     LIMIT 1'
);
$stmt->execute([$messageId, $boxId, tenantId(), $user['id']]);
$message = $stmt->fetch();
if (!$message) {
    http_response_code(404);
    exit('ไม่พบข้อความ');
}
db()->prepare('UPDATE mail_messages SET is_read = 1 WHERE id = ?')->execute([$messageId]);
$pageTitle = (string)($message['subject'] ?: 'ข้อความอีเมล');
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <article class="card detail-panel">
    <a class="muted" href="inbox.php?id=<?= $boxId ?>">← กลับกล่อง <?= e($message['email']) ?></a>
    <h1><?= e($message['subject'] ?: '(ไม่มีหัวข้อ)') ?></h1>
    <p class="muted">จาก <?= e($message['from_name'] ?: $message['from_addr']) ?> · <?= e($message['received_at']) ?></p>
    <?php if ((string)$message['otp_code'] !== ''): ?><div class="flash flash-success">รหัส OTP: <strong><?= e($message['otp_code']) ?></strong></div><?php endif; ?>
    <div class="description"><?= nl2br(e(strip_tags((string)($message['body_text'] ?: $message['body_html'])))) ?></div>
  </article>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
