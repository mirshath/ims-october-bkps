<?php
include("../database/connection.php");

$student_id = $_POST['student_id'];

$query = "
SELECT tutor_id
FROM tutor_allocate
WHERE student_id = ?
LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

echo json_encode([
  'student_id' => $student_id,
  'tutor_id' => $row['tutor_id'] ?? ''
]);
