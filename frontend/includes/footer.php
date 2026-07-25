<?php declare(strict_types=1); ?>
</main>
<footer class="legacy-footer">
  <div class="container-x footer-grid">
    <div>
      <strong><?= e((string)setting('site_name', 'GameStore')) ?></strong>
      <p class="muted"><?= e((string)setting('site_tagline', 'บริการออนไลน์ครบวงจร')) ?></p>
    </div>
    <div class="footer-links">
      <a href="<?= e(app_url('frontend/contact/')) ?>">ติดต่อเรา</a>
      <a href="<?= e(app_url('frontend/account/orders/')) ?>">ประวัติคำสั่งซื้อ</a>
      <a href="<?= e(app_url('frontend/account/topup/')) ?>">เติมเงิน</a>
    </div>
  </div>
  <div class="container-x muted footer-copy"><?= e((string)setting('site_footer', '© ' . date('Y') . ' GameStore')) ?></div>
</footer>
<script src="<?= e(app_url('frontend/assets/js/site.js')) ?>" defer></script>
</body>
</html>
