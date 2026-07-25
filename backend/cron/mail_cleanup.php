<?php
/**
 * cron/mail_cleanup.php
 * ตั้ง Cron Job ให้รันไฟล์นี้ทุก 1 ชั่วโมง เพื่อลบกล่องอีเมลที่หมดอายุอัตโนมัติ
 * (ทั้งบนโฮสจริงและในฐานข้อมูล) — วนทุกร้าน (tenant) ในระบบ
 *
 * ตัวอย่างใน DirectAdmin > Cron Jobs:
 *   0 * * * *  php -q /home/USERNAME/public_html/cron/mail_cleanup.php
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/core.php';
require_once __DIR__ . '/../api/mail.php';

$pdo = db();
$tenants = $pdo->query('SELECT id FROM tenants')->fetchAll();
if (!$tenants) $tenants = [['id' => 1]];

$totalExpired = 0;

foreach ($tenants as $t) {
    $tid = (int)$t['id'];

    $stmt = $pdo->prepare(
        "SELECT * FROM mail_boxes WHERE tenant_id = ? AND status = 'active' AND expires_at < NOW()"
    );
    $stmt->execute([$tid]);
    $expired = $stmt->fetchAll();

    foreach ($expired as $mailbox) {
        try {
            $hStmt = $pdo->prepare('SELECT * FROM mail_hosts WHERE id = ? AND tenant_id = ? LIMIT 1');
            $hStmt->execute([$mailbox['host_id'], $tid]);
            $host = $hStmt->fetch();

            if ($host) {
                [$local, $domain] = explode('@', $mailbox['email'], 2);
                mail_host_delete_email($host, $local, $domain);
            }

            $pdo->prepare('DELETE FROM mail_messages WHERE mailbox_id = ?')->execute([$mailbox['id']]);
            $pdo->prepare('UPDATE mail_boxes SET status = "expired" WHERE id = ?')->execute([$mailbox['id']]);
            $totalExpired++;
        } catch (Throwable $e) {
            error_log('[MailCleanup] tenant ' . $tid . ' mailbox ' . $mailbox['id'] . ': ' . $e->getMessage());
        }
    }
}

echo "[MailCleanup] ลบกล่องอีเมลหมดอายุแล้ว: {$totalExpired} กล่อง\n";
