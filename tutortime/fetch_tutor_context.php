<?php
require_once("../database/connection.php");
$tutor_id = $_POST['tutor_id'] ?? 0;

// Get Programs for this tutor
$programs_q = mysqli_query($conn, "
    SELECT DISTINCT p.program_code, p.program_name
    FROM tutor_session_allocation tsa
    INNER JOIN program_table p ON tsa.program_code = p.program_code
    WHERE tsa.tutor_id = $tutor_id");

$programs = [];
while($r = mysqli_fetch_assoc($programs_q)) $programs[] = $r;

// Get Sessions for this tutor
$sessions_q = mysqli_query($conn, "
    SELECT tsa.allocation_id, tsa.program_code, tsa.session_id, tsa.session_start_date, tsa.session_end_date, 
           ts.session_name, tsa.batch_id
    FROM tutor_session_allocation tsa
    INNER JOIN tutor_session ts on tsa.session_id = ts.session_id
    WHERE tsa.tutor_id = $tutor_id");

$sessions = [];
while($r = mysqli_fetch_assoc($sessions_q)) $sessions[] = $r;

echo json_encode(['programs' => $programs, 'sessions' => $sessions]);