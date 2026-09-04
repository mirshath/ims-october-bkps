<?php
include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $program_id = $_POST['program_id'];
    $university_id = $_POST['university_id'];

    // Fetch compulsory subjects
    $compulsoryQuery = "SELECT module_name FROM modules WHERE programme_id = ? AND type = 'Compulsory'";
    $stmt = $conn->prepare($compulsoryQuery);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $compulsoryResult = $stmt->get_result();
    $compulsorySubjects = [];
    while ($row = $compulsoryResult->fetch_assoc()) {
        $compulsorySubjects[] = $row['module_name'];
    }

   

    // Return the subjects as JSON
    echo json_encode([
        'compulsory' => $compulsorySubjects,
        
    ]);
}
?>