<?php
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

// If already logged in as admin, redirect to admin dashboard
$current_user = auth_current_user();
if ($current_user && ($current_user['role'] ?? '') === 'admin') {
    $base = get_base_path();
    header('Location: ' . $base . 'admin/index.php');
    exit;
}

// If logged in as non-admin → logout and show message
$error = '';
if ($current_user && ($current_user['role'] ?? '') !== 'admin') {
    auth_logout();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $error = 'Please login with admin credentials.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $token = $_POST['csrf'] ?? '';

    if (!csrf_verify($token)) {
        $error = 'Invalid session. Please refresh and try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        if (auth_login($email, $password)) {
            $u = auth_current_user();
            if ($u && ($u['role'] ?? '') === 'admin') {
                $base = get_base_path();
                $next = $_GET['next'] ?? $base . 'admin/index.php';
                header('Location: ' . $next);
                exit;
            } else {
                auth_logout();
                $error = 'Invalid credentials. Admin credentials required.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Add viewport for mobile responsiveness -->

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Custom responsive tweaks for better mobile UX if needed */
        @media (max-width: 640px) {
            .custom-bg {
                padding: 1.25rem!important;
            }
        }
    </style>

</head>

<body class="flex items-center justify-center min-h-screen py-8 px-2 sm:px-4" style="background-image: url('../assets/images/BG-LOGO.png'); background-repeat: repeat;">

    <div class="w-full max-w-md bg-white shadow-xl rounded-xl custom-bg p-4 sm:p-8 border border-gray-200">

        <div class="flex flex-col items-center justify-center mb-6">
            <img src="../assets/images/download.png" alt="Admin Avatar"
                class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover border-4 border-gray-300 mb-3 shadow-sm">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 text-center leading-tight">
                Admin Login for BMS POS
            </h2>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 p-3 rounded-md bg-red-100 border border-red-300 text-red-700 text-xs sm:text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" class="space-y-4 sm:space-y-5">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

            <!-- Email -->
            <div>
                <label class="block text-gray-700 font-medium mb-1 text-sm sm:text-base">Email</label>
                <input type="email"
                    name="email"
                    class="w-full px-3 py-2 sm:px-4 sm:py-2 rounded-lg border border-gray-300 focus:ring-2 
                          focus:ring-blue-400 focus:outline-none text-sm sm:text-base"
                    placeholder="admin@example.com"
                    required
                    autocomplete="username"
                >
            </div>

            <!-- Password -->
            <div class="relative">
                <label class="block text-gray-700 font-medium mb-1 text-sm sm:text-base">Password</label>
                <div class="relative">
                    <input type="password"
                        name="password"
                        id="admin-password-field"
                        class="w-full px-3 py-2 sm:px-4 sm:py-2 rounded-lg border border-gray-300 focus:ring-2
                              focus:ring-blue-400 focus:outline-none pr-10 text-sm sm:text-base"
                        placeholder="•••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button"
                        tabindex="-1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                        onclick="toggleAdminPassword()"
                        aria-label="Show/Hide password"
                        style="background: none; outline: none;">
                        <svg id="admin-eye-icon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M1.458 12C2.732 7.943 6.522 5 12 5c5.478 0 9.268 2.943 10.542 7-1.274 4.057-5.064 7-10.542 7-5.478 0-9.268-2.943-10.542-7z" />
                            <circle cx="12" cy="12" r="3" stroke-width="2" stroke="currentColor" />
                        </svg>
                    </button>
                </div>
                <!--<p class="text-xs text-gray-500 mt-1 break-all">-->
                <!--    Default admin: <b>admin@example.com</b> / <b>admin123</b>-->
                <!--</p>-->
            </div>
            <script>
                function toggleAdminPassword() {
                    const field = document.getElementById('admin-password-field');
                    const icon = document.getElementById('admin-eye-icon');
                    if (field.type === 'password') {
                        field.type = 'text';
                        icon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-5.478 0-9.268-2.943-10.542-7a10.041 10.041 0 012.733-4.362m2.764-2.095C8.816 5.18 10.353 5 12 5c5.478 0 9.268 2.943 10.542 7a9.96 9.96 0 01-4.889 5.614M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        `;
                    } else {
                        field.type = 'password';
                        icon.innerHTML = `
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M1.458 12C2.732 7.943 6.522 5 12 5c5.478 0 9.268 2.943 10.542 7-1.274 4.057-5.064 7-10.542 7-5.478 0-9.268-2.943-10.542-7z" />
                            <circle cx="12" cy="12" r="3" stroke-width="2" stroke="currentColor"/>
                        `;
                    }
                }
            </script>

            <!-- Submit Button -->
            <button
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 sm:py-3
                   rounded-lg shadow-md transition duration-300 text-sm sm:text-base">
                Sign in as Admin
            </button>

        </form>

        <div class="mt-5 text-center">
            <a href="../login.php" class="text-xs sm:text-sm text-gray-600 hover:text-blue-600 block sm:inline">
                ← User Login
            </a>
        </div>

    </div>

</body>

</html>