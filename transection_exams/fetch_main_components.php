<?php
include("../database/connection.php");

// Check if module_id is set
if (isset($_POST['module_id'])) {
    $moduleId = $_POST['module_id'];

    // Prepare the SQL query to fetch main components with INNER JOIN
    // $query = "
    //     SELECT ac.id, ac.main_component_id, a.as_main_component_name 
    //     FROM allocated_components ac
    //     INNER JOIN assignment_components a ON ac.main_component_id = a.id
    //     WHERE ac.module_id = ?
    // ";
    // ------------------------- changes uniq 21.03.2025  ---------------------------------------
    $query = "
    SELECT DISTINCT ac.main_component_id, a.as_main_component_name 
    FROM allocated_components ac
    INNER JOIN assignment_components a ON ac.main_component_id = a.id
    WHERE ac.module_id = ?
";

    // $query = "
    //     SELECT ac.id, ac.main_component_id, a.as_main_component_name 
    //     FROM allocated_components ac
    //     INNER JOIN assignment_components a ON ac.main_component_id = a.id
    //     WHERE ac.module_id = ?
    // ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();

    $mainComponents = [];
    while ($row = $result->fetch_assoc()) {
        $mainComponents[] = $row; // Add each row to the array
    }

    // Return the data as JSON
    echo json_encode($mainComponents);
} else {
    // Return an empty array if module_id is not set
    echo json_encode([]);
}
