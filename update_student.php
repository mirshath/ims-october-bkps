<?php
session_start();
$Session_username = $_SESSION['username'];

include("./database/connection.php");

// Get the values from the form (POST request)
$student_code = $_POST['student_code'];
$registration_code = $_POST['registration_code'];
// $compulsory_subjects = $_POST['compulsory_subjects'];


// $compulsory_subjects = isset($_POST['compulsory_subjects']) ? implode(',', $_POST['compulsory_subjects']) : '';
// $elective_subjects = isset($_POST['elective_subjects']) ? implode(',', $_POST['elective_subjects']) : '';


$compulsory_subjects = isset($_POST['compulsory_subjects']) ? implode(',', array_map('trim', $_POST['compulsory_subjects'])) : '';
$elective_subjects = isset($_POST['elective_subjects']) ? implode(',', array_map('trim', $_POST['elective_subjects'])) : '';


// Prepare the SQL query to update the allocate_programme table
$query = "
    UPDATE allocate_programme 
    SET student_registration_id = ?, 
        compulsory_sub = ?, 
        elective_subs = ?,
        entered_by = ?
    WHERE student_code = ?
    ORDER BY id DESC
        LIMIT 1
";


// Prepare the statement
$stmt = $conn->prepare($query);

// Bind the parameters (types: s = string, i = integer)
$stmt->bind_param('ssssi', $registration_code, $compulsory_subjects, $elective_subjects,$Session_username, $student_code);


// Execute the query
if ($stmt->execute()) {
    // Get the last inserted id for the allocated_id
    $allocated_id_query = "SELECT id FROM allocate_programme WHERE student_code = ? ORDER BY id DESC LIMIT 1";
    $allocated_id_stmt = $conn->prepare($allocated_id_query);
    $allocated_id_stmt->bind_param('i', $student_code);
    $allocated_id_stmt->execute();
    $allocated_id_stmt->bind_result($allocated_id);
    $allocated_id_stmt->fetch();
    $allocated_id_stmt->close();

    // Now update the from_registration_code in the student_transfer table
    $update_transfer_query = "
        UPDATE student_transfer 
        SET from_registration_code = ? 
        WHERE allocated_id = ?
    ";
    $update_transfer_stmt = $conn->prepare($update_transfer_query);
    $update_transfer_stmt->bind_param('si', $registration_code, $allocated_id);

    if ($update_transfer_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Update successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update student transfer data.']);
    }

    // Close the update transfer statement
    $update_transfer_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update allocate programme data.']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
