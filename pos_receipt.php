<?php
session_start();
include("database/connection.php");
include("pos-includes/bootstrap.php");

pos_require_login();

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
$st = mysqli_prepare($conn, 'SELECT id, subtotal, discount, tax, total, customer_type, customer_name, created_at FROM orders WHERE id=?');
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
        body { font-family: monospace, monospace; margin: 0; padding: 8px; background: #fff; }
        .receipt { width: 65mm; max-width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .row { display: flex; justify-content: space-between; }
        .hr { border-top: 1px dashed #000; margin: 6px 0; }
        .title { font-size: 14px; font-weight: bold; }
        .small { font-size: 11px; }
        .line { margin: 2px 0; }
        .muted { opacity: 0.9; }
        .totals .row { margin: 2px 0; }
        @media print {
            @page { size: 65mm auto; margin: 0; }
            body { margin: 0; padding: 0; }
            .receipt { width: 65mm; padding: 4mm 3mm; }
            .hr { border-top: 1px dashed #000; }
        }
        .back-to-shop-btn {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);
            padding: 16px 40px; background: #0d6efd; color: white; border: none;
            border-radius: 12px; font-size: 18px; font-weight: 700; cursor: pointer;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); z-index: 1000;
            transition: all 0.3s ease; white-space: nowrap;
        }
        .back-to-shop-btn:hover { background: #0b5ed7; transform: translate(-50%, -50%) scale(1.05); box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3); }
        .back-to-shop-btn:active { transform: translate(-50%, -50%) scale(0.98); }
        @media print { .back-to-shop-btn { display: none !important; } }
        @media (max-width: 768px) { .back-to-shop-btn { padding: 14px 35px; font-size: 16px; } }
    </style>
</head>

<body onload="setTimeout(function(){ window.print(); setTimeout(function(){ window.close(); }, 300); }, 200);">
    <div class="receipt">
        <div class="center">
            <img src="https://202.124.164.112:8140/img/logo4.png" alt="BMS POS Logo" style="max-height:48px; margin-bottom:5px;">
        </div>

        <div class="center small muted">Receipt #<?= (int)$order['id'] ?></div>
        <div class="center small muted"><?= htmlspecialchars($order['created_at']) ?></div>
        <?php if (!empty($order['customer_type'])): ?>
            <div class="center small muted" style="font-weight:bold; margin-top:2px;">
                Customer: <?= htmlspecialchars($order['customer_type']) ?>
                <?php if (!empty($order['customer_name'])): ?>
                    <br><?= htmlspecialchars($order['customer_name']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
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
            <?php if (!empty($order['discount']) && (float)$order['discount'] > 0): ?>
                <div class="row" style="color:#198754;">
                    <div>Discount
                        <?php if (!empty($order['discount_rate']) && (float)$order['discount_rate'] > 0): ?>
                            (<?= number_format((float)$order['discount_rate'] * 100, 0) ?>%)
                        <?php endif; ?>
                    </div>
                    <div class="right">- <?= number_format((float)$order['discount'], 2) ?></div>
                </div>
            <?php endif; ?>
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

    <button class="back-to-shop-btn" onclick="closeAndRedirect()">
        🏠 Back to Shop
    </button>

    <script>
        function closeAndRedirect() {
            if (window.opener) {
                window.close();
            } else {
                window.location.href = 'pos_store.php';
            }
        }
        window.addEventListener('popstate', function(event) {
            closeAndRedirect();
        });
    </script>
</body>

</html>
