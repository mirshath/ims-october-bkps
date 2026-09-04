<?php
session_start();
include("../database/connection.php");

if (isset($_POST['programme_id']) && isset($_POST['batch_id'])) {
    $programmeId = $_POST['programme_id'];
    $batchId = $_POST['batch_id'];
    $studentStatus = isset($_POST['student_status']) ? $_POST['student_status'] : '';

    // Query to get students allocated to the selected program and batch with status filter
    $query = "SELECT a.student_code, a.student_registration_id, 
                     CONCAT(s.first_name, ' ', s.last_name) AS student_name
              FROM allocate_programme a
              JOIN students s ON a.student_code = s.student_code
              WHERE a.programme_code = ? 
              AND a.batch_id = ? ";

    if (!empty($studentStatus)) {
        $query .= " AND a.status = ? ";
    }

    $query .= " ORDER BY s.first_name, s.last_name";

    /** @var mysqli $conn */
    global $conn;
    $stmt = $conn->prepare($query);
    
    if (!empty($studentStatus)) {
        $stmt->bind_param("iis", $programmeId, $batchId, $studentStatus);
    } else {
        $stmt->bind_param("ii", $programmeId, $batchId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    // Return the data in JSON format
    echo json_encode($students);
}
?>
