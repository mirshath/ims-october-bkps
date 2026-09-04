<?php
session_start();
include("database/connection.php");

// Accept both program_id/batch_id (preferred) and program_name/batch_name for backward compatibility
$installments = [];
if (isset($_POST['program_id'], $_POST['batch_id'])) {
    $program_id = $_POST['program_id'];
    $batch_id = $_POST['batch_id'];

    $stmt = $conn->prepare("SELECT * FROM final_Yeat_instalment_data WHERE program_id = ? AND batch_id = ? ORDER BY installment_no ASC");
    $stmt->bind_param("ii", $program_id, $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $installments[] = $row;
    }
    $stmt->close();
} elseif (isset($_POST['program_name'], $_POST['batch_name'])) {
    $program_name = $_POST['program_name'];
    $batch_name = $_POST['batch_name'];

    $stmt = $conn->prepare("SELECT * FROM final_Yeat_instalment_data WHERE program = ? AND batch = ? ORDER BY installment_no ASC");
    $stmt->bind_param("ss", $program_name, $batch_name);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $installments[] = $row;
    }
    $stmt->close();
}

echo json_encode(['installments' => $installments]);
$conn->close();
?>