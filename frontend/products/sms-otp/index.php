<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
require_once dirname(__DIR__, 3) . '/backend/api/supplier.php';

$pageTitle = 'เช่าเบอร์ SMS OTP';
$pageDescription = 'เช่าเบอร์มือถือเสมือนเพื่อรับ SMS OTP';
$catalog = [];
$catalogError = '';
if (setting('sms_otp_enabled', '1') === '1') {
    try {
        $rawCatalog = supplier_get_sms_products();
        if (is_array($rawCatalog)) {
            $globalMarkup = (float)setting('sms_otp_markup', '2.00');
            $overrides = json_decode((string)setting('sms_otp_markup_overrides', '{}'), true) ?: [];
            foreach ($rawCatalog as $item) {
                if (empty($item['product'])) {
                    continue;
                }
                $name = (string)$item['product'];
                $item['final_price'] = (float)($item['point'] ?? 0) + (float)($overrides[$name] ?? $globalMarkup);
                $catalog[] = $item;
            }
        }
    } catch (Throwable $error) {
        $catalogError = APP_DEBUG ? $error->getMessage() : 'ไม่สามารถโหลดรายการจากคู่ค้าได้';
    }
}

require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div>
      <h1>เช่าเบอร์ SMS OTP</h1>
      <p class="muted">เลือกบริการและประเทศ แต่ละรายการทำงานแยกจากสินค้าโมดูลอื่น</p>
    </div>
    <span class="balance-pill chip">ยอดเงิน ฿<?= money($user['balance']) ?></span>
  </div>

  <?php if (setting('sms_otp_enabled', '1') !== '1'): ?>
    <div class="card empty-state"><h2>ปิดบริการชั่วคราว</h2><p class="muted">ผู้ดูแลกำลังปรับปรุงระบบ SMS OTP</p></div>
  <?php elseif ($catalogError !== ''): ?>
    <div class="flash flash-danger"><?= e($catalogError) ?></div>
  <?php elseif (!$catalog): ?>
    <div class="card empty-state"><h2>ยังไม่มีเบอร์พร้อมให้บริการ</h2></div>
  <?php else: ?>
    <div class="legacy-table-wrap">
      <table class="legacy-table">
        <thead><tr><th>บริการ</th><th>ประเทศ</th><th>คงเหลือ</th><th>ราคา</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($catalog as $item): ?>
          <tr>
            <td><strong><?= e($item['product']) ?></strong></td>
            <td><?= e($item['location'] ?? '-') ?></td>
            <td><?= (int)($item['stock'] ?? 0) ?></td>
            <td class="price">฿<?= money($item['final_price']) ?></td>
            <td>
              <form method="post" action="purchase.php">
                <?= csrf_field() ?>
                <input type="hidden" name="product" value="<?= e($item['product']) ?>">
                <input type="hidden" name="location" value="<?= e($item['location'] ?? '') ?>">
                <button class="btn btn-primary" type="submit" <?= (int)($item['stock'] ?? 0) < 1 ? 'disabled' : '' ?> data-confirm="ยืนยันเช่าเบอร์สำหรับ <?= e($item['product']) ?>?">เช่าเบอร์</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
