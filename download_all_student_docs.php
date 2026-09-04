<?php
ob_start(); // Prevent any accidental output from breaking headers
session_start();
include("database/connection.php");

// Enable error reporting for debugging on hosting (output to log, not screen)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['username'])) {
    die("Unauthorized access");
}

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id == 0) {
    die("Invalid Student ID");
}

// 1. Fetch Student Data
$stmt = $conn->prepare("SELECT * FROM students_temporary_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$reg_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg_data) {
    die("Student not found");
}

$temp_id = $reg_data['temp_id'];
$program = $reg_data['program'];
$batch = $reg_data['batch'];

// Helper to clean names - ensuring consistency with allocation logic
$program_clean_uploaded = preg_replace('/[^A-Za-z0-9]/', '', $program);
$batch_clean_uploaded = preg_replace('/[^A-Za-z0-9]/', '', $batch);

$program_clean_gen = preg_replace('/[^A-Za-z0-9_\-]/', '', $program);
if (empty($program_clean_gen))
    $program_clean_gen = "General";
$batch_clean_gen = preg_replace('/[^A-Za-z0-9_\-]/', '', $batch);
if (empty($batch_clean_gen))
    $batch_clean_gen = "Batch";

$temp_id_clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_id);

// Directory paths
$uploaded_dir = __DIR__ . "/uploaded_documents/" . $program_clean_uploaded . "/" . $batch_clean_uploaded . "/" . $temp_id_clean;
$generated_dir = __DIR__ . "/application_pdfs/" . $program_clean_gen . "/" . $batch_clean_gen . "/" . $temp_id_clean;

// Check if ZipArchive is installed
if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive extension is not enabled on this server. Please contact hosting support.");
}

$zip = new ZipArchive();
$zip_filename = "Student_Docs_" . preg_replace('/[^A-Za-z0-9]/', '_', $reg_data['fullname']) . "_" . $temp_id_clean . ".zip";

// Use a local temp directory instead of system temp to avoid open_basedir restrictions
$local_temp_dir = __DIR__ . "/temp_zip";
if (!is_dir($local_temp_dir)) {
    mkdir($local_temp_dir, 0755, true);
}
$zip_path = $local_temp_dir . "/" . $zip_filename;

if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $file_added = false;

    // Add uploaded documents
    if (is_dir($uploaded_dir)) {
        $files = scandir($uploaded_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if ($zip->addFile($uploaded_dir . "/" . $file, "Uploaded_Documents/" . $file)) {
                    $file_added = true;
                }
            }
        }
    }

    // Add generated documents
    if (is_dir($generated_dir)) {
        $files = scandir($generated_dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if ($zip->addFile($generated_dir . "/" . $file, "Generated_Documents/" . $file)) {
                    $file_added = true;
                }
            }
        }
    }

    $zip->close();

    if (!$file_added) {
        // Optional: delete empty zip
        if (file_exists($zip_path))
            unlink($zip_path);
        die("No documents found for this student to download.");
    }

    // Stream the file
    if (file_exists($zip_path)) {
        // Clear buffer before sending headers
        ob_end_clean();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');

        readfile($zip_path);

        // Delete the temporary zip file after download
        unlink($zip_path);
        exit;
    } else {
        die("Failed to create zip file at: " . $zip_path);
    }
} else {
    die("Could not open zip archive for creation. Check folder permissions.");
}
?>
