<?php
session_start();
include("./database/connection.php");

if (!isset( $_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// Get the student code from the AJAX request
$student_code = $_POST['student_code'];

$query = "
    SELECT
        s.*,
        u.university_name,
        p.*,
        b.batch_name,
        a.*
        FROM allocate_programme a
        JOIN students s ON a.student_code = s.student_code
        JOIN universities u ON a.university_id = u.id
        JOIN program_table p ON a.programme_code = p.program_code
        JOIN batch_table b ON a.batch_id = b.id
        WHERE a.student_code = ? 
        ORDER BY a.id DESC
        LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $student_code);
$stmt->execute();
$result = $stmt->get_result();

// Check if any results were returned
if ($result->num_rows === 0) {
    // No allocations found for this student
    echo json_encode(['allocated' => false]);
    exit; // Exit the script
}

$data = $result->fetch_assoc();

// Prepare the response as a JSON object
$response = [
    'allocated' => true, // Indicate that the student has allocations

    'student_id' => $data['student_code'], // Add student_id (or student_code)
    'student_name' => $data['first_name'] . ' ' . $data['last_name'],
    'university' => $data['university_name'],
    'programme' => $data['program_name'],
    'program_id' => $data['program_code'], // Fetch this too
    'batch' => $data['batch_name'],
    'registration_code' => $data['student_registration_id'],
    'elective_subjects' => $data['elective_subs'],
    'compulsory_subjects' => $data['compulsory_sub']
];

echo json_encode($response);
