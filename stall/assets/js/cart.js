$(function () {
  var cart = {};
  var catalog = {};
  var taxRate = 0.0;

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
            "warning"
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
        free: false,
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
    if (item.free) return;
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
    if (!item || item.free) return;
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

  // Promotion logic always calculated realtime
  function applyPromotions() {
    // var tumbler = findPaidByContains("Vacuum Tumbler");
    var vacuum = findPaidByContains("vacuum");
    var teddy = findPaidByContains("teddy");
    var penInfo = findCatalogByContains("pen");
    var paidPen = findPaidByContains("pen");
    var freeId = penInfo ? penInfo.id : null;
    var freeQty = 0;

    // Promotion: 1 free pen per tumbler
    // if (tumbler && tumbler.qty >= 1) freeQty += tumbler.qty;
    // Promotion: 1 free pen per vacuum
    if (vacuum && vacuum.qty >= 1) freeQty += vacuum.qty;

    // Promotion: 1 free pen per teddy (from 2)
    if (teddy && teddy.qty >= 2) freeQty += teddy.qty;

    var paidPenQty = paidPen ? paidPen.qty : 0;

    if (freeId && penInfo) {
      var available = penInfo.stock - paidPenQty;
      if (freeQty > available) {
        freeQty = available;
      }

      var freeKey = "__free_" + freeId;
      var freeItem = cart[freeKey];

      if (freeQty > 0) {
        if (!freeItem) {
          cart[freeKey] = {
            key: freeKey,
            id: freeId,
            name: "Wooden Pen (Free)",
            price: 0,
            stock: penInfo.stock,
            qty: freeQty,
            image: penInfo.image,
            free: true,
          };
        } else {
          freeItem.qty = freeQty;
          freeItem.stock = penInfo.stock; // Keep synced
        }
      } else {
        if (freeItem) delete cart[freeKey];
      }
    }
  }

  // Find paid item in cart (non-free) by term
  function findPaidByContains(term) {
    term = String(term).toLowerCase();
    var found = null;
    Object.values(cart).forEach(function (i) {
      if (!i.free && String(i.name).toLowerCase().includes(term)) {
        found = i;
      }
    });
    return found;
  }

  // Find catalog item by term
  function findCatalogByContains(term) {
    syncCatalogFromGrid(); // realtime
    term = String(term).toLowerCase();
    var found = null;
    Object.keys(catalog).forEach(function (k) {
      if (String(k).toLowerCase().includes(term)) {
        found = catalog[k];
      }
    });
    return found;
  }

  // Main render triggers everything (realtime)
  function render() {
    applyPromotions();
    renderCart();
    renderTotals();
    renderSummary();
  }

  // Render cart UI (keeps values realtime with catalog changes)
  function renderCart() {
    var $list = $("#cart-list");
    $list.empty();

    Object.values(cart).forEach(function (item) {
      var li = $(
        '<li class="list-group-item d-flex align-items-center justify-content-between"></li>'
      );

      var left = $('<div class="d-flex align-items-center gap-2"></div>');
      left.append(
        $("<img>")
          .attr("src", item.image)
          .addClass("cart-thumb")
          .css({ width: "50px", height: "50px", objectFit: "cover" })
      );

      var meta = $('<div class="d-flex flex-column"></div>');
      meta.append($('<div class="fw-semibold"></div>').text(item.name));
      meta.append(
        $('<div class="text-muted small"></div>').text(
          "LKR " + format(item.price)
        )
      );
      left.append(meta);

      var right = $('<div class="d-flex align-items-center gap-2"></div>');

      var qtyGroup = $(
        '<div class="input-group input-group-sm" style="width:130px;"></div>'
      );
      var minusBtn = $(
        '<button type="button" class="btn btn-outline-secondary">-</button>'
      );
      var qtyInput = $('<input type="number" class="form-control text-center">')
        .attr("min", 1)
        .attr("max", item.stock)
        .val(item.qty);
      var plusBtn = $(
        '<button type="button" class="btn btn-outline-secondary">+</button>'
      );

      if (item.free) {
        minusBtn.prop("disabled", true);
        plusBtn.prop("disabled", true);
        qtyInput.prop("disabled", true);
      } else {
        minusBtn.off("click").on("click", function () {
          adjustQuantity(item.key, -1);
        });
        plusBtn.off("click").on("click", function () {
          adjustQuantity(item.key, 1);
        });
        qtyInput.off("input change").on("input change", function () {
          updateQuantity(item.key, $(this).val());
        });
      }

      qtyGroup.append(minusBtn, qtyInput, plusBtn);

      var subtotal = $('<div class="fw-semibold"></div>').text(
        "LKR " + format(item.price * item.qty)
      );

      var removeBtn = $(
        '<button class="btn btn-outline-danger btn-sm touch-target">Remove</button>'
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
        '<li class="list-group-item text-center text-muted">No items in cart</li>'
      );
    }
  }

  // Realtime cart summary
  function renderSummary() {
    var $wrap = $("#summary-items");
    $wrap.empty();

    Object.values(cart).forEach(function (item) {
      var row = $('<div class="d-flex justify-content-between"></div>');
      row.append($("<div></div>").text(item.name + " × " + item.qty));
      row.append($("<div></div>").text("LKR " + format(item.price * item.qty)));
      $wrap.append(row);
    });
  }

  // Realtime totals
  function renderTotals() {
    var subtotal = Object.values(cart).reduce(function (acc, it) {
      return acc + it.price * it.qty;
    }, 0);
    var tax = subtotal * taxRate;
    var grand = subtotal + tax;

    $("#subtotal").text("LKR " + format(subtotal));
    $("#tax").text("LKR " + format(tax));
    $("#grand-total").text("LKR " + format(grand));
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

  // Just paid items, for checkout
  function getPayload() {
    var paidItems = Object.values(cart).filter(function (i) {
      return !i.free;
    });

    var subtotal = paidItems.reduce(function (acc, i) {
      return acc + i.price * i.qty;
    }, 0);

    var tax = subtotal * taxRate;
    var grand = subtotal + tax;

    return {
      items: paidItems.map(function (i) {
        return {
          id: i.id,
          name: i.name,
          price: i.price,
          qty: i.qty,
        };
      }),
      totals: {
        subtotal: parseFloat(subtotal.toFixed(2)),
        tax: parseFloat(tax.toFixed(2)),
        grand_total: parseFloat(grand.toFixed(2)),
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

  // Replace the checkout button click handler in cart.js with this improved version

  $("#checkout-btn").on("click", function () {
    syncCatalogFromGrid();

    var paidItemsCount = Object.values(cart).filter(function (i) {
      return !i.free;
    }).length;

    if (paidItemsCount === 0) {
      showTempMessage("Please add items to cart before checkout", "danger");
      $("#checkout-status")
        .removeClass()
        .addClass("text-danger")
        .text("Cart is empty");
      return;
    }

    $("#cart-alert").text("").removeClass();
    $("#checkout-status").text("").removeClass();
    $("#checkout-status").addClass("text-muted").text("Processing purchase...");
    $("#checkout-btn").prop("disabled", true).text("Processing...");

    var payload = getPayload();
    var isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    $.ajax({
      url: "checkout.php",
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
            "success"
          );

          var receiptUrl =
            "receipt.php?order_id=" + encodeURIComponent(res.order_id);

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
                  window.location.href = "index.php";
                }, 3000);
              } else {
                // Pop-up blocked
                alert(
                  "Please allow pop-ups to view and print the receipt.\n\nYou can view your order history from the menu."
                );
                window.location.href = "index.php";
              }
            } else {
              // Desktop: Open receipt in popup window and auto-print
              var receiptWindow = window.open(
                receiptUrl,
                "receipt",
                "width=400,height=700,scrollbars=yes,resizable=yes"
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
                  window.location.href = "index.php";
                }, 2000);
              } else {
                alert("Please allow pop-ups to view and print the receipt.");
                window.location.href = "index.php";
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
        var errorMsg = "Purchase failed - ";

        if (status === "timeout") {
          errorMsg += "request timed out";
        } else if (xhr.status === 400) {
          errorMsg += "invalid request";
        } else if (xhr.status === 500) {
          errorMsg += "server error";
        } else {
          errorMsg += "please try again";
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
