<?php
session_start();
include 'database/connection.php';

try {
    $qrData = isset($_POST['nic']) ? trim($_POST['nic']) : '';
    
    if (empty($qrData)) {
        echo json_encode(['success' => false, 'message' => 'Please enter or scan a NIC/Student Code']);
        exit;
    }

    // Find student by nic or student_code, and check program-batch status
    $sql = "SELECT 
                ies.id,
                ies.program_id,
                ies.batch_id,
                ies.student_code,
                s.title,
                s.first_name,
                s.last_name,
                ies.attended,
                ies.attended_time,
                ies.pack_collected,
                ies.fees_paid,
                pt.program_name,
                bt.batch_name,
                iat.status AS program_batch_status
            FROM induction_emails_sent ies
            INNER JOIN students s ON ies.student_code = s.student_code
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id
            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
            WHERE (ies.nic = ? OR ies.student_code = ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $qrData, $qrData);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found in induction email list']);
        exit;
    }

    $student = $result->fetch_assoc();

    // Check if program-batch is inactive
    $programBatchStatus = $student['program_batch_status'] ?? 'active';
    if ($programBatchStatus === 'inactive') {
        echo json_encode([
            'success' => false,
            'message' => 'Program batch is not active!',
            'student' => [
                'name' => trim($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']),
                'student_code' => $student['student_code'],
                'program_name' => $student['program_name'],
                'batch_name' => $student['batch_name'],
                'fees_paid' => $student['fees_paid']
            ]
        ]);
        exit;
    }

    // Check if already attended
    if ($student['attended'] === 'yes') {
        echo json_encode([
            'success' => false,
            'message' => 'Student already checked in!',
            'student' => [
                'name' => trim($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']),
                'student_code' => $student['student_code'],
                'program_name' => $student['program_name'],
                'batch_name' => $student['batch_name'],
                'attended' => 'yes',
                'attended_time' => $student['attended_time'],
                'pack_collected' => $student['pack_collected'],
                'fees_paid' => $student['fees_paid']
            ]
        ]);
        exit;
    }

    // Check if fees are unpaid
    if (strtolower($student['fees_paid']) !== 'paid') {
        echo json_encode([
            'success' => false,
            'message' => 'Make the payment first!',
            'student' => [
                'name' => trim($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']),
                'student_code' => $student['student_code'],
                'program_name' => $student['program_name'],
                'batch_name' => $student['batch_name'],
                'fees_paid' => $student['fees_paid']
            ]
        ]);
        exit;
    }

    // Update attendance and mark pack as collected (since fees are paid)
    $updateSql = "UPDATE induction_emails_sent SET attended = 'yes', attended_time = NOW(), pack_collected = 1 WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $student['id']);
    $updateStmt->execute();

    // Get updated pack_collected value
    $newPackCollected = 1;

    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully!',
        'student' => [
            'name' => trim($student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']),
            'student_code' => $student['student_code'],
            'program_name' => $student['program_name'],
            'batch_name' => $student['batch_name'],
            'attended' => 'yes',
            'attended_time' => date('Y-m-d H:i:s'),
            'pack_collected' => $newPackCollected,
            'fees_paid' => $student['fees_paid']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
