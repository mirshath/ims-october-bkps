<?php
// data link code use to other pages to link iths page.
include_once 'tracking/track_engine.php';

// Get current file name without extension
$currentPage = basename($_SERVER['PHP_SELF'], ".php"); // e.g., 'addLeads'

// --- FIX 1: super_admin bypasses the per-page permission check entirely ---
// Previously this file only looked at user_permission rows, so even
// super_admin accounts got redirected if their row was missing an entry
// for a given page. That's why pages like pos_dashboard / lib_dashboard
// kept bouncing back to "index" even though the user was logged in fine.
if (($_SESSION['role'] ?? '') !== 'super_admin') {

    $userId = $_SESSION['user_id'] ?? null;

    if ($userId !== null) {
        // Use a prepared statement instead of interpolating $userId directly.
        $query = "SELECT sub_list_value FROM user_permission WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result_for_up = mysqli_stmt_get_result($stmt);

        // Initialize an array to store processed sub_list_values
        $subListValues = [];

        if ($result_for_up) {
            while ($row = mysqli_fetch_assoc($result_for_up)) {
                // Explode by dash and get the second part, then trim it
                $parts = explode('-', $row['sub_list_value']);
                if (isset($parts[1])) {
                    $subListValues[] = trim($parts[1]); // Only add "addLeads" part
                }
            }
        } else {
            echo "Error fetching permissions: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    } else {
        $subListValues = [];
    }

    // Check if $currentPage is in the list
    if (!in_array($currentPage, $subListValues)) {
        // --- FIX 2: added the missing exit; ---
        // Without this, the script kept running and rendering the whole
        // page even after telling the browser to redirect, which is what
        // made the earlier redirect behavior look inconsistent.
        if ($currentPage != 'index') {
            echo '<script>window.location.href = "index";</script>';
            exit;
        }
    }
}
