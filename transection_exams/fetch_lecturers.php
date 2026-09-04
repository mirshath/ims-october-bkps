<?php
session_start();
include("../database/connection.php"); // Adjust the path as necessary

if (!isset($_SESSION['username'])) {
    echo json_encode([]);
    exit();
}

if (isset($_POST['programme_id'])) {
    $programme_id = $_POST['programme_id'];
    
    // Get the program name from program_table
    $program_query = "SELECT program_name FROM program_table WHERE program_code = ?";
    $stmt = $conn->prepare($program_query);
    $stmt->bind_param("i", $programme_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $program_row = $result->fetch_assoc();
    $program_name = $program_row['program_name'];
    
    // Now fetch lecturers who teach this program
    // The programs field in lecturer_table contains comma-separated program names
    $lecturer_query = "SELECT id, CONCAT(title, ' ', lecturer_name) AS name FROM lecturer_table 
                      WHERE FIND_IN_SET(?, programs) OR programs LIKE ? OR programs LIKE ? OR programs LIKE ?";
    $stmt = $conn->prepare($lecturer_query);
    $search_term = "%$program_name%";
    $stmt->bind_param("ssss", $program_name, $search_term, $search_term, $search_term);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        $lecturers = [];
        while ($row = $result->fetch_assoc()) {
            $lecturers[] = $row;
        }
        
        echo json_encode($lecturers);
    } else {
        echo json_encode(['error' => 'Failed to execute query']);
    }
} else {
    echo json_encode([]);
}
