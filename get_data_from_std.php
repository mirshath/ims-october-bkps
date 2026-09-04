<?php
session_start();
include("database/connection.php");
include("includes/header.php");

// Initialize variables to prevent undefined variable errors
$isEdit = false;
$assessment = [];

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 

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
                    <h4 class="h4 mb-0 text-gray-800">Get Data From Online Registration </h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Student Update</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="programme">Programme</label>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group mb-3">
                                            <select name="programme_id" id="programme" style="font-size: 12px;"
                                                class="form-control select2" required>
                                                <option value="">Select Programme</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="batch">Batch</label>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group mb-3">
                                            <select name="batch_id" id="batch" style="font-size: 12px;"
                                                class="form-control select2" required>
                                                <option value="">Select Batch</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12 text-right">
                                        <button id="showDataBtn" class="btn btn-primary btn-sm">Show</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Container -->
                <div id="studentResults" class="row">
                    <!-- Data will be loaded here -->
                </div>

            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
<style>
    /* DataTables Override: ensure search box is visible and styling is consistent */
    .dataTables_filter {
        float: right;
        margin-bottom: 10px;
    }

    .dataTables_length {
        float: left;
        margin-bottom: 10px;
    }

    .dt-buttons {
        margin-bottom: 10px;
    }
</style>

<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize Select2
        $('.select2').select2();

        // Fetch Programmes
        $.ajax({
            url: "transection_exams/fetch_programmes.php",
            method: "GET",
            dataType: "json",
            success: function (data) {
                let programmeDropdown = $('#programme');
                programmeDropdown.empty().append('<option value="">Select Programme</option>');
                data.forEach(function (programme) {
                    let selected = <?php echo $isEdit ? 'programme.program_code == ' . $assessment['programme_id'] : 'false'; ?> ? 'selected' : '';
                    programmeDropdown.append(`<option value="${programme.program_code}" ${selected}>${programme.program_name}</option>`);
                });
                <?php if ($isEdit): ?>
                    programmeDropdown.trigger('change');
                <?php endif; ?>
            }
        });

        // Programme change event
        $('#programme').change(function () {
            let programmeId = $(this).val();
            $.ajax({
                url: "transection_exams/fetch_batches.php",
                method: "POST",
                data: {
                    programme_id: programmeId
                },
                dataType: "json",
                success: function (data) {
                    let batchDropdown = $('#batch');
                    batchDropdown.empty().append('<option value="">Select Batch</option>');
                    data.forEach(function (batch) {
                        let selected = <?php echo $isEdit ? 'batch.id == ' . $assessment['batch_id'] : 'false'; ?> ? 'selected' : '';
                        batchDropdown.append(`<option value="${batch.id}" ${selected}>${batch.batch_name}</option>`);
                    });
                    <?php if ($isEdit): ?>
                        batchDropdown.trigger('change');
                    <?php endif; ?>
                }
            });
        });

        // Show Data Button Click
        $('#showDataBtn').click(function () {
            let programmeId = $('#programme').val();
            let batchId = $('#batch').val();

            if (programmeId && batchId) {
                // Show loading state
                $('#studentResults').html('<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');

                $.ajax({
                    url: "transection_exams/fetch_temp_students.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function (data) {
                        let html = '<div class="col-md-12"><div class="card shadow"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Temporary Student Registration Data</h6></div><div class="card-body"><div class="table-responsive"><table id="tempStudentTable" class="table table-borderless table-striped table-hover" width="100%" cellspacing="0">';
                        html += '<thead class="thead-light"><tr><th>#</th><th>Student Name</th><th>NIC</th><th>Mobile Number</th><th>Status</th><th>Action</th></tr></thead><tbody>';

                        if (data.length > 0) {
                            data.forEach(function (student, index) {
                                let statusHtml = '';
                                if (student.approved == '1') {
                                    statusHtml = `<span class="badge badge-success">Approved</span> <br> <small>${student.approved_by || ''}</small>`;
                                } else {
                                    statusHtml = `<span class="badge badge-danger">Pending</span>`;
                                }

                                html += `<tr>
                                    <td>${index + 1}</td>
                                    <td>${student.fullname}</td>
                                    <td>${student.nic}</td>
                                    <td>${student.mobile}</td>
                                    <td>${statusHtml}</td>
                                    <td class="text-center">
                                        <a target="_blank" href="temp_student_all_data.php?id=${student.id}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>`;
                            });
                        } else {
                            html += '<tr><td colspan="6" class="text-center">No students found for this batch</td></tr>';
                        }

                        html += '</tbody></table></div></div></div></div>';
                        $('#studentResults').html(html);

                        // Initialize DataTables
                        $('#tempStudentTable').DataTable({
                            "pageLength": 100,
                            dom: 'Bfrtip',
                            buttons: [
                                'copy', 'csv', 'excel', 'pdf', 'print'
                            ]
                        });
                    },
                    error: function (xhr, status, error) {
                        $('#studentResults').html('<div class="col-12 text-danger">Error fetching data: ' + error + '</div>');
                    }
                });
            } else {
                alert("Please select both Programme and Batch");
            }
        });
    });
</script>

</body>

</html>