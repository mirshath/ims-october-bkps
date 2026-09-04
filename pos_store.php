<?php
session_start();
include("database/connection.php");
include("pos-includes/bootstrap.php");
include("includes/header.php");
include("pos-includes/products.php");


require_once 'PermissionChecking.php';


pos_require_login();

// Only products the admin has switched "Active" AND that are in stock
// are shown to shoppers. Deactivated products never appear here even if
// they still have stock.
$products = fetch_active_products(true);
if (!is_array($products) || count($products) === 0) {
    $products = [];
}

// Fetch Staff Members from admin table
$mysqli = get_db();
$staff_members = [];
if ($mysqli) {
    $resStaff = mysqli_query($mysqli, "SELECT id, username, full_name, title, role FROM admin ORDER BY username ASC");
    if ($resStaff) {
        while ($row = mysqli_fetch_assoc($resStaff)) {
            $staff_members[] = $row;
        }
    }
}

// Fetch Active Students from allocate_programme table joined with students
$active_students = [];
if ($mysqli) {
    $sqlStudents = "SELECT ap.id, ap.student_code, ap.student_registration_id, ap.new_student_registration_id, 
                           s.title, s.first_name, s.last_name, s.certificate_name, s.preferred_name
                    FROM allocate_programme ap
                    LEFT JOIN students s ON ap.student_code = s.student_code
                    WHERE ap.status = 'active'
                    ORDER BY ap.id DESC";
    $resStd = mysqli_query($mysqli, $sqlStudents);
    if ($resStd) {
        while ($row = mysqli_fetch_assoc($resStd)) {
            $active_students[] = $row;
        }
    }
}

require_once 'PermissionChecking.php';
?>
<link rel="stylesheet" href="pos-assets/css/pos-theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css">
<style>
    /* Match Select2 height/look to the existing form-select-sm controls */
    #pos-customer-type-wrapper .select2-container .select2-selection--single,
    #pos-staff-wrapper .select2-container .select2-selection--single,
    #pos-student-wrapper .select2-container .select2-selection--single {
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.15rem 0.5rem;
        font-size: 0.875rem;
        border: 1px solid #ced4da;
        border-radius: 0.2rem;
    }
    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 1.7;
        padding-left: 0.25rem;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: calc(1.5em + 0.5rem);
    }
    .select2-container {
        width: 100% !important;
    }
</style>

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
            <div class="p-3 pos-scope">

                <div class="pos-store-hero d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4><i class="fas fa-store me-2"></i>BMS POS - Store</h4>
                        <p>Tap a product to add it to the cart, then complete the purchase.</p>
                    </div>
                    <?php if (pos_is_admin()): ?>
                        <a href="pos_dashboard.php" class="pos-btn pos-btn-navy"
                            style="background:#fff; color:var(--pos-navy); box-shadow:none;">
                            <i class="fas fa-cogs"></i>&nbsp; POS Admin Panel
                        </a>
                    <?php endif; ?>
                </div>

                <style>
                    @keyframes pulseEffect {
                        0% {
                            transform: scale(1);
                            opacity: 1;
                        }

                        50% {
                            transform: scale(1.4);
                            opacity: 0.7;
                        }

                        100% {
                            transform: scale(1);
                            opacity: 1;
                        }
                    }

                    .pulse {
                        animation: pulseEffect 0.6s ease;
                    }

                    @media (max-width: 991px) {
                        .product-card {
                            display: flex !important;
                            flex-direction: row !important;
                            align-items: center;
                            padding: 0.50rem;
                            margin-bottom: 0.75rem;
                            gap: 0.75rem;
                        }

                        .product-img {
                            width: 50px !important;
                            height: 50px !important;
                            object-fit: cover;
                            border-radius: 8px;
                            flex-shrink: 0;
                        }

                        .pos-card-body {
                            padding: 0 !important;
                            flex: 1;
                            display: flex;
                            flex-direction: column;
                            min-width: 0;
                        }

                        .pos-card-body>div:first-child {
                            font-size: 14px !important;
                            font-weight: 600 !important;
                            margin: 0 !important;
                            line-height: 1.2 !important;
                        }

                        .pos-card-body .text-muted {
                            font-size: 10px !important;
                            line-height: 1.2 !important;
                        }

                        .pos-card-body .price {
                            font-size: 15px !important;
                            font-weight: 700 !important;
                            margin: 0.15rem 0 !important;
                        }

                        .pos-card-body .mt-auto {
                            margin-top: 0 !important;
                            display: flex !important;
                            align-items: center;
                            justify-content: flex-start;
                        }

                        #checkout-btn {
                            position: relative !important;
                            width: 100% !important;
                            margin-top: 1.5rem !important;
                            margin-bottom: 0 !important;
                            font-size: 16px !important;
                            font-weight: 900 !important;
                            z-index: 10;
                        }

                        #complete-purchase-btn {
                            display: flex !important;
                            justify-content: center !important;
                            align-items: center !important;
                            width: 100%;
                            margin-left: 0.5rem !important;
                            margin-right: 0.5rem !important;
                        }

                        .add-to-cart-btn,
                        .pos-btn-secondary {
                            position: absolute !important;
                            right: 0.75rem;
                            top: 50%;
                            transform: translateY(-50%);
                            width: auto !important;
                            padding: 0.4rem 0.8rem !important;
                            font-size: 13px !important;
                            white-space: nowrap;
                            flex-shrink: 0;
                        }

                        #product-grid {
                            display: block !important;
                        }

                        #product-grid>div {
                            width: 100% !important;
                            max-width: 100% !important;
                            flex: none !important;
                        }
                    }

                    @media (min-width: 992px) {
                        #mobileTab {
                            display: none !important;
                        }

                        .tab-content {
                            display: flex;
                            gap: 1rem;
                        }

                        #products-section {
                            flex: 2;
                        }

                        #cart-section {
                            flex: 1;
                            display: block !important;
                            opacity: 1 !important;
                        }

                        .product-card {
                            display: flex !important;
                            flex-direction: column !important;
                        }

                        .product-img {
                            width: 100% !important;
                            height: 140px !important;
                            object-fit: cover;
                        }

                        /* Tighter body so 6-per-row cards don't feel cramped */
                        .pos-product-card .pos-card-body {
                            padding: 0.85rem !important;
                        }

                        .pos-product-card .pos-card-body .fw-bold {
                            font-size: 13.5px !important;
                            line-height: 1.25 !important;
                            min-height: 2.2em;
                            overflow: hidden;
                            display: -webkit-box;
                            -webkit-line-clamp: 2;
                            -webkit-box-orient: vertical;
                        }

                        .pos-product-card .price.pos-price-tag {
                            font-size: 0.92rem !important;
                        }

                        .pos-product-card .add-to-cart-btn {
                            font-size: 12px !important;
                            padding: 0.35rem 0.5rem !important;
                        }
                    }

                    @media (max-width: 991px) {
                        #cart-list .list-group-item {
                            display: flex !important;
                            align-items: center;
                            padding: 0.75rem !important;
                            gap: 0.75rem;
                        }

                        #cart-list .cart-thumb {
                            width: 60px !important;
                            height: 60px !important;
                            object-fit: cover;
                            border-radius: 6px;
                            flex-shrink: 0;
                        }
                    }
                </style>

                <div class="container-fluid">
                    <div class="d-lg-none mb-3 position-relative">
                        <ul class="nav nav-tabs" id="mobileTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="products-tab" data-bs-toggle="tab"
                                    data-bs-target="#products-section" type="button" role="tab">
                                    🛍️ Products
                                </button>
                            </li>
                            <li class="nav-item position-relative" role="presentation">
                                <button class="nav-link" id="cart-tab" data-bs-toggle="tab"
                                    data-bs-target="#cart-section" type="button" role="tab">
                                    🛒 Cart & Summary
                                    <span id="cart-badge"
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <!-- Products -->
                        <div class="tab-pane fade show active" id="products-section" role="tabpanel">
                            <div class="row g-3" id="product-grid">
                                <?php if (count($products) === 0): ?>
                                    <div class="col-12">
                                        <div class="pos-empty">
                                            <i class="fas fa-box-open"></i>
                                            <strong>No products available right now</strong>
                                            <span>
                                                <?php if (pos_is_admin()): ?>
                                                    Add products and switch them on from the <a href="pos_products.php">POS
                                                        Admin Panel</a>.
                                                <?php else: ?>
                                                    Please check back soon.
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php foreach ($products as $p): ?>
                                    <?php if ((int) $p['stock'] <= 0)
                                        continue; ?>
                                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                                        <div class="card h-100 shadow-sm product-card pos-product-card position-relative">
                                            <div class="pos-product-imgwrap">
                                                <?php if ((int) $p['stock'] <= 5): ?>
                                                    <span
                                                        class="pos-pill is-warning pos-stock-tag d-none d-lg-inline-flex"><span
                                                            class="pos-pill-dot"></span> Low Stock</span>
                                                <?php endif; ?>
                                                <img src="<?= htmlspecialchars($p['image']) ?>"
                                                    alt="<?= htmlspecialchars($p['name']) ?>"
                                                    class="card-img-top product-img"
                                                    data-id="<?= htmlspecialchars($p['id']) ?>"
                                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                                    data-price="<?= number_format($p['price'], 2, '.', '') ?>"
                                                    data-stock="<?= (int) $p['stock'] ?>"
                                                    data-image="<?= htmlspecialchars($p['image']) ?>">
                                            </div>
                                            <div class="card-body d-flex flex-column pos-card-body">
                                                <div class="fw-bold" style="font-size:16px;">
                                                    <?= htmlspecialchars($p['name']) ?></div>
                                                <div class="text-muted small d-none d-lg-block">Item Code: <span
                                                        class="pos-code-tag"><?= htmlspecialchars($p['id']) ?></span></div>
                                                <div class="mt-1 price pos-price-tag">LKR
                                                    <?= number_format($p['price'], 2) ?></div>
                                                <div class="text-muted small">Stock: <?= (int) $p['stock'] ?></div>
                                                <div class="mt-auto d-none d-lg-block">
                                                    <button class="btn btn-sm btn-primary w-100 add-to-cart-btn"
                                                        data-id="<?= htmlspecialchars($p['id']) ?>"
                                                        data-name="<?= htmlspecialchars($p['name']) ?>"
                                                        data-price="<?= number_format($p['price'], 2, '.', '') ?>"
                                                        data-stock="<?= (int) $p['stock'] ?>"
                                                        data-image="<?= htmlspecialchars($p['image']) ?>">
                                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Cart -->
                        <div class="tab-pane fade" id="cart-section" role="tabpanel">
                            <!-- Card 1: Cart Items -->
                            <div class="pos-card mt-3">
                                <div class="pos-card-header">
                                    <div class="pos-card-title"><span class="pos-mini-icon"><i class="fas fa-cart-shopping"></i></span> Cart</div>
                                    <div id="cart-alert" class="text-danger small"></div>
                                </div>
                                <div class="pos-card-body">
                                    <ul id="cart-list" class="list-group list-group-flush"></ul>
                                </div>
                            </div>

                            <!-- Card 2: Customer Type -->
                            <div class="pos-card mt-3">
                                <div class="pos-card-header">
                                    <div class="pos-card-title"><span class="pos-mini-icon"><i class="fas fa-user-tag"></i></span> Customer Type</div>
                                </div>
                                <div class="pos-card-body">
                                    <div id="pos-customer-type-wrapper" class="mb-2">
                                        <label for="pos-customer-type" class="form-label small fw-bold text-muted mb-1">Select Customer Category</label>
                                        <select id="pos-customer-type" class="form-select form-select-sm">
                                            <option value="Cash" selected>Cash</option>
                                            <option value="Staff">Staff</option>
                                            <option value="Student">Student</option>
                                            <option value="Corporate">Corporate</option>
                                        </select>
                                    </div>

                                    <!-- Staff Member Selection -->
                                    <div id="pos-staff-wrapper" class="mb-2" style="display: none;">
                                        <label for="pos-staff-select" class="form-label small fw-bold text-muted mb-1">Select Staff Member</label>
                                        <select id="pos-staff-select" class="form-select form-select-sm">
                                            <option value="">-- Choose Staff --</option>
                                            <?php foreach ($staff_members as $sm): ?>
                                                <?php
                                                $nameParts = array_filter([$sm['title'] ?? '', $sm['full_name'] ?? '']);
                                                $displayName = implode(' ', $nameParts);
                                                if (empty($displayName)) {
                                                    $displayName = $sm['username'];
                                                } else {
                                                    $displayName .= ' (' . $sm['username'] . ')';
                                                }
                                                ?>
                                                <option value="<?= htmlspecialchars($displayName) ?>" data-username="<?= htmlspecialchars($sm['username']) ?>">
                                                    <?= htmlspecialchars($displayName) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Student Selection -->
                                    <div id="pos-student-wrapper" class="mb-2" style="display: none;">
                                        <label for="pos-student-select" class="form-label small fw-bold text-muted mb-1">Select Active Student</label>
                                        <select id="pos-student-select" class="form-select form-select-sm">
                                            <option value="">-- Choose Active Student --</option>
                                            <?php foreach ($active_students as $st): ?>
                                                <?php
                                                $regId = !empty($st['student_registration_id']) ? $st['student_registration_id'] : (!empty($st['new_student_registration_id']) ? $st['new_student_registration_id'] : 'Code: ' . $st['student_code']);
                                                $stdName = trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? ''));
                                                if (empty($stdName)) {
                                                    $stdName = trim($st['certificate_name'] ?? '') ?: trim($st['preferred_name'] ?? '');
                                                }
                                                $displayStudent = $regId . (!empty($stdName) ? ' - ' . $stdName : '');
                                                ?>
                                                <option value="<?= htmlspecialchars($displayStudent) ?>" data-regid="<?= htmlspecialchars($regId) ?>">
                                                    <?= htmlspecialchars($displayStudent) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Corporate Input -->
                                    <div id="pos-corporate-wrapper" class="mb-2" style="display: none;">
                                        <label for="pos-corporate-input" class="form-label small fw-bold text-muted mb-1">Corporate Details</label>
                                        <input type="text" id="pos-corporate-input" class="form-control form-control-sm" placeholder="Enter Corporate Name / Details">
                                    </div>
                                </div>
                            </div>

                            <!-- Card 3: Payment Summary -->
                            <div class="pos-card mt-3">
                                <div class="pos-card-header">
                                    <div class="pos-card-title"><span class="pos-mini-icon"><i class="fas fa-receipt"></i></span> Payment Summary</div>
                                </div>
                                <div class="pos-card-body">
                                    <div id="summary-items" class="mb-2"></div>
                                    <div class="d-flex justify-content-between">
                                        <div>Subtotal</div>
                                        <div id="subtotal" class="fw-semibold">LKR 0.00</div>
                                    </div>
                                    <div class="d-flex justify-content-between d-none" id="discount-row">
                                        <div id="discount-label" class="text-success">Discount</div>
                                        <div id="discount" class="fw-semibold text-success">- LKR 0.00</div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div>Tax</div>
                                        <div id="tax" class="fw-semibold">LKR 0.00</div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-bold">Grand Total</div>
                                        <div id="grand-total" class="fw-bold pos-price-tag">LKR 0.00</div>
                                    </div>
                                    <div id="complete-purchase-btn">
                                        <button id="checkout-btn" class="pos-btn pos-btn-navy w-100 mt-3 justify-content-center" style="padding:0.7rem;">
                                            <i class="fas fa-check-circle"></i>&nbsp;Complete Purchase
                                        </button>
                                    </div>
                                    <div id="checkout-status" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const cartBadge = document.getElementById("cart-badge");
            const addToCartBtns = document.querySelectorAll(".add-to-cart-btn");
            let cartCount = 0;
            addToCartBtns.forEach(btn => {
                btn.addEventListener("click", () => {
                    cartCount++;
                    cartBadge.textContent = cartCount;
                    cartBadge.classList.remove("d-none");
                    cartBadge.classList.add("pulse");
                    setTimeout(() => cartBadge.classList.remove("pulse"), 600);
                });
            });
            // Customer Type select2 + show/hide logic now lives in pos-assets/js/cart.js
            // (single source of truth - avoids two competing handlers on the same select).
        });
    </script>
    <!-- cart.js talks to pos_checkout.php and pos_receipt.php -->
    <script src="pos-assets/js/cart.js"></script>

    </body>

    </html>