<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_member_page();
$stmt = db()->prepare('SELECT name, webmail_url FROM mail_hosts WHERE tenant_id = ? AND is_active = 1 AND webmail_url IS NOT NULL AND webmail_url != "" ORDER BY name');
$stmt->execute([tenantId()]);
$hosts = $stmt->fetchAll();
$pageTitle = 'เว็บเมลภายนอก';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>เว็บเมลภายนอก</h1><p class="muted">เปิดหน้าเข้าสู่ระบบของโฮสที่เชื่อมต่อไว้</p></div></div>
  <div class="module-grid">
    <?php foreach ($hosts as $host): ?>
      <a class="card card-hover module-card" href="<?= e($host['webmail_url']) ?>" target="_blank" rel="noopener">
        <div class="module-icon">✉</div><div><h3><?= e($host['name']) ?></h3><p class="muted">เปิดเว็บเมลในแท็บใหม่</p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
