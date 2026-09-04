<?php
// include '../database/connection.php';

// $programme_id = $_POST['programme_id'];
// $batch_id = $_POST['batch_id'];

// $sql = "SELECT student_registration_id, new_student_registration_id
//         FROM allocate_programme 
//         WHERE programme_code = ? 
//         AND batch_id = ?";

// $stmt = $conn->prepare($sql);
// $stmt->bind_param("ii", $programme_id, $batch_id);
// $stmt->execute();
// $result = $stmt->get_result();

// $data = [];
// while ($row = $result->fetch_assoc()) {
//     $data[] = $row;
// }

// echo json_encode($data);




include '../database/connection.php';

$programme_id = $_POST['programme_id'];
$batch_id = $_POST['batch_id'];

// 1. Get student_registration_id using WHERE
$sql1 = "SELECT student_registration_id FROM allocate_programme WHERE programme_code = ? AND batch_id = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("ii", $programme_id, $batch_id);
$stmt1->execute();
$result1 = $stmt1->get_result();

$student_registration_ids = [];
while ($row = $result1->fetch_assoc()) {
    $student_registration_ids[] = $row['student_registration_id'];
}
$stmt1->close();

// 2. Get all new_student_registration_id without WHERE
$sql2 = "SELECT new_student_registration_id FROM allocate_programme";
$result2 = $conn->query($sql2);

$new_student_registration_ids = [];
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        $new_student_registration_ids[] = $row['new_student_registration_id'];
    }
}

// Return both results as associative array
echo json_encode([
    'student_registration_id' => $student_registration_ids,
    'new_student_registration_id' => $new_student_registration_ids
]);
