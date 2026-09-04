<?php
include("database/connection.php");

echo "Starting migration...\n";

// Set charset
mysqli_set_charset($conn, "utf8mb4");

$checkQuery = "SELECT sub_lists FROM nav_collections WHERE id = 5";
$result = mysqli_query($conn, $checkQuery);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $subLists = $row['sub_lists'];
    
    // Check if the permission already exists
    if (strpos($subLists, 'induction_fl_report_db') === false) {
        $newSubLists = $subLists . ", Induction DB Student List - induction_fl_report_db";
        $updateQuery = "UPDATE nav_collections SET sub_lists = '" . mysqli_real_escape_string($conn, $newSubLists) . "' WHERE id = 5";
        
        if (mysqli_query($conn, $updateQuery)) {
            echo "Successfully added 'Induction DB Student List - induction_fl_report_db' to nav_collections.\n";
        } else {
            echo "Error updating nav_collections: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Permission 'induction_fl_report_db' already exists in nav_collections.\n";
    }
} else {
    echo "Reports category (id = 5) not found in nav_collections table.\n";
}

$conn->close();
echo "Migration complete.\n";
?>
