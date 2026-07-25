<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
$pageTitle = 'ติดต่อเรา';
$contacts = [
    ['label' => 'อีเมล', 'value' => (string)setting('contact_email', ''), 'href' => 'mailto:' . (string)setting('contact_email', '')],
    ['label' => 'โทรศัพท์', 'value' => (string)setting('contact_phone', ''), 'href' => 'tel:' . (string)setting('contact_phone', '')],
    ['label' => 'LINE', 'value' => (string)setting('contact_line', ''), 'href' => (string)setting('contact_line', '')],
    ['label' => 'Facebook', 'value' => (string)setting('contact_facebook', ''), 'href' => (string)setting('contact_facebook', '')],
];
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>ติดต่อทีมงาน</h1><p class="muted">เลือกช่องทางที่สะดวกสำหรับคำถามเรื่องสินค้าและคำสั่งซื้อ</p></div></div>
  <div class="module-grid">
    <?php foreach ($contacts as $contact): if ($contact['value'] === '') continue; ?>
      <a class="card card-hover module-card" href="<?= e($contact['href']) ?>" target="<?= str_starts_with($contact['href'], 'http') ? '_blank' : '_self' ?>" rel="noopener">
        <div class="module-icon">•</div><div><h3><?= e($contact['label']) ?></h3><p class="muted" style="word-break:break-word"><?= e($contact['value']) ?></p></div>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
