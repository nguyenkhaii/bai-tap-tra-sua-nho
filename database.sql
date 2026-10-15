CREATE DATABASE IF NOT EXISTS trasua CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trasua;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NULL,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  price INT UNSIGNED NOT NULL,
  image VARCHAR(255) NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(100) NOT NULL,
  phone VARCHAR(15) NOT NULL,
  address VARCHAR(255) NOT NULL,
  note VARCHAR(255) NULL,
  total INT UNSIGNED NOT NULL,
  status ENUM('pending','preparing','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (phone), INDEX (created_at)
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NULL,
  product_name VARCHAR(150) NOT NULL,
  price INT UNSIGNED NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

INSERT INTO categories(name) VALUES ('Trà sữa'),('Trà trái cây'),('Cà phê'),('Đá xay');
INSERT INTO products(category_id,name,description,price) VALUES
(1,'Trà sữa trân châu đường đen','Trà đen, sữa tươi, trân châu nấu đường đen',38000),
(1,'Trà sữa matcha','Matcha Nhật, sữa tươi, ít ngọt',42000),
(1,'Trà sữa khoai môn','Khoai môn nghiền, sữa béo',40000),
(2,'Trà đào cam sả','Đào miếng, cam vàng, sả tươi',39000),
(2,'Trà vải hoa hồng','Vải thiều, cánh hồng sấy',39000),
(3,'Cà phê muối','Espresso, kem muối, sữa đặc',32000),
(4,'Matcha đá xay','Matcha, sữa, kem tươi',48000);