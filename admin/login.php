<?php
require __DIR__ . '/../includes/init.php';
if (is_admin()) redirect('orders.php');

if (!db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()) {
    db()->prepare('INSERT INTO admins(username,password_hash) VALUES (?,?)')
        ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([trim($_POST['username'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $u['id'];
        redirect('orders.php');
    }
    $error = 'Sai tên đăng nhập hoặc mật khẩu.';
    usleep(500000);
}
$title = 'Đăng nhập quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="admin">
<main class="wrap narrow">
  <h1>Đăng nhập quản trị</h1>
  <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="panel form">
    <?= csrf_field() ?>
    <label>Tên đăng nhập <input name="username" required autofocus autocomplete="username"></label>
    <label>Mật khẩu <input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn">Đăng nhập</button>
  </form>
</main>
</body>
</html>