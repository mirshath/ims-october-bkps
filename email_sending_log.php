<?php
// Enable error reporting during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Global rolling date filter parameters (Past 10 days)
$end_date   = date('Y-m-d 23:59:59');
$start_date = date('Y-m-d 00:00:00', strtotime('-1 days'));

// ==========================================
// TAB 1 QUERY: Email Sending Log
// ==========================================
$sql1 = "SELECT 
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
            grp.group_latest_date,
            IFNULL(cnt.active_count, 0) AS active_count
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
        LEFT JOIN (
            SELECT programme_code, batch_id, COUNT(*) AS active_count
            FROM allocate_programme
            WHERE status = 'active'
            GROUP BY programme_code, batch_id
        ) cnt ON esl.program_id = cnt.programme_code AND esl.batch_id = cnt.batch_id
        WHERE esl.sent_date BETWEEN ? AND ?
        ORDER BY grp.group_latest_date DESC, esl.sent_date DESC";

$stmt1 = mysqli_prepare($conn, $sql1);
if (!$stmt1) {
    die("Query 1 Preparation Failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt1, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt1);
$result1 = mysqli_stmt_get_result($stmt1);

$groups1 = [];
$groupOrder1 = [];

while ($row = mysqli_fetch_assoc($result1)) {
    $groupKey = $row['program_id'] . '_' . $row['batch_id'];
    if (!isset($groups1[$groupKey])) {
        $groups1[$groupKey] = [
            'program_name'  => $row['program_name'] ?? 'Unknown Program',
            'batch_name'    => $row['batch_name'] ?? 'Unknown Batch',
            'active_count'  => $row['active_count'],
            'rows'          => []
        ];
        $groupOrder1[] = $groupKey;
    }
    $groups1[$groupKey]['rows'][] = $row;
}

foreach ($groups1 as $groupKey => &$group) {
    $regCounts = [];
    foreach ($group['rows'] as $row) {
        $regId = $row['student_registration_id'];
        if ($regId !== null && $regId !== '') {
            $regCounts[$regId] = ($regCounts[$regId] ?? 0) + 1;
        }
    }
    $group['reg_counts'] = $regCounts;
}
unset($group);


// ==========================================
// TAB 2 QUERY: Assessment Email Log
// ==========================================
$sql2 = "SELECT 
            ael.id,
            ael.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            a.programme_id AS program_id,
            p.program_name,
            a.batch_id,
            b.batch_name,
            a.module_id,
            m.module_name,
            ael.assessment_id,
            a.description AS assessment_name,
            a.main_component_id,
            ac.as_main_component_name AS main_component_name,
            a.sub_component_id,
            sac.sub_component_name AS sub_component_name,
            ap.student_registration_id,
            ael.sent_date,
            ael.sent_by,
            ael.status,
            ael.error_message,
            grp.group_latest_date,
            IFNULL(cnt.active_count, 0) AS active_count
        FROM assessment_email_log ael
        INNER JOIN assessments a ON ael.assessment_id = a.id
        LEFT JOIN students s ON ael.student_id = s.student_code
        LEFT JOIN program_table p ON a.programme_id = p.program_code
        LEFT JOIN batch_table b ON a.batch_id = b.id
        LEFT JOIN modules m ON a.module_id = m.id
        LEFT JOIN assignment_components ac ON a.main_component_id = ac.id
        LEFT JOIN sub_assign_components sac ON a.sub_component_id = sac.id
        LEFT JOIN allocate_programme ap 
            ON ael.student_id = ap.student_code 
           AND a.programme_id = ap.programme_code 
           AND a.batch_id = ap.batch_id
        INNER JOIN (
            SELECT a2.programme_id AS program_id, a2.batch_id AS batch_id, MAX(ael2.sent_date) AS group_latest_date
            FROM assessment_email_log ael2
            INNER JOIN assessments a2 ON ael2.assessment_id = a2.id
            WHERE ael2.sent_date BETWEEN ? AND ?
            GROUP BY a2.programme_id, a2.batch_id
        ) grp ON a.programme_id = grp.program_id AND a.batch_id = grp.batch_id
        LEFT JOIN (
            SELECT programme_code, batch_id, COUNT(*) AS active_count
            FROM allocate_programme
            WHERE status = 'active'
            GROUP BY programme_code, batch_id
        ) cnt ON a.programme_id = cnt.programme_code AND a.batch_id = cnt.batch_id
        WHERE ael.sent_date BETWEEN ? AND ?
        ORDER BY grp.group_latest_date DESC, ael.sent_date DESC";

$stmt2 = mysqli_prepare($conn, $sql2);
if (!$stmt2) {
    die("Query 2 Preparation Failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt2, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

$groups2 = [];
$groupOrder2 = [];

while ($row = mysqli_fetch_assoc($result2)) {
    $groupKey = $row['program_id'] . '_' . $row['batch_id'];
    if (!isset($groups2[$groupKey])) {
        $groups2[$groupKey] = [
            'program_name'  => $row['program_name'] ?? 'Unknown Program',
            'batch_name'    => $row['batch_name'] ?? 'Unknown Batch',
            'active_count'  => $row['active_count'],
            'rows'          => []
        ];
        $groupOrder2[] = $groupKey;
    }
    $groups2[$groupKey]['rows'][] = $row;
}

foreach ($groups2 as $groupKey => &$group) {
    $regCounts = [];
    foreach ($group['rows'] as $row) {
        $regId = $row['student_registration_id'];
        if ($regId !== null && $regId !== '') {
            $regCounts[$regId] = ($regCounts[$regId] ?? 0) + 1;
        }
    }
    $group['reg_counts'] = $regCounts;
}
unset($group);


// ==========================================
// TAB 3 QUERY: Assessment Document Send Email Log
// ==========================================
$sql3 = "SELECT 
            adsl.id,
            adsl.student_id,
            CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            a.programme_id AS program_id,
            p.program_name,
            a.batch_id,
            b.batch_name,
            a.module_id,
            m.module_name,
            adsl.assessment_id,
            a.description AS assessment_name,
            a.main_component_id,
            ac.as_main_component_name AS main_component_name,
            a.sub_component_id,
            sac.sub_component_name AS sub_component_name,
            IFNULL(ap.student_registration_id, adsl.student_registration_id) AS student_registration_id,
            adsl.sent_date,
            adsl.sent_by,
            adsl.status,
            adsl.error_message,
            grp.group_latest_date,
            IFNULL(cnt.active_count, 0) AS active_count
        FROM assesment_document_send_email_log adsl
        INNER JOIN assessments a ON adsl.assessment_id = a.id
        LEFT JOIN students s ON adsl.student_id = s.student_code
        LEFT JOIN program_table p ON a.programme_id = p.program_code
        LEFT JOIN batch_table b ON a.batch_id = b.id
        LEFT JOIN modules m ON a.module_id = m.id
        LEFT JOIN assignment_components ac ON a.main_component_id = ac.id
        LEFT JOIN sub_assign_components sac ON a.sub_component_id = sac.id
        LEFT JOIN allocate_programme ap 
            ON adsl.student_id = ap.student_code 
           AND a.programme_id = ap.programme_code 
           AND a.batch_id = ap.batch_id
        INNER JOIN (
            SELECT a2.programme_id AS program_id, a2.batch_id AS batch_id, MAX(adsl2.sent_date) AS group_latest_date
            FROM assesment_document_send_email_log adsl2
            INNER JOIN assessments a2 ON adsl2.assessment_id = a2.id
            WHERE adsl2.sent_date BETWEEN ? AND ?
            GROUP BY a2.programme_id, a2.batch_id
        ) grp ON a.programme_id = grp.program_id AND a.batch_id = grp.batch_id
        LEFT JOIN (
            SELECT programme_code, batch_id, COUNT(*) AS active_count
            FROM allocate_programme
            WHERE status = 'active'
            GROUP BY programme_code, batch_id
        ) cnt ON a.programme_id = cnt.programme_code AND a.batch_id = cnt.batch_id
        WHERE adsl.sent_date BETWEEN ? AND ?
        ORDER BY grp.group_latest_date DESC, adsl.sent_date DESC";

$stmt3 = mysqli_prepare($conn, $sql3);
if (!$stmt3) {
    die("Query 3 Preparation Failed: " . mysqli_error($conn));
}
mysqli_stmt_bind_param($stmt3, "ssss", $start_date, $end_date, $start_date, $end_date);
mysqli_stmt_execute($stmt3);
$result3 = mysqli_stmt_get_result($stmt3);

$groups3 = [];
$groupOrder3 = [];

while ($row = mysqli_fetch_assoc($result3)) {
    $groupKey = $row['program_id'] . '_' . $row['batch_id'];
    if (!isset($groups3[$groupKey])) {
        $groups3[$groupKey] = [
            'program_name'  => $row['program_name'] ?? 'Unknown Program',
            'batch_name'    => $row['batch_name'] ?? 'Unknown Batch',
            'active_count'  => $row['active_count'],
            'rows'          => []
        ];
        $groupOrder3[] = $groupKey;
    }
    $groups3[$groupKey]['rows'][] = $row;
}

foreach ($groups3 as $groupKey => &$group) {
    $regCounts = [];
    foreach ($group['rows'] as $row) {
        $regId = $row['student_registration_id'];
        if ($regId !== null && $regId !== '') {
            $regCounts[$regId] = ($regCounts[$regId] ?? 0) + 1;
        }
    }
    $group['reg_counts'] = $regCounts;
}
unset($group);
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3">
                <!-- Navigation Tabs Header -->
                <ul class="nav nav-tabs mb-4" id="logTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active font-weight-bold" id="email-log-tab" data-bs-toggle="tab" data-bs-target="#email-log" type="button" role="tab" aria-controls="email-log" aria-selected="true">
                            <i class="fas fa-envelope me-2"></i>Email Send Log
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-weight-bold" id="assessment-log-tab" data-bs-toggle="tab" data-bs-target="#assessment-log" type="button" role="tab" aria-controls="assessment-log" aria-selected="false">
                            <i class="fas fa-tasks me-2"></i>Assessment Send Log
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-weight-bold" id="assessment-doc-log-tab" data-bs-toggle="tab" data-bs-target="#assessment-doc-log" type="button" role="tab" aria-controls="assessment-doc-log" aria-selected="false">
                            <i class="fas fa-file-alt me-2"></i>Assessment Document Send Log
                        </button>
                    </li>
                </ul>

                <!-- Navigation Tabs Content -->
                <div class="tab-content" id="logTabsContent">

                    <!-- TAB 1: Email Send Log -->
                    <div class="tab-pane fade show active" id="email-log" role="tabpanel" aria-labelledby="email-log-tab">
                        <?php if (empty($groups1)): ?>
                            <div class="row mb-5">
                                <div class="col-md-12">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No email dispatch records found within the past 01 days.</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php $tableIndex = 0; ?>
                            <?php foreach ($groupOrder1 as $groupKey): ?>
                                <?php
                                $group = $groups1[$groupKey];
                                $tableId = 'studentsTable_' . $tableIndex;
                                ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card shadow-sm border-0">
                                            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-layer-group"></i>
                                                    </span> &nbsp;&nbsp;&nbsp;
                                                    <h6 class="mb-0 font-weight-bold text-dark">
                                                        <?= htmlspecialchars($group['program_name']) ?>
                                                        <span class="text-muted">|</span>
                                                        <?= htmlspecialchars($group['batch_name']) ?>
                                                    </h6>
                                                </div>
                                                <div>
                                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                                        <i class="fas fa-users me-1"></i> Active Enrolled: <?= htmlspecialchars($group['active_count']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table id="<?= $tableId ?>" class="table table-striped table-bordered group-table" style="width: 100%; font-size: 11px;">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Student Name</th>
                                                                <th>Reg. ID</th>
                                                                <th>Module</th>
                                                                <th>Main Component</th>
                                                                <th>Sub Component</th>
                                                                <th>Email Status</th>
                                                                <th>Sent Date</th>
                                                                <th>Sent By</th>
                                                                <th>State</th>
                                                                <th>Error Log</th>
                                                                <th>Emailed Result</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($group['rows'] as $row): ?>
                                                                <?php
                                                                $regId = $row['student_registration_id'];
                                                                $isDuplicate = ($regId !== null && $regId !== '' && ($group['reg_counts'][$regId] ?? 0) > 1);
                                                                ?>
                                                                <tr class="<?= $isDuplicate ? 'table-warning' : '' ?>">
                                                                    <td class="font-weight-bold"><?= htmlspecialchars($row['id']) ?></td>
                                                                    <td><?= htmlspecialchars(trim($row['student_name']) !== '' ? trim($row['student_name']) : '-') ?></td>
                                                                    <td>
                                                                        <?php if ($isDuplicate): ?>
                                                                            <span class="badge bg-warning text-dark" title="Multiple notifications logged for this student in current batch">
                                                                                <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($regId) ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <?= htmlspecialchars($regId ?? '-') ?>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['module_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['main_component_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sub_component_name'] ?? '-') ?></td>
                                                                    <td>
                                                                        <?php if ($row['email_sent']): ?>
                                                                            <span class="text-success font-weight-bold"><i class="fas fa-check me-1"></i>Yes</span>
                                                                        <?php else: ?>
                                                                            <span class="text-secondary"><i class="fas fa-times me-1"></i>No</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['sent_date'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sent_by'] ?? '-') ?></td>
                                                                    <td>
                                                                        <?php if ($row['status'] === 'sent'): ?>
                                                                            <span class="badge bg-success"><i class="fas fa-paper-plane me-1"></i>Sent</span>
                                                                        <?php elseif ($row['status'] === 'failed'): ?>
                                                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($row['error_message'])): ?>
                                                                            <span class="text-danger small" title="<?= htmlspecialchars($row['error_message']) ?>">
                                                                                <?= htmlspecialchars(substr($row['error_message'], 0, 30)) ?><?= strlen($row['error_message']) > 30 ? '...' : '' ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['emailed_result'] ?? '-') ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $tableIndex++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 2: Assessment Send Log -->
                    <div class="tab-pane fade" id="assessment-log" role="tabpanel" aria-labelledby="assessment-log-tab">
                        <?php if (empty($groups2)): ?>
                            <div class="row mb-5">
                                <div class="col-md-12">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No assessment dispatch records found within the past 01 days.</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($groupOrder2 as $groupKey): ?>
                                <?php
                                $group = $groups2[$groupKey];
                                $tableId = 'assessmentTable_' . $tableIndex;
                                ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card shadow-sm border-0">
                                            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-layer-group"></i>
                                                    </span> &nbsp;&nbsp;&nbsp;
                                                    <h6 class="mb-0 font-weight-bold text-dark">
                                                        <?= htmlspecialchars($group['program_name']) ?>
                                                        <span class="text-muted">|</span>
                                                        <?= htmlspecialchars($group['batch_name']) ?>
                                                    </h6>
                                                </div>
                                                <div>
                                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                                        <i class="fas fa-users me-1"></i> Active Enrolled: <?= htmlspecialchars($group['active_count']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table id="<?= $tableId ?>" class="table table-striped table-bordered group-table" style="width: 100%; font-size: 11px;">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Student Name</th>
                                                                <th>Reg. ID</th>
                                                                <th>Module</th>
                                                                <th>Assessment</th>
                                                                <th>Main Component</th>
                                                                <th>Sub Component</th>
                                                                <th>Sent Date</th>
                                                                <th>Sent By</th>
                                                                <th>State</th>
                                                                <th>Error Log</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($group['rows'] as $row): ?>
                                                                <?php
                                                                $regId = $row['student_registration_id'];
                                                                $isDuplicate = ($regId !== null && $regId !== '' && ($group['reg_counts'][$regId] ?? 0) > 1);
                                                                ?>
                                                                <tr class="<?= $isDuplicate ? 'table-warning' : '' ?>">
                                                                    <td class="font-weight-bold"><?= htmlspecialchars($row['id']) ?></td>
                                                                    <td><?= htmlspecialchars(trim($row['student_name']) !== '' ? trim($row['student_name']) : '-') ?></td>
                                                                    <td>
                                                                        <?php if ($isDuplicate): ?>
                                                                            <span class="badge bg-warning text-dark" title="Multiple notifications logged for this student in current batch">
                                                                                <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($regId) ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <?= htmlspecialchars($regId ?? '-') ?>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['module_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['assessment_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['main_component_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sub_component_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sent_date'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sent_by'] ?? '-') ?></td>
                                                                    <td>
                                                                        <?php if ($row['status'] === 'sent'): ?>
                                                                            <span class="badge bg-success"><i class="fas fa-paper-plane me-1"></i>Sent</span>
                                                                        <?php elseif ($row['status'] === 'failed'): ?>
                                                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($row['error_message'])): ?>
                                                                            <span class="text-danger small" title="<?= htmlspecialchars($row['error_message']) ?>">
                                                                                <?= htmlspecialchars(substr($row['error_message'], 0, 30)) ?><?= strlen($row['error_message']) > 30 ? '...' : '' ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $tableIndex++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 3: Assessment Document Send Log -->
                    <div class="tab-pane fade" id="assessment-doc-log" role="tabpanel" aria-labelledby="assessment-doc-log-tab">
                        <?php if (empty($groups3)): ?>
                            <div class="row mb-5">
                                <div class="col-md-12">
                                    <div class="card shadow-sm border-0">
                                        <div class="card-body text-center py-5">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No assessment document dispatch records found within the past 01 days.</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($groupOrder3 as $groupKey): ?>
                                <?php
                                $group = $groups3[$groupKey];
                                $tableId = 'assessmentDocTable_' . $tableIndex;
                                ?>
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="card shadow-sm border-0">
                                            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                                                <div class="d-flex align-items-center">
                                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-layer-group"></i>
                                                    </span> &nbsp;&nbsp;&nbsp;
                                                    <h6 class="mb-0 font-weight-bold text-dark">
                                                        <?= htmlspecialchars($group['program_name']) ?>
                                                        <span class="text-muted">|</span>
                                                        <?= htmlspecialchars($group['batch_name']) ?>
                                                    </h6>
                                                </div>
                                                <div>
                                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                                        <i class="fas fa-users me-1"></i> Active Enrolled: <?= htmlspecialchars($group['active_count']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table id="<?= $tableId ?>" class="table table-striped table-bordered group-table" style="width: 100%; font-size: 11px;">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Student Name</th>
                                                                <th>Reg. ID</th>
                                                                <th>Module</th>
                                                                <th>Assessment</th>
                                                                <th>Main Component</th>
                                                                <th>Sub Component</th>
                                                                <th>Sent Date</th>
                                                                <th>Sent By</th>
                                                                <th>State</th>
                                                                <th>Error Log</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($group['rows'] as $row): ?>
                                                                <?php
                                                                $regId = $row['student_registration_id'];
                                                                $isDuplicate = ($regId !== null && $regId !== '' && ($group['reg_counts'][$regId] ?? 0) > 1);
                                                                ?>
                                                                <tr class="<?= $isDuplicate ? 'table-warning' : '' ?>">
                                                                    <td class="font-weight-bold"><?= htmlspecialchars($row['id']) ?></td>
                                                                    <td><?= htmlspecialchars(trim($row['student_name']) !== '' ? trim($row['student_name']) : '-') ?></td>
                                                                    <td>
                                                                        <?php if ($isDuplicate): ?>
                                                                            <span class="badge bg-warning text-dark" title="Multiple notifications logged for this student in current batch">
                                                                                <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($regId) ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <?= htmlspecialchars($regId ?? '-') ?>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['module_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['assessment_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['main_component_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sub_component_name'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sent_date'] ?? '-') ?></td>
                                                                    <td><?= htmlspecialchars($row['sent_by'] ?? '-') ?></td>
                                                                    <td>
                                                                        <?php if ($row['status'] === 'sent'): ?>
                                                                            <span class="badge bg-success"><i class="fas fa-paper-plane me-1"></i>Sent</span>
                                                                        <?php elseif ($row['status'] === 'failed'): ?>
                                                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Failed</span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-secondary">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($row['error_message'])): ?>
                                                                            <span class="text-danger small" title="<?= htmlspecialchars($row['error_message']) ?>">
                                                                                <?= htmlspecialchars(substr($row['error_message'], 0, 30)) ?><?= strlen($row['error_message']) > 30 ? '...' : '' ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">-</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php $tableIndex++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('.group-table').each(function() {
            $(this).DataTable({
                "order": [],
                "pageLength": 25,
                "lengthMenu": [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Filter records..."
                }
            });
        });

        // Bootstrap's own Tab plugin (bootstrap.bundle.min.js) is what normally
        // makes data-bs-toggle="tab" buttons clickable. This page doesn't load
        // it (only jQuery/Select2/DataTables are included above), so clicking
        // the tabs did nothing. This handler switches tabs itself and doesn't
        // depend on Bootstrap's JS being present at all, so it works either way.
        $('#logTabs button[data-bs-toggle="tab"]').on('click', function(e) {
            e.preventDefault();

            var $clickedBtn = $(this);
            var targetSelector = $clickedBtn.attr('data-bs-target');

            // Toggle the tab buttons
            $('#logTabs button').removeClass('active').attr('aria-selected', 'false');
            $clickedBtn.addClass('active').attr('aria-selected', 'true');

            // Toggle the tab panes
            $('#logTabsContent .tab-pane').removeClass('show active');
            $(targetSelector).addClass('show active');

            // Re-adjust DataTables column widths now that the pane is visible,
            // since a table initialized while hidden gets a 0-width layout.
            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });
    });
</script>
</body>

</html>