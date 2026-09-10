<?php
require __DIR__ . '/_bootstrap.php';
// $conn is now a verified, connected mysqli instance, and $_SESSION['username'] is confirmed set.

$studentCode = isset($_GET['student_code']) ? (int) $_GET['student_code'] : 0;

if ($studentCode <= 0) {
    ajax_fail('Invalid student_code');
}

/* ---- 1. Student personal data ---- */
$stmt = $conn->prepare("SELECT student_code, title, first_name, last_name, preferred_name, student_status, nic, bms_email
                         FROM students WHERE student_code = ? LIMIT 1");
if (!$stmt) {
    ajax_fail('Prepare failed (students): ' . $conn->error);
}
$stmt->bind_param("i", $studentCode);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    ajax_fail('Student not found');
}
$student = $studentResult->fetch_assoc();
$stmt->close();

/* ---- 2. Active program(s) + batch(es) from allocate_programme ---- */
$stmt = $conn->prepare("SELECT ap.id AS allocate_id, ap.programme_code, ap.batch_id,
                                ap.student_registration_id, ap.new_student_registration_id, ap.status,
                                p.program_name, p.prog_code, p.university_id,
                                b.batch_name, b.batch_no, b.intake_no, b.year_batch_code,
                                b.intake_date, b.intake_end_date, b.year_no
                         FROM allocate_programme ap
                         INNER JOIN program_table p ON p.program_code = ap.programme_code
                         INNER JOIN batch_table b ON b.id = ap.batch_id
                         WHERE ap.student_code = ? AND ap.status = 'active'");
if (!$stmt) {
    ajax_fail('Prepare failed (allocate_programme): ' . $conn->error);
}
$stmt->bind_param("i", $studentCode);
$stmt->execute();
$progResult = $stmt->get_result();

$programs = [];
while ($prog = $progResult->fetch_assoc()) {
    $programmeCode = (int) $prog['programme_code'];
    $batchId = (int) $prog['batch_id'];

    /* ---- 3. Matching payment_withheld_table row for this student/program/batch ---- */
    $pwStmt = $conn->prepare("SELECT id, student_code, student_registration_id, program_id, batch_id,
                                      payment_status, due_count_bms_fees, due_count_uni_fees,
                                      last_payment_date
                               FROM payment_withheld_table
                               WHERE student_code = ? AND program_id = ? AND batch_id = ?
                               LIMIT 1");
    if (!$pwStmt) {
        ajax_fail('Prepare failed (payment_withheld_table): ' . $conn->error);
    }
    $studentCodeStr = (string) $studentCode;
    $pwStmt->bind_param("sii", $studentCodeStr, $programmeCode, $batchId);
    $pwStmt->execute();
    $pwResult = $pwStmt->get_result();
    $paymentWithheld = $pwResult->fetch_assoc(); // null if no existing record
    $pwStmt->close();

    $prog['payment_withheld'] = $paymentWithheld ? $paymentWithheld : null;
    $programs[] = $prog;
}
$stmt->close();

if (ob_get_length()) {
    ob_clean();
}
echo json_encode([
    'success'  => true,
    'student'  => $student,
    'programs' => $programs
]);
