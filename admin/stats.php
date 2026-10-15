<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$type = ($_GET['type'] ?? 'month') === 'week' ? 'week' : 'month';
if ($type === 'week') {
    $expr  = 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)'; // thứ Hai đầu tuần
    $since = 'DATE_SUB(CURDATE(), INTERVAL 12 WEEK)';
} else {
    $expr  = "DATE_FORMAT(created_at, '%Y-%m-01')";
    $since = 'DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
}

// Chỉ tính đơn chưa bị huỷ
$rows = db()->query("SELECT $expr AS period, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue
                     FROM orders WHERE status <> 'cancelled' AND created_at >= $since
                     GROUP BY period ORDER BY period DESC")->fetchAll();

$top = db()->query("SELECT oi.product_name, SUM(oi.quantity) AS qty
                    FROM order_items oi JOIN orders o ON o.id = oi.order_id
                    WHERE o.status <> 'cancelled' AND o.created_at >= $since
                    GROUP BY oi.product_name ORDER BY qty DESC LIMIT 5")->fetchAll();

$byStatus = db()->query('SELECT status, COUNT(*) c FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);

$maxRev = max(1, ...array_map('intval', array_column($rows, 'revenue') ?: [1]));
$sumRev = array_sum(array_column($rows, 'revenue'));
$sumOrd = array_sum(array_column($rows, 'orders'));

$title = 'Thống kê';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1>Thống kê đơn hàng</h1>
<div class="tabs">
  <a href="?type=week"  class="<?= $type === 'week'  ? 'on' : '' ?>">Theo tuần (12 tuần gần nhất)</a>
  <a href="?type=month" class="<?= $type === 'month' ? 'on' : '' ?>">Theo tháng (12 tháng gần nhất)</a>
</div>

<div class="summary">
  <div><span class="muted">Số đơn</span><strong><?= number_format($sumOrd) ?></strong></div>
  <div><span class="muted">Doanh thu</span><strong><?= money($sumRev) ?></strong></div>
  <?php foreach (STATUSES as $k => $label): ?>
    <div><span class="muted"><?= e($label) ?></span><strong><?= (int)($byStatus[$k] ?? 0) ?></strong></div>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <p class="empty">Chưa có đơn nào trong khoảng thời gian này.</p>
<?php else: ?>
<table class="tbl">
  <thead><tr><th><?= $type === 'week' ? 'Tuần bắt đầu' : 'Tháng' ?></th><th>Số đơn</th><th>Doanh thu</th><th class="bar-col"></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= $type === 'week' ? date('d/m/Y', strtotime($r['period'])) : date('m/Y', strtotime($r['period'])) ?></td>
      <td><?= (int)$r['orders'] ?></td>
      <td><?= money($r['revenue']) ?></td>
      <td class="bar-col"><div class="bar" style="width: <?= round($r['revenue'] / $maxRev * 100) ?>%"></div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<h2>Món bán chạy trong giai đoạn này</h2>
<ol class="top">
  <?php foreach ($top as $t): ?>
    <li><?= e($t['product_name']) ?> <span class="muted">— <?= (int)$t['qty'] ?> ly</span></li>
  <?php endforeach; ?>
</ol>
<p class="muted">Doanh thu và số đơn không tính đơn đã huỷ.</p>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>