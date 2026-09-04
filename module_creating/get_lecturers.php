<?php
include("../database/connection.php");

if (isset($_POST['programme_id'])) {
    $programme_id = $_POST['programme_id'];
    
    // First get program name from program_table
    $program_query = "SELECT program_name FROM program_table WHERE program_code = ?";
    $stmt = $conn->prepare($program_query);
    $stmt->bind_param("s", $programme_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program_row = $result->fetch_assoc();
    $program_name = $program_row['program_name'] ?? '';
    $stmt->close();

    // Now get lecturers for this program
    $lecturer_query = "SELECT l.id, l.lecturer_name, a.full_name 
                       FROM lecturer_table l
                       LEFT JOIN admin a ON l.lecturer_name = a.username
                       WHERE FIND_IN_SET(?, l.programs) OR l.programs LIKE ? OR l.programs LIKE ? OR l.programs LIKE ?";
    $stmt = $conn->prepare($lecturer_query);
    $search_term = "%$program_name%";
    $stmt->bind_param("ssss", $program_name, $search_term, $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    $lecturers = [];
    while ($row = $result->fetch_assoc()) {
        $lecturers[] = $row;
    }
    
    echo json_encode($lecturers);
} else {
    echo json_encode([]);
}
