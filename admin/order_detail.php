<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $new = $_POST['status'] ?? '';
    if (isset(STATUSES[$new])) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$new, $id]);
        flash('Đã cập nhật trạng thái đơn #' . $id . ' thành "' . STATUSES[$new] . '".');
    }
    redirect('order_detail.php?id=' . $id);
}

$st = db()->prepare('SELECT * FROM orders WHERE id = ?');
$st->execute([$id]);
$o = $st->fetch();
if (!$o) { http_response_code(404); exit('Không tìm thấy đơn hàng.'); }
$it = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();

$title = 'Đơn #' . $id;
include __DIR__ . '/../includes/admin_header.php';
?>
<p><a href="orders.php">← Danh sách đơn</a></p>
<h1>Đơn #<?= $o['id'] ?> <span class="badge st-<?= e($o['status']) ?>"><?= e(STATUSES[$o['status']]) ?></span></h1>

<section class="panel">
  <p><strong><?= e($o['customer_name']) ?></strong> · <?= e($o['phone']) ?></p>
  <p>Địa chỉ: <?= e($o['address']) ?></p>
  <?php if ($o['note']): ?><p>Ghi chú: <?= e($o['note']) ?></p><?php endif; ?>
  <p class="muted">Đặt lúc <?= date('H:i d/m/Y', strtotime($o['created_at'])) ?></p>
</section>

<table class="tbl">
  <thead><tr><th>Món</th><th>Đơn giá</th><th>SL</th><th>Thành tiền</th></tr></thead>
  <tbody>
  <?php foreach ($items as $i): ?>
    <tr><td><?= e($i['product_name']) ?></td><td><?= money($i['price']) ?></td>
        <td><?= (int)$i['quantity'] ?></td><td><?= money($i['price'] * $i['quantity']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot><tr><td colspan="3">Tổng cộng</td><td><strong><?= money($o['total']) ?></strong></td></tr></tfoot>
</table>

<form method="post" class="panel form">
  <?= csrf_field() ?>
  <label>Cập nhật trạng thái
    <select name="status">
      <?php foreach (STATUSES as $k => $label): ?>
        <option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn">Lưu trạng thái</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>