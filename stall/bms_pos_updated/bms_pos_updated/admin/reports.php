<?php require_once __DIR__ . '/../includes/auth.php';
require_admin();
$assetBase = '../';
require __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../includes/db.php'; ?>
<?php
$conn = get_db();
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h5 mb-0">Reports</div>
    <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
  </div>
  <?php if ($orderId && $conn): ?>
    <?php $o = null;
    $r = mysqli_query($conn, "SELECT id, subtotal, tax, total, created_at FROM orders WHERE id=" . $orderId);
    if ($r) {
      $o = mysqli_fetch_assoc($r);
    } ?>
    <?php if ($o): ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div>Order #<?= $o['id'] ?></div>
            <div><?= htmlspecialchars($o['created_at']) ?></div>
          </div>
          <div class="d-flex justify-content-between">
            <div>Subtotal</div>
            <div>LKR<?= number_format($o['subtotal'], 2) ?></div>
          </div>
          <div class="d-flex justify-content-between">
            <div>Tax</div>
            <div>LKR<?= number_format($o['tax'], 2) ?></div>
          </div>
          <div class="d-flex justify-content-between">
            <div>Total</div>
            <div>LKR<?= number_format($o['total'], 2) ?></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Price</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php $ri = mysqli_query($conn, "SELECT oi.product_id, p.name, oi.qty, oi.price FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=" . $orderId); ?>
                <?php if ($ri) {
                  while ($it = mysqli_fetch_assoc($ri)): ?>
                    <tr>
                      <td><?= htmlspecialchars($it['name']) ?></td>
                      <td><?= (int)$it['qty'] ?></td>
                      <td>$<?= number_format($it['price'], 2) ?></td>
                      <td>$<?= number_format($it['qty'] * $it['price'], 2) ?></td>
                    </tr>
                <?php endwhile;
                } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning">Order not found</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="row g-3">
      <div class="col-12 col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="h6">Inventory Summary</div>
            <?php $inv = mysqli_query($conn, "SELECT COALESCE(SUM(stock),0) s FROM products");
            $sum = $inv ? intval(mysqli_fetch_assoc($inv)['s']) : 0; ?>
            <div class="d-flex justify-content-between">
              <div>Total Stock</div>
              <div><?= $sum ?></div>
            </div>
            <hr>
            <div class="h6">Out of Stock</div>
            <ul class="list-group list-group-flush">
              <?php $os = mysqli_query($conn, "SELECT name FROM products WHERE stock=0 ORDER BY name");
              if ($os && mysqli_num_rows($os) > 0) {
                while ($p = mysqli_fetch_assoc($os)) {
                  echo '<li class=\'list-group-item\'>' . htmlspecialchars($p['name']) . '</li>';
                }
              } else {
                echo '<li class=\'list-group-item text-muted\'>None</li>';
              } ?>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="card">
          <div class="card-body">
            <div class="h6">Sales Summary</div>
            <?php $or = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM orders");
            $osum = $or ? mysqli_fetch_assoc($or) : ['c' => 0, 't' => 0]; ?>
            <div class="d-flex justify-content-between">
              <div>Total Orders</div>
              <div><?= (int)$osum['c'] ?></div>
            </div>
            <div class="d-flex justify-content-between">
              <div>Total Revenue</div>
              <div>LKR <?= number_format((float)$osum['t'], 2) ?></div>
            </div>
            <hr>
            <div class="h6">Top Products</div>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $tp = mysqli_query($conn, "SELECT p.name, SUM(oi.qty) q, SUM(oi.qty*oi.price) r FROM order_items oi JOIN products p ON p.id=oi.product_id GROUP BY p.name ORDER BY q DESC LIMIT 10");
                  if ($tp) {
                    while ($t = mysqli_fetch_assoc($tp)) {
                      echo '<tr><td>' . htmlspecialchars($t['name']) . '</td><td>' . (int)$t['q'] . '</td><td>LKR ' . number_format((float)$t['r'], 2) . '</td></tr>';
                    }
                  } ?>
                </tbody>
              </table>
            </div>
            <hr>
            <div class="h6">Recent Orders</div>
            <ul class="list-group list-group-flush">
              <?php $ro = mysqli_query($conn, "SELECT id,total,created_at FROM orders ORDER BY id DESC LIMIT 10");
              if ($ro) {
                while ($o = mysqli_fetch_assoc($ro)) {
                  echo '<li class=\'list-group-item d-flex justify-content-between\'><a href=\'reports.php?order_id=' . (int)$o['id'] . '\'>#' . (int)$o['id'] . '</a><span>LKR ' . number_format((float)$o['total'], 2) . '</span></li>';
                }
              } ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../templates/footer.php'; ?>