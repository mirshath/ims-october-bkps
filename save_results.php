<?php
include './database/connection.php'; // Ensure this file contains DB connection setup

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['results'])) {
    $stmt = $conn->prepare("INSERT INTO hd_program_results 
        (student_id, student_registration_id, program_id, batch_id, module_id, main_component_id, sub_component_id, full_marks, converted_marks) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data['results'] as $result) {
        // Replace these with actual values from your application
        $student_id = $result['student_id'];
        $student_registration_id = 12345; // Replace with actual value
        $program_id = 1; // Replace with actual value
        $batch_id = 2025; // Replace with actual value
        $module_id = 10; // Replace with actual value
        $main_component_id = 2; // Replace with actual value
        $sub_component_id = 5; // Replace with actual value
        $full_marks = $result['full_marks'];
        $converted_marks = $result['converted_marks'];

        $stmt->bind_param("iiiiiiidd", $student_id, $student_registration_id, $program_id, $batch_id, $module_id, $main_component_id, $sub_component_id, $full_marks, $converted_marks);
        $stmt->execute();
    }

    echo json_encode(["message" => "Results saved successfully!"]);
} else {
    echo json_encode(["message" => "No data received!"]);
}
?>
