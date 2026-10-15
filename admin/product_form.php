<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$p = ['name' => '', 'category_id' => '', 'price' => '', 'description' => '', 'is_available' => 1, 'image' => null];

if ($id) {
    $st = db()->prepare('SELECT * FROM products WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();
    if (!$p) { http_response_code(404); exit('Không tìm thấy món.'); }
}
$cats = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $p['name']         = trim($_POST['name'] ?? '');
    $p['category_id']  = (int)($_POST['category_id'] ?? 0) ?: null;
    $p['price']        = (int)($_POST['price'] ?? 0);
    $p['description']  = trim($_POST['description'] ?? '');
    $p['is_available'] = isset($_POST['is_available']) ? 1 : 0;

    if ($p['name'] === '' || mb_strlen($p['name']) > 150) $errors[] = 'Tên món không được để trống (tối đa 150 ký tự).';
    if ($p['price'] <= 0) $errors[] = 'Giá phải lớn hơn 0.';

    $newImage = null;
    if (!empty($_FILES['image']['name'])) {
        $f = $_FILES['image'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
        if ($f['error'] !== UPLOAD_ERR_OK)      $errors[] = 'Tải ảnh thất bại, vui lòng thử lại.';
        elseif (!isset($allowed[$mime]))        $errors[] = 'Ảnh phải là JPG, PNG hoặc WEBP.';
        elseif ($f['size'] > 2 * 1024 * 1024)   $errors[] = 'Ảnh tối đa 2MB.';
        else {
            $newImage = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $newImage)) {
                $errors[] = 'Không lưu được ảnh. Kiểm tra thư mục uploads.';
                $newImage = null;
            }
        }
    }

    if (!$errors) {
        $oldImage = $p['image'];
        $image = $newImage ?: $oldImage;
        if ($id) {
            db()->prepare('UPDATE products SET name=?, category_id=?, price=?, description=?, is_available=?, image=? WHERE id=?')
                ->execute([$p['name'], $p['category_id'], $p['price'], $p['description'], $p['is_available'], $image, $id]);
        } else {
            db()->prepare('INSERT INTO products(name,category_id,price,description,is_available,image) VALUES (?,?,?,?,?,?)')
                ->execute([$p['name'], $p['category_id'], $p['price'], $p['description'], $p['is_available'], $image]);
        }
        if ($newImage && $oldImage && is_file(__DIR__ . '/../uploads/' . $oldImage)) {
            unlink(__DIR__ . '/../uploads/' . $oldImage);
        }
        flash($id ? 'Đã lưu thay đổi.' : 'Đã thêm món mới.');
        redirect('products.php');
    }
}

$title = $id ? 'Sửa món' : 'Thêm món';
include __DIR__ . '/../includes/admin_header.php';
?>
<p><a href="products.php">← Danh sách món</a></p>
<h1><?= e($title) ?></h1>
<?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="panel form">
  <?= csrf_field() ?>
  <label>Tên món <input name="name" value="<?= e($p['name']) ?>" required maxlength="150"></label>
  <label>Nhóm
    <select name="category_id">
      <option value="0">Khác</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (int)$p['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Giá (đồng) <input type="number" name="price" value="<?= e($p['price']) ?>" min="1000" step="1000" required></label>
  <label>Mô tả <textarea name="description" rows="3"><?= e($p['description']) ?></textarea></label>
  <label>Ảnh (JPG/PNG/WEBP, tối đa 2MB)
    <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
  </label>
  <?php if ($p['image']): ?><img class="thumb big" src="<?= BASE_URL ?>/uploads/<?= e($p['image']) ?>" alt=""><?php endif; ?>
  <label class="check"><input type="checkbox" name="is_available" <?= $p['is_available'] ? 'checked' : '' ?>> Đang bán</label>
  <button class="btn">Lưu món</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>