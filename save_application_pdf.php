<?php
session_start();
header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or upload error occurred'
    ]);
    exit;
}

// Get form data
$studentCode = isset($_POST['student_code']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $_POST['student_code']) : 'unknown';
$tempId = isset($_POST['temp_id']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $_POST['temp_id']) : 'unknown';
$studentName = isset($_POST['student_name']) ? preg_replace('/[^A-Za-z0-9_\- ]/', '_', $_POST['student_name']) : 'unknown';
$program = isset($_POST['program']) ? preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['program']) : 'General';
$batch = isset($_POST['batch']) ? preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['batch']) : 'General';

// Define base directory for storing PDFs
$baseDir = dirname(__FILE__) . '/application_pdfs';

// Create directory structure: application_pdfs/Program/Batch/TempId/
$cleanProgram = preg_replace('/[^A-Za-z0-9_\-]/', '', $program);
if (empty($cleanProgram))
    $cleanProgram = "General";
$cleanBatch = preg_replace('/[^A-Za-z0-9_\-]/', '', $batch);
if (empty($cleanBatch))
    $cleanBatch = "Batch";
$cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tempId);

$studentDir = $baseDir . '/' . $cleanProgram . '/' . $cleanBatch . '/' . $cleanTempId;

// Robust directory creation
if (!is_dir($studentDir)) {
    if (!mkdir($studentDir, 0755, true)) {
        // Fallback to base dir if creation fails
        $studentDir = $baseDir;
        if (!is_dir($studentDir)) {
            mkdir($studentDir, 0755, true);
        }
    }
}

if (!is_writable($studentDir)) {
    // If not writable, try base dir
    $studentDir = $baseDir;
}

// Get identification
$identification = isset($_POST['identification']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $_POST['identification']) : '';

// Generate filename
$filename = 'Application_' . $studentCode . '_' . str_replace(' ', '_', $studentName) . (!empty($identification) ? '_' . $identification : '') . '.pdf';
$filePath = $studentDir . '/' . $filename;

// Move uploaded file to destination
if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $filePath)) {
    // Get relative path for display
    $relativePath = 'application_pdfs/' . $program . '/' . $batch . '/' . $filename;

    echo json_encode([
        'success' => true,
        'message' => 'PDF saved successfully',
        'file_path' => $relativePath,
        'full_path' => $filePath,
        'filename' => $filename
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move uploaded file'
    ]);
}
