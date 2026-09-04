<?php
include '../database/connection.php';

// 1) Grab all the GET params
$matched_column    = $_GET['matched_column']    ?? '';
$programme_id      = $_GET['programme_id']      ?? '';
$batch_id          = $_GET['batch_id']          ?? '';
$module_id         = $_GET['module_id']         ?? '';
$main_comp_id      = $_GET['main_component_id'] ?? '';

// 2) Decide which marks column
if ($matched_column === 'examinor_1') {
    $marks_column = 'examiner1_marks';
} else {
    $marks_column = 'examiner2_marks';
}

// 3) SQL with filtering
$sql = "
SELECT 
    s.student_code,
    CONCAT(s.first_name, ' ', s.last_name) AS student_name,
    ap.student_registration_id,
    br.with_Ques,
    MAX(CASE WHEN br.question_no = 'Q1' THEN br.{$marks_column} END) AS Q1,
    MAX(CASE WHEN br.question_no = 'Q2' THEN br.{$marks_column} END) AS Q2,
    MAX(CASE WHEN br.question_no = 'Q3' THEN br.{$marks_column} END) AS Q3,
    MAX(CASE WHEN br.question_no = 'Q4' THEN br.{$marks_column} END) AS Q4,
    MAX(CASE WHEN br.question_no = 'Q5' THEN br.{$marks_column} END) AS Q5,
    MAX(CASE WHEN br.question_no = 'Q6' THEN br.{$marks_column} END) AS Q6,
    MAX(CASE WHEN br.question_no = 'Q7' THEN br.{$marks_column} END) AS Q7,
    MAX(CASE WHEN br.question_no = 'Q8' THEN br.{$marks_column} END) AS Q8
FROM students s
JOIN allocate_programme ap 
  ON s.student_code = ap.student_code
JOIN bbm_result_details br 
  ON s.student_code = br.student_id
WHERE br.with_Ques    = 'yes'
  AND br.program_id    = ?
  AND br.batch_id      = ?
  AND br.module_id     = ?
  AND br.main_comp_id  = ?
GROUP BY s.student_code, ap.student_registration_id
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "iiii",
    $programme_id,
    $batch_id,
    $module_id,
    $main_comp_id
);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
echo json_encode($students);
