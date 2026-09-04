<?php
// =====================================================================
// google-form/upload_image.php
// AJAX endpoint used ONLY from the logged-in form builder
// (create_form.php). It uploads ONE image at a time and immediately
// returns the stored path, so the builder page can show a preview
// and attach that path to the form/question before "Save Form" is
// even clicked.
//
// type=banner   -> saved into uploads/banners/
// type=question -> saved into uploads/questions/
// =====================================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image received']);
    exit();
}

$type = $_POST['type'] ?? '';
$allowedTypes = ['banner' => 'banners', 'question' => 'questions'];
if (!isset($allowedTypes[$type])) {
    echo json_encode(['success' => false, 'message' => 'Invalid upload type']);
    exit();
}
$subfolder = $allowedTypes[$type];

$file = $_FILES['image'];

// -------- Basic upload error check --------
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed (error code ' . $file['error'] . ')']);
    exit();
}

// -------- Size limit: 5 MB --------
$maxBytes = 5 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'message' => 'Image is larger than 5 MB']);
    exit();
}

// -------- Validate it is actually an image, and read its real type
//          (never trust the client-supplied MIME type / extension) --------
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    echo json_encode(['success' => false, 'message' => 'File is not a valid image']);
    exit();
}

$allowedMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$mime = $imageInfo['mime'];
if (!isset($allowedMime[$mime])) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, or WEBP images are allowed']);
    exit();
}
$ext = $allowedMime[$mime];

// -------- Generate a random, collision-safe filename (never trust the
//          original filename from the client) --------
$filename = bin2hex(random_bytes(16)) . '.' . $ext;

$targetDir = __DIR__ . "/uploads/{$subfolder}/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
$targetPath = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Could not save the uploaded file']);
    exit();
}

// Path is relative to the google-form/ folder, which is how
// view_form.php / save_form.php reference it. create_form.php (one
// level up) prefixes it with "google-form/" when it builds <img src>.
$relativePath = "uploads/{$subfolder}/{$filename}";

echo json_encode(['success' => true, 'path' => $relativePath]);
