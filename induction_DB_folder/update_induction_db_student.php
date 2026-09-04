<?php
session_start();
header('Content-Type: application/json');
include '../database/connection.php';

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $studentCode = isset($_POST['student_code']) ? intval($_POST['student_code']) : 0;
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $nic = isset($_POST['nic']) ? trim($_POST['nic']) : '';
    $contactNo = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
    $feesPaid = isset($_POST['fees_paid']) ? trim($_POST['fees_paid']) : '';
    $attended = isset($_POST['attended']) ? trim($_POST['attended']) : '';
    
    if (!$id || !$studentCode) {
        echo json_encode(['success' => false, 'message' => 'Invalid student data']);
        exit;
    }
    
    $conn->begin_transaction();
    
    // Update students table
    // Parse full name into title, first_name, last_name
    $nameParts = explode(' ', trim($fullName), 3);
    $title = $nameParts[0] ?? '';
    $firstName = $nameParts[1] ?? '';
    $lastName = $nameParts[2] ?? '';
    
    $studentUpdateSql = "UPDATE students SET title = ?, first_name = ?, last_name = ?, nic = ?, mobile = ? WHERE student_code = ?";
    $studentStmt = $conn->prepare($studentUpdateSql);
    $studentStmt->bind_param("sssssi", $title, $firstName, $lastName, $nic, $contactNo, $studentCode);
    $studentStmt->execute();
    $studentStmt->close();
    
    // Update induction_emails_sent table
    $sql = "UPDATE induction_emails_sent SET fees_paid = ?, attended = ?";
    $params = [$feesPaid, $attended];
    $types = "ss";
    
    // If attended changed from No to Yes, update attended_time
    $currentDataSql = "SELECT attended FROM induction_emails_sent WHERE id = ?";
    $stmt = $conn->prepare($currentDataSql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $currentData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($currentData['attended'] !== 'yes' && strtolower($attended) === 'yes') {
        $sql .= ", attended_time = NOW()";
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $id;
    $types .= "i";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Student details updated successfully!']);
} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno === 0 && $conn->in_transaction()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
