<?php
require __DIR__ . '/includes/init.php';

$phone  = preg_replace('/\s+/', '', $_GET['phone'] ?? '');
$orders = [];
$error  = '';
$searched = isset($_GET['phone']);

if ($searched) {
    if (!preg_match('/^0\d{9,10}$/', $phone)) {
        $error = 'Số điện thoại không hợp lệ. Nhập 10–11 số, bắt đầu bằng 0.';
    } else {
        $st = db()->prepare('SELECT * FROM orders WHERE phone = ? ORDER BY created_at DESC LIMIT 20');
        $st->execute([$phone]);
        $orders = $st->fetchAll();
        $itSt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        foreach ($orders as &$o) { $itSt->execute([$o['id']]); $o['items'] = $itSt->fetchAll(); }
        unset($o);
    }
}

$title = 'Tra cứu đơn hàng';
include __DIR__ . '/includes/header.php';
?>
<h1>Tra cứu đơn hàng</h1>
<form method="get" class="search">
  <input name="phone" value="<?= e($phone) ?>" placeholder="Số điện thoại đã đặt hàng" inputmode="numeric" required>
  <button class="btn">Tra cứu</button>
</form>

<?php if ($error): ?><div class="flash err"><?= e($error) ?></div>
<?php elseif ($searched && !$orders): ?>
  <p class="empty">Chưa có đơn nào với số <?= e($phone) ?>. Kiểm tra lại số điện thoại hoặc <a href="<?= BASE_URL ?>/index.php">đặt đồ uống</a>.</p>
<?php endif; ?>

<?php foreach ($orders as $o): ?>
  <section class="panel order">
    <header>
      <strong>Đơn #<?= $o['id'] ?></strong>
      <span class="badge st-<?= e($o['status']) ?>"><?= e(STATUSES[$o['status']]) ?></span>
      <span class="muted"><?= date('H:i d/m/Y', strtotime($o['created_at'])) ?></span>
    </header>
    <ul>
      <?php foreach ($o['items'] as $it): ?>
        <li><?= (int)$it['quantity'] ?> × <?= e($it['product_name']) ?> <span class="muted">(<?= money($it['price']) ?>)</span></li>
      <?php endforeach; ?>
    </ul>
    <p>Giao đến: <?= e($o['address']) ?><?= $o['note'] ? ' · Ghi chú: ' . e($o['note']) : '' ?></p>
    <p><strong>Tổng: <?= money($o['total']) ?></strong></p>
  </section>
<?php endforeach; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>