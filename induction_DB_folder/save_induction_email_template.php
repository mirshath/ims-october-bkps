<?php
session_start();
include '../database/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$programmeId = isset($_POST['programme_id']) ? trim($_POST['programme_id']) : '';
$batchId = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$time = isset($_POST['time']) ? trim($_POST['time']) : '';
$dressCode = isset($_POST['dress_code']) ? trim($_POST['dress_code']) : '';
$importantNote = isset($_POST['important_note']) ? trim($_POST['important_note']) : '';
$emailBody = isset($_POST['email_body']) ? $_POST['email_body'] : '';
$createdBy = $_SESSION['username'];
$editId = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

// Validate required fields
if (!$programmeId || !$batchId || empty($emailBody) || empty($date) || empty($time) || empty($dressCode) || empty($importantNote)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

$bannerPath = '';
$existingBannerPath = '';
if ($editId > 0) {
    $checkExisting = $conn->prepare("SELECT banner_image_path FROM induction_email_body_db_table WHERE id = ?");
    $checkExisting->bind_param("i", $editId);
    $checkExisting->execute();
    $existing = $checkExisting->get_result()->fetch_assoc();
    $checkExisting->close();
    if ($existing) {
        $existingBannerPath = $existing['banner_image_path'];
    }
}

// Handle file upload
if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/induction_banners/';
    $publicUploadDir = 'uploads/induction_banners/';
    
    // Create directory if doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExt = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('banner_', true) . '.' . $fileExt;
    $targetFile = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $targetFile)) {
        $bannerPath = $publicUploadDir . $fileName;
    }
} else if ($editId > 0 && !empty($existingBannerPath)) {
    // If editing and no new banner, keep existing
    $bannerPath = $existingBannerPath;
}

// If still no banner path (only require for new template), error
if (empty($bannerPath) && $editId == 0) {
    echo json_encode(['success' => false, 'message' => 'Banner Image is required']);
    exit;
}

try {
    if ($editId > 0) {
        // Update existing record
        // We already got existingBannerPath earlier
        
        $updateQuery = "UPDATE induction_email_body_db_table 
                        SET program_id = ?, batch_id = ?, banner_image_path = ?, email_body = ?, date = ?, time = ?, dress_code = ?, important_note = ?, updated_at = NOW()
                        WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sissssssi", $programmeId, $batchId, $bannerPath, $emailBody, $date, $time, $dressCode, $importantNote, $editId);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
        
    } else {
        // Check if a template already exists for this program + batch
        $checkQuery = "SELECT id FROM induction_email_body_db_table WHERE program_id = ? AND batch_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("si", $programmeId, $batchId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A template already exists for this program and batch']);
            exit;
        }
        $checkStmt->close();
        
        // Insert new record
        $insertQuery = "INSERT INTO induction_email_body_db_table 
                        (program_id, batch_id, banner_image_path, email_body, date, time, dress_code, important_note, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sisssssss", $programmeId, $batchId, $bannerPath, $emailBody, $date, $time, $dressCode, $importantNote, $createdBy);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();
?>
