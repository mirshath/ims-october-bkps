<?php
session_start();
// lib_report.php
//session_start();
date_default_timezone_set('Asia/Colombo');

$basePath = dirname(__DIR__);
require_once $basePath . '/database/connection.php';

if (!isset($conn) || !$conn) {
    die('Database connection failed');
}

if (!isset($_SESSION['username'])) {
    die('Unauthorized');
}

$format   = $_GET['format'] ?? 'pdf';
$category = $_GET['category'] ?? 'both';
$type     = $_GET['type'] ?? 'custom';
$from     = $_GET['from'] ?? date('Y-m-01');
$to       = $_GET['to'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'all';
$itemName = $_GET['item_name'] ?? 'all';

$job_type_filter = '';
if ($category === 'print') {
    $job_type_filter = "AND job_type = 'printing'";
} elseif ($category === 'binding') {
    $job_type_filter = "AND job_type = 'binding'";
}

// Get categories
$categories = [];
$catResult = $conn->query("SELECT catagory_id, catagory_name, catagory_color, icon FROM lib_catagory ORDER BY catagory_name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[$row['catagory_name']] = $row;
    }
}

$sql = "
    SELECT 
        bill_id,
        job_date, 
        name, 
        batch, 
        user_type,
        job_type,
        one_side, 
        both_side, 
        rough_one, 
        rough_two, 
        error_count,
        binding_name, 
        size,
        quantity,
        item_unit_price,
        subtotal, 
        discount_amount, 
        discount_percent,
        net_amount, 
        is_free, 
        total_sheets, 
        user,
        DATE_FORMAT(created_at, '%H:%i') as job_time
    FROM lib_printing_jobs
    WHERE job_date BETWEEN ? AND ?
    $job_type_filter
    ORDER BY bill_id DESC, id ASC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Query preparation failed: ' . $conn->error);
}
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();

$allJobs = [];
while ($row = $result->fetch_assoc()) {
    $numericFields = ['one_side', 'both_side', 'rough_one', 'rough_two', 'error_count', 
                      'total_sheets', 'quantity', 'subtotal', 'discount_amount', 'net_amount', 'item_unit_price'];
    foreach ($numericFields as $field) {
        if (isset($row[$field]) && $row[$field] !== '') {
            $row[$field] = (float)$row[$field];
        } else {
            $row[$field] = 0;
        }
    }
    foreach ($row as $key => $value) {
        if ($value === null) {
            $row[$key] = '';
        }
    }
    $allJobs[] = $row;
}
$stmt->close();

// Filter by category for category report
if ($reportType === 'category' && $category !== 'both') {
    $filteredJobs = [];
    foreach ($allJobs as $job) {
        $jobCategory = strtolower($job['job_type'] ?? '');
        $filterCategory = strtolower($category);
        if ($jobCategory === $filterCategory) {
            $filteredJobs[] = $job;
        }
    }
    $allJobs = $filteredJobs;
}

// Filter by item name for item report
if ($reportType === 'item' && $itemName !== 'all') {
    $filteredJobs = [];
    foreach ($allJobs as $job) {
        $displayName = '';
        if ($job['job_type'] === 'binding') {
            $displayName = $job['binding_name'] ?: 'Binding';
            if ($job['size']) {
                $displayName .= " (" . $job['size'] . ")";
            }
        } else {
            $parts = [];
            if ($job['one_side'] > 0) $parts[] = "One Side ×" . (int)$job['one_side'];
            if ($job['both_side'] > 0) $parts[] = "Both Side ×" . (int)$job['both_side'];
            if ($job['rough_one'] > 0) $parts[] = "Rough One ×" . (int)$job['rough_one'];
            if ($job['rough_two'] > 0) $parts[] = "Rough Both ×" . (int)$job['rough_two'];
            if ($job['error_count'] > 0) $parts[] = "Error ×" . (int)$job['error_count'];
            $displayName = implode(', ', $parts) ?: 'Printing Item';
            if ($job['size']) {
                $displayName .= " (" . $job['size'] . ")";
            }
        }
        if (stripos($displayName, $itemName) !== false) {
            $filteredJobs[] = $job;
        }
    }
    $allJobs = $filteredJobs;
}

// Group by bill
$bills = [];
foreach ($allJobs as $job) {
    $billId = $job['bill_id'];
    if (!isset($bills[$billId])) {
        $bills[$billId] = [
            'bill_id' => $billId,
            'job_date' => $job['job_date'],
            'job_time' => $job['job_time'],
            'name' => $job['name'],
            'batch' => $job['batch'] ?: '-',
            'user_type' => $job['user_type'],
            'user' => $job['user'],
            'is_free' => $job['is_free'],
            'total' => 0,
            'discount' => 0,
            'subtotal' => 0,
            'items' => []
        ];
    }
    
    $itemSubtotal = $job['subtotal'] ?? 0;
    $itemDiscount = $job['discount_amount'] ?? 0;
    $itemNet = $job['net_amount'] ?? 0;
    
    $bills[$billId]['total'] += $itemNet;
    $bills[$billId]['discount'] += $itemDiscount;
    $bills[$billId]['subtotal'] += $itemSubtotal;
    
    if ($job['job_type'] === 'binding') {
        $itemNameDisplay = $job['binding_name'] ?: 'Binding';
        if ($job['size']) {
            $itemNameDisplay .= " (" . $job['size'] . ")";
        }
        $qty = (int)$job['quantity'];
        $price = $job['item_unit_price'];
        $category = 'binding';
    } else {
        $parts = [];
        if ($job['one_side'] > 0) $parts[] = "One Side ×" . (int)$job['one_side'];
        if ($job['both_side'] > 0) $parts[] = "Both Side ×" . (int)$job['both_side'];
        if ($job['rough_one'] > 0) $parts[] = "Rough One ×" . (int)$job['rough_one'];
        if ($job['rough_two'] > 0) $parts[] = "Rough Both ×" . (int)$job['rough_two'];
        if ($job['error_count'] > 0) $parts[] = "Error ×" . (int)$job['error_count'];
        $itemNameDisplay = implode(', ', $parts) ?: 'Printing Item';
        if ($job['size']) {
            $itemNameDisplay .= " (" . $job['size'] . ")";
        }
        $qty = (int)($job['one_side'] + $job['both_side'] + $job['rough_one'] + $job['rough_two'] + $job['error_count']);
        $price = $job['item_unit_price'];
        $category = 'printing';
    }
    
    $bills[$billId]['items'][] = [
        'item_name' => $itemNameDisplay,
        'item_name_display' => $itemNameDisplay,
        'qty' => $qty,
        'price' => $price,
        'subtotal' => $itemSubtotal,
        'discount' => $itemDiscount,
        'discount_percent' => $job['discount_percent'] ?: 0,
        'net_amount' => $itemNet,
        'job_type' => $job['job_type'],
        'category' => $category,
        'size' => $job['size'] ?? ''
    ];
}

$jobs = array_values($bills);

// Calculate summary
$summary = [
    'total_jobs' => count($jobs),
    'total_revenue' => 0,
    'print_revenue' => 0,
    'binding_revenue' => 0,
    'total_discount' => 0,
    'paid_jobs' => 0,
    'free_jobs' => 0,
    'total_qty' => 0
];

foreach ($jobs as $bill) {
    $summary['total_revenue'] += $bill['total'];
    $summary['total_discount'] += $bill['discount'];
    $billQty = 0;
    foreach ($bill['items'] as $item) {
        $billQty += $item['qty'];
        $itemNet = $item['net_amount'] ?? 0;
        if ($item['job_type'] === 'binding') {
            $summary['binding_revenue'] += $itemNet;
        } else {
            $summary['print_revenue'] += $itemNet;
        }
    }
    $summary['total_qty'] += $billQty;
    if ($bill['is_free']) $summary['free_jobs']++;
    else $summary['paid_jobs']++;
}

$rows = [];

// ALL REPORT
if ($reportType === 'all') {
    $rows[] = ['DATE', 'BILL #', 'CUSTOMER', 'BATCH', 'TYPE', 'ITEMS', 'QTY', 'TOTAL', 'USER', 'STATUS'];
    $grandTotal = 0; $grandQty = 0; $grandCount = 0;
    $dateGroups = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        if (!isset($dateGroups[$date])) $dateGroups[$date] = [];
        $dateGroups[$date][] = $bill;
    }
    ksort($dateGroups);
    foreach ($dateGroups as $date => $bills) {
        $rows[] = [];
        $rows[] = ['=== ' . $date . ' ===', '', '', '', '', '', '', '', '', ''];
        $dateTotal = 0; $dateQty = 0; $dateCount = 0;
        foreach ($bills as $bill) {
            $status = $bill['is_free'] ? 'FREE' : 'PAID';
            $userType = $bill['user_type'] ?: 'Other';
            $itemNames = [];
            $billQty = 0;
            foreach ($bill['items'] as $item) {
                $itemNames[] = $item['item_name'];
                $billQty += $item['qty'];
            }
            $rows[] = [$date, $bill['bill_id'], $bill['name'], $bill['batch'] ?: '-', $userType, implode(', ', $itemNames), $billQty, number_format($bill['total'], 2), $bill['user'] ?: 'N/A', $status];
            $dateTotal += $bill['total']; $dateQty += $billQty; $dateCount++;
        }
        $rows[] = ['', '', '--- DATE TOTAL ---', '', '', '', $dateQty, number_format($dateTotal, 2), '', ''];
        $rows[] = [];
        $grandTotal += $dateTotal; $grandQty += $dateQty; $grandCount += $dateCount;
    }
    $rows[] = ['', '', '=== GRAND TOTAL ===', '', '', '', $grandQty, number_format($grandTotal, 2), '', ''];
    $rows[] = ['Total Bills: ' . $grandCount, '', '', '', '', '', '', '', '', ''];

// BILL REPORT
} elseif ($reportType === 'bill') {
    $rows[] = ['DATE', 'BILL #', 'TIME', 'CUSTOMER', 'BATCH', 'TYPE', 'ITEM', 'QTY', 'PRICE', 'DISC%', 'DISCOUNT', 'NET', 'STATUS'];
    $grandTotal = 0; $grandQty = 0; $grandCount = 0;
    $dateGroups = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        if (!isset($dateGroups[$date])) $dateGroups[$date] = [];
        $dateGroups[$date][] = $bill;
    }
    ksort($dateGroups);
    foreach ($dateGroups as $date => $bills) {
        $rows[] = [];
        $rows[] = ['=== ' . $date . ' ===', '', '', '', '', '', '', '', '', '', '', '', ''];
        $dateTotal = 0; $dateQty = 0; $dateCount = 0;
        foreach ($bills as $bill) {
            $status = $bill['is_free'] ? 'FREE' : 'PAID';
            $userType = $bill['user_type'] ?: 'Other';
            $firstItem = true;
            foreach ($bill['items'] as $item) {
                $rows[] = [
                    $firstItem ? $date : '',
                    $firstItem ? $bill['bill_id'] : '',
                    $firstItem ? ($bill['job_time'] ?: '-') : '',
                    $firstItem ? $bill['name'] : '',
                    $firstItem ? ($bill['batch'] ?: '-') : '',
                    $firstItem ? $userType : '',
                    $item['item_name'],
                    $item['qty'],
                    number_format($item['price'], 2),
                    $item['discount_percent'] ? $item['discount_percent'] . '%' : '-',
                    number_format($item['discount'], 2),
                    number_format($item['net_amount'], 2),
                    $firstItem ? $status : ''
                ];
                $firstItem = false;
                $dateQty += $item['qty'];
            }
            $rows[] = ['', '', '', '--- BILL TOTAL ---', '', '', '', '', '', '', number_format($bill['discount'], 2), number_format($bill['total'], 2), ''];
            $dateTotal += $bill['total']; $dateCount++;
        }
        $rows[] = ['', '', '', '--- DATE TOTAL ---', '', '', '', $dateQty, '', '', number_format($dateTotal, 2), '', ''];
        $rows[] = [];
        $grandTotal += $dateTotal; $grandQty += $dateQty; $grandCount += $dateCount;
    }
    $rows[] = ['', '', '', '=== GRAND TOTAL ===', '', '', '', $grandQty, '', '', number_format($grandTotal, 2), '', ''];
    $rows[] = ['Total Bills: ' . $grandCount, '', '', '', '', '', '', '', '', '', '', '', ''];

// CATEGORY REPORT
} elseif ($reportType === 'category') {
    $rows[] = ['DATE', 'CATEGORY', 'JOBS', 'QTY', 'TOTAL REVENUE'];
    $grandTotal = 0; $grandQty = 0; $grandJobs = 0;
    $dateCatMap = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        foreach ($bill['items'] as $item) {
            $cat = ucfirst($item['job_type']) ?: 'Other';
            if (!isset($dateCatMap[$date])) $dateCatMap[$date] = [];
            if (!isset($dateCatMap[$date][$cat])) {
                $dateCatMap[$date][$cat] = ['jobs' => 0, 'qty' => 0, 'total' => 0];
            }
            $dateCatMap[$date][$cat]['jobs']++;
            $dateCatMap[$date][$cat]['qty'] += $item['qty'];
            $dateCatMap[$date][$cat]['total'] += $item['net_amount'];
        }
    }
    ksort($dateCatMap);
    foreach ($dateCatMap as $date => $cats) {
        $rows[] = [];
        $rows[] = ['=== ' . $date . ' ===', '', '', '', ''];
        $dateTotal = 0; $dateQty = 0; $dateJobs = 0;
        foreach ($cats as $cat => $data) {
            $rows[] = [$date, $cat, $data['jobs'], $data['qty'], number_format($data['total'], 2)];
            $dateTotal += $data['total']; $dateQty += $data['qty']; $dateJobs += $data['jobs'];
        }
        $rows[] = ['', '--- DATE TOTAL ---', $dateJobs, $dateQty, number_format($dateTotal, 2)];
        $rows[] = [];
        $grandTotal += $dateTotal; $grandQty += $dateQty; $grandJobs += $dateJobs;
    }
    $rows[] = ['', '=== GRAND TOTAL ===', $grandJobs, $grandQty, number_format($grandTotal, 2)];

// ITEM REPORT
} elseif ($reportType === 'item') {
    $rows[] = ['DATE', 'BILL #', 'ITEM', 'CATEGORY', 'QTY', 'PRICE', 'DISC%', 'TOTAL'];
    $grandTotal = 0; $grandQty = 0;
    $dateItemMap = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        foreach ($bill['items'] as $item) {
            $itemName = $item['item_name'];
            if (!isset($dateItemMap[$date])) $dateItemMap[$date] = [];
            if (!isset($dateItemMap[$date][$itemName])) {
                $dateItemMap[$date][$itemName] = [
                    'qty' => 0, 'total' => 0, 'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'] ?: 0,
                    'category' => $item['category'] ?: 'Other',
                    'bills' => []
                ];
            }
            $dateItemMap[$date][$itemName]['qty'] += $item['qty'];
            $dateItemMap[$date][$itemName]['total'] += $item['net_amount'];
            if ($item['discount_percent'] > $dateItemMap[$date][$itemName]['discount_percent']) {
                $dateItemMap[$date][$itemName]['discount_percent'] = $item['discount_percent'];
            }
            if (!in_array($bill['bill_id'], $dateItemMap[$date][$itemName]['bills'])) {
                $dateItemMap[$date][$itemName]['bills'][] = $bill['bill_id'];
            }
        }
    }
    ksort($dateItemMap);
    foreach ($dateItemMap as $date => $items) {
        $rows[] = [];
        $rows[] = ['=== ' . $date . ' ===', '', '', '', '', '', '', ''];
        $dateTotal = 0; $dateQty = 0;
        foreach ($items as $name => $data) {
            $billNumbers = implode(', ', $data['bills']);
            $rows[] = [
                $date,
                $billNumbers,
                $name,
                $data['category'],
                $data['qty'],
                number_format($data['price'], 2),
                $data['discount_percent'] ? $data['discount_percent'] . '%' : '-',
                number_format($data['total'], 2)
            ];
            $dateTotal += $data['total']; $dateQty += $data['qty'];
        }
        $rows[] = ['', '--- DATE TOTAL ---', '', '', $dateQty, '', '', number_format($dateTotal, 2)];
        $rows[] = [];
        $grandTotal += $dateTotal; $grandQty += $dateQty;
    }
    $rows[] = ['', '=== GRAND TOTAL ===', '', '', $grandQty, '', '', number_format($grandTotal, 2)];

// Z-REPORT
} elseif ($reportType === 'zreport') {
    $rows[] = ['Z-REPORT - HOURLY SALES SUMMARY'];
    $rows[] = ['Period: ' . $from . ' to ' . $to];
    $rows[] = [];
    $rows[] = ['HOUR', 'BILLS', 'QTY', 'GROSS', 'DISCOUNT', 'NET', 'AVG PER BILL'];
    $hourlyData = [];
    foreach ($jobs as $bill) {
        $hour = isset($bill['job_time']) ? (int)$bill['job_time'] : 0;
        $billQty = 0;
        foreach ($bill['items'] as $item) $billQty += $item['qty'];
        if (!isset($hourlyData[$hour])) $hourlyData[$hour] = ['bills' => 0, 'qty' => 0, 'revenue' => 0, 'discount' => 0];
        $hourlyData[$hour]['bills']++;
        $hourlyData[$hour]['qty'] += $billQty;
        $hourlyData[$hour]['revenue'] += $bill['total'];
        $hourlyData[$hour]['discount'] += $bill['discount'];
    }
    $totalBills = 0; $totalQty = 0; $totalRevenue = 0; $totalDiscount = 0;
    ksort($hourlyData);
    foreach ($hourlyData as $hour => $data) {
        $avg = $data['bills'] > 0 ? $data['revenue'] / $data['bills'] : 0;
        $rows[] = [sprintf('%02d:00 - %02d:00', $hour, $hour + 1), $data['bills'], $data['qty'], number_format($data['revenue'] + $data['discount'], 2), number_format($data['discount'], 2), number_format($data['revenue'], 2), number_format($avg, 2)];
        $totalBills += $data['bills']; $totalQty += $data['qty']; $totalRevenue += $data['revenue']; $totalDiscount += $data['discount'];
    }
    $rows[] = [];
    $rows[] = ['=== Z-REPORT TOTALS ===', $totalBills, $totalQty, number_format($totalRevenue + $totalDiscount, 2), number_format($totalDiscount, 2), number_format($totalRevenue, 2), number_format($totalBills > 0 ? $totalRevenue / $totalBills : 0, 2)];

// X-REPORT
} elseif ($reportType === 'xreport') {
    $rows[] = ['X-REPORT - DAILY SALES SUMMARY'];
    $rows[] = ['Period: ' . $from . ' to ' . $to];
    $rows[] = [];
    $rows[] = ['DATE', 'BILLS', 'QTY', 'GROSS', 'DISCOUNT', 'NET', 'AVG PER BILL'];
    $dateGroups = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        $billQty = 0;
        foreach ($bill['items'] as $item) $billQty += $item['qty'];
        if (!isset($dateGroups[$date])) $dateGroups[$date] = ['bills' => 0, 'qty' => 0, 'revenue' => 0, 'discount' => 0];
        $dateGroups[$date]['bills']++;
        $dateGroups[$date]['qty'] += $billQty;
        $dateGroups[$date]['revenue'] += $bill['total'];
        $dateGroups[$date]['discount'] += $bill['discount'];
    }
    $totalBills = 0; $totalQty = 0; $totalRevenue = 0; $totalDiscount = 0;
    ksort($dateGroups);
    foreach ($dateGroups as $date => $data) {
        $avg = $data['bills'] > 0 ? $data['revenue'] / $data['bills'] : 0;
        $rows[] = [$date, $data['bills'], $data['qty'], number_format($data['revenue'] + $data['discount'], 2), number_format($data['discount'], 2), number_format($data['revenue'], 2), number_format($avg, 2)];
        $totalBills += $data['bills']; $totalQty += $data['qty']; $totalRevenue += $data['revenue']; $totalDiscount += $data['discount'];
    }
    $rows[] = [];
    $rows[] = ['=== X-REPORT TOTALS ===', $totalBills, $totalQty, number_format($totalRevenue + $totalDiscount, 2), number_format($totalDiscount, 2), number_format($totalRevenue, 2), number_format($totalBills > 0 ? $totalRevenue / $totalBills : 0, 2)];

// FINANCIAL REPORT
} elseif ($reportType === 'financial') {
    $rows[] = ['FINANCIAL REPORT'];
    $rows[] = ['Period: ' . $from . ' to ' . $to];
    $rows[] = [];
    $rows[] = ['=== FINANCIAL SUMMARY ==='];
    $rows[] = ['Metric', 'Value'];
    $rows[] = ['Total Bills', $summary['total_jobs']];
    $rows[] = ['Total Items Qty', $summary['total_qty']];
    $rows[] = ['Gross Revenue', number_format($summary['total_revenue'] + $summary['total_discount'], 2)];
    $rows[] = ['Total Discount', number_format($summary['total_discount'], 2)];
    $rows[] = ['Net Revenue', number_format($summary['total_revenue'], 2)];
    $rows[] = ['Tax (10%)', number_format($summary['total_revenue'] * 0.1, 2)];
    $rows[] = ['Net After Tax', number_format($summary['total_revenue'] * 0.9, 2)];
    $rows[] = ['Average per Bill', number_format(($summary['total_jobs'] > 0 ? $summary['total_revenue'] / $summary['total_jobs'] : 0), 2)];
    $rows[] = ['Paid Bills', $summary['paid_jobs']];
    $rows[] = ['Free Bills', $summary['free_jobs']];
    $rows[] = ['Print Revenue', number_format($summary['print_revenue'], 2)];
    $rows[] = ['Binding Revenue', number_format($summary['binding_revenue'], 2)];
    $rows[] = [];
    $rows[] = ['=== DAILY BREAKDOWN ==='];
    $rows[] = ['DATE', 'BILLS', 'QTY', 'REVENUE', 'DISCOUNT', 'PAID', 'FREE', 'PRINT', 'BINDING'];
    $dateGroups = [];
    foreach ($jobs as $bill) {
        $date = $bill['job_date'];
        $billQty = 0;
        foreach ($bill['items'] as $item) $billQty += $item['qty'];
        if (!isset($dateGroups[$date])) $dateGroups[$date] = ['bills' => 0, 'qty' => 0, 'revenue' => 0, 'discount' => 0, 'paid' => 0, 'free' => 0, 'print' => 0, 'binding' => 0];
        $dateGroups[$date]['bills']++;
        $dateGroups[$date]['qty'] += $billQty;
        $dateGroups[$date]['revenue'] += $bill['total'];
        $dateGroups[$date]['discount'] += $bill['discount'];
        if ($bill['is_free']) $dateGroups[$date]['free']++;
        else $dateGroups[$date]['paid']++;
        foreach ($bill['items'] as $item) {
            $itemNet = $item['net_amount'] ?? 0;
            if ($item['job_type'] === 'binding') $dateGroups[$date]['binding'] += $itemNet;
            else $dateGroups[$date]['print'] += $itemNet;
        }
    }
    $grandBills = 0; $grandQty = 0; $grandRevenue = 0; $grandDiscount = 0; $grandPaid = 0; $grandFree = 0; $grandPrint = 0; $grandBinding = 0;
    ksort($dateGroups);
    foreach ($dateGroups as $date => $data) {
        $rows[] = [$date, $data['bills'], $data['qty'], number_format($data['revenue'], 2), number_format($data['discount'], 2), $data['paid'], $data['free'], number_format($data['print'], 2), number_format($data['binding'], 2)];
        $grandBills += $data['bills']; $grandQty += $data['qty']; $grandRevenue += $data['revenue']; $grandDiscount += $data['discount']; $grandPaid += $data['paid']; $grandFree += $data['free']; $grandPrint += $data['print']; $grandBinding += $data['binding'];
    }
    $rows[] = ['=== GRAND TOTAL ===', $grandBills, $grandQty, number_format($grandRevenue, 2), number_format($grandDiscount, 2), $grandPaid, $grandFree, number_format($grandPrint, 2), number_format($grandBinding, 2)];
}

// Add summary for non-financial reports
if ($reportType !== 'financial' && $reportType !== 'zreport' && $reportType !== 'xreport') {
    $rows[] = [];
    $rows[] = ['REPORT SUMMARY'];
    $rows[] = ['Total Bills', $summary['total_jobs']];
    $rows[] = ['Total Qty', $summary['total_qty']];
    $rows[] = ['Total Revenue', number_format($summary['total_revenue'], 2)];
    $rows[] = ['Print Revenue', number_format($summary['print_revenue'] ?? 0, 2)];
    $rows[] = ['Binding Revenue', number_format($summary['binding_revenue'] ?? 0, 2)];
    $rows[] = ['Total Discount', number_format($summary['total_discount'] ?? 0, 2)];
    $rows[] = ['Paid Bills', $summary['paid_jobs']];
    $rows[] = ['Free Bills', $summary['free_jobs']];
}

// Output
if ($format === 'pdf') {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="report.html"');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <style>
            @page { size: A4 landscape; margin: 6mm; }
            body { font-family: Arial, sans-serif; font-size: 7px; line-height: 1.1; }
            h2 { font-size: 14px; margin: 0 0 4px 0; color: #2c3e50; }
            .sub { font-size: 9px; margin: 0 0 6px 0; color: #7f8c8d; }
            table { border-collapse: collapse; width: 100%; margin-top: 4px; }
            th, td { border: 1px solid #bdc3c7; padding: 2px 3px; text-align: left; word-wrap: break-word; }
            th { background: #2c3e50; color: white; font-weight: bold; font-size: 7px; }
            td { font-size: 7px; }
            .grand-total { background: #042d5c; color: #ffc107 !important; font-weight: bold; }
            .date-header { background: #e8f0fe; font-weight: bold; }
            .bill-total { background: #fff3cd; font-weight: bold; }
            .footer { margin-top: 10px; font-size: 7px; color: #7f8c8d; text-align: center; border-top: 1px solid #bdc3c7; padding-top: 6px; }
        </style>
    </head>
    <body>
        <h2>' . ucfirst(str_replace('_', ' ', $reportType)) . ' Report</h2>
        <p class="sub">Period: ' . $from . ' to ' . $to . ' | Total Bills: ' . count($jobs) . ' | Generated: ' . date('Y-m-d H:i:s') . '</p>
        <table>
            <thead><tr>';
    foreach ($rows[0] as $col) {
        echo '<th>' . htmlspecialchars($col) . '</th>';
    }
    echo '</tr></thead><tbody>';
    for ($i = 1; $i < count($rows); $i++) {
        if (empty($rows[$i]) || !is_array($rows[$i])) continue;
        $isHeader = isset($rows[$i][0]) && (strpos($rows[$i][0], '===') !== false || strpos($rows[$i][0], '---') !== false || $rows[$i][0] === 'REPORT SUMMARY' || $rows[$i][0] === '=== FINANCIAL SUMMARY ===' || $rows[$i][0] === '=== DAILY BREAKDOWN ===' || $rows[$i][0] === '=== Z-REPORT TOTALS ===' || $rows[$i][0] === '=== X-REPORT TOTALS ===' || $rows[$i][0] === '=== GRAND TOTAL ===' || $rows[$i][0] === 'FINANCIAL REPORT' || $rows[$i][0] === 'Z-REPORT - HOURLY SALES SUMMARY' || $rows[$i][0] === 'X-REPORT - DAILY SALES SUMMARY');
        $isGrandTotal = isset($rows[$i][0]) && strpos($rows[$i][0], '=== GRAND TOTAL ===') !== false;
        $isBillTotal = isset($rows[$i][0]) && strpos($rows[$i][0], '--- BILL TOTAL ---') !== false;
        $isDateTotal = isset($rows[$i][0]) && strpos($rows[$i][0], '--- DATE TOTAL ---') !== false;
        if ($isGrandTotal) echo '<tr class="grand-total">';
        elseif ($isHeader || $isDateTotal) echo '<tr class="date-header">';
        elseif ($isBillTotal) echo '<tr class="bill-total">';
        else echo '<tr>';
        foreach ($rows[$i] as $cell) echo '<td>' . htmlspecialchars($cell) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<div class="footer">This report is automatically generated by BMS Library POS System</div>';
    echo '</body></html>';
} else {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report.csv"');
    $fp = fopen('php://output', 'w');
    foreach ($rows as $row) {
        if (is_array($row) && !empty($row)) fputcsv($fp, $row);
        else fputcsv($fp, ['']);
    }
    fclose($fp);
}
?>