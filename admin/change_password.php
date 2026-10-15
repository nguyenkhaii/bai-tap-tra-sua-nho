<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = $_POST['current'] ?? '';
    $new     = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $st = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    $admin = $st->fetch();

    if (!$admin || !password_verify($current, $admin['password_hash'])) {
        $errors[] = 'Mật khẩu hiện tại không đúng.';
        usleep(500000); // làm chậm dò mật khẩu
    }
    if (mb_strlen($new) < 8) {
        $errors[] = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Mật khẩu mới và phần nhập lại không khớp.';
    }
    if ($new !== '' && $new === $current) {
        $errors[] = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    }

    if (!$errors) {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        session_regenerate_id(true);
        flash('Đã đổi mật khẩu thành công.');
        redirect('orders.php');
    }
}

$title = 'Đổi mật khẩu';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1>Đổi mật khẩu</h1>
<?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" class="panel form">
  <?= csrf_field() ?>
  <label>Mật khẩu hiện tại
    <input type="password" name="current" required autocomplete="current-password">
  </label>
  <label>Mật khẩu mới (ít nhất 8 ký tự)
    <input type="password" name="new" required minlength="8" autocomplete="new-password">
  </label>
  <label>Nhập lại mật khẩu mới
    <input type="password" name="confirm" required minlength="8" autocomplete="new-password">
  </label>
  <button class="btn">Lưu mật khẩu mới</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>