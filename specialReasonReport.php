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


// Fetch active students from allocated_programme
$studentQuery = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id 
                FROM students s
                JOIN allocate_programme ap ON s.student_code = ap.student_code
                WHERE ap.status = 'active'
                ORDER BY s.first_name, s.last_name";
$studentResult = mysqli_query($conn, $studentQuery);

// Fetch courses from program_table
$courseQuery = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
$courseResult = mysqli_query($conn, $courseQuery);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reportType = $_POST['reportType'];
    $studentId = isset($_POST['student']) ? $_POST['student'] : '';
    $courseId = isset($_POST['course']) ? $_POST['course'] : '';
    $fromDate = isset($_POST['fromDate']) ? $_POST['fromDate'] : '';
    
    // Build query based on report type
    $query = "SELECT sr.id, s.student_code, s.first_name, s.last_name, p.program_name, 
              sr.reason, sr.from_date, sr.to_date, sr.remark, sr.entered_date, sr.entered_by
              FROM special_reason sr
              JOIN students s ON sr.student_id = s.student_code
              JOIN program_table p ON sr.program_id = p.program_code
              WHERE 1=1";
    
    if ($reportType == 'student' && !empty($studentId)) {
        $query .= " AND s.student_code = '$studentId'";
    } else if ($reportType == 'course' && !empty($courseId)) {
        $query .= " AND p.program_code = '$courseId'";
    }
    
    if (!empty($fromDate)) {
        $query .= " AND sr.entered_date >= '$fromDate'";
    }
    
    $query .= " ORDER BY sr.entered_date DESC";
    $reasonsResult = mysqli_query($conn, $query);
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
                    <h4 class="h4 mb-0 text-gray-800">Student Special Reasons Report</h4>
                    <?php if (isset($reasonsResult) && mysqli_num_rows($reasonsResult) > 0): ?>
                    <div>
                        <button id="exportBtn" class="btn btn-info me-2">
                            <i class="fas fa-file-export me-1"></i> Export
                        </button>
                        <button id="printBtn" class="btn btn-info">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-filter"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Report Filter</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="reportForm">
                                    <div class="mb-3">
                                        <label class="form-label">Report Type:</label>
                                        <div class="form-check form-check-inline ms-3">
                                            <input class="form-check-input" type="radio" name="reportType" id="reportAll" value="all" checked>
                                            <label class="form-check-label" for="reportAll">All</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="reportType" id="reportStudent" value="student">
                                            <label class="form-check-label" for="reportStudent">Student</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="reportType" id="reportCourse" value="course">
                                            <label class="form-check-label" for="reportCourse">Course</label>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="student" class="form-label">Select Student:</label>
                                            <select class="form-select" id="student" name="student" disabled>
                                                <option value="">Select Student</option>
                                                <?php 
                                                mysqli_data_seek($studentResult, 0);
                                                while ($row = mysqli_fetch_assoc($studentResult)) { 
                                                ?>
                                                    <option value="<?php echo $row['student_code']; ?>">
                                                        <?php echo $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['student_registration_id'] . ')'; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="course" class="form-label">Select Course:</label>
                                            <select class="form-select" id="course" name="course" disabled>
                                                <option value="">Select Course</option>
                                                <?php 
                                                mysqli_data_seek($courseResult, 0);
                                                while ($row = mysqli_fetch_assoc($courseResult)) { 
                                                ?>
                                                    <option value="<?php echo $row['program_code']; ?>"><?php echo $row['program_name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3 text-end">
                                        <button type="submit" class="btn btn-success">Submit</button>
                                        <button type="reset" class="btn btn-secondary" id="resetBtn">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($reasonsResult) && mysqli_num_rows($reasonsResult) > 0): ?>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-table"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Special Reasons Report</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" id="reportContent">
                                    <table id="reasonsTable" class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Course</th>
                                                <th>ID</th>
                                                <th>Reason</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Remark</th>
                                                <th>Entered Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($reason = mysqli_fetch_assoc($reasonsResult)): ?>
                                            <tr>
                                                <td><?php echo $reason['student_code']; ?></td>
                                                <td><?php echo $reason['first_name'] . ' ' . $reason['last_name']; ?></td>
                                                <td><?php echo $reason['program_name']; ?></td>
                                                <td><?php echo $reason['id']; ?></td>
                                                <td><?php echo $reason['reason']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($reason['from_date'])); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($reason['to_date'])); ?></td>
                                                <td><?php echo $reason['remark']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($reason['entered_date'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif (isset($reasonsResult)): ?>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            No special reasons found matching the selected criteria.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="text-center text-muted small mt-4">
                    2021 © SunTec Information Systems
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet" />

    <script>
    $(document).ready(function() {
        // Initialize Select2 for enhanced dropdowns
        $('#student, #course').select2({
            width: '100%',
            placeholder: "Select an option"
        });

        // Initialize DataTables for the results table with export buttons
        if ($('#reasonsTable').length > 0) {
            var table = $('#reasonsTable').DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf'
                ]
            });
            
            // Connect export button to DataTables export
            $('#exportBtn').on('click', function() {
                $('.buttons-excel').click();
            });
        }

        // Function to update form state based on selection
        function updateFormState() {
            if ($('#reportAll').is(':checked')) {
                $('#student').prop('disabled', true).trigger('change');
                $('#course').prop('disabled', true).trigger('change');
            } else if ($('#reportStudent').is(':checked')) {
                $('#student').prop('disabled', false).trigger('change');
                $('#course').prop('disabled', true).trigger('change');
            } else if ($('#reportCourse').is(':checked')) {
                $('#student').prop('disabled', true).trigger('change');
                $('#course').prop('disabled', false).trigger('change');
            }
        }

        // Add event listeners to radio buttons
        $('input[name="reportType"]').on('change', updateFormState);

        // Reset button handler
        $('#resetBtn').on('click', function() {
            $('#reportAll').prop('checked', true);
            updateFormState();
        });

        // Date filter handler
        $('#fromDate').on('change', function() {
            // Add the date to the form when submitting
            var fromDate = $(this).val();
            if (fromDate) {
                // If there's already a hidden input, update it, otherwise create it
                if ($('input[name="fromDate"]').length) {
                    $('input[name="fromDate"]').val(fromDate);
                } else {
                    $('#reportForm').append('<input type="hidden" name="fromDate" value="' + fromDate + '">');
                }
            }
        });

        // Print functionality
        $('#printBtn').on('click', function() {
            var printContents = document.getElementById('reportContent').innerHTML;
            var originalContents = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h1 style="text-align: center; margin-bottom: 20px;">Student Special Reasons Report</h1>
                    ${printContents}
                </div>
            `;
            
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        });

        // Initialize form state
        updateFormState();
    });
    </script>
</div>
</body>
</html>
