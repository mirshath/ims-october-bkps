<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// Fetch email sending logs with joined names, filtered by rolling 10-day date range
$end_date   = date('Y-m-d H:i:s'); // now
$start_date = date('Y-m-d H:i:s', strtotime('-20 days')); // 10 days before now

$sql = "SELECT 
            esl.id,
            esl.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            esl.program_id,
            p.program_name,
            esl.batch_id,
            b.batch_name,
            esl.module_id,
            m.module_name,
            esl.main_component_id,
            ac.as_main_component_name AS main_component_name,
            esl.sub_component_id,
            sac.sub_component_name AS sub_component_name,
            ap.student_registration_id,
            esl.email_sent,
            esl.sent_date,
            esl.sent_by,
            esl.status,
            esl.error_message,
            esl.emailed_result,
            grp.group_latest_date
        FROM email_sending_log esl
        LEFT JOIN students s ON esl.student_id = s.student_code
        LEFT JOIN program_table p ON esl.program_id = p.program_code
        LEFT JOIN batch_table b ON esl.batch_id = b.id
        LEFT JOIN modules m ON esl.module_id = m.id
        LEFT JOIN assignment_components ac ON esl.main_component_id = ac.id
        LEFT JOIN sub_assign_components sac ON esl.sub_component_id = sac.id
        LEFT JOIN allocate_programme ap 
            ON esl.student_id = ap.student_code 
           AND esl.program_id = ap.programme_code 
           AND esl.batch_id = ap.batch_id
        INNER JOIN (
            SELECT program_id, batch_id, MAX(sent_date) AS group_latest_date
            FROM email_sending_log
            WHERE sent_date BETWEEN ? AND ?
            GROUP BY program_id, batch_id
        ) grp ON esl.program_id = grp.program_id AND esl.batch_id = grp.batch_id
        WHERE esl.sent_date BETWEEN ? AND ?
        ORDER BY grp.group_latest_date DESC, esl.sent_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
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
                    <h4 class="h4 mb-0 text-gray-800">Update Student Status 2</h4>
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
                                <h6 class="mb-0 me-2">Student Update Data</h6>
                            </div>
                            <div class="card-body">
                                <!-- Students Table -->
                                <div class="table-responsive mb-4">
                                    <table id="studentsTable" class="table table-striped table-bordered" style="width: 100%; font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Student Name</th>
                                                <th>Registration ID</th>
                                                <th>Program Name</th>
                                                <th>Batch Name</th>
                                                <th>Module Name</th>
                                                <th>Main Component</th>
                                                <th>Sub Component</th>
                                                <th>Email Sent</th>
                                                <th>Sent Date</th>
                                                <th>Sent By</th>
                                                <th>Status</th>
                                                <th>Error Message</th>
                                                <th>Emailed Result</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['id']) ?></td>
                                                        <td><?= htmlspecialchars(trim($row['student_name']) !== '' ? trim($row['student_name']) : '-') ?></td>
                                                        <td><?= htmlspecialchars($row['student_registration_id'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['program_name'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['batch_name'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['module_name'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['main_component_name'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['sub_component_name'] ?? '-') ?></td>
                                                        <td><?= $row['email_sent'] ? 'Yes' : 'No' ?></td>
                                                        <td><?= htmlspecialchars($row['sent_date'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['sent_by'] ?? '-') ?></td>
                                                        <td>
                                                            <?php if ($row['status'] === 'sent'): ?>
                                                                <span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span>
                                                            <?php elseif ($row['status'] === 'failed'): ?>
                                                                <span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['error_message'] ?? '-') ?></td>
                                                        <td><?= htmlspecialchars($row['emailed_result'] ?? '-') ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="14" class="text-center">No records found</td>
                                                </tr>
                                            <?php endif; ?>
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
    <script src="https://cdn.datatables.net/rowgroup/1.3.1/js/dataTables.rowGroup.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/rowgroup/1.3.1/css/rowGroup.dataTables.min.css" rel="stylesheet" />

    <style>
        table.dataTable tr.dtrg-group td {
            background-color: #2c3e50;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
        }
    </style>

    <script>
        $(document).ready(function() {
            $('#studentsTable').DataTable({
                "order": [], // keep server-side order (group's latest date DESC, then sent_date DESC within group)
                rowGroup: {
                    dataSrc: function(row) {
                        // Combine Program Name (col 3) + Batch Name (col 4) into one group label
                        return row[3] + ' - ' + row[4];
                    }
                }
            });
        });
    </script>

</div>
</body>

</html>