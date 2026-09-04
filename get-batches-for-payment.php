<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo json_encode([]);
    exit;
}

if (isset($_POST['program_code']) && !empty($_POST['program_code'])) {
    $programCode = $_POST['program_code'];
    
    // Fetch batches for the selected program
    $batches = [];
    $sql = "SELECT id, batch_name FROM batch_table WHERE programme = ? ORDER BY batch_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $programCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    
    echo json_encode($batches);
} else {
    echo json_encode([]);
}
?>