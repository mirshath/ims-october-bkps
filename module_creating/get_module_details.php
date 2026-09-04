<?php
include("../database/connection.php");

if (isset($_GET['module_code'])) {
    $module_code = $_GET['module_code'];

    $sql = "SELECT m.*, 
                   p.program_name, 
                   y.year_name, 
                   se.semester_name, 
                   u.university_name 
            FROM modules m
            LEFT JOIN program_table p ON m.programme_id = p.program_code
            LEFT JOIN year_table y ON m.year_id = y.id
            LEFT JOIN semester_table se ON m.semester_id = se.id
            LEFT JOIN universities u ON m.university_id = u.id
            WHERE m.module_code = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $module_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $module_details = $result->fetch_assoc();
        
        // ✅ Log data to check values
        error_log("Module Data: " . json_encode($module_details));

        echo json_encode($module_details);
    } else {
        echo json_encode(["error" => "Module not found"]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["error" => "No module code provided"]);
}
