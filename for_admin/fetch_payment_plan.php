<?php
// include("../database/connection.php");

// if (!isset($_GET['student_code'])) {
//     echo json_encode(["status" => false, "message" => "student_code missing"]);
//     exit();
// }

// $student_code = $_GET['student_code'];

// $sql = "
//     SELECT 
//         p.*,
//         bt.batch_name,
//         pt.program_name
//     FROM add_payment_plan_table p
//     LEFT JOIN batch_table bt ON p.programme_batch = bt.id
//     LEFT JOIN program_table pt ON bt.programme = pt.program_code
//     WHERE p.student_id = ?
//     ORDER BY p.id ASC
// ";

// $stmt = $conn->prepare($sql);
// $stmt->bind_param("s", $student_code);
// $stmt->execute();
// $res = $stmt->get_result();

// $data = [];

// while ($row = $res->fetch_assoc()) {
//     $data[] = $row;
// }

// if (empty($data)) {
//     echo json_encode(["status" => false, "message" => "No payment plan found"]);
// } else {
//     echo json_encode($data);
// }



include("../database/connection.php");

if (!isset($_GET['student_code'])) {
    echo json_encode(["status" => false, "message" => "student_code missing"]);
    exit();
}

$student_code = $_GET['student_code'];

$sql = "SELECT * FROM add_payment_plan_table WHERE student_id = ? ORDER BY id ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$res = $stmt->get_result();

$data = [];

while ($row = $res->fetch_assoc()) {

    // Remove NULL, empty string, and zero values
    $clean = [];
    foreach ($row as $key => $value) {
        if ($value !== null && $value !== "" && $value != 0) {
            $clean[$key] = $value;
        }
    }

    $data[] = $clean;
}

if (empty($data)) {
    echo json_encode(["status" => false, "message" => "No payment plan found"]);
} else {
    echo json_encode($data);
}
