<?php
// libPOS/lib_api.php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Colombo');

$basePath = dirname(__DIR__);
require_once $basePath . '/database/connection.php';

function jsonResponse($data) {
    ob_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getPrintCounts($conn, $date) {
    $singlePages = 0;
    $doublePages = 0;
    $errorPages = 0;
    
    // Get all counts in one query
    $sql = "SELECT 
                SUM(one_side) as single_total, 
                SUM(both_side) as double_total, 
                SUM(error_count) as error_total 
            FROM lib_printing_jobs 
            WHERE job_date = ? AND job_type = 'printing'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $singlePages = (int)($row['single_total'] ?? 0);
    $doublePages = (int)($row['double_total'] ?? 0);
    $errorPages = (int)($row['error_total'] ?? 0);
    $stmt->close();
    
    // Total Print Pages = Single + (Double × 2) + Error
    $totalPages = $singlePages + ($doublePages * 2) + $errorPages;
    
    return [
        'single_pages' => $singlePages,
        'double_pages' => $doublePages,
        'error_pages' => $errorPages,
        'total_pages' => $totalPages
    ];
}

if (!$conn || !($conn instanceof mysqli)) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed']);
}

if ($conn->connect_error) {
    jsonResponse(['success' => false, 'message' => 'Database connection error: ' . $conn->connect_error]);
}

if (!isset($_SESSION['username'])) {
    jsonResponse(['success' => false, 'message' => 'Session expired. Please login again.']);
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================
// SAVE JOB - Professional Bill Storage (FIXED)
// ============================================
if ($action === 'save_job') {

    try {

        if (empty($_POST['name'])) {
            throw new Exception("Customer name is required.");
        }

        $job_date   = $_POST['job_date'] ?? date('Y-m-d');
        $user_type  = $_POST['user_type'] ?? 'student';
        $name       = trim($_POST['name'] ?? '');
        $batch      = trim($_POST['batch'] ?? '');
        $user       = trim($_POST['user'] ?? $_SESSION['username'] ?? '');
        $is_free    = (int)($_POST['is_free'] ?? 0);
        $bill_time  = date('H:i:s');

        $cartItems = json_decode($_POST['items_json'] ?? '[]', true);

        if (!is_array($cartItems) || empty($cartItems)) {
            throw new Exception("No items found");
        }

        $conn->begin_transaction();

        // =========================
        // BILL ID (safe sequence)
        // =========================
        $prefix = date("Ym");

        $stmtID = $conn->prepare("
            SELECT bill_id 
            FROM lib_printing_jobs 
            WHERE bill_id LIKE ? 
            ORDER BY bill_id DESC 
            LIMIT 1
        ");

        $search = $prefix . "%";
        $stmtID->bind_param("s", $search);
        $stmtID->execute();
        $res = $stmtID->get_result();

        $seq = 1;
        if ($row = $res->fetch_assoc()) {
            $seq = ((int)substr($row['bill_id'], -4)) + 1;
        }

        $stmtID->close();

        $bill_id = $prefix . sprintf("%04d", $seq);

        // =========================
        // PREPARE INSERT
        // =========================
        $sql = "
            INSERT INTO lib_printing_jobs(
                bill_id, job_date, bill_date, bill_time,
                name, batch, user_type,
                job_type, item_category, catagory_id,
                one_side, both_side, rough_one, rough_two, error_count,
                total_sheets,
                binding_name, item_name_display, size,
                quantity,
                item_unit_price, subtotal,
                discount_amount, discount_percent,
                net_amount,
                is_free, user
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $invoiceTotal = 0;
        $inserted = 0;

        foreach ($cartItems as $item) {

            // =========================
            // INPUT NORMALIZATION
            // =========================
            $itemName = trim($item['name'] ?? '');
            $itemQty  = (int)($item['qty'] ?? 1);
            $itemSize = trim($item['size'] ?? '');
            $category = strtolower($item['category'] ?? 'printing');

            // =========================
            // GET VALUES FROM FRONTEND (POPUP)
            // =========================
            $itemPrice = (float)($item['price'] ?? 0);
            $subtotal = (float)($item['subtotal'] ?? 0);
            $discountPercent = (float)($item['discount_percent'] ?? 0);
            $discountAmount = (float)($item['discount_amount'] ?? 0);
            $netAmount = (float)($item['net_amount'] ?? 0);
            
            // =========================
            // DEBUG: Log what we received
            // =========================
            error_log("FRONTEND VALUES: " . json_encode([
                'item' => $itemName,
                'qty' => $itemQty,
                'price' => $itemPrice,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount
            ]));

            // =========================
            // FREE JOB OVERRIDE - FORCE 100% DISCOUNT
            // =========================
            if ($is_free == 1) {
                // Calculate subtotal from price * qty
                $subtotal = $itemPrice * $itemQty;
                // Set discount to 100% of subtotal
                $discountPercent = 100;
                $discountAmount = $subtotal;
                // Net amount becomes 0
                $netAmount = 0;
                
                error_log("FREE JOB OVERRIDE: subtotal={$subtotal}, discount_amount={$discountAmount}, net_amount={$netAmount}");
            } else {
                // =========================
                // FALLBACK: If frontend didn't send values, calculate from database
                // =========================
                if ($subtotal <= 0 && $itemPrice > 0) {
                    $subtotal = $itemPrice * $itemQty;
                }
                
                if ($discountPercent <= 0 && $discountAmount <= 0) {
                    // Try to get from database as fallback
                    $q = $conn->prepare("
                        SELECT amount, discount_percent, catagory_id
                        FROM lib_item
                        WHERE LOWER(item_name) = LOWER(?)
                        LIMIT 1
                    ");
                    $q->bind_param("s", $itemName);
                    $q->execute();
                    $r = $q->get_result();
                    if ($x = $r->fetch_assoc()) {
                        if ($itemPrice <= 0) {
                            $itemPrice = (float)$x['amount'];
                            $subtotal = $itemPrice * $itemQty;
                        }
                        if ($discountPercent <= 0) {
                            $discountPercent = (float)$x['discount_percent'];
                        }
                        $catId = (int)$x['catagory_id'];
                    }
                    $q->close();
                    
                    if ($discountPercent > 0 && $discountAmount <= 0) {
                        $discountAmount = ($subtotal * $discountPercent) / 100;
                    }
                    if ($netAmount <= 0) {
                        $netAmount = $subtotal - $discountAmount;
                    }
                }
            }

            // =========================
            // GET CATEGORY ID (if not set)
            // =========================
            $catId = 0;
            if (!isset($catId) || $catId <= 0) {
                $q2 = $conn->prepare("
                    SELECT catagory_id 
                    FROM lib_catagory 
                    WHERE LOWER(catagory_name) = LOWER(?)
                    LIMIT 1
                ");
                $q2->bind_param("s", $category);
                $q2->execute();
                $r2 = $q2->get_result();
                if ($x2 = $r2->fetch_assoc()) {
                    $catId = (int)$x2['catagory_id'];
                }
                $q2->close();
            }

            // ROUNDING
            $itemPrice = round($itemPrice, 2);
            $subtotal = round($subtotal, 2);
            $discountPercent = round($discountPercent, 2);
            $discountAmount = round($discountAmount, 2);
            $netAmount = round($netAmount, 2);

            // =========================
            // CATEGORY SPLIT LOGIC
            // =========================
            $one = $both = $r1 = $r2 = $err = 0;
            $totalSheets = 0;
            $binding = '';
            $display = $itemName;

            if ($category === "binding") {
                $binding = $itemName;
                if ($itemSize) {
                    $display .= " ($itemSize)";
                }
                // For binding, use quantity from item
                $quantity = $itemQty;
            } else {
                $quantity = $itemQty;
                $totalSheets = $itemQty;
                $low = strtolower($itemName);
                if (strpos($low, "one side") !== false || strpos($low, "single") !== false) {
                    $one = $itemQty;
                } elseif (strpos($low, "both side") !== false || strpos($low, "double") !== false) {
                    $both = $itemQty;
                } elseif (strpos($low, "rough one") !== false) {
                    $r1 = $itemQty;
                } elseif (strpos($low, "rough two") !== false) {
                    $r2 = $itemQty;
                } elseif (strpos($low, "error") !== false) {
                    $err = $itemQty;
                } else {
                    if (strpos($low, "rough") === false) {
                        $one = $itemQty;
                    } else {
                        // If it's rough but didn't match rough_one or rough_two, treat as rough_one
                        $r1 = $itemQty;
                    }
                }
            }

            $invoiceTotal += $netAmount;

            // =========================
            // DEBUG: Log what we are about to insert
            // =========================
            error_log("INSERTING VALUES: " . json_encode([
                'bill_id' => $bill_id,
                'item' => $display,
                'qty' => $quantity,
                'item_unit_price' => $itemPrice,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'one_side' => $one,
                'both_side' => $both,
                'is_free' => $is_free
            ]));

            // =========================
            // BIND VARIABLES - 27 parameters
            // =========================
            $stmt->bind_param(
                "issssssssiiiiiiisssiddddiss",
                $bill_id,
                $job_date,
                $job_date,
                $bill_time,
                $name,
                $batch,
                $user_type,
                $category,
                $category,
                $catId,
                $one,
                $both,
                $r1,
                $r2,
                $err,
                $totalSheets,
                $binding,
                $display,
                $itemSize,
                $quantity,
                $itemPrice,
                $subtotal,
                $discountAmount,
                $discountPercent,
                $netAmount,
                $is_free,
                $user
            );

            if (!$stmt->execute()) {
                throw new Exception("Insert failed: " . $stmt->error);
            }

            $inserted++;
        }

        $stmt->close();
        $conn->commit();

        jsonResponse([
            "success" => true,
            "bill_id" => $bill_id,
            "items" => $inserted,
            "total" => round($invoiceTotal, 2),
            "message" => "Job saved successfully"
        ]);

    } catch (Throwable $e) {
        if (isset($conn)) {
            $conn->rollback();
        }

        error_log("SAVE JOB ERROR: " . $e->getMessage());
        error_log("SAVE JOB STACK: " . $e->getTraceAsString());

        jsonResponse([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
}


// ============================================
// REPORT DATA - FINAL FIX
// ============================================
if ($action === 'report_data') {
    try {
        $from = $_GET['from'] ?? date('Y-m-d');
        $to = $_GET['to'] ?? date('Y-m-d');

        $sql = "
            SELECT
                bill_id,
                job_date,
                DATE_FORMAT(bill_time, '%H:%i') as time,
                name as customer,
                batch,
                user,
                user_type,
                job_type,
                item_name_display,
                binding_name,
                size,
                one_side,
                both_side,
                rough_one,
                rough_two,
                error_count,
                quantity,
                item_unit_price,
                discount_amount,
                discount_percent,
                net_amount,
                is_free,
                (SELECT catagory_color FROM lib_catagory WHERE LOWER(catagory_name) = LOWER(job_type) LIMIT 1) as category_color
            FROM lib_printing_jobs
            WHERE bill_date BETWEEN ? AND ?
            ORDER BY bill_id DESC, id ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $from, $to);
        $stmt->execute();
        $result = $stmt->get_result();

        $bills = [];
        $summary = [
            'total_revenue' => 0,
            'total_discount' => 0,
            'total_bills' => 0,
            'print_revenue' => 0,
            'binding_revenue' => 0,
            'print_count' => 0,
            'binding_count' => 0,
            'category_revenue' => []
        ];

        while ($row = $result->fetch_assoc()) {
            $billId = $row['bill_id'];

            if (!isset($bills[$billId])) {
                $bills[$billId] = [
                    'bill_id' => $billId,
                    'customer' => $row['customer'],
                    'batch' => $row['batch'] ?: '-',
                    'user' => $row['user'],
                    'user_type' => $row['user_type'],
                    'time' => $row['time'],
                    'status' => $row['is_free'] ? 'FREE' : 'PAID',
                    'total' => 0,
                    'discount' => 0,
                    'items' => []
                ];
            }

            $category = strtolower($row['job_type']);

            // Build full item name with size
            if ($category == 'binding') {
                $itemName = $row['binding_name'];
                if ($row['size']) {
                    $itemName .= " (" . $row['size'] . ")";
                }
                $qty = (int)$row['quantity'];
            } else {
                // In the item building section
                $displayParts = [];
                if ((int)$row['one_side'] > 0) $displayParts[] = "One Side ×" . (int)$row['one_side'];
                if ((int)$row['both_side'] > 0) $displayParts[] = "Both Side ×" . (int)$row['both_side'];
                if ((int)$row['rough_one'] > 0) $displayParts[] = "Rough One ×" . (int)$row['rough_one'];
                if ((int)$row['rough_two'] > 0) $displayParts[] = "Rough Both ×" . (int)$row['rough_two'];
                if ((int)$row['error_count'] > 0) $displayParts[] = "Error ×" . (int)$row['error_count'];

                $itemName = implode(', ', $displayParts);
                if (empty($itemName)) {
                    $itemName = $row['item_name_display'] ?: 'Printing Item';
                }
                if ($row['size']) {
                    $itemName .= " (" . $row['size'] . ")";
                }

                // FIX: For display purposes, show all quantities
                $qty = (int)$row['one_side'] + 
                    (int)$row['both_side'] + 
                    (int)$row['rough_one'] + 
                    (int)$row['rough_two'] + 
                    (int)$row['error_count'];

                // FIX: For print_count, ONLY count one_side + both_side + error
                // DO NOT include rough_one and rough_two
                $printQty = (int)$row['one_side'] + (int)$row['both_side'] + (int)$row['error_count'];
            }

            // For free jobs, ensure net_amount is 0 in display
            $netAmount = (float)$row['net_amount'];
            $discountAmount = (float)$row['discount_amount'];
            $isFree = (int)$row['is_free'];
            
            if ($isFree) {
                // For free jobs, show the discount amount correctly
                // The net amount should be 0, and discount should equal subtotal
                $discountAmount = (float)$row['discount_amount'];
                $itemPrice = (float)$row['item_unit_price'];
                $subtotal = $itemPrice * $qty;
                if ($discountAmount == 0) {
                    // If discount_amount is 0, recalculate it as 100% of subtotal
                    $discountAmount = $subtotal;
                    $netAmount = 0;
                }
            }

            $item = [
                'category' => $row['job_type'],
                'category_color' => $row['category_color'] ?? '#6c757d',
                'item_name' => $itemName,
                'qty' => $qty,
                'price' => (float)$row['item_unit_price'],
                'discount' => $discountAmount,
                'discount_percent' => (float)$row['discount_percent'] ?: ($isFree ? 100 : 0),
                'total' => $netAmount
            ];

            $bills[$billId]['items'][] = $item;
            $bills[$billId]['total'] += $item['total'];
            $bills[$billId]['discount'] += $item['discount'];

            // SUMMARY
            $summary['total_revenue'] += $item['total'];
            $summary['total_discount'] += $item['discount'];

            if (!isset($summary['category_revenue'][$category])) {
                $summary['category_revenue'][$category] = 0;
            }
            $summary['category_revenue'][$category] += $item['total'];


// In the while loop, when calculating print_count
// Print vs Binding (everything except binding goes to print)
if ($category == "binding") {
    $summary['binding_revenue'] += $item['total'];
    $summary['binding_count'] += $qty;
} else {
    $summary['print_revenue'] += $item['total'];
    // FIX: Only count one_side + both_side + error_count for print jobs
    // DO NOT include rough_one and rough_two
    $printJobQty = (int)$row['one_side'] + (int)$row['both_side'] + (int)$row['error_count'];
    $summary['print_count'] += $printJobQty;
}
        }

        $stmt->close();
        $jobs = array_values($bills);
        $summary['total_bills'] = count($jobs);

// Get print counts for today
$todayPrintCounts = getPrintCounts($conn, $from);

// Get error count and rough sheets for today
$todayErrorCount = 0;
$todayRoughSheets = 0;

$errorSql = "SELECT SUM(error_count) as total_error, SUM(rough_one + rough_two) as total_rough FROM lib_printing_jobs WHERE job_date = ?";
$errorStmt = $conn->prepare($errorSql);
$errorStmt->bind_param("s", $from);
$errorStmt->execute();
$errorResult = $errorStmt->get_result();
if ($errorRow = $errorResult->fetch_assoc()) {
    $todayErrorCount = (int)($errorRow['total_error'] ?? 0);
    $todayRoughSheets = (int)($errorRow['total_rough'] ?? 0);
}
$errorStmt->close();

$today_sales = [
    'today_sales' => $summary['total_revenue'],
    'total_jobs' => $summary['total_bills'],
    'print_revenue' => $summary['print_revenue'],
    'binding_revenue' => $summary['binding_revenue'],
    'print_count' => $summary['print_count'],
    'binding_count' => $summary['binding_count'],
    'print_counts' => [
        'single_pages' => $todayPrintCounts['single_pages'],
        'double_pages' => $todayPrintCounts['double_pages'],
        'error_pages' => $todayPrintCounts['error_pages'], 
        'total_pages' => $todayPrintCounts['total_pages']
    ],
    'error_count' => $todayErrorCount,
    'rough_sheets' => $todayRoughSheets
];

        jsonResponse([
            'success' => true,
            'today_sales' => $today_sales,
            'summary' => [
                'total_revenue' => $summary['total_revenue'],
                'total_discount' => $summary['total_discount'],
                'total_jobs' => $summary['total_bills'],
                'category_revenue' => $summary['category_revenue'],
                'print_revenue' => $summary['print_revenue'],
                'binding_revenue' => $summary['binding_revenue'],
                'print_count' => $summary['print_count'],
                'binding_count' => $summary['binding_count']
            ],
            'jobs' => $jobs
        ]);

    } catch (Exception $e) {
        jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

    // ============================================
    // GET CATEGORIES
    // ============================================
    if ($action === 'get_categories') {
        $result = $conn->query("SELECT * FROM lib_catagory ORDER BY catagory_name");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        jsonResponse($categories);
    }

    // ============================================
    // GET ITEMS
    // ============================================
    if ($action === 'get_items') {
        $category = $_GET['category'] ?? '';
        if (!empty($category) && $category !== 'All') {
            $stmt = $conn->prepare("
                SELECT i.*, c.catagory_name, c.catagory_color, 
                       sc.sub_catagory_name, sc.sub_catagory_color
                FROM lib_item i 
                LEFT JOIN lib_catagory c ON i.catagory_id = c.catagory_id 
                LEFT JOIN lib_sub_category sc ON i.sub_catagory_id = sc.id
                WHERE c.catagory_name = ? AND i.is_active = 1
                ORDER BY sc.sub_catagory_name, i.item_name
            ");
            $stmt->bind_param("s", $category);
        } else {
            $stmt = $conn->prepare("
                SELECT i.*, c.catagory_name, c.catagory_color,
                       sc.sub_catagory_name, sc.sub_catagory_color
                FROM lib_item i 
                LEFT JOIN lib_catagory c ON i.catagory_id = c.catagory_id 
                LEFT JOIN lib_sub_category sc ON i.sub_catagory_id = sc.id
                WHERE i.is_active = 1
                ORDER BY c.catagory_name, sc.sub_catagory_name, i.item_name
            ");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        jsonResponse($items);
    }

    // ============================================
    // INVALID ACTION
    // ============================================
    jsonResponse([
        'success' => false,
        'message' => 'Invalid action: ' . $action
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Fatal error: ' . $e->getMessage()
    ]);
}

if (ob_get_level()) ob_end_clean();
?>