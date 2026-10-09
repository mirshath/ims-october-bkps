<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}
// permission checking
require_once 'PermissionChecking.php';


/* Clean names that contain extra spaces (common in this data) */
function cleanText($v)
{
    return htmlspecialchars(preg_replace('/\s+/', ' ', trim((string)$v)), ENT_QUOTES, 'UTF-8');
}

/* Initials for the student marker, e.g. "Sanesh Peris" -> "SP" */
function initials($name)
{
    $parts = preg_split('/\s+/', trim((string)$name), -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '--';
    $a = mb_substr($parts[0], 0, 1);
    $b = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
    return htmlspecialchars(mb_strtoupper($a . $b), ENT_QUOTES, 'UTF-8');
}

/* ---------------- Filters ---------------- */
$sel_program = isset($_POST['programme_code']) && $_POST['programme_code'] !== '' ? (int)$_POST['programme_code'] : 0;
$sel_batch   = isset($_POST['batch_id']) && $_POST['batch_id'] !== '' ? (int)$_POST['batch_id'] : 0;
$sel_from_program = isset($_POST['from_programme']) ? trim((string)$_POST['from_programme']) : '';
$sel_from_batch   = isset($_POST['from_batch']) ? trim((string)$_POST['from_batch']) : '';
$db_error    = '';

/* Programme list (only programmes that exist in the progression table) */
$programs = [];
$prog_res = mysqli_query($conn, "SELECT DISTINCT pp.programme_code, p.program_name
                                     FROM program_progression_table pp
                                     LEFT JOIN program_table p ON pp.programme_code = p.program_code
                                     ORDER BY p.program_name ASC");
while ($prog_res && ($r = mysqli_fetch_assoc($prog_res))) {
    $programs[] = $r;
}

/* Batches grouped by programme (used by the dependent batch dropdown) */
$batchMap = [];
$batch_res = mysqli_query($conn, "SELECT DISTINCT pp.programme_code, pp.batch_id, b.batch_name
                                      FROM program_progression_table pp
                                      LEFT JOIN batch_table b ON pp.batch_id = b.id
                                      ORDER BY b.batch_name ASC");
while ($batch_res && ($r = mysqli_fetch_assoc($batch_res))) {
    $batchMap[$r['programme_code']][] = [
        'id'   => (int)$r['batch_id'],
        'name' => $r['batch_name'] ?: ('Batch ID ' . $r['batch_id'])
    ];
}

/* "From" side: programme / batch names stored as text in the progression table */
$from_programs = [];
$fromBatchMap  = []; // [from programme name => [ ['id'=>name,'name'=>name], ... ]]
$from_res = mysqli_query($conn, "SELECT DISTINCT TRIM(from_programme) AS fp, TRIM(from_batch) AS fb
                                     FROM program_progression_table
                                     WHERE TRIM(from_programme) <> ''
                                     ORDER BY fp ASC, fb ASC");
while ($from_res && ($r = mysqli_fetch_assoc($from_res))) {
    $from_programs[$r['fp']] = true;
    if ($r['fb'] !== '') {
        $fromBatchMap[$r['fp']][] = ['id' => $r['fb'], 'name' => $r['fb']];
    }
}
$from_programs = array_keys($from_programs);

/* Names of the active filters (for the filter chips) */
$sel_program_name = '';
foreach ($programs as $p) {
    if ((int)$p['programme_code'] === $sel_program) {
        $sel_program_name = $p['program_name'] ?: ('Programme ' . $p['programme_code']);
    }
}
$sel_batch_name = '';
foreach ($batchMap as $list) {
    foreach ($list as $b) {
        if ($b['id'] === $sel_batch) {
            $sel_batch_name = $b['name'];
        }
    }
}

/* ---------------- Report query ---------------- */
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
if ($sel_from_program !== '') {
    $sql .= " AND TRIM(pp.from_programme) = ?";
    $types .= 's';
    $params[] = $sel_from_program;
}
if ($sel_from_batch !== '') {
    $sql .= " AND TRIM(pp.from_batch) = ?";
    $types .= 's';
    $params[] = $sel_from_batch;
}
$sql .= " ORDER BY pp.transfer_date DESC, pp.id DESC";

$rows = [];
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
} else {
    $db_error = 'The report could not be loaded. Please refresh the page or contact support.';
}
$total_rows = count($rows);
$has_filter = ($sel_program || $sel_batch || $sel_from_program !== '' || $sel_from_batch !== '');

/* Summary numbers */
$active_count = 0;
$uniq_programs = [];
$uniq_batches  = [];
foreach ($rows as $r) {
    if (strtolower(trim($r['active_status'])) === 'active') $active_count++;
    $uniq_programs[$r['programme_code']] = true;
    $uniq_batches[$r['batch_id']] = true;
}
$latest_transfer = $total_rows ? $rows[0]['transfer_date'] : null;
?>

<style>
    /* ====== Progression Report : scoped styles, inherits the site theme ======
           Colours come from the theme (Bootstrap / SB Admin variables) with the
           SB Admin palette as fallback, so the page always matches the system. */
    .pr-page {
        --pr-primary: var(--bs-primary, var(--primary, #4e73df));
        --pr-primary-dark: #224abe;
        --pr-primary-soft: rgba(78, 115, 223, .09);
        --pr-primary-soft: color-mix(in srgb, var(--pr-primary) 9%, #fff);
        --pr-success: var(--bs-success, var(--success, #1cc88a));
        --pr-info: var(--bs-info, var(--info, #36b9cc));
        --pr-warning: var(--bs-warning, var(--warning, #f6c23e));
        --pr-danger: var(--bs-danger, var(--danger, #e74a3b));
        --pr-text: #5a5c69;
        --pr-heading: #3a3b45;
        --pr-muted: #858796;
        --pr-line: #e3e6f0;
        --pr-surface: #ffffff;
        --pr-subtle: #f8f9fc;
        --pr-radius: 8px;
        --pr-shadow: 0 .15rem 1.25rem 0 rgba(58, 59, 69, .08);
        --pr-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;

        font-family: inherit;
        color: var(--pr-text);
        max-width: 1560px;
        margin: 0 auto;
    }

    .pr-page *,
    .pr-page *::before,
    .pr-page *::after {
        box-sizing: border-box;
    }

    /* keep the page inside the viewport (nav stays in place) */
    #content-wrapper,
    #content {
        min-width: 0;
    }

    @keyframes prShimmer {
        from {
            background-position: 200% 0;
        }

        to {
            background-position: -200% 0;
        }
    }

    /* ---------- header ---------- */
    .pr-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .pr-title {
        margin: 0 0 4px;
        font-size: 1.6rem;
        font-weight: 500;
        line-height: 1.2;
        color: var(--pr-heading);
    }

    .pr-sub {
        margin: 0;
        font-size: .85rem;
        color: var(--pr-muted);
    }

    .pr-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 38px;
        padding: 0 16px;
        border-radius: 6px;
        border: 1px solid transparent;
        font-size: .85rem;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        text-decoration: none !important;
        transition: background-color .2s, border-color .2s, box-shadow .2s, transform .15s;
    }

    .pr-btn:active {
        transform: translateY(1px);
    }

    .pr-btn-primary {
        background: var(--pr-primary);
        border-color: var(--pr-primary);
        color: #fff;
        box-shadow: 0 .125rem .25rem 0 rgba(58, 59, 69, .2);
    }

    .pr-btn-primary:hover {
        background: var(--pr-primary-dark);
        border-color: var(--pr-primary-dark);
        color: #fff;
    }

    .pr-btn-light {
        background: #fff;
        border-color: #d1d3e2;
        color: var(--pr-text);
    }

    .pr-btn-light:hover {
        background: var(--pr-subtle);
        color: var(--pr-heading);
    }

    /* ---------- stat tiles ---------- */
    .pr-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 22px;
    }

    .pr-stat {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 18px 22px;
        background: var(--pr-surface);
        border: 1px solid var(--pr-line);
        border-left: 4px solid var(--c);
        border-radius: var(--pr-radius);
        box-shadow: var(--pr-shadow);
        min-width: 0;
    }

    .pr-stat.is-primary {
        --c: var(--pr-primary);
    }

    .pr-stat.is-success {
        --c: var(--pr-success);
    }

    .pr-stat.is-info {
        --c: var(--pr-info);
    }

    .pr-stat.is-warning {
        --c: var(--pr-warning);
    }

    .pr-stat-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--c);
        margin-bottom: 6px;
    }

    .pr-stat-value {
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--pr-heading);
        font-variant-numeric: tabular-nums;
    }

    .pr-stat-value small {
        display: block;
        margin-top: 3px;
        font-size: .72rem;
        font-weight: 400;
        color: var(--pr-muted);
    }

    .pr-stat-icon {
        flex: none;
        font-size: 1.7rem;
        color: #dddfeb;
    }

    /* ---------- cards ---------- */
    .pr-card {
        background: var(--pr-surface);
        border: 1px solid var(--pr-line);
        border-radius: var(--pr-radius);
        box-shadow: var(--pr-shadow);
        margin-bottom: 22px;
    }

    .pr-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 15px 22px;
        background: var(--pr-subtle);
        border-bottom: 1px solid var(--pr-line);
        border-radius: var(--pr-radius) var(--pr-radius) 0 0;
    }

    .pr-card-head h2 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: var(--pr-primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pr-card-body {
        padding: 22px;
    }

    .pr-count {
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 11px;
        border-radius: 999px;
        background: var(--pr-primary-soft);
        color: var(--pr-primary);
    }

    /* ---------- filter ---------- */
    .pr-filter-groups {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 20px;
    }

    .pr-fgroup {
        padding: 16px 18px 18px;
        border: 1px solid var(--pr-line);
        border-radius: var(--pr-radius);
        background: var(--pr-subtle);
    }

    .pr-fgroup-title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 0 14px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--pr-muted);
    }

    .pr-fgroup.is-to {
        background: var(--pr-primary-soft);
        border-color: rgba(78, 115, 223, .25);
    }

    .pr-fgroup.is-to .pr-fgroup-title {
        color: var(--pr-primary);
    }

    .pr-fgroup-fields {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 14px;
    }

    .pr-field {
        display: flex;
        flex-direction: column;
        gap: 7px;
        min-width: 0;
    }

    .pr-field label {
        margin: 0;
        font-size: .78rem;
        font-weight: 700;
        color: var(--pr-heading);
    }

    .pr-actions {
        display: flex;
        gap: 10px;
        margin-top: 18px;
    }

    .pr-chips {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid var(--pr-line);
        font-size: .78rem;
        color: var(--pr-muted);
    }

    .pr-chip {
        display: inline-flex;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 999px;
        background: var(--pr-primary-soft);
        color: var(--pr-primary);
        font-weight: 600;
    }

    .pr-chip span {
        font-weight: 400;
        opacity: .75;
    }

    /* select2 skin (matches theme form-control) */
    .pr-page .select2-container {
        width: 100% !important;
    }

    .pr-page .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #d1d3e2;
        border-radius: 6px;
        background: #fff;
        display: flex;
        align-items: center;
        transition: border-color .15s, box-shadow .15s;
    }

    .pr-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: .85rem;
        color: var(--pr-text);
        padding-left: 12px;
        padding-right: 32px;
        line-height: 36px;
    }

    .pr-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 6px;
    }

    .pr-page .select2-container--default.select2-container--focus .select2-selection--single,
    .pr-page .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #bac8f3;
        box-shadow: 0 0 0 .2rem rgba(78, 115, 223, .2);
    }

    .select2-dropdown {
        border: 1px solid #d1d3e2 !important;
        border-radius: 6px !important;
        box-shadow: 0 .5rem 1.5rem rgba(58, 59, 69, .15);
        font-size: .85rem;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background: #4e73df !important;
        color: #fff !important;
    }

    /* ---------- table ---------- */
    .pr-body {
        position: relative;
        padding: 8px 22px 20px;
    }

    .pr-skeleton {
        position: absolute;
        inset: 18px 22px auto 22px;
        display: grid;
        gap: 12px;
    }

    .pr-skeleton i {
        display: block;
        height: 44px;
        border-radius: 6px;
        background: linear-gradient(100deg, #f1f2f8 30%, #f8f9fc 50%, #f1f2f8 70%);
        background-size: 200% 100%;
        animation: prShimmer 1.6s linear infinite;
    }

    .pr-table-wrap {
        opacity: 0;
        transition: opacity .3s;
    }

    .pr-ready .pr-table-wrap {
        opacity: 1;
    }

    .pr-ready .pr-skeleton {
        display: none;
    }

    .pr-page table.pr-table {
        width: 100% !important;
        margin: 0 !important;
        border-collapse: collapse;
        font-size: .82rem;
        color: var(--pr-text);
    }

    .pr-page table.pr-table thead th {
        padding: 12px 14px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--pr-heading);
        background: var(--pr-subtle);
        border: 0 !important;
        border-top: 1px solid var(--pr-line) !important;
        border-bottom: 2px solid var(--pr-line) !important;
        white-space: nowrap;
    }

    .pr-page table.pr-table tbody td {
        padding: 13px 14px;
        vertical-align: middle;
        border: 0 !important;
        border-bottom: 1px solid var(--pr-line) !important;
        background: transparent !important;
        box-shadow: none !important;
        word-break: break-word;
    }

    .pr-page table.pr-table tbody tr:hover td {
        background: var(--pr-subtle) !important;
    }

    .pr-page table.pr-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .pr-page table.dataTable thead .sorting:before,
    .pr-page table.dataTable thead .sorting:after,
    .pr-page table.dataTable thead .sorting_asc:before,
    .pr-page table.dataTable thead .sorting_asc:after,
    .pr-page table.dataTable thead .sorting_desc:before,
    .pr-page table.dataTable thead .sorting_desc:after {
        opacity: .3;
    }

    .pr-code {
        font-family: var(--pr-mono);
        font-size: .78rem;
        color: var(--pr-heading);
    }

    .pr-muted {
        color: var(--pr-muted);
        font-size: .74rem;
    }

    .pr-idx {
        color: var(--pr-muted);
        font-variant-numeric: tabular-nums;
    }

    .pr-student {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 190px;
    }

    .pr-avatar {
        flex: none;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: .72rem;
        font-weight: 700;
        background: var(--pr-primary-soft);
        color: var(--pr-primary);
    }

    .pr-student b {
        display: block;
        font-weight: 600;
        color: var(--pr-heading);
        line-height: 1.35;
    }

    .pr-route-prog {
        display: block;
        font-weight: 600;
        color: var(--pr-heading);
        line-height: 1.4;
    }

    .pr-route-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        margin-top: 5px;
    }

    .pr-tag {
        font-size: .7rem;
        font-weight: 600;
        padding: 2px 9px;
        border-radius: 4px;
        background: #eaecf4;
        color: var(--pr-text);
    }

    .pr-tag.is-to {
        background: var(--pr-primary-soft);
        color: var(--pr-primary);
    }

    .pr-to-inner {
        display: flex;
        gap: 11px;
        align-items: flex-start;
    }

    .pr-arrow {
        flex: none;
        margin-top: 3px;
        font-size: .75rem;
        color: var(--pr-primary);
    }

    .pr-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 4px 11px;
        border-radius: 999px;
        font-size: .74rem;
        font-weight: 700;
        text-transform: capitalize;
        background: #eaecf4;
        color: var(--pr-muted);
    }

    .pr-status::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .pr-status.is-active {
        background: rgba(28, 200, 138, .12);
        color: #13855c;
    }

    .pr-status.is-drop {
        background: rgba(231, 74, 59, .1);
        color: #c0392b;
    }

    /* datatables chrome */
    .pr-page .dataTables_wrapper .row:first-child {
        padding: 14px 0 12px;
        align-items: center;
    }

    .pr-page .dataTables_length label,
    .pr-page .dataTables_filter label {
        font-size: .8rem;
        color: var(--pr-muted);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    .pr-page .dataTables_filter label {
        justify-content: flex-end;
    }

    .pr-page .dataTables_filter input,
    .pr-page .dataTables_length select {
        height: 36px;
        border: 1px solid #d1d3e2;
        border-radius: 6px;
        padding: 0 12px;
        font-size: .82rem;
        color: var(--pr-text);
        background-color: #fff;
        outline: none;
        margin-left: 0 !important;
    }

    .pr-page .dataTables_filter input {
        width: 290px;
        max-width: 100%;
    }

    .pr-page .dataTables_filter input:focus,
    .pr-page .dataTables_length select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 .2rem rgba(78, 115, 223, .2);
    }

    .pr-page .dataTables_info {
        font-size: .8rem;
        color: var(--pr-muted);
        padding-top: 16px !important;
    }

    .pr-page .dataTables_paginate {
        padding-top: 10px !important;
    }

    .pr-page .pagination {
        margin: 0;
    }

    .pr-page .page-link {
        font-size: .8rem;
        color: var(--pr-primary);
        border-color: var(--pr-line);
    }

    .pr-page .page-item.active .page-link {
        background: var(--pr-primary);
        border-color: var(--pr-primary);
        color: #fff;
    }

    .pr-page .page-item.disabled .page-link {
        color: #b7b9cc;
    }

    /* ---------- empty / error ---------- */
    .pr-empty {
        display: grid;
        place-items: center;
        text-align: center;
        gap: 8px;
        padding: 52px 16px 44px;
    }

    .pr-empty-mark {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 1.2rem;
        background: var(--pr-primary-soft);
        color: var(--pr-primary);
        margin-bottom: 6px;
    }

    .pr-empty h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--pr-heading);
    }

    .pr-empty p {
        margin: 0 0 10px;
        max-width: 46ch;
        font-size: .85rem;
        line-height: 1.6;
        color: var(--pr-muted);
    }

    .pr-error {
        margin-bottom: 20px;
        padding: 13px 18px;
        border-radius: 6px;
        background: rgba(231, 74, 59, .08);
        border: 1px solid rgba(231, 74, 59, .3);
        color: #c0392b;
        font-size: .85rem;
    }

    /* ---------- responsive ---------- */
    @media (max-width: 1199px) {
        .pr-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pr-filter-groups {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767px) {
        .pr-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .pr-stats,
        .pr-fgroup-fields {
            grid-template-columns: 1fr;
        }

        .pr-body {
            padding-left: 14px;
            padding-right: 14px;
            overflow-x: auto;
        }

        .pr-skeleton {
            inset: 18px 14px auto 14px;
        }

        .pr-page .dataTables_filter input {
            width: 100%;
        }
    }

    /* ---------- print ---------- */
    @media print {
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

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
            padding: 0 !important;
        }

        .pr-table-wrap {
            opacity: 1 !important;
        }

        .no-print,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_paginate,
        .dataTables_info,
        .pr-skeleton {
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
                <div class="pr-page">

                    <!-- Header -->
                    <div class="pr-head no-print">
                        <div>
                            <h1 class="pr-title">Progression Report</h1>
                            <p class="pr-sub">Student Progression between programmes and batches, newest first.</p>
                        </div>
                        <button id="print" type="button" class="pr-btn pr-btn-primary">
                            <i class="fas fa-print"></i> Print Report
                        </button>
                    </div>

                    <?php if ($db_error) { ?>
                        <div class="pr-error no-print"><?= htmlspecialchars($db_error) ?></div>
                    <?php } ?>

                    <!-- Summary -->
                    <div class="pr-stats no-print">
                        <div class="pr-stat is-primary">
                            <div>
                                <div class="pr-stat-label">Total Progressions</div>
                                <div class="pr-stat-value"><?= number_format($total_rows) ?></div>
                            </div>
                            <i class="fas fa-exchange-alt pr-stat-icon"></i>
                        </div>
                        <div class="pr-stat is-success">
                            <div>
                                <div class="pr-stat-label">Active</div>
                                <div class="pr-stat-value"><?= number_format($active_count) ?></div>
                            </div>
                            <i class="fas fa-check-circle pr-stat-icon"></i>
                        </div>
                        <div class="pr-stat is-info">
                            <div>
                                <div class="pr-stat-label">Programmes / Batches</div>
                                <div class="pr-stat-value"><?= count($uniq_programs) ?> / <?= count($uniq_batches) ?></div>
                            </div>
                            <i class="fas fa-layer-group pr-stat-icon"></i>
                        </div>
                        <div class="pr-stat is-warning">
                            <div>
                                <div class="pr-stat-label">Latest Progression</div>
                                <div class="pr-stat-value">
                                    <?php if ($latest_transfer) { ?>
                                        <?= htmlspecialchars(date('d M Y', strtotime($latest_transfer))) ?>
                                        <small><?= htmlspecialchars(date('H:i', strtotime($latest_transfer))) ?></small>
                                    <?php } else { ?>
                                        <span style="color:#b7b9cc">No data</span>
                                    <?php } ?>
                                </div>
                            </div>
                            <i class="fas fa-calendar-alt pr-stat-icon"></i>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="pr-card no-print">
                        <div class="pr-card-head">
                            <h2><i class="fas fa-filter"></i> Filter</h2>
                        </div>
                        <div class="pr-card-body">
                            <form method="POST">
                                <div class="pr-filter-groups">
                                    <!-- FROM (previous programme) -->
                                    <div class="pr-fgroup">
                                        <h3 class="pr-fgroup-title"><i class="fas fa-sign-out-alt"></i> From (previous)</h3>
                                        <div class="pr-fgroup-fields">
                                            <div class="pr-field">
                                                <label for="from_programme_select">From Programme</label>
                                                <select id="from_programme_select" name="from_programme">
                                                    <option value="">All Programmes</option>
                                                    <?php foreach ($from_programs as $fp) { ?>
                                                        <option value="<?= htmlspecialchars($fp, ENT_QUOTES, 'UTF-8') ?>" <?= $sel_from_program === $fp ? 'selected' : '' ?>>
                                                            <?= cleanText($fp) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="pr-field">
                                                <label for="from_batch_select">From Batch</label>
                                                <select id="from_batch_select" name="from_batch">
                                                    <option value="">All Batches</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TO (current programme) -->
                                    <div class="pr-fgroup is-to">
                                        <h3 class="pr-fgroup-title"><i class="fas fa-sign-in-alt"></i> To (current)</h3>
                                        <div class="pr-fgroup-fields">
                                            <div class="pr-field">
                                                <label for="programme_select">To Programme</label>
                                                <select id="programme_select" name="programme_code">
                                                    <option value="">All Programmes</option>
                                                    <?php foreach ($programs as $p) { ?>
                                                        <option value="<?= (int)$p['programme_code'] ?>" <?= $sel_program === (int)$p['programme_code'] ? 'selected' : '' ?>>
                                                            <?= cleanText($p['program_name'] ?: ('Programme ' . $p['programme_code'])) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="pr-field">
                                                <label for="batch_select">To Batch</label>
                                                <select id="batch_select" name="batch_id">
                                                    <option value="">All Batches</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pr-actions">
                                    <button type="submit" name="submit" class="pr-btn pr-btn-primary"><i class="fas fa-search"></i> Search</button>
                                    <a href="progressionToReport" class="pr-btn pr-btn-light">Reset</a>
                                </div>

                                <?php if ($has_filter) { ?>
                                    <div class="pr-chips">
                                        Active filters:
                                        <?php if ($sel_from_program !== '') { ?>
                                            <span class="pr-chip"><span>From programme</span> <?= cleanText($sel_from_program) ?></span>
                                        <?php } ?>
                                        <?php if ($sel_from_batch !== '') { ?>
                                            <span class="pr-chip"><span>From batch</span> <?= cleanText($sel_from_batch) ?></span>
                                        <?php } ?>
                                        <?php if ($sel_program) { ?>
                                            <span class="pr-chip"><span>To programme</span> <?= cleanText($sel_program_name) ?></span>
                                        <?php } ?>
                                        <?php if ($sel_batch) { ?>
                                            <span class="pr-chip"><span>To batch</span> <?= cleanText($sel_batch_name) ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </form>
                        </div>
                    </div>

                    <!-- Report Data -->
                    <div class="pr-card">
                        <div class="pr-card-head no-print">
                            <h2><i class="fas fa-table"></i> Progression Details</h2>
                            <span class="pr-count"><?= $total_rows ?> <?= $total_rows === 1 ? 'record' : 'records' ?></span>
                        </div>

                        <div class="pr-body" id="progression_report_area">
                            <?php if ($total_rows === 0) { ?>
                                <div class="pr-empty">
                                    <div class="pr-empty-mark"><i class="fas fa-search"></i></div>
                                    <h3>No progression records found</h3>
                                    <p><?= ($has_filter)
                                            ? 'Nothing matches the selected filters. Try a different programme or batch, or clear the filter to see every progression.'
                                            : 'Progressions will appear here once a student is moved to another programme or batch.' ?></p>
                                    <?php if ($has_filter) { ?>
                                        <a href="progressionToReport" class="pr-btn pr-btn-primary">Clear Filter</a>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="pr-skeleton" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div>

                                <div class="pr-table-wrap">
                                    <table id="progressionTable" class="pr-table" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th style="width:44px">#</th>
                                                <th>Student</th>
                                                <th>Registration</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Progression</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 0;
                                            foreach ($rows as $row) {
                                                $no++;
                                                $status = strtolower(trim($row['active_status']));
                                                $cls    = $status === 'active' ? 'is-active' : ($status === 'drop' ? 'is-drop' : '');
                                                $ts     = strtotime($row['transfer_date']);
                                            ?>
                                                <tr>
                                                    <td class="pr-idx"><?= $no ?></td>

                                                    <td data-order="<?= cleanText($row['from_student']) ?>">
                                                        <div class="pr-student">
                                                            <div class="pr-avatar"><?= initials($row['from_student']) ?></div>
                                                            <div>
                                                                <b><?= cleanText($row['from_student']) ?></b>
                                                                <span class="pr-muted">Student ID <?= (int)$row['student_id'] ?></span>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="pr-code"><?= cleanText($row['OLD_registration_id']) ?: '--' ?></span><br>
                                                        <span class="pr-muted"><?= cleanText($row['from_registration_code']) ?: '--' ?></span><br>
                                                        <span class="pr-muted">Alloc #<?= (int)$row['allocated_id'] ?></span>
                                                    </td>

                                                    <td>
                                                        <span class="pr-route-prog"><?= cleanText($row['from_programme']) ?></span>
                                                        <div class="pr-route-meta">
                                                            <span class="pr-tag"><?= cleanText($row['from_batch']) ?></span>
                                                            <span class="pr-muted"><?= cleanText($row['from_university']) ?></span>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <div class="pr-to-inner">
                                                            <i class="fas fa-arrow-right pr-arrow"></i>
                                                            <div>
                                                                <span class="pr-route-prog"><?= cleanText($row['program_name']) ?></span>
                                                                <div class="pr-route-meta">
                                                                    <span class="pr-tag is-to"><?= cleanText($row['batch_name']) ?></span>
                                                                    <span class="pr-muted"><?= cleanText($row['university_name']) ?></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <td data-order="<?= (int)$ts ?>">
                                                        <?= htmlspecialchars(date('d M Y', $ts)) ?><br>
                                                        <span class="pr-muted"><?= htmlspecialchars(date('H:i', $ts)) ?> &middot; <?= cleanText($row['entered_by']) ?></span>
                                                    </td>

                                                    <td>
                                                        <span class="pr-status <?= $cls ?>"><?= cleanText($row['active_status']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <script>
        // Dropdown data built by PHP
        const toBatchMap = <?= json_encode($batchMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; // { programme_code: [{id, name}] }
        const fromBatchMap = <?= json_encode($fromBatchMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; // { programme name: [{id, name}] }
        const selectedToBatch = <?= json_encode((string)($sel_batch ?: ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const selectedFromBatch = <?= json_encode($sel_from_batch, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // Fill a batch dropdown with the batches of the chosen programme (or all batches)
        function fillBatches(batchSel, map, programKey, keepValue) {
            const $batch = $(batchSel);
            $batch.empty().append('<option value="">All Batches</option>');

            let list = [];
            if (programKey) {
                list = (map[programKey] || []).slice();
            } else {
                const seen = {};
                Object.values(map).forEach(arr => arr.forEach(b => {
                    if (!seen[b.id]) {
                        seen[b.id] = true;
                        list.push(b);
                    }
                }));
            }
            list.sort((a, b) => String(a.name).localeCompare(String(b.name), undefined, {
                numeric: true
            }));

            list.forEach(b => {
                $batch.append(new Option(b.name, b.id, false, keepValue !== '' && String(b.id) === String(keepValue)));
            });
            $batch.trigger('change.select2');
        }

        // Link a programme dropdown with its batch dropdown
        function linkFilters(progSel, batchSel, map, initialBatch) {
            $(progSel).select2({
                width: '100%'
            });
            $(batchSel).select2({
                width: '100%'
            });
            fillBatches(batchSel, map, $(progSel).val(), initialBatch);
            $(progSel).on('change', function() {
                fillBatches(batchSel, map, $(this).val(), '');
            });
        }

        $(document).ready(function() {
            linkFilters('#from_programme_select', '#from_batch_select', fromBatchMap, selectedFromBatch);
            linkFilters('#programme_select', '#batch_select', toBatchMap, selectedToBatch);

            const reveal = () => $('.pr-body').addClass('pr-ready');

            if ($('#progressionTable').length) {
                $('#progressionTable').DataTable({
                    retrieve: true,
                    pageLength: 10,
                    ordering: true,
                    searching: true,
                    order: [], // keep SQL order (latest transfer first)
                    columnDefs: [{
                        orderable: false,
                        targets: 0
                    }],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search student, batch, registration code',
                        lengthMenu: '_MENU_ per page',
                        info: 'Showing _START_ to _END_ of _TOTAL_ transfers',
                        zeroRecords: 'No transfers match your search.',
                        paginate: {
                            previous: 'Previous',
                            next: 'Next'
                        }
                    },
                    initComplete: reveal
                });
                setTimeout(reveal, 1500); // safety net
            }
        });

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('#print')) window.print();
        });
    </script>

</div>
</body>

</html>