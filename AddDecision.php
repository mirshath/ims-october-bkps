<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------

?>

<!-- TinyMCE Script -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#description',
    menubar: false,
    toolbar: 'undo redo | styleselect | bold italic underline | bullist numlist | alignleft aligncenter alignright | link image',
    height: 300
  });
</script>

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
                    <h4 class="h4 mb-0 text-gray-800">Add Decision</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-10 offset-md-1">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Decision Form</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="process_decision.php">
                                    <!-- Programme -->
                                    <div class="mb-3 row">
                                        <label for="programme" class="col-sm-2 col-form-label">Programme:</label>
                                        <div class="col-sm-10">
                                            <select class="form-select" id="programme" name="programme">
                                                <option selected disabled>-- Select Programme --</option>
                                                <option>Programme A</option>
                                                <option>Programme B</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Student Uni ID -->
                                    <div class="mb-3 row">
                                        <label for="student_id" class="col-sm-2 col-form-label">Student's Uni ID:</label>
                                        <div class="col-sm-10">
                                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                                        </div>
                                    </div>

                                    <!-- Decision -->
                                    <div class="mb-3 row">
                                        <label for="decision" class="col-sm-2 col-form-label">Decision:</label>
                                        <div class="col-sm-10">
                                            <select class="form-select" id="decision" name="decision">
                                                <option selected disabled>-- Select Decision --</option>
                                                <option>Approved</option>
                                                <option>Rejected</option>
                                                <option>Deferred</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="mb-3 row">
                                        <label for="description" class="col-sm-2 col-form-label">Description:</label>
                                        <div class="col-sm-10">
                                            <textarea id="description" name="description"></textarea>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success">Send Mail</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Content -->
        </div>
        <!-- End Main Content -->
    </div>
    <!-- End Content Wrapper -->
</div>
<!-- End Page Wrapper -->

<!-- JS and CSS includes (same as struc.php) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

</body>
</html>
