<?php
// lib_catagory_items.php
ob_start();
date_default_timezone_set('Asia/Colombo');

include("includes/header.php");

require_once __DIR__ . '/database/connection.php';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

require_once 'PermissionChecking.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Category Actions
    if ($action === 'save_category') {
        $id = $_POST['id'] ?? null;
        $catagory_name = trim($_POST['catagory_name']);
        $catagory_color = trim($_POST['catagory_color'] ?? '#e9ecef');
        $icon = !empty(trim($_POST['icon'] ?? '')) ? trim($_POST['icon']) : null;
        
        if (empty($catagory_name)) {
            header('Location: lib_catagory_items.php?error=Category+name+is+required');
            exit;
        }
        
        if ($id) {
            $stmt = $conn->prepare("UPDATE lib_catagory SET catagory_name=?, catagory_color=?, icon=? WHERE catagory_id=?");
            $stmt->bind_param("sssi", $catagory_name, $catagory_color, $icon, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO lib_catagory (catagory_name, catagory_color, icon) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $catagory_name, $catagory_color, $icon);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Category+saved&tab=categories');
        exit;
    }
    
    if ($action === 'delete_category') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM lib_catagory WHERE catagory_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Category+deleted&tab=categories');
        exit;
    }
    
    // Sub Category Actions
    if ($action === 'save_sub_category') {
        $id = $_POST['id'] ?? null;
        $catagory_id = (int)$_POST['catagory_id'];
        $sub_catagory_name = trim($_POST['sub_catagory_name']);
        $sub_catagory_color = trim($_POST['sub_catagory_color'] ?? '#e9ecef');
        $icon = !empty(trim($_POST['icon'] ?? '')) ? trim($_POST['icon']) : null;
        $is_active = (int)($_POST['is_active'] ?? 1);
        
        if (empty($sub_catagory_name) || $catagory_id <= 0) {
            header('Location: lib_catagory_items.php?error=Sub+Category+name+and+Category+are+required&tab=subcategories');
            exit;
        }
        
        if ($id) {
            $stmt = $conn->prepare("UPDATE lib_sub_category SET catagory_id=?, sub_catagory_name=?, sub_catagory_color=?, icon=?, is_active=? WHERE id=?");
            $stmt->bind_param("issiii", $catagory_id, $sub_catagory_name, $sub_catagory_color, $icon, $is_active, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO lib_sub_category (catagory_id, sub_catagory_name, sub_catagory_color, icon, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $catagory_id, $sub_catagory_name, $sub_catagory_color, $icon, $is_active);
        }
        
        if (!$stmt->execute()) {
            header('Location: lib_catagory_items.php?error=' . urlencode($stmt->error) . '&tab=subcategories');
            exit;
        }
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Sub+Category+saved&tab=subcategories');
        exit;
    }
    
    if ($action === 'delete_sub_category') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM lib_sub_category WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Sub+Category+deleted&tab=subcategories');
        exit;
    }
    
    // Item Actions
    if ($action === 'save_item') {
        $id = $_POST['id'] ?? null;
        $item_name = trim($_POST['item_name']);
        $catagory_id = (int)$_POST['catagory_id'];
        $sub_catagory_id = !empty($_POST['sub_catagory_id']) ? (int)$_POST['sub_catagory_id'] : null;
        $size = trim($_POST['size'] ?? '');
        $amount = (float)$_POST['amount'];
        $discount_percent = (float)$_POST['discount_percent'];
        $valid_discount = trim($_POST['valid_discount'] ?? '');
        $min_quantity = (int)($_POST['min_quantity'] ?? 1);
        $is_active = (int)($_POST['is_active'] ?? 1);
        
        if (empty($item_name) || $catagory_id <= 0 || empty($size) || $amount <= 0) {
            header('Location: lib_catagory_items.php?error=Item+name,+category,+size+and+price+are+required&tab=items');
            exit;
        }
        
        if ($id) {
            $stmt = $conn->prepare("UPDATE lib_item SET item_name=?, catagory_id=?, sub_catagory_id=?, size=?, amount=?, discount_percent=?, valid_discount=?, min_quantity=?, is_active=? WHERE id=?");
            $stmt->bind_param("siisddsiii", $item_name, $catagory_id, $sub_catagory_id, $size, $amount, $discount_percent, $valid_discount, $min_quantity, $is_active, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO lib_item (item_name, catagory_id, sub_catagory_id, size, amount, discount_percent, valid_discount, min_quantity, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisddsii", $item_name, $catagory_id, $sub_catagory_id, $size, $amount, $discount_percent, $valid_discount, $min_quantity, $is_active);
        }
        $stmt->execute();
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Item+saved&tab=items');
        exit;
    }
    
    if ($action === 'delete_item') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM lib_item WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header('Location: lib_catagory_items.php?msg=Item+deleted&tab=items');
        exit;
    }
}

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
$subCatResult = $conn->query("
    SELECT sc.*, c.catagory_name 
    FROM lib_sub_category sc 
    LEFT JOIN lib_catagory c ON sc.catagory_id = c.catagory_id 
    ORDER BY c.catagory_name, sc.sub_catagory_name
");
if ($subCatResult) {
    while ($row = $subCatResult->fetch_assoc()) {
        $subCategories[] = $row;
    }
}

// Fetch items with category and sub category names
$items = [];
$itemResult = $conn->query("
    SELECT i.*, c.catagory_name, c.catagory_color, 
           sc.sub_catagory_name, sc.sub_catagory_color
    FROM lib_item i 
    LEFT JOIN lib_catagory c ON i.catagory_id = c.catagory_id 
    LEFT JOIN lib_sub_category sc ON i.sub_catagory_id = sc.id
    ORDER BY c.catagory_name, sc.sub_catagory_name, i.item_name
");
if ($itemResult) {
    while ($row = $itemResult->fetch_assoc()) {
        $items[] = $row;
    }
}

// Get active tab from URL parameter
$activeTab = $_GET['tab'] ?? 'categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories & Items – BMS Library</title>
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
        body { background: #f4f7fc; font-size: 0.85rem; }
        .card { border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .table-sm th, .table-sm td { padding: 4px 6px; font-size: 0.8rem; }
        .form-control-sm, .form-select-sm { font-size: 0.8rem; padding: 2px 8px; }
        .btn-sm { font-size: 0.75rem; padding: 2px 10px; }
        .color-preview { display: inline-block; width: 20px; height: 20px; border-radius: 4px; border: 1px solid #ddd; vertical-align: middle; }
        .nav-tabs .nav-link { font-size: 0.85rem; padding: 6px 16px; }
        .badge-icon { font-size: 1.2rem; }
        .sub-cat-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; color: white; }
        
        /* Form controls - #042d5c color and bold */
        .form-control, .form-select, .form-control-sm, .form-select-sm {
            color: #042d5c !important;
            font-weight: 600 !important;
        }
        .form-control::placeholder, .form-select::placeholder {
            color: #6c757d !important;
            font-weight: 400 !important;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        /* DataTable search box positioning */
        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
            margin-left: 5px;
            color: #042d5c !important;
            font-weight: 600 !important;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-bottom: 10px;
        }
        
        /* Auto close alerts */
        .alert-dismissible .btn-close {
            padding: 0.5rem;
        }
    </style>
</head>
<body>

<!-- Page Wrapper -->
<div id="wrapper">
    <!-- Sidebar -->
    <?php include("nav.php"); ?>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>

            <!-- Begin Page Content -->
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Categories & Items Manager</h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-tags"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">ADD | EDIT | DELETE</h6>
                            </div>
                            <div class="card-body">

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show py-1" role="alert" id="autoCloseAlert">
        <?= htmlspecialchars($_GET['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show py-1" role="alert" id="autoCloseAlert">
        <?= htmlspecialchars($_GET['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-2" id="managerTabs" role="tablist">
    <li class="nav-item"><a class="nav-link <?= $activeTab == 'categories' ? 'active' : '' ?>" data-bs-toggle="tab" href="#categoriesTab">Categories</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab == 'subcategories' ? 'active' : '' ?>" data-bs-toggle="tab" href="#subCategoriesTab">Sub Categories</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab == 'items' ? 'active' : '' ?>" data-bs-toggle="tab" href="#itemsTab">Items</a></li>
</ul>

<div class="tab-content">
    <!-- Categories Tab -->
    <div class="tab-pane fade <?= $activeTab == 'categories' ? 'show active' : '' ?>" id="categoriesTab">
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header py-1"><strong>Category List</strong></div>
                    <div class="card-body p-1">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm mb-0" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Icon</th>
                                        <th>Name</th>
                                        <th>Color</th>
                                        <th style="width:120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= $cat['catagory_id'] ?></td>
                                        <td><?= htmlspecialchars($cat['icon'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($cat['catagory_name']) ?></td>
                                        <td>
                                            <span class="color-preview" style="background:<?= htmlspecialchars($cat['catagory_color'] ?? '#e9ecef') ?>"></span>
                                            <?= htmlspecialchars($cat['catagory_color'] ?? '#e9ecef') ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editCategory(<?= $cat['catagory_id'] ?>)"><i class="bi bi-pencil"></i></button>
                                            <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this category? This will also delete all items in it.')">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="id" value="<?= $cat['catagory_id'] ?>">
                                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header py-1"><strong id="categoryFormTitle">Add Category</strong></div>
                    <div class="card-body py-2">
                        <form method="post" class="row g-2" id="categoryForm">
                            <input type="hidden" name="action" value="save_category">
                            <input type="hidden" name="id" id="categoryId" value="">
                            <div class="col-12">
                                <label class="form-label small">Category Name</label>
                                <input type="text" name="catagory_name" id="categoryName" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Color</label>
                                <input type="color" name="catagory_color" id="categoryColor" class="form-control form-control-sm" value="#e9ecef">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Icon (Emoji)</label>
                                <input type="text" name="icon" id="categoryIcon" class="form-control form-control-sm" placeholder="Enter emoji">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm" type="submit" id="categorySaveBtn">Save Category</button>
                                <button class="btn btn-secondary btn-sm" type="button" onclick="resetCategoryForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sub Categories Tab -->
    <div class="tab-pane fade <?= $activeTab == 'subcategories' ? 'show active' : '' ?>" id="subCategoriesTab">
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header py-1"><strong>Sub Category List</strong></div>
                    <div class="card-body p-1">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm mb-0" id="subCategoriesTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Icon</th>
                                        <th>Sub Category</th>
                                        <th>Category</th>
                                        <th>Color</th>
                                        <th>Active</th>
                                        <th style="width:100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subCategories as $sc): ?>
                                    <tr>
                                        <td><?= $sc['id'] ?></td>
                                        <td><?= htmlspecialchars($sc['icon'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sc['sub_catagory_name']) ?></td>
                                        <td><?= htmlspecialchars($sc['catagory_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="color-preview" style="background:<?= htmlspecialchars($sc['sub_catagory_color'] ?? '#e9ecef') ?>"></span>
                                            <?= htmlspecialchars($sc['sub_catagory_color'] ?? '#e9ecef') ?>
                                        </td>
                                        <td><?= $sc['is_active'] ? '✅' : '❌' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editSubCategory(<?= $sc['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                            <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this sub category?')">
                                                <input type="hidden" name="action" value="delete_sub_category">
                                                <input type="hidden" name="id" value="<?= $sc['id'] ?>">
                                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header py-1"><strong id="subCategoryFormTitle">Add Sub Category</strong></div>
                    <div class="card-body py-2">
                        <form method="post" class="row g-2" id="subCategoryForm">
                            <input type="hidden" name="action" value="save_sub_category">
                            <input type="hidden" name="id" id="subCategoryId" value="">
                            <div class="col-12">
                                <label class="form-label small">Sub Category Name</label>
                                <input type="text" name="sub_catagory_name" id="subCategoryName" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Parent Category</label>
                                <select name="catagory_id" id="subCategoryParent" class="form-select form-select-sm" required>
                                    <option value="">Select</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['catagory_id'] ?>"><?= htmlspecialchars($cat['catagory_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Color</label>
                                <input type="color" name="sub_catagory_color" id="subCategoryColor" class="form-control form-control-sm" value="#e9ecef">
                            </div>
                            <div class="col-3">
                                <label class="form-label small">Icon</label>
                                <input type="text" name="icon" id="subCategoryIcon" class="form-control form-control-sm" placeholder="Enter emoji">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Active</label>
                                <select name="is_active" id="subCategoryActive" class="form-select form-select-sm">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm" type="submit" id="subCategorySaveBtn">Save Sub Category</button>
                                <button class="btn btn-secondary btn-sm" type="button" onclick="resetSubCategoryForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Tab -->
    <div class="tab-pane fade <?= $activeTab == 'items' ? 'show active' : '' ?>" id="itemsTab">
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header py-1"><strong>Item List</strong></div>
                    <div class="card-body p-1">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm mb-0" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Sub Category</th>
                                        <th>Item Name</th>
                                        <th>Size</th>
                                        <th>Price</th>
                                        <th>Disc%</th>
                                        <th>Qty Disc</th>
                                        <th>Active</th>
                                        <th style="width:80px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td>
                                            <span class="color-preview" style="background:<?= htmlspecialchars($item['catagory_color'] ?? '#e9ecef') ?>"></span>
                                            <?= htmlspecialchars($item['catagory_name'] ?? 'Uncategorized') ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['sub_catagory_name'])): ?>
                                                <span class="sub-cat-badge" style="background:<?= htmlspecialchars($item['sub_catagory_color'] ?? '#e9ecef') ?>">
                                                    <?= htmlspecialchars($item['sub_catagory_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td><?= htmlspecialchars($item['size'] ?? '-') ?></td>
                                        <td><?= number_format($item['amount'], 2) ?></td>
                                        <td><?= $item['discount_percent'] ?>%</td>
                                        <td>
                                            <?php if (!empty($item['valid_discount'])): ?>
                                                <small><?= htmlspecialchars($item['valid_discount']) ?></small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $item['is_active'] ? '✅' : '❌' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editItem(<?= $item['id'] ?>)"><i class="bi bi-pencil"></i></button>
                                            <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this item?')">
                                                <input type="hidden" name="action" value="delete_item">
                                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                                <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header py-1"><strong id="itemFormTitle">Add Item</strong></div>
                    <div class="card-body py-2">
                        <form method="post" class="row g-2" id="itemForm">
                            <input type="hidden" name="action" value="save_item">
                            <input type="hidden" name="id" id="itemId" value="">
                            <div class="col-7">
                                <label class="form-label small">Item Name</label>
                                <input type="text" name="item_name" id="itemName" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small">Active</label>
                                <select name="is_active" id="itemActive" class="form-select form-select-sm">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Category</label>
                                <select name="catagory_id" id="itemCategory" class="form-select form-select-sm" required onchange="loadSubCategoriesForItem()">
                                    <option value="">Select</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['catagory_id'] ?>"><?= htmlspecialchars($cat['catagory_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Sub Category</label>
                                <select name="sub_catagory_id" id="itemSubCategory" class="form-select form-select-sm">
                                    <option value="">None</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Size</label>
                                <input type="text" name="size" id="itemSize" class="form-control form-control-sm" placeholder="e.g. A4" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Price (LKR)</label>
                                <input type="number" step="0.01" name="amount" id="itemAmount" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Discount %</label>
                                <input type="number" step="0.1" name="discount_percent" id="itemDiscount" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Min Qty</label>
                                <input type="number" name="min_quantity" id="itemMinQty" class="form-control form-control-sm" value="1">
                            </div>
                            <div class="col-8">
                                <label class="form-label small">Qty Discount Rules (qty=discount%, e.g. 10=5, 20=10)</label>
                                <input type="text" name="valid_discount" id="itemValidDiscount" class="form-control form-control-sm" placeholder="10=5, 20=10, 50=15">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary btn-sm" type="submit" id="itemSaveBtn">Save Item</button>
                                <button class="btn btn-secondary btn-sm" type="button" onclick="resetItemForm()">Cancel</button>
                            </div>
                        </form>
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
            </div>
        </div>
    </div>
</div>

<script>
// Auto close alert after 3 seconds
$(document).ready(function() {
    if ($('#autoCloseAlert').length) {
        setTimeout(function() {
            $('#autoCloseAlert').fadeOut('slow', function() {
                $(this).alert('close');
            });
        }, 3000);
    }
});

// Load sub categories for item form
function loadSubCategoriesForItem() {
    const catId = document.getElementById('itemCategory').value;
    const subSelect = document.getElementById('itemSubCategory');
    subSelect.innerHTML = '<option value="">None</option>';
    
    if (!catId) return Promise.resolve();
    
    return fetch('libPOS/get_sub_categories.php?catagory_id=' + catId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sub_categories) {
                data.sub_categories.forEach(sc => {
                    const opt = document.createElement('option');
                    opt.value = sc.id;
                    opt.textContent = sc.sub_catagory_name;
                    subSelect.appendChild(opt);
                });
            }
        })
        .catch(err => console.error('Error loading sub categories:', err));
}

// Category functions
// Category functions
function editCategory(id) {
    const rows = document.querySelectorAll('#categoriesTab tbody tr');
    let found = false;
    
    rows.forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length && parseInt(cells[0].textContent) === id) {
            const icon = cells[1].textContent.trim();
            const catName = cells[2].textContent.trim();
            const colorText = cells[3].textContent.trim();
            
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = catName;
            
            const colorMatch = colorText.match(/#[0-9a-fA-F]{6}/);
            document.getElementById('categoryColor').value = colorMatch ? colorMatch[0] : '#e9ecef';
            document.getElementById('categoryIcon').value = icon;
            
            document.getElementById('categoryFormTitle').textContent = 'Edit Category';
            document.getElementById('categorySaveBtn').textContent = 'Update Category';
            found = true;
        }
    });
    
    if (!found) {
        alert('Category not found!');
    }
}

function resetCategoryForm() {
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryColor').value = '#e9ecef';
    document.getElementById('categoryIcon').value = '';
    document.getElementById('categoryFormTitle').textContent = 'Add Category';
    document.getElementById('categorySaveBtn').textContent = 'Save Category';
}

function resetCategoryForm() {
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryColor').value = '#e9ecef';
    document.getElementById('categoryIcon').value = '';
    document.getElementById('categoryFormTitle').textContent = 'Add Category';
    document.getElementById('categorySaveBtn').textContent = 'Save Category';
}

// Sub Category functions
// Sub Category functions
function editSubCategory(id) {
    // Find the row in the table
    const rows = document.querySelectorAll('#subCategoriesTab tbody tr');
    let found = false;
    
    rows.forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length && parseInt(cells[0].textContent) === id) {
            // Get data from table cells
            const icon = cells[1].textContent.trim();
            const subCatName = cells[2].textContent.trim();
            const catName = cells[3].textContent.trim();
            const colorText = cells[4].textContent.trim();
            const activeStatus = cells[5].textContent.trim();
            
            // Set form values
            document.getElementById('subCategoryId').value = id;
            document.getElementById('subCategoryName').value = subCatName;
            
            // Set parent category
            const parentSelect = document.getElementById('subCategoryParent');
            for (let i = 0; i < parentSelect.options.length; i++) {
                if (parentSelect.options[i].text.trim() === catName) {
                    parentSelect.value = parentSelect.options[i].value;
                    break;
                }
            }
            
            // Set color (extract hex value)
            const colorMatch = colorText.match(/#[0-9a-fA-F]{6}/);
            document.getElementById('subCategoryColor').value = colorMatch ? colorMatch[0] : '#e9ecef';
            
            // Set icon
            document.getElementById('subCategoryIcon').value = icon;
            
            // Set active status
            document.getElementById('subCategoryActive').value = activeStatus === '✅' ? '1' : '0';
            
            // Update form title and button
            document.getElementById('subCategoryFormTitle').textContent = 'Edit Sub Category';
            document.getElementById('subCategorySaveBtn').textContent = 'Update Sub Category';
            
            found = true;
        }
    });
    
    if (!found) {
        alert('Sub Category not found!');
    }
}

function resetSubCategoryForm() {
    document.getElementById('subCategoryId').value = '';
    document.getElementById('subCategoryName').value = '';
    document.getElementById('subCategoryParent').value = '';
    document.getElementById('subCategoryColor').value = '#e9ecef';
    document.getElementById('subCategoryIcon').value = '';
    document.getElementById('subCategoryActive').value = '1';
    document.getElementById('subCategoryFormTitle').textContent = 'Add Sub Category';
    document.getElementById('subCategorySaveBtn').textContent = 'Save Sub Category';
}

function resetSubCategoryForm() {
    document.getElementById('subCategoryId').value = '';
    document.getElementById('subCategoryName').value = '';
    document.getElementById('subCategoryParent').value = '';
    document.getElementById('subCategoryColor').value = '#e9ecef';
    document.getElementById('subCategoryIcon').value = '';
    document.getElementById('subCategoryActive').value = '1';
    document.getElementById('subCategoryFormTitle').textContent = 'Add Sub Category';
    document.getElementById('subCategorySaveBtn').textContent = 'Save Sub Category';
}

// Item functions
function editItem(id) {
    fetch('libPOS/get_item.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('itemId').value = data.item.id;
                document.getElementById('itemName').value = data.item.item_name;
                document.getElementById('itemCategory').value = data.item.catagory_id;
                document.getElementById('itemSize').value = data.item.size || '';
                document.getElementById('itemAmount').value = data.item.amount;
                document.getElementById('itemDiscount').value = data.item.discount_percent;
                document.getElementById('itemValidDiscount').value = data.item.valid_discount || '';
                document.getElementById('itemActive').value = data.item.is_active;
                document.getElementById('itemMinQty').value = data.item.min_quantity || 1;
                document.getElementById('itemFormTitle').textContent = 'Edit Item';
                document.getElementById('itemSaveBtn').textContent = 'Update Item';
                
                // Load sub categories and set selected
                loadSubCategoriesForItem().then(() => {
                    if (data.item.sub_catagory_id) {
                        document.getElementById('itemSubCategory').value = data.item.sub_catagory_id;
                    }
                });
            }
        })
        .catch(err => console.error('Error loading item:', err));
}

function resetItemForm() {
    document.getElementById('itemId').value = '';
    document.getElementById('itemName').value = '';
    document.getElementById('itemCategory').value = '';
    document.getElementById('itemSubCategory').innerHTML = '<option value="">None</option>';
    document.getElementById('itemSize').value = '';
    document.getElementById('itemAmount').value = '';
    document.getElementById('itemDiscount').value = '0';
    document.getElementById('itemValidDiscount').value = '';
    document.getElementById('itemActive').value = '1';
    document.getElementById('itemMinQty').value = '1';
    document.getElementById('itemFormTitle').textContent = 'Add Item';
    document.getElementById('itemSaveBtn').textContent = 'Save Item';
}

// Initialize DataTables with search
$(document).ready(function() {
    $('#categoriesTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "info": true,
        "lengthChange": true,
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": 4 }
        ],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries"
        }
    });
    
    $('#subCategoriesTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "info": true,
        "lengthChange": true,
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": 6 }
        ],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries"
        }
    });
    
    $('#itemsTable').DataTable({
        "pageLength": 10,
        "ordering": true,
        "searching": true,
        "info": true,
        "lengthChange": true,
        "autoWidth": false,
        "columnDefs": [
            { "orderable": false, "targets": 9 }
        ],
        "language": {
            "search": "Search:",
            "lengthMenu": "Show _MENU_ entries"
        }
    });
});

// Initialize sub categories when category changes
document.addEventListener('DOMContentLoaded', function() {
    const catSelect = document.getElementById('itemCategory');
    if (catSelect) {
        catSelect.addEventListener('change', loadSubCategoriesForItem);
    }
});
</script>

</body>
</html>