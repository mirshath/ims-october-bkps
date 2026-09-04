<?php
require_once __DIR__ . '/includes/auth.php';
start_secure_session();
$__u = require_user();
require __DIR__ . '/templates/header.php';
require_once __DIR__ . '/includes/products.php';

$products = fetch_products();
if (!is_array($products) || count($products) === 0) {
  $products = [
    ['id' => 'pen', 'name' => 'Pen', 'price' => 1.50, 'stock' => 20, 'image' => 'https://via.placeholder.com/200?text=Pen'],
    ['id' => 'tumbler', 'name' => 'Vacuum Tumbler', 'price' => 6.99, 'stock' => 10, 'image' => 'https://via.placeholder.com/200?text=Vacuum+Tumbler'],
    ['id' => 'teddy', 'name' => 'Teddy', 'price' => 12.00, 'stock' => 8, 'image' => 'https://via.placeholder.com/200?text=Teddy'],
    ['id' => 'notebook', 'name' => 'Notebook', 'price' => 3.25, 'stock' => 25, 'image' => 'https://via.placeholder.com/200?text=Notebook'],
    ['id' => 'mug', 'name' => 'Mug', 'price' => 8.50, 'stock' => 12, 'image' => 'https://via.placeholder.com/200?text=Mug'],
  ];
}
?>

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

  /* Mobile: Horizontal row layout */
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

    .card-body {
      padding: 0 !important;
      flex: 1;
      display: flex;
      flex-direction: column;
      /* gap: 0.2rem; */
      min-width: 0;
    }

    .card-body>div:first-child {
      font-size: 14px !important;
      font-weight: 600 !important;
      margin: 0 !important;
      line-height: 1.2 !important;
    }

    .card-body .text-muted {
      font-size: 10px !important;
      line-height: 1.2 !important;
    }

    .card-body .price {
      font-size: 15px !important;
      font-weight: 700 !important;
      margin: 0.15rem 0 !important;
    }

    .card-body .mt-auto {
      margin-top: 0 !important;
      display: flex !important;
      align-items: center;
      justify-content: flex-start;
    }


    /* Checkout button mobile - Fixed at bottom */
    #checkout-btn {
      position: relative !important;
      width: 100% !important;
      margin-top: 1.5rem !important;
      margin-bottom: 0 !important;
      /* On mobile, ensure not fixed but prominent */
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
    .btn-secondary {
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

  /* Desktop: Grid layout */
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
      height: 200px !important;
      object-fit: cover;
    }
  }

  /* Mobile Cart Styling */
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

    #cart-list .d-flex.align-items-center.gap-2 {
      flex: 1 !important;
      min-width: 0;
    }

    #cart-list .d-flex.flex-column {
      flex: 1;
      min-width: 0;
    }

    #cart-list .fw-semibold {
      font-size: 14px !important;
      line-height: 1.3;
      margin-bottom: 0.2rem;
    }

    #cart-list .text-muted.small {
      font-size: 12px !important;
    }

    #cart-list .d-flex.align-items-center.gap-2:last-child {
      display: flex !important;
      flex-direction: column !important;
      align-items: flex-end !important;
      gap: 0.5rem !important;
      flex-shrink: 0;
    }

    #cart-list .input-group {
      width: 110px !important;
      order: 2;
    }

    #cart-list .fw-semibold:not(.text-muted) {
      order: 1;
      font-size: 14px !important;
      margin-bottom: 0.3rem;
    }

    #cart-list .btn-outline-danger {
      order: 3;
      padding: 0.3rem 0.6rem !important;
      font-size: 12px !important;
    }

    /* Summary items mobile */
    #summary-items>div {
      font-size: 13px;
      margin-bottom: 0.4rem;
    }

    .card-header {
      padding: 0.75rem !important;
      font-size: 15px !important;
    }

    .card-body {
      padding: 0.75rem !important;
    }
  }
</style>

<div class="container">
  <!-- ✅ Mobile Tabs -->
  <div class="d-lg-none mb-3 position-relative">
    <ul class="nav nav-tabs" id="mobileTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products-section" type="button" role="tab">
          🛍️ Products
        </button>
      </li>
      <li class="nav-item position-relative" role="presentation">
        <button class="nav-link" id="cart-tab" data-bs-toggle="tab" data-bs-target="#cart-section" type="button" role="tab">
          🛒 Cart & Summary
          <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
            0
          </span>
        </button>
      </li>
    </ul>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
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
    });
  </script>

  <!-- ✅ Main Layout -->
  <div class="tab-content">
    <!-- 🛍️ Products Section -->

    <!-- 🛍️ Products Section  -->
    <div class="tab-pane fade show active" id="products-section" role="tabpanel">
      <div class="row g-3" id="product-grid">
        <?php foreach ($products as $p): ?>
          <?php if ((int)$p['stock'] <= 0) continue; ?>
          <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm product-card position-relative">
              <img src="<?= htmlspecialchars($p['image']) ?>"
                alt="<?= htmlspecialchars($p['name']) ?>"
                class="card-img-top product-img"
                data-id="<?= htmlspecialchars($p['id']) ?>"
                data-name="<?= htmlspecialchars($p['name']) ?>"
                data-price="<?= number_format($p['price'], 2, '.', '') ?>"
                data-stock="<?= (int)$p['stock'] ?>"
                data-image="<?= htmlspecialchars($p['image']) ?>">

              <div class="card-body d-flex flex-column">
                <div class="fw-bold" style="font-size:16px;"><?= htmlspecialchars($p['name']) ?></div>
                <div class="text-muted small d-none d-lg-block">Item Code: <?= htmlspecialchars($p['id']) ?></div>
                <div class="mt-1 price text-success">LKR <?= number_format($p['price'], 2) ?></div>
                <div class="text-muted small">Stock: <?= (int)$p['stock'] ?></div>
                <div class="mt-auto d-none d-lg-block">
                  <button class="btn btn-sm btn-primary w-100 add-to-cart-btn"
                    data-id="<?= htmlspecialchars($p['id']) ?>"
                    data-name="<?= htmlspecialchars($p['name']) ?>"
                    data-price="<?= number_format($p['price'], 2, '.', '') ?>"
                    data-stock="<?= (int)$p['stock'] ?>"
                    data-image="<?= htmlspecialchars($p['image']) ?>">
                    Add to Cart
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 🛒 Cart Section -->
    <div class="tab-pane fade" id="cart-section" role="tabpanel">
      <div class="card mb-3 mt-3">
        <div class="card-header bg-white">
          <div class="d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Cart</div>
            <div id="cart-alert" class="text-danger small"></div>
          </div>
        </div>
        <div class="card-body p-2">
          <ul id="cart-list" class="list-group list-group-flush"></ul>
        </div>
      </div>
      <div class="card mb-3">
        <div class="card-header bg-white fw-semibold">Payment Summary</div>
        <div class="card-body">
          <div id="summary-items" class="mb-2"></div>
          <div class="d-flex justify-content-between">
            <div>Subtotal</div>
            <div id="subtotal" class="fw-semibold">LKR 0.00</div>
          </div>
          <div class="d-flex justify-content-between">
            <div>Tax</div>
            <div id="tax" class="fw-semibold">LKR 0.00</div>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <div class="fw-bold">Grand Total</div>
            <div id="grand-total" class="fw-bold text-success">LKR 0.00</div>
          </div>

          <!-- <button id="checkout-btn" class="btn btn-secondary w-100 mt-3">Complete Purchase</button> -->

          <div id="complete-purchase-btn">
            <button id="checkout-btn" class="btn btn-secondary w-100 mt-3">Complete Purchase</button> <br>
          </div>


          <div id="checkout-status" class="mt-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/templates/footer.php'; ?>