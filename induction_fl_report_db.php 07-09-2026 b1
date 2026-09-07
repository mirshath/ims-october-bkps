<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 

// Set charset for proper display
mysqli_set_charset($conn, "utf8mb4");
?>

<!-- Bootstrap + Google Fonts + Material Icons -->
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css" rel="stylesheet" />

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="container-fluid py-4">
                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h2 class="mb-2">
                            <i class="fas fa-users me-2"></i>Induction DB Student Report
                        </h2>
                        <p class="text-muted mb-0">Complete list of all students in the induction_emails_sent table
                            (From Database)</p>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-graduation-cap me-1"></i>Programme & Batch
                                </label>
                                <select id="programmeFilter" class="form-select form-select-sm">
                                    <option value="">All Programme - Batches</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-money-bill-wave me-1"></i>Fees Status
                                </label>
                                <select id="feesFilter" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-check-circle me-1"></i>Attended
                                </label>
                                <select id="attendedFilter" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-box me-1"></i>Pack Collected
                                </label>
                                <select id="packCollectedFilter" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times me-1"></i>Clear All Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">
                                            <i class="fas fa-table me-2"></i>Induction DB Students Data
                                        </h5>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-dark" id="recordCount">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <!-- Export buttons will appear here -->
                                    <div id="exportButtons"></div>
                                </div>
                                <div class="table-responsive">
                                    <table id="studentsTable"
                                        class="table table-striped table-bordered table-hover w-100"
                                        style="font-size:13px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Attended</th>
                                                <th>Attended Time</th>
                                                <th>Pack Collected</th>
                                                <th>Ref No</th>
                                                <th>Full Name</th>
                                                <th>Programme & Batch</th>
                                                <th>NIC</th>
                                                <th>Contact No</th>
                                                <th>Landline</th>
                                                <th>Fees/Paid</th>
                                                <th>Qualification</th>
                                                <th>Institute</th>
                                                <th>Gender</th>
                                                <th>Date of Birth</th>
                                                <th>Nationality</th>
                                                <th>Email</th>
                                                <th>BMS Email</th>
                                                <th>Address 1</th>
                                                <th>Address 2</th>
                                                <th>Email Sent</th>
                                                <th>Email Sent Time</th>
                                                <th>Email Sent By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded via DataTables AJAX -->
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
</div>

<!-- JS and jQuery Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function () {
        let table = $('#studentsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'induction_DB_folder/fetch_all_induction_db_students.php',
                type: 'GET',
                data: function (d) {
                    d.programme = $('#programmeFilter').val();
                    d.fees = $('#feesFilter').val();
                    d.attended = $('#attendedFilter').val();
                    d.pack_collected = $('#packCollectedFilter').val();
                }
            },
            pageLength: 150,
            lengthMenu: [
                [25, 50, 100, 200, -1],
                [25, 50, 100, 200, "All"]
            ],
            lengthChange: true,
            ordering: true,
            searching: true,
            responsive: true,
            scrollX: true,
            order: [
                [0, 'asc']
            ],
            columnDefs: [{
                targets: 0,
                width: "50px",
                orderable: true
            },
            {
                targets: '_all',
                orderable: true
            }
            ],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: "<'row'<'col-md-6'l><'col-md-6'f>>" +
                "<'row'<'col-12'B>>" +
                "<'row'<'col-12'tr>>" +
                "<'row'<'col-md-5'i><'col-md-7'p>>",
            buttons: [{
                extend: 'copyHtml5',
                text: '<i class="fas fa-copy"></i> Copy',
                className: 'btn btn-secondary btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'csvHtml5',
                text: '<i class="fas fa-file-csv"></i> CSV',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Print',
                className: 'btn btn-primary btn-sm',
                exportOptions: {
                    columns: ':visible'
                }
            }
            ],
            drawCallback: function (settings) {
                // Update record count
                let api = this.api();
                let pageInfo = api.page.info();
                $('#recordCount').text(
                    'Showing ' + (pageInfo.start + 1) + ' to ' +
                    (pageInfo.end) + ' of ' + pageInfo.recordsTotal + ' entries'
                );
            },
            initComplete: function (settings, json) {
                // Move export buttons to the custom div
                if (table.buttons) {
                    table.buttons().container().appendTo('#exportButtons');
                }
            }
        });

        // Load programmes for filter
        loadProgrammes();

        function loadProgrammes() {
            $.ajax({
                url: 'induction_DB_folder/fetch_induction_fl_programmes.php',
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    let select = $('#programmeFilter');
                    if (response && response.length > 0) {
                        response.forEach(function (prog) {
                            select.append(new Option(prog, prog));
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading programmes:', error);
                }
            });
        }

        // Filter change events
        $('#programmeFilter, #feesFilter, #attendedFilter, #packCollectedFilter').on('change', function () {
            table.ajax.reload();
        });

        // Clear filters button
        $('#clearFilters').on('click', function () {
            $('#programmeFilter').val('');
            $('#feesFilter').val('');
            $('#attendedFilter').val('');
            $('#packCollectedFilter').val('');
            table.ajax.reload();
        });
    });
</script>

<?php
include("includes/footer.php");
$conn->close();
?>