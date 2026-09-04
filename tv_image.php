<?php


// image_manager.php - Complete image manager for BMSTV
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();


include("database/connection.php");
if (!isset($_SESSION['username'])) {
        // header("location: login.php");
        echo '<script>window.location.href = "login";</script>';
        // exit();
    }

// Configuration
define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'bmstv' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR);
$allowedFolders = ['brochures', 'events'];
$currentFolder = isset($_GET['folder']) && in_array($_GET['folder'], $allowedFolders) ? $_GET['folder'] : 'brochures';

// Create directories if they don't exist
foreach ($allowedFolders as $folder) {
    $dir = BASE_PATH . $folder . DIRECTORY_SEPARATOR;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $action = $_GET['action'] ?? '';
    
    // List images
    if ($action === 'list') {
        $folder = $_GET['folder'] ?? $currentFolder;
        $targetDir = BASE_PATH . $folder . DIRECTORY_SEPARATOR;
        
        $images = [];
        if (is_dir($targetDir)) {
            $files = scandir($targetDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $images[] = [
                            'name' => $file,
                            'url' => 'bmstv/images/' . $folder . '/' . rawurlencode($file),
                            'size' => filesize($targetDir . $file)
                        ];
                    }
                }
            }
        }
        echo json_encode(['status' => 'success', 'images' => $images]);
        exit;
    }
    
    // Upload images
    if ($action === 'upload') {
        $folder = $_POST['folder'] ?? $currentFolder;
        $targetDir = BASE_PATH . $folder . DIRECTORY_SEPARATOR;
        
        // Ensure directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $uploadedCount = 0;
        $uploadedFiles = [];
        $errors = [];
        
        if (isset($_FILES['images'])) {
            $fileCount = count($_FILES['images']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $errorCode = $_FILES['images']['error'][$i];
                $originalName = $_FILES['images']['name'][$i];
                
                if ($errorCode == UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['images']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $fileSize = $_FILES['images']['size'][$i];
                    
                    // Validate file size (10MB max)
                    if ($fileSize > 10 * 1024 * 1024) {
                        $errors[] = "{$originalName}: File size exceeds 10MB limit";
                        continue;
                    }
                    
                    // Validate file extension
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $errors[] = "{$originalName}: Invalid file type. Only JPG, PNG, GIF, WEBP allowed";
                        continue;
                    }
                    
                    // Validate image
                    $checkImage = @getimagesize($tmpName);
                    if ($checkImage === false) {
                        $errors[] = "{$originalName}: File is not a valid image";
                        continue;
                    }
                    
                    // Generate unique filename
                    $safeName = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                    $destination = $targetDir . $safeName;
                    
                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadedCount++;
                        $uploadedFiles[] = $safeName;
                    } else {
                        $errors[] = "{$originalName}: Failed to save file. Check folder permissions";
                    }
                } else {
                    // Handle upload errors
                    $errorMessage = getUploadErrorMessage($errorCode);
                    $errors[] = "{$originalName}: {$errorMessage}";
                }
            }
        }
        
        $response = ['status' => 'success', 'uploaded' => $uploadedCount];
        if ($uploadedCount > 0) {
            $response['message'] = "Successfully uploaded {$uploadedCount} file(s)";
            if (!empty($errors)) {
                $response['errors'] = $errors;
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = !empty($errors) ? implode('; ', $errors) : 'No files were uploaded';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Delete image
    if ($action === 'delete') {
        $folder = $_POST['folder'] ?? $currentFolder;
        $filename = basename($_POST['filename'] ?? '');
        $filePath = BASE_PATH . $folder . DIRECTORY_SEPARATOR . $filename;
        
        if (file_exists($filePath) && unlink($filePath)) {
            echo json_encode(['status' => 'success', 'message' => 'File deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete file']);
        }
        exit;
    }
    
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Helper function for upload error messages
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'File exceeds server upload limit';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds form upload limit';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was selected';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown upload error';
    }
}

// Download file
if (isset($_GET['download'])) {
    $folder = $_GET['folder'] ?? $currentFolder;
    $filename = basename($_GET['file'] ?? '');
    $filePath = BASE_PATH . $folder . DIRECTORY_SEPARATOR . $filename;
    
    if (file_exists($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        readfile($filePath);
        exit;
    }
    die('File not found');
}


 include("includes/header.php");
 
// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMSTV Image Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        /* .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 0; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); } */
        .folder-card { cursor: pointer; transition: all 0.3s; border-radius: 15px; background: white; padding: 10px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .folder-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .folder-card.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .folder-card.active .text-muted { color: rgba(255,255,255,0.8) !important; }
        .image-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        .image-item { background: white; border-radius: 10px; overflow: hidden; width: 200px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s; }
        .image-item:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .image-item img { width: 100%; height: 160px; object-fit: cover; cursor: pointer; background: #f8f9fa; }
        .image-info { padding: 10px; background: white; }
        .image-name { font-size: 12px; margin-bottom: 8px; word-break: break-all; }
        .btn-group-custom { display: flex; gap: 5px; justify-content: space-between; }
        .upload-area { border: 2px dashed #cfc9cc; border-radius: 10px; padding: 10px; width: 100%; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s; margin-bottom: 20px; }
        .upload-area:hover { border-color: #667eea; background: #f0f2ff; }
        .upload-area.drag-over { border-color: #28a745; background: #d4edda; }
        .upload-area.uploading { opacity: 0.6; cursor: wait; pointer-events: none; }
        .modal-preview { max-width: 90%; max-height: 80vh; margin: auto; display: block; }
        .toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 9999; }
        .folder-icon { font-size: 30px; margin-bottom: 10px; }
        .loading { text-align: center; padding: 50px; }
        .spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .error-dialog { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; min-width: 300px; max-width: 500px; }
        .error-list { max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>

<div id="wrapper">
        <!-- Sidebar -->
        <?php include("nav.php"); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("includes/topnav.php"); ?>

                <!-- Begin Page Content -->
                <div class="p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">TV - Live Manager</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fa-solid fa-download"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Manage Images in Advertisement & Events Folders</h6>
                                </div>
                                <div class="card-body">

<!--  ==================================================================================================== -->
            <!-- <div style="text-align: right; color: #bebfca;">
                <i class="fas fa-folder-open"></i> Root: ims.bms.ac.lk/digital-media
            </div><br> -->
<!-- <div class="header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-images fa-2x me-2"></i> 
                <h1 class="d-inline-block h3 mb-0"></h1>
                <p class="mb-0 mt-2 small opacity-75">Manage Images in Advertisement & Events Folders</p>
            </div>
            
        </div>
    </div>
</div> -->

<div class="container">
    <!-- Folder Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="folder-card text-center" data-folder="brochures" id="folderBrochures">
                <i class="fas fa-folder-open folder-icon"></i>
                <h4 class="mb-0">💡 Advertisement and Brochures</h4>
                <small class="text-muted" id="brochuresCount">Loading...</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="folder-card text-center" data-folder="events" id="folderEvents">
                <i class="fas fa-folder-open folder-icon"></i>
                <h4 class="mb-0">💃🏻 Events and Celebrations</h4>
                <small class="text-muted" id="eventsCount">Loading...</small>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0" id="currentFolderTitle">
                <i class="fas fa-folder-open text-danger me-2"></i>
                <span id="folderName">brochures</span>
            </h5>
            <button class="btn btn-sm btn-outline-secondary" onclick="refreshImages()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <!-- Upload Area -->
            <div class="upload-area" id="uploadArea">
                <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-2"></i>
                <h6>Drag & Drop Images Here or Click to Upload</h6>
                <small class="text-muted">Supports JPG, PNG, GIF, WEBP (Max 10MB each)</small>
                <input type="file" id="fileInput" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" id="selectFileBtn">
                        <i class="fas fa-plus-circle"></i> Select Images
                    </button>
                </div>
            </div>

            <!-- Image Grid -->
            <div id="imageGrid" class="image-grid">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading images...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Error Dialog Modal -->
<div class="modal fade" id="errorDialog" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Upload Errors
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>The following errors occurred while uploading:</p>
                <div id="errorList" class="error-list"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-end">
                <button type="button" class="btn btn-light btn-sm rounded-circle" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
                <img id="previewImage" class="modal-preview mt-2 rounded shadow" alt="Preview">
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container">
    <div id="liveToast" class="toast" role="alert" data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-header">
            <i class="fas fa-info-circle me-2"></i>
            <strong class="me-auto">BMSTV Drive</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>


<!-- ===================================================================================== -->

 </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />


    </div>

        <!--  ==================================================================================== -->   

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentFolder = 'brochures';
let isUploading = false;

// Show error dialog with list of errors
function showErrorDialog(errors) {
    const errorList = document.getElementById('errorList');
    errorList.innerHTML = '';
    
    if (Array.isArray(errors)) {
        const ul = document.createElement('ul');
        ul.className = 'mb-0';
        errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            li.className = 'text-danger mb-2';
            ul.appendChild(li);
        });
        errorList.appendChild(ul);
    } else {
        errorList.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
    }
    
    const errorModal = new bootstrap.Modal(document.getElementById('errorDialog'));
    errorModal.show();
}

// Show toast notification
function showToast(message, isError = false) {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastMessage');
    const headerIcon = document.querySelector('#liveToast .toast-header i');
    const headerTitle = document.querySelector('#liveToast .toast-header strong');
    
    toastBody.textContent = message;
    if (isError) {
        headerIcon.className = 'fas fa-exclamation-triangle text-danger me-2';
        headerTitle.textContent = 'Error';
    } else {
        headerIcon.className = 'fas fa-check-circle text-success me-2';
        headerTitle.textContent = 'Success';
    }
    
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    setTimeout(() => {
        headerTitle.textContent = 'BMSTV Drive';
    }, 3000);
}

// Load images from folder
async function loadImages(folder) {
    const gridContainer = document.getElementById('imageGrid');
    gridContainer.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading images...</p></div>';
    
    try {
        const response = await fetch(`?action=list&folder=${folder}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await response.json();
        
        if (data.status === 'success' && data.images) {
            displayImages(data.images);
            updateFolderCounts();
        } else {
            throw new Error('Failed to load images');
        }
    } catch (error) {
        console.error(error);
        gridContainer.innerHTML = '<div class="text-center w-100 py-5 text-muted"><i class="fas fa-folder-open fa-3x mb-2"></i><br>No images found or server error<br><small>Make sure the folder exists and has proper permissions</small></div>';
        showToast('Error loading images: ' + error.message, true);
    }
}

// Display images in grid
function displayImages(images) {
    const gridContainer = document.getElementById('imageGrid');
    
    if (!images || images.length === 0) {
        gridContainer.innerHTML = '<div class="text-center w-100 py-5 text-muted"><i class="fas fa-image fa-3x mb-2"></i><br>No images in this folder<br><small>Upload some images using the area above</small></div>';
        return;
    }
    
    let html = '';
    images.forEach(img => {
        html += `
            <div class="image-item">
                <img src="${img.url}?t=${Date.now()}" alt="${escapeHtml(img.name)}" onclick="previewImage('${img.url}')" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'160\'%3E%3Crect width=\'200\' height=\'160\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                <div class="image-info">
                    <div class="image-name" title="${escapeHtml(img.name)}">${truncate(img.name, 20)}</div>
                    <div class="btn-group-custom">
                        <button class="btn btn-sm btn-outline-primary" onclick="downloadImage('${escapeHtml(img.name)}')">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteImage('${escapeHtml(img.name)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    gridContainer.innerHTML = html;
}

// Escape HTML special characters
function escapeHtml(str) {
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Truncate filename
function truncate(str, n) {
    return str.length > n ? str.substr(0, n-1) + '...' : str;
}

// Upload files
async function uploadFiles(files) {
    if (isUploading) {
        showToast('Upload in progress. Please wait...', true);
        return;
    }
    
    if (!files || files.length === 0) {
        showToast('No files selected', true);
        return;
    }
    
    const formData = new FormData();
    let validCount = 0;
    let validationErrors = [];
    
    for (let file of files) {
        if (!file.type.startsWith('image/')) {
            validationErrors.push(`${file.name}: Not an image file`);
            continue;
        }
        if (file.size > 10 * 1024 * 1024) {
            validationErrors.push(`${file.name}: File size exceeds 10MB limit`);
            continue;
        }
        formData.append('images[]', file);
        validCount++;
    }
    
    // Show validation errors if any
    if (validationErrors.length > 0) {
        showErrorDialog(validationErrors);
    }
    
    if (validCount === 0) {
        return;
    }
    
    formData.append('folder', currentFolder);
    
    // Set uploading state
    isUploading = true;
    const uploadArea = document.getElementById('uploadArea');
    uploadArea.classList.add('uploading');
    
    // Show uploading indicator
    showToast(`Uploading ${validCount} image(s)...`);
    
    try {
        const response = await fetch('?action=upload', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            if (data.uploaded > 0) {
                showToast(`Successfully uploaded ${data.uploaded} image(s)`);
                loadImages(currentFolder); // Refresh the grid
            }
            // Show any partial errors
            if (data.errors && data.errors.length > 0) {
                showErrorDialog(data.errors);
            }
        } else {
            throw new Error(data.message || 'Upload failed');
        }
    } catch (error) {
        showToast('Upload failed: ' + error.message, true);
        showErrorDialog([error.message]);
    } finally {
        isUploading = false;
        uploadArea.classList.remove('uploading');
    }
}

// Delete image
window.deleteImage = async function(filename) {
    if (!confirm(`Are you sure you want to delete "${filename}"?`)) return;
    
    const formData = new FormData();
    formData.append('filename', filename);
    formData.append('folder', currentFolder);
    
    try {
        const response = await fetch('?action=delete', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            showToast(`Deleted "${filename}"`);
            loadImages(currentFolder);
        } else {
            throw new Error(data.message || 'Delete failed');
        }
    } catch (error) {
        showToast('Delete failed: ' + error.message, true);
    }
};

// Download image
window.downloadImage = function(filename) {
    window.location.href = `?download=1&folder=${currentFolder}&file=${encodeURIComponent(filename)}`;
};

// Preview image
window.previewImage = function(url) {
    const img = document.getElementById('previewImage');
    img.src = url;
    img.onerror = function() {
        this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\'%3E%3Crect width=\'100%25\' height=\'100%25\' fill=\'%23ddd\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3ENo Image%3C/text%3E%3C/svg%3E';
    };
    new bootstrap.Modal(document.getElementById('previewModal')).show();
};

// Update folder counts
async function updateFolderCounts() {
    const folders = ['brochures', 'events'];
    for (let folder of folders) {
        try {
            const response = await fetch(`?action=list&folder=${folder}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            const count = data.images ? data.images.length : 0;
            const countElement = document.getElementById(`${folder}Count`);
            if (countElement) {
                countElement.textContent = `${count} image${count !== 1 ? 's' : ''}`;
            }
        } catch (e) {
            const countElement = document.getElementById(`${folder}Count`);
            if (countElement) {
                countElement.textContent = '0 images';
            }
        }
    }
}

// Switch folder
function switchFolder(folder) {
    if (isUploading) {
        showToast('Please wait for upload to complete before switching folders', true);
        return;
    }
    currentFolder = folder;
    document.getElementById('folderName').textContent = folder;
    
    // Update active state
    document.querySelectorAll('.folder-card').forEach(card => {
        card.classList.remove('active');
    });
    const folderId = folder === 'brochures' ? 'folderBrochures' : 'folderEvents';
    document.getElementById(folderId).classList.add('active');
    
    loadImages(folder);
}

// Refresh current folder
function refreshImages() {
    if (isUploading) {
        showToast('Please wait for upload to complete', true);
        return;
    }
    loadImages(currentFolder);
    updateFolderCounts();
}

// Initialize event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Folder click handlers
    document.getElementById('folderBrochures').addEventListener('click', () => switchFolder('brochures'));
    document.getElementById('folderEvents').addEventListener('click', () => switchFolder('events'));
    
    // Upload handlers
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const selectFileBtn = document.getElementById('selectFileBtn');
    
    // Handle select button click
    selectFileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.value = ''; // Reset file input
        fileInput.click();
    });
    
    // Handle upload area click
    uploadArea.addEventListener('click', (e) => {
        if (e.target === uploadArea || e.target.closest('.upload-area') && !e.target.closest('#selectFileBtn')) {
            fileInput.value = '';
            fileInput.click();
        }
    });
    
    // Handle file selection
    fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length > 0) {
            uploadFiles(e.target.files);
        }
        fileInput.value = ''; // Reset file input
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!isUploading) {
            uploadArea.classList.add('drag-over');
        }
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('drag-over');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        if (!isUploading) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadFiles(files);
            }
        } else {
            showToast('Upload in progress. Please wait...', true);
        }
    });
    
    // Load initial folder
    switchFolder('brochures');
});
</script>
</body>
</html>