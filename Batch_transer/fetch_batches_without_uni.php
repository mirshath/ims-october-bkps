<?php
// include("../database/connection.php");

// $program_id = $_POST['program_id'];
// $university_id = $_POST['university_id'];

// $query = "SELECT id, batch_name FROM batch_table 
//           WHERE programme = ? AND university = ? 
//           ORDER BY batch_name";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("ii", $program_id, $university_id);
// $stmt->execute();
// $result = $stmt->get_result();

// $output = '<option value="">-- Select Batch --</option>';
// while ($row = $result->fetch_assoc()) {
//     $output .= "<option value='{$row['id']}'>{$row['batch_name']}</option>";
// }

// echo $output;


// --------------- 
include("../database/connection.php");

$program_id = isset($_POST['program_id']) ? $_POST['program_id'] : null;

$output = '<option value="">-- Select Batch --</option>';

if ($program_id) {
    $query = "SELECT id, batch_name FROM batch_table WHERE programme = ? ORDER BY batch_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $output .= "<option value='{$row['id']}'>{$row['batch_name']}</option>";
    }
}

echo $output;
