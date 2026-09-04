<?php
// lib_report_data.php
ob_clean();
date_default_timezone_set('Asia/Colombo');

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    $basePath = dirname(__DIR__);
    require_once $basePath . '/database/connection.php';

    if (!$conn || !($conn instanceof mysqli)) {
        throw new Exception("Database connection failed");
    }

    $from = $_GET['from'] ?? date('Y-m-01');
    $to = $_GET['to'] ?? date('Y-m-d');
    $category = $_GET['category'] ?? 'both';
    $reportType = $_GET['report_type'] ?? 'all';
    $itemName = $_GET['item_name'] ?? 'all';

    if (!$from || !$to) {
        throw new Exception("Invalid date range");
    }

    $job_type_filter = '';
    if ($category === 'print') {
        $job_type_filter = "AND job_type = 'printing'";
    } elseif ($category === 'binding') {
        $job_type_filter = "AND job_type = 'binding'";
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
            item_name_display,
            DATE_FORMAT(created_at, '%H:%i') as job_time
        FROM lib_printing_jobs
        WHERE job_date BETWEEN ? AND ?
        $job_type_filter
        ORDER BY bill_id DESC, id ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
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

    // Filter by item name for item report - FIXED
    if ($reportType === 'item' && $itemName !== 'all') {
        $filteredJobs = [];
        $itemNameClean = strtolower(trim($itemName));
        
        foreach ($allJobs as $job) {
            // Build the display name for this job
            $jobDisplayName = '';
            
            if ($job['job_type'] === 'binding') {
                $jobDisplayName = trim($job['binding_name'] ?: 'Binding');
                if (!empty($job['size'])) {
                    $jobDisplayName .= ' (' . trim($job['size']) . ')';
                }
            } else {
                // Use item_name_display if available
                if (!empty($job['item_name_display'])) {
                    $jobDisplayName = trim($job['item_name_display']);
                } else {
                    // Build from parts
                    $parts = [];
                    if ($job['one_side'] > 0) $parts[] = "One Side ×" . (int)$job['one_side'];
                    if ($job['both_side'] > 0) $parts[] = "Both Side ×" . (int)$job['both_side'];
                    if ($job['rough_one'] > 0) $parts[] = "Rough One ×" . (int)$job['rough_one'];
                    if ($job['rough_two'] > 0) $parts[] = "Rough Both ×" . (int)$job['rough_two'];
                    if ($job['error_count'] > 0) $parts[] = "Error ×" . (int)$job['error_count'];
                    $jobDisplayName = implode(', ', $parts) ?: 'Printing Item';
                }
                // Add size if available
                if (!empty($job['size']) && strpos($jobDisplayName, $job['size']) === false) {
                    // Check if size is already in the display name
                    if (!preg_match('/\(.*' . preg_quote($job['size'], '/') . '.*\)/', $jobDisplayName)) {
                        $jobDisplayName .= ' (' . trim($job['size']) . ')';
                    }
                }
            }
            
            // Clean the display name for comparison
            $jobDisplayNameClean = strtolower(trim($jobDisplayName));
            
            // Check if the job display name contains the selected item name
            // or if the selected item name is part of the job display name
            $matchFound = false;
            
            // Exact match check (case insensitive)
            if ($jobDisplayNameClean === $itemNameClean) {
                $matchFound = true;
            }
            
            // Contains check
            if (!$matchFound && strpos($jobDisplayNameClean, $itemNameClean) !== false) {
                $matchFound = true;
            }
            
            // Check if item name contains part of the job name
            if (!$matchFound && strpos($itemNameClean, $jobDisplayNameClean) !== false) {
                $matchFound = true;
            }
            
            // Check without size for printing items
            if (!$matchFound && $job['job_type'] !== 'binding') {
                // Remove size from job display name for comparison
                $jobNameWithoutSize = preg_replace('/\s*\([^)]*\)/', '', $jobDisplayNameClean);
                if (strpos($jobNameWithoutSize, $itemNameClean) !== false || 
                    strpos($itemNameClean, $jobNameWithoutSize) !== false) {
                    $matchFound = true;
                }
            }
            
            if ($matchFound) {
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
        
        // Build item name for display
        if ($job['job_type'] === 'binding') {
            $itemNameDisplay = trim($job['binding_name'] ?: 'Binding');
            if (!empty($job['size'])) {
                $itemNameDisplay .= " (" . trim($job['size']) . ")";
            }
            $qty = (int)$job['quantity'];
            $price = $job['item_unit_price'];
            $category = 'binding';
        } else {
            if (!empty($job['item_name_display'])) {
                $itemNameDisplay = trim($job['item_name_display']);
            } else {
                $parts = [];
                if ($job['one_side'] > 0) $parts[] = "One Side ×" . (int)$job['one_side'];
                if ($job['both_side'] > 0) $parts[] = "Both Side ×" . (int)$job['both_side'];
                if ($job['rough_one'] > 0) $parts[] = "Rough One ×" . (int)$job['rough_one'];
                if ($job['rough_two'] > 0) $parts[] = "Rough Both ×" . (int)$job['rough_two'];
                if ($job['error_count'] > 0) $parts[] = "Error ×" . (int)$job['error_count'];
                $itemNameDisplay = implode(', ', $parts) ?: 'Printing Item';
            }
            // Add size if not already included
            if (!empty($job['size']) && strpos($itemNameDisplay, $job['size']) === false) {
                if (!preg_match('/\(.*' . preg_quote($job['size'], '/') . '.*\)/', $itemNameDisplay)) {
                    $itemNameDisplay .= " (" . trim($job['size']) . ")";
                }
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
    $total_jobs = count($jobs);
    $total_revenue = 0;
    $print_revenue = 0;
    $binding_revenue = 0;
    $free_jobs = 0;
    $paid_jobs = 0;
    $total_discount = 0;
    $total_qty = 0;

    foreach ($jobs as $bill) {
        $total_revenue += $bill['total'];
        $total_discount += $bill['discount'];
        $billQty = 0;
        foreach ($bill['items'] as $item) {
            $billQty += $item['qty'];
            $itemNet = $item['net_amount'] ?? 0;
            if ($item['job_type'] === 'binding') {
                $binding_revenue += $itemNet;
            } else {
                $print_revenue += $itemNet;
            }
        }
        $total_qty += $billQty;
        if ($bill['is_free']) $free_jobs++;
        else $paid_jobs++;
    }

    $categories = [];
    $catResult = $conn->query("SELECT catagory_id, catagory_name, catagory_color, icon FROM lib_catagory ORDER BY catagory_name");
    if ($catResult) {
        while ($row = $catResult->fetch_assoc()) {
            $categories[$row['catagory_name']] = $row;
        }
    }

    $response = [
        'success' => true,
        'jobs' => $jobs,
        'categories' => $categories,
        'summary' => [
            'total_jobs' => $total_jobs,
            'total_revenue' => round($total_revenue, 2),
            'print_revenue' => round($print_revenue, 2),
            'binding_revenue' => round($binding_revenue, 2),
            'free_jobs' => $free_jobs,
            'paid_jobs' => $paid_jobs,
            'total_discount' => round($total_discount, 2),
            'total_qty' => $total_qty
        ],
        'report_type' => $reportType,
        'from' => $from,
        'to' => $to
    ];

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>