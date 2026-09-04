<?php
$db_path = dirname(__DIR__) . "/database/connection.php";
if (file_exists($db_path)) { include($db_path); } else { @include("database/connection.php"); }

$db = null; $type = '';
foreach (['conn', 'con', 'db', 'link', 'pdo'] as $wrapper) {
    if (isset($$wrapper)) {
        if ($$wrapper instanceof PDO) { $db = $$wrapper; $type = 'pdo'; break; }
        elseif ($$wrapper instanceof mysqli) { $db = $$wrapper; $type = 'mysqli'; break; }
    }
}
if (!$db) { die("Operational Failure: Analytics data connector mappings missing."); }

$current_view = isset($_GET['view']) ? $_GET['view'] : 'stream';
$search_val   = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_user  = isset($_GET['f_user']) ? trim($_GET['f_user']) : '';
$filter_date  = isset($_GET['f_date']) ? trim($_GET['f_date']) : '';

// 1. Compile query filter maps dynamically
$clauses = []; $bind_params = []; $bind_types = "";
if ($search_val !== '') {
    $clauses[] = "(username LIKE ? OR full_url LIKE ? OR public_ip LIKE ? OR local_ip LIKE ?)";
    $term = "%$search_val%"; array_push($bind_params, $term, $term, $term, $term);
    $bind_types .= "ssss";
}
if ($filter_user !== '') { $clauses[] = "username = ?"; $bind_params[] = $filter_user; $bind_types .= "s"; }
if ($filter_date !== '') { $clauses[] = "visit_date = ?"; $bind_params[] = $filter_date; $bind_types .= "s"; }
$where_sql = count($clauses) > 0 ? " WHERE " . implode(" AND ", $clauses) : "";

// 2. Direct CSV Data Stream Generation Module
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=system_metrics_' . date('Y-m-d_His') . '.csv');
    $csv = fopen('php://output', 'w');
    fputcsv($csv, ['Record ID', 'Event Timestamp', 'Date Stamp', 'Hour', 'Session Identity', 'User Signature', 'Internal LAN IP', 'Public WAN IP', 'Target Resource URL', 'HTTP Method', 'Status Code', 'Browser Engine', 'OS Platform', 'Device Form Factor', 'Upload KB', 'Download KB', 'Net KB', 'Engine Delay MS']);
    
    $export_sql = "SELECT * FROM page_usage" . $where_sql . " ORDER BY id DESC LIMIT 10000";
    if ($type === 'pdo') {
        $stmt = $db->prepare($export_sql); $stmt->execute($bind_params);
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { fputcsv($csv, $r); }
    } else {
        $stmt = $db->prepare($export_sql);
        if (count($bind_params) > 0) { $stmt->bind_param($bind_types, ...$bind_params); }
        $stmt->execute(); $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) { fputcsv($csv, $r); }
    }
    fclose($csv); exit;
}

// 3. Compute structural dashboard KPI values
$kpi = ['views' => 0, 'bandwidth' => 0, 'users' => 0, 'latency' => 0];
$kpi_sql = "SELECT COUNT(*) as total_views, SUM(total_data_kb) as net_bytes, COUNT(DISTINCT username) as target_users, AVG(load_time_ms) as mean_speed FROM page_usage";
if ($type === 'pdo') {
    $res = $db->query($kpi_sql)->fetch(PDO::FETCH_ASSOC);
    if ($res) { $kpi = ['views' => $res['total_views'], 'bandwidth' => $res['net_bytes'] ?? 0, 'users' => $res['target_users'], 'latency' => $res['mean_speed'] ?? 0]; }
} else {
    $res = $db->query($kpi_sql)->fetch_assoc();
    if ($res) { $kpi = ['views' => $res['total_views'], 'bandwidth' => $res['net_bytes'] ?? 0, 'users' => $res['target_users'], 'latency' => $res['mean_speed'] ?? 0]; }
}

// 4. Extract multi-dimensional telemetry tracking logs
$grid_data = [];
if ($current_view === 'stream') {
    $query_sql = "SELECT * FROM page_usage" . $where_sql . " ORDER BY id DESC LIMIT 100";
    if ($type === 'pdo') {
        $stmt = $db->prepare($query_sql); $stmt->execute($bind_params); $grid_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $db->prepare($query_sql);
        if (count($bind_params) > 0) { $stmt->bind_param($bind_types, ...$bind_params); }
        $stmt->execute(); $grid_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} else {
    switch ($current_view) {
        case 'daily':
            $query_sql = "SELECT visit_date as tracking_key, COUNT(*) as page_views, SUM(traffic_up_kb) as up_kb, SUM(traffic_down_kb) as down_kb, SUM(total_data_kb) as aggregated_kb, AVG(load_time_ms) as latency_ms FROM page_usage GROUP BY visit_date ORDER BY visit_date DESC LIMIT 31";
            break;
        case 'weekly':
            $query_sql = "SELECT CONCAT(YEAR(visit_date), ' - Week ', WEEK(visit_date)) as tracking_key, COUNT(*) as page_views, SUM(traffic_up_kb) as up_kb, SUM(traffic_down_kb) as down_kb, SUM(total_data_kb) as aggregated_kb, AVG(load_time_ms) as latency_ms FROM page_usage GROUP BY YEAR(visit_date), WEEK(visit_date) ORDER BY YEAR(visit_date) DESC, WEEK(visit_date) DESC LIMIT 12";
            break;
        case 'monthly':
            $query_sql = "SELECT DATE_FORMAT(visit_date, '%Y-%m') as tracking_key, COUNT(*) as page_views, SUM(traffic_up_kb) as up_kb, SUM(traffic_down_kb) as down_kb, SUM(total_data_kb) as aggregated_kb, AVG(load_time_ms) as latency_ms FROM page_usage GROUP BY DATE_FORMAT(visit_date, '%Y-%m') ORDER BY tracking_key DESC LIMIT 12";
            break;
        case 'users':
            $query_sql = "SELECT username as tracking_key, COUNT(*) as page_views, SUM(traffic_up_kb) as up_kb, SUM(traffic_down_kb) as down_kb, SUM(total_data_kb) as aggregated_kb, AVG(load_time_ms) as latency_ms FROM page_usage GROUP BY username ORDER BY aggregated_kb DESC LIMIT 100";
            break;
        case 'pages':
            $query_sql = "SELECT full_url as tracking_key, COUNT(*) as page_views, SUM(traffic_up_kb) as up_kb, SUM(traffic_down_kb) as down_kb, SUM(total_data_kb) as aggregated_kb, AVG(load_time_ms) as latency_ms FROM page_usage GROUP BY full_url ORDER BY page_views DESC LIMIT 100";
            break;
    }
    $grid_data = ($type === 'pdo') ? $db->query($query_sql)->fetchAll(PDO::FETCH_ASSOC) : $db->query($query_sql)->fetch_all(MYSQLI_ASSOC);
}

// 5. Generate high-performance native line coordinates without script dependencies
$chart_nodes = []; $top_metric_peak = 1;
$chart_sql = "SELECT visit_date, COUNT(*) as total_counts FROM page_usage GROUP BY visit_date ORDER BY visit_date DESC LIMIT 10";
$chart_records = ($type === 'pdo') ? $db->query($chart_sql)->fetchAll(PDO::FETCH_ASSOC) : $db->query($chart_sql)->fetch_all(MYSQLI_ASSOC);
if ($chart_records) {
    $chart_nodes = array_reverse($chart_records);
    $top_metric_peak = max(array_column($chart_nodes, 'total_counts') ?: [1]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform System Telemetry & Performance Control Panel</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 24px; }
        .dashboard-container { max-width: 1650px; margin: 0 auto; }
        .control-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .control-header h1 { margin: 0; font-size: 26px; font-weight: 700; color: #1e293b; }
        .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .kpi-box { background: #ffffff; padding: 22px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); border-left: 4px solid #3b82f6; }
        .kpi-box.purple { border-left-color: #a855f7; }
        .kpi-box.emerald { border-left-color: #10b981; }
        .kpi-box.amber { border-left-color: #f59e0b; }
        .kpi-lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.75px; }
        .kpi-number { font-size: 30px; font-weight: 700; color: #1e293b; margin-top: 6px; }
        .chart-wrapper { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); margin-bottom: 24px; }
        .chart-header-title { font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 18px; }
        .chart-grid-plane { display: flex; align-items: flex-end; height: 130px; gap: 14px; padding-top: 15px; border-bottom: 2px solid #e2e8f0; }
        .chart-bar-node { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
        .chart-bar-fill { width: 100%; background: #3b82f6; border-radius: 4px 4px 0 0; min-height: 4px; transition: height 0.25s ease; }
        .chart-bar-fill:hover { background: #1d4ed8; }
        .chart-val-floating { position: absolute; top: -22px; font-size: 11px; font-weight: 700; color: #0f172a; }
        .chart-axis-label { font-size: 11px; color: #64748b; margin-top: 8px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 100%; }
        .data-panel { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; }
        .navigation-tabs { display: flex; border-bottom: 1px solid #e2e8f0; gap: 6px; margin-bottom: 22px; overflow-x: auto; }
        .nav-tab-item { padding: 12px 20px; font-size: 14px; font-weight: 600; color: #64748b; text-decoration: none; border-bottom: 2px solid transparent; transition: all 0.15s; white-space: nowrap; }
        .nav-tab-item:hover { color: #0f172a; background: #f8fafc; }
        .nav-tab-item.active { color: #3b82f6; border-bottom-color: #3b82f6; background: #f0f9ff; }
        .search-row-layout { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 22px; background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .search-row-layout input { padding: 9px 14px; font-size: 14px; border: 1px solid #cbd5e1; border-radius: 4px; outline: none; background: #ffffff; min-width: 220px; }
        .search-row-layout input:focus { border-color: #3b82f6; }
        .action-btn { background: #3b82f6; color: #ffffff; font-weight: 600; font-size: 14px; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .action-btn:hover { background: #1d4ed8; }
        .action-btn-slate { background: #475569; }
        .action-btn-slate:hover { background: #334155; }
        .action-btn-emerald { background: #10b981; }
        .action-btn-emerald:hover { background: #047857; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: top; }
        th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:hover td { background: #f8fafc; }
        code { background: #f1f5f9; padding: 3px 6px; border-radius: 4px; font-family: monospace; color: #0284c7; font-size: 13px; font-weight: 500; }
        .data-badge { display: inline-block; padding: 3px 7px; border-radius: 4px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; }
        .data-badge-speed { background: #dcfce7; color: #166534; }
        .data-badge-speed.slow { background: #fee2e2; color: #991b1b; }
        .small-muted-text { color: #64748b; font-size: 12px; line-height: 1.6; }
        .total-capacity-badge { background: #faf5ff; color: #6b21a8; font-size: 13px; padding: 4px 8px; border: 1px solid #f3e8ff; border-radius: 4px; font-weight: 600; }
        @media print {
            body { background: #ffffff; padding: 0; }
            .navigation-tabs, .search-row-layout, .control-header .btn-group { display: none !important; }
            .data-panel, .kpi-box, .chart-wrapper { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="control-header">
        <h1>Enterprise Core Security Telemetry</h1>
        <div class="btn-group" style="display: flex; gap: 8px;">
            <button onclick="window.print();" class="action-btn action-btn-slate">Print Analytics Frame</button>
            <a href="?export=csv&view=<?php echo $current_view; ?>&search=<?php echo urlencode($search_val); ?>&f_user=<?php echo urlencode($filter_user); ?>&f_date=<?php echo urlencode($filter_date); ?>" class="action-btn action-btn-emerald">Export CSV Spreadsheet</a>
        </div>
    </div>

    <!-- Telemetry Realtime Metrics Block Summary -->
    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-lbl">Total Logged Access Events</div>
            <div class="kpi-number"><?php echo number_format($kpi['views']); ?></div>
        </div>
        <div class="kpi-box purple">
            <div class="kpi-lbl">Aggregated System Traffic Volume</div>
            <div class="kpi-number"><?php echo number_format($kpi['bandwidth'] / 1024, 2); ?> MB</div>
        </div>
        <div class="kpi-box emerald">
            <div class="kpi-lbl">Distinct Identified Connections</div>
            <div class="kpi-number"><?php echo number_format($kpi['users']); ?></div>
        </div>
        <div class="kpi-box amber">
            <div class="kpi-lbl">Mean Page Assembly Latency</div>
            <div class="kpi-number"><?php echo number_format($kpi['latency'], 1); ?> ms</div>
        </div>
    </div>

    <!-- Native Dynamic Micro Graphing Node -->
    <div class="chart-wrapper">
        <div class="chart-header-title">System Request Activity Profile (Historical Tracking Days)</div>
        <div class="chart-grid-plane">
            <?php if(count($chart_nodes) === 0): ?>
                <div style="width: 100%; text-align: center; color: #64748b; font-size: 14px; padding-bottom: 35px;">Insufficient historical data log depth to build timeline mapping.</div>
            <?php else: ?>
                <?php foreach($chart_nodes as $node): 
                    $pct_height = ($node['total_counts'] / $top_metric_peak) * 100; ?>
                    <div class="chart-bar-node">
                        <div class="chart-val-floating"><?php echo $node['total_counts']; ?></div>
                        <div class="chart-bar-fill" style="height: <?php echo $pct_height; ?>%;"></div>
                        <div class="chart-axis-label"><?php echo date('M d', strtotime($node['visit_date'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- System Primary Data Matrix Workspace Grid -->
    <div class="data-panel">
        <div class="navigation-tabs">
            <a href="?view=stream" class="nav-tab-item <?php echo $current_view === 'stream' ? 'active' : ''; ?>">Live Streams</a>
            <a href="?view=daily" class="nav-tab-item <?php echo $current_view === 'daily' ? 'active' : ''; ?>">Daily Distribution</a>
            <a href="?view=weekly" class="nav-tab-item <?php echo $current_view === 'weekly' ? 'active' : ''; ?>">Weekly Clusters</a>
            <a href="?view=monthly" class="nav-tab-item <?php echo $current_view === 'monthly' ? 'active' : ''; ?>">Monthly Overview</a>
            <a href="?view=users" class="nav-tab-item <?php echo $current_view === 'users' ? 'active' : ''; ?>">User Activity Ranking</a>
            <a href="?view=pages" class="nav-tab-item <?php echo $current_view === 'pages' ? 'active' : ''; ?>">URL Resource Intensities</a>
        </div>

        <!-- Metric Engine Multi-Filter Component Interceptor Layout -->
        <form method="GET" class="search-row-layout">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($current_view); ?>">
            <input type="text" name="search" placeholder="Search values (URL, IP, User)..." value="<?php echo htmlspecialchars($search_val); ?>">
            <input type="text" name="f_user" placeholder="Target User Exact Signature..." value="<?php echo htmlspecialchars($filter_user); ?>">
            <input type="date" name="f_date" value="<?php echo htmlspecialchars($filter_date); ?>">
            <button type="submit" class="action-btn">Compute Dataset Filters</button>
            <a href="?view=<?php echo $current_view; ?>" class="action-btn action-btn-slate">Clear Grid Matrix</a>
        </form>

        <?php if ($current_view === 'stream'): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Timestamp</th>
                            <th>Identity Signature</th>
                            <th>Routing Network Addresses</th>
                            <th>Target URL Endpoint Information</th>
                            <th>Deployment Environment Metadata</th>
                            <th>Network Bandwidth Sizes</th>
                            <th>Engine Internal Velocity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($grid_data) === 0): ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b;">No explicit system access parameters match structural criteria.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($grid_data as $row): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('M d, Y', strtotime($row['visit_time'])); ?></strong><br>
                                <span class="small-muted-text"><?php echo date('h:i:s A', strtotime($row['visit_time'])); ?></span>
                            </td>
                            <td><span class="data-badge">👤 <?php echo htmlspecialchars($row['username']); ?></span></td>
                            <td>
                                <span class="small-muted-text">LAN Node:</span> <code><?php echo htmlspecialchars($row['local_ip']); ?></code><br>
                                <span class="small-muted-text">WAN Gate:</span> <code><?php echo htmlspecialchars($row['public_ip']); ?></code>
                            </td>
                            <td style="max-width: 350px; word-break: break-all;">
                                <code><?php echo htmlspecialchars($row['request_method']); ?></code> <span style="font-size: 13px; font-weight: 500; color: #334155;"><?php echo htmlspecialchars($row['full_url']); ?></span>
                                <?php if ($row['referer']): ?><br><span class="small-muted-text" style="font-size: 11px;">Source Link: <?php echo htmlspecialchars($row['referer']); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #334155;"><?php echo htmlspecialchars($row['operating_system']); ?></span><br>
                                <span class="small-muted-text"><?php echo htmlspecialchars($row['browser']); ?> (<?php echo htmlspecialchars($row['device_type']); ?>)</span>
                            </td>
                            <td>
                                <span class="small-muted-text" style="color: #2563eb; font-weight: 500;">Payload Up:</span> <?php echo number_format($row['traffic_up_kb'], 1); ?> KB<br>
                                <span class="small-muted-text" style="color: #16a34a; font-weight: 500;">Payload Down:</span> <?php echo number_format($row['traffic_down_kb'], 1); ?> KB<br>
                                <span class="small-muted-text" style="font-weight: 700; color: #0f172a;">Aggregate: <?php echo number_format($row['total_data_kb'], 1); ?> KB</span>
                            </td>
                            <td>
                                <span class="data-badge data-badge-speed <?php echo $row['load_time_ms'] > 450 ? 'slow' : ''; ?>">
                                    <?php echo number_format($row['load_time_ms'], 0); ?> ms
                                </span><br>
                                <span class="small-muted-text" style="font-size: 11px; font-weight: 600; margin-top: 4px; display: inline-block;">HTTP Status: <?php echo (int)$row['response_code']; ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- Intelligence Analytical Consolidated Matrices Output Module Block -->
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Aggregated Operational Target Key Partition</th>
                            <th>Total Execution Access Loops</th>
                            <th>Total Inbound Ingest Inbound Volume</th>
                            <th>Total Outbound Core Delivery Volume</th>
                            <th>Cumulative Shared Network Data Volume</th>
                            <th>Average Target Execution Latency Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($grid_data) === 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: #64748b;">No grouped metrics entries registered in structural tables.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($grid_data as $row): ?>
                        <tr>
                            <td><code style="font-size: 14px; padding: 4px 10px; color: #1e293b; font-weight: 600;"><?php echo htmlspecialchars($row['tracking_key']); ?></code></td>
                            <td style="font-weight: 700; font-size: 15px; color: #0f172a;"><?php echo number_format($row['page_views']); ?> triggers</td>
                            <td class="small-muted-text" style="color: #2563eb; font-weight: 500; font-size: 13px;"><?php echo number_format($row['up_kb'] / 1024, 2); ?> MB</td>
                            <td class="small-muted-text" style="color: #16a34a; font-weight: 500; font-size: 13px;"><?php echo number_format($row['down_kb'] / 1024, 2); ?> MB</td>
                            <td><span class="total-capacity-badge"><?php echo number_format($row['aggregated_kb'] / 1024, 2); ?> MB</span></td>
                            <td>
                                <span class="data-badge data-badge-speed <?php echo $row['latency_ms'] > 450 ? 'slow' : ''; ?>">
                                    <?php echo number_format($row['latency_ms'], 1); ?> ms
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>