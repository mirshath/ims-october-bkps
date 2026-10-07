<?php
session_start();
include("../database/connection.php");


$Session_username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form data
    $programme_id = $_POST['programme_id'];
    $batch_id = $_POST['batch_id'];
    $module_id = $_POST['module_id'];
    $year_id = $_POST['year_id'] ?? '';
    $semester_id = $_POST['semester_id'];
    $description = $_POST['description'];
    $main_component_id = $_POST['main_component_id'];
    $sub_component_id = $_POST['sub_component_id'];
    $assessment_date = $_POST['assessment_date'];

    $isEdit = isset($_POST['id']);
    $upload_dir = "../uploads_exam_assessments/";

    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if ($isEdit) {
        // Get current assessment data for file handling
        $id = $_POST['id'];
        $query = "SELECT attachment_1, attachment_2, attachment_3, attachment_4 FROM assessments WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();

        // Initialize file names with current values
        $attachment_1_name = $current['attachment_1'];
        $attachment_2_name = $current['attachment_2'];
        $attachment_3_name = $current['attachment_3'];
        $attachment_4_name = $current['attachment_4'];
    } else {
        // Initialize empty file names for new assessment
        $attachment_1_name = $attachment_2_name = $attachment_3_name = $attachment_4_name = "";
    }

    // Ensure the date is in the correct format for MySQL
    $assessment_date = date('Y-m-d', strtotime($assessment_date));

    // Debugging: Check the value of year_id
    error_log("Year ID: " . $year_id);

    // Process file uploads
    foreach (['attachment_1', 'attachment_2', 'attachment_3', 'attachment_4'] as $attachment) {
        if (isset($_FILES[$attachment]) && $_FILES[$attachment]['error'] === UPLOAD_ERR_OK) {
            // Delete old file if it exists (for edit mode)
            if ($isEdit && !empty($current[$attachment]) && file_exists($upload_dir . $current[$attachment])) {
                unlink($upload_dir . $current[$attachment]);
            }

            // Upload new file
            $filename = basename($_FILES[$attachment]['name']); // Only the file name
            $unique_filename = uniqid() . "_" . $filename;
            if (move_uploaded_file($_FILES[$attachment]['tmp_name'], $upload_dir . $unique_filename)) {
                // Correctly update the respective attachment variable
                if ($attachment === 'attachment_1') {
                    $attachment_1_name = $unique_filename;
                } elseif ($attachment === 'attachment_2') {
                    $attachment_2_name = $unique_filename;
                } elseif ($attachment === 'attachment_3') {
                    $attachment_3_name = $unique_filename;
                } elseif ($attachment === 'attachment_4') {
                    $attachment_4_name = $unique_filename;
                }
            }
        }
    }

    if ($isEdit) {
        // Update existing assessment
        $sql = "UPDATE assessments SET 
                programme_id = ?, 
                batch_id = ?, 
                module_id = ?, 
                year_id = ?, 
                semester_id = ?, 
                description = ?, 
                main_component_id = ?, 
                sub_component_id = ?, 
                assessment_date = ?,
                attachment_1 = ?, 
                attachment_2 = ?, 
                attachment_3 = ?, 
                attachment_4 = ?,
                entered_by =? 
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiisssiissssssi",
            $programme_id,
            $batch_id,
            $module_id,
            $year_id,
            $semester_id,
            $description,
            $main_component_id,
            $sub_component_id,
            $assessment_date,
            $attachment_1_name,
            $attachment_2_name,
            $attachment_3_name,
            $attachment_4_name,
            $Session_username,
            $id
        );
    } else {
        // Insert new assessment
        $sql = "INSERT INTO assessments (
            programme_id, 
            batch_id, 
            module_id, 
            year_id, 
            semester_id, 
            description,
            main_component_id,
            sub_component_id,
            assessment_date,
            attachment_1, 
            attachment_2, 
            attachment_3, 
            attachment_4,
            entered_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiisssiissssss",
            $programme_id,
            $batch_id,
            $module_id,
            $year_id,
            $semester_id,
            $description,
            $main_component_id,
            $sub_component_id,
            $assessment_date,
            $attachment_1_name,
            $attachment_2_name,
            $attachment_3_name,
            $attachment_4_name,
            $Session_username
        );
    }

    if ($stmt->execute()) {
        $message = $isEdit ? "Assessment updated successfully!" : "Assessment saved successfully!";
        echo "<script>alert('$message'); window.location.href='../exams';</script>";
    } else {
        $error = $isEdit ? "Error updating assessment: " : "Error saving assessment: ";
        echo "<script>alert('" . $error . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request!'); window.history.back();</script>";
}
