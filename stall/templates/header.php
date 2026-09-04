<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BMS POS</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <?php $assetBase = isset($assetBase) ? $assetBase : ''; ?>
  <link href="<?= $assetBase ?>assets/css/styles.css" rel="stylesheet">
</head>

<body class="bg-light" style="font-family:'Roboto',sans-serif;">
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#053065;">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">WELCOME TO BMS POS</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <?php
        if (!function_exists('auth_current_user')) {
          require_once __DIR__ . '/../includes/auth.php';
        }
        if (function_exists('start_secure_session')) {
          start_secure_session();
        }
        $current_user = function_exists('auth_current_user') ? auth_current_user() : null;
        if ($current_user):
        ?>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?= htmlspecialchars($current_user['name'] ?? $current_user['email']) ?>
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                  <span class="badge bg-warning text-dark ms-1">Admin</span>
                <?php endif; ?>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <?php if (($current_user['role'] ?? '') === 'admin'): ?>
                  <li><a class="dropdown-item" href="<?= isset($assetBase) ? $assetBase : '' ?>admin/index.php">Admin Dashboard</a></li>
                <?php else: ?>
                  <li><a class="dropdown-item" href="<?= isset($assetBase) ? $assetBase : '' ?>index.php">POS</a></li>
                <?php endif; ?>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="<?= isset($assetBase) ? $assetBase : '' ?>logout.php">Logout</a></li>
              </ul>
            </li>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>
  <div class="container-fluid py-3">