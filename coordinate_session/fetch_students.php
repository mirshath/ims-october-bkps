<?php
// include("../db/db.php");

// $program = intval($_POST['program_id']);
// $batch   = intval($_POST['batch_id']);
// $session = intval($_POST['session_id'] ?? 0);

// $sql = "
// SELECT DISTINCT
//     s.student_code,
//     s.first_name,
//     s.last_name,
//     ta.tutor_id,
//     ta.session_id
// FROM allocate_programme ap
// INNER JOIN students s 
//     ON s.student_code = ap.student_code
// LEFT JOIN tutor_allocate ta 
//     ON ta.student_id = s.student_code
//     AND ta.program_id = ap.programme_code
//     AND ta.batch_id = ap.batch_id
// WHERE ap.programme_code = $program
// AND ap.batch_id = $batch
// ORDER BY s.first_name, s.last_name
// ";

// $res = $conn->query($sql);

// $data = [];

// while ($r = $res->fetch_assoc()) {

//     // DEFAULT = unallocated
//     $allocated     = false;
//     $tutor_name    = '';
//     $session_name  = '';
//     $tutor_id      = '';
//     $session_id    = '';

//     // If student is allocated AND matches selected session
//     if (!empty($r['session_id']) && $r['session_id'] == $session) {

//         $allocated  = true;
//         $tutor_id   = $r['tutor_id'];
//         $session_id = $r['session_id'];

//         // Tutor name
//         if (!empty($tutor_id)) {
//             $t = $conn->query("SELECT username FROM admin WHERE id=".$tutor_id)->fetch_assoc();
//             $tutor_name = $t['username'] ?? '';
//         }

//         // Session name
//         $s = $conn->query("SELECT session_name FROM tutor_session WHERE session_id=".$session_id)->fetch_assoc();
//         $session_name = $s['session_name'] ?? '';
//     }

//     $data[] = [
//         'student_code' => $r['student_code'],
//         'name'         => $r['first_name'].' '.$r['last_name'],
//         'tutor_name'   => $tutor_name,
//         'session_name' => $session_name,
//         'tutor_id'     => $tutor_id,
//         'session_id'   => $session_id,
//         'allocated'    => $allocated
//     ];
// }

// echo json_encode(['students'=>$data]);



include("../database/connection.php");

$program = intval($_POST['program_id']);
$batch   = intval($_POST['batch_id']);
$session = intval($_POST['session_id'] ?? 0);

// Get all students in this programme/batch
$sql = "
SELECT 
    ap.*,
    ap.student_registration_id,
    s.student_code,
    s.first_name,
    s.last_name
FROM allocate_programme ap
INNER JOIN students s ON s.student_code = ap.student_code
WHERE ap.programme_code = $program
AND ap.batch_id = $batch
ORDER BY s.first_name, s.last_name
";

$res = $conn->query($sql);
$data = [];

while ($r = $res->fetch_assoc()) {

    $student_code = $r['student_code'];

    // Check if this student has allocation in selected session
    $alloc_sql = "
    SELECT ta.id, ta.tutor_id, ta.session_id
    FROM tutor_allocate ta
    WHERE ta.student_id = '$student_code'
    AND ta.program_id = $program
    AND ta.batch_id = $batch
    AND ta.session_id = $session
    ";
    $alloc_res = $conn->query($alloc_sql);

    if ($alloc_res->num_rows > 0) {
        // Student already allocated in this session
        while ($alloc = $alloc_res->fetch_assoc()) {
            $tutor_name   = '';
            $session_name = '';

            if (!empty($alloc['tutor_id'])) {
                $t = $conn->query("SELECT username FROM admin WHERE id=".$alloc['tutor_id'])->fetch_assoc();
                $tutor_name = $t['username'] ?? '';
            }

            if (!empty($alloc['session_id'])) {
                $s = $conn->query("SELECT session_name FROM tutor_session WHERE session_id=".$alloc['session_id'])->fetch_assoc();
                $session_name = $s['session_name'] ?? '';
            }

            $data[] = [
                'student_code' => $student_code,
                'stu'          => $r['student_registration_id'],
                'name'         => $r['first_name'].' '.$r['last_name'],
                'allocate_id'  => $alloc['id'],
                'tutor_name'   => $tutor_name,
                'session_name' => $session_name,
                'tutor_id'     => $alloc['tutor_id'],
                'session_id'   => $alloc['session_id'],
                'allocated'    => true
            ];
        }
    } else {
        // Student not yet allocated in this session
        $data[] = [
            'student_code' => $student_code,
            'stu'          => $r['student_registration_id'],
            'name'         => $r['first_name'].' '.$r['last_name'],
            'allocate_id'  => null,
            'tutor_name'   => '',
            'session_name' => '',
            'tutor_id'     => '',
            'session_id'   => '',
            'allocated'    => false
        ];
    }
}

echo json_encode(['students' => $data]);
