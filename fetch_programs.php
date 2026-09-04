<?php
session_start();
// Database connection
include("database/connection.php");

if (isset($_POST['university'])) {
    $universityId = $_POST['university'];

    // Prepare and execute the SQL statement to fetch programs
    $stmt = $conn->prepare("SELECT * FROM program_table WHERE university_id = ?");
    $stmt->bind_param("i", $universityId);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = "<option value='' disabled selected>Select Programme</option>";

    while ($row = $result->fetch_assoc()) {
        $options .= "<option value='" . htmlspecialchars($row['id']) . "'>" . htmlspecialchars($row['program_name']) . "</option>";
    }

    echo $options;

    $stmt->close();
}

$conn->close();
?>
