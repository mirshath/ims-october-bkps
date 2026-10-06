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

<style>
    /* ===== Induction scan (DB) — glass pro redesign: navy + red ===== */
    .ia-page {
        --ia-navy: #182a51;
        --ia-navy-2: #0f2e71;
        --ia-navy-3: #03143d;
        --ia-red: #a70000;
        --ia-soft: #7f9df0;
        --ia-ink: #0f1b3a;
        --ia-text: #4a5878;
        --ia-mute: #7d8db0;
        --ia-line: rgba(24, 42, 81, .10);
        --ia-glass: rgba(255, 255, 255, .62);
        --ia-shadow: 0 8px 28px rgba(24, 42, 81, .10), inset 0 1px 0 rgba(255, 255, 255, .85);
        padding-left: 32px;
        padding-right: 32px;
        font-family: 'Roboto', Arial, sans-serif;
        color: var(--ia-ink);
    }

    /* page header */
    .ia-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        margin: 0 0 22px;
        flex-wrap: wrap
    }

    .ia-head h1 {
        font-size: 1.55rem;
        font-weight: 700;
        letter-spacing: -.02em;
        margin: 0 0 4px;
        color: var(--ia-ink)
    }

    .ia-head p {
        margin: 0;
        color: var(--ia-mute);
        font-size: .9rem
    }

    .ia-head .ia-bar {
        display: inline-block;
        width: 4px;
        height: 22px;
        border-radius: 4px;
        background: var(--ia-red);
        margin-right: 10px;
        vertical-align: -4px
    }

    /* glass surfaces */
    .ia-page .card {
        background: var(--ia-glass) !important;
        -webkit-backdrop-filter: blur(16px) saturate(160%);
        backdrop-filter: blur(16px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, .75) !important;
        border-radius: 18px !important;
        box-shadow: var(--ia-shadow) !important;
    }

    .ia-page .card-header {
        background: transparent !important;
        border-bottom: 1px solid var(--ia-line) !important;
        height: auto !important;
        padding: 14px 20px
    }

    .ia-page .card-header .bg-primary {
        background: var(--ia-navy) !important;
        border-radius: 10px !important
    }

    .ia-page .card-header h6 {
        font-size: .98rem
    }

    /* filter */
    .ia-page .form-label {
        color: var(--ia-text);
        font-size: .85rem
    }

    .ia-page .form-select,
    .ia-page .form-control {
        border: 1px solid var(--ia-line);
        border-radius: 12px;
        background-color: rgba(255, 255, 255, .85)
    }

    .ia-page .form-select-lg {
        font-size: .95rem;
        padding: .6rem .9rem
    }

    .ia-page .form-select:focus,
    .ia-page .form-control:focus {
        border-color: var(--ia-soft);
        box-shadow: 0 0 0 4px rgba(127, 157, 240, .25)
    }

    .ia-page .select2-container--default .select2-selection--single {
        height: 44px;
        border: 1px solid var(--ia-line);
        border-radius: 12px;
        background: rgba(255, 255, 255, .85)
    }

    .ia-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 14px;
        color: var(--ia-ink)
    }

    .ia-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px
    }

    .ia-page .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--ia-soft);
        box-shadow: 0 0 0 4px rgba(127, 157, 240, .25)
    }

    /* stat cards: replace rainbow gradients with calm glass + one accent each */
    #statsContainer .card {
        background: var(--ia-glass) !important;
        position: relative;
        overflow: hidden
    }

    #statsContainer .card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--st, #182a51)
    }

    #statsContainer .card-body,
    #statsContainer .card-body.text-white {
        color: var(--ia-ink) !important;
        text-align: left !important;
        padding: 18px 20px 18px 24px
    }

    #statsContainer .d-flex {
        justify-content: flex-start !important
    }

    #statsContainer .fa-2x {
        font-size: 1rem;
        width: 40px;
        height: 40px;
        line-height: 40px;
        text-align: center;
        border-radius: 12px;
        margin: 0 !important;
        background: var(--st-bg, #e8edf8);
        color: var(--st, #182a51)
    }

    #statsContainer h3 {
        font-family: 'IBM Plex Mono', ui-monospace, monospace;
        font-size: 1.9rem;
        font-weight: 700;
        letter-spacing: -.02em;
        color: var(--ia-ink);
        margin: 6px 0 2px !important
    }

    #statsContainer p {
        color: var(--ia-text);
        font-size: .85rem !important
    }

    #statsContainer>div:nth-child(1) {
        --st: #182a51;
        --st-bg: #e8edf8
    }

    #statsContainer>div:nth-child(2) {
        --st: #15803d;
        --st-bg: #dcfce7
    }

    #statsContainer>div:nth-child(3) {
        --st: #1d4ed8;
        --st-bg: #dbeafe
    }

    #statsContainer>div:nth-child(4) {
        --st: #b45309;
        --st-bg: #fef3c7
    }

    /* scan card = the main action */
    .ia-page .card-header.text-white {
        background: linear-gradient(135deg, var(--ia-navy), var(--ia-navy-2) 60%, var(--ia-navy-3)) !important;
        border-bottom: 0 !important;
        color: #fff !important;
        border-radius: 18px 18px 0 0 !important;
        padding: 16px 22px;
    }

    .ia-page .card-header.text-white .material-icons {
        color: #c6d0e3
    }

    .ia-page .card-header.text-white .h5 {
        font-size: 1.05rem;
        letter-spacing: -.01em
    }

    #scanInput {
        height: 58px;
        font-size: 1.15rem;
        letter-spacing: .04em;
        border: 2px solid var(--ia-line);
        border-radius: 14px;
        background: #fff
    }

    #scanInput:focus {
        border-color: var(--ia-red);
        box-shadow: 0 0 0 5px rgba(167, 0, 0, .12)
    }

    .ia-page kbd {
        background: var(--ia-navy);
        color: #fff;
        border-radius: 6px;
        padding: 2px 7px;
        font-size: .78rem
    }

    /* table */
    .ia-page table.dataTable,
    .ia-page #studentsTable {
        border-collapse: separate !important;
        border-spacing: 0;
        border: 0 !important
    }

    .ia-page #studentsTable thead th {
        background: rgba(24, 42, 81, .06) !important;
        color: var(--ia-text);
        font-size: .78rem;
        font-weight: 600;
        border: 0 !important;
        border-bottom: 1px solid var(--ia-line) !important;
        padding: 12px 14px;
        white-space: nowrap
    }

    .ia-page #studentsTable tbody td {
        border: 0 !important;
        border-bottom: 1px solid var(--ia-line) !important;
        padding: 11px 14px;
        color: var(--ia-ink);
        background: transparent !important;
        box-shadow: none !important
    }

    .ia-page #studentsTable tbody tr:hover td {
        background: rgba(127, 157, 240, .12) !important
    }

    .ia-page .dataTables_wrapper .dataTables_filter input,
    .ia-page .dataTables_wrapper .dataTables_length select {
        border: 1px solid var(--ia-line);
        border-radius: 10px;
        padding: 6px 10px;
        background: rgba(255, 255, 255, .85)
    }

    .ia-page .dataTables_wrapper .dataTables_info {
        color: var(--ia-mute);
        font-size: .82rem
    }

    .ia-page .page-item .page-link {
        border: 0;
        border-radius: 9px;
        margin: 0 2px;
        color: var(--ia-text);
        background: transparent
    }

    .ia-page .page-item.active .page-link {
        background: var(--ia-navy);
        color: #fff
    }

    .ia-page .page-item .page-link:hover {
        background: rgba(24, 42, 81, .08)
    }

    /* status badges: soft pills */
    .badge {
        font-weight: 600;
        border-radius: 999px;
        padding: .4em .75em
    }

    .badge.bg-success {
        background: #dcfce7 !important;
        color: #15803d
    }

    .badge.bg-danger {
        background: #fee2e2 !important;
        color: #b91c1c
    }

    .badge.bg-secondary {
        background: #eaeef6 !important;
        color: #5b6785
    }

    /* result modal */
    #resultModal .modal-content {
        background: rgba(255, 255, 255, .88);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, .8);
        border-radius: 20px;
        box-shadow: 0 24px 60px rgba(3, 20, 61, .30);
        overflow: hidden
    }

    #resultModal .modal-header {
        border-bottom: 1px solid var(--ia-line) !important;
        padding: 16px 22px
    }

    #resultModal .modal-title {
        color: var(--ia-ink) !important;
        font-weight: 700
    }

    #resultModal .modal-body {
        padding: 8px 24px 24px
    }

    #resultModal .card.bg-light {
        background: rgba(24, 42, 81, .05) !important;
        box-shadow: none !important;
        border: 1px solid var(--ia-line) !important;
        border-radius: 14px !important
    }

    #resultModal .text-success {
        color: #15803d !important
    }

    #resultModal .text-danger {
        color: var(--ia-red) !important
    }

    #resultModal .text-muted {
        color: var(--ia-mute) !important
    }

    .ia-page .form-control:focus-visible,
    .ia-page .page-link:focus-visible {
        outline: 2px solid var(--ia-soft);
        outline-offset: 2px
    }

    @media (max-width:768px) {
        .ia-page {
            padding-left: 16px;
            padding-right: 16px
        }

        .ia-head h1 {
            font-size: 1.3rem
        }
    }
</style>

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="container-fluid py-4 ia-page">
                <!-- Page header -->
                <div class="ia-head">
                    <div>
                        <h1><span class="ia-bar"></span>Induction attendance</h1>
                        <p>Scan a student's NIC to mark attendance and track pack collection.</p>
                    </div>
                </div>

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
                                        $(document).ready(function() {
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
                                style="gap:14px;">
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
                                                <th>Programme - Batch</th>
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
        $(document).ready(function() {
            // Load programs into dropdown
            function loadPrograms() {
                $.ajax({
                    url: 'induction_DB_folder/fetch_induction_db_programs.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(programs) {
                        let programFilter = $('#programFilter');
                        programFilter.find('option:not(:first)').remove();
                        programs.forEach(function(program) {
                            programFilter.append('<option value="' + program + '">' + program + '</option>');
                        });
                    },
                    error: function() {
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
                    success: function(stats) {
                        $('#totalStudents').text(stats.totalStudents || 0);
                        $('#attendedStudents').text(stats.attendedStudents || 0);
                        $('#packIssuedCount').text(stats.packCollected || 0);
                        $('#packNotIssuedCount').text((stats.attendedStudents || 0) - (stats.packCollected || 0));
                    },
                    error: function() {
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
            $('#programFilter').on('change', function() {
                const selectedProgram = $(this).val();
                loadStatistics(selectedProgram);
                $('#studentsTable').DataTable().ajax.url(getTableUrl(selectedProgram)).load();
            });

            $('#scanInput').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    let nic = $(this).val().trim();
                    if (nic === '') return;

                    $.ajax({
                        url: 'induction_scan_db_action.php',
                        type: 'POST',
                        data: {
                            nic: nic
                        },
                        dataType: 'json',
                        success: function(response) {
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
                                                        <td class="fw-bold text-muted">NIC:</td>
                                                        <td class="fw-semibold">${student.nic}</td>
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
                                let packBadge = student.pack_collected == 1 ?
                                    '<span class="badge bg-success">Yes</span>' :
                                    '<span class="badge bg-secondary">No</span>';

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
                                                        <td class="fw-bold text-muted">NIC:</td>
                                                        <td class="fw-semibold">${student.nic}</td>
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
                                                    <tr style="font-size:20px;">
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
                                                ${response.fees_unpaid ? `
                                                <div class="text-center mt-3">
                                                    <button type="button" class="btn btn-warning btn-sm attend-manually-btn" data-id="${student.id}">
                                                        <i class="fas fa-user-check me-1"></i>Attend Manually
                                                    </button>
                                                </div>
                                                ` : ''}
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
                        error: function(xhr) {
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
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1;
                        }
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'nic',
                        defaultContent: '-'
                    },
                    {
                        data: 'program_batch',
                        defaultContent: '-'
                    },
                    {
                        data: 'email'
                    },
                    {
                        data: 'contact',
                        defaultContent: '-'
                    },
                    {
                        data: 'fees_paid',
                        render: function(data) {
                            if (data === 'paid') return '<span class="badge bg-success">Paid</span>';
                            if (data === 'unpaid') return '<span class="badge bg-danger">Unpaid</span>';
                            return '-';
                        }
                    },
                    {
                        data: 'pack_collected',
                        render: function(data) {
                            return data == 1 ?
                                '<span class="badge bg-success">Yes</span>' :
                                '<span class="badge bg-secondary">No</span>';
                        }
                    },
                    {
                        data: 'attended_time',
                        defaultContent: '-'
                    }
                ]
            });



            // Attend Manually button (shown when fees are unpaid)
            $(document).on('click', '.attend-manually-btn', function() {
                const btn = $(this);
                const studentId = btn.data('id');

                if (!studentId) return;

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Please wait...');

                $.ajax({
                    url: 'induction_manual_attend_db.php',
                    type: 'POST',
                    data: {
                        id: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let student = response.student;
                            $('#modalBody').html(`
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
                                                    <td class="fw-bold text-muted">NIC:</td>
                                                    <td class="fw-semibold">${student.nic}</td>
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
                                                    <td class="fw-bold text-muted">Attended:</td>
                                                    <td><span class="badge bg-success">${student.attended}</span></td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-bold text-muted">Attended Time:</td>
                                                    <td class="fw-semibold">${student.attended_time}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `);

                            const selectedProgram = $('#programFilter').val();
                            loadStatistics(selectedProgram);
                            $('#studentsTable').DataTable().ajax.reload(null, false);
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-user-check me-1"></i>Attend Manually');
                            $('#modalBody').prepend(`<div class="alert alert-danger py-2 mb-2">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        btn.prop('disabled', false).html('<i class="fas fa-user-check me-1"></i>Attend Manually');
                        $('#modalBody').prepend('<div class="alert alert-danger py-2 mb-2">Error marking attendance. Please try again.</div>');
                    }
                });
            });

            // Refocus input on modal close
            $('#resultModal').on('hidden.bs.modal', function() {
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