<?php
require_once __DIR__ . '/includes/db.php';
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId < 1) {
  http_response_code(400);
  echo 'Invalid order';
  exit;
}
$conn = get_db();
if (!$conn) {
  http_response_code(500);
  echo 'DB unavailable';
  exit;
}
$st = mysqli_prepare($conn, 'SELECT id, subtotal, tax, total, created_at FROM orders WHERE id=?');
mysqli_stmt_bind_param($st, 'i', $orderId);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$order = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($st);
if (!$order) {
  http_response_code(404);
  echo 'Order not found';
  exit;
}
$items = [];
$sql = 'SELECT oi.product_id, oi.qty, oi.price, p.name 
        FROM order_items oi 
        LEFT JOIN products p ON p.id = oi.product_id 
        WHERE oi.order_id=? 
        ORDER BY oi.id';
$st = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($st, 'i', $orderId);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
while ($row = $res ? mysqli_fetch_assoc($res) : null) {
  $items[] = $row;
}
mysqli_stmt_close($st);
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Receipt #<?= (int)$order['id'] ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    /* Base for screen preview */
    body {
      font-family: monospace, monospace;
      margin: 0;
      padding: 8px;
      background: #fff;
    }

    .receipt {
      width: 58mm;
      max-width: 100%;
    }

    .center {
      text-align: center;
    }

    .right {
      text-align: right;
    }

    .row {
      display: flex;
      justify-content: space-between;
    }

    .hr {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }

    .title {
      font-size: 14px;
      font-weight: bold;
    }

    .small {
      font-size: 11px;
    }

    .line {
      margin: 2px 0;
    }

    .muted {
      opacity: 0.9;
    }

    .totals .row {
      margin: 2px 0;
    }

    /* Print layout for thermal printer */
    @media print {
      @page {
        size: 58mm auto;
        margin: 0;
      }

      body {
        margin: 0;
        padding: 0;
      }

      .receipt {
        width: 58mm;
        padding: 4mm 3mm;
      }

      .hr {
        border-top: 1px dashed #000;
      }
    }
  </style>
</head>

<body onload="setTimeout(function(){ window.print(); setTimeout(function(){ window.close(); }, 300); }, 200);">
  <div class="receipt">
    <div class="center title">BMS POS</div>
    <div class="center small muted">Receipt #<?= (int)$order['id'] ?></div>
    <div class="center small muted"><?= htmlspecialchars($order['created_at']) ?></div>
    <div class="hr"></div>
    <?php foreach ($items as $it): ?>
      <div class="line">
        <div><?= htmlspecialchars($it['name'] ?: $it['product_id']) ?></div>
        <div class="row small">
          <div><?= (int)$it['qty'] ?> × <?= number_format((float)$it['price'], 2) ?></div>
          <div class="right"><?= number_format((float)$it['qty'] * (float)$it['price'], 2) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
    <div class="hr"></div>
    <div class="totals small">
      <div class="row">
        <div>Subtotal</div>
        <div class="right"><?= number_format((float)$order['subtotal'], 2) ?></div>
      </div>
      <div class="row">
        <div>Tax</div>
        <div class="right"><?= number_format((float)$order['tax'], 2) ?></div>
      </div>
    </div>
    <div class="hr"></div>
    <div class="row">
      <div class="title">TOTAL</div>
      <div class="right title"><?= number_format((float)$order['total'], 2) ?></div>
    </div>
    <div class="hr"></div>
    <div class="center small">Thank you!</div>
  </div>


  <!-- Add this to your receipt.php file, just before the closing </body> tag -->

  <!-- Add this to your receipt.php file, just before the closing </body> tag -->

  <style>
    .back-to-shop-btn {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      padding: 16px 40px;
      background: #0d6efd;
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 18px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1000;
      transition: all 0.3s ease;
      white-space: nowrap;
    }

    .back-to-shop-btn:hover {
      background: #0b5ed7;
      transform: translate(-50%, -50%) scale(1.05);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
    }

    .back-to-shop-btn:active {
      transform: translate(-50%, -50%) scale(0.98);
    }

    /* Hide button when printing */
    @media print {
      .back-to-shop-btn {
        display: none !important;
      }
    }

    /* Mobile optimization */
    @media (max-width: 768px) {
      .back-to-shop-btn {
        padding: 14px 35px;
        font-size: 16px;
      }
    }
  </style>

  <button class="back-to-shop-btn" onclick="closeAndRedirect()">
    🏠 Back to Shop
  </button>

  <script>
    function closeAndRedirect() {
      // Check if this window was opened as a popup
      if (window.opener) {
        // If opened from parent window, just close this popup
        window.close();
      } else {
        // If opened directly or in new tab, redirect
        window.location.href = 'index.php';
      }
    }

    // Also close on print dialog close (optional)
    // DISABLED: Uncomment below if you want auto-close after printing
    /*
    window.onafterprint = function() {
      setTimeout(function() {
        closeAndRedirect();
      }, 1000);
    };
    */

    // Handle back button press
    window.addEventListener('popstate', function(event) {
      closeAndRedirect();
    });
  </script>



</body>

</html>