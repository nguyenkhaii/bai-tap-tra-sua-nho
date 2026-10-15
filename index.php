<?php
require __DIR__ . '/includes/init.php';

$q   = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);

$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT p.*, c.name AS cat_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.is_available = 1';
$params = [];
if ($q !== '')  { $sql .= ' AND p.name LIKE ?';     $params[] = '%' . $q . '%'; }
if ($cat > 0)   { $sql .= ' AND p.category_id = ?'; $params[] = $cat; }
$sql .= ' ORDER BY c.name, p.name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$title = 'Thực đơn';
include __DIR__ . '/includes/header.php';
?>
<h1>Thực đơn</h1>

<form class="search" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Tìm món, ví dụ: matcha">
  <select name="cat">
    <option value="0">Tất cả nhóm</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn">Tìm</button>
</form>

<?php if (!$products): ?>
  <p class="empty">Không tìm thấy món nào khớp với "<?= e($q) ?>". Thử từ khoá khác hoặc chọn "Tất cả nhóm".</p>
<?php else: ?>
<div class="grid">
  <?php foreach ($products as $p): ?>
  <article class="card">
    <?php if ($p['image']): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
    <?php else: ?>
      <div class="ph"><?= e(mb_substr($p['name'], 0, 1)) ?></div>
    <?php endif; ?>
    <div class="card-body">
      <small class="muted"><?= e($p['cat_name'] ?? 'Khác') ?></small>
      <h3><?= e($p['name']) ?></h3>
      <p class="muted"><?= e($p['description']) ?></p>
      <div class="price"><?= money($p['price']) ?></div>
      <form method="post" action="<?= BASE_URL ?>/cart.php" class="add">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <input type="hidden" name="back" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
        <input type="number" name="qty" value="1" min="1" max="20" aria-label="Số lượng">
        <button class="btn">Thêm vào giỏ</button>
      </form>
    </div>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>