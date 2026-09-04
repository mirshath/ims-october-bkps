<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM student_documents WHERE id = $id";
    $result_doc = mysqli_query($conn, $query);

    if (!$result_doc) {
        echo "Error: " . mysqli_error($conn);
        exit;
    }

    $row_doc = mysqli_fetch_assoc($result_doc);

    if (!$row_doc) {
        echo "Document not found!";
        exit;
    }
} else {
    echo "Invalid request!";
    exit;
}

// Handle Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_code = $_POST['student_code'];
    $entered_by = $_POST['entered_by'];

    // Handling File Uploads
    function uploadFile($fileInput, $oldFile)
    {
        if (!empty($_FILES[$fileInput]['name'])) {
            $targetDir = "uploads/";
            $fileName = basename($_FILES[$fileInput]['name']);
            $targetFilePath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $targetFilePath)) {
                return $targetFilePath;
            } else {
                echo "Error uploading file: " . $_FILES[$fileInput]['name'];
                return $oldFile; // Return old file if upload fails
            }
        }
        return $oldFile; // Keep old file if no new file uploaded
    }

    $registration_receipt = uploadFile('registration_receipt', $row_doc['registration_receipt']);
    $cv = uploadFile('cv', $row_doc['cv']);
    $nic_passport = uploadFile('nic_passport', $row_doc['nic_passport']);
    $education_qualification_1 = uploadFile('education_qualification_1', $row_doc['education_qualification_1']);
    $education_qualification_2 = uploadFile('education_qualification_2', $row_doc['education_qualification_2']);

    $education_qualification_3 = uploadFile('education_qualification_3', $row_doc['education_qualification_3']);
    $education_qualification_4 = uploadFile('education_qualification_4', $row_doc['education_qualification_4']);
    $experience_1 = uploadFile('experience_1', $row_doc['experience_1']);
    $experience_2 = uploadFile('experience_2', $row_doc['experience_2']);
    $experience_3 = uploadFile('experience_3', $row_doc['experience_3']);
    $experience_4 = uploadFile('experience_4', $row_doc['experience_4']);
    $photo = uploadFile('photo', $row_doc['photo']);
    $other = uploadFile('other', $row_doc['other']);

    // Update query for new fields
    $updateQuery = "UPDATE student_documents SET 
student_code='$student_code',
entered_by='$entered_by',
registration_receipt='$registration_receipt',
cv='$cv',
nic_passport='$nic_passport',
education_qualification_1='$education_qualification_1',
education_qualification_2='$education_qualification_2',
education_qualification_3='$education_qualification_3',
education_qualification_4='$education_qualification_4',
experience_1='$experience_1',
experience_2='$experience_2',
experience_3='$experience_3',
experience_4='$experience_4',
photo='$photo',
other='$other'
WHERE id = $id";


    if (mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('Document updated successfully!'); window.location.href='uploadScanCopies';</script>";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }
}
?>

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
                    <h4 class="h4 mb-0 text-gray-800">Edit Student Document</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Student Update Data</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="row">
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <!-- <label for="student_code">Student Code:</label> -->
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="hidden" class="form-control" id="student_code" name="student_code" value="<?= htmlspecialchars($row_doc['student_code']) ?>" required>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="registration_receipt">Registration Receipt:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="registration_receipt" name="registration_receipt">
                                                        <a href="<?= htmlspecialchars($row_doc['registration_receipt']) ?>" target="_blank">View</a>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="cv">CV:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="cv" name="cv">
                                                        <a href="<?= htmlspecialchars($row_doc['cv']) ?>" target="_blank">View</a>

                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="nic_passport">NIC/Passport:</label>

                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="nic_passport" name="nic_passport">
                                                        <a href="<?= htmlspecialchars($row_doc['nic_passport']) ?>" target="_blank">View</a>

                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="education_qualification_1">Education Qualification 1:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="education_qualification_1" name="education_qualification_1">
                                                        <a href="<?= htmlspecialchars($row_doc['education_qualification_1']) ?>" target="_blank">View</a>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="education_qualification_2">Education Qualification 2:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="education_qualification_2" name="education_qualification_2">
                                                        <?php if (!empty($row_doc['education_qualification_2'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_2']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_2']) ?>" target="_blank" style="display:none;">View</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="education_qualification_3">Education Qualification 3:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="education_qualification_3" name="education_qualification_3">
                                                        <?php if (!empty($row_doc['education_qualification_3'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_3']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_3']) ?>" target="_blank">View</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="education_qualification_4">Education Qualification 4:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="education_qualification_4" name="education_qualification_4">
                                                        <?php if (!empty($row_doc['education_qualification_4'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_4']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['education_qualification_4']) ?>" target="_blank" style="display:none;">View</a>
                                                        <?php endif; ?>


                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 2nd column  -->
                                        <div class="col-md-6">
                                            <div class="row">
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="experience_1">Experience 1:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="experience_1" name="experience_1">
                                                        <a href="<?= htmlspecialchars($row_doc['experience_1']) ?>" target="_blank">View</a>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="experience_2">Experience 2:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="experience_2" name="experience_2">
                                                        <?php if (!empty($row_doc['experience_2'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_2']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_2']) ?>" target="_blank" style="display:none;">View</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->

                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="experience_3">Experience 3:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="experience_3" name="experience_3">
                                                        <?php if (!empty($row_doc['experience_3'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_3']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_3']) ?>" target="_blank" style="display:none;">View</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="experience_4">Experience 4:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="experience_4" name="experience_4">
                                                        <?php if (!empty($row_doc['education_qualification_4'])): ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_4']) ?>" target="_blank">View</a>
                                                        <?php else: ?>
                                                            <a href="<?= htmlspecialchars($row_doc['experience_4']) ?>" target="_blank" style="display:none;">View</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="photo">Photo:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="file" class="form-control-file" id="photo" name="photo">
                                                        <img src="<?= htmlspecialchars($row_doc['photo']) ?>" width="50" class="mt-2">
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <label for="other">Other:</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">

                                                        <input type="file" class="form-control-file" id="other" name="other">
                                                        <a href="<?= htmlspecialchars($row_doc['other']) ?>" target="_blank">View</a>
                                                    </div>
                                                </div>
                                                <!-- -------------------- -->
                                                <div class="col-md-4">
                                                    <!-- <label for="entered_by">Entered By:</label> -->
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <input type="hidden" class="form-control" id="entered_by" name="entered_by" value="<?= htmlspecialchars($row_doc['entered_by']) ?>" required>

                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <!-- <label for="entered_by">Entered By:</label> -->
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <button type="submit" class="btn btn-primary">Update</button>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
</div>
</body>

</html>