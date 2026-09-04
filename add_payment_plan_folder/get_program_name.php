<?php
include '../database/connection.php';

if (isset($_POST['program_code'])) {
    $program_code = trim($_POST['program_code']);

    $stmt = $conn->prepare("SELECT program_name FROM program_table WHERE program_code = ?");
    $stmt->bind_param("s", $program_code);
    $stmt->execute();
    $stmt->bind_result($program_name);

    if ($stmt->fetch()) {
        echo json_encode([
            'success' => true,
            'program_name' => $program_name
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Program code not found'
        ]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Program code not provided'
    ]);
}
