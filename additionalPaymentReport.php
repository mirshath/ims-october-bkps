<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------

// Function to get active students from allocate_programme
function getActiveStudents($conn) {
    $query = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, 
              p.program_name, b.batch_name 
              FROM students s
              JOIN allocate_programme ap ON s.student_code = ap.student_code
              JOIN program_table p ON ap.programme_code = p.program_code
              JOIN batch_table b ON ap.batch_id = b.id
              WHERE ap.status = 'active'
              ORDER BY s.first_name, s.last_name";
    
    $result = mysqli_query($conn, $query);
    $students = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
    }
    
    return $students;
}

// Function to get all programs
function getPrograms($conn) {
    $query = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
    
    $result = mysqli_query($conn, $query);
    $programs = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $programs[] = $row;
        }
    }
    
    return $programs;
}

// Get data for dropdowns
$students = getActiveStudents($conn);
$programs = getPrograms($conn);

// Initialize variables for report results
$reportData = [];
$reportTitle = "";
$showReport = false;
$noResults = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['reportType'];
    $showReport = true;
    
    // Build query based on report type
    $baseQuery = "SELECT afp.id, afp.student_id, afp.student_registration_id, afp.programme_code, 
                 afp.batch_id, afp.program_name, afp.batch_name, afp.total_amount, 
                 afp.paid_date, afp.payment_method, afp.payment_reference, 
                 afp.remarks, afp.status, afp.entered_by, afp.created_at, 
                 s.first_name, s.last_name,
                 GROUP_CONCAT(afi.description SEPARATOR ', ') as fee_items
                 FROM additional_fee_payments afp
                 JOIN students s ON afp.student_id = s.student_code
                 LEFT JOIN additional_fee_items afi ON afp.id = afi.payment_id";
    
    $whereClause = "";
    $params = [];
    $paramTypes = "";
    
    switch ($reportType) {
        case 'allAsAt':
            $asAtDate = $_POST['asAtDate'];
            $whereClause = " WHERE afp.paid_date <= ?";
            $params[] = $asAtDate;
            $paramTypes .= "s";
            $reportTitle = "Additional Payments as at " . date('d/m/Y', strtotime($asAtDate));
            break;
            
        case 'allBetween':
            $fromDate = $_POST['fromDate'];
            $toDate = $_POST['toDate'];
            $whereClause = " WHERE afp.paid_date BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $paramTypes .= "ss";
            $reportTitle = "Additional Payments between " . date('d/m/Y', strtotime($fromDate)) . " and " . date('d/m/Y', strtotime($toDate));
            break;
            
        case 'student':
            $studentId = $_POST['studentId'];
            $whereClause = " WHERE afp.student_id = ?";
            $params[] = $studentId;
            $paramTypes .= "s";
            
            // Get student name for report title
            $studentName = "";
            foreach ($students as $student) {
                if ($student['student_code'] == $studentId) {
                    $studentName = $student['first_name'] . ' ' . $student['last_name'];
                    break;
                }
            }
            $reportTitle = "Additional Payments for " . $studentName;
            break;
            
        case 'programme':
            $programId = $_POST['programId'];
            $whereClause = " WHERE afp.programme_code = ?";
            $params[] = $programId;
            $paramTypes .= "s";
            
            // Get program name for report title
            $programName = "";
            foreach ($programs as $program) {
                if ($program['program_code'] == $programId) {
                    $programName = $program['program_name'];
                    break;
                }
            }
            $reportTitle = "Additional Payments for " . $programName;
            break;
    }
    
    // Complete the query
    $query = $baseQuery . $whereClause . " GROUP BY afp.id ORDER BY afp.paid_date DESC";
    
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $reportData[] = $row;
            }
        } else {
            $noResults = true;
        }
    }
}
?>

<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .report-container {
        margin-top: 20px;
    }
    .report-header {
        margin-bottom: 20px;
    }
    .report-table th {
        background-color: #f8f9fa;
    }
    .form-check-input[type="radio"] {
        margin-top: 0.3rem;
    }
    .form-label {
        font-weight: 500;
    }
    .truncate-text {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
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
            <div class="container-fluid py-4">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Additional Payment Report</h1>
                </div>

                <!-- Report Form Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Report Parameters</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" id="reportForm">
                            <div class="row mb-3">
                                <label class="col-sm-2 col-form-label">Report Type</label>
                                <div class="col-sm-10">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="allAsAt" value="allAsAt" checked>
                                        <label class="form-check-label" for="allAsAt">
                                            All AS At
                                        </label>
                                        <div class="input-group mt-1 mb-3" id="asAtDateContainer">
                                            <input type="date" class="form-control" id="asAtDate" name="asAtDate" value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="allBetween" value="allBetween">
                                        <label class="form-check-label" for="allBetween">
                                            All Between
                                        </label>
                                        <div class="row mt-1 mb-3" id="betweenDatesContainer" style="display: none;">
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="fromDate" name="fromDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="toDate" name="toDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="student" value="student">
                                        <label class="form-check-label" for="student">
                                            Student
                                        </label>
                                        <div class="mt-1 mb-3" id="studentContainer" style="display: none;">
                                            <select class="form-control" id="studentId" name="studentId">
                                                <option value="" selected disabled>Select a student...</option>
                                                <?php foreach ($students as $student): ?>
                                                <option value="<?php echo $student['student_code']; ?>">
                                                    <?php echo $student['first_name'] . ' ' . $student['last_name']; ?> 
                                                    [<?php echo $student['student_registration_id']; ?>] - 
                                                    <?php echo $student['program_name']; ?> - 
                                                    <?php echo $student['batch_name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="reportType" id="programme" value="programme">
                                        <label class="form-check-label" for="programme">
                                            Programme
                                        </label>
                                        <div class="mt-1 mb-3" id="programmeContainer" style="display: none;">
                                            <select class="form-control" id="programId" name="programId">
                                                <option value="" selected disabled>Select a programme...</option>
                                                <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo $program['program_code']; ?>">
                                                    <?php echo $program['program_name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($showReport): ?>
                <!-- Report Results Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary"><?php echo $reportTitle; ?></h6>
                        <button class="btn btn-sm btn-outline-primary" onclick="printReport()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($noResults): ?>
                            <div class="alert alert-info">
                                No additional payments found for the selected criteria.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered report-table" id="reportTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Student</th>
                                            <th>Registration ID</th>
                                            <th>Program</th>
                                            <th>Batch</th>
                                            <th>Fee Items</th>
                                            <th>Amount</th>
                                            <th>Paid Date</th>
                                            <th>Payment Method</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                            <th>Entered By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalAmount = 0;
                                        foreach ($reportData as $row): 
                                            if ($row['status'] != 'cancelled') {
                                                $totalAmount += $row['total_amount'];
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                            <td><?php echo $row['student_registration_id']; ?></td>
                                            <td><?php echo $row['program_name']; ?></td>
                                            <td><?php echo $row['batch_name']; ?></td>
                                            <td class="truncate-text" title="<?php echo htmlspecialchars($row['fee_items']); ?>">
                                                <?php echo $row['fee_items'] ? htmlspecialchars($row['fee_items']) : 'N/A'; ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format($row['total_amount'], 2); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['paid_date'])); ?></td>
                                            <td><?php echo $row['payment_method']; ?></td>
                                            <td><?php echo $row['payment_reference'] ? $row['payment_reference'] : 'N/A'; ?></td>
                                            <td>
                                                <?php if ($row['status'] == 'paid'): ?>
                                                    <span class="badge bg-success text-white">Paid</span>
                                                <?php elseif ($row['status'] == 'cancelled'): ?>
                                                    <span class="badge bg-danger text-white">Cancelled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $row['entered_by']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="6" class="text-right">Total (Excluding Cancelled):</th>
                                            <th class="text-right"><?php echo number_format($totalAmount, 2); ?></th>
                                            <th colspan="5"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; Your Website <?php echo date('Y'); ?></span>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#studentId, #programId').select2({
            width: '100%',
            placeholder: "Select an option"
        });
        
        // Handle radio button changes
        $('input[name="reportType"]').change(function() {
            // Hide all containers first
            $('#asAtDateContainer, #betweenDatesContainer, #studentContainer, #programmeContainer').hide();
            
            // Show the relevant container based on selection
            const selectedType = $(this).val();
            switch (selectedType) {
                case 'allAsAt':
                    $('#asAtDateContainer').show();
                    break;
                case 'allBetween':
                    $('#betweenDatesContainer').show();
                    break;
                case 'student':
                    $('#studentContainer').show();
                    break;
                case 'programme':
                    $('#programmeContainer').show();
                    break;
            }
        });
        
        // Form validation
        $('#reportForm').submit(function(e) {
            const reportType = $('input[name="reportType"]:checked').val();
            let isValid = true;
            
            switch (reportType) {
                case 'allAsAt':
                    if (!$('#asAtDate').val()) {
                        alert('Please select a date');
                        isValid = false;
                    }
                    break;
                case 'allBetween':
                    if (!$('#fromDate').val() || !$('#toDate').val()) {
                        alert('Please select both from and to dates');
                        isValid = false;
                    }
                    break;
                case 'student':
                    if (!$('#studentId').val()) {
                        alert('Please select a student');
                        isValid = false;
                    }
                    break;
                case 'programme':
                    if (!$('#programId').val()) {
                        alert('Please select a programme');
                        isValid = false;
                    }
                    break;
            }
            
            return isValid;
        });
        
        // Show tooltips for truncated text
        $('.truncate-text').hover(function() {
            const fullText = $(this).attr('title');
            if (fullText && this.offsetWidth < this.scrollWidth) {
                $(this).attr('data-toggle', 'tooltip');
                $(this).tooltip('show');
            }
        });
    });
    
    // Print report function
    function printReport() {
        const printContents = document.getElementById('reportTable').outerHTML;
        const originalContents = document.body.innerHTML;
        const reportTitle = document.querySelector('.card-header h6').textContent;
        
        const printStyles = `
            <style>
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .text-right { text-align: right; }
                h2 { text-align: center; margin-bottom: 20px; }
                .truncate-text { max-width: none; white-space: normal; overflow: visible; }
            </style>
        `;
        
        document.body.innerHTML = `
            ${printStyles}
            <h2>${reportTitle}</h2>
            ${printContents}
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
        
        // Reinitialize Select2 and other JS after printing
        $(document).ready(function() {
            $('#studentId, #programId').select2({
                width: '100%',
                placeholder: "Select an option"
            });
            
            $('input[name="reportType"]').change(function() {
                $('#asAtDateContainer, #betweenDatesContainer, #studentContainer, #programmeContainer').hide();
                
                const selectedType = $(this).val();
                switch (selectedType) {
                    case 'allAsAt':
                        $('#asAtDateContainer').show();
                        break;
                    case 'allBetween':
                        $('#betweenDatesContainer').show();
                        break;
                    case 'student':
                        $('#studentContainer').show();
                        break;
                    case 'programme':
                        $('#programmeContainer').show();
                        break;
                }
            });
            
            // Ensure the correct container is shown based on the current selection
            $('input[name="reportType"]:checked').trigger('change');
        });
    }
</script>

</body>
</html>
