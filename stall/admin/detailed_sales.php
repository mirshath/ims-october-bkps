<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$assetBase = '../';
require_once __DIR__ . '/../includes/db.php';

$conn = get_db();
if (!$conn) {
    error_log("Database connection failed in detailed_sales.php");
    http_response_code(500);
    die('Database connection failed. Please check server configuration.');
}

// Helper function to escape output
function esc($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helper function to validate date format
function validate_ymd($d)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// CSV export function
function csv_export_and_exit($filename, $headers, $rows, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0)
{
    // Output CSV with BOM for Excel
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        // ensure scalar values only
        $flat = [];
        foreach ($row as $v) $flat[] = is_array($v) ? json_encode($v) : $v;
        fputcsv($out, $flat);
    }
    // Add summary row
    fputcsv($out, []); // Empty row
    fputcsv($out, ['SUMMARY', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($out, ['Total Orders', $totalOrders, '', '', '', '', '', '', '', '', '']);
    fputcsv($out, ['Total Items Sold', $totalItems, '', '', '', '', '', '', '', '', '']);
    fputcsv($out, ['Total Quantity', $totalQuantity, '', '', '', '', '', '', '', '', '']);
    fputcsv($out, ['Total Sales (Rs)', number_format($totalSales, 2), '', '', '', '', '', '', '', '', '']);
    fclose($out);
    exit;
}

// Excel export function (HTML table format)
function excel_export_and_exit($filename, $headers, $rows, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0)
{
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo '<html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1">';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th style="background-color: #4472C4; color: white; padding: 8px; font-weight: bold;">' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td style="padding: 5px;">' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    // Add summary rows
    echo '<tr><td colspan="' . count($headers) . '" style="padding: 10px; background-color: #f0f0f0;"></td></tr>';
    echo '<tr><td colspan="' . count($headers) . '" style="padding: 5px; font-weight: bold; background-color: #e0e0e0;">SUMMARY</td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Orders</td><td style="padding: 5px;">' . $totalOrders . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Items Sold</td><td style="padding: 5px;">' . $totalItems . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Quantity</td><td style="padding: 5px;">' . $totalQuantity . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Sales (Rs)</td><td style="padding: 5px; font-weight: bold; color: #d32f2f;">' . number_format($totalSales, 2) . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '</table></body></html>';
    exit;
}

// PDF export function (print-optimized HTML with grouped rows)
function pdf_export_and_exit($filename, $headers, $rows, $from, $to, $orderGroups, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0)
{
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($filename); ?></title>
        <style>
            @media print {
                @page { margin: 1cm; }
                body { margin: 0; }
                .no-print { display: none; }
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 9pt;
                margin: 20px;
            }
            h1 {
                text-align: center;
                color: #333;
                margin-bottom: 10px;
            }
            .report-info {
                text-align: center;
                margin-bottom: 20px;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            th {
                background-color: #4472C4;
                color: white;
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
                font-weight: bold;
            }
            td {
                padding: 6px;
                border: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .print-btn {
                margin: 20px;
                text-align: center;
            }
            .print-btn button {
                padding: 10px 20px;
                font-size: 14px;
                background-color: #4472C4;
                color: white;
                border: none;
                cursor: pointer;
                border-radius: 4px;
            }
            .print-btn button:hover {
                background-color: #365899;
            }
        </style>
    </head>
    <body>
        <div class="print-btn no-print">
            <button onclick="window.print()">Print / Save as PDF</button>
        </div>
        <h1>Detailed Sales Report</h1>
        <div class="report-info">
            <?php if ($from && $to): ?>
                <p>Period: <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?></p>
            <?php else: ?>
                <p>All Records</p>
            <?php endif; ?>
            <p>Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $header): ?>
                        <th><?php echo htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Display grouped rows with rowspan (like frontend)
                foreach ($orderGroups as $orderId => $orderRows):
                    $rowCount = count($orderRows);
                    $firstRow = $orderRows[0];
                    $isFirst = true;
                    
                    foreach ($orderRows as $row):
                ?>
                    <tr>
                        <?php if ($isFirst): ?>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <b><?php echo htmlspecialchars($orderId); ?></b>
                            </td>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <?php echo htmlspecialchars($firstRow['created_at']); ?>
                            </td>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <?php echo htmlspecialchars($firstRow['cashier'] ?? 'System'); ?>
                            </td>
                        <?php endif; ?>
                        
                        <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['qty']); ?></td>
                        <td><?php echo number_format((float)$row['price'], 2); ?></td>
                        <td><?php echo number_format((float)$row['line_total'], 2); ?></td>
                        
                        <?php if ($isFirst): ?>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <?php echo number_format((float)$firstRow['subtotal'], 2); ?>
                            </td>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <?php echo number_format((float)$firstRow['tax'], 2); ?>
                            </td>
                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                <b><?php echo number_format((float)$firstRow['total'], 2); ?></b>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php
                        $isFirst = false;
                    endforeach;
                endforeach;
                ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f0f0f0;">
                    <td colspan="3" style="text-align: right; font-weight: bold; padding: 8px;">Grand Total:</td>
                    <td colspan="4" style="padding: 8px;">
                        <strong>Orders: <?php echo $totalOrders; ?> | Items: <?php echo $totalItems; ?> | Qty: <?php echo $totalQuantity; ?></strong>
                    </td>
                    <td colspan="3" style="text-align: right; font-weight: bold; padding: 8px; color: #d32f2f;">
                        <?php echo number_format($totalSales, 2); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Summary Section -->
        <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd;">
            <h3 style="margin-top: 0; color: #333;">Summary</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Total Orders:</td>
                    <td style="padding: 8px;"><?php echo number_format($totalOrders); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Total Items Sold:</td>
                    <td style="padding: 8px;"><?php echo number_format($totalItems); ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Total Quantity:</td>
                    <td style="padding: 8px;"><?php echo number_format($totalQuantity); ?></td>
                </tr>
                <tr style="background-color: #fff3cd;">
                    <td style="padding: 8px; font-weight: bold; font-size: 12pt;">Total Sales (Rs):</td>
                    <td style="padding: 8px; font-weight: bold; font-size: 12pt; color: #d32f2f;"><?php echo number_format($totalSales, 2); ?></td>
                </tr>
            </table>
        </div>
        <script>
            window.onload = function() {
                // Auto-trigger print dialog (optional - comment out if you don't want auto-print)
                // window.print();
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Get and validate date filters
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$export = isset($_GET['export']) ? trim($_GET['export']) : '';

if ($from && !validate_ymd($from)) $from = '';
if ($to && !validate_ymd($to)) $to = '';

// Build query with prepared statement
$query = "
SELECT 
    o.id AS order_id,
    o.created_at,
    o.subtotal,
    o.tax,
    o.total,
    u.name AS cashier,
    p.id AS product_code,
    p.name AS product_name,
    oi.qty,
    oi.price,
    (oi.qty * oi.price) AS line_total
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
JOIN products p ON p.id = oi.product_id
LEFT JOIN users u ON u.email = o.created_by
";

$result = null;
$rows = [];

if ($from && $to) {
    $from_ts = $from . ' 00:00:00';
    $to_ts = $to . ' 23:59:59';
    $query .= " WHERE o.created_at BETWEEN ? AND ?";
    $query .= " ORDER BY o.created_at DESC, o.id DESC";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ss', $from_ts, $to_ts);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("SQL prepare error: " . mysqli_error($conn));
    }
} else {
    $query .= " ORDER BY o.created_at DESC, o.id DESC";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        error_log("SQL query error: " . mysqli_error($conn));
    }
}

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}

// Prepare export data - grouped by order (like frontend display)
$export_headers = ['Order ID', 'Date & Time', 'Cashier', 'Product Code', 'Product Name', 'Qty', 'Unit Price (Rs)', 'Line Total (Rs)', 'Order Subtotal (Rs)', 'Order Tax (Rs)', 'Order Total (Rs)'];

// Group rows by order_id
$orderGroups = [];
foreach ($rows as $row) {
    $orderId = $row['order_id'];
    if (!isset($orderGroups[$orderId])) {
        $orderGroups[$orderId] = [];
    }
    $orderGroups[$orderId][] = $row;
}

// Calculate totals
$totalSales = 0;
$totalOrders = count($orderGroups);
$totalItems = 0;
$totalQuantity = 0;

foreach ($orderGroups as $orderId => $orderRows) {
    $firstRow = $orderRows[0];
    $totalSales += (float)$firstRow['total'];
    foreach ($orderRows as $row) {
        $totalItems++;
        $totalQuantity += (int)$row['qty'];
    }
}

// Format export rows - show Order ID, Date, Cashier, Order totals only on first row of each order
$export_rows = [];
foreach ($orderGroups as $orderId => $orderRows) {
    $firstRow = $orderRows[0];
    $isFirst = true;
    
    foreach ($orderRows as $row) {
        $export_rows[] = [
            $isFirst ? $orderId : '',  // Order ID only on first row
            $isFirst ? $firstRow['created_at'] : '',  // Date only on first row
            $isFirst ? ($firstRow['cashier'] ?? 'System') : '',  // Cashier only on first row
            $row['product_code'],
            $row['product_name'],
            $row['qty'],
            number_format((float)$row['price'], 2),
            number_format((float)$row['line_total'], 2),
            $isFirst ? number_format((float)$firstRow['subtotal'], 2) : '',  // Order Subtotal only on first row
            $isFirst ? number_format((float)$firstRow['tax'], 2) : '',  // Order Tax only on first row
            $isFirst ? number_format((float)$firstRow['total'], 2) : ''  // Order Total only on first row
        ];
        $isFirst = false;
    }
}

// Handle exports
if ($export === 'csv') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.csv';
    csv_export_and_exit($filename, $export_headers, $export_rows, $totalSales, $totalOrders, $totalItems, $totalQuantity);
} elseif ($export === 'excel') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.xls';
    excel_export_and_exit($filename, $export_headers, $export_rows, $totalSales, $totalOrders, $totalItems, $totalQuantity);
} elseif ($export === 'pdf') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.html';
    pdf_export_and_exit($filename, $export_headers, $export_rows, $from, $to, $orderGroups, $totalSales, $totalOrders, $totalItems, $totalQuantity);
}

require __DIR__ . '/../templates/header.php';
?>

<div class="container mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <h3 class="mb-3 mb-md-0">🧾 Detailed Sales Report</h3>
        <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <!-- Filters -->
    <form method="GET" class="row mb-4">
        <input type="hidden" name="export" value="">
        <div class="col-md-3">
            <label class="form-label">Date From</label>
            <input type="date" name="from" class="form-control" value="<?php echo esc($from); ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Date To</label>
            <input type="date" name="to" class="form-control" value="<?php echo esc($to); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">&nbsp;</label>
            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary mt-2">Filter</button>
                <a href="detailed_sales.php" class="btn btn-outline-secondary mt-2">Reset</a>
                <?php if (count($rows) > 0): ?>
                    <div class="btn-group mt-2" role="group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            📥 Export
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=csv">
                                    📄 Export as CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=excel">
                                    📊 Export as Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=pdf" target="_blank">
                                    📑 Export as PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- Summary Cards -->
    <?php if (count($rows) > 0): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Orders</h6>
                        <h3 class="card-title text-primary"><?php echo number_format($totalOrders); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Items Sold</h6>
                        <h3 class="card-title text-info"><?php echo number_format($totalItems); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Quantity</h6>
                        <h3 class="card-title text-success"><?php echo number_format($totalQuantity); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Total Sales (Rs)</h6>
                        <h3 class="card-title text-danger"><?php echo number_format($totalSales, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Table -->
    <div class="card shadow">
        <div class="card-body">
            <?php if (count($rows) === 0): ?>
                <div class="alert alert-warning">No records found for selected filters.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Date & Time</th>
                                <th>Cashier</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit Price (Rs)</th>
                                <th>Line Total (Rs)</th>
                                <th>Order Total (Rs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $orderGroups = [];
                            
                            // Group rows by order_id
                            foreach ($rows as $row) {
                                $orderId = $row['order_id'];
                                if (!isset($orderGroups[$orderId])) {
                                    $orderGroups[$orderId] = [];
                                }
                                $orderGroups[$orderId][] = $row;
                            }
                            
                            // Display grouped rows with rowspan
                            foreach ($orderGroups as $orderId => $orderRows):
                                $rowCount = count($orderRows);
                                $firstRow = $orderRows[0];
                                $isFirst = true;
                                
                                foreach ($orderRows as $row):
                            ?>
                                <tr>
                                    <?php if ($isFirst): ?>
                                        <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                            <b><?php echo esc($orderId); ?></b>
                                        </td>
                                        <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                            <?php echo esc($firstRow['created_at']); ?>
                                        </td>
                                        <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                            <?php echo esc($firstRow['cashier'] ?? 'System'); ?>
                                        </td>
                                    <?php endif; ?>

                                    <td><?php echo esc($row['product_name']); ?> (<?php echo esc($row['product_code']); ?>)</td>
                                    <td><?php echo esc($row['qty']); ?></td>
                                    <td><?php echo number_format((float)$row['price'], 2); ?></td>
                                    <td><?php echo number_format((float)$row['line_total'], 2); ?></td>

                                    <?php if ($isFirst): ?>
                                        <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                            <b><?php echo number_format((float)$firstRow['total'], 2); ?></b>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php
                                    $isFirst = false;
                                endforeach;
                            endforeach;
                            ?>
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                <td colspan="2" class="text-end"><strong>Total Items: <?php echo number_format($totalItems); ?> | Total Qty: <?php echo number_format($totalQuantity); ?></strong></td>
                                <td colspan="2" class="text-end"><strong>Total Sales:</strong></td>
                                <td class="text-end"><strong><?php echo number_format($totalSales, 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>