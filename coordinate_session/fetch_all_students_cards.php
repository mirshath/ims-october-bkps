<?php
include("../database/connection.php");

$program = intval($_POST['program_id'] ?? 0);
$batch   = intval($_POST['batch_id'] ?? 0);
$search  = trim($_POST['search'] ?? '');
$sessionFilter = intval($_POST['sessionFilter'] ?? 0);
$tutorFilter   = intval($_POST['tutorFilter'] ?? 0);


if(!$program || !$batch){
    echo json_encode(['students'=>[], 'sessions'=>[], 'tutors'=>[]]);
    exit;
}

/* =========================
   STUDENT DATA (DISTINCT)
========================= */
$sql = "
SELECT 
    ap.*,
    ap.student_registration_id,
    s.student_code,
    s.first_name,
    s.last_name,
    p.program_name,
    b.batch_name,
    MAX(ts.session_name) AS session_name
FROM allocate_programme ap
INNER JOIN students s ON s.student_code = ap.student_code
INNER JOIN program_table p ON p.program_code = ap.programme_code
INNER JOIN batch_table b ON b.id = ap.batch_id
LEFT JOIN tutor_allocate ta 
    ON ta.student_id = s.student_code
    AND ta.program_id = ap.programme_code
    AND ta.batch_id = ap.batch_id
LEFT JOIN tutor_session ts ON ts.session_id = ta.session_id
WHERE ap.programme_code = $program
AND ap.batch_id = $batch
";

/* SEARCH */
if($search !== ''){
    $search = $conn->real_escape_string($search);
    $sql .= "
    AND (
        s.first_name LIKE '%$search%' OR
        s.last_name LIKE '%$search%' OR
        s.student_code LIKE '%$search%' OR
        ap.student_registration_id LIKE '%$search%'
    )";
}

/* FILTERS */
if($sessionFilter){
    $sql .= " AND ta.session_id = $sessionFilter";
}
if($tutorFilter){
    $sql .= " AND ta.tutor_id = $tutorFilter";
}

$sql .= " GROUP BY s.student_code ORDER BY s.first_name";

$res = $conn->query($sql);
$students=[];

while($r=$res->fetch_assoc()){
    $students[]=[
        'student_code'=>$r['student_code'],
        'stu'=>$r['student_registration_id'],
        'name'=>$r['first_name'].' '.$r['last_name'],
        'programme'=>$r['program_name'],
        'batch_name'=>$r['batch_name'],
        'session_name'=>$r['session_name'] ?? ''
    ];
}

/* =========================
   DISTINCT SESSION FILTER
========================= */
$sessions=[];
$sess=$conn->query("
    SELECT DISTINCT ts.session_id, ts.session_name
    FROM tutor_allocate ta
    INNER JOIN tutor_session ts ON ts.session_id=ta.session_id
    WHERE ta.program_id=$program
    AND ta.batch_id=$batch
    ORDER BY ts.session_name
");
while($s=$sess->fetch_assoc()){ $sessions[]=$s; }

/* =========================
   DISTINCT TUTOR FILTER
========================= */
$tutors=[];
$tut=$conn->query("
    SELECT DISTINCT a.id, a.username
    FROM tutor_allocate ta
    INNER JOIN admin a ON a.id=ta.tutor_id
    WHERE ta.program_id=$program
    AND ta.batch_id=$batch
    ORDER BY a.username
");
while($t=$tut->fetch_assoc()){ $tutors[]=$t; }

echo json_encode([
    'students'=>$students,
    'sessions'=>$sessions,
    'tutors'=>$tutors
]);
