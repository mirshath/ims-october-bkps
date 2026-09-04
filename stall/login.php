<?php
require_once __DIR__ . '/includes/auth.php';
start_secure_session();

// Check if already logged in
$current_user = auth_current_user();
if ($current_user) {
  $base = get_base_path();
  if (($current_user['role'] ?? '') === 'user') {
    header('Location: ' . $base . 'index.php');
    exit;
  }
  header('Location: ' . $base . 'admin/login.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $token = $_POST['csrf'] ?? '';

  // Verify CSRF token
  if (!csrf_verify($token)) {
    $error = 'Invalid session. Please refresh and try again.';
    $base = get_base_path();
    header("Refresh:2; url=" . $base . "login.php");
  } elseif ($email === '' || $password === '') {
    $error = 'Email and password are required.';
  } else {
    // Attempt login
    if (auth_login($email, $password)) {
      $u = auth_current_user();

      if ($u) {
        $base = get_base_path();
        if (($u['role'] ?? '') !== 'user') {
          auth_logout();
          $error = 'Admins cannot login here. Use Admin Login.';
          header("Refresh:2; url=" . $base . "login.php");
        } else {
          header('Location: ' . $base . 'index.php');
          exit;
        }
      } else {
        $error = 'Unable to fetch user session. Please try again.';
      }
    } else {
      $error = 'Invalid credentials. Please check your email or password.';
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>User Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center min-h-screen py-8 px-4 bg-gray-50" style="background-image: url('assets/images/BG-LOGO.png'); background-repeat: repeat;">
  <div class="w-full max-w-md bg-white/95 shadow-2xl rounded-2xl p-6 sm:p-8 border border-gray-100 backdrop-blur">
    <div class="flex flex-col items-center justify-center mb-6 text-center">
      <img src="assets/images/download.png" alt="User Avatar"
        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-gray-200 mb-3 shadow-sm">
      <h2 class="text-xl sm:text-2xl font-bold text-gray-800">
        User Login for BMS POS
      </h2>
    </div>

    <?php if ($error): ?>
      <div class="mb-4 p-3 rounded-md bg-red-100 border border-red-300 text-red-700 text-sm">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off" class="space-y-4 sm:space-y-5">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

      <div class="space-y-1">
        <label class="block text-gray-700 font-medium text-sm sm:text-base">Email</label>
        <input
          type="email"
          name="email"
          class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none transition"
          placeholder="user@example.com"
          required>
      </div>

      <div class="space-y-1 relative">
        <label class="block text-gray-700 font-medium text-sm sm:text-base">Password</label>
        <div class="relative">
          <input
            type="password"
            name="password"
            id="password-field"
            class="w-full px-4 py-2.5 rounded-lg border border-gray-200 focus:ring-2 focus:ring-blue-400 focus:outline-none transition pr-10"
            placeholder="••••••••"
            required>
          <button type="button"
            tabindex="-1"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
            onclick="togglePassword()"
            aria-label="Show/Hide password"
            style="background: none; outline: none;">
            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-none" viewBox="0 0 24 24" stroke="currentColor">
              <path id="eye" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M1.458 12C2.732 7.943 6.522 5 12 5c5.478 0 9.268 2.943 10.542 7-1.274 4.057-5.064 7-10.542 7-5.478 0-9.268-2.943-10.542-7z" />
              <circle id="pupil" cx="12" cy="12" r="3" stroke-width="2" stroke="currentColor" />
            </svg>
          </button>
        </div>
        <!--<p class="text-xs text-gray-500 mt-1">-->
        <!--  Default user: <b>abc@gmail.com</b> / <b>admin123</b>-->
        <!--</p>-->
      </div>
      <script>
        function togglePassword() {
          const field = document.getElementById('password-field');
          const icon = document.getElementById('eye-icon');
          if (field.type === 'password') {
            field.type = 'text';
            icon.innerHTML = `
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5.478 0-9.268-2.943-10.542-7a10.041 10.041 0 012.733-4.362m2.764-2.095C8.816 5.18 10.353 5 12 5c5.478 0 9.268 2.943 10.542 7a9.96 9.96 0 01-4.889 5.614M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
            `;
          } else {
            field.type = 'password';
            icon.innerHTML = `
              <path id="eye" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M1.458 12C2.732 7.943 6.522 5 12 5c5.478 0 9.268 2.943 10.542 7-1.274 4.057-5.064 7-10.542 7-5.478 0-9.268-2.943-10.542-7z" />
              <circle cx="12" cy="12" r="3" stroke-width="2" stroke="currentColor"/>
            `;
          }
        }
      </script>

      <!-- <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 sm:py-3 rounded-lg shadow-md transition duration-300 text-sm sm:text-base">
        Sign in as User
      </button> -->
      <button
        type="submit"
        style="background-color: #042d5c; color:rgb(255, 255, 255);"
        class="w-full font-semibold py-2.5 sm:py-3 rounded-lg shadow-md transition duration-300 text-sm sm:text-base">
        Sign in as User
      </button>
    </form>

    <!-- <div class="mt-5 text-center">
        <a href="/hosted_bms_pos/admin/login.php" class="text-sm text-gray-600 hover:text-blue-600">
          ← Admin Login
        </a>
      </div> -->
  </div>
</body>

</html>