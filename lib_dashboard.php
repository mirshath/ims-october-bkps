<?php
// lib_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

ob_start();
date_default_timezone_set('Asia/Colombo');

include("includes/header.php");
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/lib_functions.php';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

require_once 'PermissionChecking.php';

$today = date('Y-m-d');
$loginUser = $_SESSION['username'] ?? '';

// --- MACHINE COUNT MANAGEMENT ---
$stmt = $conn->prepare("SELECT id, count, end_count FROM lib_machine_log WHERE log_date = ? AND end_count IS NULL ORDER BY id DESC LIMIT 1");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$activeRow = $result->fetch_assoc();
$stmt->close();

if (!$activeRow) {
    header('Location: lib_start_day.php');
    exit;
}

$machineCount = $activeRow['count'];
$dayStarted = true;

// Get latest machine count
$stmt = $conn->prepare("SELECT end_count FROM lib_machine_log WHERE end_count IS NOT NULL ORDER BY id DESC LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$lastEndRow = $result->fetch_assoc();
$stmt->close();
$lastEndCount = $lastEndRow['end_count'] ?? null;

if ($lastEndCount === null) {
    $stmt = $conn->prepare("SELECT count FROM lib_machine_log ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $lastCountRow = $result->fetch_assoc();
    $stmt->close();
    $lastEndCount = $lastCountRow['count'] ?? null;
}

// Get previous day's end count
$stmt = $conn->prepare("SELECT end_count FROM lib_machine_log WHERE log_date < ? AND end_count IS NOT NULL ORDER BY log_date DESC, id DESC LIMIT 1");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();
$lastMachineCount = $row['end_count'] ?? null;

if ($lastMachineCount === null) {
    $stmt = $conn->prepare("SELECT count FROM lib_machine_log WHERE log_date < ? ORDER BY log_date DESC, id DESC LIMIT 1");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $lastMachineCount = $row['count'] ?? null;
}

// Handle Day End
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['end_day'])) {
    $end_count = (int)$_POST['end_count'];
    $stmt = $conn->prepare("UPDATE lib_machine_log SET end_count = ? WHERE id = ?");
    $stmt->bind_param("ii", $end_count, $activeRow['id']);
    $stmt->execute();
    $stmt->close();
    header('Location: lib_start_day.php?msg=Day+ended+with+count+'.$end_count);
    exit;
}

// Today's summary
$todayRevenue = getSummaryDisplay($conn, 'today', 'revenue');
$todayBindingRev = getSummaryDisplay($conn, 'today', 'binding_revenue');
$todayJobs = getSummaryDisplay($conn, 'today', 'jobs');
$todayBindingJobs = getSummaryDisplay($conn, 'today', 'binding_jobs');

// Get today's print counts for new cards
$printCounts = getPrintCounts($conn, $today);
$singlePages = $printCounts['single_pages'] ?? 0;
$doublePages = $printCounts['double_pages'] ?? 0;
$errorPages = $printCounts['error_pages'] ?? 0;
$totalPrintPages = $singlePages + ($doublePages * 2) + $errorPages;

// Fetch categories
$categories = [];
$catResult = $conn->query("SELECT * FROM lib_catagory ORDER BY catagory_name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch sub categories
$subCategories = [];
$subCatResult = $conn->query("SELECT * FROM lib_sub_category WHERE is_active = 1 ORDER BY sub_catagory_name");
if ($subCatResult) {
    while ($row = $subCatResult->fetch_assoc()) {
        $subCategories[$row['catagory_id']][] = $row;
    }
}

// Fetch all items grouped by category
$allItems = [];
foreach ($categories as $cat) {
    if ($cat['catagory_name'] === 'All') continue;
    $stmt = $conn->prepare("
        SELECT i.*, c.catagory_name, c.catagory_color, 
               sc.sub_catagory_name, sc.sub_catagory_color
        FROM lib_item i 
        LEFT JOIN lib_catagory c ON i.catagory_id = c.catagory_id 
        LEFT JOIN lib_sub_category sc ON i.sub_catagory_id = sc.id
        WHERE i.catagory_id = ? AND i.is_active = 1 
        ORDER BY sc.sub_catagory_name, i.item_name
    ");
    $stmt->bind_param("i", $cat['catagory_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $allItems[] = $row;
    }
    $stmt->close();
}

// Define API base path
$apiBase = 'libPOS/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BMS Library POS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    
    .pos-container { background: white; padding: 15px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
    .today-sales { background: linear-gradient(135deg, #042d5c 0%, #011836 100%); padding: 15px; border-radius: 12px; color: white; margin-top: 0px; height: 200; }
    .today-sales .stat-box { background: rgba(255,255,255,0.12); padding: 5px; border-radius: 10px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
    .today-sales .stat-number { font-size: 20px; font-weight: 500; color: white; text-align: right; }
    .today-sales .text-muted { color: rgba(255,255,255,0.8) !important; }
    .top-bar { background: white; padding: 10px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .pos-header { background: #dc3545; color: white; padding: 12px 20px; border-radius: 12px 12px 0 0; font-weight: 600; }
    .cart-table td { vertical-align: middle; padding: 6px; font-size: 0.85rem; }
    .stat-box { background: white; padding: 12px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .stat-number { font-size: 22px; font-weight: 700; }
    .discount-badge { background: #28a745; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.7rem; }
    .left-panel { border-right: 1px solid #e9ecef; padding-right: 20px; }
    .right-panel { padding-left: 20px; }
    @media (max-width: 768px) {
        .left-panel { border-right: none; border-bottom: 1px solid #e9ecef; margin-bottom: 20px; padding-right: 0; }
        .right-panel { padding-left: 0; }
    }
    .form-label { font-weight: 600; font-size: 0.85rem; color: #495057; }
    .form-control, .form-select { border-radius: 10px; border: 1px solid #e9ecef; padding: 8px 12px; }
    .btn { border-radius: 10px; font-weight: 600; padding: 8px 20px; }
    .btn-primary { background: #042d5c; border: none; }
    .btn-primary:hover { background: #031f40; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(4,45,92,0.4); }
    .machine-count-display { background: #e9ecef; padding: 8px 16px; border-radius: 8px; display: inline-block; }
    .category-tabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0 10px 0; }
    .category-tab { padding: 6px 16px; border: none; border-radius: 12px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: all 0.3s ease; color: white; display: flex; align-items: center; gap: 5px; height:35px; width: 120px; text-align: center; justify-content: center; }
    .category-tab:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .category-tab.active { box-shadow: 0 0 0 3px rgba(255,255,255,0.5); transform: translateY(-2px); }
    .category-tab-all { background: #6c757d; }
    .category-tab-printing { background: #007bff; }
    .category-tab-binding { background: #13796b; }
    .category-tab-lamination { background: #fd7e14; }
    .category-tab-cutting { background: #dc3545; }
    .item-buttons-container { display: flex; flex-wrap: wrap; gap: 6px; padding: 5px 0; max-height: 400px; min-height: 300px; overflow-y: auto; }
    .item-btn { width: 160px; min-height: 35px; max-height:42px; padding: 6px 12px; border: 2px solid #f0718d; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s ease; font-size: 0.8rem; font-weight: 500; color: #2e2d2d; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-align: center; position: relative; }
    .item-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #ca0a2a;  }
    .item-btn .qty-badge { background: #b3021f; color: white; border-radius: 50%; padding: 0 6px; font-size: 0.65rem; font-weight: 700; min-width: 18px; text-align: center; line-height: 18px; }
    .item-btn.has-qty { background: #e8f5e9; border-color: #28a745; }
    .cart-container { max-height: 220px; overflow-y: auto; }
    .cart-table th { font-size: 0.75rem; padding: 4px 6px; background: #222324; color: #f1f1f1; text-align: center;}
    .cart-table td { font-size: 0.8rem; padding: 4px 6px; }
    .cart-table .qty-cell input { width: 45px; text-align: center; border: 1px solid #ddd; border-radius: 4px; padding: 2px; font-size: 0.8rem; }
    .cart-table .remove-btn { color: #dc3545; background: none; border: none; cursor: pointer; font-size: 1rem; padding: 0 4px; }
    .summary-section { background: white; border-radius: 8px; padding: 12px; margin-top: 10px; border: 1px solid #e9ecef; }
    .summary-row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 0.9rem; }
    .summary-row.total { font-size: 1.1rem; font-weight: 700; border-top: 2px solid #dee2e6; padding-top: 8px; margin-top: 4px; }
    .summary-row .label { color: #6c757d; }
    .summary-row .value.discount { color: #dc3545; }
    .summary-row .value.total-amount { color: #28a745; }
    .category-section { margin-bottom: 6px; padding: 4px 0; width: 100%; }
    .category-section .cat-title { font-weight: 600; font-size: 0.75rem; color: #495057; margin-bottom: 3px; }
    .category-section .cat-title .badge { font-size: 0.7rem; padding: 2px 10px; }
    .sub-cat-title { font-size: 0.7rem; color: #6c757d; margin: 2px 0 2px 10px; }
    .sub-cat-title .badge { font-size: 0.65rem; padding: 1px 8px; }
    .main-table-left { width: 60%; vertical-align: top; padding-right: 15px; }
    .main-table-right { width: 40%; vertical-align: top; padding-left: 15px; }
    .name-input-wrapper { position: relative; }
    .report-table { font-size: 0.85rem; }
    .report-table th { background: #f8f9fa; font-weight: 600; }
    .period-nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .free-text { color: #dc3545; font-weight: 600; }
    .badge-printing { background: #007bff; color: white; }
    .badge-binding { background: #13796b; color: white; }
    .badge-rough { background: #fd7e14; color: white; }
    .badge-error { background: #dc3545; color: white; }
    .badge-free { background: #dc3545; color: white; }
    .badge-paid { background: #28a745; color: white; }
    .autocomplete-items {
        position: absolute;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        background-color: #fff;
        z-index: 9999;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 250px;
        overflow-y: auto;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-top: 4px;
    }
    .autocomplete-items div {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f2f5;
        font-size: 0.85rem;
        line-height: 1.4;
        color: #495057;
    }
    .autocomplete-items div:hover {
        background-color: #f8f9fa;
        color: #042d5c;
    }
    .autocomplete-items div:last-child {
        border-bottom: none;
    }
    .autocomplete-items div .item-code {
        font-weight: bold;
        color: #042d5c;
    }
    .autocomplete-items div .item-detail {
        font-size: 0.75rem;
        color: #6c757d;
    }
    .report-table .bill-header {
        background: #f8f9fa !important;
        border-top: 2px solid #042d5c !important;
    }
    .report-table .grand-total {
        background: #042d5c !important;
        color: white !important;
    }
    .report-table .grand-total td {
        font-size: 1rem;
        font-weight: 700;
        color: #e5ff00 !important;
        background-color: #012555;
    }
    .print-stat-cards .stat-box { background: rgba(255,255,255,0.1); padding: 8px 12px; border-radius: 8px; }
    .print-stat-cards .stat-number { font-size: 20px; font-weight: 700; }
    @media (max-width: 768px) {
        .category-tabs { flex-wrap: nowrap; overflow-x: auto; padding: 5px 0; }
        .category-tab { padding: 5px 12px; font-size: 0.7rem; white-space: nowrap; }
        .item-btn { padding: 6px 12px; font-size: 0.75rem; }
        .cart-table td { font-size: 0.7rem; padding: 3px 4px; }
        .cart-table .qty-cell input { width: 35px; font-size: 0.7rem; }
    }

    /* Custom POS User Type Backgrounds */
.bg-student {
    background-color: #94173c !important;
    color: white;
    width: 3rem;
    height: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 10px;
}

.bg-staff {
    background-color: #10395a !important;
    color: white;
    width: 3rem;
    height: 1.5rem;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 10px;
}
</style>
</head>
<body>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Library POS Dashboard</h4>
                </div>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between" style="height: 60px;">
    
    <!-- LEFT SIDE: Icon & Title -->
    <div class="d-flex align-items-center">
        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
            <i class="fas fa-print"></i>
        </span>
        <!-- Changed &nbsp; strings to a clean Bootstrap margin class (ms-2) -->
        <h6 class="mb-0 ms-2">POS System</h6>
    </div>

    <!-- CENTER SIDE: Date & Machine Counts -->
    <div class="d-flex align-items-center gap-4">
        <div class="date-info">
            <i class="bi bi-calendar3 me-1"></i> <?= date('l, F j, Y') ?>
        </div>
        <div class="machine-count-display">
            <i class="bi bi-speedometer2 me-1"></i> Meter
            Start: <?= $machineCount ?? 'Not set' ?>
            <?php if ($lastEndCount !== null): ?> | Last End Count: <?= $lastEndCount ?><?php endif; ?>
            <?php if ($lastMachineCount !== null): ?> | Previous Day End: <?= $lastMachineCount ?><?php endif; ?>
        </div>
    </div>

    <!-- RIGHT SIDE: Action Buttons -->
    <div class="d-flex align-items-center gap-1">
        <?php if ($dayStarted): ?>
            <button class="btn btn-outline-danger btn-sm" onclick="openDayEndModal()">
                <i class="bi bi-stop-circle"></i> End Day
            </button>
        <?php endif; ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

</div>
                            <!-- Top Bar -->

                            <div class="card-body">

<!-- Day End Modal -->
<div class="modal fade" id="dayEndModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger"><i class="bi bi-stop-circle"></i> End Day</h5></div>
            <form method="post">
                <div class="modal-body">
                    <p>Enter ending machine count to close the day.</p>
                    <div class="mb-3">
                        <label class="form-label">Start Count (Today)</label>
                        <input type="text" class="form-control" value="<?= $machineCount ?? 'Not set' ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Count</label>
                        <input type="number" name="end_count" class="form-control form-control-lg" required min="<?= $machineCount ?? 0 ?>" placeholder="e.g. 5678">
                    </div>
                    <div class="alert alert-info">
                        <strong>Today's Summary:</strong><br>
                        Total Jobs: <?= $todayJobs ?? 0 ?><br>
                        Total Revenue: <?= number_format($todayRevenue ?? 0, 2) ?><br>
                        Print Revenue: <?= number_format(($todayRevenue ?? 0) - ($todayBindingRev ?? 0), 2) ?><br>
                        Binding Revenue: <?= number_format($todayBindingRev ?? 0, 2) ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="end_day" value="1" class="btn btn-danger">End Day</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-success"><i class="bi bi-check-circle"></i> Success</h5></div>
            <div class="modal-body" id="successMessage">Job saved successfully!</div>
            <div class="modal-footer"><button class="btn btn-primary" data-bs-dismiss="modal">OK</button></div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle"></i> Error</h5></div>
            <div class="modal-body" id="errorMessage">An error occurred.</div>
            <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">OK</button></div>
        </div>
    </div>
</div>

<!-- Bill Confirmation Modal -->
<div class="modal fade" id="billModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-receipt"></i> Job Confirmation</h5></div>
            <div class="modal-body" id="billContent"></div>
            <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" id="confirmSave">Confirm & Save</button></div>
        </div>
    </div>
</div>



<!-- POS Container -->
<div class="pos-container">
    <div class="row gx-3 align-items-start">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td class="main-table-left">
                    <div class="left-panel">
                        <div class="pos-header"><i class="bi bi-plus-circle me-2"></i> New Job Entry</div>
                        <form id="jobForm" class="p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="job_date" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">User Type</label>
                                    <select name="user_type" class="form-select" id="userType" onchange="toggleUserType()">
                                        <option value="student">Student</option>
                                        <option value="staff">Staff</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <div class="name-input-wrapper">
                                        <input type="text" name="name" id="customerName" class="form-control" required placeholder="Search Student/Staff..." autocomplete="off">
                                        <div id="autocompleteList" class="autocomplete-items" style="display:none;"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ID Info</label>
                                    <input type="text" name="batch" id="batchInfo" class="form-control" placeholder="Batch" readonly>
                                </div>
                            </div>
                        </form>

                        <!-- Category Tabs -->
                        <div class="category-tabs" id="categoryTabs">
                            <button class="category-tab category-tab-all active" data-category="All" data-category-id="0">
                                <i class="bi bi-grid"></i> All
                            </button>
                            <?php foreach ($categories as $cat): 
                                if ($cat['catagory_name'] === 'All') continue;
                                $tabClass = 'category-tab-' . strtolower($cat['catagory_name']);
                                $color = $cat['catagory_color'] ?? '#6c757d';
                            ?>
                                <button class="category-tab <?= $tabClass ?>" 
                                        data-category="<?= htmlspecialchars($cat['catagory_name']) ?>"
                                        data-category-id="<?= $cat['catagory_id'] ?>"
                                        style="background: <?= $color ?>;">
                                    <span><?= $cat['icon'] ?? 'ðŸ“„' ?></span>
                                    <?= htmlspecialchars($cat['catagory_name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Item Buttons -->
                        <div id="itemsContainer">
                            <div class="item-buttons-container" id="itemButtonsContainer">
                                <?php 
                                $currentCategory = '';
                                $currentSubCategory = '';
                                foreach ($allItems as $item): 
                                    $catName = $item['catagory_name'] ?? '';
                                    $subCatName = $item['sub_catagory_name'] ?? '';
                                    $subCatColor = $item['sub_catagory_color'] ?? '#6c757d';
                                    
                                    if ($currentCategory !== $catName && $catName !== 'All' && $catName !== ''):
                                        if ($currentCategory !== ''): ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="category-section" style="background: #f4f9ff"  data-category="<?= htmlspecialchars($catName) ?>">
                                            <div class="cat-title"><span class="badge" style="width: 120px; height:120%; background:<?= htmlspecialchars($item['catagory_color'] ?? '#6c757d') ?>"><?= htmlspecialchars($catName) ?></span></div>
                                    <?php 
                                        $currentCategory = $catName;
                                        $currentSubCategory = '';
                                    endif;
                                    
                                    // Check if sub category changed
                                    if ($subCatName && $subCatName !== $currentSubCategory):
                                        if ($currentSubCategory !== ''): ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="sub-cat-title" style="margin: 0 0 2px 0;">
                                            <span class="badge" style="background:<?= htmlspecialchars($subCatColor) ?>; font-size:0.65rem;"><?= htmlspecialchars($subCatName) ?></span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1" style="margin-left: 10px;">
                                    <?php 
                                        $currentSubCategory = $subCatName;
                                    endif;
                                ?>
                                    <button class="item-btn category-<?= strtolower($catName) ?>" 
                                            style="min-width:80px; text-align:center; justify-content:center;margin: 0 0 3px 0;"
                                            data-item-id="<?= $item['id'] ?>"
                                            data-item-name="<?= htmlspecialchars($item['item_name']) ?>"
                                            data-item-size="<?= htmlspecialchars($item['size'] ?? '') ?>"
                                            data-item-price="<?= $item['amount'] ?>"
                                            data-discount="<?= $item['discount_percent'] ?>"
                                            data-valid-discount="<?= htmlspecialchars($item['valid_discount'] ?? '') ?>"
                                            data-category-id="<?= $item['catagory_id'] ?>"
                                            data-category-name="<?= htmlspecialchars($catName) ?>"
                                            data-sub-category="<?= htmlspecialchars($subCatName) ?>"
                                            onclick="addItem(<?= $item['id'] ?>)">
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <span class="qty-badge" id="badge-<?= $item['id'] ?>">0</span>
                                    </button>
                                <?php endforeach; ?>
                                <?php if ($currentCategory !== ''): ?>
                                            </div>
                                        </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="one_side" id="hiddenOneSide" value="0">
                        <input type="hidden" name="both_side" id="hiddenBothSide" value="0">
                        <input type="hidden" name="rough_one" id="hiddenRoughOne" value="0">
                        <input type="hidden" name="rough_two" id="hiddenRoughTwo" value="0">
                        <input type="hidden" name="error_count" id="hiddenError" value="0">
                        <input type="hidden" name="quantity" id="hiddenBindingQty" value="0">
                        <input type="hidden" name="binding_name" id="hiddenBindingName" value="">
                        <input type="hidden" name="binding_size" id="hiddenBindingSize" value="">
                        <input type="hidden" name="total_amount" id="hiddenTotal" value="0">
                        <input type="hidden" name="discount_amount" id="hiddenDiscount" value="0">
                        <input type="hidden" name="net_amount" id="hiddenNet" value="0">
                        <input type="hidden" name="is_free" id="hiddenIsFree" value="0">
                        <input type="hidden" name="user" id="hiddenUser" value="<?= $loginUser ?>">
                        <input type="hidden" name="paper_size" id="hiddenPaperSize" value="A4">
                        <input type="hidden" name="job_type" id="hiddenJobType" value="printing">
                    </div>
                </td>
                <td class="main-table-right">
                    <div class="right-panel">
                        <div class="today-sales mb-2">
                            <h6 class="mb-2">
                                <i class="bi bi-graph-up-arrow me-1"></i>Today's Sales: 
                                <span class="stat-number" id="todayTotalSale" style="color: #ffc107; font-size: 20px; font-weight: 700;">0.00</span>
                                <span style="color: #c8e2ff; font-size: 14px; font-weight: 500;">
                                    ( Total Jobs: <span class="stat-number" id="todayTotalJobs" style="color: #c8e2ff; font-size: 14px; font-weight: 500;">0</span>)
                                </span>
                            </h6>
                            <div class="row g-1">
                                <div class="col-6"><div class="stat-box"><div class="text-muted small">Print Revenue</div><div class="stat-number" id="todayPrintRev"><?= number_format($todayRevenue ?? 0, 2) ?></div></div></div>
                                <div class="col-6"><div class="stat-box"><div class="text-muted small">Binding Revenue</div><div class="stat-number" id="todayBindingRev"><?= number_format($todayBindingRev ?? 0, 2) ?></div></div></div>
                            </div>
                            <!-- New Print Count Cards -->
                            <div class="row g-1 print-stat-cards mt-1">
                                <div class="col-4"><div class="stat-box text-center"><div class="text-muted small">Single Pages</div><div class="stat-number" id="todaySinglePages" style="font-size:16px;"><?= $singlePages ?></div></div></div>
                                <div class="col-4"><div class="stat-box text-center"><div class="text-muted small">Double Pages</div><div class="stat-number" id="todayDoublePages" style="font-size:16px;"><?= $doublePages ?></div></div></div>
                                <div class="col-4"><div class="stat-box text-center" style="background: rgba(255,193,7,0.2);"><div class="text-muted small">Total Print Pages</div><div class="stat-number" id="todayTotalPages" style="font-size:16px; color:#ffc107;"><?= $totalPrintPages ?></div></div></div>
                            </div>
                            
                            <!-- REPLACED SECTION - Now has 4 columns -->
                            <div class="row g-1 mt-1">
                                <div class="col-3"><div class="stat-box"><div class="text-muted small">Printed Papers</div><div class="stat-number" id="todayPrintJobs" style="font-size:18px; color: #b2f300;"><?= $todayJobs ?? 0 ?></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="text-muted small">Binding Jobs</div><div class="stat-number" id="todayBindingJobs"  style="font-size:18px; color: #b2f300;"><?= $todayBindingJobs ?? 0 ?></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="text-muted small">Error Count</div><div class="stat-number" id="todayErrorCount" style="font-size:18px; color: #ff6b6b;">0</div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="text-muted small">Rough Sheets</div><div class="stat-number" id="todayRoughSheets" style="font-size:18px; color: #ffa94d;">0</div></div></div>
                            </div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><i class="bi bi-cart me-1"></i>Job Cart</h6>
                                <div>
                                    <button class="btn btn-sm btn-danger me-1" onclick="clearCart()"><i class="bi bi-trash"></i> Clear</button>
                                    <span class="badge bg-primary" id="cartCount">0 items</span>
                                </div>
                            </div>
                            
                            <div class="cart-container">
                                <table class="table table-bordered cart-table" id="cartTable">
                                    <thead>
                                        <tr><th>Item</th><th style="width:50px;">Qty</th><th style="width:60px;">Rate</th><th style="width:50px;">Disc%</th><th style="width:70px;">Total</th><th style="width:30px;">Del</th></tr>
                                    </thead>
                                    <tbody id="cartBody">
                                        <tr><td colspan="6" class="text-center text-muted small">Cart is empty</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="summary-section">
                                <div class="summary-row"><span class="label">Subtotal</span><span class="value" id="displaySubtotal">Rs. 0.00</span></div>
                                <div class="summary-row"><span class="label">Discount <span id="discountPercentLabel" class="discount-badge">0%</span></span><span class="value discount" id="displayDiscount">Rs. 0.00</span></div>
                                <div class="summary-row total"><span class="label">Net Amount</span><span class="value total-amount" id="displayNet">Rs. 0.00</span></div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-6">
                                    <div class="form-check" style="text-align:right;">
                                        <input class="form-check-input"  style="font-size: 18px; background-color: #042d5c; color: #2e2d2d; outline: max(2px, 0.15em) solid currentColor;" type="checkbox" id="freeCheck" onchange="toggleFree()">
                                        <label class="form-check-label small" style="font-size: 16px; color: #dc3545; font-weight : 500; " for="freeCheck">Free Job</label>
                                    </div>
                                </div>
                                <div class="col-6 text-end">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="saveJob()" id="saveJobBtn" disabled>
                                        <i class="bi bi-save me-1"></i>Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Summary Tabs -->
<ul class="nav nav-tabs mb-2 mt-3" id="summaryTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#todayTab">Today's Report</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#weekTab">This Week</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#monthTab">This Month</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#yearTab">This Year</a></li>
</ul>

<div class="tab-content">
    <!-- TODAY TAB -->
    <div class="tab-pane fade show active" id="todayTab">
        <div id="todayJobsTableContainer" class="mt-3"></div>
        
        <div class="d-flex justify-content-between align-items-center mt-2" id="paginationControls">
            <div>
                <span class="text-muted small" id="paginationInfo">Showing 1-10 of 0 bills</span>
            </div>
            <div>
                <button class="btn btn-sm btn-outline-secondary me-1" onclick="changePage('today', -1)" id="prevPageBtn" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <span class="fw-bold" id="pageInfo">Page 1</span>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="changePage('today', 1)" id="nextPageBtn">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div>
                <select class="form-select form-select-sm" id="pageSizeSelect" onchange="changePageSize()" style="width:80px;display:inline-block;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- WEEK TAB -->
    <div class="tab-pane fade" id="weekTab">
        <div class="period-nav mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="changeWeek(-1)"><i class="bi bi-chevron-left"></i> Prev</button>
            <span class="fw-bold" id="weekLabel">This Week</span>
            <button class="btn btn-outline-secondary btn-sm" onclick="changeWeek(1)">Next <i class="bi bi-chevron-right"></i></button>
            <button class="btn btn-outline-primary btn-sm" onclick="resetWeek()">Today</button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Revenue</div><div class="stat-number" style="color:#007bff;" id="weekPrintRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Revenue</div><div class="stat-number" style="color:#13796b;" id="weekBindingRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Revenue</div><div class="stat-number" style="color:#042d5c;" id="weekTotalRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Jobs</div><div class="stat-number" style="color:#fd7e14;" id="weekPrintJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Jobs</div><div class="stat-number" style="color:#13796b;" id="weekBindingJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Jobs</div><div class="stat-number" style="color:#042d5c;" id="weekTotalJobs">0</div></div></div>
        </div>
        <div id="weekJobsTableContainer"></div>
    </div>

    <!-- MONTH TAB -->
    <div class="tab-pane fade" id="monthTab">
        <div class="period-nav mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="changeMonth(-1)"><i class="bi bi-chevron-left"></i> Prev</button>
            <span class="fw-bold" id="monthLabel">This Month</span>
            <button class="btn btn-outline-secondary btn-sm" onclick="changeMonth(1)">Next <i class="bi bi-chevron-right"></i></button>
            <button class="btn btn-outline-primary btn-sm" onclick="resetMonth()">Today</button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Revenue</div><div class="stat-number" style="color:#007bff;" id="monthPrintRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Revenue</div><div class="stat-number" style="color:#13796b;" id="monthBindingRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Revenue</div><div class="stat-number" style="color:#042d5c;" id="monthTotalRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Jobs</div><div class="stat-number" style="color:#fd7e14;" id="monthPrintJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Jobs</div><div class="stat-number" style="color:#13796b;" id="monthBindingJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Jobs</div><div class="stat-number" style="color:#042d5c;" id="monthTotalJobs">0</div></div></div>
        </div>
        <div id="monthJobsTableContainer"></div>
    </div>

    <!-- YEAR TAB -->
    <div class="tab-pane fade" id="yearTab">
        <div class="period-nav mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="changeYear(-1)"><i class="bi bi-chevron-left"></i> Prev</button>
            <span class="fw-bold" id="yearLabel">This Year</span>
            <button class="btn btn-outline-secondary btn-sm" onclick="changeYear(1)">Next <i class="bi bi-chevron-right"></i></button>
            <button class="btn btn-outline-primary btn-sm" onclick="resetYear()">Today</button>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Revenue</div><div class="stat-number" style="color:#007bff;" id="yearPrintRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Revenue</div><div class="stat-number" style="color:#13796b;" id="yearBindingRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Revenue</div><div class="stat-number" style="color:#042d5c;" id="yearTotalRev">0.00</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Print Jobs</div><div class="stat-number" style="color:#fd7e14;" id="yearPrintJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Binding Jobs</div><div class="stat-number" style="color:#13796b;" id="yearBindingJobs">0</div></div></div>
            <div class="col-md-2 col-6"><div class="stat-box"><div class="text-muted small">Total Jobs</div><div class="stat-number" style="color:#042d5c;" id="yearTotalJobs">0</div></div></div>
        </div>
        <div id="yearJobsTableContainer"></div>
    </div>
</div>

<div class="mt-2 text-muted small"><i class="bi bi-database me-1"></i> Machine count for today: <?= $machineCount ?? 'Not set' ?></div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ========== API Base Path ==========
var apiBase = '<?= $apiBase ?>';

// ========== Global State ==========
let cart = [];
let selectedCategory = 'All';
let weekOffset = 0;
let monthOffset = 0;
let yearOffset = 0;
let selectedCustomerData = null;
let currentPage = {};
let pageSize = 10;
let totalBills = 0;
let allBills = {};
let currentPeriod = 'today';

// ========== Save Customer Selection ==========
function saveCustomerSelection() {
    selectedCustomerData = {
        userType: document.getElementById('userType')?.value || '',
        customer: document.getElementById('customerName')?.value || '',
        batch: document.getElementById('batchInfo')?.value || ''
    };
}

function restoreCustomerSelection() {
    if (!selectedCustomerData) return;
    const type = document.getElementById('userType');
    const customer = document.getElementById('customerName');
    const batch = document.getElementById('batchInfo');
    if (type) type.value = selectedCustomerData.userType;
    if (customer) customer.value = selectedCustomerData.customer;
    if (batch) batch.value = selectedCustomerData.batch;
}

// ========== Pagination Functions ==========
function changePage(period, direction) {
    if (!currentPage[period]) currentPage[period] = 1;
    currentPage[period] += direction;
    if (currentPage[period] < 1) currentPage[period] = 1;
    renderBillsPage(period);
}

function changePageSize() {
    pageSize = parseInt(document.getElementById('pageSizeSelect').value);
    if (!currentPage[currentPeriod]) currentPage[currentPeriod] = 1;
    renderBillsPage(currentPeriod);
}

function renderBillsPage(period) {
    if (!allBills[period]) return;
    const bills = allBills[period];
    totalBills = bills.length;
    const totalPages = Math.ceil(totalBills / pageSize);
    
    if (!currentPage[period]) currentPage[period] = 1;
    if (currentPage[period] > totalPages) currentPage[period] = totalPages;
    if (currentPage[period] < 1) currentPage[period] = 1;
    
    const start = (currentPage[period] - 1) * pageSize;
    const end = Math.min(start + pageSize, totalBills);
    const pageBills = bills.slice(start, end);
    
    buildDetailedReportTable(period, pageBills);
    
    document.getElementById('paginationInfo').textContent = `Showing ${start + 1}-${end} of ${totalBills} bills`;
    document.getElementById('pageInfo').textContent = `Page ${currentPage[period]} of ${totalPages || 1}`;
    document.getElementById('prevPageBtn').disabled = currentPage[period] <= 1;
    document.getElementById('nextPageBtn').disabled = currentPage[period] >= totalPages;
}

// ========== User Type Toggle ==========
function toggleUserType() {
    const userType = document.getElementById('userType').value;
    const nameInput = document.getElementById('customerName');
    const batchInput = document.getElementById('batchInfo');
    const autocompleteList = document.getElementById('autocompleteList');

    nameInput.value = '';
    batchInput.value = '';
    autocompleteList.innerHTML = '';
    autocompleteList.style.display = 'none';

    if (userType === 'student') {
        nameInput.placeholder = 'Search Student by Name, ID or NIC...';
        batchInput.placeholder = 'Student ID / Registration';
        batchInput.readOnly = true;
    } else if (userType === 'staff') {
        nameInput.placeholder = 'Search Staff by Name or NIC...';
        batchInput.placeholder = 'NIC';
        batchInput.readOnly = true;
    } else {
        nameInput.placeholder = 'Enter Customer Name...';
        batchInput.placeholder = 'Batch / Info';
        batchInput.readOnly = false;
    }
    saveCustomerSelection();
}

// ========== Autocomplete Search ==========
function setupAutocomplete() {
    const nameInput = document.getElementById('customerName');
    const batchInput = document.getElementById('batchInfo');
    const autocompleteList = document.getElementById('autocompleteList');
    const userType = document.getElementById('userType');
    
    if (!nameInput || !autocompleteList) return;
    
    let debounceTimer;
    
    nameInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const val = this.value.trim();
        autocompleteList.innerHTML = '';
        
        if (!val || userType.value === 'other') {
            autocompleteList.style.display = 'none';
            return;
        }
        
        debounceTimer = setTimeout(() => {
            const isStudent = userType.value === 'student';
            const endpoint = isStudent ? apiBase + 'search_students.php' : apiBase + 'search_staff.php';
            
            fetch(`${endpoint}?q=${encodeURIComponent(val)}`)
                .then(response => response.json())
                .then(data => {
                    autocompleteList.innerHTML = '';
                    
                    if (data.error || !Array.isArray(data) || data.length === 0) {
                        const div = document.createElement('div');
                        div.textContent = 'No results found';
                        div.style.color = '#6c757d';
                        div.style.fontStyle = 'italic';
                        div.style.cursor = 'default';
                        autocompleteList.appendChild(div);
                        autocompleteList.style.display = 'block';
                        return;
                    }
                    
                    data.forEach(item => {
                        const div = document.createElement('div');
                        
                        if (isStudent) {
                            const regId = item.student_registration_id || item.student_code || 'N/A';
                            const fullName = (item.first_name || '') + ' ' + (item.last_name || '');
                            const nic = item.nic || 'N/A';
                            
                            div.innerHTML = `
                                <div class="item-code">${regId}</div>
                                <div class="item-detail">${fullName} | ${nic}</div>
                            `;
                            
                            div.addEventListener('click', function() {
                                nameInput.value = fullName;
                                batchInput.value = regId;
                                saveCustomerSelection();
                                autocompleteList.style.display = 'none';
                                autocompleteList.innerHTML = '';
                            });
                        } else {
                            const fullName = item.full_name || '';
                            const nic = item.nic || 'N/A';
                            
                            div.innerHTML = `
                                <div class="item-code">${fullName}</div>
                                <div class="item-detail">${nic}</div>
                            `;
                            
                            div.addEventListener('click', function() {
                                nameInput.value = fullName;
                                batchInput.value = nic;
                                saveCustomerSelection();
                                autocompleteList.style.display = 'none';
                                autocompleteList.innerHTML = '';
                            });
                        }
                        autocompleteList.appendChild(div);
                    });
                    autocompleteList.style.display = 'block';
                })
                .catch(err => {
                    console.error('Search error:', err);
                    autocompleteList.style.display = 'none';
                });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== nameInput && !autocompleteList.contains(e.target)) {
            autocompleteList.style.display = 'none';
        }
    });
    
    nameInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            autocompleteList.style.display = 'none';
        }
    });
}

// ========== Show Error ==========
function showError(message) {
    document.getElementById('errorMessage').textContent = message;
    new bootstrap.Modal(document.getElementById('errorModal')).show();
}

// ========== Category Filtering ==========
$(document).ready(function() {
    $('.category-tab').on('click', function() {
        $('.category-tab').removeClass('active');
        $(this).addClass('active');
        const catId = $(this).data('category-id');
        $('.item-btn').each(function() {
            const itemCatId = $(this).data('category-id');
            if (catId == 0 || itemCatId == catId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    setTimeout(function() {
        loadAllSummaries();
    }, 100);
    
    toggleFree();
    toggleUserType();
    setupAutocomplete();
});

// ========== Cart Functions ==========
function addItem(itemId) {
    saveCustomerSelection();
    const btn = document.querySelector(`.item-btn[data-item-id="${itemId}"]`);
    if (!btn) return;
    const existing = cart.find(i => i.id == itemId);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({
            id: itemId,
            name: btn.dataset.itemName,
            size: btn.dataset.itemSize || '',
            price: parseFloat(btn.dataset.itemPrice) || 0,
            discount: parseFloat(btn.dataset.discount) || 0,
            validDiscount: btn.dataset.validDiscount || '',
            categoryId: btn.dataset.categoryId,
            categoryName: btn.dataset.categoryName,
            subCategory: btn.dataset.subCategory || '',
            quantity: 1
        });
    }
    updateCartDisplay();
    restoreCustomerSelection();
}

function removeItem(itemId) {
    saveCustomerSelection();
    const index = cart.findIndex(item => item.id == itemId);
    if (index !== -1) cart.splice(index, 1);
    updateCartDisplay();
    restoreCustomerSelection();
}

function updateQtyFromInput(itemId, value) {
    saveCustomerSelection();
    const qty = parseInt(value) || 0;
    const item = cart.find(i => i.id == itemId);
    if (item) {
        if (qty === 0) {
            removeItem(itemId);
            return;
        }
        item.quantity = qty;
    }
    updateCartDisplay();
    restoreCustomerSelection();
}

function clearCart() {
    saveCustomerSelection();
    cart = [];
    document.querySelectorAll('.item-btn .qty-badge').forEach(badge => badge.textContent = '0');
    document.querySelectorAll('.item-btn').forEach(btn => btn.classList.remove('has-qty'));
    document.getElementById('freeCheck').checked = false;
    document.getElementById('hiddenIsFree').value = '0';
    updateCartDisplay();
    toggleFree();
    restoreCustomerSelection();
}

function updateCartDisplay() {
    saveCustomerSelection();
    const subtotalEl = document.getElementById('displaySubtotal');
    const discountEl = document.getElementById('displayDiscount');
    const netEl = document.getElementById('displayNet');
    const countEl = document.getElementById('cartCount');
    const saveBtn = document.getElementById('saveJobBtn');
    const tbody = document.getElementById('cartBody');

    let subtotal = 0;
    let totalDiscount = 0;
    let isBinding = false;
    const isFree = document.getElementById('freeCheck').checked;

    document.querySelectorAll('.item-btn .qty-badge').forEach(badge => {
        const id = badge.id.replace('badge-', '');
        const item = cart.find(i => i.id == id);
        const qty = item ? item.quantity : 0;
        badge.textContent = qty;
        badge.closest('.item-btn').classList.toggle('has-qty', qty > 0);
    });

    if (cart.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted small">Cart is empty</td></tr>';
        saveBtn.disabled = true;
        subtotalEl.textContent = 'Rs. 0.00';
        discountEl.textContent = 'Rs. 0.00';
        netEl.textContent = 'Rs. 0.00';
        document.getElementById('discountPercentLabel').textContent = '0%';
        countEl.textContent = '0 items';
        updateHiddenFields();
        restoreCustomerSelection();
        return;
    }

    let html = '';
    for (const item of cart) {
        const baseAmount = item.price * item.quantity;
        let discountPercent = item.discount || 0;
        
        // If free, force 100% discount
        if (isFree) {
            discountPercent = 100;
        } else {
            // Normal discount calculation
            if (item.validDiscount) {
                const rules = item.validDiscount.split(',');
                for (const rule of rules) {
                    const parts = rule.trim().split('=');
                    if (parts.length === 2) {
                        const threshold = parseInt(parts[0]);
                        const discPercent = parseFloat(parts[1]);
                        if (item.quantity >= threshold) {
                            discountPercent = discPercent;
                        }
                    }
                }
            }
            // Check sub-category group discount
            if (item.subCategory) {
                const subCatItems = cart.filter(i => i.subCategory === item.subCategory);
                const totalSubQty = subCatItems.reduce((sum, i) => sum + i.quantity, 0);
                if (item.validDiscount) {
                    const rules = item.validDiscount.split(',');
                    for (const rule of rules) {
                        const parts = rule.trim().split('=');
                        if (parts.length === 2) {
                            const threshold = parseInt(parts[0]);
                            const discPercent = parseFloat(parts[1]);
                            if (totalSubQty >= threshold) {
                                discountPercent = Math.max(discountPercent, discPercent);
                            }
                        }
                    }
                }
            }
        }
        
        const discountAmount = baseAmount * (discountPercent / 100);
        const netAmount = baseAmount - discountAmount;
        subtotal += baseAmount;
        totalDiscount += discountAmount;
        const catName = item.categoryName || '';
        if (catName.toLowerCase() === 'binding') isBinding = true;
        html += `
            <tr>
                <td class="item-name-cell">${item.name} ${item.size ? '('+item.size+')' : ''}</td>
                <td class="qty-cell">
                    <input type="number" value="${item.quantity}" min="0" 
                           data-id="${item.id}" onchange="updateQtyFromInput(${item.id}, this.value)">
                </td>
                <td>${item.price.toFixed(2)}</td>
                <td>${discountPercent > 0 ? discountPercent+'%' : '-'}</td>
                <td>${netAmount.toFixed(2)}</td>
                <td><button class="remove-btn" onclick="removeItem(${item.id})"><i class="bi bi-x-circle"></i></button></td>
            </tr>
        `;
    }
    tbody.innerHTML = html;

    const netTotal = subtotal - totalDiscount;
    subtotalEl.textContent = `Rs. ${subtotal.toFixed(2)}`;
    discountEl.textContent = `Rs. ${totalDiscount.toFixed(2)}`;
    netEl.textContent = `Rs. ${netTotal.toFixed(2)}`;
    const percent = subtotal > 0 ? (totalDiscount / subtotal * 100) : 0;
    document.getElementById('discountPercentLabel').textContent = Math.round(percent) + '%';
    countEl.textContent = cart.reduce((sum, item) => sum + item.quantity, 0) + ' items';
    saveBtn.disabled = false;
    document.getElementById('hiddenJobType').value = isBinding ? 'binding' : 'printing';
    updateHiddenFields();
    restoreCustomerSelection();
}

function toggleFree() {
    const isChecked = document.getElementById('freeCheck').checked;
    document.getElementById('hiddenIsFree').value = isChecked ? '1' : '0';
    
    if (isChecked) {
        // Calculate total subtotal and set discount to equal subtotal (100% discount)
        let subtotal = 0;
        for (const item of cart) {
            subtotal += item.price * item.quantity;
        }
        document.getElementById('displayNet').textContent = 'Rs. 0.00';
        document.getElementById('displayDiscount').textContent = `Rs. ${subtotal.toFixed(2)}`;
        document.getElementById('discountPercentLabel').textContent = '100%';
        document.getElementById('hiddenNet').value = '0.00';
        document.getElementById('hiddenDiscount').value = subtotal.toFixed(2);
        
        // Also update cart display to show 100% discount on each item
        updateCartDisplay();
    } else {
        updateCartDisplay();
    }
}

function updateHiddenFields() {
    let subtotal = 0;
    let totalDiscount = 0;
    let oneSide = 0, bothSide = 0, roughOne = 0, roughTwo = 0, error = 0;
    let bindingQty = 0, bindingName = '', bindingSize = '';
    let isBinding = false;
    const isFree = document.getElementById('freeCheck').checked;
    
    for (const item of cart) {
        const baseAmount = item.price * item.quantity;
        let discountPercent = item.discount || 0;
        
        // If free, force 100% discount
        if (isFree) {
            discountPercent = 100;
        } else {
            if (item.validDiscount) {
                const rules = item.validDiscount.split(',');
                for (const rule of rules) {
                    const parts = rule.trim().split('=');
                    if (parts.length === 2) {
                        const threshold = parseInt(parts[0]);
                        const discPercent = parseFloat(parts[1]);
                        if (item.quantity >= threshold) {
                            discountPercent = discPercent;
                        }
                    }
                }
            }
            if (item.subCategory) {
                const subCatItems = cart.filter(i => i.subCategory === item.subCategory);
                const totalSubQty = subCatItems.reduce((sum, i) => sum + i.quantity, 0);
                if (item.validDiscount) {
                    const rules = item.validDiscount.split(',');
                    for (const rule of rules) {
                        const parts = rule.trim().split('=');
                        if (parts.length === 2) {
                            const threshold = parseInt(parts[0]);
                            const discPercent = parseFloat(parts[1]);
                            if (totalSubQty >= threshold) {
                                discountPercent = Math.max(discountPercent, discPercent);
                            }
                        }
                    }
                }
            }
        }
        const discountAmount = baseAmount * (discountPercent / 100);
        subtotal += baseAmount;
        totalDiscount += discountAmount;
        const name = item.name.toLowerCase();
        const catName = (item.categoryName || '').toLowerCase();
        if (catName === 'binding') {
            bindingQty += item.quantity;
            bindingName = item.name;
            bindingSize = item.size;
            isBinding = true;
        } else {
            if (name.includes('one side') || name.includes('single')) oneSide += item.quantity;
            else if (name.includes('both side') || name.includes('double')) bothSide += item.quantity;
            else if (name.includes('rough one')) roughOne += item.quantity;
            else if (name.includes('rough both')) roughTwo += item.quantity;
            else if (name.includes('error')) error += item.quantity;
            else {
                bindingQty += item.quantity;
                bindingName = item.name;
                bindingSize = item.size;
                isBinding = true;
            }
        }
    }
    
    document.getElementById('hiddenOneSide').value = oneSide;
    document.getElementById('hiddenBothSide').value = bothSide;
    document.getElementById('hiddenRoughOne').value = roughOne;
    document.getElementById('hiddenRoughTwo').value = roughTwo;
    document.getElementById('hiddenError').value = error;
    document.getElementById('hiddenBindingQty').value = bindingQty;
    document.getElementById('hiddenBindingName').value = bindingName;
    document.getElementById('hiddenBindingSize').value = bindingSize;
    document.getElementById('hiddenTotal').value = subtotal.toFixed(2);
    document.getElementById('hiddenDiscount').value = totalDiscount.toFixed(2);
    document.getElementById('hiddenNet').value = (subtotal - totalDiscount).toFixed(2);
    document.getElementById('hiddenJobType').value = isBinding ? 'binding' : 'printing';
}

function saveJob() {
    const nameInput = document.getElementById('customerName');
    let name = nameInput.value.trim();
    if (!name) { showError('Customer name is required.'); return; }
    if (cart.length === 0) { showError('Please select at least one item.'); return; }
    
    const formData = new FormData(document.getElementById('jobForm'));
    const data = Object.fromEntries(formData.entries());
    data.name = name;
    data.one_side = document.getElementById('hiddenOneSide').value;
    data.both_side = document.getElementById('hiddenBothSide').value;
    data.rough_one = document.getElementById('hiddenRoughOne').value;
    data.rough_two = document.getElementById('hiddenRoughTwo').value;
    data.error_count = document.getElementById('hiddenError').value;
    data.quantity = document.getElementById('hiddenBindingQty').value;
    data.binding_name = document.getElementById('hiddenBindingName').value;
    data.binding_size = document.getElementById('hiddenBindingSize').value;
    data.total_amount = document.getElementById('hiddenTotal').value;
    data.discount_amount = document.getElementById('hiddenDiscount').value;
    data.net_amount = document.getElementById('hiddenNet').value;
    data.is_free = document.getElementById('hiddenIsFree').value;
    data.user = document.getElementById('hiddenUser').value;
    data.paper_size = 'A4';
    data.job_type = document.getElementById('hiddenJobType').value;
    
    // Build items with ALL calculated values from the cart
    const itemsData = cart.map(item => {
        const baseAmount = item.price * item.quantity;
        let discountPercent = item.discount || 0;
        if (item.validDiscount) {
            const rules = item.validDiscount.split(',');
            for (const rule of rules) {
                const parts = rule.trim().split('=');
                if (parts.length === 2) {
                    const threshold = parseInt(parts[0]);
                    const discPercent = parseFloat(parts[1]);
                    if (item.quantity >= threshold) {
                        discountPercent = discPercent;
                    }
                }
            }
        }
        // Check sub-category group discount
        if (item.subCategory) {
            const subCatItems = cart.filter(i => i.subCategory === item.subCategory);
            const totalSubQty = subCatItems.reduce((sum, i) => sum + i.quantity, 0);
            if (item.validDiscount) {
                const rules = item.validDiscount.split(',');
                for (const rule of rules) {
                    const parts = rule.trim().split('=');
                    if (parts.length === 2) {
                        const threshold = parseInt(parts[0]);
                        const discPercent = parseFloat(parts[1]);
                        if (totalSubQty >= threshold) {
                            discountPercent = Math.max(discountPercent, discPercent);
                        }
                    }
                }
            }
        }
        
        const discountAmount = baseAmount * (discountPercent / 100);
        const netAmount = baseAmount - discountAmount;
        
        return {
            name: item.name,
            qty: item.quantity,
            category: item.categoryName ? item.categoryName.toLowerCase() : 'printing',
            price: item.price,
            size: item.size || '',
            subtotal: parseFloat(baseAmount.toFixed(2)),
            discount_percent: parseFloat(discountPercent.toFixed(2)),
            discount_amount: parseFloat(discountAmount.toFixed(2)),
            net_amount: parseFloat(netAmount.toFixed(2))
        };
    });
    
    data.items_json = JSON.stringify(itemsData);
    
    // Build bill popup
    let billHtml = buildBill(data);
    document.getElementById('billContent').innerHTML = billHtml;
    var billModal = new bootstrap.Modal(document.getElementById('billModal'));
    billModal.show();
    
    document.getElementById('confirmSave').onclick = function() {
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        const payload = new FormData();
        for (const key in data) {
            payload.append(key, data[key]);
        }
        payload.append('action', 'save_job');
        
        $.ajax({
            url: apiBase + 'lib_api.php',
            type: 'POST',
            data: payload,
            processData: false,
            contentType: false,
            dataType: 'json',
            timeout: 60000,
            success: function(response) {
                billModal.hide();
                btn.disabled = false;
                btn.textContent = 'Confirm & Save';
                if (response.success) {
                    document.getElementById('successMessage').textContent = 'Job saved successfully! Bill #' + response.bill_id;
                    new bootstrap.Modal(document.getElementById('successModal')).show();
                    clearCart();
                    document.getElementById('jobForm').reset();
                    document.getElementById('freeCheck').checked = false;
                    document.getElementById('hiddenIsFree').value = '0';
                    toggleUserType();
                    loadAllSummaries();
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showError(response.message || 'Error saving job.');
                }
            },
            error: function(xhr, status, error) {
                billModal.hide();
                btn.disabled = false;
                btn.textContent = 'Confirm & Save';
                console.error('AJAX Error:', xhr.responseText);
                showError('Error saving job: ' + error + ' - Check console for details.');
            }
        });
    };
}

function buildBill(data) {
    let html = `<div class="container">
        <div class="row mb-2">
            <div class="col-6"><strong>Date:</strong> ${data.job_date}</div>
            <div class="col-6"><strong>User Type:</strong> ${data.user_type}</div>
        </div>
        <div class="row mb-2">
            <div class="col-6"><strong>Name:</strong> ${data.name}</div>
            <div class="col-6"><strong>Batch:</strong> ${data.batch || 'N/A'}</div>
        </div>
        <div class="row mb-2">
            <div class="col-6"><strong>User:</strong> ${data.user || 'N/A'}</div>
            <div class="col-6"><strong>Type:</strong> ${data.job_type || 'printing'}</div>
        </div>
        <hr>
        <table class="table table-bordered bill-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Disc%</th>
                    <th>Discount</th>
                    <th>Subtotal</th>
                    <th>Net Total</th>
                </tr>
            </thead>
            <tbody>`;
    
    let hasItems = false;
    let totalSubtotal = 0;
    let totalDiscount = 0;
    const isFree = data.is_free == 1;
    
    for (const item of cart) {
        const baseAmount = item.price * item.quantity;
        let discountPercent = item.discount || 0;
        
        // If free, force 100% discount
        if (isFree) {
            discountPercent = 100;
        } else {
            if (item.validDiscount) {
                const rules = item.validDiscount.split(',');
                for (const rule of rules) {
                    const parts = rule.trim().split('=');
                    if (parts.length === 2) {
                        const threshold = parseInt(parts[0]);
                        const discPercent = parseFloat(parts[1]);
                        if (item.quantity >= threshold) {
                            discountPercent = discPercent;
                        }
                    }
                }
            }
            if (item.subCategory) {
                const subCatItems = cart.filter(i => i.subCategory === item.subCategory);
                const totalSubQty = subCatItems.reduce((sum, i) => sum + i.quantity, 0);
                if (item.validDiscount) {
                    const rules = item.validDiscount.split(',');
                    for (const rule of rules) {
                        const parts = rule.trim().split('=');
                        if (parts.length === 2) {
                            const threshold = parseInt(parts[0]);
                            const discPercent = parseFloat(parts[1]);
                            if (totalSubQty >= threshold) {
                                discountPercent = Math.max(discountPercent, discPercent);
                            }
                        }
                    }
                }
            }
        }
        const discountAmount = baseAmount * (discountPercent / 100);
        const netAmount = baseAmount - discountAmount;
        
        totalSubtotal += baseAmount;
        totalDiscount += discountAmount;
        hasItems = true;
        
        html += `<tr>
            <td>${item.name} ${item.size ? '('+item.size+')' : ''}</td>
            <td>${item.quantity}</td>
            <td>${item.price.toFixed(2)}</td>
            <td>${discountPercent}%</td>
            <td>${discountAmount.toFixed(2)}</td>
            <td>${baseAmount.toFixed(2)}</td>
            <td>${netAmount.toFixed(2)}</td>
        </tr>`;
    }
    
    if (!hasItems) {
        html += `<tr><td colspan="7" class="text-center">No items</td></tr>`;
    }
    
    const netTotal = isFree ? 0 : (totalSubtotal - totalDiscount);
    const percent = totalSubtotal > 0 ? (totalDiscount / totalSubtotal * 100).toFixed(1) : 0;
    
    html += `</tbody></table>
        <div class="row"><div class="col-6"><strong>Subtotal</strong></div><div class="col-6 text-end">${totalSubtotal.toFixed(2)}</div></div>
        <div class="row"><div class="col-6"><strong>Discount (${percent}%)</strong></div><div class="col-6 text-end text-success">-${totalDiscount.toFixed(2)}</div></div>
        <div class="row"><div class="col-6"><strong>Net Amount</strong></div><div class="col-6 text-end text-danger"  style="font-size: 35px;"><strong>${netTotal.toFixed(2)}</strong></div></div>
        <div class="row"><div class="col-6"><strong>Free</strong></div><div class="col-6 text-end">${isFree ? 'Yes' : 'No'}</div></div>
    </div>`;
    return html;
}

// ========== Load Report Data ==========
function loadReportData(period, from, to) {
    let url = apiBase + 'lib_api.php?action=report_data';
    if (from && to) {
        url += '&from=' + from + '&to=' + to;
    } else {
        const d = new Date().toISOString().split('T')[0];
        if (period === 'today') {
            url += '&from=' + d + '&to=' + d;
        } else if (period === 'week') {
            const now = new Date();
            const day = now.getDay();
            const diff = (day === 0 ? 6 : day - 1);
            const monday = new Date(now);
            monday.setDate(now.getDate() - diff);
            const sunday = new Date(monday);
            sunday.setDate(monday.getDate() + 6);
            url += '&from=' + monday.toISOString().split('T')[0] + '&to=' + sunday.toISOString().split('T')[0];
        } else if (period === 'month') {
            const now = new Date();
            const y = now.getFullYear();
            const m = now.getMonth() + 1;
            url += '&from=' + y + '-' + String(m).padStart(2, '0') + '-01';
            url += '&to=' + y + '-' + String(m).padStart(2, '0') + '-' + new Date(y, m, 0).getDate();
        } else if (period === 'year') {
            const y = new Date().getFullYear();
            url += '&from=' + y + '-01-01&to=' + y + '-12-31';
        }
    }
    
    $.ajax({
        url: url,
        dataType: 'json',
        success: function(response) {
            if (!response.success || !response.jobs) {
                updateEmptyState(period);
                return;
            }
            const jobs = response.jobs || [];
            const summary = response.summary || {};
            const todaySales = response.today_sales || {};
            
            allBills[period] = jobs;
            currentPeriod = period;
            if (!currentPage[period]) currentPage[period] = 1;
            
            updateSummaryCards(period, summary, todaySales);
            renderBillsPage(period);
        },
        error: function(xhr, status, error) {
            console.error('Error loading report for ' + period + ':', error);
            updateEmptyState(period);
        }
    });
}

// Update the updateSummaryCards function
// Update the updateSummaryCards function
function updateSummaryCards(period, summary, todaySales) {
    // Update Today's Sales (top section) – always uses todaySales
    if (period === 'today' && todaySales) {
        $('#todayTotalSale').text((todaySales.today_sales || 0).toFixed(2));
        $('#todayTotalJobs').text(todaySales.total_jobs || 0);
        $('#todayPrintRev').text((todaySales.print_revenue || 0).toFixed(2));
        $('#todayBindingRev').text((todaySales.binding_revenue || 0).toFixed(2));
        
        // FIX: Use the correct print_count from the API
        $('#todayPrintJobs').text(todaySales.print_count || 0);
        $('#todayBindingJobs').text(todaySales.binding_count || 0);
        
        // Update print counts from response
      if (todaySales.print_counts) {
    // Debug: Log the data to see what's coming from the API
    console.log('Print Counts Data:', todaySales.print_counts);
    console.log('Error Pages from API:', todaySales.print_counts.error_pages);
    
    // Get individual values
    var singlePages = parseInt(todaySales.print_counts.single_pages) || 0;
    var doublePages = parseInt(todaySales.print_counts.double_pages) || 0;
    var errorPages = parseInt(todaySales.print_counts.error_pages) || 0;
    
    // If error_pages is not available, try to get it from error_count as fallback
    if (errorPages === 0 && todaySales.error_count) {
        errorPages = parseInt(todaySales.error_count) || 0;
        console.log('Using error_count as fallback:', errorPages);
    }
    
    // Display Single Pages
    $('#todaySinglePages').text(singlePages);
    
    // Display Double Pages
    $('#todayDoublePages').text(doublePages);
    
    // Calculate Total Print Pages = Single + (Double × 2) + Error
    var totalPrintPages = singlePages + (doublePages * 2) + errorPages;
    
    // Debug: Log the calculation
    console.log('Calculation:', singlePages + ' + (' + doublePages + ' × 2) + ' + errorPages + ' = ' + totalPrintPages);
    
    // Display Total Print Pages
    $('#todayTotalPages').text(totalPrintPages);
}
        
        // Update error count and rough sheets
        if (todaySales.error_count !== undefined) {
            $('#todayErrorCount').text(todaySales.error_count || 0);
        }
        if (todaySales.rough_sheets !== undefined) {
            $('#todayRoughSheets').text(todaySales.rough_sheets || 0);
        }
    }

    // Update Week, Month, Year summary cards (if summary is provided)
    if (summary) {
        var rev = summary.total_revenue || 0;
        var disc = summary.total_discount || 0;
        var jobs = summary.total_jobs || 0;
        var printRev = summary.print_revenue || 0;
        var bindRev = summary.binding_revenue || 0;
        var printCnt = summary.print_count || 0;
        var bindCnt = summary.binding_count || 0;

        if (period === 'week') {
            $('#weekPrintRev').text(printRev.toFixed(2));
            $('#weekBindingRev').text(bindRev.toFixed(2));
            $('#weekTotalRev').text(rev.toFixed(2));
            $('#weekPrintJobs').text(printCnt);
            $('#weekBindingJobs').text(bindCnt);
            $('#weekTotalJobs').text(jobs);
        }
        if (period === 'month') {
            $('#monthPrintRev').text(printRev.toFixed(2));
            $('#monthBindingRev').text(bindRev.toFixed(2));
            $('#monthTotalRev').text(rev.toFixed(2));
            $('#monthPrintJobs').text(printCnt);
            $('#monthBindingJobs').text(bindCnt);
            $('#monthTotalJobs').text(jobs);
        }
        if (period === 'year') {
            $('#yearPrintRev').text(printRev.toFixed(2));
            $('#yearBindingRev').text(bindRev.toFixed(2));
            $('#yearTotalRev').text(rev.toFixed(2));
            $('#yearPrintJobs').text(printCnt);
            $('#yearBindingJobs').text(bindCnt);
            $('#yearTotalJobs').text(jobs);
        }
    }
}

function buildDetailedReportTable(period, jobs) {
    var container;
    if (period === 'today') container = $('#todayJobsTableContainer');
    else if (period === 'week') container = $('#weekJobsTableContainer');
    else if (period === 'month') container = $('#monthJobsTableContainer');
    else if (period === 'year') container = $('#yearJobsTableContainer');
    else container = $('#jobsTableBody');
    
    if (!container.length) {
        console.error('Container not found for period:', period);
        return;
    }
    
    if (!jobs || jobs.length === 0) {
        container.html('<div class="text-center text-muted py-4">No bills found for this period.</div>');
        return;
    }
    
    var grandTotalRev = 0, grandTotalDisc = 0, grandTotalSub = 0, grandTotalQty = 0;
    
    var html = '';
    html += '<div class="table-responsive">';
    html += '<table class="table table-bordered table-hover table-sm report-table" style="font-size:0.78rem;">';
    html += '<thead>';
    html += '<tr style="background:#2c3e50; color:white; position:sticky; top:0; z-index:10;">';
    html += '<th style="width:100px;text-align:center;">Bill #</th>';
    html += '<th style="width:65px;text-align:center;">Time</th>';
    html += '<th style="min-width:120px;">Customer</th>';
    html += '<th style="width:75px;">Batch</th>';
    html += '<th style="width:65px;">Type</th>';
    html += '<th style="min-width:100px;">Category</th>';
    html += '<th style="min-width:150px;">Item Name</th>';
    html += '<th style="width:40px;text-align:center;">Qty</th>';
    html += '<th style="width:60px;text-align:right;">Price</th>';
    html += '<th style="width:60px;text-align:center;">Disc%</th>';
    html += '<th style="width:70px;text-align:right;">Discount</th>';
    html += '<th style="width:85px;text-align:right;">Total</th>';
    html += '<th style="width:75px;">User</th>';
    html += '<th style="width:65px;text-align:center;">Status</th>';
    html += '</tr>';
    html += '</thead>';
    html += '<tbody>';
    
    // Sort bills by bill_id descending (latest first)
    jobs.sort(function(a, b) {
        return String(b.bill_id).localeCompare(String(a.bill_id), undefined, { numeric: true });
    });
    
    $.each(jobs, function(i, bill) {
        var isFree = bill.status === 'FREE';
        var billTotal = bill.total || 0;
        var billDiscount = bill.discount || 0;
        var billQty = 0;
        var billSubtotal = 0;
        var items = bill.items || [];
        var totalRows = items.length;
        
        if (totalRows === 0) return;
        
        var rowIndex = 0;
        var billIdDisplayed = false;
        var displayBillId = bill.bill_id;
        
        $.each(items, function(idx, item) {
            var qty = item.qty || 0;
            var price = item.price || 0;
            var discount = item.discount || 0;
            var total = item.total || 0;
            var subtotal = price * qty;
            var discPct = item.discount_percent || 0;
            
            billQty += qty;
            billSubtotal += subtotal;
            
            var cat = item.category || 'Other';
            var color = item.category_color || '#6c757d';
            
            html += '<tr class="item-row" style="border-bottom:1px solid #e9ecef;">';
            
            if (!billIdDisplayed) {
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;font-weight:bold;color:#042d5c;">' + displayBillId + '</td>';
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;">' + (bill.time || '') + '</td>';
                html += '<td rowspan="' + totalRows + '" style="vertical-align:middle;"><strong>' + (bill.customer || '-') + '</strong></td>';
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;">' + (bill.batch || '-') + '</td>';
                // var userTypeClass = bill.user_type === 'student' ? 'bg-info' : (bill.user_type === 'staff' ? 'bg-warning text-dark' : 'bg-secondary');
                var userTypeClass = bill.user_type === 'student' ? 'bg-student' : (bill.user_type === 'staff' ? 'bg-staff' : 'bg-secondary');
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;"><span class="badge ' + userTypeClass + '">' + (bill.user_type || 'Other') + '</span></td>';
                billIdDisplayed = true;
            }
            
            html += '<td style="vertical-align:middle;">';
            html += '<span class="category-badge-sm" style="display:inline-block;padding:2px 10px;border-radius:10px;font-size:0.65rem;font-weight:600;color:white;background:' + color + ';">' + cat + '</span>';
            html += '</td>';
            
            html += '<td style="vertical-align:middle;font-size:0.75rem;">' + (item.item_name || '-') + '</td>';
            
            html += '<td style="text-align:center;vertical-align:middle;font-weight:600;">' + qty + '</td>';
            html += '<td style="text-align:right;vertical-align:middle;">' + price.toFixed(2) + '</td>';
            html += '<td style="text-align:center;vertical-align:middle;font-weight:600;color: #dc3545;">' + (discPct > 0 ? Math.round(discPct) + '%' : '-') + '</td>';
            html += '<td style="text-align:right;vertical-align:middle;color: #dc3545;">' + discount.toFixed(2) + '</td>';
            html += '<td style="text-align:right;vertical-align:middle;font-weight:bold;color: #042d5c;">' + total.toFixed(2) + '</td>';
            
            if (rowIndex === 0) {
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;">' + (bill.user || 'N/A') + '</td>';
                html += '<td rowspan="' + totalRows + '" style="text-align:center;vertical-align:middle;">' + (isFree ? '<span class="badge bg-danger" style="font-size:0.65rem;">FREE</span>' : '<span class="badge bg-success" style="font-size:0.65rem;">PAID</span>') + '</td>';
            }
            
            html += '</tr>';
            rowIndex++;
        });
        
        // Accumulate grand totals
        grandTotalQty += billQty;
        grandTotalRev += billTotal;
        grandTotalDisc += billDiscount;
        grandTotalSub += billSubtotal;
        
        var billDiscPct = (billSubtotal > 0) ? (billDiscount / billSubtotal) * 100 : 0;
        html += '<tr class="bill-total" style="background:#fff3cd; border-top:2px solid #dc3545;">';
        html += '<td colspan="7" style="text-align:right;font-weight:bold;padding-right:15px;">BILL TOTAL</td>';
        html += '<td style="text-align:center;font-weight:bold;">' + billQty + '</td>';
        html += '<td></td>';
        html += '<td style="text-align:center;font-weight:bold;color: #dc3545;">' + (billDiscPct > 0 ? Math.round(billDiscPct) + '%' : '-') + '</td>';
        html += '<td style="text-align:right;font-weight:bold;color: #dc3545;">' + billDiscount.toFixed(2) + '</td>';
        html += '<td style="text-align:right;font-weight:bold;color: #042d5c;font-size:1.05rem;">' + billTotal.toFixed(2) + '</td>';
        html += '<td colspan="2"></td>';
        html += '</tr>';
        html += '<tr style="height:3px;background:transparent;"><td colspan="14" style="border:none;padding:2px 0;"></td></tr>';
    });
    
    // Grand Total
    var grandDiscPct = (grandTotalSub > 0) ? (grandTotalDisc / grandTotalSub) * 100 : 0;
    html += '</tbody>';
    html += '<tfoot>';
    html += '<tr class="grand-total" style="background: #042d5c; color:white; font-weight:bold; border-top:3px solid #ff0730;">';
    html += '<td colspan="7" style="text-align:right; font-size:0.9rem; padding:8px;">GRAND TOTAL</td>';
    html += '<td style="text-align:center; font-size:0.9rem; padding:8px;">' + grandTotalQty + '</td>';
    html += '<td></td>';
    html += '<td style="text-align:center;color:#ffc107;font-size:0.9rem; padding:8px;">' + (grandDiscPct > 0 ? Math.round(grandDiscPct) + '%' : '-') + '</td>';
    html += '<td style="text-align:right;color:#ffc107;font-size:0.9rem; padding:8px;">' + grandTotalDisc.toFixed(2) + '</td>';
    html += '<td style="text-align:right;color:#ffc107;font-size:1.2rem;font-weight:bold;">' + grandTotalRev.toFixed(2) + '</td>';
    html += '<td colspan="2"></td>';
    html += '</tr>';
    html += '</tfoot>';
    html += '</table>';
    html += '</div>';
    
    container.html(html);
}

function updateEmptyState(period) {
    let container;
    if (period === 'today') container = $('#todayJobsTableContainer');
    else if (period === 'week') container = $('#weekJobsTableContainer');
    else if (period === 'month') container = $('#monthJobsTableContainer');
    else if (period === 'year') container = $('#yearJobsTableContainer');
    else container = $('#jobsTableBody');
    if (container.length) { 
        container.html('<div class="text-center text-muted py-4">No data available.</div>'); 
    }
}

function loadAllSummaries() {
    const now = new Date();
    loadReportData('today');
    const currentDay = now.getDay(), diff = (currentDay === 0 ? 6 : currentDay - 1);
    const monday = new Date(now); monday.setDate(now.getDate() - diff);
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
    const wFrom = monday.toISOString().split('T')[0], wTo = sunday.toISOString().split('T')[0];
    $('#weekLabel').text('This Week (' + wFrom + ' to ' + wTo + ')');
    loadReportData('week', wFrom, wTo);
    const m = now.getMonth() + 1, y = now.getFullYear();
    const mFrom = y + '-' + String(m).padStart(2,'0') + '-01';
    const mTo = y + '-' + String(m).padStart(2,'0') + '-' + new Date(y, m, 0).getDate();
    $('#monthLabel').text('This Month (' + new Date(y, m-1, 1).toLocaleString('default', { month: 'long', year: 'numeric' }) + ')');
    loadReportData('month', mFrom, mTo);
    const yFrom = y + '-01-01', yTo = y + '-12-31';
    $('#yearLabel').text('This Year (' + y + ')');
    loadReportData('year', yFrom, yTo);
}

function changeWeek(delta) {
    weekOffset += delta;
    const now = new Date(), currentDay = now.getDay(), diff = (currentDay === 0 ? 6 : currentDay - 1);
    const monday = new Date(now); monday.setDate(now.getDate() - diff + (weekOffset * 7));
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
    const from = monday.toISOString().split('T')[0], to = sunday.toISOString().split('T')[0];
    $('#weekLabel').text('Week of ' + monday.toLocaleDateString() + ' - ' + sunday.toLocaleDateString());
    loadReportData('week', from, to);
}

function resetWeek() { 
    weekOffset = 0; 
    const now = new Date(), currentDay = now.getDay(), diff = (currentDay === 0 ? 6 : currentDay - 1);
    const monday = new Date(now); monday.setDate(now.getDate() - diff);
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
    const from = monday.toISOString().split('T')[0], to = sunday.toISOString().split('T')[0];
    $('#weekLabel').text('This Week'); 
    loadReportData('week', from, to);
}

function changeMonth(delta) {
    monthOffset += delta;
    const now = new Date(), month = now.getMonth() + monthOffset, year = now.getFullYear() + Math.floor(month / 12);
    const m = ((month % 12) + 12) % 12 + 1;
    const from = year + '-' + String(m).padStart(2,'0') + '-01';
    const to = year + '-' + String(m).padStart(2,'0') + '-' + new Date(year, m, 0).getDate();
    $('#monthLabel').text(new Date(year, m-1, 1).toLocaleString('default', { month: 'long', year: 'numeric' }));
    loadReportData('month', from, to);
}

function resetMonth() { 
    monthOffset = 0; 
    const now = new Date(), m = now.getMonth() + 1, y = now.getFullYear();
    const from = y + '-' + String(m).padStart(2,'0') + '-01';
    const to = y + '-' + String(m).padStart(2,'0') + '-' + new Date(y, m, 0).getDate();
    $('#monthLabel').text('This Month'); 
    loadReportData('month', from, to);
}

function changeYear(delta) {
    yearOffset += delta;
    const now = new Date(), year = now.getFullYear() + yearOffset;
    const from = year + '-01-01', to = year + '-12-31';
    $('#yearLabel').text(year); 
    loadReportData('year', from, to);
}

function resetYear() { 
    yearOffset = 0; 
    const now = new Date(), year = now.getFullYear();
    const from = year + '-01-01', to = year + '-12-31';
    $('#yearLabel').text('This Year'); 
    loadReportData('year', from, to);
}

function safeFormat(value, decimals = 2) {
    const num = parseFloat(value) || 0;
    return num.toFixed(decimals);
}

function safeNumber(value) {
    return parseFloat(value) || 0;
}

function openDayEndModal() {
    new bootstrap.Modal(document.getElementById('dayEndModal')).show();
}

function openFinanceReportModal() {
    $('#financeReportModal').modal('show');
    $('#financeReportContent').html('<p class="text-muted small">Select range and click Show.</p>');
    window.reportData = null;
}

function toggleFinanceReportType() {
    const selected = $('input[name="financeReportType"]:checked').val();
    if (selected === 'all' || selected === 'zreport' || selected === 'xreport' || selected === 'financial') {
        $('#financeCategoryGroup').hide();
        $('#financeItemGroup').hide();
    } else if (selected === 'category') {
        $('#financeCategoryGroup').show();
        $('#financeItemGroup').hide();
    } else if (selected === 'item' || selected === 'bill') {
        $('#financeCategoryGroup').hide();
        $('#financeItemGroup').show();
    }
}

function toggleAllItems() {
    const checked = $('#selectAllItems').is(':checked');
    $('#financeItemGroup input[type="checkbox"]').not('#selectAllItems').prop('checked', checked);
}

function showFinanceReport() {
    const from = $('#financeFrom').val();
    const to = $('#financeTo').val();
    const reportType = $('input[name="financeReportType"]:checked').val();

    if (!from || !to) {
        $('#financeReportContent').html('<div class="alert alert-danger">Please select date range.</div>');
        return;
    }

    window.reportFrom = from;
    window.reportTo = to;

    let url = apiBase + 'lib_report_data.php?from=' + from + '&to=' + to + '&report_type=' + reportType;

    if (reportType === 'category') {
        const category = $('#financeCategory').val();
        url += '&category=' + category;
    } else if (reportType === 'item' || reportType === 'bill') {
        const items = [];
        $('#financeItemGroup input[type="checkbox"]:not(#selectAllItems):checked').each(function() {
            items.push($(this).val());
        });
        if (items.length === 0) {
            $('#financeReportContent').html('<div class="alert alert-danger">Please select at least one item.</div>');
            return;
        }
        url += '&items=' + items.join(',');
        url += '&category=both';
        window.reportItems = items;
    } else {
        url += '&category=both';
    }

    window.reportType = reportType;

    $('#financeReportContent').html('<div class="text-center"><i class="bi bi-hourglass-split"></i> Loading...</div>');

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                $('#financeReportContent').html('<div class="alert alert-danger">Error: ' + (data.error || 'Unknown error') + '</div>');
                return;
            }
            window.reportData = data;
            renderFinanceReport(data);
        })
        .catch(error => {
            console.error('Error:', error);
            $('#financeReportContent').html('<div class="alert alert-danger">Error loading report: ' + error.message + '</div>');
        });
}

function renderFinanceReport(response) {
    if (!response || !response.jobs) {
        $('#financeReportContent').html('<div class="alert alert-warning">No data received from server.</div>');
        return;
    }

    const jobs = response.jobs || [];
    const summary = response.summary || {};
    const reportType = response.report_type || 'all';
    const from = response.from || window.reportFrom || '';
    const to = response.to || window.reportTo || '';

    let html = `
        <div class="mb-3">
            <h5><strong>${reportType.toUpperCase()} Report</strong></h5>
            <small class="text-muted">Period: ${from} to ${to}</small>
            <span class="badge bg-info ms-2">${jobs.length} Jobs</span>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Total Jobs</div><div class="stat-number">${summary.total_jobs || 0}</div></div></div>
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Total Revenue</div><div class="stat-number">${safeFormat(summary.total_revenue)}</div></div></div>
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Print Revenue</div><div class="stat-number">${safeFormat(summary.print_revenue)}</div></div></div>
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Binding Revenue</div><div class="stat-number">${safeFormat(summary.binding_revenue)}</div></div></div>
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Paid Jobs</div><div class="stat-number">${summary.paid_jobs || 0}</div></div></div>
            <div class="col-md-2"><div class="stat-box"><div class="text-muted small">Free Jobs</div><div class="stat-number">${summary.free_jobs || 0}</div></div></div>
        </div>
    `;

    if (jobs.length === 0) {
        html += `<div class="alert alert-info text-center">No jobs found for the selected period.</div>`;
        $('#financeReportContent').html(html);
        return;
    }

    if (reportType === 'all') {
        html += `<div class="table-responsive"><table class="table table-striped table-sm">
            <thead><tr><th>Date</th><th>Name</th><th>Type</th><th>Amount</th></tr></thead><tbody>`;
        jobs.forEach(job => {
            html += `<tr><td>${job.job_date}</td><td>${job.name}</td><td>${job.job_type}</td><td>${safeFormat(job.net_amount)}</td></tr>`;
        });
        html += `</tbody></table></div>`;
    } else if (reportType === 'category') {
        const catMap = {};
        jobs.forEach(job => {
            const cat = job.job_type || 'Other';
            if (!catMap[cat]) catMap[cat] = { count: 0, total: 0 };
            catMap[cat].count++;
            catMap[cat].total += parseFloat(job.net_amount) || 0;
        });
        html += `<div class="table-responsive"><table class="table table-striped table-sm">
            <thead><tr><th>Category</th><th>Jobs</th><th>Total Revenue</th></tr></thead><tbody>`;
        Object.keys(catMap).forEach(cat => {
            html += `<tr><td>${cat}</td><td>${catMap[cat].count}</td><td>${safeFormat(catMap[cat].total)}</td></tr>`;
        });
        html += `</tbody></table></div>`;
    } else if (reportType === 'item') {
        const itemMap = {};
        jobs.forEach(job => {
            if (job.job_type === 'printing') {
                ['one_side','both_side','rough_one','rough_two','error_count'].forEach(field => {
                    const qty = parseInt(job[field]) || 0;
                    if (qty > 0) {
                        const name = field.replace('_',' ').toUpperCase();
                        if (!itemMap[name]) itemMap[name] = { qty: 0, total: 0 };
                        itemMap[name].qty += qty;
                        const share = qty / (parseInt(job.total_sheets) || 1);
                        itemMap[name].total += (parseFloat(job.net_amount) || 0) * share;
                    }
                });
            } else {
                const qty = parseInt(job.quantity) || 0;
                if (qty > 0) {
                    const name = 'Binding';
                    if (!itemMap[name]) itemMap[name] = { qty: 0, total: 0 };
                    itemMap[name].qty += qty;
                    itemMap[name].total += parseFloat(job.net_amount) || 0;
                }
            }
        });
        html += `<div class="table-responsive"><table class="table table-striped table-sm">
            <thead><tr><th>Item</th><th>Total Qty</th><th>Total Revenue</th></tr></thead><tbody>`;
        Object.keys(itemMap).forEach(name => {
            html += `<tr><td>${name}</td><td>${itemMap[name].qty}</td><td>${safeFormat(itemMap[name].total)}</td></tr>`;
        });
        html += `</tbody></table></div>`;
    } else {
        html += `<div class="alert alert-info">Report type ${reportType} rendered with ${jobs.length} jobs.</div>`;
    }

    $('#financeReportContent').html(html);
}

function downloadFinanceReport(format) {
    if (!window.reportData) {
        alert('Please click "Show" first to generate the report.');
        return;
    }

    const from = window.reportFrom || $('#financeFrom').val();
    const to = window.reportTo || $('#financeTo').val();
    const reportType = window.reportType || $('input[name="financeReportType"]:checked').val();
    let category = 'both';
    let items = [];

    if (reportType === 'category') {
        category = window.reportCategory || $('#financeCategory').val();
    } else if (reportType === 'item' || reportType === 'bill') {
        items = window.reportItems || [];
        if (items.length === 0) {
            $('#financeItemGroup input[type="checkbox"]:not(#selectAllItems):checked').each(function() {
                items.push($(this).val());
            });
        }
        if (items.length === 0) {
            alert('Please select at least one item.');
            return;
        }
    }

    let params = 'format=' + format + '&type=custom&from=' + from + '&to=' + to + '&report_type=' + reportType;
    if (reportType === 'category') {
        params += '&category=' + category;
    } else if (reportType === 'item' || reportType === 'bill') {
        params += '&category=both&items=' + items.join(',');
    } else {
        params += '&category=both';
    }

    window.open(apiBase + 'lib_report.php?' + params, '_blank');
}
</script>

<!-- Add this to your index.php page -->
<script>
// Auto-logout after 15 minutes of inactivity
(function() {
    // Set timeout to 15 minutes (900,000 milliseconds)
    const INACTIVITY_TIMEOUT = 15 * 60 * 1000;
    let logoutTimer;

    // Function to handle logout
    function logoutUser() {
        // Clear any session data
        localStorage.clear();
        sessionStorage.clear();
        
        // Redirect to logout page which will handle server-side logout
        window.location.href = 'logout.php';
        // Or redirect directly to login page
        // window.location.href = 'login.php';
    }

    // Function to reset the timer
    function resetTimer() {
        clearTimeout(logoutTimer);
        logoutTimer = setTimeout(logoutUser, INACTIVITY_TIMEOUT);
    }

    // Reset timer on user activity
    const events = [
        'load', 'mousemove', 'mousedown', 'click', 'scroll', 
        'keypress', 'touchstart', 'touchmove', 'wheel',
        'input', 'change', 'submit', 'focus'
    ];

    // Add event listeners
    events.forEach(event => {
        document.addEventListener(event, resetTimer);
    });

    // Initialize timer when page loads
    resetTimer();

    // Optional: Show warning before logout (10 seconds before)
    function showWarning() {
        // You can implement a warning popup here if needed
        console.log('Session will expire in 10 seconds');
    }

    // Uncomment below to show warning 10 seconds before logout
    // setTimeout(() => {
    //     alert('Your session will expire in 10 seconds. Click OK to stay logged in.');
    //     resetTimer();
    // }, INACTIVITY_TIMEOUT - 10000);

})();
</script>

</body>
</html>