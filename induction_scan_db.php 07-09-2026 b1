<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// Permission checking
require_once 'PermissionChecking.php';
?>

<!-- Bootstrap + Google Fonts + Material Icons -->
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="container-fluid py-4">
                <!-- Program Filter -->
                <div class="row mb-3">
                    <div class="col-12 d-flex justify-content-end">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-3">
                                <div class="card-body">
                                    <label for="programFilter" class="form-label fw-semibold mb-2">
                                        <i class="fas fa-filter me-2"></i>Filter by Programme
                                    </label>
                                    <select id="programFilter" class="form-select form-select-lg" style="width: 100%;">
                                        <option value="all">All Programmes</option>
                                    </select>
                                    <script>
                                        $(document).ready(function () {
                                            $('#programFilter').select2({
                                                placeholder: "Select a programme",
                                                allowClear: true,
                                                width: '100%'
                                            });
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4" id="statsContainer">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-0 shadow-sm h-100 rounded-4"
                             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-users fa-2x me-2"></i>
                                </div>
                                <h3 class="mb-1 fw-bold" id="totalStudents">0</h3>
                                <p class="mb-0 small">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-0 shadow-sm h-100 rounded-4"
                             style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-check-circle fa-2x me-2"></i>
                                </div>
                                <h3 class="mb-1 fw-bold" id="attendedStudents">0</h3>
                                <p class="mb-0 small">Attended</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-0 shadow-sm h-100 rounded-4"
                             style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-box-open fa-2x me-2"></i>
                                </div>
                                <h3 class="mb-1 fw-bold" id="packIssuedCount">0</h3>
                                <p class="mb-0 small">Pack Issued</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card border-0 shadow-sm h-100 rounded-4"
                             style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <div class="card-body text-white text-center">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <i class="fas fa-box fa-2x me-2"></i>
                                </div>
                                <h3 class="mb-1 fw-bold" id="packNotIssuedCount">0</h3>
                                <p class="mb-0 small">Pack Not Issued</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Scan Card -->
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-6 col-md-8">
                        <div class="card shadow-lg border-0 rounded-4">
                            <div class="card-header text-white d-flex align-items-center rounded-top-4"
                                 style="gap:14px; background: rgb(4 45 92) !important;">
                                <span class="material-icons fs-2 me-2">qr_code_scanner</span>
                                <span class="h5 fw-bold mb-0">Induction Attendance (From DB)</span>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3 fw-bold text-secondary fs-5">
                                    Scan Here for Attendance
                                </div>
                                <form autocomplete="off" onsubmit="return false;">
                                    <div class="mb-3 mx-auto" style="max-width:340px;">
                                        <input type="text" id="scanInput"
                                               class="form-control form-control-lg text-center rounded-3"
                                               placeholder="Scan or Enter NIC" autofocus>
                                    </div>
                                    <div class="text-muted mb-2">
                                        Scan QR or type NIC and press <kbd>Enter</kbd>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Attendance Status -->
                <div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel"
                     aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content" style="font-family:'Roboto', Arial, sans-serif;">
                            <div class="modal-header border-bottom">
                                <h5 class="modal-title text-primary" id="resultModalLabel">Attendance Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="modalBody">
                                <!-- AJAX result will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Update Card / Table -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white d-flex align-items-center justify-content-between"
                                 style="height: 60px;">
                                <div class="d-flex align-items-center">
                                    <span
                                        class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center me-3"
                                        style="width: 34px; height: 34px; font-size:1.2rem;">
                                        <i class="fas fa-user-edit"></i>
                                    </span>
                                    <h6 class="mb-0 fw-semibold">Attended students</h6>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive mb-4">
                                    <table id="studentsTable"
                                           class="table table-striped table-bordered align-middle mb-0"
                                           style="width:100%;font-size:13px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>NIC</th>
                                                <th>Programme</th>
                                                <th>Email</th>
                                                <th>Contact</th>
                                                <th>Paid / Unpaid</th>
                                                <th>Pack Collected</th>
                                                <th>Attended Time</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS and jQuery Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <script>
        $(document).ready(function () {
            // Load programs into dropdown
            function loadPrograms() {
                $.ajax({
                    url: 'induction_DB_folder/fetch_induction_db_programs.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function (programs) {
                        let programFilter = $('#programFilter');
                        programFilter.find('option:not(:first)').remove();
                        programs.forEach(function (program) {
                            programFilter.append('<option value="' + program + '">' + program + '</option>');
                        });
                    },
                    error: function () {
                        console.error('Failed to load programs');
                    }
                });
            }

            // Function to load statistics
            function loadStatistics(program = 'all') {
                let url = 'induction_DB_folder/fetch_induction_db_stats.php';
                if (program !== 'all') {
                    url += '?program=' + encodeURIComponent(program);
                }

                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    success: function (stats) {
                        $('#totalStudents').text(stats.totalStudents || 0);
                        $('#attendedStudents').text(stats.attendedStudents || 0);
                        $('#packIssuedCount').text(stats.packCollected || 0);
                        $('#packNotIssuedCount').text((stats.attendedStudents || 0) - (stats.packCollected || 0));
                    },
                    error: function () {
                        console.error('Failed to load statistics');
                    }
                });
            }

            // Function to get table URL with program filter
            function getTableUrl(program = 'all') {
                let url = 'induction_DB_folder/fetch_attended_db_students.php';
                if (program !== 'all') {
                    url += '?program=' + encodeURIComponent(program);
                }
                return url;
            }

            // Load programs and statistics on page load
            loadPrograms();
            loadStatistics();

            // Program filter change event
            $('#programFilter').on('change', function () {
                const selectedProgram = $(this).val();
                loadStatistics(selectedProgram);
                $('#studentsTable').DataTable().ajax.url(getTableUrl(selectedProgram)).load();
            });

            $('#scanInput').on('keypress', function (e) {
                if (e.which === 13) { // Enter key
                    let nic = $(this).val().trim();
                    if (nic === '') return;

                    $.ajax({
                        url: 'induction_scan_db_action.php',
                        type: 'POST',
                        data: { nic: nic },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                // Attendance marked successfully
                                let student = response.student;
                                // Determine fees badge
                                let feesBadge = '';
                                if (student.fees_paid === 'paid') {
                                    feesBadge = '<span class="badge bg-success">Paid</span>';
                                } else if (student.fees_paid === 'unpaid') {
                                    feesBadge = '<span class="badge bg-danger">Unpaid</span>';
                                } else {
                                    feesBadge = '<span class="badge bg-secondary">-</span>';
                                }
                                let html = `
                                    <div class="text-center py-3">
                                        <div class="mb-3">
                                            <i class="fas fa-check-circle text-success" style="font-size: 3.5rem;"></i>
                                        </div>
                                        <h5 class="text-success fw-bold mb-2">${response.message}</h5>
                                        <div class="card bg-light border-0 mt-3">
                                            <div class="card-body text-start">
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tr>
                                                        <td class="fw-bold text-muted">Name:</td>
                                                        <td class="fw-semibold">${student.name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Student Code:</td>
                                                        <td class="fw-semibold">${student.student_code}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Programme:</td>
                                                        <td class="fw-semibold">${student.program_name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Batch:</td>
                                                        <td class="fw-semibold">${student.batch_name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Fees Status:</td>
                                                        <td>${feesBadge}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Attended:</td>
                                                        <td><span class="badge bg-success">${student.attended}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Attended Time:</td>
                                                        <td class="fw-semibold">${student.attended_time}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Pack Collected:</td>
                                                        <td><span class="badge bg-success">Yes</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                $('#modalBody').html(html);
                            } else if (response.student) {
                                // Error but with student data (already attended or inactive)
                                let student = response.student;
                                // Determine fees badge
                                let feesBadge = '';
                                if (student.fees_paid === 'paid') {
                                    feesBadge = '<span class="badge bg-success">Paid</span>';
                                } else if (student.fees_paid === 'unpaid') {
                                    feesBadge = '<span class="badge bg-danger">Unpaid</span>';
                                } else {
                                    feesBadge = '<span class="badge bg-secondary">-</span>';
                                }
                                // Determine pack collected badge
                                let packBadge = student.pack_collected == 1 
                                    ? '<span class="badge bg-success">Yes</span>' 
                                    : '<span class="badge bg-secondary">No</span>';
                                    
                                $('#modalBody').html(`
                                    <div class="text-center py-3">
                                        <div class="mb-3">
                                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 3.5rem;"></i>
                                        </div>
                                        <h5 class="text-danger fw-bold mb-2">${response.message}</h5>
                                        <div class="card bg-light border-0 mt-3">
                                            <div class="card-body text-start">
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tr>
                                                        <td class="fw-bold text-muted">Name:</td>
                                                        <td class="fw-semibold">${student.name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Student Code:</td>
                                                        <td class="fw-semibold">${student.student_code}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Programme:</td>
                                                        <td class="fw-semibold">${student.program_name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Batch:</td>
                                                        <td class="fw-semibold">${student.batch_name}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Fees Status:</td>
                                                        <td>${feesBadge}</td>
                                                    </tr>
                                                    ${student.attended ? `
                                                    <tr>
                                                        <td class="fw-bold text-muted">Attended:</td>
                                                        <td><span class="badge bg-success">${student.attended}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Attended Time:</td>
                                                        <td class="fw-semibold">${student.attended_time}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="fw-bold text-muted">Pack Collected:</td>
                                                        <td>${packBadge}</td>
                                                    </tr>
                                                    ` : ''}
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            } else {
                                // Generic error without student data
                                $('#modalBody').html(`
                                    <div class="text-center py-3">
                                        <div class="mb-3">
                                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 3.5rem;"></i>
                                        </div>
                                        <h5 class="text-danger fw-bold">${response.message}</h5>
                                    </div>
                                `);
                            }

                            let modalEl = document.getElementById('resultModal');
                            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                            modal.show();

                            $('#scanInput').val('').focus();

                            const selectedProgram = $('#programFilter').val();
                            loadStatistics(selectedProgram);
                            $('#studentsTable').DataTable().ajax.reload(null, false);
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            $('#modalBody').html('<div class="text-danger text-center fw-semibold py-3">Error processing scan. Please try again.</div>');
                            let modalEl = document.getElementById('resultModal');
                            let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                            modal.show();
                            $('#scanInput').val('').focus();
                        }
                    });
                }
            });

            // Initialize DataTable
            const table = $('#studentsTable').DataTable({
                processing: true,
                ajax: {
                    url: getTableUrl($('#programFilter').val()),
                    type: 'GET',
                    dataSrc: 'students'
                },
                pageLength: 150,
                lengthChange: false,
                ordering: true,
                searching: true,
                responsive: true,
                columns: [
                    {
                        data: null,
                        render: function (data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    { data: 'name' },
                    { data: 'nic', defaultContent: '-' },
                    { data: 'program_name' },
                    { data: 'email' },
                    { data: 'contact', defaultContent: '-' },
                    {
                        data: 'fees_paid',
                        render: function (data) {
                            if (data === 'paid') return '<span class="badge bg-success">Paid</span>';
                            if (data === 'unpaid') return '<span class="badge bg-danger">Unpaid</span>';
                            return '-';
                        }
                    },
                    {
                        data: 'pack_collected',
                        render: function (data) {
                            return data == 1 
                                ? '<span class="badge bg-success">Yes</span>' 
                                : '<span class="badge bg-secondary">No</span>';
                        }
                    },
                    { data: 'attended_time', defaultContent: '-' }
                ]
            });



            // Refocus input on modal close
            $('#resultModal').on('hidden.bs.modal', function () {
                $('#scanInput').focus();
                const selectedProgram = $('#programFilter').val();
                loadStatistics(selectedProgram);
                $('#studentsTable').DataTable().ajax.url(getTableUrl(selectedProgram)).load();
            });

            // Autofocus input on load
            $('#scanInput').focus();
        });
    </script>
</div>
</body>
</html>
