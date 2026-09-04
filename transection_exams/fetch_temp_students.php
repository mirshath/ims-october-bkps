<?php
include('../database/connection.php');

if (isset($_POST['programme_id']) && isset($_POST['batch_id'])) {
    $programme_id = $_POST['programme_id'];
    $batch_id = $_POST['batch_id'];

    // 1. Get Program Name 
    $program_name = "";
    $stmt1 = $conn->prepare("SELECT program_name FROM program_table WHERE program_code = ?");
    if ($stmt1) {
        $stmt1->bind_param("s", $programme_id);
        $stmt1->execute();
        $stmt1->bind_result($program_name);
        $stmt1->fetch();
        $stmt1->close();
    }

    // 2. Get Batch Name
    $batch_name = "";
    $stmt2 = $conn->prepare("SELECT batch_name FROM batch_table WHERE id = ?");
    if ($stmt2) {
        $stmt2->bind_param("s", $batch_id);
        $stmt2->execute();
        $stmt2->bind_result($batch_name);
        $stmt2->fetch();
        $stmt2->close();
    }

    // 3. Fetch Students using the names
    $students = [];
    if (!empty($program_name) && !empty($batch_name)) {
        $query = "SELECT id, fullname, nic, mobile, approved, approved_by FROM students_temporary_registration WHERE program = ? AND batch = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ss", $program_name, $batch_name);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
        }
    }

    echo json_encode($students);

} else {
    echo json_encode(['error' => 'Missing parameters']);
}
?>