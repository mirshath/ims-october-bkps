<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
// require_once 'PermissionChecking.php';

/* ------------------------------------------------------------------
 * Helper: clean up names that contain extra spaces (common in the data)
 * ------------------------------------------------------------------ */
function cleanText($v)
{
    return htmlspecialchars(preg_replace('/\s+/', ' ', trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------------
 * Filters (POST, same style as studentWiseDetails.php)
 * ------------------------------------------------------------------ */
$sel_program = isset($_POST['programme_code']) && $_POST['programme_code'] !== '' ? (int)$_POST['programme_code'] : 0;
$sel_batch   = isset($_POST['batch_id']) && $_POST['batch_id'] !== '' ? (int)$_POST['batch_id'] : 0;

/* ------------------------------------------------------------------
 * Program list + Batch list (only those that exist in progression table)
 * Batches are grouped by program so the batch dropdown can be filtered
 * on the client side when a program is chosen.
 * ------------------------------------------------------------------ */
$programs = [];
$prog_sql = "SELECT DISTINCT pp.programme_code, p.program_name
             FROM program_progression_table pp
             LEFT JOIN program_table p ON pp.programme_code = p.program_code
             ORDER BY p.program_name ASC";
$prog_res = mysqli_query($conn, $prog_sql);
while ($r = mysqli_fetch_assoc($prog_res)) {
    $programs[] = $r;
}

$batchMap = []; // [programme_code => [ ['id'=>..,'name'=>..], ... ]]
$batch_sql = "SELECT DISTINCT pp.programme_code, pp.batch_id, b.batch_name
              FROM program_progression_table pp
              LEFT JOIN batch_table b ON pp.batch_id = b.id
              ORDER BY b.batch_name ASC";
$batch_res = mysqli_query($conn, $batch_sql);
while ($r = mysqli_fetch_assoc($batch_res)) {
    $batchMap[$r['programme_code']][] = [
        'id'   => (int)$r['batch_id'],
        'name' => $r['batch_name'] ?: ('Batch ID ' . $r['batch_id'])
    ];
}

/* ------------------------------------------------------------------
 * Main report query (prepared statement, filters are optional)
 * ------------------------------------------------------------------ */
$sql = "SELECT pp.*, p.program_name, b.batch_name, u.university_name
        FROM program_progression_table pp
        LEFT JOIN program_table p ON pp.programme_code = p.program_code
        LEFT JOIN batch_table b ON pp.batch_id = b.id
        LEFT JOIN universities u ON pp.university_id = u.id
        WHERE 1=1";
$types  = '';
$params = [];

if ($sel_program > 0) {
    $sql .= " AND pp.programme_code = ?";
    $types .= 'i';
    $params[] = $sel_program;
}
if ($sel_batch > 0) {
    $sql .= " AND pp.batch_id = ?";
    $types .= 'i';
    $params[] = $sel_batch;
}
$sql .= " ORDER BY pp.transfer_date DESC, pp.id DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$report_result = mysqli_stmt_get_result($stmt);
$rows = mysqli_fetch_all($report_result, MYSQLI_ASSOC);
$total_rows = count($rows);
?>

<style>
    .btn-print {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        color: #fff;
        font-weight: 600;
        letter-spacing: .3px;
        padding: 8px 18px;
        border-radius: 50px;
        box-shadow: 0 3px 8px rgba(78, 115, 223, 0.35);
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .btn-print i {
        margin-right: 6px;
    }

    .btn-print:hover,
    .btn-print:focus {
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(34, 74, 190, 0.45);
    }

    .btn-print:active {
        transform: translateY(0);
    }

    #progressionTable th,
    #progressionTable td {
        white-space: nowrap;
        vertical-align: middle;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #progression_report_area,
        #progression_report_area * {
            visibility: visible;
        }

        #progression_report_area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            font-size: 11px;
        }

        .no-print,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_paginate,
        .dataTables_info {
            display: none !important;
        }
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
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Progression Report</h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-4 no-print">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-filter"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Filter Progression Report</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="programme_select">Programme:</label>
                                            <select id="programme_select" name="programme_code" class="form-control">
                                                <option value="">All Programmes</option>
                                                <?php foreach ($programs as $p) { ?>
                                                    <option value="<?= (int)$p['programme_code'] ?>"
                                                        <?= $sel_program === (int)$p['programme_code'] ? 'selected' : '' ?>>
                                                        <?= cleanText($p['program_name'] ?: ('Programme ' . $p['programme_code'])) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="form-group col-md-6">
                                            <label for="batch_select">Batch:</label>
                                            <select id="batch_select" name="batch_id" class="form-control">
                                                <option value="">All Batches</option>
                                                <!-- options are filled by JavaScript based on the selected programme -->
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" name="submit" class="btn btn-primary mt-3">Search</button>
                                    <a href="progressionToReport" class="btn btn-secondary mt-3">Reset</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="p-2 d-flex justify-content-end no-print">
                            <button id="print" type="button" class="btn btn-print">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Progression Details</h6>
                                <span class="badge bg-primary">Total Records: <?= $total_rows ?></span>
                            </div>
                            <div class="card-body" style="font-size: 13px;" id="progression_report_area">
                                <div class="table-responsive">
                                    <table id="progressionTable" class="table table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Old Registration ID</th>
                                                <th>From Registration Code</th>
                                                <th>From University</th>
                                                <th>From Programme</th>
                                                <th>From Batch</th>
                                                <th>To University</th>
                                                <th>To Programme</th>
                                                <th>To Batch</th>
                                                <th>Allocated ID</th>
                                                <th>Transfer Date</th>
                                                <th>Status</th>
                                                <th>Entered By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 0;
                                            foreach ($rows as $row) {
                                                $no++;
                                                $status = strtolower(trim($row['active_status']));
                                                $badge  = $status === 'active' ? 'success' : ($status === 'drop' ? 'danger' : 'secondary');
                                            ?>
                                                <tr>
                                                    <td><?= $no ?></td>
                                                    <td><?= (int)$row['student_id'] ?></td>
                                                    <td><?= cleanText($row['from_student']) ?></td>
                                                    <td><?= cleanText($row['OLD_registration_id']) ?></td>
                                                    <td><?= cleanText($row['from_registration_code']) ?></td>
                                                    <td><?= cleanText($row['from_university']) ?></td>
                                                    <td><?= cleanText($row['from_programme']) ?></td>
                                                    <td><?= cleanText($row['from_batch']) ?></td>
                                                    <td><?= cleanText($row['university_name']) ?></td>
                                                    <td><?= cleanText($row['program_name']) ?></td>
                                                    <td><?= cleanText($row['batch_name']) ?></td>
                                                    <td><?= (int)$row['allocated_id'] ?></td>
                                                    <td><?= htmlspecialchars($row['transfer_date']) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $badge ?>">
                                                            <?= cleanText($row['active_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= cleanText($row['entered_by']) ?></td>
                                                </tr>
                                            <?php } ?>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">

    <script>
        // Batches grouped by programme_code (built by PHP)
        const batchMap = <?= json_encode($batchMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const selectedBatch = "<?= $sel_batch ?: '' ?>";

        function loadBatches(programCode, keepSelected) {
            const $batch = $('#batch_select');
            $batch.empty().append('<option value="">All Batches</option>');

            let list = [];
            if (programCode) {
                // Only the batches of the chosen programme
                list = batchMap[programCode] || [];
            } else {
                // No programme chosen -> show all batches (without duplicates)
                const seen = {};
                Object.values(batchMap).forEach(arr => arr.forEach(b => {
                    if (!seen[b.id]) {
                        seen[b.id] = true;
                        list.push(b);
                    }
                }));
                list.sort((a, b) => a.name.localeCompare(b.name, undefined, {
                    numeric: true
                }));
            }

            list.forEach(b => {
                const opt = new Option(b.name, b.id, false, keepSelected && String(b.id) === selectedBatch);
                $batch.append(opt);
            });
            $batch.trigger('change.select2');
        }

        $(document).ready(function() {
            $('#programme_select').select2();
            $('#batch_select').select2();

            // Initial load (keeps the chosen batch after the form is submitted)
            loadBatches($('#programme_select').val(), true);

            // When programme changes -> refresh batch list
            $('#programme_select').on('change', function() {
                loadBatches($(this).val(), false);
            });

            $('#progressionTable').DataTable({
                retrieve: true,
                responsive: false,
                scrollX: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                order: [] // keep the SQL order (latest transfer first)
            });
        });

        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('#print');
            if (!btn) return;
            window.print();
        });
    </script>
</div>
</body>

</html>