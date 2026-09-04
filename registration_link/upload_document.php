<?php
include("../database/connection.php");

// Validate GET params
$requiredGet = ['temp_id', 'nic', 'token'];
foreach ($requiredGet as $field) {
    if (empty($_GET[$field]) || !is_string($_GET[$field])) {
        echo "<script>alert('Missing or invalid parameter: $field'); window.location.href='https://www.bms.ac.lk/';</script>";
        exit();
    }
}

$temp_id = $_GET['temp_id'];
$nic = $_GET['nic'];
$token = $_GET['token'];

// ----------------------------------------
// Clean folder names for folder structure
function clean_folder_name($name)
{
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    return $clean;
}
function clean_batch_name($name)
{
    return preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
}
function clean_tempid($id)
{
    return preg_replace('/[^A-Za-z0-9_\-]/', '_', $id);
}
// ----------------------------------------

// Lookup student registration info and fetch program/batch names (including std_entered_batch) and clean them for foldering
try {
    $fields = "id, temp_id, token, title, firstname, lastname, fullname, certificate_name, dob, gender, nationality, permanent_address, current_address, mobile, home_number, office_number, emergency_contact, nic, passport, email, program, batch, std_entered_batch, created_at, approved, approved_by";
    $stmt = $conn->prepare("SELECT $fields FROM students_temporary_registration WHERE temp_id = ? AND nic = ? AND token = ? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param("sss", $temp_id, $nic, $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows < 1) {
        echo "<script>alert('Invalid or expired link. Please register first.'); window.location.href='https://www.bms.ac.lk/';</script>";
        exit();
    }
    $row = $res->fetch_assoc();

    $id = $row['id'];
    $firstname = $row['firstname'];
    $lastname = $row['lastname'];
    $program = $row['program'];
    $batch = $row['batch'];
    $std_entered_batch = isset($row['std_entered_batch']) ? $row['std_entered_batch'] : null;

    $batch_for_usage = $batch;
    if (
        $std_entered_batch !== null
        && $std_entered_batch !== ''
        && $batch !== $std_entered_batch
    ) {
        $batch_for_usage = $std_entered_batch;
    }

    $clean_program = clean_folder_name($program);
    $clean_batch = clean_batch_name($batch_for_usage);
    $clean_temp_id = clean_tempid($temp_id);
} catch (Exception $e) {
    error_log("UPLOAD DOCUMENT LOOKUP: " . $e->getMessage());
    echo "<script>alert('System error. Please try again later.'); window.location.href='https://www.bms.ac.lk/';</script>";
    exit();
}

// --------- File Path Helpers ----------
function get_specific_passport_photo_path_for_tempid($clean_program, $clean_batch, $clean_temp_id, $temp_id)
{
    $abs_folder = dirname(__DIR__) . "/uploaded_documents/{$clean_program}/{$clean_batch}/{$clean_temp_id}/";
    $extensions = ['jpg', 'jpeg', 'png'];
    foreach ($extensions as $ext) {
        $filename = "{$clean_temp_id}.{$ext}";
        $filepath = $abs_folder . $filename;
        if (file_exists($filepath)) {
            return "uploaded_documents/{$clean_program}/{$clean_batch}/{$clean_temp_id}/{$filename}";
        }
    }

    global $conn;
    $stmt_fb = $conn->prepare("SELECT doc0 FROM students_temporary_document WHERE temp_id=? LIMIT 1");
    if ($stmt_fb) {
        $stmt_fb->bind_param("s", $temp_id);
        $stmt_fb->execute();
        $res_fb = $stmt_fb->get_result();
        if ($row_fb = $res_fb->fetch_assoc()) {
            $doc0val = $row_fb['doc0'];
            if (!empty($doc0val)) {
                $doc0_filename = basename($doc0val);
                $fb_path = $abs_folder . $doc0_filename;
                if (file_exists($fb_path)) {
                    return "uploaded_documents/{$clean_program}/{$clean_batch}/{$clean_temp_id}/{$doc0_filename}";
                }
            }
        }
    }
    return null;
}

function get_document_file_path($clean_program, $clean_batch, $clean_temp_id, $filename)
{
    if (empty($filename)) return null;
    $safe_filename = basename($filename);
    $abs_folder = dirname(__DIR__) . "/uploaded_documents/{$clean_program}/{$clean_batch}/{$clean_temp_id}/";
    $fullpath = $abs_folder . $safe_filename;
    if (file_exists($fullpath)) {
        return "uploaded_documents/{$clean_program}/{$clean_batch}/{$clean_temp_id}/{$safe_filename}";
    }
    return null;
}

$docFields = [
    'doc0' => 'Passport Size Photo',
    'doc1' => 'NIC',
    'doc2' => 'Passport',
    'doc3' => 'O/L Result',
    'doc4' => 'A/L Result',
    'degree_certificate' => 'Degree Certificate',
    'transcript' => 'Transcript',
    'other_qualification_1' => 'Other Qualification 1',
    'other_qualification_2' => 'Other Qualification 2'
];

$uploadedDocs = [];
try {
    $stmtDocs = $conn->prepare("SELECT doc0, doc1, doc2, doc3, doc4, degree_certificate, transcript, other_qualification_1, other_qualification_2 FROM students_temporary_document WHERE temp_id=? AND nic=? LIMIT 1");
    foreach (array_keys($docFields) as $k) $uploadedDocs[$k] = null;
    if ($stmtDocs) {
        $stmtDocs->bind_param("ss", $temp_id, $nic);
        $stmtDocs->execute();
        $resDocs = $stmtDocs->get_result();
        if ($rowDocs = $resDocs->fetch_assoc()) {
            foreach (array_keys($docFields) as $d) {
                if (!empty($rowDocs[$d])) {
                    if ($d === 'doc0') {
                        continue;
                    }
                    $webPath = get_document_file_path($clean_program, $clean_batch, $clean_temp_id, $rowDocs[$d]);
                    $uploadedDocs[$d] = $webPath ?: $rowDocs[$d];
                }
            }
        }
    }
    $photoPath = get_specific_passport_photo_path_for_tempid($clean_program, $clean_batch, $clean_temp_id, $temp_id);
    if ($photoPath) {
        $uploadedDocs['doc0'] = $photoPath;
    } else {
        if (!empty($rowDocs['doc0'])) {
            $uploadedDocs['doc0'] = get_document_file_path($clean_program, $clean_batch, $clean_temp_id, $rowDocs['doc0']) ?: $rowDocs['doc0'];
        }
    }
} catch (Exception $e) {
}

function get_file_url($path)
{
    if (preg_match('/^https?:\/\//i', $path)) return $path;
    return "../" . ltrim($path, "/");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>BMS Registration - Upload Documents</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.css" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #e9ecef 0%, #f7fafc 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .upload-container {
            max-width: 520px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            padding: 2.4rem 2rem 2rem 2rem;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        }

        .banner img {
            display: block;
            width: 100%;
            border-radius: 12px;
            margin-bottom: 18px;
            object-fit: cover;
        }

        h2 {
            text-align: center;
            color: #05427e;
            font-weight: 700;
            margin-bottom: 1.4rem;
        }

        .student-info {
            margin-bottom: 1.4rem;
        }

        .info-row {
            margin-bottom: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #1065ba;
            min-width: 110px;
            display: inline-block;
        }

        .info-value {
            color: #284250;
        }

        .accordion-list {
            border-radius: 12px;
            overflow: hidden;
            background: #f3f7fd;
            box-shadow: 0 2px 9px rgba(13, 41, 94, 0.03);
            margin-bottom: 1.3rem;
        }

        .accordion-block {
            border-bottom: 1px solid #e3e8f2;
            background: #f8fbfe;
        }

        .accordion-block:last-child {
            border-bottom: 0;
        }

        .accordion-title {
            cursor: pointer;
            padding: 0.96em 1.2em;
            background: #f3f7fd;
            color: #1767a8;
            font-weight: 600;
            font-size: 1.04rem;
            display: flex;
            align-items: center;
            letter-spacing: .02em;
            border: none;
            outline: none;
            width: 100%;
            text-align: left;
            transition: background 0.18s;
            user-select: none;
        }

        .accordion-title:after {
            content: "▼";
            margin-left: auto;
            font-size: 0.97em;
            color: #a6b7ce;
            transition: transform 0.19s;
        }

        .accordion-title.open:after {
            transform: rotate(180deg);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            background: #fff;
            transition: max-height 0.28s cubic-bezier(.32, .54, .34, 1), padding 0.19s;
            padding: 0 1.2em;
        }

        .accordion-content.show {
            padding: .9em 1.2em 1.44em 1.2em;
            max-height: 280px;
        }

        .upload-btn-label {
            display: inline-block;
            background: #3280e8;
            color: #fff;
            padding: 0.39em 1.14em;
            border-radius: 7px;
            font-size: 1.01rem;
            cursor: pointer;
            font-weight: 600;
            transition: background .17s;
            margin-top: 5px;
            margin-bottom: 7px;
        }

        .upload-btn-label:hover {
            background: #2256a6;
        }

        .file-input-visuallyhidden {
            display: none;
        }

        .uploaded-info {
            margin-top: 8px;
            font-size: .96rem;
        }

        .uploaded-link {
            color: #1f6ede;
            text-decoration: underline;
            font-weight: 500;
            margin-right: 8px;
            word-break: break-word;
        }

        .uploaded-img-modal {
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            cursor: pointer;
        }

        .uploaded-img-modal img {
            height: 34px;
            max-width: 105px;
            vertical-align: middle;
            object-fit: contain;
            border-radius: 3px;
            border: 1px solid #d3eafb;
            background: #fcfdff;
        }

        .no-upload {
            color: #b5b9c0;
            font-style: italic;
        }

        .file-preview {
            margin-top: 7px;
            font-size: .92rem;
            color: #3c6cc5;
            word-break: break-word;
        }

        /* MODAL CROP-UPDATES FOR RESPONSIVE FULL PAGE */
        .modal-cropper-bg,
        .modal-preview-bg {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            background: rgba(25, 37, 58, 0.25);
            width: 100vw;
            height: 100dvh;
            min-width: 100vw;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal-cropper {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 24px rgba(25, 47, 83, 0.23);
            width: 96vw;
            min-width: 220px;
            max-width: 480px;
            padding: 1em 0.5vw 1.1em 0.5vw;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            overflow-y: auto;
            max-height: 98dvh;
        }

        .modal-cropper-cropimg-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 94vw;
            max-width: 370px;
            height: 73vw;
            /* mobile-first: this gives portrait on short screens and fills well on tall phones */
            max-height: 63dvh;
            min-width: 150px;
            min-height: 220px;
            margin: 0 auto 10px auto;
            background: #f8faff;
            border-radius: 5px;
            position: relative;
            overflow: auto;
        }

        .modal-cropper-cropimg-container img.crop-image {
            display: block;
            margin: 0 auto;
            max-width: 98vw !important;
            max-height: 60vh !important;
            width: auto;
            height: auto;
            box-shadow: 0 1px 6px rgba(33, 44, 74, 0.13);
            border-radius: 7px;
            background: #f8faff;
        }

        .modal-cropper img:not(.crop-image) {
            max-width: 95vw !important;
            max-height: 53vh !important;
        }

        .modal-preview-bg {
            align-items: center;
            justify-content: center;
        }

        .modal-preview {
            background: #fff;
            border-radius: 8px;
            padding: 1.1em 1em 1em 1em;
            box-shadow: 0 3px 24px rgba(25, 47, 83, 0.26);
            width: 92vw;
            max-width: 400px;
            text-align: center;
            position: relative;
        }

        .modal-preview img {
            max-width: 97vw;
            max-height: 74vh;
            box-shadow: 0 1px 6px rgba(33, 44, 74, 0.11);
            border-radius: 7px;
        }

        .modal-preview-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #f9fafd;
            border: 0;
            border-radius: 18px;
            width: 31px;
            height: 31px;
            font-size: 1.4em;
            color: #3f4a68;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.14s;
            box-shadow: 0 1px 4px rgba(33, 44, 74, 0.08);
        }

        .modal-preview-close:hover {
            background: #eef7ff;
        }

        .modal-cropper .modal-btns {
            margin-top: 1em;
            display: flex;
            justify-content: center;
            gap: 1em;
            flex-wrap: wrap;
            width: 100%;
        }

        .modal-cropper button {
            font-family: inherit;
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            padding: .5em 1.1em;
            background: #3196ee;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            margin-right: 8px;
            transition: background .16s;
        }

        .modal-cropper button:last-child {
            margin-right: 0;
        }

        .modal-cropper button.cancel-btn {
            background: #fafbfc;
            color: #b22d2d;
            border: 1px solid #b22d2d;
        }

        .modal-cropper .modal-close-x {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 1.7em;
            background: none;
            border: none;
            color: #354;
            cursor: pointer;
            z-index: 15;
        }

        @media (max-width: 780px) {
            .modal-cropper {
                width: 100vw;
                max-width: none;
                padding: .7em .7vw 1vw .7vw;
            }

            .modal-cropper-cropimg-container {
                max-width: 98vw;
                width: 98vw;
                height: 74vw;
                max-height: 60vh;
            }
        }

        @media (max-width: 570px) {
            .upload-container {
                padding: 1.1rem .2rem;
            }

            .accordion-title {
                padding-left: .7em;
                padding-right: .7em;
            }

            .accordion-content {
                padding-left: .7em;
                padding-right: .7em;
            }

            .modal-cropper,
            .modal-preview {
                width: 100vw !important;
                max-width: 100vw !important;
            }

            .modal-cropper-cropimg-container {
                width: 100vw;
                max-width: 100vw;
                min-width: 110px;
                max-height: 360px;
                min-height: 120px;
            }

            .modal-cropper-cropimg-container img {
                max-width: 99vw !important;
            }

            .modal-cropper img,
            .modal-preview img {
                max-width: 97vw !important;
            }
        }

        @media (max-width: 410px) {
            .modal-cropper-cropimg-container {
                min-width: 80vw;
            }

            .modal-cropper {
                min-width: 90vw;
            }
        }
    </style>
</head>

<body>
    <div class="upload-container">
        <div class="banner">
            <img src="https://ims.bms.ac.lk//admin/uploads/img/Registration-form-Banner.jpg" alt="BMS Banner">
        </div>
        <h2>Document Upload</h2>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <div class="student-info">
            <table class="table table-striped table-bordered mb-3" style="background:#fff;">
                <tbody>
                    <tr>
                        <th scope="row" style="width:140px;">Name</th>
                        <td><?= htmlspecialchars($firstname) ?> <?= htmlspecialchars($lastname) ?></td>
                    </tr>
                    <tr>
                        <th scope="row">NIC</th>
                        <td><?= htmlspecialchars($nic) ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Program</th>
                        <td><?= htmlspecialchars($program) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
        try {
            $stmtCnt = $conn->prepare("SELECT uploaded_count FROM students_temporary_document WHERE temp_id=? AND nic=? LIMIT 1");
            $currentCount = 0;
            if ($stmtCnt) {
                $stmtCnt->bind_param("ss", $temp_id, $nic);
                $stmtCnt->execute();
                $resCnt = $stmtCnt->get_result();
                if ($rowCnt = $resCnt->fetch_assoc()) {
                    $currentCount = (int)$rowCnt['uploaded_count'];
                }
            }
        } catch (Exception $e) {
            $currentCount = 0;
        }
        ?>
        <div style="text-align:center; color:#13518e; font-size:1.01rem; margin-bottom:1.11rem;">
            Upload Attempts: <b><?= $currentCount ?></b> / 3 <span style="color:#999;"> <br>(You can edit/upload until the coordinator approves your documents.)</span>
        </div>
        <form id="uploadDocForm" action="upload_doc_backend.php" method="POST" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="temp_id" value="<?= htmlspecialchars($temp_id) ?>">
            <input type="hidden" name="nic" value="<?= htmlspecialchars($nic) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="accordion-list">
                <?php
                $first = true;
                $idx = 0;
                foreach ($docFields as $docKey => $docTitle):
                    $isRequired = ($docKey == 'doc0' || $docKey == 'doc1');
                ?>
                    <div class="accordion-block<?= $docKey == 'doc0' ? ' passport-photo-block' : '' ?>">
                        <button
                            type="button"
                            class="accordion-title<?= $first ? ' open' : '' ?>"
                            id="acc-title-<?= $docKey ?>"
                            data-target="acc-content-<?= $docKey ?>"
                            aria-expanded="<?= $first ? 'true' : 'false' ?>">
                            <?= htmlspecialchars($docTitle) ?>
                            <?php if ($docKey === 'doc0'): ?>
                                <span style="color: #b72509;font-size: .92rem;margin-left: .7em;">
                                    (Required: JPEG/PNG, 350x450px recommended; <b>Must be perfectly clear – face and shoulders must be fully visible and in focus.</b>)
                                </span>
                            <?php endif; ?>
                        </button>
                        <div class="accordion-content<?= $first ? ' show' : '' ?>" id="acc-content-<?= $docKey ?>">
                            <label class="upload-btn-label" for="<?= $docKey ?>">
                                Browse/Upload <?= htmlspecialchars($docTitle) ?>
                                <?= $isRequired ? ' <span style="color:#e10a0a;">*</span>' : '' ?>
                            </label>
                            <input
                                type="file"
                                class="file-input-visuallyhidden"
                                name="<?= $docKey ?>"
                                id="<?= $docKey ?>"
                                accept="<?= $docKey === 'doc0' ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png' ?>"
                                <?php if ($isRequired && empty($uploadedDocs[$docKey])) echo 'required'; ?>>
                            <div style="color:#838ba8; font-size:.97em; margin-top:-9px; margin-bottom:5px;">
                                <?php if ($docKey === 'doc0'): ?>
                                    Please upload a recent <b>passport size color photo</b> (max 2MB, <b>must be perfectly clear, not blurry, and face with full shoulders must be visible, with plain background</b>).
                                <?php endif; ?>
                            </div>
                            <!-- Preview uploaded file from storage (if exists) -->
                            <div class="uploaded-info">
                                <?php
                                if ($uploadedDocs[$docKey]) {
                                    $rawPath = $uploadedDocs[$docKey];
                                    $name = basename($rawPath);
                                    $fileUrl = get_file_url($rawPath);

                                    if ($docKey == 'doc0' && preg_match('/\.(jpg|jpeg|png)$/i', $name)) {
                                        echo '<span class="uploaded-img-modal uploaded-img-modal-' . $docKey . '" title="Click to preview">';
                                        echo '<img src="' . htmlspecialchars($fileUrl) . '" alt="Student Photo" data-full="' . htmlspecialchars($fileUrl) . '" style="cursor:pointer">';
                                        echo '</span>';
                                        echo htmlspecialchars($name);
                                    } elseif (preg_match('/\.(jpg|jpeg|png)$/i', $name)) {
                                        echo '<a href="' . htmlspecialchars($fileUrl) . '" class="uploaded-link uploaded-link-' . $docKey . '" data-open="' . htmlspecialchars($fileUrl) . '" target="_blank">';
                                        echo '<img src="' . htmlspecialchars($fileUrl) . '" alt="Uploaded image" title="' . htmlspecialchars($name) . '"> ';
                                        echo htmlspecialchars($name) . '</a>';
                                    } elseif (preg_match('/\.(pdf)$/i', $name)) {
                                        echo '<a href="' . htmlspecialchars($fileUrl) . '" class="uploaded-link uploaded-link-' . $docKey . '" data-open="' . htmlspecialchars($fileUrl) . '" target="_blank">';
                                        echo 'PDF: ' . htmlspecialchars($name) . '</a>';
                                    }
                                } else {
                                    echo '<span class="no-upload">No file uploaded yet.</span>';
                                }
                                ?>
                            </div>
                            <div id="preview-<?= $docKey ?>" class="file-preview"></div>
                        </div>
                    </div>
                <?php $first = false;
                    $idx++;
                endforeach; ?>
            </div>

            <?php
            $showUploadBtn = true;
            try {
                $stmtChk = $conn->prepare("SELECT uploaded_count FROM students_temporary_document WHERE temp_id=? AND nic=? LIMIT 1");
                if ($stmtChk) {
                    $stmtChk->bind_param("ss", $temp_id, $nic);
                    $stmtChk->execute();
                    $resChk = $stmtChk->get_result();
                    if ($rowChk = $resChk->fetch_assoc()) {
                        if ((int)$rowChk['uploaded_count'] >= 3) {
                            $showUploadBtn = false;
                            echo '<div class="alert">Maximum upload attempts reached (3 allowed).</div>';
                        }
                    }
                }
            } catch (Exception $e) {
            }
            if ($showUploadBtn): ?>

                <?php if (isset($row['approved']) && (int)$row['approved'] !== 1): ?>
                    <div style="text-align: right;">
                        <button type="submit" id="uploadSubmitBtn" style="display:inline-block;padding:0.56em 1.7em;background:#1875d2;color:#fff;border:none;border-radius:7px;font-size:1.07rem;font-weight:600;box-shadow:0 2px 8px rgba(20,50,90,0.07);cursor:pointer;transition:background .16s;letter-spacing:.01em;margin-top:12px;">
                            <span id="uploadBtnText" style="display:inline-block;padding-right:2px;">Upload Documents</span>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </form>
    </div>
    <script src="https://unpkg.com/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.accordion-title').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var contentId = btn.getAttribute('data-target');
                    var contentDiv = document.getElementById(contentId);

                    var isOpen = btn.classList.contains('open');
                    document.querySelectorAll('.accordion-title').forEach(function(b) {
                        b.classList.remove('open');
                        var cDiv = document.getElementById(b.getAttribute('data-target'));
                        if (cDiv) {
                            cDiv.classList.remove('show');
                        }
                    });
                    if (!isOpen) {
                        btn.classList.add('open');
                        contentDiv.classList.add('show');
                        btn.setAttribute('aria-expanded', 'true');
                    } else {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });

            document.querySelectorAll('.uploaded-link').forEach(function(a) {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.open(this.getAttribute('data-open') || this.href, '_blank', 'noopener');
                });
            });

            document.querySelectorAll('.uploaded-img-modal.doc0, .uploaded-img-modal.doc0 img, .uploaded-img-modal-doc0, .uploaded-img-modal-doc0 img').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var img = this.tagName.toLowerCase() === 'img' ? this : this.querySelector('img');
                    if (!img) return;
                    var src = img.getAttribute('data-full') || img.src;
                    showModalImagePreview(src);
                });
            });
            var passportImgs = document.querySelectorAll('.uploaded-img-modal-doc0 img, .uploaded-img-modal.doc0 img, .uploaded-img-modal-doc0, .uploaded-img-modal.doc0');
            if (passportImgs.length === 0) passportImgs = document.querySelectorAll('.uploaded-img-modal img');
            passportImgs.forEach(function(imgEl) {
                imgEl.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var src = imgEl.getAttribute('data-full') || imgEl.src;
                    showModalImagePreview(src);
                });
            });

            function showModalImagePreview(src) {
                var oldModal = document.querySelector('.modal-preview-bg');
                if (oldModal) oldModal.remove();
                var bg = document.createElement('div');
                bg.className = 'modal-preview-bg';
                var container = document.createElement('div');
                container.className = 'modal-preview';
                var closeBtn = document.createElement('button');
                closeBtn.className = 'modal-preview-close';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = function() {
                    bg.remove();
                };
                bg.onclick = function(e) {
                    if (e.target === bg) bg.remove();
                };
                var img = document.createElement('img');
                img.src = src;
                img.alt = "Preview";
                img.style = "display:block;max-width:98vw;max-height:78vh;margin:auto;";
                container.appendChild(closeBtn);
                container.appendChild(img);
                bg.appendChild(container);
                document.body.appendChild(bg);
            }

            var form = document.getElementById('uploadDocForm');
            var submitBtn = document.getElementById('uploadSubmitBtn');
            var uploadBtnText = document.getElementById('uploadBtnText');
            if (form && submitBtn && uploadBtnText) {
                form.addEventListener('submit', function(e) {
                    submitBtn.disabled = true;
                    uploadBtnText.innerHTML = '<span class="spinner"></span>Uploading...';
                });
            }
        });

        <?php foreach (array_keys($docFields) as $docKey): ?>
                (function() {
                    const input = document.getElementById('<?= $docKey ?>');
                    const previewDiv = document.getElementById('preview-<?= $docKey ?>');
                    if (!input || !previewDiv) return;

                    <?php if ($docKey === 'doc0'): ?>
                        let cropper;
                        let cropModalBG, cropModalContainer, cropImage, cropConfirmBtn, cropCancelBtn, cropImgWrapper;

                        input.addEventListener('change', function() {
                            previewDiv.innerHTML = '';
                            const files = input.files;
                            if (!files || !files.length) return;

                            const file = files[0];
                            if (!(file.type.startsWith('image/'))) {
                                previewDiv.innerText = "File must be image (.jpg, .jpeg, .png)";
                                return;
                            }

                            // Blur/sharpness check (preserved as in original)
                            const fileURL = URL.createObjectURL(file);
                            let imgTest = new window.Image();
                            imgTest.crossOrigin = "anonymous";
                            imgTest.onload = function() {
                                try {
                                    const canvas = document.createElement('canvas');
                                    canvas.width = imgTest.naturalWidth;
                                    canvas.height = imgTest.naturalHeight;
                                    const ctx = canvas.getContext('2d');
                                    ctx.drawImage(imgTest, 0, 0);

                                    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                                    let sum = 0,
                                        sumSq = 0,
                                        n = 0;
                                    const marginX = Math.floor(canvas.width * 0.18);
                                    const marginY = Math.floor(canvas.height * 0.12);

                                    for (let y = marginY; y < canvas.height - marginY; y += 2) {
                                        for (let x = marginX; x < canvas.width - marginX; x += 2) {
                                            const idx = (y * canvas.width + x) * 4;
                                            const r = imgData.data[idx],
                                                g = imgData.data[idx + 1],
                                                b = imgData.data[idx + 2];
                                            const v = Math.round(0.299 * r + 0.587 * g + 0.114 * b);
                                            sum += v;
                                            sumSq += v * v;
                                            n++;
                                        }
                                    }
                                    const mean = sum / n;
                                    const variance = (sumSq / n) - (mean * mean);

                                    if (variance < 350) {
                                        previewDiv.innerHTML = "<span style='color:#b51313;font-weight:600;'>❗ The selected photo appears blurry or unclear.<br>Please select another photo that is full clear, sharp, and face is visible.</span>";
                                        input.value = "";
                                        return;
                                    }
                                } catch (ex) {
                                    // ignore
                                }

                                // -- Cropper Modal
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    document.querySelectorAll('.modal-cropper-bg').forEach(el => el.remove());

                                    cropModalBG = document.createElement("div");
                                    cropModalBG.className = "modal-cropper-bg";
                                    cropModalBG.onclick = function(ev) {
                                        if (ev.target === cropModalBG) {
                                            cropper && cropper.destroy();
                                            cropModalBG.remove();
                                            input.value = "";
                                            previewDiv.innerHTML = "";
                                        }
                                    };

                                    cropModalContainer = document.createElement("div");
                                    cropModalContainer.className = "modal-cropper";

                                    let instr = document.createElement("div");
                                    instr.innerHTML = `
                                <div style="color:#17245e;font-weight:500;font-size:1.13rem;margin-bottom:7px;text-align:center;">
                                    Crop Your Passport Size Photo
                                </div>
                                <div style="font-size:1.01em;color:#495394;margin-bottom:8px;text-align:left;max-width:97vw;">
                                    Drag and pinch/zoom the crop rectangle. <b>Your face and full shoulders must be visible and in focus.</b>
                                    <span style='color:#c0392b'>Do not use photos that are blurry, cut away your face/shoulders, or unclear!</span>
                                </div>
                            `;
                                    instr.style.maxWidth = "99vw";
                                    instr.style.margin = "0 auto 6px auto";
                                    cropModalContainer.appendChild(instr);

                                    cropImgWrapper = document.createElement("div");
                                    cropImgWrapper.className = "modal-cropper-cropimg-container";

                                    cropImage = document.createElement("img");
                                    cropImage.src = e.target.result;
                                    cropImage.alt = "Crop Preview";
                                    cropImage.className = "crop-image";
                                    cropImgWrapper.appendChild(cropImage);

                                    cropModalContainer.appendChild(cropImgWrapper);

                                    const btns = document.createElement('div');
                                    btns.className = 'modal-btns';

                                    cropCancelBtn = document.createElement('button');
                                    cropCancelBtn.textContent = "Cancel";
                                    cropCancelBtn.className = "cancel-btn";

                                    cropConfirmBtn = document.createElement('button');
                                    cropConfirmBtn.textContent = "Crop & Use";
                                    cropConfirmBtn.style.background = "#1568e1";
                                    cropConfirmBtn.style.marginLeft = "10px";

                                    btns.appendChild(cropCancelBtn);
                                    btns.appendChild(cropConfirmBtn);
                                    cropModalContainer.appendChild(btns);

                                    const closeX = document.createElement('button');
                                    closeX.textContent = '×';
                                    closeX.title = 'Close';
                                    closeX.className = 'modal-close-x';
                                    closeX.onclick = function() {
                                        cropper && cropper.destroy();
                                        cropModalBG.remove();
                                        input.value = "";
                                        previewDiv.innerHTML = "";
                                    };
                                    cropModalContainer.appendChild(closeX);

                                    cropModalBG.appendChild(cropModalContainer);
                                    document.body.appendChild(cropModalBG);

                                    cropper = new Cropper(cropImage, {
                                        aspectRatio: 25 / 35,
                                        viewMode: 1,
                                        autoCropArea: 1,
                                        minCropBoxWidth: 20,
                                        minCropBoxHeight: 40,
                                        background: false,
                                        responsive: true,
                                        checkCrossOrigin: false,
                                        modal: true,
                                        guides: true,
                                        movable: true,
                                        cropBoxResizable: true,
                                        minContainerWidth: 140,
                                        minContainerHeight: 110,
                                        dragMode: 'move'
                                    });

                                    function closeModalCropper() {
                                        cropper && cropper.destroy();
                                        cropModalBG.remove();
                                        input.value = "";
                                        previewDiv.innerHTML = "";
                                    }

                                    cropCancelBtn.onclick = closeModalCropper;

                                    // Main change: Always export PNG (lossless), regardless of original format
                                    cropConfirmBtn.onclick = function() {
                                        const cw = Math.min(350, window.innerWidth * 0.97);
                                        const ch = Math.round(cw * 1.285);

                                        const canvas = cropper.getCroppedCanvas({
                                            width: cw,
                                            height: ch,
                                            imageSmoothingEnabled: true,
                                            imageSmoothingQuality: 'high'
                                        });
                                        previewDiv.innerHTML = "";

                                        // Force PNG (100% full quality, lossless, no JPEG compression artifacts)
                                        canvas.toBlob(function(blob) {
                                            const url = URL.createObjectURL(blob);

                                            const img = document.createElement('img');
                                            img.src = url;
                                            img.alt = 'Cropped Photo';
                                            img.style = "height:70px;max-width:110px;border-radius:4.5px;object-fit:cover;border:1px solid #b4d6fa;background:#f8fcff;margin-right:7px;cursor:pointer;";
                                            img.title = "Click to preview";
                                            img.addEventListener('click', function(ev) {
                                                ev.stopPropagation();
                                                let modalPrev = document.createElement('div');
                                                modalPrev.className = 'modal-preview-bg';
                                                let box = document.createElement('div');
                                                box.className = 'modal-preview';
                                                let closeBtn = document.createElement('button');
                                                closeBtn.className = 'modal-preview-close';
                                                closeBtn.innerHTML = '&times;';
                                                closeBtn.onclick = function() {
                                                    modalPrev.remove();
                                                };
                                                let mimg = document.createElement('img');
                                                mimg.src = url;
                                                mimg.alt = "Preview";
                                                box.appendChild(closeBtn);
                                                box.appendChild(mimg);
                                                modalPrev.appendChild(box);
                                                document.body.appendChild(modalPrev);
                                            });
                                            previewDiv.appendChild(img);

                                            const fn = document.createElement('span');
                                            fn.textContent = "passport_photo.png (cropped, full quality lossless, face and shoulders)";
                                            previewDiv.appendChild(fn);

                                            const dt = new DataTransfer();
                                            const croppedFile = new File([blob], "passport_photo.png", {
                                                type: "image/png"
                                            });
                                            dt.items.add(croppedFile);
                                            input.files = dt.files;
                                        }, "image/png");
                                        cropper && cropper.destroy();
                                        cropModalBG.remove();
                                    };
                                };
                                reader.readAsDataURL(file);
                            };
                            imgTest.onerror = function() {
                                previewDiv.innerHTML = "<span style='color:#b51313;font-weight:600;'>❗ Unable to load selected image.</span>";
                                input.value = "";
                            };
                            imgTest.src = fileURL;
                        });
                    <?php else: ?>
                        input.addEventListener('change', function() {
                            previewDiv.innerHTML = '';
                            const files = input.files;
                            if (!files || !files.length) return;
                            Array.from(files).forEach(function(file) {
                                const fileName = file.name;
                                if (file.type.startsWith('image/')) {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        const link = document.createElement('a');
                                        link.href = e.target.result;
                                        link.target = "_blank";
                                        const img = document.createElement('img');
                                        img.src = e.target.result;
                                        img.alt = 'Preview';
                                        img.style = "height:27px;max-width:90px;vertical-align:middle;margin-right:7px;object-fit:contain;border-radius:2.5px;border:1px solid #b4d6fa;background:#f8fcff;";
                                        link.appendChild(img);
                                        link.appendChild(document.createTextNode(fileName));
                                        previewDiv.appendChild(link);
                                    }
                                    reader.readAsDataURL(file);
                                } else if (file.type === 'application/pdf') {
                                    const link = document.createElement('a');
                                    link.href = URL.createObjectURL(file);
                                    link.target = "_blank";
                                    link.textContent = 'PDF: ' + fileName;
                                    previewDiv.appendChild(link);
                                } else {
                                    const span = document.createElement('span');
                                    span.textContent = fileName;
                                    previewDiv.appendChild(span);
                                }
                            });
                        });
                    <?php endif; ?>
                })();
        <?php endforeach; ?>
    </script>
</body>

</html>