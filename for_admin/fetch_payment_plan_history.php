<?php
// include("../database/connection.php");

// if (!isset($_GET['student_code'])) {
//     echo json_encode(["status" => false, "message" => "student_code missing"]);
//     exit();
// }

// $student_code = $_GET['student_code'];

// $sql = "
//     SELECT 
//         id,
//         student_id,
//         program_id,
//         batch_id,
//         discount_type,
//         discount_value,
//         created_at,
//         updated_by,
//         re_marks
//     FROM payment_plan_history
//     WHERE student_id = ?
//     ORDER BY id ASC
// ";

// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $student_code);
// $stmt->execute();
// $res = $stmt->get_result();

// $data = [];

// while ($row = $res->fetch_assoc()) {

//     // Remove NULL / empty / zero values
//     $clean = [];
//     foreach ($row as $key => $value) {
//         if ($value !== null && $value !== "" && $value != 0) {
//             $clean[$key] = $value;
//         }
//     }

//     $data[] = $clean;
// }

// if (empty($data)) {
//     echo json_encode(["status" => false, "message" => "No payment history found"]);
// } else {
//     echo json_encode($data);
// }




include("../database/connection.php");

$student_code = $_GET['student_code'] ?? '';

if (!$student_code) {
    echo json_encode(['status' => false, 'message' => 'Student code missing']);
    exit();
}

// Fetch payment plan history with student, program, and batch info
$sql = "
    SELECT 
        pph.id,
        pph.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        pph.program_id,
        pr.program_name,
        pph.batch_id,
        b.batch_name,
        pph.discount_type,
        pph.discount_value,
        pph.created_at,
        pph.updated_by,
        pph.re_marks
    FROM payment_plan_history pph
    LEFT JOIN students s ON pph.student_id = s.student_code
    LEFT JOIN program_table pr ON pph.program_id = pr.program_code
    LEFT JOIN batch_table b ON pph.batch_id = b.id
    WHERE pph.student_id = ?
    ORDER BY pph.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_code);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

if (!$data) {
    echo json_encode(['status' => false, 'message' => 'No data found']);
} else {
    echo json_encode($data);
}
