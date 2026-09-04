<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}
// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 


$programmesRes = $conn->query("SELECT DISTINCT programme FROM induction_students WHERE email_sent = 0 ORDER BY programme ASC");


?>


<!DOCTYPE html>
<html>

<head>
    <title>Induction Students</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        th {
            background: #f2f2f2;
        }

        button {
            padding: 8px 14px;
            margin-top: 10px;
        }
    </style>
</head>


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
                    <h4 class="h4 mb-0 text-gray-800">Uplaod Student Data</h4>
                </div>



                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Upload Induction Students (CSV)</h6>
                                </div>
                                <div class="card-body">
                                    <form action="import_induction_students.php" method="post" enctype="multipart/form-data" class="mb-4">
                                        <input type="file" name="csv_file" accept=".csv" required>
                                        <button type="submit" class="btn btn-success">Upload CSV</button>
                                        <a href="Excel/induction_template.csv" download class="btn btn-outline-success ms-2" title="Download Excel Template">
                                            <i class="fas fa-file-excel">&nbsp; Download CSV and USE</i>
                                        </a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


                <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
                <form id="sendEmailsForm" class="mb-3 mt-4">
                    <div class="row justify-content-end">
                        <div class="col-md-7 d-flex flex-column align-items-end ms-auto">
                            <label for="programmeFilter" class="form-label w-100 text-end">Filter by Programme:</label>
                            <select name="programme" id="programmeFilter" class="form-select mb-2 select2-programme-filter" style="width: 50%;">
                                <option value="">-- All Programmes --</option>
                                <?php while ($p = $programmesRes->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($p['programme']) ?>"><?= htmlspecialchars($p['programme']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="d-flex justify-content-end w-100">
                                <button type="submit" id="sendEmailsBtn" class="btn btn-primary">📧 Send Emails</button>
                            </div>
                        </div>
                    </div>
                </form>

                <script>
                    $(document).ready(function() {
                        $('#programmeFilter').select2({
                            placeholder: "-- All Programmes --",
                            allowClear: true,
                            width: 'resolve' // or use 'style: width: 50%' to keep the same
                        });
                    });
                </script>


                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Induction Students List (Email Not Send List)</h6>
                            </div>

                            <div class="card-body">
                                <!-- Students Table -->
                                <div id="studentsTableContainer">
                                    <!-- Table will be loaded via AJAX -->
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <?php
                // --- Email Sent Table + Export Button ---

                $query = "
                    SELECT 
                       *
                    FROM induction_students
                    WHERE email_sent = 1
                ";
                $result = $conn->query($query);
                ?>

                <div class="card mb-5">
                    <div class="card-header d-flex align-items-center justify-content-between" style="height: 60px;">
                        <div class="d-flex align-items-center">
                            <span class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-check-circle"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0 me-2">Induction Students List (<b>Email Sent</b>)</h6>
                        </div>
                        <button id="exportSentTable" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                    </div>
                    <div class="card-body">
                        <div style="overflow-x:auto;">
                            <table id="sentStudentsTable" class="table table-bordered table-striped" style="font-size:14px; min-width:900px; width:100%;">
                                <thead class="table-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Ref No</th>
                                        <th>Name</th>
                                        <th>NIC</th>
                                        <th>Email</th>
                                        <th>Programme</th>
                                        <th>Contact</th>
                                        <th>Gender</th>
                                        <th>Payment</th>
                                        <th>Paid</th>
                                        <th>Email Sent</th>
                                        <th>Email Sent Time</th>
                                        <th>Email Sent By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result && $result->num_rows > 0):
                                        $i = 1;
                                        while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $i++ ?></td>
                                                <td><?= htmlspecialchars($row['ref_no']) ?></td>
                                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                                <td><?= htmlspecialchars($row['nic']) ?></td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td><?= htmlspecialchars($row['programme']) ?></td>
                                                <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                                <td><?= htmlspecialchars($row['fees']) ?></td>
                                                <td><?= htmlspecialchars($row['paid']) ?></td>
                                                <td><?= $row['email_sent'] ? '✅ Yes' : '❌ No' ?></td>
                                                <td><?= htmlspecialchars($row['email_sent_time']) ?></td>
                                                <td><?= htmlspecialchars($row['email_sent_by']) ?></td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="12" class="text-center">No students with email sent.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/tableexport.jquery.plugin/tableExport.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
                <script>
                    $(document).ready(function() {
                        let table = $('#sentStudentsTable').DataTable({
                            "order": [
                                [0, "asc"]
                            ],
                            "responsive": true,
                            "autoWidth": false,
                            "pageLength": 200
                        });

                        // Export Table to Excel
                        $('#exportSentTable').on('click', function() {
                            // Only export visible (filtered) table
                            // TableExport looks for a <table>, not for DataTables wrapper
                            // We'll use SheetJS for more control
                            var wb = XLSX.utils.table_to_book(document.getElementById('sentStudentsTable'), {
                                sheet: "Email Sent"
                            });
                            XLSX.writeFile(wb, 'induction_students_email_sent.xlsx');
                        });
                    });
                </script>

                <!-- Modal -->
                <div class="modal fade" id="sendingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center">
                                <h5>Sending emails... Please wait</h5>
                                <p id="progressText">Sent 0 / 0</p>
                                <div class="spinner-border text-primary mt-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {

            // Function to load table
            function loadTable(programme = '') {
                $.post('load_induction_students.php', {
                    programme: programme
                }, function(data) {
                    $('#studentsTableContainer').html(data);
                });
            }

            // Initial load - show all students
            loadTable();

            // Reload table when filter changes
            $('#programmeFilter').on('change', function() {
                var programme = $(this).val();
                loadTable(programme);
            });

            // Send Emails with progress
            $('#sendEmailsForm').on('submit', function(e) {
                e.preventDefault();
                var programme = $('#programmeFilter').val();
                $('#sendEmailsBtn').prop('disabled', true);

                var sendingModal = new bootstrap.Modal(document.getElementById('sendingModal'));
                sendingModal.show();

                // Get total students to send
                $.post('send_induction_emails.php', {
                    programme: programme,
                    get_total: 1
                }, function(resp) {
                    let total = resp.total || 0;
                    if (total === 0) {
                        alert("No emails to send for this filter");
                        sendingModal.hide();
                        $('#sendEmailsBtn').prop('disabled', false);
                        return;
                    }

                    let sentCount = 0;

                    function sendNext() {
                        $.post('send_induction_emails.php', {
                            programme: programme,
                            send_one: 1
                        }, function(r) {
                            sentCount++;
                            $('#progressText').text(`Sent ${sentCount} / ${total}`);
                            if (sentCount < total) {
                                sendNext();
                            } else {
                                setTimeout(function() {
                                    sendingModal.hide();
                                    // Refresh table after sending
                                    loadTable(programme);
                                    $('#sendEmailsBtn').prop('disabled', false);
                                }, 1000);
                            }
                        }, 'json').fail(function() {
                            alert("Error sending emails");
                            sendingModal.hide();
                            $('#sendEmailsBtn').prop('disabled', false);
                        });
                    }

                    sendNext();

                }, 'json');
            });

        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />


</div>
</body>

</html>