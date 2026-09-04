<?php
session_start();
include("database/connection.php");
include("pos-includes/bootstrap.php");
include("includes/header.php");

require_once 'PermissionChecking.php';

pos_require_admin();

$metrics = ['products' => 0, 'active' => 0, 'stock' => 0, 'store_stock' => 0, 'out_of_stock' => 0, 'orders' => 0, 'revenue' => 0.00];
$recent_orders = [];
$oos_products = [];
$low_stock = [];
$trend = [];

$r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(CASE WHEN active=1 THEN stock ELSE 0 END),0) s, COALESCE(SUM(CASE WHEN active=1 THEN store_stock ELSE 0 END),0) ss, SUM(CASE WHEN stock=0 THEN 1 ELSE 0 END) o, SUM(CASE WHEN active=1 THEN 1 ELSE 0 END) a FROM products");
if ($r) {
    $row = mysqli_fetch_assoc($r);
    $metrics['products'] = intval($row['c']);
    $metrics['stock'] = intval($row['s']);
    $metrics['store_stock'] = intval($row['ss']);
    $metrics['out_of_stock'] = intval($row['o']);
    $metrics['active'] = intval($row['a']);
}
$r = mysqli_query($conn, "SELECT COUNT(*) c, COALESCE(SUM(total),0) rev FROM orders");
if ($r) {
    $row = mysqli_fetch_assoc($r);
    $metrics['orders'] = intval($row['c']);
    $metrics['revenue'] = floatval($row['rev']);
}
$r = mysqli_query($conn, "SELECT id,total,created_at FROM orders ORDER BY created_at DESC LIMIT 6");
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
// Last 7 days revenue trend, for the mini sparkline-style bar chart below.
$r = mysqli_query($conn, "SELECT DATE(created_at) d, COALESCE(SUM(total),0) t FROM orders WHERE created_at >= (CURDATE() - INTERVAL 6 DAY) GROUP BY DATE(created_at)");
$trendMap = [];
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $trendMap[$row['d']] = floatval($row['t']);
    }
}
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $trend[] = ['label' => date('D', strtotime($d)), 'value' => $trendMap[$d] ?? 0.0];
}
$trendMax = max(1, max(array_column($trend, 'value')));

$oos_pct = ($metrics['products'] > 0) ? round(($metrics['out_of_stock'] / $metrics['products']) * 100) : 0;
$active_pct = ($metrics['products'] > 0) ? round(($metrics['active'] / $metrics['products']) * 100) : 0;
?>
<link rel="stylesheet" href="pos-assets/css/pos-theme.css">
<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3 pos-scope">

                <div class="pos-page-header">
                    <div class="pos-title-block">
                        <div class="pos-page-icon"><i class="fas fa-gauge-high"></i></div>
                        <div>
                            <h4>POS Admin Dashboard</h4>
                            <div class="pos-subtitle">A live snapshot of your store's inventory and sales</div>
                        </div>
                    </div>
                    <div class="pos-header-actions">
                        <a href="pos_store.php" class="pos-btn pos-btn-outline"><i class="fas fa-store"></i> Back to
                            Store</a>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="pos_products.php" class="pos-btn pos-btn-navy"><i class="fas fa-boxes-stacked"></i> Manage
                        Products</a>
                    <a href="pos_reports.php" class="pos-btn pos-btn-ghost-navy"><i class="fas fa-chart-pie"></i>
                        Overall Reports</a>
                    <a href="pos_detailed_report.php" class="pos-btn pos-btn-ghost-navy"><i
                            class="fas fa-file-lines"></i> Detailed Reports</a>
                    <a href="pos_detailed_sales.php" class="pos-btn pos-btn-ghost-navy"><i class="fas fa-receipt"></i>
                        Detailed Sales</a>
                </div>

                <div class="pos-stat-grid">
                    <div class="pos-stat-card is-navy">
                        <div class="pos-stat-top">
                            <div class="pos-stat-icon"><i class="fas fa-box"></i></div>
                        </div>
                        <div class="pos-stat-label">Products</div>
                        <div class="pos-stat-value"><?= $metrics['active'] ?></div>
                        <div class="pos-stat-foot"><?= $metrics['products'] ?> total (<?= $active_pct ?>%)</div>
                    </div>
                    <div class="pos-stat-card is-blue">
                        <div class="pos-stat-top">
                            <div class="pos-stat-icon"><i class="fas fa-cubes"></i></div>
                        </div>
                        <div class="pos-stat-label">Total Stock</div>
                        <div class="pos-stat-value"><?= $metrics['stock'] ?></div>
                        <div class="pos-stat-foot">Units available</div>
                    </div>
                    <div class="pos-stat-card is-blue">
                        <div class="pos-stat-top">
                            <div class="pos-stat-icon"><i class="fas fa-warehouse"></i></div>
                        </div>
                        <div class="pos-stat-label">Store Stock</div>
                        <div class="pos-stat-value"><?= $metrics['store_stock'] ?></div>
                        <div class="pos-stat-foot">In-store units, active products</div>
                    </div>
                    <div class="pos-stat-card is-red">
                        <div class="pos-stat-top">
                            <div class="pos-stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
                            <span class="pos-stat-badge is-red"><?= $oos_pct ?>%</span>
                        </div>
                        <div class="pos-stat-label">Out of Stock</div>
                        <div class="pos-stat-value"><?= $metrics['out_of_stock'] ?></div>
                        <div class="progress mt-2" style="height:6px; border-radius:999px;">
                            <div class="progress-bar bg-danger" role="progressbar"
                                style="width: <?= $oos_pct ?>%; border-radius:999px;"></div>
                        </div>
                    </div>
                    <div class="pos-stat-card is-green">
                        <div class="pos-stat-top">
                            <div class="pos-stat-icon"><i class="fas fa-receipt"></i></div>
                        </div>
                        <div class="pos-stat-label">Orders</div>
                        <div class="pos-stat-value"><?= $metrics['orders'] ?></div>
                        <div class="pos-stat-foot">All-time</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <div class="pos-card h-100 mb-0">
                            <div class="pos-card-body d-flex flex-column justify-content-center h-100">
                                <div class="pos-stat-label mb-1">Total Revenue</div>
                                <div class="h3 fw-bold mb-1" style="color:var(--pos-red);">LKR
                                    <?= number_format($metrics['revenue'], 2) ?></div>
                                <div class="pos-stat-foot mb-0">Gross total from all completed orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-7">
                        <div class="pos-card h-100 mb-0">
                            <div class="pos-card-header">
                                <div class="pos-card-title"><span class="pos-mini-icon"><i
                                            class="fas fa-chart-column"></i></span> Last 7 Days</div>
                            </div>
                            <div class="pos-card-body">
                                <div class="d-flex align-items-end justify-content-between"
                                    style="height:110px; gap:8px;">
                                    <?php foreach ($trend as $t):
                                        $h = max(6, round(($t['value'] / $trendMax) * 100)); ?>
                                        <div class="d-flex flex-column align-items-center flex-fill"
                                            title="LKR <?= number_format($t['value'], 2) ?>">
                                            <div
                                                style="width:100%; max-width:28px; height:<?= $h ?>px; background:linear-gradient(180deg, var(--pos-navy-light), var(--pos-navy)); border-radius:6px 6px 2px 2px;">
                                            </div>
                                            <div class="small text-muted mt-2"><?= htmlspecialchars($t['label']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-lg-6">
                        <div class="pos-card mb-0 h-100">
                            <div class="pos-card-header">
                                <div class="pos-card-title"><span class="pos-mini-icon"><i
                                            class="fas fa-clock-rotate-left"></i></span> Recent Orders</div>
                                <a class="pos-btn pos-btn-outline pos-btn-sm" href="pos_reports.php">View Reports</a>
                            </div>
                            <div class="pos-card-body-flush">
                                <?php if (count($recent_orders) === 0): ?>
                                    <div class="pos-empty"><i class="fas fa-inbox"></i><strong>No recent orders</strong>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table pos-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Order</th>
                                                    <th>Total (LKR)</th>
                                                    <th>Date</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $o): ?>
                                                    <tr>
                                                        <td><span class="pos-code-tag">#<?= (int) $o['id'] ?></span></td>
                                                        <td class="fw-semibold"><?= number_format((float) $o['total'], 2) ?></td>
                                                        <td class="text-muted"><?= htmlspecialchars($o['created_at']) ?></td>
                                                        <td class="text-end"><a class="pos-btn pos-btn-ghost-navy pos-btn-sm"
                                                                href="pos_reports.php?order_id=<?= (int) $o['id'] ?>">Details</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="pos-card mb-0 h-100">
                            <div class="pos-card-header">
                                <div class="pos-card-title"><span class="pos-mini-icon"><i
                                            class="fas fa-bell"></i></span> Inventory Alerts</div>
                                <a class="pos-btn pos-btn-outline pos-btn-sm" href="pos_products.php">Manage
                                    Products</a>
                            </div>
                            <div class="pos-card-body">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="pos-divider-label">Out of Stock</div>
                                        <ul class="list-group list-group-flush">
                                            <?php if (count($oos_products) === 0): ?>
                                                <li class="list-group-item py-1 border-0 ps-0"><span
                                                        class="text-muted small">None</span></li>
                                                <?php else:
                                                foreach ($oos_products as $p): ?>
                                                    <li
                                                        class="list-group-item py-2 border-0 ps-0 d-flex justify-content-between align-items-center">
                                                        <span class="small"><?= htmlspecialchars($p['name']) ?></span>
                                                        <span class="pos-pill is-danger"><span class="pos-pill-dot"></span>
                                                            0</span>
                                                    </li>
                                            <?php endforeach;
                                            endif; ?>
                                        </ul>
                                    </div>
                                    <div class="col-12 col-md-6 mt-3 mt-md-0">
                                        <div class="pos-divider-label">Low Stock (≤5)</div>
                                        <ul class="list-group list-group-flush">
                                            <?php if (count($low_stock) === 0): ?>
                                                <li class="list-group-item py-1 border-0 ps-0"><span
                                                        class="text-muted small">None</span></li>
                                                <?php else:
                                                foreach ($low_stock as $p): ?>
                                                    <li
                                                        class="list-group-item py-2 border-0 ps-0 d-flex justify-content-between align-items-center">
                                                        <span class="small"><?= htmlspecialchars($p['name']) ?></span>
                                                        <span class="pos-pill is-warning"><span class="pos-pill-dot"></span>
                                                            <?= (int) $p['stock'] ?></span>
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
        </div>
    </div>
</div>

</body>

</html>