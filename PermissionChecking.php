<?php 
// data link code use to other pages to link iths page. 
include_once 'tracking/track_engine.php'; ?>

<?php

// Get current file name without extension
$currentPage = basename($_SERVER['PHP_SELF'], ".php"); // e.g., 'addLeads'
// echo $currentPage; // Output: 'addLeads'
// echo "<br>"; // Output: 'addLeads'
$userId = $_SESSION['user_id'];

$query = "SELECT sub_list_value FROM user_permission WHERE user_id = '$userId'";
$result_for_up = mysqli_query($conn, $query);

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

// Check if $currentPage is in the list
if (in_array($currentPage, $subListValues)) {
    // echo "Allowed";
    // print_r($subListValues);
} else {
    // echo "Not Allowed";
    // echo '<script>window.location.href = "index";</script>';
    
    // echo "Not Allowed";

    if ($currentPage != 'index') {
        echo '<script>window.location.href = "index";</script>';
    }
    
}
