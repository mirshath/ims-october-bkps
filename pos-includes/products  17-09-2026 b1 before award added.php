<?php
// get_db() is provided by pos-includes/bootstrap.php (which must be
// included before this file).

/**
 * All products (any status), for the admin "Manage Products" screen.
 */
function fetch_products()
{
  $conn = get_db();
  if (!$conn)
    return [];
  $res = mysqli_query($conn, "SELECT id,name,price,store_stock,stock,image,active FROM products ORDER BY name");
  $out = [];
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $out[] = $row;
    }
  }
  return $out;
}

/**
 * Only products that are switched on ("active") - used by the storefront
 * so customers only ever see items the admin has made available for sale.
 * Pass $in_stock_only = false if you also want active-but-sold-out items.
 */
function fetch_active_products($in_stock_only = true)
{
  $conn = get_db();
  if (!$conn)
    return [];
  $sql = "SELECT id,name,price,store_stock,stock,image,active FROM products WHERE active = 1";
  if ($in_stock_only) {
    $sql .= " AND stock > 0";
  }
  $sql .= " ORDER BY name";
  $res = mysqli_query($conn, $sql);
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
  if (!$conn)
    return null;
  $stmt = mysqli_prepare($conn, "SELECT id,name,price,store_stock,stock,image,active FROM products WHERE id=?");
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
  if (!$conn)
    return false;
  $stmt = mysqli_prepare($conn, "UPDATE products SET stock=stock-? WHERE id=? AND stock>=?");
  mysqli_stmt_bind_param($stmt, 'isi', $qty, $id, $qty);
  mysqli_stmt_execute($stmt);
  $affected = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);
  return $affected > 0;
}

/**
 * Flip a product between active (visible in store) and inactive (hidden).
 */
function set_product_active($id, $active)
{
  $conn = get_db();
  if (!$conn)
    return false;
  $stmt = mysqli_prepare($conn, "UPDATE products SET active=? WHERE id=?");
  $activeInt = $active ? 1 : 0;
  mysqli_stmt_bind_param($stmt, 'is', $activeInt, $id);
  mysqli_stmt_execute($stmt);
  $ok = mysqli_stmt_affected_rows($stmt) >= 0 && mysqli_errno($conn) === 0;
  mysqli_stmt_close($stmt);
  return $ok;
}