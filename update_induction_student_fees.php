<?php
session_start();
include 'database/connection.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // Retrieve all fields
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $date_of_birth = isset($_POST['date_of_birth']) ? trim($_POST['date_of_birth']) : '';
    $nic = isset($_POST['nic']) ? trim($_POST['nic']) : '';
    $programme = isset($_POST['programme']) ? trim($_POST['programme']) : '';
    $contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
    $fees = isset($_POST['fees']) ? trim($_POST['fees']) : '';

    if ($id > 0) {
        // Prepare update query
        $query = "UPDATE induction_students SET 
                  full_name = ?, 
                  date_of_birth = ?, 
                  nic = ?, 
                  programme = ?, 
                  contact_no = ?, 
                  fees = ? 
                  WHERE id = ?";

        // Update columns in the database
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $full_name, $date_of_birth, $nic, $programme, $contact_no, $fees, $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Student details updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating details: ' . $conn->error
            ]);
        }

        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid parameters'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
