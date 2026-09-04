<?php
include("../database/connection.php");

$students = $_POST['student_id'] ?? [];
$programs = $_POST['program_id'] ?? [];
$batches  = $_POST['batch_id'] ?? [];
$tutors   = $_POST['tutor_id'] ?? [];
$sessions = $_POST['session_id'] ?? [];

/// This will now work because of the hidden input field in your HTML
$creator = $_POST['created_by'] ?? ''; 

// If the POST is still empty for some reason, use the Session directly as a backup
if (empty($creator)) {
    $creator = $_SESSION['username'] ?? 'System';
}

$creator = $conn->real_escape_string($creator);

for ($i = 0; $i < count($students); $i++) {

    if (empty($tutors[$i]) || empty($sessions[$i])) {
        continue;
    }

    $student = $conn->real_escape_string($students[$i]);
    $program = $conn->real_escape_string($programs[$i]);
    $batch   = $conn->real_escape_string($batches[$i]);
    $tutor   = $conn->real_escape_string($tutors[$i]);
    $session = $conn->real_escape_string($sessions[$i]);


    /* INSERT ONLY – NO UPDATE */
    $conn->query("
        INSERT INTO tutor_allocate
        (student_id, program_id, batch_id, tutor_id, session_id, created_by)
        VALUES
        ('$student', '$program', '$batch', '$tutor', '$session', '$creator')
    ");
}

echo "success";
