$(function () {
  var cart = {};
  var catalog = {};
  var taxRate = 0.0;

  function getTieredDiscount(subtotal) {
    // Total quantity across ALL products in the cart (not distinct product count)
    var totalQty = Object.values(cart).reduce(function (acc, it) {
      return acc + it.qty;
    }, 0);
    var rate = 0.0;
    var tierLabel = "";
    if (totalQty >= 3) {
      rate = 0.15;
      tierLabel = "15% (" + totalQty + " items)";
    } else if (totalQty === 2) {
      rate = 0.1;
      tierLabel = "10% (" + totalQty + " items)";
    } else if (totalQty === 1) {
      rate = 0.0;
      tierLabel = "0% (" + totalQty + " item)";
    } else {
      tierLabel = "";
    }
    var amount = subtotal * rate;
    return { rate: rate, amount: amount, label: tierLabel };
  }

  function getTotals() {
    var customerType = $("#pos-customer-type").val();
    if (customerType === "Corporate") {
      return {
        subtotal: 0.0,
        discount: 0.0,
        discountRate: 0.0,
        discountLabel: "",
        afterDiscount: 0.0,
        tax: 0.0,
        grand: 0.0,
      };
    }
    var subtotal = Object.values(cart).reduce(function (acc, it) {
      return acc + it.price * it.qty;
    }, 0);
    var disc = getTieredDiscount(subtotal);
    var afterDiscount = subtotal - disc.amount;
    if (afterDiscount < 0) afterDiscount = 0;
    var tax = afterDiscount * taxRate;
    var grand = afterDiscount + tax;
    return {
      subtotal: subtotal,
      discount: disc.amount,
      discountRate: disc.rate,
      discountLabel: disc.label,
      afterDiscount: afterDiscount,
      tax: tax,
      grand: grand,
    };
  }

  // Build catalog dynamically and support realtime updates
  function syncCatalogFromGrid() {
    catalog = {};
    $("#product-grid .product-img").each(function () {
      var $e = $(this);
      catalog[$e.data("name")] = {
        id: $e.data("id"),
        name: $e.data("name"),
        price: parseFloat($e.data("price")),
        stock: parseInt($e.data("stock"), 10),
        image: $e.data("image"),
      };
    });
  }

  // Live stock sync when called (for example, you could call this via Pusher/socket)
  function updateCatalogAndStock() {
    syncCatalogFromGrid();

    // Sync cart items with updated stock in catalog
    Object.values(cart).forEach(function (item) {
      var catItem = catalog[item.name];
      if (catItem) {
        item.stock = catItem.stock;
        item.price = catItem.price;
        item.image = catItem.image;
        // If qty is now over stock, reduce & alert
        if (item.qty > item.stock) {
          item.qty = item.stock;
          showTempMessage(
            "Stock changed, adjusted quantity for: " + item.name,
            "warning",
          );
        }
      }
    });
    render();
  }

  syncCatalogFromGrid();

  // Add item to cart, then sync with catalog (always latest)
  function addItem(p) {
    syncCatalogFromGrid();
    var id = p.id;
    var currentCat = catalog[p.name];
    if (!currentCat) {
      showTempMessage("Product not available", "danger");
      return;
    }
    if (!cart[id]) {
      cart[id] = {
        key: id,
        id: id,
        name: p.name,
        price: parseFloat(currentCat.price),
        stock: parseInt(currentCat.stock, 10),
        qty: 1,
        image: currentCat.image,
      };
    } else {
      if (cart[id].qty < cart[id].stock) {
        cart[id].qty += 1;
      } else {
        showTempMessage("Maximum stock reached for " + p.name, "danger");
      }
    }
    render();
    sendRealtimeCartUpdate("add", cart[id]);
  }

  // Remove item from cart, then send realtime
  function removeItem(key) {
    var removedItem = cart[key];
    delete cart[key];
    render();
    sendRealtimeCartUpdate("remove", removedItem);
  }

  // Update item quantity realtime
  function updateQuantity(key, qty) {
    var item = cart[key];
    if (!item) return;
    syncCatalogFromGrid();
    var catItem = catalog[item.name];
    if (catItem) {
      item.stock = catItem.stock;
      item.price = catItem.price;
    }
    qty = parseInt(qty, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    if (qty > item.stock) {
      qty = item.stock;
      showTempMessage("Adjusted to available stock for " + item.name, "danger");
    }
    item.qty = qty;
    render();
    sendRealtimeCartUpdate("update", item);
  }

  // Adjust quantity realtime
  function adjustQuantity(key, delta) {
    var item = cart[key];
    if (!item) return;
    syncCatalogFromGrid();
    var catItem = catalog[item.name];
    if (catItem) {
      item.stock = catItem.stock;
      item.price = catItem.price;
    }
    var next = item.qty + delta;
    if (next < 1) next = 1;
    if (next > item.stock) {
      next = item.stock;
      showTempMessage("Adjusted to available stock for " + item.name, "danger");
    }
    if (next !== item.qty) {
      item.qty = next;
      render();
      sendRealtimeCartUpdate("update", item);
    }
  }

  function render() {
    renderCart();
    renderTotals();
    renderSummary();
  }

  // Render cart UI (keeps values realtime with catalog changes)
  function renderCart() {
    var $list = $("#cart-list");
    $list.empty();
    var isCorporate = $("#pos-customer-type").val() === "Corporate";

    Object.values(cart).forEach(function (item) {
      var itemPrice = isCorporate ? 0 : item.price;
      var li = $(
        '<li class="list-group-item d-flex align-items-center justify-content-between"></li>',
      );

      var left = $('<div class="d-flex align-items-center gap-2"></div>');
      left.append(
        $("<img>")
          .attr("src", item.image)
          .addClass("cart-thumb")
          .css({ width: "50px", height: "50px", objectFit: "cover" }),
      );

      var meta = $('<div class="d-flex flex-column"></div>');
      meta.append($('<div class="fw-semibold"></div>').text(item.name));
      meta.append(
        $('<div class="text-muted small"></div>').text(
          "LKR " + format(itemPrice),
        ),
      );
      left.append(meta);

      var right = $('<div class="d-flex align-items-center gap-2"></div>');

      var qtyGroup = $(
        '<div class="input-group input-group-sm" style="width:130px;"></div>',
      );
      var minusBtn = $(
        '<button type="button" class="btn btn-outline-secondary">-</button>',
      );
      var qtyInput = $('<input type="number" class="form-control text-center">')
        .attr("min", 1)
        .attr("max", item.stock)
        .val(item.qty);
      var plusBtn = $(
        '<button type="button" class="btn btn-outline-secondary">+</button>',
      );

      minusBtn.off("click").on("click", function () {
        adjustQuantity(item.key, -1);
      });
      plusBtn.off("click").on("click", function () {
        adjustQuantity(item.key, 1);
      });
      qtyInput.off("input change").on("input change", function () {
        updateQuantity(item.key, $(this).val());
      });

      qtyGroup.append(minusBtn, qtyInput, plusBtn);

      var subtotal = $('<div class="fw-semibold"></div>').text(
        "LKR " + format(itemPrice * item.qty),
      );

      var removeBtn = $(
        '<button class="btn btn-outline-danger btn-sm touch-target">Remove</button>',
      )
        .off("click")
        .on("click", function () {
          removeItem(item.key);
        });

      right.append(qtyGroup).append(subtotal).append(removeBtn);
      li.append(left).append(right);
      $list.append(li.hide().fadeIn(75));
    });

    if ($list.children().length === 0) {
      $list.append(
        '<li class="list-group-item text-center text-muted">No items in cart</li>',
      );
    }
  }

  // Realtime cart summary
  function renderSummary() {
    var $wrap = $("#summary-items");
    $wrap.empty();
    var isCorporate = $("#pos-customer-type").val() === "Corporate";

    Object.values(cart).forEach(function (item) {
      var itemPrice = isCorporate ? 0 : item.price;
      var row = $('<div class="d-flex justify-content-between"></div>');
      row.append($("<div></div>").text(item.name + " × " + item.qty));
      row.append($("<div></div>").text("LKR " + format(itemPrice * item.qty)));
      $wrap.append(row);
    });
  }

  function renderTotals() {
    var t = getTotals();
    $("#subtotal").text("LKR " + format(t.subtotal));

    var $discountRow = $("#discount-row");
    var $discountAmt = $("#discount");
    var $discountLabel = $("#discount-label");
    if (t.discountRate > 0) {
      $discountRow.removeClass("d-none");
      $discountLabel.text(
        "Discount " + (t.discountRate * 100).toFixed(0) + "%",
      );
      $discountAmt.text("- LKR " + format(t.discount));
    } else {
      $discountRow.addClass("d-none");
      $discountLabel.text("Discount");
      $discountAmt.text("- LKR 0.00");
    }

    $("#tax").text("LKR " + format(t.tax));
    $("#grand-total").text("LKR " + format(t.grand));
  }

  // Format currency
  function format(n) {
    return parseFloat(n).toFixed(2);
  }

  // Show temp msg (realtime clearing any old msg)
  function showTempMessage(msg, type) {
    var $alert = $("#cart-alert");
    $alert.removeClass("text-danger text-success text-warning text-info");

    if (type === "success") {
      $alert.addClass("text-success").text("✓ " + msg);
    } else if (type === "danger") {
      $alert.addClass("text-danger").text(msg);
    } else if (type === "warning") {
      $alert.addClass("text-warning").text("⚠️ " + msg);
    } else {
      $alert.addClass("text-info").text("ℹ️ " + msg);
    }

    setTimeout(function () {
      $alert.text("").removeClass();
    }, 3500);
  }

  // Customer Type / Staff / Student: Select2 init + show/hide (single source of truth,
  // so there's no second competing handler fighting over the same <select> elements)
  var $custType = $("#pos-customer-type");
  var $staffWrapper = $("#pos-staff-wrapper");
  var $studentWrapper = $("#pos-student-wrapper");
  var $corporateWrapper = $("#pos-corporate-wrapper");
  var $staffSelect = $("#pos-staff-select");
  var $studentSelect = $("#pos-student-select");
  var $corporateInput = $("#pos-corporate-input");

  if ($.fn.select2) {
    $custType.select2({ width: "100%", minimumResultsForSearch: Infinity });
    $staffSelect.select2({
      width: "100%",
      placeholder: "-- Choose Staff --",
      allowClear: false,
    });
    $studentSelect.select2({
      width: "100%",
      placeholder: "-- Choose Active Student --",
      allowClear: false,
    });
  }

  function syncCustomerTypeUI() {
    var type = $custType.val();
    if (type === "Staff") {
      $staffWrapper.show();
      $studentWrapper.hide();
      $corporateWrapper.hide();
    } else if (type === "Student") {
      $studentWrapper.show();
      $staffWrapper.hide();
      $corporateWrapper.hide();
    } else if (type === "Corporate") {
      $corporateWrapper.show();
      $staffWrapper.hide();
      $studentWrapper.hide();
    } else {
      $staffWrapper.hide();
      $studentWrapper.hide();
      $corporateWrapper.hide();
      // Reset any leftover selection so a stale value can never be sent for Cash
      $staffSelect.val("").trigger("change.select2");
      $studentSelect.val("").trigger("change.select2");
      $corporateInput.val("");
    }
    render();
  }

  // "change" fires for both native selects and Select2 (Select2 always keeps the
  // underlying <select> in sync and re-triggers a native "change" event on it).
  $(document).on("change", "#pos-customer-type", syncCustomerTypeUI);

  syncCustomerTypeUI();

  function getPayload() {
    var items = Object.values(cart);
    var t = getTotals();

    var customerType = $("#pos-customer-type").val() || "Cash";
    var customerName = "";
    if (customerType === "Staff") {
      customerName = $("#pos-staff-select").val() || "";
    } else if (customerType === "Student") {
      customerName = $("#pos-student-select").val() || "";
    } else if (customerType === "Corporate") {
      customerName = $.trim($("#pos-corporate-input").val()) || "";
    }

    var isCorporate = customerType === "Corporate";
    return {
      customer_type: customerType,
      customer_name: customerName,
      items: items.map(function (i) {
        return {
          id: i.id,
          name: i.name,
          price: isCorporate ? 0 : i.price,
          qty: i.qty,
        };
      }),
      totals: {
        subtotal: parseFloat(t.subtotal.toFixed(2)),
        discount: parseFloat(t.discount.toFixed(2)),
        discount_rate: t.discountRate,
        tax: parseFloat(t.tax.toFixed(2)),
        grand_total: parseFloat(t.grand.toFixed(2)),
      },
    };
  }

  // Event handler: add to cart from product image
  $("#product-grid").on("click", ".product-img", function () {
    syncCatalogFromGrid();
    var $el = $(this);
    addItem({
      id: $el.data("id"),
      name: $el.data("name"),
      price: $el.data("price"),
      stock: $el.data("stock"),
      image: $el.data("image"),
    });
  });

  // Event handler: add to cart from button
  $("#product-grid").on("click", ".add-to-cart-btn", function () {
    syncCatalogFromGrid();
    var $el = $(this);
    addItem({
      id: $el.data("id"),
      name: $el.data("name"),
      price: $el.data("price"),
      stock: $el.data("stock"),
      image: $el.data("image"),
    });
  });

  $("#checkout-btn").on("click", function () {
    syncCatalogFromGrid();

    if (Object.keys(cart).length === 0) {
      showTempMessage("Please add items to cart before checkout", "danger");
      $("#checkout-status")
        .removeClass()
        .addClass("text-danger")
        .text("Cart is empty");
      return;
    }

    var customerType = $("#pos-customer-type").val() || "Cash";
    if (customerType === "Staff" && !$("#pos-staff-select").val()) {
      showTempMessage("Please select a staff member", "danger");
      $("#checkout-status")
        .removeClass()
        .addClass("text-danger")
        .text("❌ Please select a staff member");
      return;
    }
    if (customerType === "Student" && !$("#pos-student-select").val()) {
      showTempMessage("Please select a student", "danger");
      $("#checkout-status")
        .removeClass()
        .addClass("text-danger")
        .text("❌ Please select a student");
      return;
    }
    if (customerType === "Corporate" && !$.trim($("#pos-corporate-input").val())) {
      showTempMessage("Please enter corporate details", "danger");
      $("#checkout-status")
        .removeClass()
        .addClass("text-danger")
        .text("❌ Please enter corporate details");
      return;
    }

    $("#cart-alert").text("").removeClass();
    $("#checkout-status").text("").removeClass();
    $("#checkout-status").addClass("text-muted").text("Processing purchase...");
    $("#checkout-btn").prop("disabled", true).text("Processing...");

    var payload = getPayload();
    var isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    $.ajax({
      url: "pos_checkout.php",
      method: "POST",
      data: JSON.stringify(payload),
      contentType: "application/json",
      dataType: "json",
      timeout: 12000,
    })
      .done(function (res) {
        if (res && res.success) {
          $("#checkout-status")
            .removeClass()
            .addClass("text-success")
            .text("✅ Purchase completed successfully!");

          showTempMessage(
            "Order #" + res.order_id + " placed successfully!",
            "success",
          );

          var receiptUrl =
            "pos_receipt.php?order_id=" + encodeURIComponent(res.order_id);

          // Clear cart immediately after success
          cart = {};
          render();
          sendRealtimeCartUpdate("clear", null);

          // Directly open receipt for preview/print (no confirmation)
          setTimeout(function () {
            if (isMobile) {
              // Mobile: Open receipt in new tab and trigger print if possible
              var receiptWindow = window.open(receiptUrl, "_blank");

              if (receiptWindow) {
                setTimeout(function () {
                  try {
                    receiptWindow.focus();
                    receiptWindow.print();
                  } catch (err) {
                    console.log("Print dialog error:", err);
                  }
                }, 1500);

                // Redirect main page after a short delay
                setTimeout(function () {
                  window.location.href = "pos_store.php";
                }, 3000);
              } else {
                // Pop-up blocked
                alert(
                  "Please allow pop-ups to view and print the receipt.\n\nYou can view your order history from the menu.",
                );
                window.location.href = "pos_store.php";
              }
            } else {
              // Desktop: Open receipt in popup window and auto-print
              var receiptWindow = window.open(
                receiptUrl,
                "receipt",
                "width=400,height=700,scrollbars=yes,resizable=yes",
              );

              if (receiptWindow) {
                receiptWindow.onload = function () {
                  receiptWindow.focus();

                  // Trigger print dialog after window loads
                  setTimeout(function () {
                    receiptWindow.print();
                  }, 500);
                };

                // Redirect after print or timeout
                setTimeout(function () {
                  window.location.href = "pos_store.php";
                }, 2000);
              } else {
                alert("Please allow pop-ups to view and print the receipt.");
                window.location.href = "pos_store.php";
              }
            }

            $("#checkout-btn")
              .prop("disabled", false)
              .text("Complete Purchase");
          }, 500); // Small delay to show success message first
        } else {
          var errorMsg =
            res && res.message ? res.message : "Purchase failed - server error";
          $("#checkout-status")
            .removeClass()
            .addClass("text-danger")
            .text("❌ " + errorMsg);

          showTempMessage(errorMsg, "danger");
          $("#checkout-btn").prop("disabled", false).text("Complete Purchase");
        }
      })
      .fail(function (xhr, status, error) {
        // Log the raw response so the real server-side cause (e.g. a stray
        // PHP warning breaking the JSON, an auth issue, etc.) is visible in
        // devtools even when the UI can only show a generic message.
        console.error(
          "Checkout request failed:",
          status,
          error,
          "HTTP " + xhr.status,
          "raw response:",
          xhr.responseText,
        );

        var errorMsg = "";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMsg = xhr.responseJSON.error;
        } else if (status === "timeout") {
          errorMsg = "Request timed out";
        } else if (xhr.status === 400) {
          errorMsg = "Invalid request payload or stock issue";
        } else if (xhr.status === 500) {
          errorMsg = "Server error during checkout";
        } else {
          errorMsg = "Purchase failed - please try again";
        }

        $("#checkout-status")
          .removeClass()
          .addClass("text-danger")
          .text("❌ " + errorMsg);

        showTempMessage(errorMsg, "danger");
        $("#checkout-btn").prop("disabled", false).text("Complete Purchase");
      });
  });
  // ------------------

  // ------------------
  // ------------------
  // --- Realtime sync stub hooks ---
  // In real implementation, hook with sockets (Pusher/WebSocket/SignalR/Firebase, etc.)
  function sendRealtimeCartUpdate(action, item) {
    // This is where you would push cart changes out to collaborators, e.g.:
    // socket.emit("cart_update", { action: action, item: item, cart: cart });
    // Or let server push update to all clients (including this one)
    // For now noop.
  }
  // Optionally subscribe to server/pusher and call `updateCatalogAndStock()` + render() on update.

  // On page focus or visibility change, reload all catalog realtime (in case price/stock changed externally)
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) {
      updateCatalogAndStock();
    }
  });

  // Periodically poll for realtime stock update (if no push)
  setInterval(updateCatalogAndStock, 30000);

  // Initial render
  render();
});
