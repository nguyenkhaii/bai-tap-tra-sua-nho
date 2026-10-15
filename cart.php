<?php
require __DIR__ . '/includes/init.php';
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$errors = [];
$old = ['name' => '', 'phone' => '', 'address' => '', 'note' => ''];

/** Lấy sản phẩm trong giỏ từ DB */
function cart_items(): array {
    $ids = array_keys($_SESSION['cart']);
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT * FROM products WHERE is_available = 1 AND id IN ($in)");
    $stmt->execute($ids);
    $items = [];
    foreach ($stmt->fetchAll() as $p) {
        $p['qty'] = $_SESSION['cart'][$p['id']];
        $p['sub'] = $p['qty'] * $p['price'];
        $items[$p['id']] = $p;
    }
    // Dọn món đã bị ẩn/xoá khỏi giỏ
    foreach ($ids as $id) if (!isset($items[$id])) unset($_SESSION['cart'][$id]);
    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $id  = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, min(20, (int)($_POST['qty'] ?? 1)));
        $st = db()->prepare('SELECT name FROM products WHERE id = ? AND is_available = 1');
        $st->execute([$id]);
        if ($name = $st->fetchColumn()) {
            $_SESSION['cart'][$id] = min(50, ($_SESSION['cart'][$id] ?? 0) + $qty);
            flash('Đã thêm "' . $name . '" vào giỏ.');
        } else {
            flash('Món này hiện không còn bán.', 'err');
        }
        $back = preg_replace('/[^a-zA-Z0-9=&%+_.-]/', '', $_POST['back'] ?? '');
        redirect(BASE_URL . '/index.php' . ($back ? '?' . $back : ''));
    }

    if ($action === 'update') {
        foreach (($_POST['qty'] ?? []) as $id => $q) {
            $q = (int)$q;
            if ($q <= 0) unset($_SESSION['cart'][(int)$id]);
            elseif (isset($_SESSION['cart'][(int)$id])) $_SESSION['cart'][(int)$id] = min(50, $q);
        }
        redirect(BASE_URL . '/cart.php');
    }

    if ($action === 'remove') {
        unset($_SESSION['cart'][(int)($_POST['product_id'] ?? 0)]);
        redirect(BASE_URL . '/cart.php');
    }

    if ($action === 'checkout') {
        $old = [
            'name'    => trim($_POST['name'] ?? ''),
            'phone'   => preg_replace('/\s+/', '', $_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'note'    => trim($_POST['note'] ?? ''),
        ];
        $items = cart_items();
        if (!$items) $errors[] = 'Giỏ hàng đang trống.';
        if ($old['name'] === '' || mb_strlen($old['name']) > 100) $errors[] = 'Vui lòng nhập họ tên (tối đa 100 ký tự).';
        if (!preg_match('/^0\d{9,10}$/', $old['phone'])) $errors[] = 'Số điện thoại phải bắt đầu bằng 0 và gồm 10–11 số.';
        if ($old['address'] === '' || mb_strlen($old['address']) > 255) $errors[] = 'Vui lòng nhập địa chỉ giao hàng.';
        if (mb_strlen($old['note']) > 255) $errors[] = 'Ghi chú tối đa 255 ký tự.';

        if (!$errors) {
            $total = array_sum(array_column($items, 'sub'));
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare('INSERT INTO orders(customer_name,phone,address,note,total) VALUES (?,?,?,?,?)')
                    ->execute([$old['name'], $old['phone'], $old['address'], $old['note'] ?: null, $total]);
                $orderId = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,price,quantity) VALUES (?,?,?,?,?)');
                foreach ($items as $p) $ins->execute([$orderId, $p['id'], $p['name'], $p['price'], $p['qty']]);
                $pdo->commit();
                $_SESSION['cart'] = [];
                flash("Đặt hàng thành công! Mã đơn của bạn là #$orderId. Bạn có thể theo dõi đơn bằng số điện thoại.");
                redirect(BASE_URL . '/track.php?phone=' . urlencode($old['phone']));
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Không thể tạo đơn lúc này. Vui lòng thử lại.';
            }
        }
    }
}

$items = cart_items();
$total = array_sum(array_column($items, 'sub'));
$title = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>
<h1>Giỏ hàng</h1>

<?php if (!$items): ?>
  <p class="empty">Giỏ hàng đang trống. <a href="<?= BASE_URL ?>/index.php">Chọn đồ uống từ thực đơn</a>.</p>
<?php else: ?>
  <form method="post" class="panel">
    <?= csrf_field() ?><input type="hidden" name="action" value="update">
    <table class="tbl">
      <thead><tr><th>Món</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $p): ?>
        <tr>
          <td><?= e($p['name']) ?></td>
          <td><?= money($p['price']) ?></td>
          <td><input type="number" name="qty[<?= $p['id'] ?>]" value="<?= $p['qty'] ?>" min="0" max="50" class="qty"></td>
          <td><?= money($p['sub']) ?></td>
          <td>
            <button class="link danger" form="rm<?= $p['id'] ?>">Bỏ</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td colspan="3">Tổng cộng</td><td colspan="2"><strong><?= money($total) ?></strong></td></tr></tfoot>
    </table>
    <button class="btn alt">Cập nhật số lượng</button>
    <small class="muted">Đặt số lượng 0 để bỏ món.</small>
  </form>
  <?php foreach ($items as $p): ?>
    <form id="rm<?= $p['id'] ?>" method="post" class="inline">
      <?= csrf_field() ?><input type="hidden" name="action" value="remove">
      <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
    </form>
  <?php endforeach; ?>

  <h2>Thông tin nhận hàng</h2>
  <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" class="panel form">
    <?= csrf_field() ?><input type="hidden" name="action" value="checkout">
    <label>Họ và tên <input name="name" value="<?= e($old['name']) ?>" required maxlength="100"></label>
    <label>Số điện thoại <input name="phone" value="<?= e($old['phone']) ?>" required inputmode="numeric" placeholder="0901234567"></label>
    <label>Địa chỉ giao hàng <input name="address" value="<?= e($old['address']) ?>" required maxlength="255"></label>
    <label>Ghi chú (tuỳ chọn) <input name="note" value="<?= e($old['note']) ?>" maxlength="255" placeholder="Ít đá, 50% đường..."></label>
    <button class="btn">Đặt hàng · <?= money($total) ?></button>
  </form>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>