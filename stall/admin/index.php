<?php require_once __DIR__ . '/../includes/auth.php';
require_admin();
$assetBase = '../';
require __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../includes/db.php'; ?>
<?php
$conn = get_db();
$metrics = ['products' => 0, 'stock' => 0, 'out_of_stock' => 0, 'orders' => 0, 'revenue' => 0.00];
$recent_orders = [];
$oos_products = [];
$low_stock = [];
if ($conn) {
  $r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(stock),0) s, SUM(CASE WHEN stock=0 THEN 1 ELSE 0 END) o FROM products");
  if ($r) {
    $row = mysqli_fetch_assoc($r);
    $metrics['products'] = intval($row['c']);
    $metrics['stock'] = intval($row['s']);
    $metrics['out_of_stock'] = intval($row['o']);
  }
  $r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev FROM orders");
  if ($r) {
    $row = mysqli_fetch_assoc($r);
    $metrics['orders'] = intval($row['c']);
    $metrics['revenue'] = floatval($row['rev']);
  }
  $r = mysqli_query($conn, "SELECT id,total,created_at FROM orders ORDER BY created_at DESC LIMIT 5");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $recent_orders[] = $row;
    }
  }
  $r = mysqli_query($conn, "SELECT id,name,stock FROM products WHERE stock=0 ORDER BY name LIMIT 5");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $oos_products[] = $row;
    }
  }
  $r = mysqli_query($conn, "SELECT id,name,stock FROM products WHERE stock BETWEEN 1 AND 5 ORDER BY stock ASC, name LIMIT 5");
  if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
      $low_stock[] = $row;
    }
  }
}
$oos_pct = ($metrics['products'] > 0) ? round(($metrics['out_of_stock'] / $metrics['products']) * 100) : 0;
?>

<div class="container">

  <div class="dashboard-header d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between mb-3">
    <div class="text-center text-lg-start w-100 flex-grow-1">
      <div class="h5 mb-0">Admin Dashboard</div>
      <div class="text-muted small">Overview of inventory and sales</div>
    </div>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 w-100 dashboard-actions">
      <a href="products.php" class="btn btn-primary dashboard-action-btn">Manage Products</a>
      <a href="reports.php" class="btn btn-secondary dashboard-action-btn">Overall Reports</a>
      <a href="detailed-report.php" class="btn btn-danger dashboard-action-btn">Detailed Reports</a>
      <a href="detailed_sales.php" class="btn btn-danger dashboard-action-btn">detailed Sales</a>
      <a href="users.php" class="btn btn-warning text-dark dashboard-action-btn">Manage Users</a>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Products</div>
          <div class="h4 mb-1"><?= $metrics['products'] ?></div>
          <div class="small text-muted">Items listed</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Total Stock</div>
          <div class="h4 mb-1"><?= $metrics['stock'] ?></div>
          <div class="small text-muted">Units available</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">Out of Stock</div>
            <span class="badge bg-danger-subtle text-danger border"><?= $oos_pct ?>%</span>
          </div>
          <div class="h4 text-danger mb-2"><?= $metrics['out_of_stock'] ?></div>
          <div class="progress" style="height:6px;">
            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $oos_pct ?>%" aria-valuenow="<?= $oos_pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Orders</div>
          <div class="h4 mb-1"><?= $metrics['orders'] ?></div>
          <div class="small text-muted">All-time</div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-3">
    <div class="col-12 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Revenue</div>
          <div class="h4 text-success mb-2">LKR <?= number_format($metrics['revenue'], 2) ?></div>
          <div class="small text-muted">Gross total from all orders</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Quick Links</div>
          <div class="d-flex flex-wrap gap-2 mt-2">
            <a class="btn btn-outline-primary" href="products.php">Products</a>
            <a class="btn btn-outline-secondary" href="reports.php">Reports</a>
            <a class="btn btn-outline-dark" href="../index.php">POS</a>
            <a class="btn btn-outline-warning text-dark" href="users.php">Users</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-3 mt-1">
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">Recent Orders</div>
            <a class="btn btn-sm btn-outline-secondary" href="reports.php">View Reports</a>
          </div>
          <div class="table-responsive mt-2">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Total (LKR)</th>
                  <th>Date</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($recent_orders) === 0): ?>
                  <tr>
                    <td colspan="4" class="text-muted">No recent orders</td>
                  </tr>
                  <?php else: foreach ($recent_orders as $o): ?>
                    <tr>
                      <td><?= (int)$o['id'] ?></td>
                      <td><?= number_format((float)$o['total'], 2) ?></td>
                      <td><?= htmlspecialchars($o['created_at']) ?></td>
                      <td><a class="btn btn-sm btn-outline-primary" href="reports.php?order_id=<?= (int)$o['id'] ?>">Details</a></td>
                    </tr>
                <?php endforeach;
                endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Inventory Alerts</div>
          <div class="row mt-2">
            <div class="col-12 col-md-6">
              <div class="small text-muted mb-1">Out of Stock</div>
              <ul class="list-group list-group-flush">
                <?php if (count($oos_products) === 0): ?>
                  <li class="list-group-item py-1"><span class="text-muted">None</span></li>
                  <?php else: foreach ($oos_products as $p): ?>
                    <li class="list-group-item py-1 d-flex justify-content-between align-items-center">
                      <span><?= htmlspecialchars($p['name']) ?></span>
                      <a class="btn btn-sm btn-outline-primary" href="products.php">Manage</a>
                    </li>
                <?php endforeach;
                endif; ?>
              </ul>
            </div>
            <div class="col-12 col-md-6 mt-3 mt-md-0">
              <div class="small text-muted mb-1">Low Stock (≤5)</div>
              <ul class="list-group list-group-flush">
                <?php if (count($low_stock) === 0): ?>
                  <li class="list-group-item py-1"><span class="text-muted">None</span></li>
                  <?php else: foreach ($low_stock as $p): ?>
                    <li class="list-group-item py-1 d-flex justify-content-between align-items-center">
                      <span><?= htmlspecialchars($p['name']) ?> <span class="badge bg-warning text-dark ms-1"><?= (int)$p['stock'] ?></span></span>
                      <a class="btn btn-sm btn-outline-primary" href="products.php">Manage</a>
                    </li>
                <?php endforeach;
                endif; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>




<?php require __DIR__ . '/../templates/footer.php'; ?>