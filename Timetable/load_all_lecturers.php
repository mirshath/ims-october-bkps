<?php
include("../database/connection.php");

if (!$conn) {
    die('Database connection failed');
}

// Query to get all lecturers from lecturer_table
$query = "SELECT id, lecturer_name, title 
          FROM lecturer_table 
          ORDER BY lecturer_name";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo '<option value="">Error loading lecturers</option>';
    exit;
}

if(mysqli_num_rows($result) > 0) {
    echo '<option value="">Select Lecturer</option>';
    while($row = mysqli_fetch_assoc($result)) {
        $title = $row['title'] ?? '';
        $displayName = $title ? $title . ' ' . $row['lecturer_name'] : $row['lecturer_name'];
        echo '<option value="' . htmlspecialchars($row['lecturer_name']) . '">' . htmlspecialchars($displayName) . '</option>';
    }
} else {
    echo '<option value="">No lecturers found</option>';
}
?>