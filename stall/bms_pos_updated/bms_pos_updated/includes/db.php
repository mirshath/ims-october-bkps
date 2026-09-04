<?php
function db_env($k, $d = '')
{
  $v = getenv($k);
  return ($v !== false && $v !== '') ? $v : $d;
}
function get_db()
{
  static $conn;
  if ($conn) return $conn;
  $host = db_env('DB_HOST', '127.0.0.1');
  $db   = db_env('DB_NAME', 'stall');
  $user = db_env('DB_USER', 'root');
  $pass = db_env('DB_PASS', '');
  $conn = @mysqli_connect($host, $user, $pass, $db);
  if (!$conn) {
    $tmp = @mysqli_connect($host, $user, $pass);
    if (!$tmp) return null;
    mysqli_query($tmp, "CREATE DATABASE IF NOT EXISTS `" . $db . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mysqli_close($tmp);
    $conn = @mysqli_connect($host, $user, $pass, $db);
    if (!$conn) return null;
  }
  mysqli_set_charset($conn, 'utf8mb4');
  init_schema_and_seed($conn);
  return $conn;
}
function init_schema_and_seed($conn)
{
  mysqli_query($conn, "CREATE TABLE IF NOT EXISTS products (id VARCHAR(64) PRIMARY KEY, name VARCHAR(255) NOT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
  mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, subtotal DECIMAL(10,2) NOT NULL, tax DECIMAL(10,2) NOT NULL, total DECIMAL(10,2) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
  $ocol = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'created_by'");
  if (!$ocol || mysqli_num_rows($ocol) === 0) {
    @mysqli_query($conn, "ALTER TABLE orders ADD COLUMN created_by VARCHAR(255) DEFAULT NULL");
  }
  mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id VARCHAR(64) NOT NULL, qty INT NOT NULL, price DECIMAL(10,2) NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id), FOREIGN KEY (product_id) REFERENCES products(id)) ENGINE=InnoDB");
  mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'user', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
  // Ensure 'role' column exists (for older installs)
  $col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
  if (!$col || mysqli_num_rows($col) === 0) {
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
  }
  $res = mysqli_query($conn, "SELECT COUNT(*) c FROM products");
  $row = $res ? mysqli_fetch_assoc($res) : null;
  $count = $row ? intval($row['c']) : 0;
  if ($count === 0) {
    $stmt = mysqli_prepare($conn, "INSERT INTO products(id,name,price,stock,image) VALUES(?,?,?,?,?)");
    foreach (
      [
        ['GC001', 'Teddy Bear', 1500.00, 80, 'https://ims.bms.ac.lk/stall/product-image/BMS-Teddy-Bear.jpg'],
        ['GC002', 'Mug', 750.00, 100, 'https://ims.bms.ac.lk/stall/product-image/BMS-Logo-Mug.jpg'],
        ['GC003', 'Vacuum Tumbler', 1500.00, 120, 'https://ims.bms.ac.lk/stall/product-image/BMS-Logo-Bottle.jpg'],
        ['GC004', 'Note Book', 600.00, 200, 'https://ims.bms.ac.lk/stall/product-image/BMS-Note-Book.jpg'],
        ['GC005', 'Wooden Pen', 450.00, 500, 'https://ims.bms.ac.lk/stall/product-image/BMS-Wooden-Pen.jpg'],
      ] as $p
    ) {
      mysqli_stmt_bind_param($stmt, 'ssdis', $p[0], $p[1], $p[2], $p[3], $p[4]);
      mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
  }
  // Seed admin user if no users exist
  $ur = mysqli_query($conn, "SELECT COUNT(*) c FROM users");
  $urow = $ur ? mysqli_fetch_assoc($ur) : null;
  $ucount = $urow ? intval($urow['c']) : 0;
  if ($ucount === 0) {
    $adminEmail = 'admin@example.com';
    $adminName = 'Administrator';
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $userEmail = 'user@example.com';
    $userName = 'Cashier';
    $userHash = password_hash('user123', PASSWORD_DEFAULT);
    $ust = mysqli_prepare($conn, "INSERT INTO users(email,password_hash,name,role) VALUES(?,?,?,?)");
    $role = 'admin';
    mysqli_stmt_bind_param($ust, 'ssss', $adminEmail, $adminHash, $adminName, $role);
    mysqli_stmt_execute($ust);
    $role = 'user';
    mysqli_stmt_bind_param($ust, 'ssss', $userEmail, $userHash, $userName, $role);
    mysqli_stmt_execute($ust);
    mysqli_stmt_close($ust);
  }
}
