<?php
// include("../database/connection.php");

// $student_code = $_POST['student_code'] ?? '';
// // $student_code = 1;
// $programme_batch = $_POST['programme_batch'] ?? '';
// // $programme_batch = "BTEC Higher National Diploma in Business - Batch 18";

// $response = ['has_dues' => false];

// if ($student_code && $programme_batch) {
//     $stmt = $conn->prepare("SELECT due_count_bms_fees, due_count_uni_fees 
//                             FROM payment_due_tables 
//                             WHERE student_code = ? AND programme_batch = ?");
//     $stmt->bind_param("ss", $student_code, $programme_batch);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     if ($row = $result->fetch_assoc()) {
//         if ($row['due_count_bms_fees'] > 0 || $row['due_count_uni_fees'] > 0) {
//             $response['has_dues'] = true;
//         }
//     }
//     $stmt->close();
// }

// echo json_encode($response);




// include("../database/connection.php");

// $student_code = $_POST['student_code'] ?? '';
// $programme_batch = $_POST['programme_batch'] ?? '';

// $response = ['has_dues' => false];

// if ($student_code && $programme_batch) {
//     $stmt = $conn->prepare("SELECT due_count_bms_fees, due_count_uni_fees 
//                             FROM payment_due_tables 
//                             WHERE student_code = ? AND programme_batch = ?");
//     $stmt->bind_param("ss", $student_code, $programme_batch);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     if ($row = $result->fetch_assoc()) {
//         if ($row['due_count_bms_fees'] > 0 || $row['due_count_uni_fees'] > 0) {
//             $response['has_dues'] = true;
//         }
//     } else {
//         // No rows found
//         $response['error'] = true;
//         $response['message'] = 'No payment due records found for this student and programme batch!';
//     }
//     $stmt->close();
// } else {
//     $response['error'] = true;
//     $response['message'] = 'Student code and programme batch are required!';
// }

// echo json_encode($response);





include("../database/connection.php");

$student_code = $_POST['student_code'] ?? '';
$program_code = $_POST['program_code'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';

$response = ['has_dues' => false];

if ($student_code && $program_code && $batch_id) {
    $stmt = $conn->prepare(
        "SELECT due_count_bms_fees, due_count_uni_fees 
         FROM payment_withheld_table 
         WHERE student_code = ? AND program_id = ? AND batch_id = ?"
    );
    $stmt->bind_param("sii", $student_code, $program_code, $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['due_count_bms_fees'] > 0 || $row['due_count_uni_fees'] > 0) {
            $response['has_dues'] = true;
        }
    }
    // else {
    //     $response['error'] = true;
    //     $response['message'] = 'No payment withheld record found for this student, program, and batch!';
    // }

    $stmt->close();
} else {
    $response['error'] = true;
    $response['message'] = 'Student code, program code, and batch id are required!';
}

echo json_encode($response);




