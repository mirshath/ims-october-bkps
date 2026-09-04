<?php require_once __DIR__ . '/../includes/auth.php';
require_admin();
$assetBase = '../';
require __DIR__ . '/../templates/header.php'; ?>
<?php require_once __DIR__ . '/../includes/db.php'; ?>
<?php
$conn = get_db();
$msg = '';
$msg_class = 'info';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
  if (!csrf_verify($token)) {
    $msg = 'Invalid request';
    $msg_class = 'danger';
  } else {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
      $id = trim($_POST['id']);
      $name = trim($_POST['name']);
      $price = is_numeric($_POST['price']) ? floatval($_POST['price']) : null;
      $stock = is_numeric($_POST['stock']) ? intval($_POST['stock']) : null;
      $image = trim($_POST['image']);
      if ($id !== '' && $name !== '' && $price !== null && $stock !== null && $price >= 0 && $stock >= 0) {
        $st = mysqli_prepare($conn, "INSERT INTO products(id,name,price,stock,image) VALUES(?,?,?,?,?)");
        mysqli_stmt_bind_param($st, 'ssdis', $id, $name, $price, $stock, $image);
        $exec = mysqli_stmt_execute($st);
        $errno = mysqli_errno($conn);
        $ok = $exec && mysqli_stmt_affected_rows($st) > 0;
        mysqli_stmt_close($st);
        if ($ok) {
          $msg = 'Product added';
          $msg_class = 'success';
        } else {
          $msg = ($errno === 1062) ? 'Product ID already exists' : 'Failed to add';
          $msg_class = 'danger';
        }
      } else {
        $msg = 'Please provide valid ID, name, price and stock';
        $msg_class = 'warning';
      }
    } elseif ($action === 'update') {
      $id = trim($_POST['id']);
      $name = trim($_POST['name']);
      $price = is_numeric($_POST['price']) ? floatval($_POST['price']) : null;
      $stock = is_numeric($_POST['stock']) ? intval($_POST['stock']) : null;
      $image = trim($_POST['image']);
      if ($id !== '' && $name !== '' && $price !== null && $stock !== null && $price >= 0 && $stock >= 0) {
        $st = mysqli_prepare($conn, "UPDATE products SET name=?, price=?, stock=?, image=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'sdiss', $name, $price, $stock, $image, $id);
        $exec = mysqli_stmt_execute($st);
        $ok = $exec && mysqli_stmt_affected_rows($st) >= 0;
        mysqli_stmt_close($st);
        if ($ok) {
          $msg = 'Product updated';
          $msg_class = 'success';
        } else {
          $msg = 'Failed to update';
          $msg_class = 'danger';
        }
      } else {
        $msg = 'Please provide valid name, price and stock';
        $msg_class = 'warning';
      }
    } elseif ($action === 'delete') {
      $id = trim($_POST['id']);
      if ($id !== '') {
        $st = mysqli_prepare($conn, "DELETE FROM products WHERE id=?");
        mysqli_stmt_bind_param($st, 's', $id);
        $exec = mysqli_stmt_execute($st);
        $errno = mysqli_errno($conn);
        $ok = $exec && mysqli_stmt_affected_rows($st) > 0;
        mysqli_stmt_close($st);
        if ($ok) {
          $msg = 'Product deleted';
          $msg_class = 'success';
        } else {
          if ($errno === 1451) {
            $msg = 'Cannot delete: product is referenced by orders';
          } else {
            $msg = 'Failed to delete';
          }
          $msg_class = 'danger';
        }
      }
    }
  }
}
$rows = [];
if ($conn) {
  $res = mysqli_query($conn, "SELECT id,name,price,stock,image FROM products ORDER BY name");
  if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
      $rows[] = $r;
    }
  }
}
?>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h5 mb-0">Manage Products</div>
    <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
  </div>
  <?php if ($msg): ?><div class="alert alert-<?= htmlspecialchars($msg_class) ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <div class="card mb-3">
    <div class="card-body">
      <form method="post" class="row g-2">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="col-12 col-md-2"><input name="id" class="form-control" placeholder="ID" required></div>
        <div class="col-12 col-md-2"><input name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-6 col-md-2"><input name="price" type="number" step="0.01" class="form-control" placeholder="Price" required></div>
        <div class="col-6 col-md-2"><input name="stock" type="number" class="form-control" placeholder="Stock" required></div>
        <div class="col-12 col-md-3"><input name="image" class="form-control" placeholder="Image URL"></div>
        <div class="col-12 col-md-1"><button class="btn btn-primary w-100">Add</button></div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive d-none d-md-block">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Image</th>
              <th>ID</th>
              <th>Name</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Image URL</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><img src="<?= htmlspecialchars($r['image']) ?>" width="40" height="40" style="object-fit:cover"></td>
                <td><?= htmlspecialchars($r['id']) ?></td>
                <td><input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($r['name']) ?>" form="f-<?= htmlspecialchars($r['id']) ?>"></td>
                <td><input name="price" type="number" step="0.01" class="form-control form-control-sm" value="<?= number_format($r['price'], 2, '.', '') ?>" form="f-<?= htmlspecialchars($r['id']) ?>"></td>
                <td><input name="stock" type="number" class="form-control form-control-sm" value="<?= (int)$r['stock'] ?>" form="f-<?= htmlspecialchars($r['id']) ?>"></td>
                <td><input name="image" class="form-control form-control-sm" value="<?= htmlspecialchars($r['image']) ?>" form="f-<?= htmlspecialchars($r['id']) ?>"></td>
                <td>
                  <form id="f-<?= htmlspecialchars($r['id']) ?>" method="post" class="d-inline">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button class="btn btn-secondary btn-sm">Save</button>
                  </form>
                  <form method="post" class="d-inline" onsubmit="return confirm('Delete product <?= htmlspecialchars($r['id']) ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="d-md-none">
        <?php if (!$rows): ?>
          <div class="text-muted">No products yet.</div>
        <?php endif; ?>
        <div class="row g-3">
          <?php foreach ($rows as $r): ?>
            <div class="col-12">
              <div class="border rounded p-3 h-100">
                <div class="d-flex align-items-center mb-3">
                  <img src="<?= htmlspecialchars($r['image']) ?>" width="60" height="60" class="rounded me-3" style="object-fit:cover">
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($r['id']) ?></div>
                  </div>
                </div>
                <form id="fm-<?= htmlspecialchars($r['id']) ?>" method="post" class="mb-2">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <div class="mb-2">
                    <label class="form-label small mb-1">Name</label>
                    <input name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($r['name']) ?>">
                  </div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label small mb-1">Price</label>
                      <input name="price" type="number" step="0.01" class="form-control form-control-sm" value="<?= number_format($r['price'], 2, '.', '') ?>">
                    </div>
                    <div class="col-6">
                      <label class="form-label small mb-1">Stock</label>
                      <input name="stock" type="number" class="form-control form-control-sm" value="<?= (int)$r['stock'] ?>">
                    </div>
                  </div>
                  <div class="mt-2">
                    <label class="form-label small mb-1">Image URL</label>
                    <input name="image" class="form-control form-control-sm" value="<?= htmlspecialchars($r['image']) ?>">
                  </div>
                  <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-secondary btn-sm flex-grow-1">Save</button>
                    <button type="button" class="btn btn-danger btn-sm flex-grow-1" onclick="if(confirm('Delete product <?= htmlspecialchars($r['id']) ?>?')) { const form=document.getElementById('del-<?= htmlspecialchars($r['id']) ?>'); if(form) form.submit(); }">Delete</button>
                  </div>
                </form>
                <form id="del-<?= htmlspecialchars($r['id']) ?>" method="post">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($r['id']) ?>">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>