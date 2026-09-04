<?php
session_start();
$Session_username = $_SESSION['username'];
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 

$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $edit_query = "SELECT * FROM student_documents WHERE id = '$edit_id'";
    $edit_result = mysqli_query($conn, $edit_query);
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_result);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the student code
    $student_code = mysqli_real_escape_string($conn, $_POST['student_code']);
    $id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;

    // Prepare an array to hold the file paths
    $file_paths = [];

    // Define the fields to be checked
    $fields = [
        'registration_receipt',
        'cv',
        'nic_passport',
        'education_qualification_1',
        'education_qualification_2',
        'education_qualification_3',
        'education_qualification_4',
        'experience_1',
        'experience_2',
        'experience_3',
        'experience_4',
        'photo',
        'other'
    ];

    // Get student's NIC for folder organization
    $student_info_query = "SELECT nic FROM students WHERE student_code = '$student_code'";
    $student_info_result = mysqli_query($conn, $student_info_query);
    $student_nic_folder = "unknown_student";
    if ($row = mysqli_fetch_assoc($student_info_result)) {
        // Sanitize NIC for folder name (remove characters that might be invalid)
        $student_nic_folder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['nic']);
    }

    $upload_success = true;
    $errors = [];

    // Loop through each field and check if a file is uploaded
    foreach ($fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            // File is uploaded, process it
            $target_dir = "uploads/student_docs/" . $student_nic_folder . "/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES[$field]["name"], PATHINFO_EXTENSION);
            // Create a unique filename: studentCode_fieldName_timestamp.extension
            $new_filename = $student_code . "_" . $field . "_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES[$field]["tmp_name"], $target_file)) {
                // File successfully uploaded, store the file path
                $file_paths[$field] = $target_file;
            } else {
                $file_paths[$field] = null;
                $errors[] = "Failed to upload $field.";
                $upload_success = false;
            }
        } else {
            // If editing and no new file, keep the old one
            if ($id && isset($_POST['current_' . $field])) {
                $file_paths[$field] = $_POST['current_' . $field];
            } else {
                $file_paths[$field] = null;
            }
        }
    }

    if ($upload_success) {
        // Prepare values for SQL
        $registration_receipt = mysqli_real_escape_string($conn, $file_paths['registration_receipt']);
        $cv = mysqli_real_escape_string($conn, $file_paths['cv']);
        $nic_passport = mysqli_real_escape_string($conn, $file_paths['nic_passport']);
        $edu1 = mysqli_real_escape_string($conn, $file_paths['education_qualification_1']);
        $edu2 = mysqli_real_escape_string($conn, $file_paths['education_qualification_2']);
        $edu3 = mysqli_real_escape_string($conn, $file_paths['education_qualification_3']);
        $edu4 = mysqli_real_escape_string($conn, $file_paths['education_qualification_4']);
        $exp1 = mysqli_real_escape_string($conn, $file_paths['experience_1']);
        $exp2 = mysqli_real_escape_string($conn, $file_paths['experience_2']);
        $exp3 = mysqli_real_escape_string($conn, $file_paths['experience_3']);
        $exp4 = mysqli_real_escape_string($conn, $file_paths['experience_4']);
        $photo = mysqli_real_escape_string($conn, $file_paths['photo']);
        $other = mysqli_real_escape_string($conn, $file_paths['other']);

        if ($id) {
            // Check if any files were explicitly marked for removal (not in current_ field)
            // The logic below already handles this: if !isset($_FILES) and !isset($_POST['current_field']), it becomes null

            // Update logic
            $sql = "UPDATE student_documents SET 
                    student_code = '$student_code', 
                    registration_receipt = " . ($registration_receipt ? "'$registration_receipt'" : "NULL") . ", 
                    cv = " . ($cv ? "'$cv'" : "NULL") . ", 
                    nic_passport = " . ($nic_passport ? "'$nic_passport'" : "NULL") . ", 
                    education_qualification_1 = " . ($edu1 ? "'$edu1'" : "NULL") . ", 
                    education_qualification_2 = " . ($edu2 ? "'$edu2'" : "NULL") . ", 
                    education_qualification_3 = " . ($edu3 ? "'$edu3'" : "NULL") . ", 
                    education_qualification_4 = " . ($edu4 ? "'$edu4'" : "NULL") . ", 
                    experience_1 = " . ($exp1 ? "'$exp1'" : "NULL") . ", 
                    experience_2 = " . ($exp2 ? "'$exp2'" : "NULL") . ", 
                    experience_3 = " . ($exp3 ? "'$exp3'" : "NULL") . ", 
                    experience_4 = " . ($exp4 ? "'$exp4'" : "NULL") . ", 
                    photo = " . ($photo ? "'$photo'" : "NULL") . ", 
                    other = " . ($other ? "'$other'" : "NULL") . " 
                    WHERE id = '$id'";
            $msg = "Documents updated successfully!";
        } else {
            // Insert logic
            $sql = "INSERT INTO student_documents (student_code, registration_receipt, cv, nic_passport, education_qualification_1, education_qualification_2, education_qualification_3, education_qualification_4, experience_1, experience_2, experience_3, experience_4, photo, other, entered_by)
                    VALUES ('$student_code', '$registration_receipt', '$cv', '$nic_passport', '$edu1', '$edu2', '$edu3', '$edu4', '$exp1', '$exp2', '$exp3', '$exp4', '$photo', '$other', '$Session_username')";
            $msg = "Documents uploaded successfully!";
        }

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('$msg'); window.location.href='uploadScanCopies.php';</script>";
        } else {
            $errors[] = "Database Error: " . mysqli_error($conn);
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<script>alert('$error');</script>";
        }
    }
}
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <!-- Sidebar -->
    <?php include("nav.php"); ?>

    <style>
        .hr_margin_top {
            margin-top: -20px;
        }

        .dropdown-style {
            font-size: 13px;
        }

        .current-file {
            font-size: 11px;
            color: #2e59d9;
            text-decoration: underline;
            display: block;
            margin-top: 2px;
        }
    </style>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column" style="font-size: 14px;">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>

            <!-- Begin Page Content -->
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800"><?php echo $edit_data ? 'Edit' : 'Upload'; ?> Student Documents
                    </h4>
                </div>

                <!-- Form Section -->
                <div class="row mb-5 justify-content-center">
                    <div class="col-md-10">
                        <div class="card shadow-lg border-0">
                            <div
                                class="card-header bg-gradient-primary py-3 d-flex align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-white">
                                    <i
                                        class="fas <?php echo $edit_data ? 'fa-edit' : 'fa-cloud-upload-alt'; ?> mr-2"></i>
                                    <?php echo $edit_data ? 'Update Document Records' : 'Register New Document Set'; ?>
                                </h6>
                            </div>
                            <div class="card-body p-4">
                                <form action="" method="POST" enctype="multipart/form-data" class="user">
                                    <?php if ($edit_data): ?>
                                        <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                                    <?php endif; ?>

                                    <!-- Student Selection -->
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="form-group border-left-primary rounded shadow-sm p-4 bg-white">
                                                <label for="student_code"
                                                    class="text-primary font-weight-bold mb-3 d-block">
                                                    <i class="fas fa-user-check mr-2"></i>Target Student Selection
                                                </label>
                                                <select name="student_code" id="student_code"
                                                    class="form-control select2" required>
                                                    <option value="">Search by NIC, Name or Code...</option>
                                                    <?php
                                                    $query = "SELECT student_code, nic, CONCAT(first_name, ' ', last_name) as full_name FROM students ORDER BY first_name";
                                                    $result = mysqli_query($conn, $query);
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        $selected = ($edit_data && $edit_data['student_code'] == $row['student_code']) ? 'selected' : '';
                                                        echo "<option value='" . $row['student_code'] . "' $selected>" . $row['nic'] . " - " . $row['full_name'] . " (" . $row['student_code'] . ")</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <small class="text-muted mt-2 d-block"><i
                                                        class="fas fa-info-circle mr-1"></i> Ensure you select the
                                                    correct student before uploading files.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Left Column: Essential Documents -->
                                        <div class="col-lg-6 pr-lg-4 border-right">
                                            <h6 class="font-weight-bold text-gray-800 mb-4 pb-2 border-bottom">
                                                <i class="fas fa-id-card mr-2 text-info"></i>Primary Identity &
                                                Registration
                                            </h6>

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-dark">Registration Receipt
                                                    <span class="text-danger">*</span></label>
                                                <div class="custom-file-container">
                                                    <input type="file" name="registration_receipt"
                                                        class="form-control-file border rounded p-1 w-100 mb-1" <?php echo !$edit_data ? 'required' : ''; ?>>
                                                    <?php if ($edit_data && !empty($edit_data['registration_receipt']) && file_exists($edit_data['registration_receipt'])): ?>
                                                        <div class="current-file-wrapper" id="wrapper_registration_receipt">
                                                            <input type="hidden" name="current_registration_receipt"
                                                                value="<?php echo $edit_data['registration_receipt']; ?>">
                                                            <a href="<?php echo $edit_data['registration_receipt']; ?>"
                                                                target="_blank" class="badge badge-info py-1 px-2"><i
                                                                    class="fas fa-paperclip mr-1"></i> Current Receipt</a>
                                                            <button type="button"
                                                                class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                data-target="registration_receipt" title="Remove File"><i
                                                                    class="fas fa-times-circle"></i></button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-dark">Student CV / Bio-data
                                                    <span class="text-danger">*</span></label>
                                                <div class="custom-file-container">
                                                    <input type="file" name="cv"
                                                        class="form-control-file border rounded p-1 w-100 mb-1" <?php echo !$edit_data ? 'required' : ''; ?>>
                                                    <?php if ($edit_data && !empty($edit_data['cv']) && file_exists($edit_data['cv'])): ?>
                                                        <div class="current-file-wrapper" id="wrapper_cv">
                                                            <input type="hidden" name="current_cv"
                                                                value="<?php echo $edit_data['cv']; ?>">
                                                            <a href="<?php echo $edit_data['cv']; ?>" target="_blank"
                                                                class="badge badge-info py-1 px-2"><i
                                                                    class="fas fa-paperclip mr-1"></i> Current CV</a>
                                                            <button type="button"
                                                                class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                data-target="cv" title="Remove File"><i
                                                                    class="fas fa-times-circle"></i></button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-dark">NIC / Passport Scanned
                                                    Copy <span class="text-danger">*</span></label>
                                                <div class="custom-file-container">
                                                    <input type="file" name="nic_passport"
                                                        class="form-control-file border rounded p-1 w-100 mb-1" <?php echo !$edit_data ? 'required' : ''; ?>>
                                                    <?php if ($edit_data && !empty($edit_data['nic_passport']) && file_exists($edit_data['nic_passport'])): ?>
                                                        <div class="current-file-wrapper" id="wrapper_nic_passport">
                                                            <input type="hidden" name="current_nic_passport"
                                                                value="<?php echo $edit_data['nic_passport']; ?>">
                                                            <a href="<?php echo $edit_data['nic_passport']; ?>"
                                                                target="_blank" class="badge badge-info py-1 px-2"><i
                                                                    class="fas fa-paperclip mr-1"></i> Current ID Copy</a>
                                                            <button type="button"
                                                                class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                data-target="nic_passport" title="Remove File"><i
                                                                    class="fas fa-times-circle"></i></button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="form-group mb-4">
                                                <label class="small font-weight-bold text-dark">Official Photograph
                                                    <span class="text-danger">*</span></label>
                                                <div class="custom-file-container">
                                                    <input type="file" name="photo"
                                                        class="form-control-file border rounded p-1 w-100 mb-1" <?php echo !$edit_data ? 'required' : ''; ?>>
                                                    <?php if ($edit_data && !empty($edit_data['photo']) && file_exists($edit_data['photo'])): ?>
                                                        <div class="current-file-wrapper" id="wrapper_photo">
                                                            <input type="hidden" name="current_photo"
                                                                value="<?php echo $edit_data['photo']; ?>">
                                                            <div class="mt-2 d-flex align-items-center">
                                                                <img src="<?php echo $edit_data['photo']; ?>"
                                                                    class="img-profile rounded border shadow-sm mr-2"
                                                                    width="50" height="50">
                                                                <a href="<?php echo $edit_data['photo']; ?>" target="_blank"
                                                                    class="badge badge-info shadow-xs">Current Photo</a>
                                                                <button type="button"
                                                                    class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                    data-target="photo" title="Remove File"><i
                                                                        class="fas fa-times-circle"></i></button>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: Academic & Experience -->
                                        <div class="col-lg-6 pl-lg-4">
                                            <h6 class="font-weight-bold text-gray-800 mb-4 pb-2 border-bottom">
                                                <i class="fas fa-graduation-cap mr-2 text-success"></i>Academic &
                                                Professional Records
                                            </h6>

                                            <!-- Education Section -->
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="small font-weight-bold text-dark mb-0">Educational
                                                        Certificates</label>
                                                    <button type="button" id="addMoreEducationBtn"
                                                        class="btn btn-outline-success btn-sm py-0 px-2"
                                                        style="font-size: 11px;"><i class="fas fa-plus mr-1"></i> Add
                                                        More</button>
                                                </div>
                                                <div id="edu_inputs" class="bg-light p-2 rounded">
                                                    <div class="mb-2">
                                                        <input type="file" name="education_qualification_1"
                                                            class="form-control-file border rounded bg-white p-1 w-100 mb-1">
                                                        <?php if ($edit_data && !empty($edit_data['education_qualification_1']) && file_exists($edit_data['education_qualification_1'])): ?>
                                                            <div class="current-file-wrapper"
                                                                id="wrapper_education_qualification_1">
                                                                <input type="hidden"
                                                                    name="current_education_qualification_1"
                                                                    value="<?php echo $edit_data['education_qualification_1']; ?>">
                                                                <a href="<?php echo $edit_data['education_qualification_1']; ?>"
                                                                    target="_blank" class="badge badge-secondary"
                                                                    style="font-size: 10px;">Document 01 Attached</a>
                                                                <button type="button"
                                                                    class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                    data-target="education_qualification_1"
                                                                    title="Remove File"><i class="fas fa-times-circle"
                                                                        style="font-size: 10px;"></i></button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php for ($i = 2; $i <= 4; $i++):
                                                        $field = "education_qualification_$i";
                                                        $disp = ($edit_data && !empty($edit_data[$field])) ? 'block' : 'none';
                                                        ?>
                                                        <div id="edu_container_<?php echo $i; ?>" class="mb-2"
                                                            style="display: <?php echo $disp; ?>;">
                                                            <input type="file" name="<?php echo $field; ?>"
                                                                class="form-control-file border rounded bg-white p-1 w-100 mb-1">
                                                            <?php if ($edit_data && !empty($edit_data[$field]) && file_exists($edit_data[$field])): ?>
                                                                <div class="current-file-wrapper"
                                                                    id="wrapper_<?php echo $field; ?>">
                                                                    <input type="hidden" name="current_<?php echo $field; ?>"
                                                                        value="<?php echo $edit_data[$field]; ?>">
                                                                    <a href="<?php echo $edit_data[$field]; ?>" target="_blank"
                                                                        class="badge badge-secondary"
                                                                        style="font-size: 10px;">Document 0<?php echo $i; ?>
                                                                        Attached</a>
                                                                    <button type="button"
                                                                        class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                        data-target="<?php echo $field; ?>"
                                                                        title="Remove File"><i class="fas fa-times-circle"
                                                                            style="font-size: 10px;"></i></button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>

                                            <!-- Experience Section -->
                                            <div class="mb-4 text-dark">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="small font-weight-bold mb-0">Service / Experience
                                                        Letters</label>
                                                    <button type="button" id="addMoreExperienceBtn"
                                                        class="btn btn-outline-secondary btn-sm py-0 px-2"
                                                        style="font-size: 11px;"><i class="fas fa-link mr-1"></i> Add
                                                        More</button>
                                                </div>
                                                <div id="exp_inputs" class="bg-light p-2 rounded">
                                                    <div class="mb-2">
                                                        <input type="file" name="experience_1"
                                                            class="form-control-file border rounded bg-white p-1 w-100 mb-1">
                                                        <?php if ($edit_data && !empty($edit_data['experience_1']) && file_exists($edit_data['experience_1'])): ?>
                                                            <div class="current-file-wrapper" id="wrapper_experience_1">
                                                                <input type="hidden" name="current_experience_1"
                                                                    value="<?php echo $edit_data['experience_1']; ?>">
                                                                <a href="<?php echo $edit_data['experience_1']; ?>"
                                                                    target="_blank" class="badge badge-dark"
                                                                    style="font-size: 10px;">Letter 01 Attached</a>
                                                                <button type="button"
                                                                    class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                    data-target="experience_1" title="Remove File"><i
                                                                        class="fas fa-times-circle"
                                                                        style="font-size: 10px;"></i></button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php for ($i = 2; $i <= 4; $i++):
                                                        $field = "experience_$i";
                                                        $disp = ($edit_data && !empty($edit_data[$field])) ? 'block' : 'none';
                                                        ?>
                                                        <div id="exp_container_<?php echo $i; ?>" class="mb-2"
                                                            style="display: <?php echo $disp; ?>;">
                                                            <input type="file" name="<?php echo $field; ?>"
                                                                class="form-control-file border rounded bg-white p-1 w-100 mb-1">
                                                            <?php if ($edit_data && !empty($edit_data[$field]) && file_exists($edit_data[$field])): ?>
                                                                <div class="current-file-wrapper"
                                                                    id="wrapper_<?php echo $field; ?>">
                                                                    <input type="hidden" name="current_<?php echo $field; ?>"
                                                                        value="<?php echo $edit_data[$field]; ?>">
                                                                    <a href="<?php echo $edit_data[$field]; ?>" target="_blank"
                                                                        class="badge badge-dark" style="font-size: 10px;">Letter
                                                                        0<?php echo $i; ?> Attached</a>
                                                                    <button type="button"
                                                                        class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                                        data-target="<?php echo $field; ?>"
                                                                        title="Remove File"><i class="fas fa-times-circle"
                                                                            style="font-size: 10px;"></i></button>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="small font-weight-bold text-gray-700">Other Miscellaneous
                                                    Documents</label>
                                                <input type="file" name="other"
                                                    class="form-control-file border rounded p-1 w-100 mb-1">
                                                <?php if ($edit_data && !empty($edit_data['other']) && file_exists($edit_data['other'])): ?>
                                                    <div class="current-file-wrapper" id="wrapper_other">
                                                        <input type="hidden" name="current_other"
                                                            value="<?php echo $edit_data['other']; ?>">
                                                        <a href="<?php echo $edit_data['other']; ?>" target="_blank"
                                                            class="badge badge-light border text-dark"
                                                            style="font-size: 10px;">Current Supplemental File</a>
                                                        <button type="button"
                                                            class="btn btn-link btn-sm text-danger p-0 ml-1 remove-file-trigger"
                                                            data-target="other" title="Remove File"><i
                                                                class="fas fa-times-circle"
                                                                style="font-size: 10px;"></i></button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-5 border-top pt-4">
                                        <button type="submit"
                                            class="btn btn-primary btn-icon-split shadow hover-elevate-up btn-sm">
                                            <span class="icon text-white-50">
                                                <i
                                                    class="fas <?php echo $edit_data ? 'fa-save' : 'fa-cloud-upload-alt'; ?>"></i>
                                            </span>
                                            <span
                                                class="text px-4 font-weight-bold"><?php echo $edit_data ? 'Save Changes' : 'Upload Student Documents'; ?></span>
                                        </button>
                                        <?php if ($edit_data): ?>
                                            <a href="uploadScanCopies.php"
                                                class="btn btn-light btn-icon-split ml-3 shadow-sm">
                                                <span class="icon text-gray-600">
                                                    <i class="fas fa-arrow-left"></i>
                                                </span>
                                                <span class="text">Back to List</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="card shadow-sm border-0 mb-4">
                    <div
                        class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div>
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-database mr-2"></i> Document Repository
                            </h6>
                            <small class="text-muted">Detailed view of all student document submissions</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $query = "SELECT sd.*, s.first_name, s.last_name, s.nic as student_nic 
                                  FROM student_documents sd 
                                  LEFT JOIN students s ON sd.student_code = s.student_code 
                                  ORDER BY sd.id DESC";
                        $result = mysqli_query($conn, $query);
                        $count = 1;
                        ?>

                        <div class="table-responsive">
                            <table id="documentsTable" class="table table-hover align-middle mb-0" width="100%"
                                cellspacing="0">
                                <thead class="bg-gray-100 text-dark uppercase small font-weight-bold">
                                    <tr>
                                        <th class="pl-4">#</th>
                                        <th style="min-width: 180px;">Student Profile</th>
                                        <th class="text-center">Receipt</th>
                                        <th class="text-center">CV</th>
                                        <th class="text-center">NIC/Pass</th>
                                        <th class="text-center">Education</th>
                                        <th class="text-center">Experience</th>
                                        <th class="text-center">Other</th>
                                        <th class="text-center">Photo</th>
                                        <th class="text-center text-primary"><i class="fas fa-file-archive mr-1"></i>Zip
                                        </th>
                                        <th class="text-center">Admin</th>
                                        <th class="text-center pr-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr style="height: 70px;">
                                            <td class="pl-4 font-weight-bold text-gray-400 small align-middle">
                                                <?php echo str_pad($count++, 2, '0', STR_PAD_LEFT); ?>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-2 shadow-sm"
                                                        style="width: 35px; height: 35px; min-width: 35px;">
                                                        <i class="fas fa-user-graduate text-primary"
                                                            style="font-size: 12px;"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-dark font-weight-bold mb-0"
                                                            style="font-size: 13px; line-height: 1.2;">
                                                            <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                                                        </div>
                                                        <div class="text-muted small"><?php echo $row['student_code']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Registration Receipt -->
                                            <td class="text-center align-middle">
                                                <?php if (!empty($row['registration_receipt']) && file_exists($row['registration_receipt'])): ?>
                                                    <a href="<?php echo $row['registration_receipt']; ?>" target="_blank"
                                                        class="btn btn-outline-info btn-sm btn-circle shadow-xs"
                                                        title="View Receipt">
                                                        <i class="fas fa-receipt"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light text-gray-300 px-2">Missing</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- CV / Bio-data -->
                                            <td class="text-center align-middle">
                                                <?php if (!empty($row['cv']) && file_exists($row['cv'])): ?>
                                                    <a href="<?php echo $row['cv']; ?>" target="_blank"
                                                        class="btn btn-outline-danger btn-sm btn-circle shadow-xs"
                                                        title="View CV">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light text-gray-300 px-2">Missing</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- NIC / Passport -->
                                            <td class="text-center align-middle">
                                                <?php if (!empty($row['nic_passport']) && file_exists($row['nic_passport'])): ?>
                                                    <a href="<?php echo $row['nic_passport']; ?>" target="_blank"
                                                        class="btn btn-outline-primary btn-sm btn-circle shadow-xs"
                                                        title="View ID">
                                                        <i class="fas fa-id-card"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge badge-light text-gray-300 px-2">Missing</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Educational Certificates -->
                                            <td class="text-center align-middle">
                                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                                    <?php
                                                    $edu_total = 0;
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $field = "education_qualification_$i";
                                                        if (!empty($row[$field]) && file_exists($row[$field])) {
                                                            echo '<a href="' . $row[$field] . '" target="_blank" class="badge badge-soft-success mb-1" style="background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 9px;" title="Certificate ' . $i . '">C' . $i . '</a>';
                                                            $edu_total++;
                                                        }
                                                    }
                                                    if ($edu_total == 0)
                                                        echo '<span class="text-gray-300 mt-1"><i class="fas fa-times"></i></span>';
                                                    ?>
                                                </div>
                                            </td>

                                            <!-- Service / Experience Letters -->
                                            <td class="text-center align-middle">
                                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                                    <?php
                                                    $exp_total = 0;
                                                    for ($i = 1; $i <= 4; $i++) {
                                                        $field = "experience_$i";
                                                        if (!empty($row[$field]) && file_exists($row[$field])) {
                                                            echo '<a href="' . $row[$field] . '" target="_blank" class="badge badge-soft-secondary mb-1" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 9px;" title="Experience ' . $i . '">E' . $i . '</a>';
                                                            $exp_total++;
                                                        }
                                                    }
                                                    if ($exp_total == 0)
                                                        echo '<span class="text-gray-300 mt-1"><i class="fas fa-times"></i></span>';
                                                    ?>
                                                </div>
                                            </td>

                                            <!-- Other Miscellaneous -->
                                            <td class="text-center align-middle">
                                                <?php if (!empty($row['other']) && file_exists($row['other'])): ?>
                                                    <a href="<?php echo $row['other']; ?>" target="_blank"
                                                        class="btn btn-outline-warning btn-sm btn-circle shadow-xs"
                                                        title="Other Documents">
                                                        <i class="fas fa-folder-open"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-300"><i class="fas fa-minus"></i></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Official Photograph -->
                                            <td class="text-center align-middle">
                                                <?php if (!empty($row['photo']) && file_exists($row['photo'])): ?>
                                                    <a href="<?php echo $row['photo']; ?>" target="_blank">
                                                        <img src="<?php echo $row['photo']; ?>" class="rounded shadow-sm border"
                                                            width="35" height="40" style="object-fit: cover;">
                                                    </a>
                                                <?php else: ?>
                                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center mx-auto"
                                                        style="width: 35px; height: 40px;">
                                                        <i class="fas fa-user-alt text-gray-300 small"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <td class="text-center align-middle">
                                                <a href="download_student_docs.php?id=<?php echo $row['id']; ?>"
                                                    class="btn btn-primary btn-sm btn-icon-split shadow-xs"
                                                    style="padding: 2px 8px; font-size: 10px;">
                                                    <span class="icon text-white-50"><i class="fas fa-download"></i></span>
                                                    <span class="text">Docs</span>
                                                </a>
                                            </td>

                                            <td class="text-center align-middle">
                                                <div class="small font-weight-bold text-gray-600 mb-0">
                                                    <?php echo $row['entered_by']; ?>
                                                </div>
                                                <div class="text-gray-400" style="font-size: 9px;">System Log</div>
                                            </td>

                                            <td class="text-center pr-4 align-middle">
                                                <a href="uploadScanCopies.php?edit_id=<?php echo $row['id']; ?>"
                                                    class="btn btn-white btn-sm border shadow-sm btn-circle text-warning"
                                                    title="Edit Data">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dynamic Fields -->
<script>
    $(document).ready(function () {
        $('#documentsTable').DataTable({
            "pageLength": 10,
            "ordering": true,
            "language": {
                "search": "Quick Filter:"
            },
            "columnDefs": [
                { "orderable": false, "targets": [2, 3, 4, 5, 6, 7, 8, 10] } // Disable ordering on doc columns
            ]
        });

        $('#student_code').select2({
            width: '100%',
            placeholder: "Select a student",
            allowClear: true
        });

        // Education Section
        let eduFieldCount = <?php
        $count = 1;
        if ($edit_data) {
            for ($i = 2; $i <= 4; $i++)
                if (!empty($edit_data['education_qualification_' . $i]))
                    $count = $i;
        }
        echo (int) $count;
        ?>;
        const maxEduFields = 4;
        const addMoreEducationBtn = document.getElementById("addMoreEducationBtn");

        addMoreEducationBtn.addEventListener("click", function () {
            if (eduFieldCount < maxEduFields) {
                eduFieldCount++;
                document.getElementById("edu_container_" + eduFieldCount).style.display = "flex";
                if (eduFieldCount === maxEduFields) addMoreEducationBtn.style.display = "none";
            }
        });

        // Experience Section
        let expFieldCount = <?php
        $count = 1;
        if ($edit_data) {
            for ($i = 2; $i <= 4; $i++)
                if (!empty($edit_data['experience_' . $i]))
                    $count = $i;
        }
        echo (int) $count;
        ?>;
        const maxExpFields = 4;
        const addMoreExperienceBtn = document.getElementById("addMoreExperienceBtn");

        addMoreExperienceBtn.addEventListener("click", function () {
            if (expFieldCount < maxExpFields) {
                expFieldCount++;
                document.getElementById("exp_container_" + expFieldCount).style.display = "flex";
                if (expFieldCount === maxExpFields) addMoreExperienceBtn.style.display = "none";
            }
        });

        if (eduFieldCount >= maxEduFields) addMoreEducationBtn.style.display = "none";
        if (expFieldCount >= maxExpFields) addMoreExperienceBtn.style.display = "none";

        // File Removal Implementation
        $(".remove-file-trigger").on("click", function () {
            const field = $(this).data("target");
            const wrapper = $("#wrapper_" + field);

            if (confirm("Are you sure you want to remove this document from the record? (This will take effect after you click Save Changes)")) {
                // Clear the hidden input so it won't be sent in POST
                wrapper.find('input[type="hidden"]').remove();
                // Hide the preview badge
                wrapper.fadeOut(300, function () {
                    $(this).after('<span class="badge badge-warning small">Removed (Pending Save)</span>');
                });
            }
        });
    });
</script>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<?php include("includes/footer.php"); ?>