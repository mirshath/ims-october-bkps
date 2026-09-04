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




// Fetch programs from program_table
$programQuery = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
$programResult = mysqli_query($conn, $programQuery);

// Fetch statuses from status_table
$statusQuery = "SELECT id, status_name FROM status_table ORDER BY status_name";
$statusResult = mysqli_query($conn, $statusQuery);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $filterType = $_POST['filterType'];
    $programId = isset($_POST['program']) ? $_POST['program'] : '';
    $statusId = isset($_POST['status']) ? $_POST['status'] : '';
    
    // Build query based on filter type
    $query = "SELECT l.id, l.date, l.first_name, l.last_name, l.contact, l.email, 
              l.university, l.programme, l.intake, l.status, l.details, l.entered_by
              FROM leads l
              WHERE 1=1";
    
    if ($filterType == 'program' && !empty($programId)) {
        // Get the program name first
        $programNameQuery = "SELECT program_name FROM program_table WHERE program_code = '$programId'";
        $programNameResult = mysqli_query($conn, $programNameQuery);
        $programNameRow = mysqli_fetch_assoc($programNameResult);
        $programName = $programNameRow['program_name'];
        
        // Use LIKE for more flexible matching
        $query .= " AND (l.programme LIKE '%$programName%' OR l.programme = '$programId')";
    } else if ($filterType == 'status' && !empty($statusId)) {
        $statusNameQuery = "SELECT status_name FROM status_table WHERE id = '$statusId'";
        $statusNameResult = mysqli_query($conn, $statusNameQuery);
        $statusNameRow = mysqli_fetch_assoc($statusNameResult);
        $statusName = $statusNameRow['status_name'];
        
        $query .= " AND l.status = '$statusName'";
    }
    
    // For debugging
    $debug = false; // Set to true to see the query
    if ($debug) {
        echo "<div class='alert alert-info'>Query: $query</div>";
    }

    $query .= " ORDER BY l.date DESC";
    $leadsResult = mysqli_query($conn, $query);
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
                    <h4 class="h4 mb-0 text-gray-800">Leads Report</h4>
                    <?php if (isset($leadsResult) && mysqli_num_rows($leadsResult) > 0): ?>
                    <button id="printBtn" class="btn btn-info">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
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
                                <h6 class="mb-0 me-2">Lead Report Filter</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="filterType" id="filterAll" value="all" checked>
                                                <label class="form-check-label" for="filterAll">
                                                    All
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="filterType" id="filterProgram" value="program">
                                                <label class="form-check-label" for="filterProgram">
                                                    Programme
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="filterType" id="filterStatus" value="status">
                                                <label class="form-check-label" for="filterStatus">
                                                    Lead Status
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="program" class="form-label">Program:</label>
                                            <select class="form-select" id="program" name="program" disabled>
                                                <option value="">Select Program</option>
                                                <?php 
                                                mysqli_data_seek($programResult, 0);
                                                while ($row = mysqli_fetch_assoc($programResult)) { 
                                                ?>
                                                    <option value="<?php echo $row['program_code']; ?>"><?php echo $row['program_name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Lead Status:</label>
                                            <select class="form-select" id="status" name="status" disabled>
                                                <option value="">Select Status</option>
                                                <?php 
                                                mysqli_data_seek($statusResult, 0);
                                                while ($row = mysqli_fetch_assoc($statusResult)) { 
                                                ?>
                                                    <option value="<?php echo $row['id']; ?>"><?php echo $row['status_name']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12 text-end">
                                            <button type="submit" class="btn btn-primary">Generate Report</button>
                                            <button type="reset" class="btn btn-secondary" id="resetBtn">Reset</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($leadsResult) && mysqli_num_rows($leadsResult) > 0): ?>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-table"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Leads Report Data</h6>
                            </div>
                            <div class="card-body">
                                <!-- Leads Table -->
                                <div class="table-responsive mb-4" id="reportContent">
                                    <table id="leadsTable" class="table table-striped table-bordered" style="width: 100%; font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Name</th>
                                                <th>Contact</th>
                                                <th>Email</th>
                                                <th>University</th>
                                                <th>Programme</th>
                                                <th>Intake</th>
                                                <th>Status</th>
                                                <th>Details</th>
                                                <th>Entered By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($lead = mysqli_fetch_assoc($leadsResult)): ?>
                                            <tr>
                                                <td><?php echo $lead['id']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($lead['date'])); ?></td>
                                                <td><?php echo $lead['first_name'] . ' ' . $lead['last_name']; ?></td>
                                                <td><?php echo $lead['contact']; ?></td>
                                                <td><?php echo $lead['email']; ?></td>
                                                <td>
                                                    <?php 
                                                    $uniId = $lead['university'];
                                                    $uniQuery = "SELECT university_name FROM universities WHERE id = '$uniId'";
                                                    $uniResult = mysqli_query($conn, $uniQuery);
                                                    $uniRow = mysqli_fetch_assoc($uniResult);
                                                    echo $uniRow ? $uniRow['university_name'] : $uniId;
                                                    ?>
                                                </td>
                                                <td><?php echo $lead['programme']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($lead['intake'])); ?></td>
                                                <td><?php echo $lead['status']; ?></td>
                                                <td><?php echo $lead['details']; ?></td>
                                                <td><?php echo $lead['entered_by']; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Summary Section -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Summary by Status</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Status</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        mysqli_data_seek($leadsResult, 0);
                                                        $statusCounts = [];
                                                        $totalLeads = mysqli_num_rows($leadsResult);
                                                        
                                                        while ($lead = mysqli_fetch_assoc($leadsResult)) {
                                                            $status = $lead['status'];
                                                            if (!isset($statusCounts[$status])) {
                                                                $statusCounts[$status] = 0;
                                                            }
                                                            $statusCounts[$status]++;
                                                        }
                                                        
                                                        foreach ($statusCounts as $status => $count) {
                                                            $percentage = ($count / $totalLeads) * 100;
                                                            echo "<tr>
                                                                <td>{$status}</td>
                                                                <td>{$count}</td>
                                                                <td>" . number_format($percentage, 2) . "%</td>
                                                            </tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Summary by Programme</h6>
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Programme</th>
                                                            <th>Count</th>
                                                            <th>Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        mysqli_data_seek($leadsResult, 0);
                                                        $programmeCounts = [];
                                                        
                                                        while ($lead = mysqli_fetch_assoc($leadsResult)) {
                                                            $programme = $lead['programme'];
                                                            if (!isset($programmeCounts[$programme])) {
                                                                $programmeCounts[$programme] = 0;
                                                            }
                                                            $programmeCounts[$programme]++;
                                                        }
                                                        
                                                        foreach ($programmeCounts as $programme => $count) {
                                                            $percentage = ($count / $totalLeads) * 100;
                                                            echo "<tr>
                                                                <td>{$programme}</td>
                                                                <td>{$count}</td>
                                                                <td>" . number_format($percentage, 2) . "%</td>
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
                <?php elseif (isset($leadsResult)): ?>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            No leads found matching the selected criteria.
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
        $('#program, #status').select2({
            width: '100%'
        });

        // Initialize DataTables for the results table with export buttons
        $('#leadsTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf'
            ]
        });

        // Function to update form state based on selection
        function updateFormState() {
            if ($('#filterAll').is(':checked')) {
                $('#program').prop('disabled', true).trigger('change');
                $('#status').prop('disabled', true).trigger('change');
            } else if ($('#filterProgram').is(':checked')) {
                $('#program').prop('disabled', false).trigger('change');
                $('#status').prop('disabled', true).trigger('change');
            } else if ($('#filterStatus').is(':checked')) {
                $('#program').prop('disabled', true).trigger('change');
                $('#status').prop('disabled', false).trigger('change');
            }
        }

        // Add event listeners to radio buttons
        $('input[name="filterType"]').on('change', updateFormState);

        // Reset button handler
        $('#resetBtn').on('click', function() {
            $('#filterAll').prop('checked', true);
            updateFormState();
        });

        // Print functionality
        $('#printBtn').on('click', function() {
            var printContents = document.getElementById('reportContent').innerHTML;
            var originalContents = document.body.innerHTML;
            
            document.body.innerHTML = `
                <div style="padding: 20px;">
                    <h1 style="text-align: center; margin-bottom: 20px;">Leads Report</h1>
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
