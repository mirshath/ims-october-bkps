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
                    <h4 class="h4 mb-0 text-gray-800">Special Reasons</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-8 offset-md-2">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Special Reasons Form</h6>
                            </div>
                            <div class="card-body">
                                <form>
                                    <!-- Student -->
                                    <div class="mb-3 row">
                                        <label for="student" class="col-sm-3 col-form-label">Student:</label>
                                        <div class="col-sm-9">
                                            <select class="form-select" id="student">
                                                <option selected disabled>-- Select Student --</option>
                                                <option>Student 1</option>
                                                <option>Student 2</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Course -->
                                    <div class="mb-3 row">
                                        <label for="course" class="col-sm-3 col-form-label">Course:</label>
                                        <div class="col-sm-9">
                                            <select class="form-select" id="course">
                                                <option selected disabled>-- Select Course --</option>
                                                <option>Course A</option>
                                                <option>Course B</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Reasons -->
                                    <fieldset class="mb-3 row">
                                        <legend class="col-form-label col-sm-3 pt-0">Reason:</legend>
                                        <div class="col-sm-9">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="reason" id="ec" value="EC">
                                                <label class="form-check-label" for="ec">EC</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="reason" id="ios" value="IOS">
                                                <label class="form-check-label" for="ios">IOS</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="reason" id="withdrawals" value="Withdrawals">
                                                <label class="form-check-label" for="withdrawals">Withdrawals</label>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <!-- Date From -->
                                    <div class="mb-3 row">
                                        <label for="dateFrom" class="col-sm-3 col-form-label">From:</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="dateFrom">
                                        </div>
                                    </div>

                                    <!-- Date To -->
                                    <div class="mb-3 row">
                                        <label for="dateTo" class="col-sm-3 col-form-label">To:</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="dateTo">
                                        </div>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="mb-3 row">
                                        <label for="remarks" class="col-sm-3 col-form-label">Remarks:</label>
                                        <div class="col-sm-9">
                                            <textarea class="form-control" id="remarks" rows="4"></textarea>
                                        </div>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-success">Save</button>
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
