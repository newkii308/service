<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = require_member_page();
$stmt = db()->prepare(
    "SELECT o.*, COALESCE(p.type, 'code') AS product_type
     FROM orders o
     LEFT JOIN products p ON p.id = o.product_id AND p.tenant_id = o.tenant_id
     WHERE o.user_id = ? AND o.tenant_id = ?
     ORDER BY o.id DESC"
);
$stmt->execute([$user['id'], tenantId()]);
$orders = $stmt->fetchAll();
$pageTitle = 'ประวัติคำสั่งซื้อ';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading">
    <div><h1>ประวัติคำสั่งซื้อ</h1><p class="muted">รายการทั้งหมดของบัญชี <?= e($user['username']) ?></p></div>
    <a class="btn btn-primary" href="<?= e(app_url('frontend/home/')) ?>">เลือกซื้อเพิ่ม</a>
  </div>
  <?php if ($orders): ?>
    <div class="legacy-table-wrap">
      <table class="legacy-table">
        <thead><tr><th>เลขที่</th><th>สินค้า</th><th>ยอดเงิน</th><th>สถานะ/ข้อมูลส่งมอบ</th><th>วันที่</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td>#<?= (int)$order['id'] ?></td>
            <td><strong><?= e($order['product_name']) ?></strong>
              <?php if ((string)$order['refill_uid'] !== ''): ?><div class="muted">UID: <?= e($order['refill_uid']) ?> <?= e($order['refill_server']) ?></div><?php endif; ?>
            </td>
            <td>฿<?= money($order['price']) ?></td>
            <td>
              <span class="status status-<?= e($order['status']) ?>"><?= e($order['status']) ?></span>
              <?php if ((string)$order['code_content'] !== ''): ?><details style="margin-top:.5rem"><summary>ดูรายละเอียด</summary><div class="code-box" style="margin-top:.5rem"><?= e($order['code_content']) ?></div></details><?php endif; ?>
              <?php if ($order['status'] === 'processing' && $order['product_id'] === null && (string)$order['api_order_id'] !== ''): ?>
                <div class="split-actions" style="margin-top:.5rem">
                  <form method="post" action="<?= e(app_url('frontend/products/sms-otp/status.php')) ?>"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-ghost" type="submit">เช็ก OTP</button></form>
                  <form method="post" action="<?= e(app_url('frontend/products/sms-otp/cancel.php')) ?>"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันยกเลิกและขอคืนเงิน?">ยกเลิก</button></form>
                </div>
              <?php endif; ?>
            </td>
            <td><?= e($order['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card empty-state"><h2>ยังไม่มีคำสั่งซื้อ</h2><p class="muted">สินค้าและบริการที่ซื้อจะแสดงที่นี่</p></div>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
