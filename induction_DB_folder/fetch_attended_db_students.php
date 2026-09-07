<?php
session_start();
include '../database/connection.php';

try {
    $programFilter = isset($_GET['program']) ? trim($_GET['program']) : 'all';
    $user_id = $_SESSION['user_id'] ?? 0;
    $role    = $_SESSION['role'] ?? '';

    // The dropdown can send either a plain programme name ("Diploma in HR")
    // or a combined "Programme - Batch" value ("Diploma in HR - Batch 12").
    // Split it so we can filter on both program_name and batch_name when present.
    $programName = null;
    $batchName = null;
    if ($programFilter !== '' && strtolower($programFilter) !== 'all') {
        $parts = explode(' - ', $programFilter, 2);
        if (count($parts) === 2) {
            $programName = $parts[0];
            $batchName = $parts[1];
        } else {
            $programName = $programFilter;
        }
    }

    $conditions = [
        "ies.attended = 'yes'",
        "(iat.status = 'active' OR iat.status IS NULL)"
    ];
    $types = '';
    $params = [];

    if ($role !== 'super_admin') {
        $conditions[] = "pau.user_id = ?";
        $types .= 'i';
        $params[] = $user_id;
    }

    if ($programName !== null) {
        $conditions[] = "pt.program_name = ?";
        $types .= 's';
        $params[] = $programName;
    }

    if ($batchName !== null) {
        $conditions[] = "bt.batch_name = ?";
        $types .= 's';
        $params[] = $batchName;
    }

    $userJoin = ($role !== 'super_admin')
        ? "INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code"
        : "";

    $sql = "SELECT 
                ies.id,
                ies.student_code,
                s.title,
                s.first_name,
                s.last_name,
                s.nic,
                s.mobile as contact,
                ies.email,
                ies.attended,
                ies.attended_time,
                ies.pack_collected,
                ies.fees_paid,
                pt.program_name,
                bt.batch_name
            FROM induction_emails_sent ies
            INNER JOIN students s ON ies.student_code = s.student_code
            INNER JOIN program_table pt ON ies.program_id = pt.program_code
            INNER JOIN batch_table bt ON ies.batch_id = bt.id
            $userJoin
            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
            WHERE " . implode(' AND ', $conditions) . "
            ORDER BY s.last_name, s.first_name";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Query prepare failed: " . $conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'student_code' => $row['student_code'],
            'name' => trim($row['title'] . ' ' . $row['first_name'] . ' ' . $row['last_name']),
            'nic' => $row['nic'],
            'contact' => $row['contact'],
            'email' => $row['email'],
            'attended' => $row['attended'],
            'attended_time' => $row['attended_time'],
            'pack_collected' => $row['pack_collected'],
            'fees_paid' => $row['fees_paid'],
            'program_name' => $row['program_name'],
            'batch_name' => $row['batch_name'],
            'program_batch' => trim($row['program_name'] . ' - ' . $row['batch_name'])
        ];
    }

    echo json_encode([
        'success' => true,
        'students' => $students
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
