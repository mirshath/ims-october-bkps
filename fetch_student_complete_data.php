<?php
session_start();
include("database/connection.php");

if (!isset($_POST['student_code'])) {
    echo json_encode(['success' => false, 'message' => 'Student code missing']);
    exit();
}

$studentCode = mysqli_real_escape_string($conn, $_POST['student_code']);

$response = [
    'success' => true,
    'data' => []
];

// 1. students (Basic Profile)
$res = mysqli_query($conn, "SELECT * FROM students WHERE student_code = '$studentCode'");
$response['data']['students'] = mysqli_fetch_assoc($res);

// 2. allocate_programme (Joined with program and batch names)
$query = "SELECT ap.*, pt.program_name, bt.batch_name 
          FROM allocate_programme ap
          LEFT JOIN program_table pt ON ap.programme_code = pt.program_code
          LEFT JOIN batch_table bt ON ap.batch_id = bt.id
          WHERE ap.student_code = '$studentCode'";
$res = mysqli_query($conn, $query);
$response['data']['allocate_programme'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['allocate_programme'][] = $row;
}

// 3. add_payment_plan_table
$res = mysqli_query($conn, "SELECT * FROM add_payment_plan_table WHERE student_id = '$studentCode'");
$response['data']['add_payment_plan_table'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['add_payment_plan_table'][] = $row;
}

// 4. installment_payment_table
$res = mysqli_query($conn, "SELECT * FROM installment_payment_table WHERE student_id = '$studentCode'");
$response['data']['installment_payment_table'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['installment_payment_table'][] = $row;
}

// 5. installment_details_table
$res = mysqli_query($conn, "SELECT * FROM installment_details_table WHERE student_id = '$studentCode'");
$response['data']['installment_details_table'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['installment_details_table'][] = $row;
}

// 6. payment_due_tables
$res = mysqli_query($conn, "SELECT * FROM payment_due_tables WHERE student_code = '$studentCode'");
$response['data']['payment_due_tables'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['payment_due_tables'][] = $row;
}

// 7. payment_withheld_table (Joined with program and batch names)
$query = "SELECT pw.*, pt.program_name, bt.batch_name 
          FROM payment_withheld_table pw
          LEFT JOIN program_table pt ON pw.program_id = pt.program_code
          LEFT JOIN batch_table bt ON pw.batch_id = bt.id
          WHERE pw.student_code = '$studentCode'";
$res = mysqli_query($conn, $query);
$response['data']['payment_withheld_table'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['payment_withheld_table'][] = $row;
}

// 8. payment_wise_info (Actual installment-wise payment transactions)
// Fetch by program_batch + paid_date first (grouping), then re-order rows so
// "Initial Payment" always comes first, followed by installment_1, installment_2, ... installment_x
// in correct NUMERIC order (a plain SQL ORDER BY would sort "installment_10" before "installment_2").
$res = mysqli_query($conn, "SELECT * FROM payment_wise_info WHERE student_id = '$studentCode' ORDER BY program_batch ASC, paid_date ASC, id ASC");
$pwiRows = [];
while ($row = mysqli_fetch_assoc($res)) {
    $pwiRows[] = $row;
}

function getInstallmentSortKey($installmentNumber) {
    $label = trim($installmentNumber ?? '');

    // "Initial Payment" always comes first
    if (strcasecmp($label, 'Initial Payment') === 0) {
        return [0, 0];
    }

    // "installment_1", "installment_2", ... "installment_x" sorted numerically
    if (preg_match('/^installment_(\d+)$/i', $label, $matches)) {
        return [1, (int) $matches[1]];
    }

    // Anything unexpected falls to the end, alphabetically
    return [2, $label];
}

usort($pwiRows, function ($a, $b) {
    // Keep separate program_batch groups together, in their original (paid_date) order
    $batchCompare = strcmp($a['program_batch'] ?? '', $b['program_batch'] ?? '');
    if ($batchCompare !== 0) {
        return $batchCompare;
    }

    $keyA = getInstallmentSortKey($a['installmentNumber']);
    $keyB = getInstallmentSortKey($b['installmentNumber']);

    if ($keyA[0] !== $keyB[0]) {
        return $keyA[0] <=> $keyB[0];
    }
    return $keyA[1] <=> $keyB[1];
});

$response['data']['payment_wise_info'] = $pwiRows;

// 9. Discount Information (course fee discount + registration fee discount, per program/batch)
// discounted_percentage / dis_yes_no already come from installment_payment_table (item #4 above).
// These two tables hold the actual discount records applied to this student.

// 9a. payment_plan_history -> Course Fee discount history
$query = "SELECT pph.*, pt.program_name, bt.batch_name
          FROM payment_plan_history pph
          LEFT JOIN program_table pt ON pph.program_id = pt.program_code
          LEFT JOIN batch_table bt ON pph.batch_id = bt.id
          WHERE pph.student_id = '$studentCode'
          ORDER BY pph.created_at DESC";
$res = mysqli_query($conn, $query);
$response['data']['payment_plan_history'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['payment_plan_history'][] = $row;
}

// 9b. payment_plan_regfee_discount -> Registration Fee discount history
$query = "SELECT prd.*, pt.program_name, bt.batch_name
          FROM payment_plan_regfee_discount prd
          LEFT JOIN program_table pt ON prd.program_id = pt.program_code
          LEFT JOIN batch_table bt ON prd.batch_id = bt.id
          WHERE prd.student_id = '$studentCode'
          ORDER BY prd.created_at DESC";
$res = mysqli_query($conn, $query);
$response['data']['payment_plan_regfee_discount'] = [];
while ($row = mysqli_fetch_assoc($res)) {
    $response['data']['payment_plan_regfee_discount'][] = $row;
}

echo json_encode($response);
$conn->close();
?>