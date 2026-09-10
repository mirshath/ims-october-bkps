<?php
session_start();
header('Content-Type: application/json');
include("../database/connection.php");

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$recordId      = isset($_POST['record_id']) ? (int) $_POST['record_id'] : 0;
$studentCode   = isset($_POST['student_code']) ? (string) $_POST['student_code'] : '';
$programId     = isset($_POST['program_id']) ? (int) $_POST['program_id'] : 0;
$batchId       = isset($_POST['batch_id']) ? (int) $_POST['batch_id'] : 0;
$paymentStatus = isset($_POST['payment_status']) ? trim($_POST['payment_status']) : 'active';
$dueBms        = isset($_POST['due_count_bms_fees']) && $_POST['due_count_bms_fees'] !== '' ? (int) $_POST['due_count_bms_fees'] : 0;
$dueUni        = isset($_POST['due_count_uni_fees']) && $_POST['due_count_uni_fees'] !== '' ? (int) $_POST['due_count_uni_fees'] : 0;
$lastPayment   = isset($_POST['last_payment_date']) && $_POST['last_payment_date'] !== '' ? $_POST['last_payment_date'] : null;

if ($studentCode === '' || $programId <= 0 || $batchId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

if (!in_array($paymentStatus, ['active', 'withheld'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment_status']);
    exit();
}

// Normalize datetime-local (YYYY-MM-DDTHH:MM) to MySQL DATETIME
if ($lastPayment !== null) {
    $lastPayment = str_replace('T', ' ', $lastPayment);
    if (strlen($lastPayment) === 16) {
        $lastPayment .= ':00';
    }
}

/* Determine whether to UPDATE (existing row) or INSERT (new row) */
if ($recordId <= 0) {
    $checkStmt = $conn->prepare("SELECT id FROM payment_withheld_table
                                  WHERE student_code = ? AND program_id = ? AND batch_id = ? LIMIT 1");
    $checkStmt->bind_param("sii", $studentCode, $programId, $batchId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($row = $checkResult->fetch_assoc()) {
        $recordId = (int) $row['id'];
    }
    $checkStmt->close();
}

if ($recordId > 0) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE payment_withheld_table
                             SET payment_status = ?, due_count_bms_fees = ?, due_count_uni_fees = ?, last_payment_date = ?
                             WHERE id = ?");
    $stmt->bind_param("siisi", $paymentStatus, $dueBms, $dueUni, $lastPayment, $recordId);
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
        exit();
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Record updated', 'record_id' => $recordId]);
    exit();
} else {
    // INSERT — pull the registration id from allocate_programme for convenience
    $regId = null;
    $regStmt = $conn->prepare("SELECT student_registration_id, new_student_registration_id
                                FROM allocate_programme
                                WHERE student_code = ? AND programme_code = ? AND batch_id = ? AND status = 'active'
                                LIMIT 1");
    $regStmt->bind_param("sii", $studentCode, $programId, $batchId);
    $regStmt->execute();
    $regResult = $regStmt->get_result();
    if ($regRow = $regResult->fetch_assoc()) {
        $regId = !empty($regRow['new_student_registration_id'])
            ? $regRow['new_student_registration_id']
            : $regRow['student_registration_id'];
    }
    $regStmt->close();

    $stmt = $conn->prepare("INSERT INTO payment_withheld_table
                             (student_code, student_registration_id, program_id, batch_id, payment_status,
                              due_count_bms_fees, due_count_uni_fees, last_payment_date)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisiis", $studentCode, $regId, $programId, $batchId, $paymentStatus, $dueBms, $dueUni, $lastPayment);
    $ok = $stmt->execute();

    if (!$ok) {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
        exit();
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Record created', 'record_id' => $newId]);
    exit();
}
