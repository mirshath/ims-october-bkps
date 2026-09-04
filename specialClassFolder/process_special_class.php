<?php
session_start();
include("../database/connection.php");

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Validate required fields
$requiredFields = ['programme', 'batch', 'module', 'dateTime', 'mailSubject', 'description'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
        exit;
    }
}

// Get form data
$programmeId = mysqli_real_escape_string($conn, $_POST['programme']);
$batchId = mysqli_real_escape_string($conn, $_POST['batch']);
$moduleId = mysqli_real_escape_string($conn, $_POST['module']);
$dateTime = mysqli_real_escape_string($conn, $_POST['dateTime']);
$timeInput = isset($_POST['timeInput']) ? mysqli_real_escape_string($conn, $_POST['timeInput']) : '';
$link = isset($_POST['link']) ? mysqli_real_escape_string($conn, $_POST['link']) : '';
$mailSubject = mysqli_real_escape_string($conn, $_POST['mailSubject']);
$description = mysqli_real_escape_string($conn, $_POST['description']);
$enteredBy = mysqli_real_escape_string($conn, $_SESSION['username']);

// Get programme name
$programmeQuery = "SELECT program_name FROM program_table WHERE program_code = ?";
$stmtProgramme = mysqli_prepare($conn, $programmeQuery);
mysqli_stmt_bind_param($stmtProgramme, "i", $programmeId);
mysqli_stmt_execute($stmtProgramme);
$programmeResult = mysqli_stmt_get_result($stmtProgramme);
$programmeName = '';
if ($programmeRow = mysqli_fetch_assoc($programmeResult)) {
    $programmeName = $programmeRow['program_name'];
}

// Get batch name
$batchQuery = "SELECT batch_name FROM batch_table WHERE id = ?";
$stmtBatch = mysqli_prepare($conn, $batchQuery);
mysqli_stmt_bind_param($stmtBatch, "i", $batchId);
mysqli_stmt_execute($stmtBatch);
$batchResult = mysqli_stmt_get_result($stmtBatch);
$batchName = '';
if ($batchRow = mysqli_fetch_assoc($batchResult)) {
    $batchName = $batchRow['batch_name'];
}

// Get module name
$moduleQuery = "SELECT module_name FROM modules WHERE id = ?";
$stmtModule = mysqli_prepare($conn, $moduleQuery);
mysqli_stmt_bind_param($stmtModule, "i", $moduleId);
mysqli_stmt_execute($stmtModule);
$moduleResult = mysqli_stmt_get_result($stmtModule);
$moduleName = '';
if ($moduleRow = mysqli_fetch_assoc($moduleResult)) {
    $moduleName = $moduleRow['module_name'];
}

// Insert into special_class_messages table (you'll need to create this table)
$insertQuery = "INSERT INTO special_class_messages (programme_id, programme_name, batch_id, batch_name, 
                module_id, module_name, class_date, class_time, meeting_link, mail_subject, 
                description, entered_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = mysqli_prepare($conn, $insertQuery);
mysqli_stmt_bind_param($stmt, "isisississs", 
    $programmeId, $programmeName, $batchId, $batchName, 
    $moduleId, $moduleName, $dateTime, $timeInput, $link, 
    $mailSubject, $description, $enteredBy);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'message' => 'Special class message saved successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save special class message: ' . mysqli_error($conn)]);
}

exit;
