<?php
// Include your database connection
include('../database/connection.php');

// Decode the JSON string into an array
$studentData = json_decode($_POST['student_data'], true);
$programId = $_POST['program_id'];
$batchId = $_POST['batch_id'];
$moduleId = $_POST['module_id'];
$mainCompId = $_POST['main_comp_id'];
$subCompId = $_POST['sub_component_id'];

$studentIds = array_column($studentData, 'student_code');

if (!empty($studentIds)) {
    $placeholders = implode(',', array_fill(0, count($studentIds), '?'));

    $sql = "
        SELECT `student_id`, `100marksEx1`, `examiner1_marks`
        FROM `bbm_direct_result`
        WHERE `student_id` IN ($placeholders) 
        AND `program_id` = ? 
        AND `batch_id` = ? 
        AND `module_id` = ? 
        AND `main_comp_id` = ? 
        AND `sub_component_id` = ?
    ";

    $stmt = $conn->prepare($sql);

    // Create type string and merge params
    $types = str_repeat('s', count($studentIds)) . 'iiiii'; // student_id might be string
    $params = array_merge($studentIds, [$programId, $batchId, $moduleId, $mainCompId, $subCompId]);

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $bbmResults = [];
        while ($row = $result->fetch_assoc()) {
            $bbmResults[] = [
                'student_id' => $row['student_id'],
                '100marksEx1' => $row['100marksEx1'],
                'examiner1_marks' => $row['examiner1_marks']
            ];
        }
        echo json_encode($bbmResults);
    } else {
        echo json_encode(['error' => 'Error executing query']);
    }
} else {
    echo json_encode(['error' => 'No student data found']);
}

$stmt->close();
$conn->close();
