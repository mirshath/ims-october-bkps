<?php
include("../database/connection.php");
header('Content-Type: application/json');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $student_code = $_POST['id'];

    // Fetch latest allocation details
    $query = "SELECT ap.*, 
              s.first_name, s.last_name, 
              u.university_name as university, 
              p.program_name as programme,
              b.batch_name as batch
              FROM allocate_programme ap
              INNER JOIN students s ON ap.student_code = s.student_code
              INNER JOIN universities u ON ap.university_id = u.id
              INNER JOIN program_table p ON ap.programme_code = p.program_code
              INNER JOIN batch_table b ON ap.batch_id = b.id
              WHERE ap.student_code = ? 
              ORDER BY ap.id DESC LIMIT 1";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $student_code);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $programBatch = $student['programme'] . ' - ' . $student['batch'];

            // Fetch total paid per installment
            $payQuery = "SELECT installmentNumber, SUM(paymentAmount) AS total 
                         FROM payment_wise_info 
                         WHERE student_id = ? AND program_batch = ? AND status = 'paid'
                         GROUP BY installmentNumber";
            $payStmt = $conn->prepare($payQuery);
            $payStmt->bind_param("is", $student_code, $programBatch);
            $payStmt->execute();
            $payResult = $payStmt->get_result();

            $installments = [];
            while ($row = $payResult->fetch_assoc()) {
                $installments[$row['installmentNumber']] = $row['total'];
            }
            $initialPayment = $installments['initial'] ?? 0;

            // Fetch total paid for university fee
            $uniQuery = "SELECT SUM(paid_amount) AS total_uni_paid 
                         FROM payment_uni_fee 
                         WHERE student_id = ? AND program_batch = ?";
            $uniStmt = $conn->prepare($uniQuery);
            $uniStmt->bind_param("is", $student_code, $programBatch);
            $uniStmt->execute();
            $uniResult = $uniStmt->get_result();
            $uniData = $uniResult->fetch_assoc();
            $totalUniPaid = $uniData['total_uni_paid'] ?? 0;

            // Add totals to student array
            $student['installments'] = $installments;
            $student['initial_payment'] = $initialPayment;
            $student['total_uni_paid'] = $totalUniPaid;

            echo json_encode($student);

            $payStmt->close();
            $uniStmt->close();
        } else {
            echo json_encode(['error' => 'Student not found']);
        }
    } else {
        echo json_encode(['error' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}

$conn->close();
?>
