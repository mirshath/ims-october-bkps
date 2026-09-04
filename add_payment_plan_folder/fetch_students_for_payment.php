<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
include '../database/connection.php';  // Ensure to include your DB connection file
mysqli_set_charset($conn, "utf8");

$sql = "SELECT DISTINCT ap.*, s.*, ap.batch_id, ap.student_registration_id,ap.programme_code ,b.batch_name, p.program_name
FROM allocate_programme ap
JOIN add_payment_plan_table pp
  ON ap.student_code = pp.student_id
JOIN students s
  ON ap.student_code = s.student_code
  JOIN program_table p
  ON ap.programme_code = p.program_code
JOIN batch_table b
  ON ap.batch_id = b.id
  WHERE ap.status NOT IN ('batchSwap', 'completed', 'inactive')
";
//   WHERE ap.status != 'batchSwap' OR ap.status != 'completed'

$result = mysqli_query($conn, $sql);

// Check for query errors
if (!$result) {
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit;
}

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

// Check if any students were found
if (empty($students)) {
    echo json_encode([
        'error' => true,
        'message' => 'No students found with payment plans'
    ]);
    exit;
}

echo json_encode($students);
?>