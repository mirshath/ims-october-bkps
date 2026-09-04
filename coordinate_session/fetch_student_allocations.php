<?php
include("../database/connection.php");

$student_code = $_POST['student_code'];

// Fetch all allocations for this student
$sql = "
SELECT ta.id, ta.tutor_id, ta.session_id
FROM tutor_allocate ta
WHERE ta.student_id = '$student_code'
ORDER BY ta.id
";

$res = $conn->query($sql);
$allocations = [];

while ($r = $res->fetch_assoc()) {
    $tutor_name = '';
    $session_name = '';

    if (!empty($r['tutor_id'])) {
        $t = $conn->query("SELECT username FROM admin WHERE id=".$r['tutor_id'])->fetch_assoc();
        $tutor_name = $t['username'] ?? '';
    }

    if (!empty($r['session_id'])) {
        $s = $conn->query("SELECT session_name FROM tutor_session WHERE session_id=".$r['session_id'])->fetch_assoc();
        $session_name = $s['session_name'] ?? '';
    }

    $allocations[] = [
        'id' => $r['id'],
        'tutor_name' => $tutor_name,
        'session_name' => $session_name,
        'tutor_id' => $r['tutor_id'],
        'session_id' => $r['session_id']
    ];
}

// Get student name
$name_res = $conn->query("SELECT first_name,last_name FROM students WHERE student_code='$student_code'")->fetch_assoc();
$student_name = $name_res['first_name'].' '.$name_res['last_name'];

echo json_encode(['allocations'=>$allocations,'student_name'=>$student_name]);
