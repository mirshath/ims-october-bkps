<?php
// Secure document upload backend (supports doc0, Passport Size Photo etc.)
// upload_doc_backend.php (FULL QUALITY VERSION - NO COMPRESSION)
include("../database/connection.php");

// -------------------------------
// Validate POST parameters
// -------------------------------
$requiredPost = ['temp_id', 'nic', 'token', 'id'];
foreach ($requiredPost as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field]) || $_POST[$field] === "") {
        echo "<script>alert('Missing or invalid parameter: $field'); window.location.href='https://www.bms.ac.lk/';</script>";
        exit();
    }
}

$id = $_POST['id'];
$temp_id = $_POST['temp_id'];
$nic = $_POST['nic'];
$token = $_POST['token'];

// -------------------------------
// Get program and batch for folder hierarchy
// -------------------------------
$program = '';
$batch = '';

try {
    $stmt = $conn->prepare("SELECT program, batch FROM students_temporary_registration WHERE id=? LIMIT 1");
    if (!$stmt)
        throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows < 1) {
        echo "<script>alert('Invalid or expired upload link.'); window.location.href='https://www.bms.ac.lk/';</script>";
        exit();
    }
    $row = $res->fetch_assoc();
    $program = isset($row['program']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['program']) : 'unknown_program';
    $batch = isset($row['batch']) ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['batch']) : 'unknown_batch';
} catch (Exception $e) {
    error_log("UPLOAD BACKEND VALIDATION (program-batch): " . $e->getMessage());
    echo "<script>alert('System error. Please try again later.'); window.location.href='https://www.bms.ac.lk/';</script>";
    exit();
}

// -------------------------------
// Fetch existing uploaded documents and ensure student_auto_id stored
// -------------------------------
$uploadedDocs = [
    'doc0' => null,
    'doc1' => null,
    'doc2' => null,
    'doc3' => null,
    'doc4' => null,
    'degree_certificate' => null,
    'transcript' => null,
    'other_qualification_1' => null,
    'other_qualification_2' => null
];
$currentUploads = 0;
$documentRowExists = false;

try {
    $stmt = $conn->prepare("SELECT id, student_auto_id, temp_id, nic, doc0, doc1, doc2, doc3, doc4, degree_certificate, transcript, other_qualification_1, other_qualification_2, uploaded_count, created_at FROM students_temporary_document WHERE temp_id=? AND nic=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $temp_id, $nic);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            foreach (['doc0', 'doc1', 'doc2', 'doc3', 'doc4', 'degree_certificate', 'transcript', 'other_qualification_1', 'other_qualification_2'] as $d) {
                if (!empty($row[$d]))
                    $uploadedDocs[$d] = $row[$d];
            }
            $currentUploads = (int) $row['uploaded_count'];
            $documentRowExists = true;
            // If student_auto_id is missing or empty, update it now
            if (empty($row['student_auto_id']) && !empty($id)) {
                $updateId = $conn->prepare("UPDATE students_temporary_document SET student_auto_id=? WHERE id=?");
                if ($updateId) {
                    $updateId->bind_param("ii", $id, $row['id']);
                    $updateId->execute();
                    $updateId->close();
                }
            }
        } else {
            // First upload: create row (insert student_auto_id as $id too)
            $insert = $conn->prepare("INSERT INTO students_temporary_document (student_auto_id, temp_id, nic, uploaded_count) VALUES (?, ?, ?, 0)");
            if ($insert) {
                $insert->bind_param("iss", $id, $temp_id, $nic);
                $insert->execute();
                $insert->close();
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("UPLOAD FETCH EXISTING ERROR: " . $e->getMessage());
}

// -------------------------------
// Check max upload attempts
// -------------------------------
if ($currentUploads >= 3) {
    echo "<script>alert('Maximum upload attempts reached (3).'); window.location.href='https://www.bms.ac.lk/';</script>";
    exit();
}

// -------------------------------
// Prepare upload folder structure
// -------------------------------
$baseFolder = "../uploaded_documents";

function makeFolderNamePretty($name)
{
    // Only letters/numbers/spaces/paren allowed; condense spaces
    $pretty = preg_replace('/[^A-Za-z0-9 ()]/', '', $name);
    $pretty = preg_replace('/\s+/', ' ', $pretty);
    return trim($pretty);
}

$programFolderName = makeFolderNamePretty($program);
$batchFolderName = makeFolderNamePretty($batch);
$cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_id);

$programFolder = $baseFolder . "/" . $programFolderName;
$batchFolder = $programFolder . "/" . $batchFolderName;
$folder = $batchFolder . "/" . $cleanTempId;

// Ensure folders exist
foreach ([$baseFolder, $programFolder, $batchFolder, $folder] as $f) {
    if (!is_dir($f)) {
        if (!mkdir($f, 0777, true) && !is_dir($f)) {
            error_log("Failed to create upload folder: $f");
            echo "<script>alert('Could not create upload folder structure.'); window.location.href='https://www.bms.ac.lk/';</script>";
            exit();
        }
    }
}

// -------------------------------
// File upload helper - PRESERVES ORIGINAL QUALITY
// -------------------------------
function saveFile_correctly($field, $folder, $imageOnly = false, $preferredName = null)
{
    if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
        // Define allowed extensions
        $allowed = $imageOnly ? ['jpg', 'jpeg', 'png'] : ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));

        // Validate extension
        if (!in_array($ext, $allowed)) {
            error_log("Invalid file extension for $field: $ext");
            return null;
        }

        // Validate file size (3MB)
        $maxSize = 3 * 1024 * 1024;
        if ($_FILES[$field]['size'] > $maxSize) {
            error_log("File too large for $field: " . $_FILES[$field]['size'] . " bytes");
            return null;
        }

        // Validate MIME type for images
        if ($imageOnly || in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES[$field]['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimes)) {
                error_log("Invalid MIME type for $field: $mimeType");
                return null;
            }
        }

        // Generate filename: use preferred name if provided, else use field name
        // Added a short random suffix to prevent issues if same-named files are cached
        $nameBase = $preferredName ?: $field;
        $newName = $nameBase . '_' . substr(md5(time()), 0, 5) . '.' . $ext;
        $targetPath = $folder . '/' . $newName;

        if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
            chmod($targetPath, 0644);
            error_log("Successfully uploaded $field as $newName");
            return $newName;
        } else {
            error_log("Failed to move uploaded file for $field");
            return null;
        }
    }
    return null;
}

// -------------------------------
// Process uploaded files (doc0...doc4)
// -------------------------------
$fileInputs = [
    'doc0' => true,  // only images (passport photo - already cropped as PNG from frontend)
    'doc1' => false, // NIC - PDF or image
    'doc2' => false, // Passport - PDF or image
    'doc3' => false, // O/L Result - PDF or image
    'doc4' => false, // A/L Result - PDF or image
    'degree_certificate' => false,
    'transcript' => false,
    'other_qualification_1' => false,
    'other_qualification_2' => false
];
$updatedFiles = [];
$atLeastOneFileChanged = false;


$fieldToName = [
    'doc0' => 'passport_photo',
    'doc1' => 'nic',
    'doc2' => 'passport',
    'doc3' => 'ol_result',
    'doc4' => 'al_result',
    'degree_certificate' => 'degree_certificate',
    'transcript' => 'transcript',
    'other_qualification_1' => 'other_qualification_1',
    'other_qualification_2' => 'other_qualification_2'
];

foreach ($fileInputs as $field => $imgOnly) {
    $existing = $uploadedDocs[$field] ?? null;
    if (!empty($_FILES[$field]['name'])) {
        $preferredName = isset($fieldToName[$field]) ? $fieldToName[$field] : $field;
        $saved = saveFile_correctly($field, $folder, $imgOnly, $preferredName);
        if ($saved) {
            $updatedFiles[$field] = $saved;
            $atLeastOneFileChanged = true;
        } else {
            // If upload failed, keep existing file
            $updatedFiles[$field] = $existing;
        }
    } else {
        // No new file uploaded, keep existing
        $updatedFiles[$field] = $existing;
    }
}

// -------------------------------
// Require Document 1 (doc1, passport/NIC scan)
// -------------------------------
if (empty($updatedFiles['doc1'])) {
    echo "<script>alert('Document 1 (Passport or NIC Scan) is required.'); window.history.back();</script>";
    exit();
}

// -------------------------------
// Save update to DB
// -------------------------------
try {
    $newCount = $currentUploads;
    if ($atLeastOneFileChanged)
        $newCount++;

    $stmt = $conn->prepare(
        "UPDATE students_temporary_document SET doc0=?, doc1=?, doc2=?, doc3=?, doc4=?, degree_certificate=?, transcript=?, other_qualification_1=?, other_qualification_2=?, uploaded_count=? WHERE student_auto_id=?"
    );
    if (!$stmt)
        throw new Exception("Prepare update failed: " . $conn->error);

    $stmt->bind_param(
        "sssssssssii",
        $updatedFiles['doc0'],
        $updatedFiles['doc1'],
        $updatedFiles['doc2'],
        $updatedFiles['doc3'],
        $updatedFiles['doc4'],
        $updatedFiles['degree_certificate'],
        $updatedFiles['transcript'],
        $updatedFiles['other_qualification_1'],
        $updatedFiles['other_qualification_2'],
        $newCount,
        $id
    );
    $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    error_log("UPLOAD SAVE ERROR: " . $e->getMessage());
    echo "<script>alert('Failed to save upload. Try again.'); window.location.href='https://www.bms.ac.lk/';</script>";
    exit();
}

// -------------------------------
// Success
// -------------------------------
// echo "<script>
//     alert('Document(s) uploaded successfully!');
//     window.location.href='upload_document.php?temp_id=" . urlencode($temp_id) . "&nic=" . urlencode($nic) . "&token=" . urlencode($token) . "';
// </script>";
echo "<script>
    alert('Document(s) uploaded successfully!');
    window.location.href='https://www.bms.ac.lk/';
</script>";
exit();
