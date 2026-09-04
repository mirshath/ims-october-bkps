<?php
include("../database/connection.php");

// 1. Sanitize input
$program = intval($_POST['program_id'] ?? 0);
$batch   = intval($_POST['batch_id'] ?? 0);
$session = intval($_POST['session_id'] ?? 0);

// 2. Use JOINs instead of queries inside a loop (Optimized)
$sql = "
SELECT DISTINCT
    s.student_code,
    s.first_name,
    s.last_name,
    p.program_name,
    b.batch_name,
    ta.id AS allocate_id,
    ta.tutor_id,
    ta.session_id,
    adm.username AS tutor_name,
    ts.session_name
FROM allocate_programme ap
INNER JOIN students s ON s.student_code = ap.student_code
INNER JOIN program_table p ON p.program_code = ap.programme_code
INNER JOIN batch_table b ON b.id = ap.batch_id
LEFT JOIN tutor_allocate ta 
    ON ta.student_id = s.student_code 
    AND ta.program_id = ap.programme_code 
    AND ta.batch_id = ap.batch_id
LEFT JOIN admin adm ON ta.tutor_id = adm.id
LEFT JOIN tutor_session ts ON ta.session_id = ts.session_id
WHERE ap.programme_code = ? 
AND ap.batch_id = ?
";

// 3. Handle the session filter dynamically
if ($session) {
    $sql .= " AND (ta.session_id = ? OR ta.session_id IS NULL)";
}

// 4. Use Prepared Statements (Prevents SQL Injection)
$stmt = $conn->prepare($sql);

if ($session) {
    $stmt->bind_param("iii", $program, $batch, $session);
} else {
    $stmt->bind_param("ii", $program, $batch);
}

$stmt->execute();
$result = $stmt->get_result();
$data = [];

while ($r = $result->fetch_assoc()) {
    $data[] = [
        'student_code' => $r['student_code'],
        'name'         => trim($r['first_name'] . ' ' . $r['last_name']),
        'programme'    => $r['program_name'],
        'batch'        => $r['batch_name'],
        'allocate_id'  => $r['allocate_id'],
        'tutor_id'     => $r['tutor_id'],
        'session_id'   => $r['session_id'],
        'tutor_name'   => $r['tutor_name'] ?? '',
        'session_name' => $r['session_name'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode($data);