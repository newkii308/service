<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin_page();
$stmt = db()->prepare('SELECT * FROM categories WHERE tenant_id = ? ORDER BY sort_order, name');
$stmt->execute([tenantId()]);
$categories = $stmt->fetchAll();
$edit = ['id'=>0,'name'=>'','cover_image'=>'','sort_order'=>0];
if (!empty($_GET['id'])) foreach ($categories as $category) if ((int)$category['id'] === (int)$_GET['id']) $edit = $category;
$pageTitle = 'จัดการหมวดหมู่';
require dirname(__DIR__) . '/includes/header.php';
?>
<section class="container-x page-shell">
  <div class="page-heading"><div><h1>หมวดหมู่</h1><p class="muted">หมวดหมู่เป็นข้อมูลร่วม แต่สินค้าแต่ละประเภทยังแยกคนละโมดูล</p></div></div>
  <form class="card form-card form-stack" method="post" action="save.php" style="max-width:760px;margin-bottom:1.5rem">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="form-row"><div class="field-group"><label>ชื่อหมวดหมู่</label><input class="field" name="name" value="<?= e($edit['name']) ?>" required></div><div class="field-group"><label>ลำดับ</label><input class="field" name="sort_order" type="number" value="<?= (int)$edit['sort_order'] ?>"></div></div>
    <div class="field-group"><label>URL รูปปก</label><input class="field" name="cover_image" type="url" value="<?= e($edit['cover_image']) ?>"></div>
    <button class="btn btn-primary" type="submit">บันทึกหมวดหมู่</button>
  </form>
  <div class="legacy-table-wrap"><table class="legacy-table"><thead><tr><th>ID</th><th>ชื่อ</th><th>Slug</th><th>ลำดับ</th><th></th></tr></thead><tbody><?php foreach ($categories as $category): ?><tr><td>#<?= (int)$category['id'] ?></td><td><strong><?= e($category['name']) ?></strong></td><td><?= e($category['slug']) ?></td><td><?= (int)$category['sort_order'] ?></td><td><div class="split-actions"><a class="btn btn-ghost" href="?id=<?= (int)$category['id'] ?>">แก้ไข</a><form method="post" action="delete.php"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><button class="btn btn-ghost" type="submit" data-confirm="ยืนยันลบหมวดหมู่นี้?">ลบ</button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
