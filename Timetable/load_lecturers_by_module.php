<?php
include("../database/connection.php");

if (!$conn) {
    die('Database connection failed');
}

$module = $_POST['module'] ?? '';

if(empty($module)) {
    echo '<option value="">Select Lecturer</option>';
    exit;
}

// Escape the module to prevent SQL injection
$module = mysqli_real_escape_string($conn, $module);

// Query to find lecturers from the modules table
// The 'lecturers' column may contain multiple lecturers separated by commas
$query = "SELECT lecturers FROM modules WHERE module_code = '$module' OR module_name = '$module' LIMIT 1";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo '<option value="">Error loading lecturers</option>';
    exit;
}

if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $lecturers = $row['lecturers'] ?? '';
    
    if(empty($lecturers)) {
        echo '<option value="">No lecturers assigned to this module</option>';
        exit;
    }
    
    // Split the lecturers string by comma (or any delimiter used)
    // Assuming lecturers are stored as: "Dr. John Doe, Prof. Jane Smith, Mr. Bob Johnson"
    $lecturer_array = array_map('trim', explode(',', $lecturers));
    
    // Remove empty values
    $lecturer_array = array_filter($lecturer_array);
    
    if(count($lecturer_array) > 0) {
        echo '<option value="">Select Lecturer</option>';
        foreach($lecturer_array as $lecturer_name) {
            echo '<option value="' . htmlspecialchars($lecturer_name) . '">' . htmlspecialchars($lecturer_name) . '</option>';
        }
    } else {
        echo '<option value="">No lecturers found for this module</option>';
    }
} else {
    echo '<option value="">Module not found</option>';
}
?>