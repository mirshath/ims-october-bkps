<?php
include("../database/connection.php"); // Adjust the path as necessary

// Check if main_component_id and module_id are set
if (isset($_POST['main_component_id']) && isset($_POST['module_id'])) {
    $mainComponentId = $_POST['main_component_id'];
    $moduleId = $_POST['module_id'];

    // Debugging: Log the received IDs
    error_log("Received main_component_id: " . $mainComponentId);
    error_log("Received module_id: " . $moduleId);

    // Prepare the SQL query to fetch sub components
    $query = "
        SELECT sc.id, sc.sub_component_name 
        FROM sub_assign_components sc
        INNER JOIN allocated_components ac ON sc.id = ac.sub_component_id
        WHERE ac.main_component_id = ? AND ac.module_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $mainComponentId, $moduleId);
    $stmt->execute();
    $result = $stmt->get_result();

    $subComponents = [];
    while ($row = $result->fetch_assoc()) {
        $subComponents[] = $row; // Add each row to the array
    }

    // Debugging: Log the number of sub-components found
    error_log("Number of sub-components found: " . count($subComponents));

    // Return the data as JSON
    echo json_encode($subComponents);
} else {
    // Return an empty array if parameters are not set
    echo json_encode([]);
}
