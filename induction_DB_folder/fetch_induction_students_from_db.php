<?php
session_start();
include '../database/connection.php';

try {
    $programId = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
    $batchId = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;

    if (!$programId || !$batchId) {
        echo json_encode([
            'success' => false,
            'message' => 'Program and batch are required'
        ]);
        exit;
    }

    $sql = "SELECT 
                ap.id,
                ap.student_code,
                ap.student_registration_id,
                s.title,
                s.first_name,
                s.last_name,
                s.personal_email,
                s.nic,
                ap.programme_code,
                ap.batch_id,
                ies.id as email_sent_id,
                ies.sent_by,
                ies.sent_at
            FROM allocate_programme ap
            INNER JOIN students s ON ap.student_code = s.student_code
            INNER JOIN program_table pt ON ap.programme_code = pt.program_code
            INNER JOIN batch_table bt ON ap.batch_id = bt.id
            LEFT JOIN induction_emails_sent ies ON ap.id = ies.allocate_programme_id
            WHERE ap.programme_code = ? 
                AND ap.batch_id = ? 
                AND ap.status = 'active'
            ORDER BY s.last_name, s.first_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $programId, $batchId);
    $stmt->execute();
    $result = $stmt->get_result();

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'student_code' => $row['student_code'],
            'student_registration_id' => $row['student_registration_id'],
            'nic' => $row['nic'],
            'title' => $row['title'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'personal_email' => $row['personal_email'],
            'nic' => $row['nic'],
            'programme_code' => $row['programme_code'],
            'batch_id' => $row['batch_id'],
            'email_sent' => !empty($row['email_sent_id']) ? true : false,
            'sent_by' => $row['sent_by'],
            'sent_at' => $row['sent_at']
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
