<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}



// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 


// The specific fields to select from students_temporary_registration
$studentFields = "id, temp_id, token, title, firstname, lastname, fullname, certificate_name, dob, gender, nationality, permanent_address, current_address, mobile, home_number, office_number, emergency_contact, nic, passport, email, program, batch, conditional_offer_letter, conditional_offer_letter_text, conditional_offer_letter_text_02, conditional_offer_letter_text_03, conditional_offer_letter_text_04, created_at, approved, approved_by";

// Fetch all students for the dropdown (show all, regardless of approval status)
$students = [];
try {
    // $stmt = $conn->prepare("SELECT $studentFields FROM students_temporary_registration ORDER BY firstname, lastname");
    $stmt = $conn->prepare("SELECT $studentFields FROM students_temporary_registration WHERE approved = 0 ORDER BY firstname, lastname");
    $stmt->execute();

    // If MySQLnd is not enabled or get_result fails (on some PHP builds), fallback to bind_result
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $students[] = $row;
        }
    } else {
        // Fallback for non-MySQLnd systems (should not happen with recent PHP)
        $stmt->bind_result(
            $id,
            $temp_id,
            $token,
            $title,
            $firstname,
            $lastname,
            $fullname,
            $certificate_name,
            $dob,
            $gender,
            $nationality,
            $permanent_address,
            $current_address,
            $mobile,
            $home_number,
            $office_number,
            $emergency_contact,
            $nic,
            $passport,
            $email,
            $program,
            $batch,
            $conditional_offer_letter,
            $conditional_offer_letter_text,
            $conditional_offer_letter_text_02,
            $conditional_offer_letter_text_03,
            $conditional_offer_letter_text_04,
            $created_at,
            $approved,
            $approved_by
        );
        while ($stmt->fetch()) {
            $students[] = [
                'id' => $id,
                'temp_id' => $temp_id,
                'token' => $token,
                'title' => $title,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'fullname' => $fullname,
                'certificate_name' => $certificate_name,
                'dob' => $dob,
                'gender' => $gender,
                'nationality' => $nationality,
                'permanent_address' => $permanent_address,
                'current_address' => $current_address,
                'mobile' => $mobile,
                'home_number' => $home_number,
                'office_number' => $office_number,
                'emergency_contact' => $emergency_contact,
                'nic' => $nic,
                'passport' => $passport,
                'email' => $email,
                'program' => $program,
                'batch' => $batch,
                'conditional_offer_letter' => $conditional_offer_letter,
                'conditional_offer_letter_text' => $conditional_offer_letter_text,
                'conditional_offer_letter_text_02' => $conditional_offer_letter_text_02,
                'conditional_offer_letter_text_03' => $conditional_offer_letter_text_03,
                'conditional_offer_letter_text_04' => $conditional_offer_letter_text_04,
                'created_at' => $created_at,
                'approved' => $approved,
                'approved_by' => $approved_by
            ];
        }
    }
    $stmt->close();
} catch (Exception $e) {
    $students = [];
}

// Fetch details for the selected student and their documents
$selectedStudent = null;
$studentDetails = null;
$studentDocumentRow = null;
// Extra variables for program & batch info (for document path)
$programNameDisplay = '';
$batchNameDisplay = '';
if (isset($_GET['student_id']) && $_GET['student_id'] != '') {
    $selectedStudent = intval($_GET['student_id']);
    try {
        $stmt = $conn->prepare("SELECT $studentFields FROM students_temporary_registration WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $selectedStudent);
        $stmt->execute();
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $studentDetails = $row;
            }
        } else {
            $stmt->bind_result(
                $id,
                $temp_id,
                $token,
                $title,
                $firstname,
                $lastname,
                $fullname,
                $certificate_name,
                $dob,
                $gender,
                $nationality,
                $permanent_address,
                $current_address,
                $mobile,
                $home_number,
                $office_number,
                $emergency_contact,
                $nic,
                $passport,
                $email,
                $program,
                $batch,
                $conditional_offer_letter,
                $conditional_offer_letter_text,
                $conditional_offer_letter_text_02,
                $conditional_offer_letter_text_03,
                $conditional_offer_letter_text_04,
                $created_at,
                $approved,
                $approved_by
            );
            if ($stmt->fetch()) {
                $studentDetails = [
                    'id' => $id,
                    'temp_id' => $temp_id,
                    'token' => $token,
                    'title' => $title,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'fullname' => $fullname,
                    'certificate_name' => $certificate_name,
                    'dob' => $dob,
                    'gender' => $gender,
                    'nationality' => $nationality,
                    'permanent_address' => $permanent_address,
                    'current_address' => $current_address,
                    'mobile' => $mobile,
                    'home_number' => $home_number,
                    'office_number' => $office_number,
                    'emergency_contact' => $emergency_contact,
                    'nic' => $nic,
                    'passport' => $passport,
                    'email' => $email,
                    'program' => $program,
                    'batch' => $batch,
                    'conditional_offer_letter' => $conditional_offer_letter,
                    'conditional_offer_letter_text' => $conditional_offer_letter_text,
                    'conditional_offer_letter_text_02' => $conditional_offer_letter_text_02,
                    'conditional_offer_letter_text_03' => $conditional_offer_letter_text_03,
                    'conditional_offer_letter_text_04' => $conditional_offer_letter_text_04,
                    'created_at' => $created_at,
                    'approved' => $approved,
                    'approved_by' => $approved_by
                ];
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        $studentDetails = null;
    }
    // Fetch uploaded documents for this student, matching temp_id or nic
    if ($studentDetails && (isset($studentDetails['temp_id']) || isset($studentDetails['nic']))) {
        $temp_id = isset($studentDetails['temp_id']) ? $studentDetails['temp_id'] : '';
        $nic = isset($studentDetails['nic']) ? $studentDetails['nic'] : '';
        $docSql = "SELECT * FROM students_temporary_document WHERE (temp_id = ? OR nic = ?) ORDER BY created_at DESC LIMIT 1";
        if ($stmtDoc = $conn->prepare($docSql)) {
            $stmtDoc->bind_param("ss", $temp_id, $nic);
            $stmtDoc->execute();
            if (method_exists($stmtDoc, 'get_result')) {
                $docRes = $stmtDoc->get_result();
                if ($docRes && $docRes->num_rows > 0) {
                    $studentDocumentRow = $docRes->fetch_assoc();
                }
            } else {
                $meta = $stmtDoc->result_metadata();
                if ($meta) {
                    $fields = [];
                    $rowData = [];
                    while ($field = $meta->fetch_field()) {
                        $fields[] = &$rowData[$field->name];
                    }
                    call_user_func_array([$stmtDoc, 'bind_result'], $fields);

                    if ($stmtDoc->fetch()) {
                        $studentDocumentRow = [];
                        foreach ($rowData as $k => $v)
                            $studentDocumentRow[$k] = $v;
                    }
                    $meta->free();
                }
            }
            $stmtDoc->close();
        }
    }
    // For program and batch folder names for document path
    $programNameDisplay = isset($studentDetails['program']) && !empty($studentDetails['program']) ? $studentDetails['program'] : '';
    if (isset($studentDetails['batch']) && !empty($studentDetails['batch'])) {
        $batchNameDisplay = $studentDetails['batch'];
    } elseif (isset($studentDetails['batch_name']) && !empty($studentDetails['batch_name'])) {
        $batchNameDisplay = $studentDetails['batch_name'];
    }
}

// Session role for BMS email field
$session_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

?>

<style>
    .bg-info {
        background-color: rgb(4 45 92) !important;
    }
</style>
<!-- Page Wrapper -->
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
                    <h4 class="h4 mb-0 text-gray-800">Student Online Registration Data</h4>
                </div>

                <!-- Student Selector Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-user"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Select Student</h6>
                            </div>
                            <div class="card-body">
                                <form method="get" action="">
                                    <label for="student_id" class="form-label">Select Student:</label>
                                    <select name="student_id" id="student_id" class="form-select select2"
                                        onchange="this.form.submit()">
                                        <option value="">-- Select --</option>
                                        <?php if (count($students) === 0): ?>
                                            <option disabled>No students found.</option>
                                        <?php else: ?>
                                            <?php foreach ($students as $stu): ?>
                                                <?php
                                                // Compose label: First Last (TempID | NIC)
                                                $labelName = trim((isset($stu['firstname']) ? $stu['firstname'] : '') . ' ' . (isset($stu['lastname']) ? $stu['lastname'] : ''));
                                                $labelTempId = isset($stu['temp_id']) ? $stu['temp_id'] : '';
                                                $labelNic = isset($stu['nic']) ? $stu['nic'] : '';
                                                $label = $labelName . " (" . $labelTempId . " | " . $labelNic . ")";
                                                ?>
                                                <option value="<?= htmlspecialchars($stu['id']) ?>"
                                                    <?= ($selectedStudent == $stu['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    </select>
                                    <noscript>
                                        <button type="submit" class="btn btn-primary mt-2">View</button>
                                    </noscript>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($selectedStudent && $studentDetails): ?>
                    <?php
                    // -------------- SHOW UPLOADED DOCUMENTS FOR SELECTED STUDENT ---------------
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0" style="background-color: #f7f8fa;">
                                <div class="card-body d-flex align-items-center py-3" style="font-size: 1.18rem;">
                                    <div class="me-4">
                                        <span class="badge bg-primary p-2 fs-6" style="width:60px;"><i
                                                class="fas fa-graduation-cap"></i></span>
                                    </div>
                                    <div>
                                        <span class="fw-semibold text-dark">Program:</span>
                                        <span class="text-secondary"
                                            style="margin-right: 2rem;"><?= htmlspecialchars($programNameDisplay ?? '-') ?></span>
                                        <span class="fw-semibold text-dark">Batch:</span>
                                        <span
                                            class="text-secondary"><?= htmlspecialchars($batchNameDisplay ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-file-upload"></i>
                                    Uploaded Documents
                                </div>
                                <div class="card-body">

                                    <?php

                                    // Transforms "Graduate Diploma in Management (Level 6)" > "GraduateDiplomainManagementLevel6"
                                    function program_folder_clean($str)
                                    {
                                        // Remove all non-alphanum except underscore
                                        return preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '', $str));
                                    }
                                    // Transforms "Batch 85" > "Batch85"
                                    function batch_folder_clean($str)
                                    {
                                        return preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '', $str));
                                    }
                                    // TempId - just ensure spaces become _
                                    function temp_id_folder_clean($str)
                                    {
                                        return str_replace([' ', '\\', '/'], '_', $str);
                                    }

                                    $passportPhotoUrl = "";
                                    $hasPassportPhoto = false;

                                    $tempIdForPath = isset($studentDetails['temp_id']) ? $studentDetails['temp_id'] : '';
                                    $programForPath = isset($studentDetails['program']) && $studentDetails['program'] ? $studentDetails['program'] : (isset($programNameDisplay) ? $programNameDisplay : '');
                                    $batchForPath = isset($studentDetails['batch']) && $studentDetails['batch'] ? $studentDetails['batch'] : (isset($batchNameDisplay) ? $batchNameDisplay : '');

                                    // Prefer doc0 from database
                                    if (
                                        isset($studentDocumentRow['doc0']) &&
                                        !empty($studentDocumentRow['doc0']) &&
                                        strtolower($studentDocumentRow['doc0']) !== 'null' &&
                                        $programForPath && $batchForPath && $tempIdForPath
                                    ) {
                                        $fileName = $studentDocumentRow['doc0'];
                                        $progf = program_folder_clean($programForPath);
                                        $batchf = batch_folder_clean($batchForPath);
                                        $tempidf = temp_id_folder_clean($tempIdForPath);
                                        $passportPhotoUrl = "uploaded_documents/{$progf}/{$batchf}/{$tempidf}/" . str_replace(' ', '_', basename($fileName));
                                        $hasPassportPhoto = true;
                                    }

                                    // fallback to $studentPhotoUrl if not already set
                                    if (!$hasPassportPhoto && !empty($studentPhotoUrl)) {
                                        $passportPhotoUrl = $studentPhotoUrl;
                                    }

                                    if (empty($passportPhotoUrl)) {
                                        $passportPhotoUrl = "https://www.shutterstock.com/image-vector/unknown-person-hidden-covered-masked-600nw-1552977773.jpg";
                                    }
                                    ?>

                                    <!-- PASSPORT SIZE IMAGE -->
                                    <div class="mb-4 d-flex align-items-center">
                                        <div class="me-4">
                                            <div class="border rounded"
                                                style="width:120px; height:160px; display:flex; align-items:center; justify-content:center; background:#f0f2f7;">
                                                <img src="<?= htmlspecialchars($passportPhotoUrl) ?>"
                                                    alt="Passport Size Photo"
                                                    style="display:block;max-width:97px;max-height:135px;object-fit:cover;"
                                                    onerror="this.onerror=null;this.src='https://www.shutterstock.com/image-vector/unknown-person-hidden-covered-masked-600nw-1552977773.jpg';" />
                                            </div>
                                            <div style="font-size:0.9em;text-align:center;" class="text-muted mt-2">
                                                Passport Size Photo
                                                <?php if ($hasPassportPhoto): ?>
                                                    <br><span class="badge bg-success">Uploaded</span>
                                                <?php else: ?>
                                                    <br><span class="badge bg-secondary">Not Uploaded</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($hasPassportPhoto): ?>
                                                <div class="text-center mt-2">
                                                    <a href="<?= htmlspecialchars($passportPhotoUrl) ?>" target="_blank"
                                                        class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i> View Full Image
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <?php
                                            // --- Build doc fields table (doc1-4) ---
                                            // Actual FOLDER logic: uploaded_documents/CLEANEDPROG/CLEANEDBATCH/CLEANTEMPID/
                                            $baseDocDir = "";
                                            if ($programForPath && $batchForPath && $tempIdForPath) {
                                                $progf = program_folder_clean($programForPath);
                                                $batchf = batch_folder_clean($batchForPath);
                                                $tempidf = temp_id_folder_clean($tempIdForPath);
                                                $baseDocDir = "uploaded_documents/{$progf}/{$batchf}/{$tempidf}/";
                                            }
                                            ?>
                                            <?php if ($studentDocumentRow): ?>
                                                <table class="table table-bordered mb-0">
                                                    <tr>
                                                        <th>Document</th>
                                                        <th>Status</th>
                                                        <th>File</th>
                                                    </tr>
                                                    <?php
                                                    $docFields = [
                                                        'doc1' => 'NIC',
                                                        'doc2' => 'PASSPORT',
                                                        'doc3' => 'O/L RESULT',
                                                        'doc4' => 'A/L RESULT',
                                                        'degree_certificate' => 'DEGREE CERTIFICATE',
                                                        'transcript' => 'TRANSCRIPT',
                                                        'other_qualification_1' => 'OTHER QUALIFICATION 1',
                                                        'other_qualification_2' => 'OTHER QUALIFICATION 2'
                                                    ];
                                                    foreach ($docFields as $fld => $label):
                                                        $fileVal = isset($studentDocumentRow[$fld]) ? $studentDocumentRow[$fld] : '';
                                                        $hasFile = !empty($fileVal) && strtolower($fileVal) !== 'null';
                                                        $fileValCleaned = str_replace('\\', '/', $fileVal);
                                                        ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($label); ?></td>
                                                            <td>
                                                                <?php if ($hasFile): ?>
                                                                    <span class="badge bg-success">Uploaded</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Not Uploaded</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($hasFile && $baseDocDir): ?>
                                                                    <?php
                                                                    $fileUrl = $baseDocDir . str_replace(' ', '_', basename($fileValCleaned));
                                                                    ?>
                                                                    <a href="<?= htmlspecialchars($fileUrl); ?>" target="_blank">
                                                                        <i class="fas fa-file-download"></i> View/Download
                                                                    </a>
                                                                <?php elseif ($hasFile): ?>
                                                                    <?php
                                                                    $fallbackUrl = "/registration_link/uploads/students_temp_docs/" . str_replace(' ', '_', basename($fileValCleaned));
                                                                    ?>
                                                                    <a href="<?= htmlspecialchars($fallbackUrl); ?>" target="_blank">
                                                                        <i class="fas fa-file-download"></i> View/Download
                                                                    </a>
                                                                <?php else: ?>
                                                                    -
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr>
                                                        <td colspan="3" class="text-end">
                                                            <strong>Total Uploaded:</strong>
                                                            <?= isset($studentDocumentRow['uploaded_count']) ? intval($studentDocumentRow['uploaded_count']) : 0; ?>
                                                            <?php if (isset($studentDocumentRow['created_at'])): ?>
                                                                <span class="ms-3 text-muted" style="font-size:0.95em;">Last Upload:
                                                                    <?= htmlspecialchars($studentDocumentRow['created_at']); ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            <?php else: ?>
                                                <div class="alert alert-warning mb-0">No documents uploaded by this student.
                                                </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                    <!-- END PASSPORT SIZE IMAGE & DOCS TABLE -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END DOCUMENTS BLOCK -->

                    <!-- ------------------------------------ -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span
                                        class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                        style="width: 30px; height: 30px;">
                                        <i class="fas fa-address-card"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Student Registration Information</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Start of Registration Form - adapted from studentRegister.php -->
                                    <form id="studentForm" method="post" action="online_registration_upload_process.php">
                                        <input type="hidden" id="student_code" name="student_code" class="form-control"
                                            value="<?= isset($studentDetails['student_code']) ? htmlspecialchars($studentDetails['student_code']) : '' ?>"
                                            required>
                                        <input type="hidden" name="id"
                                            value="<?= htmlspecialchars($studentDetails['id']) ?>">


                                        <div class="row">
                                            <div class="col-md-5">
                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="title" class="form-label">Title: <span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <select id="title" name="title"
                                                                class="form-select form-control select2" required>
                                                                <?php
                                                                $titles = ["Mr", "Mrs", "Ms", "Dr", "Prof"];
                                                                $selectedTitle = isset($studentDetails['title']) ? $studentDetails['title'] : '';
                                                                ?>
                                                                <?php foreach ($titles as $item): ?>
                                                                    <option value="<?= $item ?>" <?= ($selectedTitle == $item) ? 'selected' : '' ?>><?= $item ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3"> <label for="first_name"
                                                                class="form-label">First Name:<span
                                                                    class="text-danger fw-bold">*</span></label></div>
                                                        <div class="col">
                                                            <input type="text" id="first_name" name="first_name"
                                                                placeholder="First Name" class="form-control"
                                                                value="<?= isset($studentDetails['firstname']) ? htmlspecialchars($studentDetails['firstname']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3"> <label for="last_name"
                                                                class="form-label">Last Name:<span
                                                                    class="text-danger fw-bold">*</span></label></div>
                                                        <div class="col">
                                                            <input type="text" id="last_name" name="last_name"
                                                                placeholder="Last Name" class="form-control"
                                                                value="<?= isset($studentDetails['lastname']) ? htmlspecialchars($studentDetails['lastname']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3"> <label for="certificate_name"
                                                                class="form-label">Name for the Certificate:<span
                                                                    class="text-danger fw-bold">*</span></label></div>
                                                        <div class="col">
                                                            <input type="text" id="certificate_name"
                                                                placeholder="Name for the Certificate"
                                                                name="certificate_name" class="form-control" value="<?= (isset($studentDetails['certificate_name']) && $studentDetails['certificate_name'] !== '')
                                                                    ? htmlspecialchars($studentDetails['certificate_name'])
                                                                    : ((isset($studentDetails['firstname']) ? htmlspecialchars($studentDetails['firstname']) : '') .
                                                                        ((isset($studentDetails['firstname']) && isset($studentDetails['lastname']) && $studentDetails['firstname'] !== '' && $studentDetails['lastname'] !== '') ? ' ' : '') .
                                                                        (isset($studentDetails['lastname']) ? htmlspecialchars($studentDetails['lastname']) : ''))
                                                                    ?>" required>
                                                            <!-- <small class="form-text text-muted">This will auto-fill as "First Name Last Name". You can modify it if required.</small> -->
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3"> <label for="preferred_name"
                                                                class="form-label">Preferred Name:<span
                                                                    class="text-danger fw-bold">*</span></label></div>
                                                        <div class="col">
                                                            <input type="text" id="preferred_name"
                                                                placeholder="Preferred Name" name="preferred_name"
                                                                class="form-control" value="<?= (isset($studentDetails['preferred_name']) && $studentDetails['preferred_name'] !== '')
                                                                    ? htmlspecialchars($studentDetails['preferred_name'])
                                                                    : ((isset($studentDetails['firstname']) ? htmlspecialchars($studentDetails['firstname']) : '') .
                                                                        ((isset($studentDetails['firstname']) && isset($studentDetails['lastname']) && $studentDetails['firstname'] !== '' && $studentDetails['lastname'] !== '') ? ' ' : '') .
                                                                        (isset($studentDetails['lastname']) ? htmlspecialchars($studentDetails['lastname']) : ''))
                                                                    ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3"> <label for="dob" class="form-label">Date of
                                                                Birth:<span class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="date" id="dob" name="dob"
                                                                placeholder="Date of Birth" class="form-control"
                                                                value="<?= isset($studentDetails['dob']) ? htmlspecialchars($studentDetails['dob']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="nationality" class="form-label">Nationality:<span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="nationality" placeholder="Nationality"
                                                                name="nationality" class="form-control" required
                                                                value="<?= isset($studentDetails['nationality']) ? htmlspecialchars($studentDetails['nationality']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Permanent Address:<span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="permanent_address"
                                                                name="permanent_address" placeholder="Permanent Address"
                                                                class="form-control"
                                                                value="<?= isset($studentDetails['permanent_address']) ? htmlspecialchars($studentDetails['permanent_address']) : '' ?>"
                                                                required>
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="same_as_permanent" name="same_as_permanent"
                                                                    <?= (isset($studentDetails['same_as_permanent']) && $studentDetails['same_as_permanent']) ? "checked" : "" ?>
                                                                    onclick="if(this.checked){ $('#current_address').val($('#permanent_address').val()); }">
                                                                <label class="form-check-label" for="same_as_permanent">Same
                                                                    as Permanent Address</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="current_address" class="form-label">Current
                                                                Address:<span class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="current_address" name="current_address"
                                                                placeholder="Current Address" class="form-control"
                                                                value="<?= isset($studentDetails['current_address']) ? htmlspecialchars($studentDetails['current_address']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>
                                                <script>
                                                    $(document).ready(function () {
                                                        $('#same_as_permanent').on('change', function () {
                                                            if ($(this).is(':checked')) {
                                                                $('#current_address').val($('#permanent_address').val());
                                                            }
                                                        });
                                                        $('#permanent_address').on('input', function () {
                                                            if ($('#same_as_permanent').is(':checked')) {
                                                                $('#current_address').val($(this).val());
                                                            }
                                                        });
                                                    });
                                                </script>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="mobile" class="form-label">Mobile:<span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="mobile" placeholder="Mobile"
                                                                name="mobile" class="form-control"
                                                                value="<?= isset($studentDetails['mobile']) ? htmlspecialchars($studentDetails['mobile']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="telephone" class="form-label">Telephone:<span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col"> <input type="text" id="telephone"
                                                                placeholder="Telephone" name="telephone"
                                                                class="form-control"
                                                                value="<?= isset($studentDetails['telephone']) ? htmlspecialchars($studentDetails['telephone']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr style="width: 55%;">
                                                <div class="row d-flex">
                                                    <label for="emergency_contact_name" class="form-label">Emergency Name &
                                                        Contact </label>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label for="emergency_contact_name"
                                                                        class="form-label">Name:</label>
                                                                </div>
                                                                <div class="col">
                                                                    <input type="text" id="emergency_contact_name"
                                                                        placeholder="Emergency Contact Name"
                                                                        name="emergency_contact_name" class="form-control"
                                                                        value="<?= isset($studentDetails['emergency_contact_name']) ? htmlspecialchars($studentDetails['emergency_contact_name']) : '' ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label for="emergency_contact_number"
                                                                        class="form-label"> Contact:</label>
                                                                </div>
                                                                <div class="col">
                                                                    <input type="text" id="emergency_contact_number"
                                                                        placeholder="Emergency Contact Number"
                                                                        name="emergency_contact_number" class="form-control"
                                                                        value="<?= isset($studentDetails['emergency_contact_number']) ? htmlspecialchars($studentDetails['emergency_contact_number']) : '' ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <hr style="width: 55%;">

                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="english_ability" class="form-label">English
                                                                Ability:</label>
                                                            <input type="checkbox" id="english_ability"
                                                                name="english_ability" value="1"
                                                                <?= (isset($studentDetails['english_ability']) && $studentDetails['english_ability']) ? "checked" : "" ?>>
                                                        </div>
                                                    </div>
                                                    <hr style="width: 55%;">

                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="minimum_entry_qualification"
                                                                class="form-label">Minimum Entry Qualification:</label>
                                                            <input type="checkbox" id="minimum_entry_qualification"
                                                                name="minimum_entry_qualification" value="1"
                                                                <?= (!isset($studentDetails['minimum_entry_qualification']) || $studentDetails['minimum_entry_qualification']) ? "checked" : "" ?>>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-5" style="margin-left: 100px;">
                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="nic" class="form-label">NIC: <span
                                                                    class="text-danger fw-bold">*</span> </label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" placeholder="NIC" id="nic" name="nic"
                                                                class="form-control"
                                                                value="<?= isset($studentDetails['nic']) ? htmlspecialchars($studentDetails['nic']) : '' ?>"
                                                                required>
                                                            <div id="nic-feedback"
                                                                style="color: red; font-weight: bold; display:none; margin-top: 5px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="passport" class="form-label">Passport:</label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" placeholder="Passport" id="passport"
                                                                name="passport" class="form-control"
                                                                value="<?= isset($studentDetails['passport']) ? htmlspecialchars($studentDetails['passport']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="personal_email" class="form-label">Personal Email:
                                                                <span class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="email" id="personal_email"
                                                                placeholder="Personal Email" name="personal_email"
                                                                class="form-control"
                                                                value="<?= isset($studentDetails['email']) ? htmlspecialchars($studentDetails['email']) : '' ?>"
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="bms_email" class="form-label">BMS Email:</label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="email" id="bms_email" placeholder="BMS Email"
                                                                name="bms_email" class="form-control"
                                                                value="<?= isset($studentDetails['bms_email']) ? htmlspecialchars($studentDetails['bms_email']) : '' ?>"
                                                                <?= ($session_role !== 'super_admin') ? 'disabled' : ''; ?>>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="occupation" class="form-label">Occupation:</label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="occupation" placeholder="Occupation"
                                                                name="occupation" class="form-control"
                                                                value="<?= isset($studentDetails['occupation']) ? htmlspecialchars($studentDetails['occupation']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="organization"
                                                                class="form-label">Organization:</label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="organization" name="organization"
                                                                placeholder="Organization" class="form-control"
                                                                value="<?= isset($studentDetails['organization']) ? htmlspecialchars($studentDetails['organization']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="previous_organization" class="form-label">Previous
                                                                Organization:</label>
                                                        </div>
                                                        <div class="col">
                                                            <input type="text" id="previous_organization"
                                                                name="previous_organization"
                                                                placeholder="Previous Organization" class="form-control"
                                                                value="<?= isset($studentDetails['previous_organization']) ? htmlspecialchars($studentDetails['previous_organization']) : '' ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3" style="line-height: 32px;">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label for="qualifications"
                                                                class="form-label">Qualifications:<span
                                                                    class="text-danger fw-bold">*</span></label>
                                                        </div>
                                                        <div class="col">
                                                            <div class="row d-flex">
                                                                <div class="col-md-6">
                                                                    <?php
                                                                    $qualificationList = ["Bachelors", "Masters", "Diploma", "ECM", "AL"];
                                                                    $existingQualifications = isset($studentDetails['qualifications']) && is_array($studentDetails['qualifications']) ? $studentDetails['qualifications'] : [];
                                                                    if (isset($studentDetails['qualifications']) && !is_array($studentDetails['qualifications'])) {
                                                                        $existingQualifications = explode(',', $studentDetails['qualifications']);
                                                                    }
                                                                    // Ensure 'AL' is selected by default if nothing chosen
                                                                    if (empty($existingQualifications) || (count($existingQualifications) === 1 && $existingQualifications[0] === '')) {
                                                                        $existingQualifications = ['AL'];
                                                                    }
                                                                    ?>
                                                                    <?php foreach ($qualificationList as $q): ?>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                name="qualifications[]" value="<?= $q ?>"
                                                                                id="<?= strtolower($q) ?>" <?= in_array($q, $existingQualifications) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label"
                                                                                for="<?= strtolower($q) ?>"><?= $q ?></label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <?php
                                                                    $qualificationList2 = ["PGDip", "IFD", "OL"];
                                                                    ?>
                                                                    <?php foreach ($qualificationList2 as $q): ?>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                name="qualifications[]" value="<?= $q ?>"
                                                                                id="<?= strtolower($q) ?>" <?= in_array($q, $existingQualifications) ? 'checked' : '' ?>>
                                                                            <label class="form-check-label"
                                                                                for="<?= strtolower($q) ?>"><?= $q ?></label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr>

                                                <!-- Allocation Section: University, Programme, Batch info from DB -->

                                                <div id="allocateSection">
                                                    <?php
                                                    // PHP part for initial page render
                                                    // 1. Get University ID
                                                    $universityId = 1;
                                                    if (isset($studentDetails['university']) && !empty($studentDetails['university'])) {
                                                        $uniQuery = "SELECT id FROM university WHERE name = ?";
                                                        if ($stmt = $conn->prepare($uniQuery)) {
                                                            $stmt->bind_param('s', $studentDetails['university']);
                                                            $stmt->execute();
                                                            $uniResult = $stmt->get_result();
                                                            if ($uniRow = $uniResult->fetch_assoc()) {
                                                                $universityId = $uniRow['id'];
                                                            }
                                                            $stmt->close();
                                                        }
                                                    }

                                                    // 2. Get Program ID
                                                    $programId = null;
                                                    $programNameDisplay = isset($studentDetails['program']) ? $studentDetails['program'] : '-';
                                                    if (isset($studentDetails['program']) && $studentDetails['program'] !== '') {
                                                        $programName = $studentDetails['program'];
                                                        $query = "SELECT program_code FROM program_table WHERE university_id = ? AND program_name = ?";
                                                        if ($stmt = $conn->prepare($query)) {
                                                            $stmt->bind_param('is', $universityId, $programName);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            if ($row = $result->fetch_assoc()) {
                                                                $programId = $row['program_code'];
                                                            }
                                                            $stmt->close();
                                                        }
                                                    }

                                                    // 3. Batch - collect all batches for dropdown
                                                    $selectedBatchId = null;
                                                    $selectedBatchLabel = '';
                                                    $batches = [];
                                                    if ($programId) {
                                                        $batchQuery = "SELECT id, batch_name, university, programme, year_batch_code, intake_date, batch_no, intake_no, year_no, end_date, created_at, updated_at 
                                                                FROM batch_table 
                                                                WHERE university = ? AND programme = ? 
                                                                ORDER BY intake_date DESC, batch_name ASC";
                                                        if ($stmt = $conn->prepare($batchQuery)) {
                                                            $stmt->bind_param('ii', $universityId, $programId);
                                                            $stmt->execute();
                                                            $batchResult = $stmt->get_result();
                                                            while ($batchRow = $batchResult->fetch_assoc()) {
                                                                $batches[] = $batchRow;
                                                            }
                                                            $stmt->close();
                                                        }
                                                    }

                                                    // 4. Determine selected batch
                                                    if (isset($studentDetails['batch']) && !empty($studentDetails['batch'])) {
                                                        // Find corresponding batch by id, name or batch_no
                                                        foreach ($batches as $b) {
                                                            if (
                                                                $studentDetails['batch'] == $b['id'] ||
                                                                $studentDetails['batch'] == $b['batch_name'] ||
                                                                $studentDetails['batch'] == $b['batch_no']
                                                            ) {
                                                                $selectedBatchId = $b['id'];
                                                                $selectedBatchLabel = $b['batch_name'] . " (" . $b['batch_no'] . ")";
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if (!$selectedBatchId && isset($studentDetails['batch_name']) && !empty($studentDetails['batch_name'])) {
                                                        foreach ($batches as $b) {
                                                            if ($studentDetails['batch_name'] == $b['batch_name']) {
                                                                $selectedBatchId = $b['id'];
                                                                $selectedBatchLabel = $b['batch_name'] . " (" . $b['batch_no'] . ")";
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    // If no batch from student, default to first batch
                                                    if (!$selectedBatchId && count($batches) > 0) {
                                                        $selectedBatchId = $batches[0]['id'];
                                                        $selectedBatchLabel = $batches[0]['batch_name'] . " (" . $batches[0]['batch_no'] . ")";
                                                    }

                                                    // 5. Payment batch allocation info
                                                    $batchDetails = null;
                                                    $batchDetailsError = null;
                                                    if ($selectedBatchId) {
                                                        $batchDetailsQuery = "
                                                                SELECT 
                                                                    id, 
                                                                    programme_id, 
                                                                    batch_id,
                                                                    course_fee_lkr, 
                                                                    uni_fee_gbp, 
                                                                    uni_fee_usd, 
                                                                    uni_fee_euro, 
                                                                    register_date, 
                                                                    installment_no, 
                                                                    registration_fee, 
                                                                    created_at, 
                                                                    only_course_fee
                                                                FROM payment_batch_allocation
                                                                WHERE batch_id = ?
                                                                LIMIT 1
                                                            ";
                                                        try {
                                                            if ($stmt = $conn->prepare($batchDetailsQuery)) {
                                                                $stmt->bind_param('i', $selectedBatchId);
                                                                $stmt->execute();
                                                                $batchDetailsResult = $stmt->get_result();
                                                                if ($row = $batchDetailsResult->fetch_assoc()) {
                                                                    $batchDetails = $row;
                                                                }
                                                                $stmt->close();
                                                            }
                                                        } catch (mysqli_sql_exception $ex) {
                                                            $batchDetailsError = 'Database error: ' . htmlspecialchars($ex->getMessage());
                                                        }
                                                    }

                                                    $dispUniversity = isset($studentDetails['university']) && !empty($studentDetails['university'])
                                                        ? htmlspecialchars($studentDetails['university'])
                                                        : 'BMS';
                                                    ?>

                                                    <table class="table table-bordered mb-0" style="background: #fff;">
                                                        <tbody>
                                                            <tr>
                                                                <th style="width:180px;">University</th>
                                                                <td><?= $dispUniversity ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Programme</th>
                                                                <td>
                                                                    <?= htmlspecialchars($programNameDisplay) ?>
                                                                    <?php if ($programId): ?>
                                                                        <small class="text-muted ms-2">(Program ID:
                                                                            <?= htmlspecialchars($programId) ?>)</small>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Batch</th>
                                                                <td>
                                                                    <select name="batch_id" id="batch_id"
                                                                        class="form-select mb-1 select2" required
                                                                        style="width:100%;">
                                                                        <option value="">Select a batch...</option>
                                                                        <?php foreach ($batches as $b):
                                                                            $optionText = htmlspecialchars($b['batch_name'] . " (Batch No: " . $b['batch_no'] . "), Intake: "
                                                                                . $b['intake_date'] . ", Year: " . $b['year_no'] . ", End: " . $b['end_date']);
                                                                            ?>
                                                                            <option value="<?= $b['id'] ?>"
                                                                                <?= $selectedBatchId == $b['id'] ? 'selected' : '' ?>>
                                                                                <?= $optionText ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <?php if ($selectedBatchLabel): ?>
                                                                        <small class="text-muted ms-2">Selected:
                                                                            <?= htmlspecialchars($selectedBatchLabel) ?> (Batch
                                                                            ID:
                                                                            <?= htmlspecialchars($selectedBatchId) ?>)</small>
                                                                    <?php endif; ?>

                                                                    <script>
                                                                        // Ensure jQuery and Select2 are loaded
                                                                        $(document).ready(function () {
                                                                            $('#batch_id').select2({
                                                                                placeholder: 'Select a batch...',
                                                                                width: 'resolve'
                                                                            });
                                                                        });
                                                                    </script>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <div id="batchDetailsWrapper">
                                                        <?php if ($batchDetails): ?>
                                                            <div class="mt-2">
                                                                <table class="table table-sm table-striped table-bordered"
                                                                    style="background: #fafcff;">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th colspan="2" class="text-center">Payment Batch
                                                                                Allocation Details</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <th>Course Fee LKR</th>
                                                                            <td><?= htmlspecialchars($batchDetails['course_fee_lkr']) ?>
                                                                            </td>
                                                                        </tr>
                                                                        <?php if (isset($batchDetails['uni_fee_gbp']) && floatval($batchDetails['uni_fee_gbp']) > 0): ?>
                                                                            <tr>
                                                                                <th>University Fee GBP</th>
                                                                                <td><?= htmlspecialchars($batchDetails['uni_fee_gbp']) ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                        <?php if (isset($batchDetails['uni_fee_usd']) && floatval($batchDetails['uni_fee_usd']) > 0): ?>
                                                                            <tr>
                                                                                <th>University Fee USD</th>
                                                                                <td><?= htmlspecialchars($batchDetails['uni_fee_usd']) ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                        <?php if (isset($batchDetails['uni_fee_euro']) && floatval($batchDetails['uni_fee_euro']) > 0): ?>
                                                                            <tr>
                                                                                <th>University Fee EURO</th>
                                                                                <td><?= htmlspecialchars($batchDetails['uni_fee_euro']) ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endif; ?>
                                                                        <tr>
                                                                            <th>Register Date</th>
                                                                            <td><?= htmlspecialchars($batchDetails['register_date']) ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Installment No</th>
                                                                            <td><?= htmlspecialchars($batchDetails['installment_no']) ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Registration Fee</th>
                                                                            <td><?= htmlspecialchars($batchDetails['registration_fee']) ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Only Course Fee</th>
                                                                            <td><?= htmlspecialchars($batchDetails['only_course_fee']) ?>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php elseif ($selectedBatchId): ?>
                                                            <div class="alert alert-warning mt-2">Not allocated. No payment
                                                                batch allocation details for this batch.</div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <script>
                                                    // AJAX update of payment batch allocation info on batch change
                                                    // Here we embed all PHP batch allocation data in a JS object instead of using URL ajax_get_batch_allocation.php
                                                    <?php
                                                    // Prepare JS object of batch_id => batch_details for all batches in this university/program & batch
                                                    $allBatchDetailsData = [];
                                                    if ($programId && !empty($batches)) {
                                                        foreach ($batches as $b) {
                                                            // For each batch, get their batch allocation, if exists
                                                            $q = "SELECT 
                                                                    id, 
                                                                    programme_id, 
                                                                    batch_id,
                                                                    course_fee_lkr, 
                                                                    uni_fee_gbp, 
                                                                    uni_fee_usd, 
                                                                    uni_fee_euro, 
                                                                    register_date, 
                                                                    installment_no, 
                                                                    registration_fee, 
                                                                    created_at, 
                                                                    only_course_fee
                                                                FROM payment_batch_allocation
                                                                WHERE batch_id = ? LIMIT 1";

                                                            $batchData = null;
                                                            if ($stmt = $conn->prepare($q)) {
                                                                $stmt->bind_param('i', $b['id']);
                                                                $stmt->execute();
                                                                $res = $stmt->get_result();
                                                                if ($row = $res->fetch_assoc()) {
                                                                    $batchData = $row;
                                                                }
                                                                $stmt->close();
                                                            }
                                                            if ($batchData) {
                                                                $allBatchDetailsData[$b['id']] = $batchData;
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    var batchPaymentAllocations = <?php echo json_encode($allBatchDetailsData); ?>;
                                                    $(document).ready(function () {
                                                        // Show/hide the "Verify Application" button in real time
                                                        function updateSbtBtnVisibility(batchId) {
                                                            var d = batchPaymentAllocations[batchId];
                                                            if (d) {
                                                                $('#sbt_btn').show();
                                                            } else {
                                                                $('#sbt_btn').hide();
                                                            }
                                                        }

                                                        // Init: hide or show button based on current value
                                                        var initialBatchId = $('#batch_id').val();
                                                        updateSbtBtnVisibility(initialBatchId);

                                                        $('#batch_id').on('change', function () {
                                                            var batchId = $(this).val();
                                                            $('#batchDetailsWrapper').html('<div class="text-secondary mt-2">Loading payment batch allocation ...</div>');

                                                            setTimeout(function () {
                                                                let html = '';
                                                                var d = batchPaymentAllocations[batchId];
                                                                if (d) {
                                                                    html += `<div class="mt-2">
                                                                    <table class="table table-sm table-striped table-bordered" style="background: #fafcff;">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th colspan="2" class="text-center">Payment Batch Allocation Details</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr>
                                                                                <th>Course Fee LKR</th>
                                                                                <td>${d.course_fee_lkr ? escapeHtml(d.course_fee_lkr) : ''}</td>
                                                                            </tr>`;
                                                                    if (d.uni_fee_gbp && parseFloat(d.uni_fee_gbp) > 0) html += `
                                                                            <tr>
                                                                                <th>University Fee GBP</th>
                                                                                <td>${escapeHtml(d.uni_fee_gbp)}</td>
                                                                            </tr>`;
                                                                    if (d.uni_fee_usd && parseFloat(d.uni_fee_usd) > 0) html += `
                                                                            <tr>
                                                                                <th>University Fee USD</th>
                                                                                <td>${escapeHtml(d.uni_fee_usd)}</td>
                                                                            </tr>`;
                                                                    if (d.uni_fee_euro && parseFloat(d.uni_fee_euro) > 0) html += `
                                                                            <tr>
                                                                                <th>University Fee EURO</th>
                                                                                <td>${escapeHtml(d.uni_fee_euro)}</td>
                                                                            </tr>`;
                                                                    html += `
                                                                            <tr>
                                                                                <th>Register Date</th>
                                                                                <td>${d.register_date ? escapeHtml(d.register_date) : ''}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Installment No</th>
                                                                                <td>${d.installment_no ? escapeHtml(d.installment_no) : ''}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Registration Fee</th>
                                                                                <td>${d.registration_fee ? escapeHtml(d.registration_fee) : ''}</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <th>Only Course Fee</th>
                                                                                <td>${d.only_course_fee ? escapeHtml(d.only_course_fee) : ''}</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>`;
                                                                } else {
                                                                    html = `<div class="alert alert-warning mt-2">Not allocated. No payment batch allocation details for this batch.</div>`;
                                                                }
                                                                $('#batchDetailsWrapper').html(html);
                                                                updateSbtBtnVisibility(batchId);
                                                            }, 200); // simulate loading latency
                                                        });

                                                        // Simple HTML escape to prevent code injection
                                                        function escapeHtml(text) {
                                                            return $('<div/>').text(text).html();
                                                        }
                                                    });
                                                </script>

                                                <!-- END ALLOCATION SECTION -->
                                                <hr>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="active"
                                                        name="active" value="1" <?= (!isset($studentDetails['active']) || $studentDetails['active']) ? "checked" : "" ?>>
                                                    <label class="form-check-label" for="active">Active</label>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input"
                                                        id="conditional_offer_letter" name="conditional_offer_letter"
                                                        value="1" <?= (isset($studentDetails['conditional_offer_letter']) && $studentDetails['conditional_offer_letter']) ? "checked" : "" ?>>
                                                    <label class="form-check-label"
                                                        for="conditional_offer_letter">Conditional Offer Letter</label>
                                                </div>
                                                <div class="mb-3" id="conditional_text_div"
                                                    style="<?= (isset($studentDetails['conditional_offer_letter']) && $studentDetails['conditional_offer_letter']) ? '' : 'display:none;' ?>">
                                                    <label for="conditional_offer_letter_text" class="form-label">Condition
                                                        01:</label>
                                                    <input type="text" class="form-control mb-2"
                                                        name="conditional_offer_letter_text"
                                                        id="conditional_offer_letter_text"
                                                        placeholder="Enter condition 01..."
                                                        value="<?= isset($studentDetails['conditional_offer_letter_text']) ? htmlspecialchars($studentDetails['conditional_offer_letter_text']) : '' ?>"
                                                        <?= (isset($studentDetails['conditional_offer_letter']) && $studentDetails['conditional_offer_letter']) ? 'required' : '' ?>>

                                                    <label for="conditional_offer_letter_text_02"
                                                        class="form-label">Condition 02:</label>
                                                    <input type="text" class="form-control mb-2"
                                                        name="conditional_offer_letter_text_02"
                                                        id="conditional_offer_letter_text_02"
                                                        placeholder="Enter condition 02..."
                                                        value="<?= isset($studentDetails['conditional_offer_letter_text_02']) ? htmlspecialchars($studentDetails['conditional_offer_letter_text_02']) : '' ?>">

                                                    <label for="conditional_offer_letter_text_03"
                                                        class="form-label">Condition 03:</label>
                                                    <input type="text" class="form-control mb-2"
                                                        name="conditional_offer_letter_text_03"
                                                        id="conditional_offer_letter_text_03"
                                                        placeholder="Enter condition 03..."
                                                        value="<?= isset($studentDetails['conditional_offer_letter_text_03']) ? htmlspecialchars($studentDetails['conditional_offer_letter_text_03']) : '' ?>">

                                                    <label for="conditional_offer_letter_text_04"
                                                        class="form-label">Condition 04:</label>
                                                    <input type="text" class="form-control mb-2"
                                                        name="conditional_offer_letter_text_04"
                                                        id="conditional_offer_letter_text_04"
                                                        placeholder="Enter condition 04..."
                                                        value="<?= isset($studentDetails['conditional_offer_letter_text_04']) ? htmlspecialchars($studentDetails['conditional_offer_letter_text_04']) : '' ?>">
                                                </div>


                                                <?php
                                                // Handle approval logic on form submission
                                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['student_id'])) {
                                                    $studentId = intval($_GET['student_id']);
                                                    $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
                                                    if ($username && $studentId) {
                                                        $updateSql = "UPDATE students_temporary_registration SET approved = 'approved', approved_by = ? WHERE id = ?";
                                                        if ($stmt = $conn->prepare($updateSql)) {
                                                            $stmt->bind_param("si", $username, $studentId);
                                                            $stmt->execute();
                                                            $stmt->close();
                                                            echo "<script>alert('Student approved successfully!');</script>";
                                                            echo "<script>window.location.href = 'online_registration_data';</script>";
                                                        }
                                                    }
                                                }
                                                ?>

                                                <div class="mb-3">
                                                    <button type="submit" name="action" value="approve"
                                                        class="btn btn-primary float-right ml-2" id="sbt_btn">Accept
                                                    </button>
                                                    <button type="submit" name="action" value="reject"
                                                        class="btn btn-danger float-right" id="rej_btn">Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif (isset($_GET['student_id']) && !$studentDetails): ?>
                    <div class="no-data alert alert-danger mt-4">Student not found or no data.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                width: '100%'
            });

            // Auto-fill Certificate Name as "First Name Last Name" when first/last name changes (if it's blank or matches)
            function updateCertificateName(auto = false) {
                var fname = $('#first_name').val().trim();
                var lname = $('#last_name').val().trim();
                var certField = $('#certificate_name');
                var existingCertVal = certField.val().trim();
                var autoVal = fname && lname ? (fname + ' ' + lname) : (fname || lname);

                // If user has not manually changed certificate name, or it's same as the old auto-fill, update it
                if (
                    auto ||
                    existingCertVal === "" ||
                    existingCertVal === autoVal // If it matches the previous
                ) {
                    certField.val(autoVal);
                }
            }
            $('#first_name, #last_name').on('input', function () {
                updateCertificateName(true);

                // Also update preferred name if blank or same as auto
                var fname = $('#first_name').val().trim();
                var lname = $('#last_name').val().trim();
                var prefField = $('#preferred_name');
                var existingPrefVal = prefField.val().trim();
                var autoPrefVal = fname && lname ? (fname + ' ' + lname) : (fname || lname);

                if (
                    existingPrefVal === "" ||
                    existingPrefVal === autoPrefVal
                ) {
                    prefField.val(autoPrefVal);
                }
            });
            // On page load, try to fill if its empty
            updateCertificateName();
            (function updatePreferredNameInit() {
                var fname = $('#first_name').val().trim();
                var lname = $('#last_name').val().trim();
                var prefField = $('#preferred_name');
                var existingPrefVal = prefField.val().trim();
                var autoPrefVal = fname && lname ? (fname + ' ' + lname) : (fname || lname);
                if (
                    existingPrefVal === "" ||
                    existingPrefVal === autoPrefVal
                ) {
                    prefField.val(autoPrefVal);
                }
            })();

            // Toggle conditional offer letter text input
            $('#conditional_offer_letter').on('change', function () {
                if ($(this).is(':checked')) {
                    $('#conditional_text_div').show();
                    $('#conditional_offer_letter_text').prop('required', true);
                } else {
                    $('#conditional_text_div').hide();
                    $('#conditional_offer_letter_text').val('').prop('required', false);
                    $('#conditional_offer_letter_text_02').val('');
                    $('#conditional_offer_letter_text_03').val('');
                    $('#conditional_offer_letter_text_04').val('');
                }
            });

        });
        $(document).on('click', '#rej_btn', function (e) {
            if (!confirm("Are you sure you want to reject this application? This will send a rejection email to the student.")) {
                e.preventDefault();
            }
        });
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
</div>
</body>

</html>