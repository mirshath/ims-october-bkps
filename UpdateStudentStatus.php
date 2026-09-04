<?php
session_start();
include("database/connection.php");
include("includes/header.php");

// Pull user session info
$Session_username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// Permissions checking
require_once 'PermissionChecking.php';
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
                    <h4 class="h4 mb-0 text-gray-800">Update Student Status</h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Student Update</h6>
                            </div>
                            <div class="card-body">
                                <form id="filterForm" method="post" class="mb-3">
                                    <div class="form-group">
                                        <label for="program">Programme:</label>
                                        <select id="program" name="program" class="form-control select2">
                                            <option value="">Select Program</option>
                                            <?php
                                            if ($role === 'super_admin') {
                                                $query = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
                                                $result = mysqli_query($conn, $query);
                                            } else {
                                                $query = "
                                                    SELECT pt.program_code, pt.program_name
                                                    FROM program_allocation_user AS pau
                                                    INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
                                                    WHERE pau.user_id = $user_id
                                                    ORDER BY pt.program_name
                                                ";
                                                $result = mysqli_query($conn, $query);
                                            }
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<option value='{$row['program_code']}'>{$row['program_name']}</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batch">Batch:</label>
                                        <select id="batch" name="batch" class="form-control select2">
                                            <option value="">Select Batch</option>
                                        </select>
                                    </div>

                                    <div class="text-right">
                                        <button type="button" id="submitBtn" class="btn btn-primary">Load Students</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Details of Student Status:</h6>
                            </div>
                            <div class="card-body">
                                <!-- Students Table -->
                                <div class="table-responsive mb-4">
                                    <table id="studentsTable" class="table table-striped table-bordered" style="width: 100%; font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>Program</th>
                                                <th style="width: 50px;">Batch</th>
                                                <th style="width: 10px;">Student Code</th>
                                                <th style="width: 80px;">Reg No</th>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Remark</th>
                                                <th style="width: 50px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data dynamically loaded -->
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // Load batches (only the actual batches for the correct program!) on program select
            $('#program').change(function() {
                var selectedProgramId = $.trim($(this).val());
                if (selectedProgramId) {
                    $.ajax({
                        type: 'POST',
                        url: 'updateStudentStatus/fetch_batches.php',
                        data: { program_id: selectedProgramId },
                        success: function(response) {
                            $('#batch').html(response);
                        },
                        error: function() {
                            alert('Failed to load batches. Please try again.');
                        }
                    });
                } else {
                    $('#batch').html('<option value="">Select Batch</option>');
                }
            });

            // On "Load Students", load the student list but do NOT trust just UI ids
            // Send the selected IDs, and fetch FULL data (incl. correct program/batch) from server
            $('#submitBtn').click(function() {
                var selectedProgramCode = $('#program').val();
                var selectedBatchId = $('#batch').val();

                if (selectedProgramCode && selectedBatchId) {
                    $.ajax({
                        type: 'POST',
                        url: 'updateStudentStatus/fetch_students.php',
                        data: {
                            program_id: selectedProgramCode,
                            batch_id: selectedBatchId
                        },
                        success: function(response) {
                            var studentsData = JSON.parse(response);
                            var table = $('#studentsTable').DataTable();
                            table.clear();

                            studentsData.forEach(function(student) {
                                var statusOptions = getStatusOptions(student.status);
                                // Now, store dataset with the real program/batch id as data attributes for later use
                                table.row.add([
                                    "<span class='program-name' data-program-code='"+student.program_code+"'>"+student.program_name+"</span>",
                                    "<span class='batch-name' data-batch-id='"+student.batch_id+"'>"+student.batch_name+"</span>",
                                    student.student_code,
                                    student.student_registration_id,
                                    student.first_name + " " + student.last_name,
                                    "<select name='student_status' class='form-control'>" + statusOptions + "</select>",
                                    "<input type='text' name='additional_info[]' class='form-control' placeholder='Additional Info' value='"+student.dm_remark+"' />",
                                    "<button type='button' class='btn btn-sm btn-primary updateBtn' " +
                                        "data-registration-id='"+student.student_registration_id+"' " +
                                        "data-student-code='"+student.student_code+"' " +
                                        "data-program-code='"+student.program_code+"' " +
                                        "data-batch-id='"+student.batch_id+"' "+
                                    ">Update</button>"
                                ]).draw();
                            });
                        },
                        error: function() {
                            alert('Failed to load students data. Please try again.');
                        }
                    });
                } else {
                    alert('Please select both Program and Batch.');
                }
            });

            function getStatusOptions(status) {
                var statusOptions = "";
                // var statuses = ['active', 'drop', 'transferred', 'completed', 'inactive'];
                  var statuses = ['active', 'drop', 'transferred', 'completed', 'inactive', 'batchSwap','ReRegister'];
                statuses.forEach(function(s) {
                    statusOptions += "<option value='"+s+"' "+(status === s ? 'selected':'')+">"+capitalizeFirstLetter(s)+"</option>";
                });
                return statusOptions;
            }
            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase()+string.slice(1);
            }

            // On clicking "Update", use the stored (data-*) attributes for CORRECT program/batch ids
            $('#studentsTable').on('click', '.updateBtn', function() {
                var btn = $(this);
                var row = btn.closest('tr');

                var studentCode = btn.data('student-code');
                var studentStatus = row.find('select[name="student_status"]').val();
                var additionalInfo = row.find('input[name="additional_info[]"]').val();
                var studentRegistrationId = btn.data('registration-id');
                // Use always the data-program-code and data-batch-id that came from DB, never just UI text!
                var programCode = btn.data('program-code');
                var batchId = btn.data('batch-id');
                var programName = row.find('.program-name').text();
                var batchName = row.find('.batch-name').text();

                // ---- Add console logging of these values ----
                console.log("Student Code:", studentCode);
                console.log("Program Code:", programCode);
                console.log("Batch ID:", batchId);

                if (!programCode || !batchId) {
                    alert("Program or Batch ID missing, cannot update.");
                    return;
                }

                // If status=completed, check dues using correct ids
                if (studentStatus === 'completed') {
                    $.ajax({
                        type: 'POST',
                        url: 'updateStudentStatus/check_student_dues.php',
                        data: {
                            student_code: studentCode,
                            program_code: programCode,
                            batch_id: batchId
                        },
                        success: function(resp) {
                            var data;
                            try {
                                data = JSON.parse(resp);
                            } catch (e) {
                                alert('Invalid response received from server.');
                                return;
                            }
                            if (data.error) {
                                alert(data.message || "Error while checking dues.");
                                return;
                            }
                            if (data.has_dues) {
                                alert('Cannot mark as Completed: student has outstanding payments!');
                                return;
                            } else {
                                updateStudentStatus(studentCode, studentRegistrationId, studentStatus, additionalInfo, programCode, batchId);
                            }
                        },
                        error: function() {
                            alert('Failed to check payments. Please try again.');
                        }
                    });
                } else {
                    updateStudentStatus(studentCode, studentRegistrationId, studentStatus, additionalInfo, programCode, batchId);
                }
            });

            // Actual update call, using the CORRECT program_code and batch_id from DB only.
            function updateStudentStatus(studentCode, studentRegistrationId, studentStatus, additionalInfo, programCode, batchId) {
                $.ajax({
                    type: 'POST',
                    url: 'updateStudentStatus/update_student_status.php',
                    data: {
                        student_code: studentCode,
                        student_status: studentStatus,
                        additional_info: additionalInfo,
                        student_registration_id_pass: studentRegistrationId,
                        program_code: programCode,
                        batch_id: batchId
                    },
                    success: function(response) {
                        alert(response);
                    },
                    error: function() {
                        alert('Failed to update student status. Please try again.');
                    }
                });
            }

        });
    </script>
</div>
</body>
</html>