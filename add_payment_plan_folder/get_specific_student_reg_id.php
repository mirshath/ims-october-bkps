<?php
include('../database/connection.php');

$student_code = $_POST['student_code'] ?? '';
$program_code = $_POST['program_code'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';

$response = ['success' => false];

if ($student_code && $program_code && $batch_id) {
  $sql = "SELECT student_registration_id, new_student_registration_id 
            FROM allocate_programme 
            WHERE student_code = ? 
              AND programme_code  = ? 
              AND batch_id  = ? 
            LIMIT 1";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssi", $student_code, $program_code, $batch_id);
  $stmt->execute();
  $stmt->bind_result($student_registration_id, $new_student_registration_id);

  if ($stmt->fetch()) {
    $response['success'] = true;
    $response['student_registration_id'] = $student_registration_id;
    $response['new_student_registration_id'] = $new_student_registration_id;
  }

  $stmt->close();
}

echo json_encode($response);
