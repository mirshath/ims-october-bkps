<?php
include("../database/connection.php");

if (isset($_POST['student_registration_id']) && isset($_POST['student_code'])) {
    $studentRegistrationId = $_POST['student_registration_id'];
    $studentCode = $_POST['student_code'];

    // Prepare the query to check the allocation status
    $checkAllocationQuery = $conn->prepare("
        SELECT status, transfer_status_alct_tbl 
        FROM allocate_programme 
        WHERE student_registration_id = ?
    ");
    $checkAllocationQuery->bind_param("s", $studentRegistrationId);
    $checkAllocationQuery->execute();
    $result = $checkAllocationQuery->get_result();

    if ($result->num_rows > 0) {
        $allocation = $result->fetch_assoc();

        // Check if the status is transferred and transfer_status is 0
        if ($allocation['status'] === 'transferred' && $allocation['transfer_status_alct_tbl'] == 0) {
            // Update the student status in the students table
            $updateStudentQuery = $conn->prepare("
                UPDATE students 
                SET student_status = 'active', transfer_status = 0 
                WHERE student_code = ?
            ");
            $updateStudentQuery->bind_param("s", $studentCode);

            if ($updateStudentQuery->execute()) {
                echo json_encode(['success' => true, 'message' => 'Student status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update student status']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Conditions not met for update']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No allocation found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
