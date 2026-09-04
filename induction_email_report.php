<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
// require_once 'PermissionChecking.php';
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
                            <i class="fas fa-envelope me-2"></i>Induction Email Report
                        </h2>
                        <p class="text-muted mb-0">Complete report of induction emails sent (success and failure)</p>
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
                                    <i class="fas fa-check-circle me-1"></i>Email Status
                                </label>
                                <select id="statusFilter" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="sent">Sent</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold text-secondary">
                                    <i class="fas fa-calendar me-1"></i>Date Range
                                </label>
                                <input type="date" id="dateFrom" class="form-control form-control-sm mb-1">
                                <input type="date" id="dateTo" class="form-control form-control-sm">
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

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-left-primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Emails
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEmails">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-envelope fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-left-success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Successfully Sent
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="sentEmails">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm border-left-danger">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Failed
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="failedEmails">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                    </div>
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
                                            <i class="fas fa-table me-2"></i>Email Log
                                        </h5>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-dark" id="recordCount">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div id="exportButtons"></div>
                                </div>
                                <div class="table-responsive">
                                    <table id="emailsTable"
                                        class="table table-striped table-bordered table-hover w-100"
                                        style="font-size:13px;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Student Code</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Programme</th>
                                                <th>Batch</th>
                                                <th>Status</th>
                                                <th>Error Message</th>
                                                <th>Sent By</th>
                                                <th>Sent At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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
        let table = $('#emailsTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: 'induction_DB_folder/fetch_induction_email_log.php',
                type: 'GET',
                data: function (d) {
                    d.programme = $('#programmeFilter').val();
                    d.status = $('#statusFilter').val();
                    d.dateFrom = $('#dateFrom').val();
                    d.dateTo = $('#dateTo').val();
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
                [9, 'desc']
            ],
            columnDefs: [
                {
                    targets: 0,
                    width: "50px",
                    orderable: true
                },
                {
                    targets: 6,
                    render: function (data, type, row) {
                        if (data === 'sent') {
                            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Sent</span>';
                        } else if (data === 'failed') {
                            return '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Failed</span>';
                        } else {
                            return '<span class="badge bg-secondary">Unknown</span>';
                        }
                    }
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
            buttons: [
                {
                    extend: 'copyHtml5',
                    text: '<i class="fas fa-copy"></i> Copy',
                    className: 'btn btn-secondary btn-sm',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success btn-sm',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger btn-sm',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print',
                    className: 'btn btn-primary btn-sm',
                    exportOptions: { columns: ':visible' }
                }
            ],
            drawCallback: function (settings) {
                let api = this.api();
                let pageInfo = api.page.info();
                $('#recordCount').text(
                    'Showing ' + (pageInfo.start + 1) + ' to ' +
                    (pageInfo.end) + ' of ' + pageInfo.recordsTotal + ' entries'
                );
                updateSummary(api);
            },
            initComplete: function (settings, json) {
                if (table.buttons) {
                    table.buttons().container().appendTo('#exportButtons');
                }
            }
        });

        function updateSummary(api) {
            let data = api.rows({ search: 'applied' }).data().toArray();
            let total = data.length;
            let sent = 0;
            let failed = 0;
            data.forEach(row => {
                if (row[6] === 'sent') sent++;
                else if (row[6] === 'failed') failed++;
            });
            $('#totalEmails').text(total);
            $('#sentEmails').text(sent);
            $('#failedEmails').text(failed);
        }

        loadProgrammes();

        function loadProgrammes() {
            $.ajax({
                url: 'induction_DB_folder/fetch_induction_email_programmes.php',
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

        $('#programmeFilter, #statusFilter, #dateFrom, #dateTo').on('change', function () {
            table.ajax.reload();
        });

        $('#clearFilters').on('click', function () {
            $('#programmeFilter').val('');
            $('#statusFilter').val('');
            $('#dateFrom').val('');
            $('#dateTo').val('');
            table.ajax.reload();
        });
    });
</script>

<?php
include("includes/footer.php");
$conn->close();
?>
