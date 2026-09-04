<?php
require_once __DIR__ . '/includes/auth.php';
start_secure_session();
$current_user = auth_current_user();
$was_admin = $current_user && ($current_user['role'] ?? '') === 'admin';
auth_logout();
// Redirect to appropriate login page based on previous role
header('Location: ' . ($was_admin ? '/stall/admin/login.php' : '/stall/login.php'));
exit;
