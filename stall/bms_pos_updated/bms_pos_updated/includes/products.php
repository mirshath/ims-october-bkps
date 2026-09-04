<?php
require_once __DIR__ . '/db.php';
function fetch_products()
{
  $conn = get_db();
  if (!$conn) return [];
  $res = mysqli_query($conn, "SELECT id,name,price,stock,image FROM products ORDER BY name");
  $out = [];
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $out[] = $row;
    }
  }
  return $out;
}
function get_product($id)
{
  $conn = get_db();
  if (!$conn) return null;
  $stmt = mysqli_prepare($conn, "SELECT id,name,price,stock,image FROM products WHERE id=?");
  mysqli_stmt_bind_param($stmt, 's', $id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($stmt);
  return $row;
}
function decrement_stock($id, $qty)
{
  $conn = get_db();
  if (!$conn) return false;
  $stmt = mysqli_prepare($conn, "UPDATE products SET stock=stock-? WHERE id=? AND stock>=?");
  mysqli_stmt_bind_param($stmt, 'isi', $qty, $id, $qty);
  mysqli_stmt_execute($stmt);
  $affected = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);
  return $affected > 0;
}
