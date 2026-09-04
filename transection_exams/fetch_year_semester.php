<?php
include("../database/connection.php");

if (isset($_POST['module_id'])) {
    $moduleId = $_POST['module_id'];

    // Query to get the year and semester for the selected module
    $query = "SELECT year_table.year_name, semester_table.semester_name
              FROM modules 
              LEFT JOIN year_table ON modules.year_id = year_table.id
              LEFT JOIN semester_table ON modules.semester_id = semester_table.id
              WHERE modules.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch the data
    $data = $result->fetch_assoc();

    // Return the data in JSON format
    echo json_encode($data);
}
?>
