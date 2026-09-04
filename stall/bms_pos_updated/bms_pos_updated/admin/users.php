<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$assetBase = '../';
require __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../includes/db.php';

$conn = get_db();
$errors = [];
$statusMap = [
  'created' => 'User created successfully.',
  'updated' => 'User updated successfully.',
  'deleted' => 'User removed successfully.',
];
$success = isset($_GET['status'], $statusMap[$_GET['status']]) ? $statusMap[$_GET['status']] : null;
$currentAdmin = auth_current_user();
$editRequestId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$editUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? '';
  $action = $_POST['action'] ?? 'create';
  if (!csrf_verify($token)) {
    $errors[] = 'Invalid request. Please refresh and try again.';
  } elseif (!$conn) {
    $errors[] = 'Database connection failed.';
  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    $isDelete = ($action === 'delete');
    $isUpdate = ($action === 'update');
    $isCreate = (!$isDelete && !$isUpdate);

    if ($isDelete) {
      if ($userId <= 0) {
        $errors[] = 'Invalid user selected for deletion.';
      }
      if (!$errors) {
        $st = mysqli_prepare($conn, "SELECT id, role FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $userId);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $target = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($st);
        if (!$target) {
          $errors[] = 'User not found.';
        } elseif (($currentAdmin['id'] ?? 0) === (int)$target['id']) {
          $errors[] = 'You cannot delete your own account.';
        }
      }
      if (!$errors && isset($target) && $target['role'] === 'admin') {
        $countRes = mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='admin'");
        $countRow = $countRes ? mysqli_fetch_assoc($countRes) : null;
        $adminCount = $countRow ? (int)$countRow['c'] : 0;
        if ($adminCount <= 1) {
          $errors[] = 'At least one admin account must remain.';
        }
      }
      if (!$errors) {
        $del = mysqli_prepare($conn, "DELETE FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($del, 'i', $userId);
        if (mysqli_stmt_execute($del)) {
          mysqli_stmt_close($del);
          header('Location: users.php?status=deleted');
          exit;
        }
        $errors[] = 'Failed to delete user. Please try again.';
        mysqli_stmt_close($del);
      }
    } else {
      if ($name === '') {
        $errors[] = 'Name is required.';
      }
      if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
      }
      if (!in_array($role, ['admin', 'user'], true)) {
        $errors[] = 'Role must be either admin or user.';
      }
      if ($isCreate && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
      }
      if ($isUpdate && $password !== '' && strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters if provided.';
      }
      if ($isUpdate && $userId <= 0) {
        $errors[] = 'Invalid user selected for update.';
      }
      if (!$errors) {
        if ($isUpdate) {
          $check = mysqli_prepare($conn, "SELECT id FROM users WHERE id=? LIMIT 1");
          mysqli_stmt_bind_param($check, 'i', $userId);
          mysqli_stmt_execute($check);
          $res = mysqli_stmt_get_result($check);
          $existing = $res ? mysqli_fetch_assoc($res) : null;
          mysqli_stmt_close($check);
          if (!$existing) {
            $errors[] = 'User not found.';
          }
        }
      }
      if (!$errors) {
        if ($isUpdate) {
          $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
          mysqli_stmt_bind_param($check, 'si', $email, $userId);
        } else {
          $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
          mysqli_stmt_bind_param($check, 's', $email);
        }
        mysqli_stmt_execute($check);
        $exists = mysqli_stmt_get_result($check);
        if ($exists && mysqli_fetch_assoc($exists)) {
          $errors[] = 'A user with this email already exists.';
        }
        mysqli_stmt_close($check);
      }
      if (!$errors) {
        if ($isCreate) {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $stmt = mysqli_prepare($conn, "INSERT INTO users(email,password_hash,name,role) VALUES(?,?,?,?)");
          mysqli_stmt_bind_param($stmt, 'ssss', $email, $hash, $name, $role);
          if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: users.php?status=created');
            exit;
          }
          $errors[] = 'Failed to create user. Please try again.';
          mysqli_stmt_close($stmt);
        } else {
          if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $role, $hash, $userId);
          } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $role, $userId);
          }
          if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header('Location: users.php?status=updated');
            exit;
          }
          $errors[] = 'Failed to update user. Please try again.';
          mysqli_stmt_close($stmt);
        }
      }
      if ($isUpdate && $errors) {
        $editRequestId = $userId;
      }
    }
  }
}

if ($editRequestId && $conn) {
  $st = mysqli_prepare($conn, "SELECT id,name,email,role FROM users WHERE id=? LIMIT 1");
  mysqli_stmt_bind_param($st, 'i', $editRequestId);
  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $editUser = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($st);
  if (!$editUser) {
    $errors[] = 'The selected user could not be found for editing.';
    $editRequestId = null;
  }
}

$users = [];
if ($conn) {
  $res = mysqli_query($conn, "SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC");
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $users[] = $row;
    }
  }
}
$roles = ['admin' => 'Admin', 'user' => 'User'];
$token = csrf_token();
?>

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="h5 mb-0">Manage Users</div>
      <div class="text-muted small">Create staff logins and assign roles</div>
    </div>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-lg-5">
      <div class="card">
        <div class="card-body">
          <div class="h6 mb-3"><?= $editUser ? 'Edit User' : 'Create New User' ?></div>
          <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?>
              <input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
            <?php endif; ?>
            <div class="mb-3">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label"><?= $editUser ? 'New Password (optional)' : 'Password' ?></label>
              <input type="password" class="form-control" name="password" <?= $editUser ? '' : 'minlength="6" required' ?>>
              <div class="form-text"><?= $editUser ? 'Leave blank to keep current password.' : 'Minimum 6 characters.' ?></div>
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <select class="form-select" name="role" required>
                <?php foreach ($roles as $value => $label): ?>
                  <option value="<?= $value ?>" <?= (($editUser['role'] ?? 'user') === $value) ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-primary"><?= $editUser ? 'Update User' : 'Create User' ?></button>
              <?php if ($editUser): ?>
                <a class="btn btn-outline-secondary" href="users.php">Cancel Editing</a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-7">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="h6 mb-0">Existing Users</div>
            <span class="badge bg-secondary"><?= count($users) ?> total</span>
          </div>
          <div class="table-responsive d-none d-md-block">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Created</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$users): ?>
                  <tr>
                    <td colspan="5" class="text-muted">No users found.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($users as $user): ?>
                    <tr>
                      <td><?= htmlspecialchars($user['name']) ?></td>
                      <td><?= htmlspecialchars($user['email']) ?></td>
                      <td>
                        <span class="badge <?= $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                          <?= htmlspecialchars(ucfirst($user['role'])) ?>
                        </span>
                      </td>
                      <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))) ?></td>
                      <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="users.php?edit=<?= (int)$user['id'] ?>">Edit</a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this user?');">
                          <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" <?= ($currentAdmin['id'] ?? 0) === (int)$user['id'] ? 'disabled' : '' ?>>Delete</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="d-md-none">
            <?php if (!$users): ?>
              <div class="text-muted">No users found.</div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($users as $user): ?>
                  <div class="col-12">
                    <div class="border rounded p-3 h-100">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                          <div class="fw-semibold"><?= htmlspecialchars($user['name']) ?></div>
                          <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <span class="badge <?= $user['role'] === 'admin' ? 'bg-warning text-dark' : 'bg-primary' ?>">
                          <?= htmlspecialchars(ucfirst($user['role'])) ?>
                        </span>
                      </div>
                      <div class="text-muted small mb-3">Joined <?= htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))) ?></div>
                      <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-secondary flex-grow-1" href="users.php?edit=<?= (int)$user['id'] ?>">Edit</a>
                        <?php if (($currentAdmin['id'] ?? 0) === (int)$user['id']): ?>
                          <button class="btn btn-sm btn-outline-danger flex-grow-1" disabled>Delete</button>
                        <?php else: ?>
                          <button class="btn btn-sm btn-outline-danger flex-grow-1" type="button" onclick="if(confirm('Delete this user?')) document.getElementById('del-user-<?= (int)$user['id'] ?>').submit();">Delete</button>
                          <form id="del-user-<?= (int)$user['id'] ?>" method="post" class="d-none">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                          </form>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>