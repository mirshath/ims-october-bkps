<?php
include("../database/connection.php");

if (!isset($_GET['student_code'])) {
    echo json_encode(["status" => false, "message" => "No student code"]);
    exit();
}

$student_code = $_GET['student_code'];

$sql = "
    SELECT 
        ap.*,
        pt.program_name,
        bt.batch_name
    FROM allocate_programme ap
    INNER JOIN program_table pt ON ap.programme_code = pt.program_code
    INNER JOIN batch_table bt ON ap.batch_id = bt.id
    WHERE ap.student_code = ?
    ORDER BY ap.id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    echo json_encode($rows);
} else {
    echo json_encode(["status" => false, "message" => "No program allocation found"]);
}
