<?php
session_start();

include('./database/connection.php');

// Get the program type from the POST request
$program_type = isset($_POST['program_type']) ? $_POST['program_type'] : '';
$student_code = isset($_POST['student_code']) ? $_POST['student_code'] : '';

// Query to fetch elective subjects from modules and program_table
$query = "SELECT m.module_name 
          FROM modules m
          INNER JOIN program_table p ON m.programme_id = p.program_code
          WHERE m.type = 'Elective' AND p.program_name = ?";

// Fetch the elective subjects stored in the allocation for the student
$query_check_alloc = "SELECT elective_subs FROM allocate_programme WHERE student_code = ?";

$response = [];

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $program_type);
    $stmt->execute();
    $result = $stmt->get_result();

    // Array to hold elective subjects
    $electiveSubjects = [];

    while ($row = $result->fetch_assoc()) {
        $electiveSubjects[] = $row['module_name'];
    }

    // Fetch the elective subjects stored in the allocation for the student
    if ($stmt_check = $conn->prepare($query_check_alloc)) {
        $stmt_check->bind_param("s", $student_code);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $allocated_electives = explode(',', $row_check['elective_subs']);  // Assuming they are stored as a comma-separated string

        // Add both elective subjects and the allocated elective subjects to the response
        $response['elective_subjects'] = $electiveSubjects;
        $response['allocated_elective_subjects'] = $allocated_electives;
    }
}

// Close the database connection
$conn->close();

// Output the response for debugging
echo json_encode($response);
