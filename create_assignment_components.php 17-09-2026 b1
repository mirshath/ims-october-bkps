<?php
session_start();
// Include database connection
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------


// Handle form submission for adding main component
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Retrieve form inputs
    $assessment_code = mysqli_real_escape_string($conn, $_POST['assessment_code']);
    $as_main_component_name = mysqli_real_escape_string($conn, $_POST['as_main_component_name']);
    $as_main_component_name_percent = mysqli_real_escape_string($conn, $_POST['as_main_component_name_percent']);

    // Validation
    if (!empty($assessment_code) && !empty($as_main_component_name)) {
        // Insert into database
        $sql = "INSERT INTO assignment_components (assessment_code, as_main_component_name, main_component_percent) 
                VALUES ('$assessment_code', '$as_main_component_name', '$as_main_component_name_percent')";

         if (mysqli_query($conn, $sql)) {
            // echo "<script>alert('Component added successfully!');</script>";
            // show the session message corectly 
            $_SESSION['message'] = "Main Component added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Please fill all fields!');</script>";
    }
}

// Handle form submission for adding sub-component
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sub_component'])) {
    // Retrieve form input
    $sub_component_name = mysqli_real_escape_string($conn, $_POST['sub_component_name']);
    $sub_component_percent = mysqli_real_escape_string($conn, $_POST['sub_component_percent']);

    // Validation
    if (!empty($sub_component_name)) {
        // Insert into database
        $sql = "INSERT INTO sub_assign_components (sub_component_name,sub_component_percent) VALUES ('$sub_component_name','$sub_component_percent')";

       
        if (mysqli_query($conn, $sql)) {
            // echo "<script>alert('Sub-component added successfully!');</script>";
            // show the session message corectly
            $_SESSION['message'] = "Sub-component added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Please fill all fields!');</script>";
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_nested_component'])) {
    // Retrieve form input
    $nested_component_name = mysqli_real_escape_string($conn, $_POST['nested_component_name']);
    $nested_component_percent = mysqli_real_escape_string($conn, $_POST['nested_component_percent']);

    // Validation
    if (!empty($nested_component_name) && !empty($nested_component_percent)) {
        // Insert into the nested_assign_components table
        $sql = "INSERT INTO nested_assign_components (nested_components_name, nested_components_percent) 
                VALUES ('$nested_component_name', '$nested_component_percent')";

        if (mysqli_query($conn, $sql)) {
            // echo "<script>alert('Nested component added successfully!');</script>";
            // show the session message corectly
            $_SESSION['message'] = "Nested component added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('Please fill all fields!');</script>";
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

            <div class="p-3">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Assignment Components Management</h4>
                </div>

                <!-- Form and Left Side Content -->
                <div class="row mb-5">

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0">Add Main Component</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="form-group mb-3">
                                        <label for="assessment_code">Assessment Code</label>
                                        <input type="text" name="assessment_code" id="assessment_code" class="form-control" placeholder="Enter Assessment Code" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="as_main_component_name">Main Component Name</label>
                                        <input type="text" name="as_main_component_name" id="as_main_component_name" class="form-control" placeholder="Enter Component Name" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="as_main_component_name_percent">Main Component Percent(%)</label>
                                        <input type="text" name="as_main_component_name_percent" id="as_main_component_name_percent" class="form-control" placeholder="Enter Component %">
                                    </div>
                                    <button type="submit" name="submit" class="btn btn-primary">Add Component</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0">Add Sub-Component</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="post">
                                    <div class="form-group mb-3">
                                        <label for="sub_component_name">Sub-Component Name</label>
                                        <input type="text" name="sub_component_name" id="sub_component_name" class="form-control" placeholder="Enter Sub-Component Name" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="sub_component_percent">Sub-Component Percent</label>
                                        <input type="text" name="sub_component_percent" id="sub_component_percent" class="form-control" placeholder="Enter Sub-Component %">
                                    </div>
                                    <button type="submit" name="add_sub_component" class="btn btn-primary">Add Sub-Component</button>
                                </form>
                            </div>
                        </div>
                    </div>



                    <div class="col-md-6"></div>


                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0">Add Nested Component</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="form-group mb-3">
                                        <label for="nested_component_name">Nested Component Name</label>
                                        <input type="text" name="nested_component_name" id="nested_component_name" class="form-control" placeholder="Enter Sub-Component Name" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="nested_component_percent">Sub-Component Percent</label>
                                        <input type="number" name="nested_component_percent" id="nested_component_percent" class="form-control" placeholder="Enter Sub-Component %" step="0.01" min="0" max="100" required>
                                    </div>
                                    <button type="submit" name="add_nested_component" class="btn btn-primary">Add Sub-Component</button>
                                </form>
                            </div>
                        </div>
                    </div>


                </div>


                <!-- Current Components Section -->
                <div class="container-fluid" style="font-size: 13px;">
                    <div class="card shadow mb-4">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-list"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0">Current Assignment Components</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="mainComponentTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th style="width: 5px;">#</th>
                                            <th>ID</th>
                                            <th>Assessment Code</th>
                                            <th>Main Component Name</th>
                                            <th>Main Component Percentage</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $index = 1; // Initialize index counter
                                        $result = mysqli_query($conn, "SELECT * FROM assignment_components");
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                            <td>" . $index++ . "</td>
                                                <td>{$row['id']}</td>
                                                <td>{$row['assessment_code']}</td>
                                                <td>{$row['as_main_component_name']}</td>
                                                <td>{$row['main_component_percent']}</td>
                                                <td>
                                                    <button class='btn btn-sm btn-warning editMainComponent' data-id='{$row['id']}' data-assessment_code='{$row['assessment_code']}' data-name='{$row['as_main_component_name']}' data-bs-toggle='modal' data-bs-target='#editMainComponentModal'>Edit</button>
                                                </td>
                                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Sub-Components Section -->
                <div class="container-fluid" style="font-size: 13px;">
                    <div class="card shadow mb-4">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-list"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0">Current Sub-Components</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="subComponentTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th style="width: 5px;">#</th>
                                            <th>ID</th>
                                            <th>Sub-Component Name</th>
                                            <th>Sub-Component Percent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $index = 1; // Initialize index counter
                                        $result = mysqli_query($conn, "SELECT * FROM sub_assign_components");
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                              <td>" . $index++ . "</td>
                                                <td>{$row['id']}</td>
                                                <td>{$row['sub_component_name']}</td>
                                                <td>{$row['sub_component_percent']}</td>
                                                <td>
                                                    <button class='btn btn-sm btn-warning editSubComponent' data-id='{$row['id']}' data-name='{$row['sub_component_name']}' data-bs-toggle='modal' data-bs-target='#editSubComponentModal'>Edit</button>
                                                </td>
                                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Current Nested Components Section -->
                <div class="container-fluid" style="font-size: 13px;">
                    <div class="card shadow mb-4">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-list"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0">Current Nested Components</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="nestedComponentTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th style="width: 5px;">#</th>
                                            <th>ID</th>
                                            <th>Nested Component Name</th>
                                            <th>Nested Component Percent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $index = 1; // Initialize index counter
                                        $result = mysqli_query($conn, "SELECT * FROM nested_assign_components");
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>
                                <td>" . $index++ . "</td>
                                <td>{$row['id']}</td>
                                <td>{$row['nested_components_name']}</td>
                                <td>{$row['nested_components_percent']}</td>
                                <td>
                                    <button class='btn btn-sm btn-warning editNestedComponent' data-id='{$row['id']}' data-name='{$row['nested_components_name']}' data-bs-toggle='modal' data-bs-target='#editNestedComponentModal'>Edit</button>
                                </td>
                            </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Main Component -->
<div class="modal fade" id="editMainComponentModal" tabindex="-1" aria-labelledby="editMainComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMainComponentModalLabel">Edit Main Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editMainComponentForm">
                    <input type="hidden" id="mainComponentId">
                    <div class="form-group">
                        <label for="editAssessmentCode">Assessment Code</label>
                        <input type="text" id="editAssessmentCode" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="editMainComponentName">Main Component Name</label>
                        <input type="text" id="editMainComponentName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Sub-Component -->
<div class="modal fade" id="editSubComponentModal" tabindex="-1" aria-labelledby="editSubComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubComponentModalLabel">Edit Sub-Component</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editSubComponentForm">
                    <input type="hidden" id="subComponentId">
                    <div class="form-group">
                        <label for="editSubComponentName">Sub-Component Name</label>
                        <input type="text" id="editSubComponentName" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">

<script>
    // Initialize DataTables
    $(document).ready(function() {
        $('#mainComponentTable').DataTable();
        $('#subComponentTable').DataTable();
        $('#nestedComponentTable').DataTable();
    });

    // Edit Main Component Modal
    $(document).on('click', '.editMainComponent', function() {
        $('#mainComponentId').val($(this).data('id'));
        $('#editAssessmentCode').val($(this).data('assessment_code'));
        $('#editMainComponentName').val($(this).data('name'));
    });

    // Edit Sub-Component Modal
    $(document).on('click', '.editSubComponent', function() {
        $('#subComponentId').val($(this).data('id'));
        $('#editSubComponentName').val($(this).data('name'));
    });

    // Update Main Component
    $('#editMainComponentForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#mainComponentId').val();
        var assessmentCode = $('#editAssessmentCode').val();
        var mainComponentName = $('#editMainComponentName').val();

        $.ajax({
            url: 'Assignment_components/update_assignment_component.php',
            type: 'POST',
            data: {
                id: id,
                assessment_code: assessmentCode,
                as_main_component_name: mainComponentName
            },
            success: function(response) {
                alert(response);
                var modal = bootstrap.Modal.getInstance(document.getElementById('editMainComponentModal'));
                modal.hide();
                location.reload();
            }
        });
    });

    // Update Sub-Component
    $('#editSubComponentForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#subComponentId').val();
        var subComponentName = $('#editSubComponentName').val();

        $.ajax({
            url: 'Assignment_components/update_sub_component.php',
            type: 'POST',
            data: {
                id: id,
                sub_component_name: subComponentName
            },
            success: function(response) {
                alert(response);
                var modal = bootstrap.Modal.getInstance(document.getElementById('editSubComponentModal'));
                modal.hide();
                location.reload();
            }
        });
    });
</script>