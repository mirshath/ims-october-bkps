<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 

// Initialize variables for edit mode
$isEdit = false;
$assessment = null;

// Check if form was submitted
$showResults = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showResults = true;

    // Get form data
    $programme_id = $_POST['programme_id'] ?? '';
    $batch_id = $_POST['batch_id'] ?? '';
    $report_type = $_POST['report_type'] ?? 'assessment';
    $module_id = isset($_POST['module_id']) ? $_POST['module_id'] : '';
    $student_id = isset($_POST['student_id']) ? $_POST['student_id'] : '';
    $student_status = isset($_POST['student_status']) ? $_POST['student_status'] : '';

    // Get programme, batch, and module details
    $programme_name = '';
    $batch_name = '';
    $module_name = '';
    $year_name = '';
    $semester_name = '';

    // Get programme name
    if (!empty($programme_id)) {
        $programme_query = "SELECT program_name FROM program_table WHERE program_code = ?";
        $stmt = $conn->prepare($programme_query);
        $stmt->bind_param("i", $programme_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $programme_name = $row['program_name'];
        }
    }

    // Get batch name
    if (!empty($batch_id)) {
        $batch_query = "SELECT batch_name FROM batch_table WHERE id = ?";
        $stmt = $conn->prepare($batch_query);
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $batch_name = $row['batch_name'];
        }
    }

    // Get module details
    if (!empty($module_id)) {
        $module_query = "SELECT m.module_name, y.year_name, s.semester_name 
                        FROM modules m 
                        LEFT JOIN year_table y ON m.year_id = y.id 
                        LEFT JOIN semester_table s ON m.semester_id = s.id 
                        WHERE m.id = ?";
        $stmt = $conn->prepare($module_query);
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $module_name = $row['module_name'];
            $year_name = $row['year_name'];
            $semester_name = $row['semester_name'];
        }
    }

    // Get assessment details
    $assessment_title = '';
    $assessment_date = '';
    $assessment_description = '';

    if (!empty($module_id)) {
        // Get assessment details from assessments table
        $assessment_query = "SELECT a.description, a.assessment_date 
                            FROM assessments a
                            WHERE a.module_id = ? AND a.programme_id = ? AND a.batch_id = ? 
                            ORDER BY a.assessment_date DESC LIMIT 1";
        $stmt = $conn->prepare($assessment_query);
        $stmt->bind_param("iii", $module_id, $programme_id, $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $assessment_description = strip_tags($row['description']);
            $assessment_date = $row['assessment_date'];
        }

        // Get assessment components for title
        $components_query = "SELECT ac.main_component_id, ac.sub_component_id, 
                            comp.as_main_component_name, sub.sub_component_name 
                            FROM allocated_components ac 
                            LEFT JOIN assignment_components comp ON ac.main_component_id = comp.id 
                            LEFT JOIN sub_assign_components sub ON ac.sub_component_id = sub.id 
                            WHERE ac.module_id = ?";
        $stmt = $conn->prepare($components_query);
        $stmt->bind_param("i", $module_id);
        $stmt->execute();
        $components_result = $stmt->get_result();
        $components = [];
        while ($comp = $components_result->fetch_assoc()) {
            $components[] = $comp;
            if (!empty($comp['as_main_component_name'])) {
                if (!empty($assessment_title)) {
                    $assessment_title .= ' & ';
                }
                $assessment_title .= $comp['as_main_component_name'];
            }
        }
    }

    // Get student results
    $students = [];
    if ($report_type === 'assessment' && !empty($module_id)) {
       

        $status_where = "";
        $bind_types = "iiiiiiiii";
        $bind_params = [$module_id, $module_id, $module_id, $module_id, $programme_id, $batch_id, $module_id, $programme_id, $batch_id];

        if (!empty($student_status)) {
            $status_where = " AND ap.status = ? ";
            $bind_types .= "s";
            $bind_params[] = $student_status;
        }

        $students_query = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id, 
                          fsr.final_result,
                          (SELECT status FROM email_sending_log 
                           WHERE student_id = s.student_code 
                           AND module_id = ? 
                           ORDER BY sent_date DESC LIMIT 1) as email_status,
                          (SELECT sent_date FROM email_sending_log 
                           WHERE student_id = s.student_code 
                           AND module_id = ? 
                           ORDER BY sent_date DESC LIMIT 1) as email_sent_date,
                          (SELECT sent_by FROM email_sending_log 
                           WHERE student_id = s.student_code 
                           AND module_id = ? 
                           ORDER BY sent_date DESC LIMIT 1) as sent_by,
                          (SELECT emailed_result FROM email_sending_log 
                           WHERE student_id = s.student_code 
                           AND module_id = ? 
                           ORDER BY sent_date DESC LIMIT 1) as emailed_result
                          FROM students s 
                          JOIN allocate_programme ap ON s.student_code = ap.student_code 
                          LEFT JOIN final_student_results fsr ON s.student_code = fsr.student_id 
                              AND fsr.program_id = ? AND fsr.batch_id = ? AND fsr.module_id = ? 
                          WHERE ap.programme_code = ? AND ap.batch_id = ? 
                          " . $status_where . "
                          ORDER BY s.first_name, s.last_name";

        $stmt = $conn->prepare($students_query);
        $stmt->bind_param($bind_types, ...$bind_params);


        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'student_code' => $row['student_code'],
                'student_name' => $row['first_name'] . ' ' . $row['last_name'],
                'student_registration_id' => $row['student_registration_id'],
                'final_result' => $row['final_result'],
                'grade' => determineGrade($row['final_result']),
                'email_status' => $row['email_status'],
                'emailed_result' => $row['emailed_result'],
                'email_sent_date' => $row['email_sent_date'],
                'sent_by' => $row['sent_by']
            ];
        }
    }

    // Get student details and results for "By Student" report
    $student_details = [];
    $student_modules = [];
    if ($report_type === 'student' && !empty($student_id) && !empty($programme_id) && !empty($batch_id)) {
        // Get student details
        $student_query = "SELECT s.student_code, s.first_name, s.last_name, ap.student_registration_id,
                         p.program_name, b.batch_name, y.year_name, sem.semester_name
                         FROM students s
                         JOIN allocate_programme ap ON s.student_code = ap.student_code
                         JOIN program_table p ON ap.programme_code = p.program_code
                         JOIN batch_table b ON ap.batch_id = b.id
                         LEFT JOIN year_table y ON y.id = (SELECT year_id FROM modules WHERE programme_id = p.program_code LIMIT 1)
                         LEFT JOIN semester_table sem ON sem.id = (SELECT semester_id FROM modules WHERE programme_id = p.program_code LIMIT 1)
                         WHERE s.student_code = ? AND ap.programme_code = ? AND ap.batch_id = ?";

        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("iii", $student_id, $programme_id, $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $student_details = [
                'student_code' => $row['student_code'],
                'student_name' => $row['first_name'] . ' ' . $row['last_name'],
                'student_registration_id' => $row['student_registration_id'],
                'program_name' => $row['program_name'],
                'batch_name' => $row['batch_name'],
                'year_name' => $row['year_name'],
                'semester_name' => $row['semester_name']
            ];
        }

      


        $modules_query = "SELECT m.id, m.module_name, m.module_code, a.assessment_date, fsr.final_result,
                 GROUP_CONCAT(DISTINCT comp.as_main_component_name SEPARATOR ' & ') as main_components,
                 (SELECT status FROM email_sending_log 
                  WHERE student_id = ? 
                  AND module_id = m.id 
                  ORDER BY sent_date DESC LIMIT 1) as email_status,
                 (SELECT emailed_result FROM email_sending_log 
                  WHERE student_id = ? 
                  AND module_id = m.id 
                  ORDER BY sent_date DESC LIMIT 1) as emailed_result,
                 (SELECT sent_date FROM email_sending_log 
                  WHERE student_id = ? 
                  AND module_id = m.id 
                  ORDER BY sent_date DESC LIMIT 1) as email_sent_date,
                 (SELECT sent_by FROM email_sending_log 
                  WHERE student_id = ? 
                  AND module_id = m.id 
                  ORDER BY sent_date DESC LIMIT 1) as sent_by
                 FROM modules m
                 LEFT JOIN assessments a ON m.id = a.module_id AND a.programme_id = ? AND a.batch_id = ?
                 LEFT JOIN final_student_results fsr ON m.id = fsr.module_id AND fsr.student_id = ? 
                     AND fsr.program_id = ? AND fsr.batch_id = ?
                 LEFT JOIN allocated_components ac ON m.id = ac.module_id
                 LEFT JOIN assignment_components comp ON ac.main_component_id = comp.id
                 WHERE m.programme_id = ?
                 GROUP BY m.id
                 ORDER BY a.assessment_date DESC";

        $stmt = $conn->prepare($modules_query);
        $stmt->bind_param(
            "iiiiiiiiii",
            $student_id,  // 1: email_status
            $student_id,  // 2: emailed_result
            $student_id,  // 3: email_sent_date
            $student_id,  // 4: sent_by
            $programme_id, // 5: assessments.programme_id
            $batch_id,    // 6: assessments.batch_id
            $student_id,  // 7: fsr.student_id
            $programme_id, // 8: fsr.program_id
            $batch_id,    // 9: fsr.batch_id
            $programme_id // 10: modules.programme_id (WHERE m.programme_id = ?)
        );


        $stmt->execute();
        $result = $stmt->get_result();

        $student_modules = [];
        while ($row = $result->fetch_assoc()) {
            $student_modules[] = [
                'module_id' => $row['id'],
                'module_code' => $row['module_code'],
                'module_name' => $row['module_name'],
                'main_components' => $row['main_components'],
                'assessment_date' => $row['assessment_date'],
                'final_result' => $row['final_result'],
                'grade' => determineGrade($row['final_result']),
                'email_status' => $row['email_status'],
                'emailed_result' => $row['emailed_result'],
                'email_sent_date' => $row['email_sent_date'],
                'sent_by' => $row['sent_by']
            ];
        }
    }
}

function determineGrade($result)
{
    // If result is empty, return empty string
    if (empty($result)) return '';


    $result = trim($result);

    if (!is_numeric($result)) {
        // If it's a string (non-numeric), just return it directly
        return $result;
    }

    // If numeric, convert to float for calculation
    $result = (float)$result;

    // Example numeric grading (you can adjust thresholds or add program-specific logic)
    if ($result >= 70) {
        return 'Distinction';
    } elseif ($result >= 60) {
        return 'Merit';
    } elseif ($result >= 50) {
        return 'Pass';
    } else {
        return 'Resit';
    }
}




// Function to format email status for display
function formatEmailStatus($status, $sentDate, $sentBy)
{
    if (empty($status)) {
        return '<span class="text-warning">Not Sent</span>';
    }

    $statusClass = ($status == 'sent') ? 'text-success' : 'text-danger';
    $statusText = ($status == 'sent') ? 'Sent' : 'Failed';
    $formattedDate = !empty($sentDate) ? date('Y-m-d H:i', strtotime($sentDate)) : 'N/A';
    $sentByInfo = !empty($sentBy) ? " by {$sentBy}" : "";

    return "<span class='{$statusClass}'>{$statusText}</span> on {$formattedDate}{$sentByInfo}";
}
?>

<!-- Add print-specific CSS -->
<style>
    @media print {

        /* Hide everything by default */
        body * {
            visibility: hidden;
        }

        /* Only show the results section */
        .print-section,
        .print-section * {
            visibility: visible;
        }

        /* Position the results section at the top of the page */
        .print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        /* Hide the print button when printing */
        .no-print {
            display: none !important;
        }

        /* Ensure tables display properly */
        table {
            width: 100%;
            border_collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        /* Add page title */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        /* Ensure proper page breaks */
        .page-break {
            page-break-after: always;
        }

        /* Hide DataTables controls when printing */
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .dataTables_processing {
            display: none !important;
        }

        /* Make sure all rows are visible */
        .dataTables_wrapper .row-border tr {
            display: table-row !important;
        }

        /* Hide DataTables buttons */
        .dt-buttons {
            display: none !important;
        }
    }

    /* Hide print header in normal view */
    .print-header {
        display: none;
    }

    /* Email status styling */
    .text-success {
        color: #28a745;
    }

    .text-danger {
        color: #dc3545;
    }

    .text-warning {
        color: #ffc107;
    }

    .fixed-header {
        position: fixed;
        top: 70px;
        /* adjust if you have a navbar */
        left: 0;
        right: 0;
        /* background: #fff; */
        z-index: 1030;
        /* keeps it above content */
        padding: 15px 20px;
        /* border-bottom: 1px solid #ddd; */

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
            <div class="p-3" style="font-size: 12px;">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">
                        Result Mailing Report
                    </h4>
                    <?php if ($showResults): ?>
                        <div class="d-flex justify-content-end fixed-header">
                            <button onclick="window.print();" class="btn btn-sm btn-primary shadow-sm no-print mr-2">
                                <i class="fas fa-print fa-sm text-white-50"></i> Print Report
                            </button>
                            <!-- <button onclick="exportTableData();" class="btn btn-sm btn-success shadow-sm no-print">
                                <i class="fas fa-file-export fa-sm text-white-50"></i> Export Data
                            </button> -->
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Section -->
                <form action="" method="POST" enctype="multipart/form-data" class="no-print">
                    <div class="row mb-5">
                        <div class="col-md-7">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="programme">Programme</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="programme_id" id="programme" class="form-control select2" required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" class="form-control select2" required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="student_status">Student Status</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="student_status" id="student_status" class="form-control select2">
                                                    <option value="active" selected>Active Students</option>
                                                    <option value="completed">Completed Students</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label>Report Type</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="report_type" id="report_type_assessment" value="assessment" checked>
                                                    <label class="form-check-label" for="report_type_assessment">By Assessment</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="report_type" id="report_type_student" value="student">
                                                    <label class="form-check-label" for="report_type_student">By Student</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="module">Module</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3" id="module_container">
                                                <select name="module_id" id="module" class="form-control select2" required>
                                                    <option value="">Select Module</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="student">Student</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3" id="student_container" style=" width: 100%;">
                                                <select name="student_id" id="student" class="form-control select2" style="width: 100%;">
                                                    <option value="">Select Student</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="year_display">Year</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <input type="text" name="year_display" id="year_display" class="form-control" readonly>
                                                <input type="hidden" name="year_id" id="year_id">
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="semester_display">Semester</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <input type="text" name="semester_display" id="semester_display" class="form-control" readonly>
                                                <input type="hidden" name="semester_id" id="semester_id">
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary float-right">
                                        View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if ($showResults): ?>
                    <!-- Results Section - This will be printed -->
                    <div class="card shadow mb-4 print-section">
                        <!-- Print-only header -->
                        <div class="print-header">
                            <h3>Exam / Assignment Report</h3>
                            <p>Date: <?php echo date('Y-m-d'); ?></p>
                        </div>

                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Results</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($report_type === 'assessment'): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" width="100%" cellspacing="0">
                                        <tr>
                                            <th width="20%">Programme</th>
                                            <td>: <?php echo $programme_name . ' - ' . $batch_name; ?></td>
                                        </tr>
                                        <?php if (!empty($student_status)): ?>
                                            <tr>
                                                <th>Student Status</th>
                                                <td>: <?php echo ucfirst($student_status); ?> Students</td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Year</th>
                                            <td>: <?php echo $year_name; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Semester</th>
                                            <td>: <?php echo $semester_name; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Module</th>
                                            <td>: <?php echo $module_name; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Title</th>
                                            <td>: <?php echo $assessment_title; ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td>: <?php echo !empty($assessment_date) ? date('Y-m-d h:i A', strtotime($assessment_date)) : ''; ?></td>
                                        </tr>
                                        <!-- <tr>
                                    <th>Description</th>
                                    <td>: <div id="assessment_description_editor"><?php echo $assessment_description; ?></div></td>
                                </tr> -->
                                    </table>
                                </div>
                            <?php endif; ?>

                            <?php if ($report_type === 'assessment' && !empty($students)): ?>
                                <div class="table-responsive mt-4">
                                    <table class="table table-striped table-hover" id="resultsTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Student Registration ID</th>
                                                <th>Student Name</th>
                                                <th>Result</th>
                                                <th>Grade</th>
                                                <th>Email Status</th>
                                                <th>Emailed Result</th>
                                                <th>Sent By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students as $student): ?>
                                                <tr>
                                                    <td><?php echo $student['student_registration_id']; ?></td>
                                                    <td><?php echo $student['student_name']; ?></td>
                                                    <td><?php echo $student['final_result']; ?></td>
                                                    <td><?php echo $student['grade']; ?></td>
                                                    <td>
                                                        <?php if (empty($student['email_status'])): ?>
                                                            <span class="text-warning">Not Sent</span>
                                                        <?php else: ?>
                                                            <?php
                                                            $statusClass = ($student['email_status'] == 'sent') ? 'text-success' : 'text-danger';
                                                            $statusText = ($student['email_status'] == 'sent') ? 'Sent' : 'Failed';
                                                            $formattedDate = !empty($student['email_sent_date']) ? date('Y-m-d H:i', strtotime($student['email_sent_date'])) : 'N/A';
                                                            echo "<span class='{$statusClass}'>{$statusText}</span> on {$formattedDate}";
                                                            ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $student['emailed_result']; ?></td>
                                                    <td><?php echo $student['sent_by'] ?? 'N/A'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php elseif ($report_type === 'student' && !empty($student_details)): ?>
                                <div class="card mb-4">
                                    <div class="card-header py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <tbody>
                                                    <tr>
                                                        <th>Student Registration ID:</th>
                                                        <td><?php echo $student_details['student_registration_id']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Student Name:</th>
                                                        <td><?php echo $student_details['student_name']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Programme:</th>
                                                        <td><?php echo $student_details['program_name'] . ' - ' . $student_details['batch_name']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Year:</th>
                                                        <td><?php echo $student_details['year_name']; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Semester:</th>
                                                        <td><?php echo $student_details['semester_name']; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($student_modules)): ?>
                                    <div class="table-responsive mt-4">
                                        <table class="table table-striped table-hover" id="studentModulesTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Module Code</th>
                                                    <th>Module Name</th>
                                                    <th>Main Components</th>
                                                    <th>Date</th>
                                                    <th>Grade</th>
                                                    <th>Email Status</th>
                                                    <th>Emailed Result</th>
                                                    <th>Sent By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($student_modules as $module): ?>
                                                    <tr>
                                                        <td><?php echo $module['module_code']; ?></td>
                                                        <td><?php echo $module['module_name']; ?></td>
                                                        <td><?php echo $module['main_components'] ?? 'N/A'; ?></td>
                                                        <td><?php echo !empty($module['assessment_date']) ? date('Y-m-d', strtotime($module['assessment_date'])) : 'N/A'; ?></td>
                                                        <td><?php echo $module['grade']; ?></td>

                                                        <td>
                                                            <?php if (empty($module['email_status'])): ?>
                                                                <span class="text-warning">Not Sent</span>
                                                            <?php else: ?>
                                                                <?php
                                                                $statusClass = ($module['email_status'] == 'sent') ? 'text-success' : 'text-danger';
                                                                $statusText = ($module['email_status'] == 'sent') ? 'Sent' : 'Failed';
                                                                $formattedDate = !empty($module['email_sent_date']) ? date('Y-m-d H:i', strtotime($module['email_sent_date'])) : 'N/A';
                                                                echo "<span class='{$statusClass}'>{$statusText}</span> on {$formattedDate}";
                                                                ?>
                                                            <?php endif; ?>
                                                        </td>

                                                        <td><?php echo $module['emailed_result'] ?? 'N/A'; ?></td>
                                                        <td><?php echo $module['sent_by'] ?? 'N/A'; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mt-4">
                                        No module results found for this student.
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($report_type === 'student'): ?>
                                <div class="alert alert-warning mt-4">
                                    No student information found. Please select a valid student.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mt-4">
                                    No results found for the selected criteria.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
<link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">

<script>
    $(document).ready(function() {
        // Initialize CKEditor for assessment description
        CKEDITOR.replace('assessment_description_editor', {
            readOnly: true,
            toolbar: [],
            removePlugins: 'elementspath,resize',
            height: '200px'
        });

        $('.select2').select2({
            width: '100%'
        });

        // Initialize report type behavior
        $('input[name="report_type"]:checked').trigger('change');

        // Fetch Programmes
        $.ajax({
            url: "transection_exams/fetch_programmes.php",
            method: "GET",
            dataType: "json",
            success: function(data) {
                let programmeDropdown = $('#programme');
                programmeDropdown.empty().append('<option value="">Select Programme</option>');
                data.forEach(function(programme) {
                    programmeDropdown.append(`<option value="${programme.program_code}">${programme.program_name}</option>`);
                });
                <?php if (isset($_POST['programme_id'])): ?>
                    $('#programme').val("<?php echo $_POST['programme_id']; ?>").trigger('change');
                <?php endif; ?>
                <?php if (isset($_POST['student_status'])): ?>
                    setTimeout(function() {
                        $('#student_status').val("<?php echo $_POST['student_status']; ?>").trigger('change');
                    }, 100);
                <?php endif; ?>
            }
        });

        // Programme change event
        $('#programme').change(function() {
            let programmeId = $(this).val();

            // Clear subsequent dropdowns and fields
            $('#batch').empty().append('<option value="">Select Batch</option>');
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#student').empty().append('<option value="">Select Student</option>');
            $('#year_display').val('');
            $('#semester_display').val('');
            $('#year_id').val('');
            $('#semester_id').val('');

            if (programmeId) {
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        let batchDropdown = $('#batch');
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        data.forEach(function(batch) {
                            batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                        });
                        <?php if (isset($_POST['batch_id'])): ?>
                            $('#batch').val("<?php echo $_POST['batch_id']; ?>").trigger('change');
                        <?php endif; ?>
                    }
                });
            }
        });

        // Student Status change event - re-fetch students if student report type
        $('#student_status').change(function() {
            let batchId = $('#batch').val();
            let reportType = $('input[name="report_type"]:checked').val();
            if (batchId && reportType === 'student') {
                $('#batch').trigger('change');
            }
        });

        // Batch change event
        $('#batch').change(function() {
            let batchId = $(this).val();
            let programmeId = $('#programme').val();
            let studentStatus = $('#student_status').val();

            // Clear subsequent dropdowns and fields
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#student').empty().append('<option value="">Select Student</option>');
            $('#year_display').val('');
            $('#semester_display').val('');
            $('#year_id').val('');
            $('#semester_id').val('');

            if (batchId && programmeId) {
                // Get report type
                let reportType = $('input[name="report_type"]:checked').val();

                // Fetch modules if report type is 'assessment'
                if (reportType === 'assessment') {
                    $.ajax({
                        url: "transection_exams/fetch_modules.php",
                        method: "POST",
                        data: {
                            batch_id: batchId
                        },
                        dataType: "json",
                        success: function(data) {
                            let moduleDropdown = $('#module');
                            moduleDropdown.empty().append('<option value="">Select Module</option>');
                            data.forEach(function(module) {
                                moduleDropdown.append(`<option value="${module.id}">${module.name}</option>`);
                            });
                            <?php if (isset($_POST['module_id'])): ?>
                                $('#module').val("<?php echo $_POST['module_id']; ?>").trigger('change');
                            <?php endif; ?>
                        }
                    });
                }

                // Fetch students if report type is 'student'
                if (reportType === 'student') {
                    $.ajax({
                        url: "transection_exams/fetch_students.php",
                        method: "POST",
                        data: {
                            programme_id: programmeId,
                            batch_id: batchId,
                            student_status: studentStatus
                        },
                        dataType: "json",
                        success: function(data) {
                            let studentDropdown = $('#student');
                            studentDropdown.empty().append('<option value="">Select Student</option>');
                            data.forEach(function(student) {
                                studentDropdown.append(`<option value="${student.student_code}">${student.student_name} (${student.student_registration_id})</option>`);
                            });
                            <?php if (isset($_POST['student_id'])): ?>
                                $('#student').val("<?php echo $_POST['student_id']; ?>").trigger('change');
                            <?php endif; ?>
                        }
                    });
                }
            }
        });

        // Module change event
        $('#module').change(function() {
            let moduleId = $(this).val();

            // Clear year and semester fields
            $('#year_display').val('');
            $('#semester_display').val('');
            $('#year_id').val('');
            $('#semester_id').val('');

            if (moduleId) {
                $.ajax({
                    url: "transection_exams/fetch_year_semester.php",
                    method: "POST",
                    data: {
                        module_id: moduleId
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#year_display').val(data.year_name);
                            $('#semester_display').val(data.semester_name);
                            $('#year_id').val(data.year_id);
                            $('#semester_id').val(data.semester_id);
                        }
                    }
                });
            }
        });

        // Report Type change event
        $('input[name="report_type"]').change(function() {
            let reportType = $('input[name="report_type"]:checked').val();

            if (reportType === 'assessment') {
                // For assessment reports:
                // - Show module dropdown, hide student dropdown
                // - Make module required, student not required
                $('#module_container').show();
                $('#student_container').show();
                $('#module').prop('required', true);
                $('#student').prop('required', false);

                // If batch is already selected, load modules
                let batchId = $('#batch').val();
                if (batchId) {
                    $('#batch').trigger('change');
                }
            } else {
                // For student reports:
                // - Hide module dropdown, show student dropdown
                // - Make module not required, student required
                $('#module_container').show();
                $('#student_container').show();
                $('#module').prop('required', false);
                $('#student').prop('required', true);

                // If batch is already selected, load students
                let batchId = $('#batch').val();
                if (batchId) {
                    $('#batch').trigger('change');
                }

                // Clear module, year and semester fields
                $('#module').val('').trigger('change');
                $('#year_display').val('');
                $('#semester_display').val('');
                $('#year_id').val('');
                $('#semester_id').val('');
            }
        });

        // Initialize DataTable for results
        <?php if ($showResults): ?>
            $('#resultsTable').DataTable({
                "paging": false, // Disable pagination completely
                "pageLength": 1000, // Set a large number as fallback
                "ordering": true, // Keep sorting functionality
                "info": true, // Keep info about records
                "searching": true, // Keep search functionality
                "dom": 'Bfrti', // Remove 'p' from dom which represents pagination
                "buttons": [{
                    extend: 'print',
                    className: 'no-print',
                    customize: function(win) {
                        $(win.document.body).find('.dataTables_info, .dataTables_filter, .dataTables_length').hide();
                    }
                }]
            });

            $('#studentModulesTable').DataTable({
                "paging": false, // Disable pagination completely
                "pageLength": 1000, // Set a large number as fallback
                "ordering": true, // Keep sorting functionality
                "info": true, // Keep info about records
                "searching": true, // Keep search functionality
                "dom": 'Bfrti', // Remove 'p' from dom which represents pagination
                "buttons": [{
                    extend: 'print',
                    className: 'no-print',
                    customize: function(win) {
                        $(win.document.body).find('.dataTables_info, .dataTables_filter, .dataTables_length').hide();
                    }
                }]
            });
        <?php endif; ?>
    });

    // Function to export table data to CSV
    function exportTableData() {
        // Get the report type
        const reportType = $('input[name="report_type"]:checked').val();

        if (reportType === 'assessment') {
            exportAssessmentReport();
        } else if (reportType === 'student') {
            exportStudentReport();
        } else {
            alert('No data available to export');
        }
    }

    // Function to export assessment report
    function exportAssessmentReport() {
        let csvContent = "";
        let filename = "assessment_results_" + new Date().toISOString().slice(0, 10);

        // First add the assessment details
        const detailsTable = document.querySelector('.card-body .table-striped:not(#resultsTable)');
        if (detailsTable) {
            // Add a section header
            csvContent += "Assessment Details\n";

            // Get all rows from the details table
            const detailRows = detailsTable.querySelectorAll('tr');
            detailRows.forEach(row => {
                const th = row.querySelector('th');
                const td = row.querySelector('td');
                if (th && td) {
                    // Remove the colon from the value if present
                    const header = th.textContent.trim();
                    const value = td.textContent.trim().replace(/^:\s*/, '');
                    csvContent += `"${header}","${value}"\n`;
                }
            });

            // Add a blank line between sections
            csvContent += "\n";
        }

        // Then add the results table
        const resultsTable = document.querySelector('#resultsTable');
        if (resultsTable) {
            // Add a section header
            csvContent += "Student Results\n";

            // Get headers
            const headers = [];
            const headerCells = resultsTable.querySelectorAll('thead th');
            headerCells.forEach(cell => {
                headers.push('"' + cell.textContent.trim() + '"');
            });
            csvContent += headers.join(',') + '\n';

            // Get rows
            const rows = resultsTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    // Clean up the text content - remove HTML tags and normalize whitespace
                    let cellText = cell.textContent.trim().replace(/"/g, '""');
                    // For email status cells, extract just the status text without the date
                    if (cell.querySelector('.text-success, .text-danger, .text-warning')) {
                        const statusSpan = cell.querySelector('.text-success, .text-danger, .text-warning');
                        cellText = statusSpan.textContent.trim();
                        // If there's a date, add it in a clean format
                        const dateText = cell.textContent.replace(statusSpan.textContent, '').trim();
                        if (dateText) {
                            cellText += ' ' + dateText;
                        }
                    }
                    rowData.push('"' + cellText + '"');
                });
                csvContent += rowData.join(',') + '\n';
            });
        }

        // If we have content to export
        if (csvContent) {
            // Create download link
            const encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', filename + '.csv');
            document.body.appendChild(link);

            // Trigger download
            link.click();
            document.body.removeChild(link);
        } else {
            alert('No assessment data available to export');
        }
    }

    // Function to export student report
    function exportStudentReport() {
        let csvContent = "";
        let filename = "student_report_" + new Date().toISOString().slice(0, 10);

        // Get the student information table
        const studentInfoTable = document.querySelector('.card-body .table-striped:not(#studentModulesTable)');
        if (studentInfoTable) {
            // Add a section header
            csvContent += "Student Information\n";

            // Get all rows from the student info table
            const infoRows = studentInfoTable.querySelectorAll('tbody tr');
            infoRows.forEach(row => {
                const th = row.querySelector('th');
                const td = row.querySelector('td');
                if (th && td) {
                    // Remove the colon from the header if present
                    const header = th.textContent.trim().replace(':', '');
                    const value = td.textContent.trim();
                    csvContent += `"${header}","${value}"\n`;
                }
            });

            // Add a blank line between sections
            csvContent += "\n";

            // Try to get student name for filename
            const studentNameCell = studentInfoTable.querySelector('tbody tr:nth-child(2) td');
            if (studentNameCell) {
                const studentName = studentNameCell.textContent.trim().replace(/\s+/g, '_');
                filename = `student_${studentName}_report_${new Date().toISOString().slice(0, 10)}`;
            }
        }

        // Get the modules table
        const modulesTable = document.querySelector('#studentModulesTable');
        if (modulesTable) {
            // Get headers
            const headers = [];
            const headerCells = modulesTable.querySelectorAll('thead th');
            headerCells.forEach(cell => {
                headers.push('"' + cell.textContent.trim() + '"');
            });

            // Get rows
            const rows = [];
            const tableRows = modulesTable.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                const rowData = [];
                const cells = row.querySelectorAll('td');
                cells.forEach(cell => {
                    // Clean up the text content - remove HTML tags and normalize whitespace
                    let cellText = cell.textContent.trim().replace(/"/g, '""');
                    // For email status cells, extract just the status text without the date
                    if (cell.querySelector('.text-success, .text-danger, .text-warning')) {
                        const statusSpan = cell.querySelector('.text-success, .text-danger, .text-warning');
                        cellText = statusSpan.textContent.trim();
                        // If there's a date, add it in a clean format
                        const dateText = cell.textContent.replace(statusSpan.textContent, '').trim();
                        if (dateText) {
                            cellText += ' ' + dateText;
                        }
                    }
                    rowData.push('"' + cellText + '"');
                });
                rows.push(rowData.join(','));
            });

            // Only add the module results section if we have data
            if (rows.length > 0) {
                // Add headers and rows
                csvContent += headers.join(',') + '\n';
                csvContent += rows.join('\n');
            } else {
                // If no rows, add a note
                csvContent += "No module results found for this student.\n";
            }
        } else {
            // Check if there's a message about no module results
            const noModulesMessage = document.querySelector('.alert.alert-info');
            if (noModulesMessage) {
                csvContent += `"${noModulesMessage.textContent.trim()}"\n`;
            }
        }

        // If we have content to export
        if (csvContent) {
            // Create download link
            const encodedUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', filename + '.csv');
            document.body.appendChild(link);

            // Trigger download
            link.click();
            document.body.removeChild(link);
        } else {
            alert('No student data available to export');
        }
    }

    // Helper function to get program information for student report
    function getProgramInfoForStudent() {
        // Get the selected values
        const programName = $('#programme option:selected').text();
        const batchName = $('#batch option:selected').text();
        const yearName = $('#year_display').val();
        const semesterName = $('#semester_display').val();

        if (programName && programName !== 'Select Programme') {
            let info = "PROGRAM DETAILS\n";
            info += `"Programme","${programName}"\n`;

            if (batchName && batchName !== 'Select Batch') {
                info += `"Batch","${batchName}"\n`;
            }

            if (yearName) {
                info += `"Year","${yearName}"\n`;
            }

            if (semesterName) {
                info += `"Semester","${semesterName}"\n`;
            }

            return info;
        }

        return "";
    }
</script>