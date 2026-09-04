<?php
session_start();

$Session_username = $_SESSION['username'];

include("../database/connection.php");

// Expecting program_code and batch_id as well
if (
    isset($_POST['student_code']) &&
    isset($_POST['student_status']) &&
    isset($_POST['additional_info']) &&
    isset($_POST['student_registration_id_pass']) &&
    isset($_POST['program_code']) &&
    isset($_POST['batch_id'])
) {
    $studentCode = $_POST['student_code'];
    $studentStatus = $_POST['student_status'];
    $additionalInfo = $_POST['additional_info']; // Directly use POST, assuming it's sanitized on the client side
    $studentRegistrationId = $_POST['student_registration_id_pass']; // Correctly retrieve the registration ID
    $programCode = $_POST['program_code'];
    $batchId = $_POST['batch_id'];

    // Determine active status based on student status
    $active = (trim(strtolower($studentStatus)) === 'drop' || trim(strtolower($studentStatus)) === 'inactive') ? 0 : 1;

    // Use prepared statements to avoid SQL injection
    $conn->begin_transaction();
    try {
        // Check the current student transfer status
        $checkTransferStatus = $conn->prepare("
            SELECT student_status, transfer_status
            FROM students 
            WHERE student_code = ?
        ");
        $checkTransferStatus->bind_param("s", $studentCode);
        $checkTransferStatus->execute();
        $result = $checkTransferStatus->get_result();

        if ($result->num_rows > 0) {
            $transferStatusArray = $result->fetch_assoc();

            // If the student is transferred and their transfer_status is 1
            if ($transferStatusArray['student_status'] === 'transferred' && $transferStatusArray['transfer_status'] == 1) {
                // Update transfer status to 0 for transferred students
                $updateStudentQuery = $conn->prepare("
                    UPDATE students 
                    SET transfer_status = 0 
                    WHERE student_code = ?
                ");
                $updateStudentQuery->bind_param("s", $studentCode);

                if (!$updateStudentQuery->execute()) {
                    throw new Exception("Error updating student transfer status: " . $updateStudentQuery->error);
                }
            } else {
                // For non-transferred students or other conditions, update the student status
                $updateStudentQuery = $conn->prepare("
                    UPDATE students 
                    SET student_status = ?, remark = ?, active = ? 
                    WHERE student_code = ?
                ");
                $updateStudentQuery->bind_param("ssis", $studentStatus, $additionalInfo, $active, $studentCode);

                if (!$updateStudentQuery->execute()) {
                    throw new Exception("Error updating student status: " . $updateStudentQuery->error);
                }
            }
        }

        // Update allocation status in 'allocate_programme' table
        // Use student_code, program_code and batch_id as identifiers (per request)
        $updateAllocateProgrammeQuery = $conn->prepare("
            UPDATE allocate_programme 
            SET status = ?, dm_remark = ? 
            WHERE student_code = ? AND programme_code  = ? AND batch_id = ?
        ");
        $updateAllocateProgrammeQuery->bind_param("sssss", $studentStatus, $additionalInfo, $studentCode, $programCode, $batchId);

        if (!$updateAllocateProgrammeQuery->execute()) {
            throw new Exception("Error updating allocation status: " . $updateAllocateProgrammeQuery->error);
        }

        // Commit the transaction if everything is successful
        $conn->commit();
        echo "Student status and allocation updated successfully.";
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $conn->rollback();
        echo $e->getMessage();
    }
}
