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
//         d_type,
//         discount_value,
//         remarks,
//         created_at,
//         updated_by
//     FROM payment_plan_regfee_discount
//     WHERE student_id = ?
//     ORDER BY id ASC
// ";

// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $student_code);
// $stmt->execute();
// $res = $stmt->get_result();

// $data = [];

// while ($row = $res->fetch_assoc()) {

//     // Remove NULL, empty, zero values
//     $clean = [];
//     foreach ($row as $key => $value) {
//         if ($value !== null && $value !== "" && $value != 0) {
//             $clean[$key] = $value;
//         }
//     }

//     $data[] = $clean;
// }

// if (empty($data)) {
//     echo json_encode(["status" => false, "message" => "No registration fee discount found"]);
// } else {
//     echo json_encode($data);
// }





include("../database/connection.php");

$student_code = $_GET['student_code'] ?? '';

if (!$student_code) {
    echo json_encode(['status' => false, 'message' => 'Student code missing']);
    exit();
}

// Fetch registration fee discount with student, program, and batch info
$sql = "
    SELECT 
        d.id,
        d.student_id,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        d.program_id,
        pr.program_name,
        d.batch_id,
        b.batch_name,
        d.d_type,
        d.discount_value,
        d.remarks,
        d.created_at,
        d.updated_by
    FROM payment_plan_regfee_discount d
    LEFT JOIN students s ON d.student_id = s.student_code
    LEFT JOIN program_table pr ON d.program_id = pr.program_code
    LEFT JOIN batch_table b ON d.batch_id = b.id
    WHERE d.student_id = ?
    ORDER BY d.created_at DESC
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
