<?php
$token = $_GET['token'] ?? null;
include("../database/connection.php");
$programName = '';
$batchName = '';
$programme_id = '';
$batch_id = '';

// Only allow loading on register.php
if (basename($_SERVER['PHP_SELF']) !== 'register.php') {
    header("Location: https://bms.ac.lk");
    exit();
}

// --- Extra PHP for AJAX endpoints (batch count, batch full list, payment details) ---
if (isset($_GET['ajax'])) {
    // AJAX: batch_count returns count for program+batch
    if ($_GET['ajax'] === 'batch_count') {
        $program = $_REQUEST['program'] ?? '';
        $batch = $_REQUEST['batch'] ?? '';
        $count = 0;
        if ($program && $batch) {
            $ps = $conn->prepare("SELECT COUNT(*) AS cnt FROM students_temporary_registration WHERE program=? AND batch=?");
            $ps->bind_param('ss', $program, $batch);
            $ps->execute();
            $rs = $ps->get_result();
            if ($row = $rs->fetch_assoc())
                $count = (int) $row['cnt'];
            $ps->close();
        }
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }
    // AJAX: batch_statuses returns full status for all batches (for live interval polling)
    if ($_GET['ajax'] === 'batch_statuses') {
        $statuses = [];
        $sql = "SELECT b.id, b.batch_name, b.batch_limit, b.programme, p.program_name,
                (SELECT COUNT(*) FROM students_temporary_registration s WHERE s.program=p.program_name AND s.batch=b.batch_name) AS current_count
                FROM batch_table b
                JOIN program_table p ON b.programme=p.program_code
                WHERE b.intake_end_date >= CURDATE()";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $statuses[] = [
                'id' => $row['id'],
                'batch_name' => $row['batch_name'],
                'program_code' => $row['programme'],
                'program_name' => $row['program_name'],
                'batch_limit' => $row['batch_limit'],
                'current_count' => (int) $row['current_count']
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['batches' => $statuses]);
        exit;
    }
    // AJAX: payment_batch_details returns payment batch allocation details for program_id+batch_id
    if ($_GET['ajax'] === 'payment_batch_details') {
        $programme_id = $_REQUEST['program_id'] ?? '';
        $batch_id = $_REQUEST['batch_id'] ?? '';
        $data = null;
        if ($programme_id && $batch_id) {
            $ps = $conn->prepare("SELECT id, programme_id, batch_id, course_fee_lkr, uni_fee_gbp, uni_fee_usd, uni_fee_euro, register_date, installment_no, registration_fee, created_at, only_course_fee FROM payment_batch_allocation WHERE programme_id=? AND batch_id=? LIMIT 1");
            $ps->bind_param('ss', $programme_id, $batch_id);
            $ps->execute();
            $rs = $ps->get_result();
            if ($row = $rs->fetch_assoc())
                $data = $row;
            $ps->close();
        }
        header('Content-Type: application/json');
        echo json_encode(['payment' => $data]);
        exit;
    }
    // AJAX: check_nic checks if NIC exists in students_temporary_registration
    if ($_GET['ajax'] === 'check_nic') {
        $nic = $_REQUEST['nic'] ?? '';
        $exists = false;
        if ($nic) {
            $stmt = $conn->prepare("SELECT id FROM students_temporary_registration WHERE nic=? LIMIT 1");
            $stmt->bind_param("s", $nic);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $exists = true;
            }
            $stmt->close();
        }
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
        exit;
    }
    // AJAX: check_passport checks if Passport exists in students_temporary_registration
    if ($_GET['ajax'] === 'check_passport') {
        $passport = $_REQUEST['passport'] ?? '';
        $exists = false;
        if ($passport) {
            $stmt = $conn->prepare("SELECT id FROM students_temporary_registration WHERE passport=? AND passport != '' LIMIT 1");
            $stmt->bind_param("s", $passport);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $exists = true;
            }
            $stmt->close();
        }
        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* ...unchanged css... */
        body {
            background-color: #f8f9fa;
        }

        .registration-form {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            padding: 24px 18px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.07);
        }

        @media (min-width: 600px) {
            .registration-form {
                padding: 38px 45px;
            }
        }

        .registration-form h2 {
            margin-bottom: 16px;
            font-weight: 700;
            color: #223748;
        }

        .custom-tabs-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 0;
            width: 100%;
        }

        .custom-tabs-progress .tab-step {
            flex: 1 1 24%;
            position: relative;
            text-align: center;
            color: #bbb;
            cursor: default;
        }

        .custom-tabs-progress .tab-step .step-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 50%;
            border: 2px solid #b3bac3;
            font-size: 1.18em;
            margin-bottom: 4px;
            transition: all 0.25s;
            color: #aaa;
            font-weight: 500;
            z-index: 2;
        }

        .custom-tabs-progress .tab-step.active .step-circle,
        .custom-tabs-progress .tab-step.completed .step-circle {
            background: #1d568f;
            color: #fff;
            border-color: #1d568f;
        }

        .custom-tabs-progress .tab-step.completed .step-circle {
            background: #00b96c;
            color: #fff;
            border-color: #00b96c;
        }

        .custom-tabs-progress .tab-label {
            font-size: 1em;
            font-weight: 500;
            white-space: nowrap;
        }

        .custom-tabs-progress .tab-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -10px;
            width: 20px;
            height: 2.5px;
            background: #b3bac3;
            z-index: 0;
            transition: background .3s;
        }

        .custom-tabs-progress .tab-step.completed:not(:last-child)::after {
            background: #00b96c;
        }

        @media (max-width: 575.98px) {
            .registration-form {
                padding: 7px 3px;
                margin: 7px 0;
            }

            .custom-tabs-progress {
                padding: 2px 0;
                margin-left: -7px;
                margin-right: -7px;
                margin-bottom: 14px;
            }

            .custom-tabs-progress .tab-step {
                flex: 1 1 24%;
            }

            .custom-tabs-progress .tab-step .step-circle {
                width: 23px;
                height: 23px;
                font-size: 0.92em;
                margin-bottom: 1px;
            }

            .custom-tabs-progress .tab-label {
                font-size: 0.75em;
                font-weight: 500;
            }

            .custom-tabs-progress .tab-step:not(:last-child)::after {
                top: 13px;
                right: -7px;
                width: 14px;
                height: 2px;
            }

            .tab-pane {
                padding: 0 !important;
            }
        }

        .form-label {
            font-weight: 500;
            font-size: .97em;
        }

        .btn-primary,
        .btn-secondary {
            width: 100%;
            padding: 9px;
            font-size: 1.08em;
            position: relative;
            letter-spacing: 0.5px;
        }

        .btn-secondary {
            margin-bottom: 4px;
        }

        .btn-loading .spinner-border {
            display: inline-block !important;
        }

        .spinner-border {
            width: 1.1rem;
            height: 1.1rem;
            margin-right: 7px;
            vertical-align: middle;
            display: none;
        }

        .select2-container .select2-selection--single {
            min-height: 36px !important;
        }

        .edu-table thead th,
        .edu-table tbody td {
            vertical-align: middle;
            text-align: center;
        }

        .tab-nav-buttons {
            display: flex;
            gap: 16px 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 18px;
        }

        @media (max-width: 767px) {
            .tab-nav-buttons {
                flex-direction: column;
                gap: 9px 0;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }

        .batch-limit-small-note {
            color: #666;
            font-size: .95em;
            display: block;
            margin-top: 2px;
            margin-bottom: 6px;
        }

        .batch-current-count-note {
            color: #914c0b;
            font-size: .97em;
            display: block;
            margin-top: 2px;
            margin-bottom: 8px;
        }

        .batch-full-message {
            color: #c00409;
            font-weight: 600;
            margin-left: 4px;
            font-size: 0.97em;
            vertical-align: middle;
        }

        /*** MOBILE FRIENDLY FOR ACADEMIC/PROF. QUAL TABLE ***/
        @media (max-width: 575.98px) {

            #acad_prof_qualifications_body tr,
            #acad_prof_qualifications_body td,
            #acad_prof_qualifications_body th,
            #acad_prof_qualifications_body tr>td,
            #acad_prof_qualifications_body tr>th {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box;
                text-align: left !important;
            }

            #acad_prof_qualifications_body tr {
                margin-bottom: 15px;
                border-bottom: 2px solid #e3e7ed;
            }

            #acad_prof_qualifications_body td {
                margin-bottom: 5px;
                border: none !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            #tab4 .edu-table thead {
                display: none;
            }

            #tab4 .edu-table {
                border: none;
            }

            #acad_prof_qualifications_body input.form-control {
                margin-bottom: 6px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="registration-form">
            <div class="mb-3 text-center">
                <img src="https://ims.bms.ac.lk//admin/uploads/img/Registration-form-Banner.jpg"
                    class="img-fluid rounded" style="max-height:160px;object-fit:cover;" alt="Registration Banner">
            </div>
            <h2 class="text-center">Student Registration</h2>

            <form id="registrationForm" action="save_register.php" method="POST" novalidate>
                <div style="font-size: 13px;" class="custom-tabs-progress mb-3" id="progressTabs">
                    <div class="tab-step" data-step="1" style="pointer-events: none;">
                        <div class="step-circle">1</div>
                        <div class="tab-label">Personal</div>
                    </div>
                    <div class="tab-step" data-step="2" style="pointer-events: none;">
                        <div class="step-circle">2</div>
                        <div class="tab-label">Contact</div>
                    </div>
                    <div class="tab-step" data-step="3" style="pointer-events: none;">
                        <div class="step-circle">3</div>
                        <div class="tab-label">Education</div>
                    </div>
                    <div class="tab-step" data-step="4" style="pointer-events: none;">
                        <div class="step-circle">4</div>
                        <div class="tab-label">Acad./Prof.</div>
                    </div>
                </div>
                <div class="tab-content" id="regTabsContent">
                    <!-- Tab 1 -->
                    <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                        <div class="row g-3 pt-3">
                            <div class="col-md-4 col-12">
                                <label class="form-label">Title <span class="fw-bolder text-danger">*</span></label>
                                <select class="form-control select2" name="title" required id="title">
                                    <option value="" disabled selected>Select Title</option>
                                    <option value="Mr">Mr</option>
                                    <option value="Ms">Ms</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Rev">Rev</option>
                                    <option value="Prof">Prof</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-12">
                                <label class="form-label">First Name <span
                                        class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="firstname" id="firstname" required
                                    placeholder="Enter your first name">
                            </div>
                            <div class="col-md-4 col-12">
                                <label class="form-label">Last Name <span class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="lastname" id="lastname" required
                                    placeholder="Enter your last name">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Full Name <span class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="fullname" id="fullname">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Name for Certificate <span
                                        class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="certificate_name" id="certificate_name">
                                <small class="form-text text-danger">
                                    <b>Note:</b> Name appears as First name followed by Surname in the Certificate.
                                    Please write your names in this order.
                                </small>
                                <small class="form-text batch-limit-small-note" id="batchLimitInfo"
                                    style="display:none;"></small>
                                <small class="form-text batch-current-count-note" id="batchCountInfo"
                                    style="display:none;"></small>
                            </div>

                            <style>
                                /* --- Global Styles for Selection Group --- */
                                .selection-group-container {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 1px;
                                }

                                /* From Uiverse.io by santhosh_2608 - Refined & Scoped */
                                .rdr-radio-option {
                                    /* --rdr-red: #d3c617ff; */
                                    --rdr-red: rgb(4 45 92) !important;
                                    --rdr-red-dark: #8a0000;
                                    --rdr-white: #fefefe;
                                    --rdr-grey: #334155;
                                    --rdr-black: #0f172a;

                                    position: relative;
                                    display: flex;
                                    align-items: center;
                                    width: 100%;
                                    padding: 12px 20px;
                                    cursor: pointer;
                                    font-size: 16px;
                                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                    z-index: 1;
                                    margin-bottom: 8px;
                                    border-radius: 4px;
                                    background: #fff;
                                    border: 1px solid #e2e8f0;
                                }

                                .rdr-radio-option:hover {
                                    transform: translateX(5px);
                                    border-color: var(--rdr-red);
                                }

                                .rdr-radio-option input {
                                    position: absolute;
                                    opacity: 0;
                                    width: 0;
                                    height: 0;
                                }

                                .rdr-radio-option::before {
                                    content: "";
                                    position: absolute;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    bottom: 0;
                                    background: linear-gradient(90deg, rgba(208, 0, 0, 0.1), rgba(138, 0, 0, 0) 90%);
                                    transform: scaleX(0);
                                    transform-origin: left;
                                    transition: transform 0.4s cubic-bezier(0.23, 1, 0.32, 1);
                                    z-index: -1;
                                    clip-path: polygon(0 0, 100% 0, 95% 100%, 0% 100%);
                                    border-left: 4px solid var(--rdr-red);
                                }

                                .rdr-radio-option:has(input:checked) {
                                    background: #fff;
                                    border-color: var(--rdr-red);
                                    /* box-shadow: 0 4px 12px rgba(208, 0, 0, 0.1); */
                                    box-shadow: 0 4px 12px rgba(4, 45, 92, 0.1) !important;
                                }

                                .rdr-radio-option:has(input:checked)::before {
                                    transform: scaleX(1);
                                    /* background: linear-gradient(90deg, rgba(208, 0, 0, 0.15), rgba(138, 0, 0, 0) 90%); */
                                    background: linear-gradient(90deg, rgba(4, 45, 92, 0.15), rgba(4, 45, 92, 0) 90%);
                                }

                                .rdr-radio-marker {
                                    position: relative;
                                    width: 24px;
                                    height: 24px;
                                    border: 2px solid #cbd5e0;
                                    border-radius: 50%;
                                    margin-right: 15px;
                                    transition: all 0.3s ease;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                    background: #fff;
                                    flex-shrink: 0;
                                }

                                .rdr-radio-option input:checked+.rdr-radio-marker {
                                    border-color: var(--rdr-red);
                                    background: var(--rdr-red);
                                }

                                .rdr-x-mark {
                                    width: 100%;
                                    height: 100%;
                                    position: absolute;
                                    pointer-events: none;
                                    z-index: 10;
                                }

                                .rdr-x-path {
                                    fill: none;
                                    stroke: #fff;
                                    stroke-width: 10;
                                    stroke-linecap: round;
                                    stroke-dasharray: 60;
                                    stroke-dashoffset: 60;
                                }

                                .rdr-radio-option input:checked+.rdr-radio-marker .rdr-x-path {
                                    animation: rdr-slash 0.3s forwards;
                                }

                                .rdr-radio-option input:checked+.rdr-radio-marker .rdr-x-path.delay {
                                    animation-delay: 0.1s;
                                }

                                .rdr-label-text {
                                    color: var(--rdr-grey);
                                    font-weight: 600;
                                    /* text-transform: uppercase; */
                                    letter-spacing: 0.01em;
                                    font-size: 0.8em;
                                    transition: all 0.3s ease;
                                }

                                .rdr-radio-option input:checked~.rdr-label-text {
                                    color: var(--rdr-red);
                                    transform: translateX(5px);
                                }

                                /* --- Intake Card Styles --- */
                                #batches-list {
                                    display: grid;
                                    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                                    gap: 5px;
                                    margin-top: 6px;
                                }

                                .intake-card {
                                    position: relative;
                                    background: #fff;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 8px;
                                    padding: 10px;
                                    cursor: pointer;
                                    transition: all 0.2s ease;
                                    overflow: hidden;
                                    font-size: 0.9em;

                                }

                                .intake-card:hover:not(.disabled) {
                                    /* border-color: #d00000; */
                                    border-color: rgb(4 45 92) !important;
                                    /* background: #fff5f5; */
                                    background: #f5f7ffff;
                                    transform: translateY(-2px);
                                }

                                .intake-card.active-intake {
                                    border-color: rgb(4 45 92) !important;
                                    background: #f5f7ffff;
                                    box-shadow: 0 4px 10px rgba(4, 45, 92, 0.1);
                                }

                                .intake-card.disabled {
                                    opacity: 0.6;
                                    cursor: not-allowed;
                                    background: #f8fafc;
                                }

                                .intake-title {
                                    font-weight: 600;
                                    font-size: 0.95em;
                                    color: #334155;
                                    display: block;
                                    text-align: center;
                                }

                                .active-intake .intake-title {
                                    /* color: #d00000; */
                                    color: rgb(4 45 92) !important;
                                }

                                .intake-status-badge {
                                    position: absolute;
                                    top: 0;
                                    right: 0;
                                    background: #d00000;
                                    color: #fff;
                                    font-size: 9px;
                                    font-weight: 900;
                                    padding: 2px 6px;
                                    border-bottom-left-radius: 6px;
                                    text-transform: uppercase;
                                }

                                /* --- Payment Info Styling --- */
                                .payment-details-box {
                                    background: #fff;
                                    border: 1px solid #e2e8f0;
                                    border-left: 4px solid rgb(4 45 92) !important;
                                    border-radius: 6px;
                                    overflow: hidden;
                                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                                }

                                .payment-header {
                                    background: #fdfdfd;
                                    padding: 10px 15px;
                                    border-bottom: 1px solid #f1f5f9;
                                    font-weight: 800;
                                    color: #1e293b;
                                    font-size: 0.9em;
                                    text-transform: uppercase;
                                    letter-spacing: 0.05em;
                                }

                                @keyframes rdr-slash {
                                    to {
                                        stroke-dashoffset: 0;
                                    }
                                }

                                @media (max-width: 576px) {
                                    #batches-list {
                                        grid-template-columns: 1fr;
                                    }
                                }
                            </style>

                            <!-- Programme selection: Intakes now show directly below the selected program -->
                            <div class="col-12">
                                <label class="form-label d-block">Programme <span
                                        class="fw-bolder text-danger">*</span></label>
                                <div id="programme" class="selection-group-container">
                                    <?php
                                    $prog_sql = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
                                    $prog_result = $conn->query($prog_sql);

                                    $batches_by_program = [];
                                    $batches_display_info = [];
                                    $batches_batch_limits = [];
                                    $program_names = [];
                                    $prog_result2 = $conn->query($prog_sql);
                                    if ($prog_result2 && $prog_result2->num_rows > 0) {
                                        while ($row = $prog_result2->fetch_assoc()) {
                                            $program_names[$row['program_code']] = $row['program_name'];
                                        }
                                    }
                                    $current_date = date('Y-m-d');
                                    $batch_sql = "SELECT * FROM batch_table WHERE intake_end_date >= '$current_date' ORDER BY batch_name";
                                    $batch_result = $conn->query($batch_sql);
                                    if ($batch_result && $batch_result->num_rows > 0) {
                                        while ($row = $batch_result->fetch_assoc()) {
                                            $prog_code = $row['programme'];
                                            $batches_by_program[$prog_code][] = [
                                                'id' => $row['id'],
                                                'batch_name' => $row['batch_name'],
                                                'batch_intake' => $row['batch_intake'],
                                                'batch_limit' => $row['batch_limit']
                                            ];
                                            $batches_display_info[$row['id']] = [
                                                'batch_name' => $row['batch_name'],
                                                'batch_intake' => $row['batch_intake'],
                                                'batch_limit' => $row['batch_limit']
                                            ];
                                            $batches_batch_limits[$row['id']] = $row['batch_limit'];
                                        }
                                    }
                                    ?>
                                    <script>
                                        var batchesByProgram = <?php echo json_encode($batches_by_program); ?>;
                                        var programNames = <?php echo json_encode($program_names); ?>;
                                        var batchesDisplayInfo = <?php echo json_encode($batches_display_info); ?>;
                                        var batchesBatchLimits = <?php echo json_encode($batches_batch_limits); ?>;
                                    </script>
                                    <?php
                                    if ($prog_result && $prog_result->num_rows > 0) {
                                        while ($prog_row = $prog_result->fetch_assoc()) {
                                            $programCode = htmlspecialchars($prog_row['program_code']);
                                            $programName = htmlspecialchars($prog_row['program_name']);
                                            $checked = (isset($programme_id) && $programme_id == $prog_row['program_code']) ? 'checked' : '';
                                            echo '
                                                <div class="program-option-wrapper mb-2">
                                                    <label class="rdr-radio-option mb-0">
                                                        <input type="radio" name="program" id="program_' . $programCode . '" value="' . $programName . '" data-program-code="' . $programCode . '" required ' . $checked . ' />
                                                        <div class="rdr-radio-marker">
                                                            <svg class="rdr-x-mark" viewBox="0 0 100 100">
                                                                <path class="rdr-x-path" d="M25,25 L75,75"></path>
                                                                <path class="rdr-x-path delay" d="M75,25 L25,75"></path>
                                                            </svg>
                                                        </div>
                                                        <span class="rdr-label-text">' . $programName . '</span>
                                                    </label>
                                                    <div class="intake-reveal-container" id="intakes_for_' . $programCode . '" style="display:none; padding: 4px 0 10px 32px;">
                                                        <!-- Batches will be moved here -->
                                                    </div>
                                                </div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <!-- Shared batch wrapper that gets moved -->
                            <div id="batches-wrapper" style="display:none;">
                                <label class="form-label text-muted small fw-bold text-uppercase">Select Intake <span
                                        class="fw-bolder text-danger">*</span></label>
                                <div id="batches-list"></div>
                                <div class="mt-3">
                                    <small class="form-text batch-limit-small-note" id="batchLimitInfo"
                                        style="display:none;"></small>
                                    <small class="form-text batch-current-count-note" id="batchCountInfo"
                                        style="display:none;"></small>
                                </div>
                                <!-- Area to show payment batch allocation details moved inside wrapper -->
                                <div id="payment-batch-allocation-area" style="display:none;">
                                    <div class="payment-details-box mt-3 mb-3">
                                        <div class="payment-header">Payment/Fee Information</div>
                                        <div id="payment-batch-details-body" class="p-3">
                                            <!-- Populated by JS -->
                                        </div>
                                    </div>
                                </div>
                                <!-- Save & Continue button moved inside wrapper to show below payment info -->
                                <div class="tab-nav-buttons mt-3">
                                    <button type="button" id="to_tab2" class="btn btn-primary"
                                        style="background-color: rgb(4 45 92) !important; border-color: rgb(4 45 92) !important;">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        <span class="tab1-btn-text">Save &amp; Continue</span>
                                    </button>
                                </div>
                            </div>

                            <script>
                                // --- Real-time + periodic batch student count & batch FULL feature ---
                                var currentSelectedProgramName = null;
                                var currentSelectedBatchName = null;
                                var currentSelectedProgramId = null; // <-- Inserted
                                var currentSelectedBatchId = null; // <-- Inserted

                                // Store cache for batch counts across render session
                                var batchCountCache = {};
                                // Store batch statuses from interval polling
                                var liveBatchStatuses = {};

                                // Helper to fetch and pass count, for synchronous usage in renderBatches
                                function getBatchCountSync(programName, batchName) {
                                    var k = programName + "##" + batchName;
                                    return typeof batchCountCache[k] !== "undefined" ? batchCountCache[k] : null;
                                }

                                // Fetch single batch count
                                function fetchBatchCount(programName, batchName, cb) {
                                    var k = programName + "##" + batchName;
                                    if (typeof batchCountCache[k] !== "undefined") {
                                        cb(batchCountCache[k]);
                                        return;
                                    }
                                    $.ajax({
                                        url: 'register.php',
                                        method: 'GET',
                                        dataType: 'json',
                                        data: {
                                            ajax: 'batch_count',
                                            program: programName,
                                            batch: batchName
                                        },
                                        success: function (res) {
                                            batchCountCache[k] = typeof res.count !== "undefined" ? res.count : 0;
                                            cb(batchCountCache[k]);
                                        },
                                        error: function () {
                                            cb(null);
                                        }
                                    });
                                }

                                // Refresh all current batch statuses from server periodically (for ALL visible batches)
                                function fetchAllBatchStatuses(cb) {
                                    $.ajax({
                                        url: 'register.php',
                                        method: 'GET',
                                        data: {
                                            ajax: 'batch_statuses'
                                        },
                                        dataType: 'json',
                                        cache: false,
                                        success: function (res) {
                                            if (res.batches) {
                                                res.batches.forEach(function (batchObj) {
                                                    var k = batchObj.program_name + "##" + batchObj.batch_name;
                                                    batchCountCache[k] = batchObj.current_count;
                                                    liveBatchStatuses[batchObj.id] = batchObj;
                                                });
                                            }
                                            if (cb) cb();
                                        }
                                    });
                                }

                                // --- AJAX for payment_batch_allocation info ---
                                function fetchPaymentBatchDetails(progId, batchId, cb) {
                                    if (!progId || !batchId) {
                                        cb(null);
                                        return;
                                    }
                                    $.ajax({
                                        url: 'register.php',
                                        method: 'GET',
                                        data: {
                                            ajax: 'payment_batch_details',
                                            program_id: progId,
                                            batch_id: batchId
                                        },
                                        dataType: 'json',
                                        cache: false,
                                        success: function (res) {
                                            if (res.payment) {
                                                cb(res.payment);
                                            } else {
                                                cb(null);
                                            }
                                        },
                                        error: function () {
                                            cb(null);
                                        }
                                    });
                                }

                                // Function to render payment batch allocation nicely
                                function renderPaymentBatchDetails(payment) {
                                    var $area = $('#payment-batch-allocation-area');
                                    var $body = $('#payment-batch-details-body');
                                    if (!payment) {
                                        $area.hide();
                                        $body.html('');
                                        return;
                                    }

                                    var html = '<table class="table table-borderless table-sm mb-0">';
                                    html += '<tbody>';
                                    if (payment.uni_fee_gbp !== null && payment.uni_fee_gbp !== "" && Number(payment.uni_fee_gbp) > 0.00) html += "<tr><td class='text-muted small'>University Fee (GBP)</td><td class='fw-bold'>" + payment.uni_fee_gbp + "</td></tr>";
                                    if (payment.uni_fee_usd !== null && payment.uni_fee_usd !== "" && Number(payment.uni_fee_usd) > 0.00) html += "<tr><td class='text-muted small'>University Fee (USD)</td><td class='fw-bold'>" + payment.uni_fee_usd + "</td></tr>";
                                    if (payment.uni_fee_euro !== null && payment.uni_fee_euro !== "" && Number(payment.uni_fee_euro) > 0.00) html += "<tr><td class='text-muted small'>University Fee (Euro)</td><td class='fw-bold'>" + payment.uni_fee_euro + "</td></tr>";
                                    if (payment.course_fee_lkr !== null && payment.course_fee_lkr !== "") html += "<tr><td class='text-muted small'>Total Course Fee (LKR)</td><td class='fw-bold text-danger'>" + payment.course_fee_lkr + "</td></tr>";
                                    if (payment.registration_fee !== null && payment.registration_fee !== "") html += "<tr><td class='text-muted small'>Registration Fee (LKR)</td><td class='fw-bold'>" + payment.registration_fee + "</td></tr>";
                                    if (payment.only_course_fee !== null && payment.only_course_fee !== "") html += "<tr><td class='text-muted small'>Installment Amount (LKR)</td><td class='fw-bold'>" + payment.only_course_fee + "</td></tr>";
                                    if (payment.installment_no !== null && payment.installment_no !== "") html += "<tr><td class='text-muted small'>No. of Installments</td><td class='fw-bold'>" + payment.installment_no + "</td></tr>";
                                    html += '</tbody></table>';
                                    $body.html(html);
                                    $area.fadeIn(400);
                                }

                                // Periodic poll interval
                                var realtimePollInterval = 4000; // ms, can adjust as needed
                                var pollTimer = null;

                                function startLiveRealtimeBatchFullUpdater() {
                                    if (pollTimer) clearInterval(pollTimer);
                                    pollTimer = setInterval(function () {
                                        fetchAllBatchStatuses(function () {
                                            updateBatchRadioFullStatus();
                                            updateBatchCountDisplayLive();
                                        });
                                    }, realtimePollInterval);
                                }

                                // Helper: get program code from name
                                function getProgramCodeByName(programName) {
                                    for (const [code, name] of Object.entries(programNames)) {
                                        if (name === programName) return code;
                                    }
                                    return null;
                                }

                                // Update the list of batch radios (and their status) -- call after every poll
                                function updateBatchRadioFullStatus() {
                                    if (!$('#tab1').hasClass('active')) return;
                                    var radioList = $('#batches-list input[type=radio][name=batch]');
                                    radioList.each(function () {
                                        var $input = $(this);
                                        var $card = $input.closest('.intake-card');
                                        var batchId = $input.data('batch-id');
                                        if (!batchId) return true;
                                        var found = liveBatchStatuses[batchId];
                                        if (found) {
                                            var cnt = found.current_count;
                                            var lim = found.batch_limit;
                                            if (lim != null && lim !== "" && typeof lim !== "undefined" && parseInt(cnt) >= parseInt(lim)) {
                                                if (!$input.prop('disabled')) {
                                                    $input.prop('checked', false);
                                                    $input.prop('disabled', true);
                                                    $card.addClass('disabled');
                                                    if ($card.find('.intake-status-badge').length === 0) {
                                                        $card.append('<div class="intake-status-badge">Full</div>');
                                                    }
                                                    $card.removeClass('active-intake');
                                                }
                                            } else {
                                                $input.prop('disabled', false);
                                                $card.removeClass('disabled');
                                                $card.find('.intake-status-badge').remove();
                                            }
                                        }
                                    });
                                    if ($('#batches-list input[type=radio][name=batch]:enabled').length === 0) {
                                        $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                    } else if ($('#batches-list input[name="batch"]:checked').length > 0) {
                                        $('.tab1-btn-text').closest('.tab-nav-buttons').show();
                                    }
                                }

                                function updateBatchCountDisplayLive() {
                                    var $selBatch = $('#batches-list input[name="batch"]:checked');
                                    if ($selBatch.length) {
                                        var batch_name = $selBatch.val();
                                        var progName = currentSelectedProgramName || $('input[name="program"]:checked').val();
                                        var batchId = $selBatch.attr('id');
                                        batchId = batchId ? batchId.replace("batch_", "") : null;
                                        var count = null,
                                            limit = null;
                                        if (batchId && liveBatchStatuses[batchId]) {
                                            count = liveBatchStatuses[batchId].current_count;
                                            limit = liveBatchStatuses[batchId].batch_limit;
                                        }
                                        if (limit !== null && limit !== undefined && limit !== "") {
                                            let html = "Current batch intake limits: <b>" + limit + " student" + (parseInt(limit) == 1 ? "" : "s") + "</b>";
                                            if (count !== null && typeof count !== "undefined") {
                                                html += "<br>Current students registered for this batch: <b>" + count + "</b>";
                                                if (parseInt(count) >= parseInt(limit)) {
                                                    html += "<br><span class='batch-full-message'>This batch is <b>FULL</b> and cannot be selected.</span>";
                                                }
                                            }
                                            $('#batchLimitInfo').html(html).show();
                                            $('#batchCountInfo').html("").hide();
                                        }
                                    }
                                }

                                // Render the batch radios, hiding full batches
                                function renderBatches(progName) {
                                    const progCode = getProgramCodeByName(progName);
                                    const targetContainer = $('#intakes_for_' + progCode);
                                    const batchesWrapper = $('#batches-wrapper');
                                    const batchesList = $('#batches-list');

                                    // Move wrapper to the specific program item and show it
                                    $('.intake-reveal-container').hide().empty();
                                    if (targetContainer.length) {
                                        batchesWrapper.appendTo(targetContainer);
                                        targetContainer.show();
                                        batchesWrapper.show();
                                    }

                                    batchesList.html('<div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div>');

                                    if (progCode && batchesByProgram[progCode] && batchesByProgram[progCode].length > 0) {
                                        let sortedBatches = batchesByProgram[progCode].slice().sort(function (a, b) {
                                            let extractNumber = function (str) {
                                                if (!str) return null;
                                                let match = str.match(/\d+/);
                                                return match ? parseInt(match[0]) : null;
                                            };
                                            let numA = extractNumber(a.batch_intake) || extractNumber(a.batch_name);
                                            let numB = extractNumber(b.batch_intake) || extractNumber(b.batch_name);
                                            if (numA !== null && numB !== null) return numA - numB;
                                            return (a.batch_intake || a.batch_name || '').localeCompare(b.batch_intake || b.batch_name || '');
                                        });

                                        let lastTwoBatches = sortedBatches.slice(-3); // Show up to 3 for variety if available
                                        let pending = lastTwoBatches.length;
                                        let batchCounts = {};

                                        if (pending === 0) {
                                            batchesList.html('<div class="text-muted small">No active intakes found.</div>');
                                            $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                            return;
                                        }

                                        lastTwoBatches.forEach(function (batch) {
                                            fetchBatchCount(progName, batch.batch_name, function (theCount) {
                                                batchCounts[batch.id] = theCount;
                                                pending--;
                                                if (pending === 0) {
                                                    batchesList.empty();
                                                    lastTwoBatches.forEach(function (b) {
                                                        let count = batchCounts[b.id];
                                                        let isFull = (b.batch_limit && count !== null && parseInt(count) >= parseInt(b.batch_limit));

                                                        let card = $(`
                                                            <div class="intake-card ${isFull ? 'disabled' : ''}" data-batch-id="${b.id}" data-batch-name="${b.batch_name}">
                                                                <input type="radio" name="batch" id="batch_${b.id}" value="${b.batch_name}" 
                                                                    data-batch-limit="${b.batch_limit || ''}" data-batch-intake="${b.batch_intake}" 
                                                                    data-batch-id="${b.id}" required ${isFull ? 'disabled' : ''} style="display:none;">
                                                                <span class="intake-title">${b.batch_intake}</span>
                                                                ${isFull ? '<div class="intake-status-badge">Full</div>' : ''}
                                                            </div>
                                                        `);

                                                        batchesList.append(card);
                                                    });

                                                    fetchAllBatchStatuses(function () {
                                                        updateBatchRadioFullStatus();
                                                        updateBatchCountDisplayLive();
                                                        startLiveRealtimeBatchFullUpdater();
                                                    });
                                                }
                                            });
                                        });
                                    } else {
                                        batchesList.html('<div class="text-muted small">No intakes available for this program.</div>');
                                        $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                    }
                                }

                                document.addEventListener('DOMContentLoaded', function () {
                                    // Programme radio: update batches on change and reset batch count info
                                    const programRadios = document.querySelectorAll('input[name="program"]');
                                    let foundChecked = false;
                                    programRadios.forEach(function (radio) {
                                        radio.addEventListener('change', function () {
                                            if (this.checked) {
                                                currentSelectedProgramName = this.value;
                                                currentSelectedBatchName = null;
                                                var progCode = this.getAttribute('data-program-code');
                                                currentSelectedProgramId = progCode;
                                                renderBatches(this.value);
                                                $('#batchCountInfo').hide().html('');
                                                // Hide payment batch allocation box
                                                $('#payment-batch-allocation-area').hide().find("#payment-batch-details-body").html('');
                                                // You may log here if desired when program alone changed (no batch yet)
                                                // console.log('Selected Program ID:', progCode);
                                            }
                                        });
                                        if (radio.checked) {
                                            currentSelectedProgramName = radio.value;
                                            var progCode = radio.getAttribute('data-program-code');
                                            currentSelectedProgramId = progCode;
                                            renderBatches(radio.value);
                                            foundChecked = true;
                                        }
                                    });

                                    // Intake card click handler
                                    $(document).on('click', '.intake-card:not(.disabled)', function () {
                                        $('.intake-card').removeClass('active-intake');
                                        $(this).addClass('active-intake');
                                        $(this).find('input[name="batch"]').prop('checked', true).trigger('change');
                                    });

                                    // On batch radio change: show batch_limit and also show live batch student count
                                    $(document).on('change', 'input[name="batch"]', function () {
                                        var selBatchRadio = $('input[name="batch"]:checked');
                                        if (selBatchRadio.length > 0) {
                                            $('.tab1-btn-text').closest('.tab-nav-buttons').fadeIn(300);
                                        } else {
                                            $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                        }
                                        if (selBatchRadio.length) {
                                            var batch_limit = selBatchRadio.data('batch-limit');
                                            var batch_name = selBatchRadio.val();
                                            var batchId = selBatchRadio.data('batch-id');
                                            currentSelectedBatchName = batch_name;
                                            currentSelectedBatchId = batchId;

                                            var progRadio = $('input[name="program"]:checked');
                                            var progCode = progRadio.length ? progRadio.data('program-code') : null;
                                            currentSelectedProgramId = progCode;

                                            if (progCode && batchId) {
                                                console.log('Selected Program ID:', progCode, 'Selected Batch ID:', batchId);
                                                fetchPaymentBatchDetails(progCode, batchId, renderPaymentBatchDetails);
                                            } else {
                                                $('#payment-batch-allocation-area').hide().find("#payment-batch-details-body").html('');
                                            }

                                            fetchBatchCount(currentSelectedProgramName, currentSelectedBatchName, function (theCount) {
                                                let html = "";
                                                if (batch_limit) {
                                                    html = "Current batch intake limit: <b>" + batch_limit + " student" + (parseInt(batch_limit) == 1 ? "" : "s") + "</b>";
                                                    if (theCount !== null) {
                                                        html += "<br>Current students registered: <b>" + theCount + "</b>";
                                                    }
                                                }
                                                $('#batchLimitInfo').html(html).fadeIn(200);
                                            });
                                        } else {
                                            $('#batchLimitInfo').hide().html('');
                                            $('#payment-batch-allocation-area').hide();
                                        }
                                    });

                                    // Hide Save & Continue if there is NO batch radio after first load
                                    if (!foundChecked) {
                                        $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                    }
                                    if ($('#batches-list input[name="batch"]').length > 0 && $('#batches-list input[name="batch"]:checked').length === 0) {
                                        $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                                    }
                                });
                            </script>

                        </div>
                    </div>

                    <!-- Tab 2, Tab 3, Tab 4: unchanged as before -->
                    <?php // same as original, omitted for brevity 
                    ?>
                    <div class="tab-pane fade" id="tab2" role="tabpanel">
                        <div class="row g-3 pt-3">
                            <div class="col-md-6 col-12">
                                <label class="form-label">Date of Birth <span
                                        class="fw-bolder text-danger">*</span></label>
                                <input type="date" class="form-control" name="dob" required placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Gender <span class="fw-bolder text-danger">*</span></label>
                                <select class="form-control select2" name="gender" required>
                                    <option value="" disabled selected>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Nationality <span
                                        class="fw-bolder text-danger">*</span></label>
                                <select class="form-control select2" name="nationality" required id="nationality">
                                    <option value="">Select Nationality</option>
                                    <option value="Sri Lankan">Sri Lankan</option>
                                    <option value="Indian">Indian</option>
                                    <option value="British">British</option>
                                    <option value="American">American</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Identification Type <span
                                        class="fw-bolder text-danger">*</span></label>
                                <select class="form-select select2" id="id_type" name="id_type">
                                    <option value="nic" selected>NIC</option>
                                    <option value="passport">Passport</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-12" id="nic_field_container">
                                <label class="form-label">NIC <span class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="nic" id="nic_input"
                                    placeholder="National Identity Card Number">
                            </div>
                            <div class="col-md-6 col-12" id="passport_field_container" style="display:none;">
                                <label class="form-label">Passport <span class="fw-bolder text-danger">*</span></label>
                                <input type="text" class="form-control" name="passport" id="passport_input"
                                    placeholder="Passport number">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Mobile</label>
                                <div class="input-group">
                                    <select class="form-select select2" name="country_code" id="country_code"
                                        style="max-width: 120px;" required>
                                        <option value="">Code</option>
                                        <option value="+94" selected>+94 (LK)</option>
                                        <option value="+91">+91 (IN)</option>
                                        <option value="+44">+44 (UK)</option>
                                        <option value="+1">+1 (US)</option>
                                    </select>
                                    <input type="tel" class="form-control" name="mobile" pattern="[0-9]{6,15}"
                                        maxlength="15" minlength="6" required placeholder="Mobile number">
                                </div>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Home No</label>
                                <input type="tel" class="form-control" name="home_no" placeholder="Home number">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Office No</label>
                                <input type="tel" class="form-control" name="office_no" placeholder="Office number">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Email <span class="fw-bolder text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required
                                    placeholder="Enter your email">
                                <small class="form-text text-muted">
                                    Please ensure your email address is correct. All registration info, including your
                                    QR code, will be sent to this address.
                                </small>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">In case of emergency, contact person/number</label>
                                <input type="text" class="form-control" name="emergency_contact"
                                    placeholder="Name &amp; Number">
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Address for Correspondence</label>
                                <textarea class="form-control" name="current_address" id="current_address" rows="2"
                                    placeholder="Address, City"></textarea>
                            </div>
                            <div class="col-md-6 col-12">
                                <label class="form-label">Permanent Address</label>
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" value="" id="same_as_permanent">
                                    <label class="form-check-label" for="same_as_permanent">
                                        Same as Address for Correspondence
                                    </label>
                                </div>
                                <textarea class="form-control" name="permanent_address" id="permanent_address" rows="2"
                                    placeholder="Address, City"></textarea>
                            </div>
                            <div class="row mb-3 mt-5">
                                <div class="col-6 d-grid">
                                    <button type="button" id="to_tab1"
                                        class="btn bg-secondary text-white d-flex align-items-center justify-content-center">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        <span class="tab2-back-btn-text ms-2">Back</span>
                                    </button>
                                </div>
                                <div class="col-6 d-grid">
                                    <button type="button" id="to_tab3"
                                        class="btn btn-primary d-flex align-items-center justify-content-center"
                                        style="background: rgb(4, 45, 92) !important;">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        <span class="tab2-btn-text ms-2">Save &amp; Continue</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab3" role="tabpanel">
                        <div class="row g-3 pt-3">
                            <h5>G. C. E. O/L</h5>
                            <div class="col-md-4 col-12 mb-3">
                                <label class="form-label">Year</label>
                                <input type="text" class="form-control" name="ol_year" maxlength="9"
                                    placeholder="Example: 2018">
                            </div>
                            <div class="col-md-8 col-12 mb-3">
                                <label class="form-label">School</label>
                                <input type="text" class="form-control" name="ol_school" maxlength="150"
                                    placeholder="School Name">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label mb-1">Subjects &amp; Grades</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm edu-table">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th style="width:45%;">Subject</th>
                                                <th style="width:25%;">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ol_subjects_body">
                                            <?php for ($i = 1; $i <= 9; ++$i): ?>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="ol_subjects[]"
                                                            maxlength="100" placeholder="Subject <?= $i ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="ol_grades[]"
                                                            maxlength="3" placeholder="Grade">
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <h5>G. C. E. A/L</h5>
                            <div class="col-md-4 col-12 mb-3">
                                <label class="form-label">Year</label>
                                <input type="text" class="form-control" name="al_year" maxlength="9"
                                    placeholder="Example: 2021">
                            </div>
                            <div class="col-md-8 col-12 mb-3">
                                <label class="form-label">School</label>
                                <input type="text" class="form-control" name="al_school" maxlength="150"
                                    placeholder="School Name">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label mb-1">Subjects &amp; Grades</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm edu-table">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th style="width:45%;">Subject</th>
                                                <th style="width:25%;">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody id="al_subjects_body">
                                            <?php for ($i = 1; $i <= 3; ++$i): ?>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="al_subjects[]"
                                                            maxlength="100" placeholder="Subject <?= $i ?>">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="al_grades[]"
                                                            maxlength="3" placeholder="Grade">
                                                    </td>
                                                </tr>
                                            <?php endfor; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="row tab-nav-buttons">
                                <div class="row mb-3">

                                    <div class="col-6 d-grid">
                                        <button type="button" id="to_tab2" class="btn btn-secondary w-100">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            <span class="tab3-back-btn-text">Back</span>
                                        </button>
                                    </div>
                                    <div class="col-6 d-grid">
                                        <button type="button" id="to_tab4" class="btn btn-primary w-100"
                                            style="background-color: rgb(4 45 92) !important; border-color: rgb(4 45 92) !important;">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            <span class="tab3-btn-text ms-2">Save &amp; Continue</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab4" role="tabpanel">
                        <div class="row g-3 pt-3">
                            <h5>Academic / Professional Qualifications</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm edu-table">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th style="width: 30%;">Qualification</th>
                                            <th style="width: 30%;">Institution</th>
                                            <th style="width: 20%;">Year</th>
                                            <th>Other Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="acad_prof_qualifications_body">
                                        <?php for ($i = 1; $i <= 3; ++$i): ?>
                                            <tr>
                                                <td>
                                                    <label class="d-block d-sm-none mb-1 fw-bold">Qualification</label>
                                                    <input type="text" class="form-control" name="acad_qualification[]"
                                                        maxlength="100" placeholder="Qualification">
                                                </td>
                                                <td>
                                                    <label class="d-block d-sm-none mb-1 fw-bold">Institution</label>
                                                    <input type="text" class="form-control" name="acad_institution[]"
                                                        maxlength="100" placeholder="Institution">
                                                </td>
                                                <td>
                                                    <label class="d-block d-sm-none mb-1 fw-bold">Year</label>
                                                    <input type="text" class="form-control" name="acad_year[]" maxlength="9"
                                                        placeholder="Year">
                                                </td>
                                                <td>
                                                    <label class="d-block d-sm-none mb-1 fw-bold">Other Details</label>
                                                    <input type="text" class="form-control" name="acad_other[]"
                                                        maxlength="150" placeholder="Other Details">
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label mb-1">Other Qualifications</label>
                                <textarea class="form-control" name="other_qualifications" rows="3"
                                    placeholder="List any other relevant qualifications"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms"
                                        required>
                                    <label class="form-check-label" for="agree_terms">
                                        I agree to the <a href="terms_and_conditions.html" target="_blank">Terms and
                                            Conditions</a>
                                    </label>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 col-12 d-grid mb-2 mb-md-0">
                                    <button type="button" id="to_tab3" class="btn btn-secondary">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        <span class="tab4-back-btn-text">Back</span>
                                    </button>
                                </div>
                                <div class="col-md-6 col-12 d-grid">
                                    <button type="submit" id="registerBtn" class="btn btn-primary"
                                        style="background-color: rgb(4 45 92) !important; border-color: rgb(4 45 92) !important;">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        <span class="register-btn-text">Submit Registration</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function () {
            var isNicDuplicate = false;
            var isPassportDuplicate = false;

            // NIC Realtime Check
            $('input[name="nic"]').on('input', function () {
                var $this = $(this);
                var nic = $this.val().trim();
                // Clear any existing error immediately on typing
                $this.next('.nic-error-message').remove();
                $this.removeClass('is-invalid');
                isNicDuplicate = false; // temporary reset until ajax confirms

                if (nic.length > 0) {
                    clearTimeout($this.data('timeout'));
                    var t = setTimeout(function () {
                        $.ajax({
                            url: 'register.php',
                            method: 'GET',
                            data: { ajax: 'check_nic', nic: nic },
                            dataType: 'json',
                            success: function (res) {
                                if (res.exists) {
                                    isNicDuplicate = true;
                                    $this.addClass('is-invalid');
                                    if ($this.next('.nic-error-message').length === 0) {
                                        $this.after('<div class="invalid-feedback nic-error-message" style="display:block;">This NIC is already registered.</div>');
                                    }
                                } else {
                                    isNicDuplicate = false;
                                    $this.removeClass('is-invalid');
                                    $this.next('.nic-error-message').remove();
                                }
                            }
                        });
                    }, 500); // 500ms debounce
                    $this.data('timeout', t);
                }
            });

            // Passport Realtime Check
            $('input[name="passport"]').on('input', function () {
                var $this = $(this);
                var passport = $this.val().trim();
                // Clear any existing error immediately on typing
                $this.next('.passport-error-message').remove();
                $this.removeClass('is-invalid');
                isPassportDuplicate = false; // temporary reset until ajax confirms

                if (passport.length > 0) {
                    clearTimeout($this.data('timeout'));
                    var t = setTimeout(function () {
                        $.ajax({
                            url: 'register.php',
                            method: 'GET',
                            data: { ajax: 'check_passport', passport: passport },
                            dataType: 'json',
                            success: function (res) {
                                if (res.exists) {
                                    isPassportDuplicate = true;
                                    $this.addClass('is-invalid');
                                    if ($this.next('.passport-error-message').length === 0) {
                                        $this.after('<div class="invalid-feedback passport-error-message" style="display:block;">This Passport number is already registered.</div>');
                                    }
                                } else {
                                    isPassportDuplicate = false;
                                    $this.removeClass('is-invalid');
                                    $this.next('.passport-error-message').remove();
                                }
                            }
                        });
                    }, 500); // 500ms debounce
                    $this.data('timeout', t);
                }
            });

            // Prevent Enter key from submitting the form
            $('#registrationForm').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    return false;
                }
            });

            $('#title').select2({
                placeholder: "Select Title",
                width: '100%',
                allowClear: true
            });
            $('#nationality').select2({
                placeholder: "Select Nationality",
                width: '100%'
            });
            $('[name="gender"]').select2({
                placeholder: "Select Gender",
                width: '100%',
                allowClear: true
            });
            $('#country_code').select2({
                width: '100px',
                minimumResultsForSearch: -1
            });

            $('#id_type').select2({
                width: '100%',
                minimumResultsForSearch: -1
            });

            // Toggle identification fields
            $('#id_type').on('change', function () {
                var type = $(this).val();
                if (type === 'nic') {
                    $('#nic_field_container').show();
                    $('#passport_field_container').hide();
                    // Optional: clear passport if switching to NIC
                    $('input[name="passport"]').val('').removeClass('is-invalid').next('.passport-error-message').remove();
                    isPassportDuplicate = false;
                } else {
                    $('#nic_field_container').hide();
                    $('#passport_field_container').show();
                    // Optional: clear NIC if switching to Passport
                    $('input[name="nic"]').val('').removeClass('is-invalid').next('.nic-error-message').remove();
                    isNicDuplicate = false;
                }
            });

            function setTab(step) {
                if (step === 1) $('#tab1').addClass('show active').siblings('.tab-pane').removeClass('show active');
                else if (step === 2) $('#tab2').addClass('show active').siblings('.tab-pane').removeClass('show active');
                else if (step === 3) $('#tab3').addClass('show active').siblings('.tab-pane').removeClass('show active');
                else if (step === 4) $('#tab4').addClass('show active').siblings('.tab-pane').removeClass('show active');
                updateTabsProgress(step);
            }

            function updateTabsProgress(active) {
                $('#progressTabs .tab-step').removeClass('active completed');
                $('#progressTabs .tab-step').each(function (i) {
                    var step = $(this).data('step');
                    if (step < active) $(this).addClass('completed');
                    else if (step == active) $(this).addClass('active');
                });
            }
            let defaultStep = 1;
            if ($('#tab4').hasClass('show active')) defaultStep = 4;
            else if ($('#tab3').hasClass('show active')) defaultStep = 3;
            else if ($('#tab2').hasClass('show active')) defaultStep = 2;
            updateTabsProgress(defaultStep);
            $('#progressTabs .tab-step').css('pointer-events', 'none');

            function validateTab(tabNum) {
                let invalid = false;
                let $tab = $('#tab' + tabNum);
                $tab.find(':input').removeClass('is-invalid');
                if (tabNum == 1) {
                    const $title = $tab.find('[name="title"]');
                    const $fn = $tab.find('[name="firstname"]');
                    const $ln = $tab.find('[name="lastname"]');
                    const $program = $tab.find('input[name="program"]:checked');
                    const $batch = $tab.find('input[name="batch"]:checked');
                    if (!$title.val()) {
                        $title.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!$fn.val().trim()) {
                        $fn.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!$ln.val().trim()) {
                        $ln.addClass('is-invalid');
                        invalid = true;
                    }
                    if ($('#programme input[name="program"]').length && !$program.length) {
                        $('#programme').addClass('is-invalid');
                        invalid = true;
                    }
                    var batchWrapperVisible = $('#batches-wrapper').is(':visible');
                    if (batchWrapperVisible && !$batch.length) {
                        $('#batches-list').addClass('is-invalid');
                        invalid = true;
                    }
                } else if (tabNum == 2) {
                    const dob = $tab.find('[name="dob"]');
                    const gen = $tab.find('[name="gender"]');
                    const nat = $tab.find('[name="nationality"]');
                    const ccode = $tab.find('[name="country_code"]');
                    const mobile = $tab.find('[name="mobile"]');
                    const email = $tab.find('[name="email"]');

                    if (!dob.val()) {
                        dob.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!gen.val()) {
                        gen.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!nat.val()) {
                        nat.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!ccode.val()) {
                        ccode.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!mobile.val().trim()) {
                        mobile.addClass('is-invalid');
                        invalid = true;
                    }
                    if (!email.val().trim()) {
                        email.addClass('is-invalid');
                        invalid = true;
                    } else if (!/^\S+@\S+\.\S+$/.test(email.val())) {
                        email.addClass('is-invalid');
                        invalid = true;
                    }

                    // Strict identification check
                    var idType = $('#id_type').val();
                    if (idType === 'nic') {
                        var nicField = $('input[name="nic"]');
                        if (!nicField.val().trim()) {
                            nicField.addClass('is-invalid');
                            invalid = true;
                        } else if (isNicDuplicate) {
                            if (nicField.next('.nic-error-message').length === 0) {
                                nicField.after('<div class="invalid-feedback nic-error-message" style="display:block;">This NIC is already registered.</div>');
                            }
                            nicField.addClass('is-invalid');
                            invalid = true;
                        }
                    } else {
                        var passField = $('input[name="passport"]');
                        if (!passField.val().trim()) {
                            passField.addClass('is-invalid');
                            invalid = true;
                        } else if (isPassportDuplicate) {
                            if (passField.next('.passport-error-message').length === 0) {
                                passField.after('<div class="invalid-feedback passport-error-message" style="display:block;">This Passport number is already registered.</div>');
                            }
                            passField.addClass('is-invalid');
                            invalid = true;
                        }
                    }
                }

                if (invalid) {
                    let $first = $tab.find('.is-invalid').first();
                    if ($first.length) {
                        $('html,body').animate({
                            scrollTop: $first.offset().top - 90
                        }, 220);
                        $first.focus();
                    }
                }
                return !invalid;
            }

            $('#to_tab2').on('click', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true).addClass('btn-loading');
                btn.find('.tab1-btn-text').text('Saving...');
                var $progChecked = $('#programme input[name="program"]:checked');
                var batchRequired = $('#batches-list input[name="batch"]').length > 0;
                var $batchChecked = $('#batches-list input[name="batch"]:checked');
                if ($progChecked.length && (!batchRequired || $batchChecked.length)) {
                    if (validateTab(1)) {
                        setTimeout(function () {
                            btn.prop('disabled', false).removeClass('btn-loading');
                            btn.find('.tab1-btn-text').text('Save & Continue');
                            setTab(2);
                        }, 300);
                    } else {
                        btn.prop('disabled', false).removeClass('btn-loading');
                        btn.find('.tab1-btn-text').text('Save & Continue');
                    }
                } else {
                    btn.prop('disabled', false).removeClass('btn-loading');
                    btn.find('.tab1-btn-text').text('Save & Continue');
                }
            });

            $('#to_tab3').on('click', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true).addClass('btn-loading');
                btn.find('.tab2-btn-text').text('Saving...');
                if (validateTab(2)) {
                    setTimeout(function () {
                        btn.prop('disabled', false).removeClass('btn-loading');
                        btn.find('.tab2-btn-text').text('Save & Continue');
                        setTab(3);
                    }, 300);
                } else {
                    btn.prop('disabled', false).removeClass('btn-loading');
                    btn.find('.tab2-btn-text').text('Save & Continue');
                }
            });

            $('#to_tab4').on('click', function (e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true).addClass('btn-loading');
                btn.find('.tab3-btn-text').text('Saving...');
                setTimeout(function () {
                    btn.prop('disabled', false).removeClass('btn-loading');
                    btn.find('.tab3-btn-text').text('Save & Continue');
                    setTab(4);
                }, 300);
            });

            // Back buttons
            $('#to_tab1').on('click', function (e) {
                e.preventDefault();
                setTab(1);
            });

            $('#to_tab2').on('click', function (e) {
                if (!$(this).hasClass('btn-secondary')) return;
                e.preventDefault();
                setTab(2);
            });

            $('#to_tab3').on('click', function (e) {
                if (!$(this).hasClass('btn-secondary')) return;
                e.preventDefault();
                setTab(3);
            });

            $('#to_tab4').on('click', function (e) {
                if (!$(this).hasClass('btn-secondary')) return;
                e.preventDefault();
                setTab(4);
            });

            $('#tab3 .btn-secondary#to_tab2').on('click', function (e) {
                e.preventDefault();
                setTab(2);
            });

            $('#tab4 .btn-secondary#to_tab3').on('click', function (e) {
                e.preventDefault();
                setTab(3);
            });

            $("#registrationForm").on('submit', function (e) {
                var btn = $('#registerBtn');
                btn.prop('disabled', true).addClass('btn-loading');
                btn.find('.register-btn-text').text("Submitting...");
            });

            // Autofill name fields
            function updateFullName() {
                if ($('#fullname').data('auto') !== false) {
                    const fname = $('#firstname').val() || '';
                    const lname = $('#lastname').val() || '';
                    const full = fname.trim() + (fname && lname ? ' ' : '') + lname.trim();
                    $('#fullname').val(full.trim());
                }
                if ($('#certificate_name').data('auto') !== false) {
                    $('#certificate_name').val($('#fullname').val());
                }
            }
            $('#firstname, #lastname').on('input', function () {
                $('#fullname').data('auto', true);
                $('#certificate_name').data('auto', true);
                updateFullName();
            });
            $('#fullname').on('input', function () {
                $(this).data('auto', false);
            });
            $('#certificate_name').on('input', function () {
                $(this).data('auto', false);
            });
            $(document).ready(function () {
                $('#fullname').data('auto', true);
                $('#certificate_name').data('auto', true);
                updateFullName();

                // Auto-render batch section for pre-selected program
                var checkedProgram = $('input[name="program"]:checked');
                if (checkedProgram.length) {
                    var programVal = checkedProgram.val();
                    var radioElem = checkedProgram[0];
                    var progName = programVal;
                    // Render batches for checked
                    if (typeof renderBatches === 'function') {
                        renderBatches(progName);
                    }
                }

                // Hide Save & Continue if there is NO batch radio after first load
                var batchRequired = $('#batches-list input[name="batch"]').length > 0;
                if (batchRequired && $('#batches-list input[name="batch"]:checked').length === 0) {
                    $('.tab1-btn-text').closest('.tab-nav-buttons').hide();
                }

                var selBatchRadio = $('#batches-list input[name="batch"]:checked');
                if (selBatchRadio.length) {
                    var batch_limit = selBatchRadio.data('batch-limit');
                    var batch_name = selBatchRadio.val();
                    // Show count and limit BOTH at the same time
                    if (batch_limit !== undefined && batch_limit !== "") {
                        fetchBatchCount($('input[name="program"]:checked').val(), batch_name, function (count) {
                            let html = "";
                            html += "Current batch intake limit: <b>" + batch_limit + " student" + (parseInt(batch_limit) == 1 ? "" : "s") + "</b>";
                            if (count !== null && typeof count !== "undefined") {
                                html += "<br>Current students registered for this batch: <b>" + count + "</b>";
                                if (parseInt(count) >= parseInt(batch_limit)) {
                                    html += "<br><span class='batch-full-message'>This batch is <b>FULL</b> and cannot be selected.</span>";
                                }
                            }
                            // $('#batchLimitInfo').html(html).show();
                            $('#batchLimitInfo').html(html).hide();
                        });
                    }

                    // On load also show the count if program & batch selected
                    var programVal = $('input[name="program"]:checked').val();
                    var batchName = selBatchRadio.val();
                    if (programVal && batchName) {
                        updateBatchCountDisplay(programVal, batchName, batch_limit);
                    } else {
                        $('#batchCountInfo').hide().html('');
                    }
                } else {
                    $('#batchCountInfo').hide().html('');
                }
            });

            // Address copy logic
            $("#same_as_permanent").change(function () {
                if (this.checked) {
                    $('#permanent_address').val($('#current_address').val()).prop('readonly', true);
                } else {
                    $('#permanent_address').prop('readonly', false);
                }
            });
            $('#current_address').on('input', function () {
                if ($('#same_as_permanent').prop('checked')) {
                    $('#permanent_address').val($(this).val());
                }
            });

            // Terms checkbox validation
            const $registerBtn = $('#registerBtn');
            const $agreeTerms = $('#agree_terms');

            function toggleSubmitButton() {
                if ($agreeTerms.is(':checked')) {
                    $registerBtn.show();
                } else {
                    $registerBtn.hide();
                }
            }

            // Initial check
            toggleSubmitButton();

            // On change
            $agreeTerms.on('change', toggleSubmitButton);
        });
    </script>
</body>

</html>