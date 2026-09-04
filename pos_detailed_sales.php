<?php
/**
 * ob_start() catches ANY accidental output (PHP notices/deprecation
 * warnings, whitespace/BOM before a <?php tag in an included file,
 * duplicate session_start() warnings, etc.) so it can be discarded
 * right before we send file-download headers below. Without this,
 * a single stray byte of output makes header() fail silently on
 * hosts where output_buffering is off (common on live servers but
 * often on by default in local dev stacks), and the "CSV" download
 * ends up as a corrupted file or a raw HTML/warning page instead.
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // never leak warnings into a file download
ini_set('log_errors', '1');

// If something fatal happens while we're mid-export (e.g. a DB error),
// discard whatever partial output/CSV bytes were buffered and log the
// real error instead of letting a raw PHP error page corrupt the download.
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[pos_detailed_sales.php] Fatal: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
        if (ob_get_length() !== false) {
            ob_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Export failed due to a server error. Please try again or contact support.';
        }
    }
});

session_start();
include("database/connection.php");
include("pos-includes/bootstrap.php");

pos_require_admin();

require_once 'PermissionChecking.php';


$conn = get_db();
if (!$conn) {
    error_log("Database connection failed in detailed_sales.php");
    http_response_code(500);
    die('Database connection failed. Please check server configuration.');
}

// Helper function to escape output
function esc($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Helper function to validate date format
function validate_ymd($d)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

// CSV export function
function csv_export_and_exit($filename, $headers, $rows, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0, $totalDiscount = 0)
{
    // Output CSV with BOM for Excel
    // Discard any accidental output buffered so far (notices, BOM,
    // whitespace from an included file) so the headers below actually
    // take effect instead of triggering a silent "headers already sent".
    if (ob_get_length() !== false) {
        ob_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        // ensure scalar values only
        $flat = [];
        foreach ($row as $v)
            $flat[] = is_array($v) ? json_encode($v) : $v;
        fputcsv($out, $flat);
    }
    // Add summary row - padded to match the actual number of columns so the
    // summary block lines up correctly no matter how many headers there are
    $colCount = count($headers);
    $pad = function ($first, $second = '') use ($colCount) {
        return array_pad([$first, $second], $colCount, '');
    };
    fputcsv($out, []); // Empty row
    fputcsv($out, $pad('SUMMARY'));
    fputcsv($out, $pad('Total Orders', $totalOrders));
    fputcsv($out, $pad('Total Items Sold', $totalItems));
    fputcsv($out, $pad('Total Quantity', $totalQuantity));
    fputcsv($out, $pad('Total Discount (Rs)', number_format($totalDiscount, 2)));
    fputcsv($out, $pad('Total Sales (Rs)', number_format($totalSales, 2)));
    fclose($out);
    exit;
}

// Excel export function (HTML table format)
function excel_export_and_exit($filename, $headers, $rows, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0, $totalDiscount = 0)
{
    // Discard any accidental output buffered so far (notices, BOM,
    // whitespace from an included file) so the headers below actually
    // take effect instead of triggering a silent "headers already sent".
    if (ob_get_length() !== false) {
        ob_clean();
    }
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
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Discount (Rs)</td><td style="padding: 5px; color: #d32f2f;">' . number_format($totalDiscount, 2) . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '<tr><td style="padding: 5px; font-weight: bold;">Total Sales (Rs)</td><td style="padding: 5px; font-weight: bold; color: #d32f2f;">' . number_format($totalSales, 2) . '</td><td colspan="' . (count($headers) - 2) . '"></td></tr>';
    echo '</table></body></html>';
    exit;
}

// PDF export function (print-optimized HTML with grouped rows)
function pdf_export_and_exit($filename, $headers, $rows, $from, $to, $orderGroups, $totalSales = 0, $totalOrders = 0, $totalItems = 0, $totalQuantity = 0, $totalDiscount = 0)
{
    // Discard any accidental output buffered so far (notices, BOM,
    // whitespace from an included file) so the headers below actually
    // take effect instead of triggering a silent "headers already sent".
    if (ob_get_length() !== false) {
        ob_clean();
    }
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($filename); ?></title>
        <style>
            @media print {
                @page {
                    margin: 1cm;
                }

                body {
                    margin: 0;
                }

                .no-print {
                    display: none;
                }
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
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo htmlspecialchars($firstRow['customer_type']); ?>
                                </td>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo htmlspecialchars($firstRow['customer_name']); ?>
                                </td>
                            <?php endif; ?>

                            <td><?php echo htmlspecialchars($row['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['qty']); ?></td>
                            <td><?php echo number_format((float) $row['price'], 2); ?></td>
                            <td><?php echo number_format((float) $row['line_discount'], 2); ?></td>
                            <td><?php echo number_format((float) $row['line_total'], 2); ?></td>

                            <?php if ($isFirst): ?>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo number_format((float) $firstRow['subtotal'], 2); ?>
                                </td>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo number_format((float) $firstRow['order_discount'], 2); ?>
                                </td>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo number_format((float) $firstRow['discount_pct'], 2); ?>%
                                </td>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <?php echo number_format((float) $firstRow['tax'], 2); ?>
                                </td>
                                <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                    <b><?php echo number_format((float) $firstRow['total'], 2); ?></b>
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
                    <td colspan="5" style="text-align: right; font-weight: bold; padding: 8px;">Grand Total:</td>
                    <td colspan="6" style="padding: 8px;">
                        <strong>Orders: <?php echo $totalOrders; ?> | Items: <?php echo $totalItems; ?> | Qty:
                            <?php echo $totalQuantity; ?> | Discount: <?php echo number_format($totalDiscount, 2); ?></strong>
                    </td>
                    <td colspan="5" style="text-align: right; font-weight: bold; padding: 8px; color: #d32f2f;">
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
                <tr>
                    <td style="padding: 8px; font-weight: bold;">Total Discount (Rs):</td>
                    <td style="padding: 8px; color: #d32f2f;"><?php echo number_format($totalDiscount, 2); ?></td>
                </tr>
                <tr style="background-color: #fff3cd;">
                    <td style="padding: 8px; font-weight: bold; font-size: 12pt;">Total Sales (Rs):</td>
                    <td style="padding: 8px; font-weight: bold; font-size: 12pt; color: #d32f2f;">
                        <?php echo number_format($totalSales, 2); ?></td>
                </tr>
            </table>
        </div>
        <script>
            window.onload = function () {
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

if ($from && !validate_ymd($from))
    $from = '';
if ($to && !validate_ymd($to))
    $to = '';

// Build query with prepared statement
$query = "
SELECT 
    o.id AS order_id,
    o.created_at,
    o.subtotal,
    o.discount AS order_discount,
    ROUND(o.discount_rate * 100, 2) AS discount_pct,
    o.tax,
    o.total,
    COALESCE(NULLIF(o.customer_type, ''), 'Cash') AS customer_type,
    COALESCE(NULLIF(o.customer_name, ''), '-') AS customer_name,
    COALESCE(NULLIF(a.full_name, ''), a.username, o.created_by) AS cashier,
    p.id AS product_code,
    p.name AS product_name,
    oi.qty,
    oi.price,
    oi.discount AS line_discount,
    (oi.qty * oi.price) AS line_gross,
    (oi.qty * oi.price - oi.discount) AS line_total
FROM orders o
JOIN order_items oi ON oi.order_id = o.id
JOIN products p ON p.id = oi.product_id
LEFT JOIN admin a ON a.username = o.created_by
";

$result = null;
$rows = [];

$conditions = [];
$params = [];
$types = '';

if ($from) {
    $conditions[] = "o.created_at >= ?";
    $params[] = $from . ' 00:00:00';
    $types .= 's';
}
if ($to) {
    $conditions[] = "o.created_at <= ?";
    $params[] = $to . ' 23:59:59';
    $types .= 's';
}

if ($conditions) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}
$query .= " ORDER BY o.created_at DESC, o.id DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("SQL prepare error: " . mysqli_error($conn));
    }
} else {
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
$export_headers = ['Order ID', 'Date & Time', 'Cashier', 'Customer Type', 'Customer Name', 'Product Code', 'Product Name', 'Qty', 'Unit Price (Rs)', 'Line Discount (Rs)', 'Line Total (Rs)', 'Order Subtotal (Rs)', 'Order Discount (Rs)', 'Discount %', 'Order Tax (Rs)', 'Order Total (Rs)'];

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
$totalDiscount = 0;
$totalOrders = count($orderGroups);
$totalItems = 0;
$totalQuantity = 0;

foreach ($orderGroups as $orderId => $orderRows) {
    $firstRow = $orderRows[0];
    $totalSales += (float) $firstRow['total'];
    $totalDiscount += (float) $firstRow['order_discount'];
    foreach ($orderRows as $row) {
        $totalItems++;
        $totalQuantity += (int) $row['qty'];
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
            $isFirst ? $firstRow['customer_type'] : '',  // Customer Type only on first row
            $isFirst ? $firstRow['customer_name'] : '',  // Customer Name only on first row
            $row['product_code'],
            $row['product_name'],
            $row['qty'],
            number_format((float) $row['price'], 2),
            number_format((float) $row['line_discount'], 2),
            number_format((float) $row['line_total'], 2),
            $isFirst ? number_format((float) $firstRow['subtotal'], 2) : '',  // Order Subtotal only on first row
            $isFirst ? number_format((float) $firstRow['order_discount'], 2) : '',  // Order Discount only on first row
            $isFirst ? number_format((float) $firstRow['discount_pct'], 2) . '%' : '',  // Discount % only on first row
            $isFirst ? number_format((float) $firstRow['tax'], 2) : '',  // Order Tax only on first row
            $isFirst ? number_format((float) $firstRow['total'], 2) : ''  // Order Total only on first row
        ];
        $isFirst = false;
    }
}

// Handle exports
if ($export === 'csv') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.csv';
    csv_export_and_exit($filename, $export_headers, $export_rows, $totalSales, $totalOrders, $totalItems, $totalQuantity, $totalDiscount);
} elseif ($export === 'excel') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.xls';
    excel_export_and_exit($filename, $export_headers, $export_rows, $totalSales, $totalOrders, $totalItems, $totalQuantity, $totalDiscount);
} elseif ($export === 'pdf') {
    $filename = 'detailed_sales_' . date('Ymd_His') . '.html';
    pdf_export_and_exit($filename, $export_headers, $export_rows, $from, $to, $orderGroups, $totalSales, $totalOrders, $totalItems, $totalQuantity, $totalDiscount);
}

include("includes/header.php");
?>
<link rel="stylesheet" href="pos-assets/css/pos-theme.css">
<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3 pos-scope">

                <div class="container-fluid">
                    <div class="pos-page-header">
                        <div class="pos-title-block">
                            <div class="pos-page-icon"><i class="fas fa-receipt"></i></div>
                            <div>
                                <h4>Detailed Sales Report</h4>
                                <div class="pos-subtitle">Every order, itemised, with CSV / Excel / PDF export</div>
                            </div>
                        </div>
                        <div class="pos-header-actions">
                            <a href="pos_detailed_report.php" class="pos-btn pos-btn-ghost-navy"><i
                                    class="fas fa-file-lines"></i> Detailed Reports</a>
                            <a href="pos_dashboard.php" class="pos-btn pos-btn-outline"><i
                                    class="fas fa-arrow-left"></i> Dashboard</a>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="pos-card">
                        <div class="pos-card-body">
                            <form method="GET" class="row g-2 align-items-end">
                                <input type="hidden" name="export" value="">
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="from" class="form-control"
                                        value="<?php echo esc($from); ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="to" class="form-control" value="<?php echo esc($to); ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="submit" class="pos-btn pos-btn-navy"><i class="fas fa-filter"></i>
                                            Filter</button>
                                        <a href="pos_detailed_sales.php" class="pos-btn pos-btn-outline">Reset</a>
                                        <?php if (count($rows) > 0): ?>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="pos-btn pos-btn-ghost-navy dropdown-toggle"
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-download"></i> Export
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=csv">
                                                            <i class="fas fa-file-csv me-1"></i> Export as CSV
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=excel">
                                                            <i class="fas fa-file-excel me-1"></i> Export as Excel
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=pdf"
                                                            target="_blank">
                                                            <i class="fas fa-file-pdf me-1"></i> Export as PDF
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <?php if (count($rows) > 0): ?>
                        <div class="pos-stat-grid">
                            <div class="pos-stat-card is-navy">
                                <div class="pos-stat-top">
                                    <div class="pos-stat-icon"><i class="fas fa-receipt"></i></div>
                                </div>
                                <div class="pos-stat-label">Total Orders</div>
                                <div class="pos-stat-value"><?php echo number_format($totalOrders); ?></div>
                            </div>
                            <div class="pos-stat-card is-blue">
                                <div class="pos-stat-top">
                                    <div class="pos-stat-icon"><i class="fas fa-list"></i></div>
                                </div>
                                <div class="pos-stat-label">Total Items Sold</div>
                                <div class="pos-stat-value"><?php echo number_format($totalItems); ?></div>
                            </div>
                            <div class="pos-stat-card is-amber">
                                <div class="pos-stat-top">
                                    <div class="pos-stat-icon"><i class="fas fa-cubes"></i></div>
                                </div>
                                <div class="pos-stat-label">Total Quantity</div>
                                <div class="pos-stat-value"><?php echo number_format($totalQuantity); ?></div>
                            </div>
                            <div class="pos-stat-card is-green">
                                <div class="pos-stat-top">
                                    <div class="pos-stat-icon"><i class="fas fa-tags"></i></div>
                                </div>
                                <div class="pos-stat-label">Total Discount (Rs)</div>
                                <div class="pos-stat-value" style="font-size:1.3rem;">
                                    <?php echo number_format($totalDiscount, 2); ?></div>
                            </div>
                            <div class="pos-stat-card is-red">
                                <div class="pos-stat-top">
                                    <div class="pos-stat-icon"><i class="fas fa-sack-dollar"></i></div>
                                </div>
                                <div class="pos-stat-label">Total Sales (Rs)</div>
                                <div class="pos-stat-value" style="font-size:1.3rem;">
                                    <?php echo number_format($totalSales, 2); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Report Table -->
                    <div class="pos-card">
                        <div class="pos-card-header">
                            <div class="pos-card-title"><span class="pos-mini-icon"><i
                                        class="fas fa-table-list"></i></span> Order-by-Order Breakdown</div>
                        </div>
                        <div class="pos-card-body-flush">
                            <?php if (count($rows) === 0): ?>
                                <div class="pos-empty">
                                    <i class="fas fa-inbox"></i>
                                    <strong>No records found</strong>
                                    <span>Try a different date range.</span>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table pos-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date & Time</th>
                                                <th>Cashier</th>
                                                <th>Customer</th>
                                                <th>Product Code</th>
                                                <th>Product Name</th>
                                                <th>Qty</th>
                                                <th>Unit Price (Rs)</th>
                                                <th>Line Discount (Rs)</th>
                                                <th>Line Total (Rs)</th>
                                                <th>Order Subtotal (Rs)</th>
                                                <th>Order Discount (Rs)</th>
                                                <th>Discount %</th>
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

                                                // Compact "Customer" display: just the type for Cash,
                                                // or "Type - Name" for Staff/Student
                                                $custType = $firstRow['customer_type'];
                                                $custName = $firstRow['customer_name'];
                                                $custDisplay = ($custType === 'Cash' || $custName === '-' || $custName === '')
                                                    ? $custType
                                                    : $custType . ' - ' . $custName;

                                                foreach ($orderRows as $row):
                                                    ?>
                                                    <tr>
                                                        <?php if ($isFirst): ?>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <span class="pos-code-tag">#<?php echo esc($orderId); ?></span>
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo esc($firstRow['created_at']); ?>
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo esc($firstRow['cashier'] ?? 'System'); ?>
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo esc($custDisplay); ?>
                                                            </td>
                                                        <?php endif; ?>

                                                        <td><?php echo esc($row['product_code']); ?></td>
                                                        <td><?php echo esc($row['product_name']); ?></td>
                                                        <td><?php echo esc($row['qty']); ?></td>
                                                        <td><?php echo number_format((float) $row['price'], 2); ?></td>
                                                        <td><?php echo number_format((float) $row['line_discount'], 2); ?></td>
                                                        <td><?php echo number_format((float) $row['line_total'], 2); ?></td>

                                                        <?php if ($isFirst): ?>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo number_format((float) $firstRow['subtotal'], 2); ?>
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo number_format((float) $firstRow['order_discount'], 2); ?>
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <?php echo number_format((float) $firstRow['discount_pct'], 2); ?>%
                                                            </td>
                                                            <td rowspan="<?php echo $rowCount; ?>" style="vertical-align: middle;">
                                                                <strong
                                                                    class="pos-price-tag"><?php echo number_format((float) $firstRow['total'], 2); ?></strong>
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
                                            <tr style="background:var(--pos-slate-50);">
                                                <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                                <td colspan="6" class="text-end"><strong>Items:
                                                        <?php echo number_format($totalItems); ?> | Qty:
                                                        <?php echo number_format($totalQuantity); ?></strong></td>
                                                <td colspan="3" class="text-end"><strong>Discount:
                                                        <?php echo number_format($totalDiscount, 2); ?></strong></td>
                                                <td class="text-end">
                                                    <strong><?php echo number_format($totalSales, 2); ?></strong></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</body>

</html>