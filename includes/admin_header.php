<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Quản trị') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body class="admin">
<header class="top">
  <a class="brand" href="<?= BASE_URL ?>/admin/orders.php">Quản trị</a>
  <nav>
    <a href="<?= BASE_URL ?>/admin/orders.php">Đơn hàng</a>
    <a href="<?= BASE_URL ?>/admin/products.php">Món</a>
    <a href="<?= BASE_URL ?>/admin/stats.php">Thống kê</a>
    <a href="<?= BASE_URL ?>/admin/change_password.php">Đổi mật khẩu</a>
    <a href="<?= BASE_URL ?>/index.php" target="_blank">Xem trang khách</a>
    <form method="post" action="<?= BASE_URL ?>/admin/logout.php" class="inline">
      <?= csrf_field() ?><button class="link">Đăng xuất</button>
    </form>
  </nav>
</header>
<main class="wrap">
<?php flash_show(); ?>