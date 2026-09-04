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

// Function to get all batches
function getBatches($conn) {
    $query = "SELECT id, batch_name, programme FROM batch_table ORDER BY batch_name";
    
    $result = mysqli_query($conn, $query);
    $batches = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $batches[] = $row;
        }
    }
    
    return $batches;
}

// Function to get all lecturers
function getLecturers($conn) {
    $query = "SELECT id, title, lecturer_name FROM lecturer_table ORDER BY lecturer_name";
    
    $result = mysqli_query($conn, $query);
    $lecturers = [];
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $lecturers[] = $row;
        }
    }
    
    return $lecturers;
}

// Get data for dropdowns
$programs = getPrograms($conn);
$batches = getBatches($conn);
$lecturers = getLecturers($conn);

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
    $baseQuery = "SELECT t.id, t.programme_id, t.programme_name, t.batch_id, t.batch_name, 
                 t.module_id, t.module_name, t.lecturer_id, t.lecturer_name, 
                 t.day, t.start_time, t.end_time, t.start_date, t.end_date, 
                 t.comp1_deadline, t.comp2_deadline, t.status
                 FROM timetable t";
    
    $whereClause = " WHERE t.status = 'active'";
    $params = [];
    $paramTypes = "";
    
    switch ($reportType) {
        case 'program':
            $programId = $_POST['programId'];
            $whereClause .= " AND t.programme_id = ?";
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
            $reportTitle = "Timetable Report for " . $programName;
            break;
            
        case 'batch':
            $batchId = $_POST['batchId'];
            $whereClause .= " AND t.batch_id = ?";
            $params[] = $batchId;
            $paramTypes .= "i";
            
            // Get batch name for report title
            $batchName = "";
            foreach ($batches as $batch) {
                if ($batch['id'] == $batchId) {
                    $batchName = $batch['batch_name'];
                    break;
                }
            }
            $reportTitle = "Timetable Report for " . $batchName;
            break;
            
        case 'lecturer':
            $lecturerId = $_POST['lecturerId'];
            $whereClause .= " AND t.lecturer_id = ?";
            $params[] = $lecturerId;
            $paramTypes .= "i";
            
            // Get lecturer name for report title
            $lecturerName = "";
            foreach ($lecturers as $lecturer) {
                if ($lecturer['id'] == $lecturerId) {
                    $lecturerName = $lecturer['title'] . ' ' . $lecturer['lecturer_name'];
                    break;
                }
            }
            $reportTitle = "Timetable Report for " . $lecturerName;
            break;
            
        case 'day':
            $day = $_POST['day'];
            $whereClause .= " AND t.day = ?";
            $params[] = $day;
            $paramTypes .= "s";
            $reportTitle = "Timetable Report for " . $day;
            break;
            
        case 'dateRange':
            $fromDate = $_POST['fromDate'];
            $toDate = $_POST['toDate'];
            $whereClause .= " AND ((t.start_date BETWEEN ? AND ?) OR (t.end_date BETWEEN ? AND ?))";
            $params[] = $fromDate;
            $params[] = $toDate;
            $params[] = $fromDate;
            $params[] = $toDate;
            $paramTypes .= "ssss";
            $reportTitle = "Timetable Report from " . date('d/m/Y', strtotime($fromDate)) . " to " . date('d/m/Y', strtotime($toDate));
            break;
            
        case 'all':
        default:
            $reportTitle = "Complete Timetable Report";
            break;
    }
    
    // Complete the query
    $query = $baseQuery . $whereClause . " ORDER BY t.day, t.start_time";
    
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
    .day-column {
        min-width: 100px;
    }
    .time-column {
        min-width: 150px;
    }
    .program-column {
        min-width: 200px;
    }
    .batch-column {
        min-width: 120px;
    }
    .module-column {
        min-width: 200px;
    }
    .lecturer-column {
        min-width: 180px;
    }
    .duration-column {
        min-width: 180px;
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
                    <h1 class="h3 mb-0 text-gray-800">Timetable Report</h1>
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
                                        <input class="form-check-input" type="radio" name="reportType" id="all" value="all" checked>
                                        <label class="form-check-label" for="all">
                                            All Timetable Entries
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="program" value="program">
                                        <label class="form-check-label" for="program">
                                            By Program
                                        </label>
                                        <div class="mt-1 mb-3" id="programContainer" style="display: none;">
                                            <select class="form-control" id="programId" name="programId">
                                                <option value="" selected disabled>Select a program...</option>
                                                <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo $program['program_code']; ?>">
                                                    <?php echo $program['program_name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="batch" value="batch">
                                        <label class="form-check-label" for="batch">
                                            By Batch
                                        </label>
                                        <div class="mt-1 mb-3" id="batchContainer" style="display: none;">
                                            <select class="form-control" id="batchId" name="batchId">
                                                <option value="" selected disabled>Select a batch...</option>
                                                <?php foreach ($batches as $batch): ?>
                                                <option value="<?php echo $batch['id']; ?>">
                                                    <?php echo $batch['batch_name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="lecturer" value="lecturer">
                                        <label class="form-check-label" for="lecturer">
                                            By Lecturer
                                        </label>
                                        <div class="mt-1 mb-3" id="lecturerContainer" style="display: none;">
                                            <select class="form-control" id="lecturerId" name="lecturerId">
                                                <option value="" selected disabled>Select a lecturer...</option>
                                                <?php foreach ($lecturers as $lecturer): ?>
                                                <option value="<?php echo $lecturer['id']; ?>">
                                                    <?php echo $lecturer['title'] . ' ' . $lecturer['lecturer_name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="reportType" id="day" value="day">
                                        <label class="form-check-label" for="day">
                                            By Day
                                        </label>
                                        <div class="mt-1 mb-3" id="dayContainer" style="display: none;">
                                            <select class="form-control" id="day" name="day">
                                                <option value="" selected disabled>Select a day...</option>
                                                <option value="Monday">Monday</option>
                                                <option value="Tuesday">Tuesday</option>
                                                <option value="Wednesday">Wednesday</option>
                                                <option value="Thursday">Thursday</option>
                                                <option value="Friday">Friday</option>
                                                <option value="Saturday">Saturday</option>
                                                <option value="Sunday">Sunday</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="reportType" id="dateRange" value="dateRange">
                                        <label class="form-check-label" for="dateRange">
                                            By Date Range
                                        </label>
                                        <div class="row mt-1 mb-3" id="dateRangeContainer" style="display: none;">
                                            <div class="col-md-6">
                                                <label for="fromDate" class="form-label">From Date</label>
                                                <input type="date" class="form-control" id="fromDate" name="fromDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="toDate" class="form-label">To Date</label>
                                                <input type="date" class="form-control" id="toDate" name="toDate" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
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
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2" onclick="printReport()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportToExcel()">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($noResults): ?>
                            <div class="alert alert-info">
                                No timetable entries found for the selected criteria.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered report-table" id="reportTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th class="day-column">Day</th>
                                            <th class="time-column">Time</th>
                                            <th class="program-column">Program</th>
                                            <th class="batch-column">Batch</th>
                                            <th class="module-column">Module</th>
                                            <th class="lecturer-column">Lecturer</th>
                                            <th class="duration-column">Duration</th>
                                            <th>Component 1 Deadline</th>
                                            <th>Component 2 Deadline</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td><?php echo $row['day']; ?></td>
                                            <td>
                                                <?php 
                                                echo date("h:i A", strtotime($row['start_time'])) . " - " . 
                                                     date("h:i A", strtotime($row['end_time'])); 
                                                ?>
                                            </td>
                                            <td><?php echo $row['programme_name']; ?></td>
                                            <td><?php echo $row['batch_name']; ?></td>
                                            <td><?php echo $row['module_name']; ?></td>
                                            <td><?php echo $row['lecturer_name']; ?></td>
                                            <td>
                                                <?php 
                                                echo date("d M Y", strtotime($row['start_date'])) . " to " . 
                                                     date("d M Y", strtotime($row['end_date'])); 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                echo !empty($row['comp1_deadline']) ? 
                                                     date("d M Y", strtotime($row['comp1_deadline'])) : 'N/A'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                echo !empty($row['comp2_deadline']) ? 
                                                     date("d M Y", strtotime($row['comp2_deadline'])) : 'N/A'; 
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
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
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#programId, #batchId, #lecturerId, #day').select2({
            width: '100%',
            placeholder: "Select an option"
        });
        
        // Initialize DataTable if report is shown
        <?php if ($showReport && !$noResults): ?>
        var table = $('#reportTable').DataTable({
            "ordering": true,
            "paging": true,
            "searching": true,
            "info": true,
            "responsive": true,
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ]
        });
        <?php endif; ?>
        
        // Handle radio button changes
        $('input[name="reportType"]').change(function() {
            // Hide all containers first
            $('#programContainer, #batchContainer, #lecturerContainer, #dayContainer, #dateRangeContainer').hide();
            
            // Show the relevant container based on selection
            const selectedType = $(this).val();
            switch (selectedType) {
                case 'program':
                    $('#programContainer').show();
                    break;
                case 'batch':
                    $('#batchContainer').show();
                    break;
                case 'lecturer':
                    $('#lecturerContainer').show();
                    break;
                case 'day':
                    $('#dayContainer').show();
                    break;
                case 'dateRange':
                    $('#dateRangeContainer').show();
                    break;
            }
        });
        
        // Form validation
        $('#reportForm').submit(function(e) {
            const reportType = $('input[name="reportType"]:checked').val();
            let isValid = true;
            
            switch (reportType) {
                case 'program':
                    if (!$('#programId').val()) {
                        alert('Please select a program');
                        isValid = false;
                    }
                    break;
                case 'batch':
                    if (!$('#batchId').val()) {
                        alert('Please select a batch');
                        isValid = false;
                    }
                    break;
                case 'lecturer':
                    if (!$('#lecturerId').val()) {
                        alert('Please select a lecturer');
                        isValid = false;
                    }
                    break;
                case 'day':
                    if (!$('#day').val()) {
                        alert('Please select a day');
                        isValid = false;
                    }
                    break;
                case 'dateRange':
                    if (!$('#fromDate').val() || !$('#toDate').val()) {
                        alert('Please select both from and to dates');
                        isValid = false;
                    }
                    break;
            }
            
            return isValid;
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
                h2 { text-align: center; margin-bottom: 20px; }
                @media print {
                    .day-column { min-width: 80px; }
                    .time-column { min-width: 120px; }
                    .program-column { min-width: 150px; }
                    .batch-column { min-width: 100px; }
                    .module-column { min-width: 150px; }
                    .lecturer-column { min-width: 130px; }
                    .duration-column { min-width: 150px; }
                }
            </style>
        `;
        
        document.body.innerHTML = `
            ${printStyles}
            <h2>${reportTitle}</h2>
            ${printContents}
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
        
        // Reinitialize Select2 and DataTable after printing
        $(document).ready(function() {
            $('#programId, #batchId, #lecturerId, #day').select2({
                width: '100%',
                placeholder: "Select an option"
            });
            
            <?php if ($showReport && !$noResults): ?>
            $('#reportTable').DataTable({
                "ordering": true,
                "paging": true,
                "searching": true,
                "info": true,
                "responsive": true,
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ]
            });
            <?php endif; ?>
            
            // Ensure the correct container is shown based on the current selection
            $('input[name="reportType"]:checked').trigger('change');
        });
    }
    
    // Export to Excel function
    function exportToExcel() {
        const table = document.getElementById('reportTable');
        const reportTitle = document.querySelector('.card-header h6').textContent;
        const fileName = reportTitle.replace(/\s+/g, '_') + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        
        // Create a workbook
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        
        // Add the worksheet to the workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Timetable Report');
        
        // Generate and save the Excel file
        XLSX.writeFile(wb, fileName);
    }
</script>

</body>
</html>
