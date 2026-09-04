<?php
session_start();
include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_code = $_POST['programme'];
    $batch_id = $_POST['batch'];
    $module_id = $_POST['module'];
    $date = date('Y-m-d', strtotime($_POST['dateTime']));
    $time = $_POST['timeInput'];
    $link = mysqli_real_escape_string($conn, $_POST['link']);
    $mail_subject = mysqli_real_escape_string($conn, $_POST['mailSubject']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Check if this is an update (edit_id is set) or a new record
    if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        
        $updateQuery = "UPDATE special_class_messages SET 
            program_code = '$program_code', 
            batch_id = '$batch_id', 
            module_id = '$module_id', 
            class_date = '$date', 
            class_time = '$time', 
            link = '$link', 
            mail_subject = '$mail_subject', 
            description = '$description' 
            WHERE id = $id";
            
        if (mysqli_query($conn, $updateQuery)) {
            echo json_encode(['status' => 'success', 'message' => 'Special class updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error updating record: ' . mysqli_error($conn)]);
        }
    } else {
        // This is a new record
        $insertQuery = "INSERT INTO special_class_messages 
            (program_code, batch_id, module_id, class_date, class_time, link, mail_subject, description) 
            VALUES ('$program_code', '$batch_id', '$module_id', '$date', '$time', '$link', '$mail_subject', '$description')";

        if (mysqli_query($conn, $insertQuery)) {
            echo json_encode(['status' => 'success', 'message' => 'Special class saved successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error saving record: ' . mysqli_error($conn)]);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>
