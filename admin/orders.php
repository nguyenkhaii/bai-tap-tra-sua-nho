<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$status = $_GET['status'] ?? '';
$sql = 'SELECT * FROM orders';
$params = [];
if (isset(STATUSES[$status])) { $sql .= ' WHERE status = ?'; $params[] = $status; }
$sql .= ' ORDER BY created_at DESC LIMIT 100';
$st = db()->prepare($sql);
$st->execute($params);
$orders = $st->fetchAll();

$counts = db()->query('SELECT status, COUNT(*) c FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$title = 'Đơn hàng';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1>Đơn hàng</h1>
<div class="tabs">
  <a href="orders.php" class="<?= $status === '' ? 'on' : '' ?>">Tất cả</a>
  <?php foreach (STATUSES as $k => $label): ?>
    <a href="?status=<?= $k ?>" class="<?= $status === $k ? 'on' : '' ?>"><?= e($label) ?> (<?= (int)($counts[$k] ?? 0) ?>)</a>
  <?php endforeach; ?>
</div>

<?php if (!$orders): ?>
  <p class="empty">Chưa có đơn hàng nào ở mục này.</p>
<?php else: ?>
<div class="scroll">
<table class="tbl">
  <thead><tr><th>Mã</th><th>Thời gian</th><th>Khách hàng</th><th>SĐT</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td>#<?= $o['id'] ?></td>
      <td><?= date('H:i d/m/Y', strtotime($o['created_at'])) ?></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= e($o['phone']) ?></td>
      <td><?= money($o['total']) ?></td>
      <td><span class="badge st-<?= e($o['status']) ?>"><?= e(STATUSES[$o['status']]) ?></span></td>
      <td><a href="order_detail.php?id=<?= $o['id'] ?>">Chi tiết</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>