<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
start_secure_session();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'invalid_payload']);
  exit;
}

$conn = get_db();
if (!$conn) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'db_unavailable']);
  exit;
}

$tx = false;
try {
  mysqli_begin_transaction($conn);
  $tx = true;

  $subtotal = 0.0;
  $items = [];
  $counts = [];

  // Process paid items
  foreach ($data['items'] as $i) {
    $id = isset($i['id']) ? $i['id'] : null;
    $qty = isset($i['qty']) ? (int)$i['qty'] : 0;
    if (!$id || $qty < 1) {
      throw new Exception('invalid_item');
    }

    $st = mysqli_prepare($conn, 'SELECT id,name,price,stock FROM products WHERE id=? FOR UPDATE');
    mysqli_stmt_bind_param($st, 's', $id);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $p = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($st);

    if (!$p) {
      throw new Exception('product_not_found');
    }
    if ($qty > (int)$p['stock']) {
      throw new Exception('insufficient_stock');
    }

    $subtotal += (float)$p['price'] * $qty;
    $items[] = ['id' => $p['id'], 'qty' => $qty, 'price' => $p['price']];
    $counts[$p['name']] = isset($counts[$p['name']]) ? $counts[$p['name']] + $qty : $qty;
  }

  // Count promotional items
  $tumblerQty = 0;
  $teddyQty = 0;
  $paidPenQty = 0;

  foreach ($counts as $n => $q) {
    $nl = strtolower($n);
    if (strpos($nl, 'tumbler') !== false || strpos($nl, 'vacuum') !== false) {
      $tumblerQty += (int)$q;
    }
    if (strpos($nl, 'teddy') !== false) $teddyQty += (int)$q;
    if (strpos($nl, 'pen') !== false) $paidPenQty += (int)$q;
  }

  // Calculate free pens
  $freePenReq = 0;
  if ($tumblerQty >= 1) {
    $freePenReq += $tumblerQty;  // 1 free pen per tumbler
  }
  if ($teddyQty >= 2) {
    $freePenReq += $teddyQty;  // 1 free pen per teddy (starting from 2)
  }

  // Try to add free pens, but DON'T FAIL if not available
  if ($freePenReq > 0) {
    $st = mysqli_prepare($conn, 'SELECT id,name,price,stock FROM products WHERE name LIKE ? FOR UPDATE');
    $penName = '%pen%';
    mysqli_stmt_bind_param($st, 's', $penName);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $pen = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($st);

    if ($pen) {
      $available = (int)$pen['stock'] - $paidPenQty;

      // Give as many free pens as available, but don't fail the purchase
      if ($available > 0) {
        $actualFreePens = min($freePenReq, $available);
        $items[] = ['id' => $pen['id'], 'qty' => $actualFreePens, 'price' => 0.00];
      }
      // If no free pens available, just continue without them
    }
    // If pen product doesn't exist, just continue without it
  }

  $tax = 0.00;
  $total = $subtotal + $tax;

  $u = auth_current_user();
  $createdBy = $u ? ($u['name'] ?? ($u['email'] ?? null)) : null;
  $st = mysqli_prepare($conn, 'INSERT INTO orders(subtotal,tax,total,created_by) VALUES(?,?,?,?)');
  $subtotalS = number_format($subtotal, 2, '.', '');
  $taxS = number_format($tax, 2, '.', '');
  $totalS = number_format($total, 2, '.', '');
  mysqli_stmt_bind_param($st, 'ssss', $subtotalS, $taxS, $totalS, $createdBy);
  mysqli_stmt_execute($st);
  mysqli_stmt_close($st);
  $order_id = (int)mysqli_insert_id($conn);

  // Insert order items and update stock
  $ins = mysqli_prepare($conn, 'INSERT INTO order_items(order_id,product_id,qty,price) VALUES(?,?,?,?)');
  $upd = mysqli_prepare($conn, 'UPDATE products SET stock = stock - ? WHERE id=?');

  foreach ($items as $it) {
    $priceS = number_format($it['price'], 2, '.', '');
    mysqli_stmt_bind_param($ins, 'isis', $order_id, $it['id'], $it['qty'], $priceS);
    mysqli_stmt_execute($ins);
    mysqli_stmt_bind_param($upd, 'is', $it['qty'], $it['id']);
    mysqli_stmt_execute($upd);
  }

  mysqli_stmt_close($ins);
  mysqli_stmt_close($upd);
  mysqli_commit($conn);

  echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Throwable $e) {
  if ($tx) mysqli_rollback($conn);
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'checkout_failed', 'message' => $e->getMessage()]);
}
