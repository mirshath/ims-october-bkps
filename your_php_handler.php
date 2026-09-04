<?php
include 'database/connection.php'; // Update path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reg_id = $_POST['reg_id'] ?? '';
    $new_reg_id = $_POST['new_reg_id'] ?? '';
    $programme_code = $_POST['programme_code'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    $student_id = $_POST['student_id'] ?? '';

    if (!$reg_id || !$programme_code || !$batch_id || !$student_id) {
        echo "Missing required parameters.";
        exit;
    }

    // Step 1: Check if a matching record exists
    $checkQuery = "SELECT id, student_registration_id, new_student_registration_id 
                   FROM allocate_programme 
                   WHERE programme_code = ? AND batch_id = ? AND student_code = ?";
    $stmt = $conn->prepare($checkQuery);
    // Change the binding to "ssi" if programme_code and student_id are strings
    $stmt->bind_param("ssi", $programme_code, $batch_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Step 2: If found, check if student_registration_id is empty or null
    if ($row = $result->fetch_assoc()) {
        $existingRegID = $row['student_registration_id'];
        $existingNewRegID = $row['new_student_registration_id'];
        $recordID = $row['id'];

        if (empty($existingRegID)) {
            // Step 3: Update student_registration_id
            $updateQuery = "UPDATE allocate_programme SET student_registration_id = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("si", $reg_id, $recordID);

            if ($updateStmt->execute()) {
                echo "Updated student_registration_id successfully.";
            } else {
                echo "Failed to update: " . $updateStmt->error;
            }

            $updateStmt->close();
        } else {
            echo "student_registration_id already exists. No update needed.";
        }

        if (empty($existingNewRegID)) {
            // Step 4: Update new_student_registration_id
            $updateNewRegIDQuery = "UPDATE allocate_programme SET new_student_registration_id = ? WHERE id = ?";
            $updateNewRegIDStmt = $conn->prepare($updateNewRegIDQuery);
            $updateNewRegIDStmt->bind_param("si", $new_reg_id, $recordID);

            if ($updateNewRegIDStmt->execute()) {
                echo "Updated new_student_registration_id successfully.";
            } else {
                echo "Failed to update: " . $updateNewRegIDStmt->error;
            }

            $updateNewRegIDStmt->close();
        } else {
            echo "new_student_registration_id already exists. No update needed.";
        }
    } else {
        echo "No matching record found.";
    }

    $stmt->close();
    $conn->close();
}
