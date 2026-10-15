<?php $cartCount = array_sum($_SESSION['cart'] ?? []); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Tiệm Trà Sữa') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="top">
  <a class="brand" href="<?= BASE_URL ?>/index.php">Tiệm Trà Sữa</a>
  <nav>
    <a href="<?= BASE_URL ?>/index.php">Thực đơn</a>
    <a href="<?= BASE_URL ?>/track.php">Tra cứu đơn</a>
    <a href="<?= BASE_URL ?>/cart.php">Giỏ hàng<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
  </nav>
</header>
<main class="wrap">
<?php flash_show(); ?>