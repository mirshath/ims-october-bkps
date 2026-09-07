<?php
session_start();
include 'database/connection.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student record.']);
        exit;
    }

    // Fetch the record first so we can confirm it exists and isn't already attended,
    // and so we can return fresh student details for the modal.
    $sql = "SELECT 
                ies.id,
                ies.student_code,
                ies.attended,
                ies.fees_paid,
                s.title,
                s.first_name,
                s.last_name,
                pt.program_name,
                bt.batch_name
            FROM induction_emails_sent ies
            INNER JOIN students s ON ies.student_code = s.student_code
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id
            WHERE ies.id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student record not found.']);
        exit;
    }

    $student = $result->fetch_assoc();

    if ($student['attended'] === 'yes') {
        echo json_encode(['success' => false, 'message' => 'Student is already marked as attended.']);
        exit;
    }

    // Manual override: mark attendance without touching fees_paid or pack_collected.
    $updateStmt = $conn->prepare("UPDATE induction_emails_sent SET attended = 'yes', attended_time = NOW() WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Update prepare failed: " . $conn->error);
    }
    $updateStmt->bind_param("i", $id);
    if (!$updateStmt->execute()) {
        throw new Exception("Update failed: " . $updateStmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked manually!',
        'student' => [
            'id' => $student['id'],
            'name' => trim($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']),
            'student_code' => $student['student_code'],
            'program_name' => $student['program_name'],
            'batch_name' => $student['batch_name'],
            'attended' => 'yes',
            'attended_time' => date('Y-m-d H:i:s'),
            'fees_paid' => $student['fees_paid']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
