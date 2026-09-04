<?php
// module_result_mailing/fetch_email_status.php
include("../database/connection.php");

header("Content-Type: application/json");

// Get POST data
$student_id = isset($_POST['student_id']) ? $_POST['student_id'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$programme_id = isset($_POST['programme_id']) ? $_POST['programme_id'] : '';
$batch_id = isset($_POST['batch_id']) ? $_POST['batch_id'] : '';

if (empty($student_id) || empty($programme_id) || empty($batch_id)) {
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Sanitize inputs
$student_id = mysqli_real_escape_string($conn, $student_id);
$email = mysqli_real_escape_string($conn, $email);
$programme_id = mysqli_real_escape_string($conn, $programme_id);
$batch_id = mysqli_real_escape_string($conn, $batch_id);

// Query to get the latest email status
$query = "SELECT status, sent_by, DATE_FORMAT(sent_date, '%Y-%m-%d %H:%i') as sent_date 
          FROM module_result_email_log 
          WHERE student_id = '$student_id' 
            AND programme_id = '$programme_id' 
            AND batch_id = '$batch_id' 
          ORDER BY sent_date DESC 
          LIMIT 1";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["status" => null]);
}
?>