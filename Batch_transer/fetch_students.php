<?php
include("../database/connection.php");

$university = $_POST['university_id'];
$program = $_POST['program_id'];
$batch = $_POST['batch_id'];


$query = "
    SELECT s.first_name,s.student_code , s.last_name, s.bms_email, a.id, a.student_registration_id
    FROM allocate_programme a
    JOIN students s ON s.student_code = a.student_code
    WHERE a.university_id='$university' AND a.programme_code='$program' AND a.batch_id='$batch' AND a.status = 'active'
";


$res = mysqli_query($conn, $query);

$students = [];

while ($row = mysqli_fetch_assoc($res)) {
    $students[] = [
        'id' => $row['id'],
        'name' => $row['first_name'] . " " . $row['last_name'],
         'student_code' => $row['student_code'],
        'bms_email' => $row['bms_email'],
        'student_registration_id' => $row['student_registration_id']  // add this
    ];
}

echo json_encode(['students' => $students]);

?>
