<?php
require __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare('SELECT image FROM products WHERE id = ?');
    $st->execute([$id]);
    if ($row = $st->fetch()) {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        if ($row['image'] && is_file(__DIR__ . '/../uploads/' . $row['image'])) {
            unlink(__DIR__ . '/../uploads/' . $row['image']);
        }
        flash('Đã xoá món. Các đơn cũ vẫn giữ nguyên tên và giá lúc đặt.');
    }
    redirect('products.php');
}

$products = db()->query('SELECT p.*, c.name AS cat_name FROM products p
                         LEFT JOIN categories c ON c.id = p.category_id
                         ORDER BY c.name, p.name')->fetchAll();

$title = 'Quản lý món';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1>Quản lý món</h1>
<p><a class="btn" href="product_form.php">Thêm món mới</a></p>

<?php if (!$products): ?>
  <p class="empty">Chưa có món nào. Bấm "Thêm món mới" để tạo món đầu tiên.</p>
<?php else: ?>
<div class="scroll">
<table class="tbl">
  <thead><tr><th>Ảnh</th><th>Tên món</th><th>Nhóm</th><th>Giá</th><th>Đang bán</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><?php if ($p['image']): ?><img class="thumb" src="<?= BASE_URL ?>/uploads/<?= e($p['image']) ?>" alt=""><?php endif; ?></td>
      <td><?= e($p['name']) ?></td>
      <td><?= e($p['cat_name'] ?? 'Khác') ?></td>
      <td><?= money($p['price']) ?></td>
      <td><?= $p['is_available'] ? 'Có' : 'Ẩn' ?></td>
      <td class="actions">
        <a href="product_form.php?id=<?= $p['id'] ?>">Sửa</a>
        <form method="post" class="inline" onsubmit="return confirm('Xoá món này?');">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>">
          <button class="link danger">Xoá</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>