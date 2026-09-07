<?php
session_start();
include '../database/connection.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Get filter parameters
$programmeFilter = isset($_GET['programme']) ? trim($_GET['programme']) : '';
$feesFilter = isset($_GET['fees']) ? trim($_GET['fees']) : '';
$attendedFilter = isset($_GET['attended']) ? trim($_GET['attended']) : '';
$packCollectedFilter = isset($_GET['pack_collected']) ? trim($_GET['pack_collected']) : '';

// Get user info for program_allocation_user check
$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';

// Build WHERE clause
$whereConditions = [];
if ($role !== 'super_admin') {
    $whereConditions[] = "pau.user_id = " . intval($user_id);
}

if ($programmeFilter !== '') {
    $parts = explode(' - ', $programmeFilter, 2);
    if (count($parts) === 2) {
        $programmeName = $parts[0];
        $batchName = $parts[1];
        $whereConditions[] = "pt.program_name = '" . mysqli_real_escape_string($conn, $programmeName) . "' AND bt.batch_name = '" . mysqli_real_escape_string($conn, $batchName) . "'";
    } else {
        $whereConditions[] = "pt.program_name = '" . mysqli_real_escape_string($conn, $programmeFilter) . "'";
    }
}

if ($feesFilter !== '') {
    $whereConditions[] = "ies.fees_paid = '" . mysqli_real_escape_string($conn, strtolower($feesFilter)) . "'";
}

if ($attendedFilter !== '') {
    $whereConditions[] = "ies.attended = '" . mysqli_real_escape_string($conn, strtolower($attendedFilter)) . "'";
}

if ($packCollectedFilter !== '') {
    $whereConditions[] = "ies.pack_collected = " . intval($packCollectedFilter);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all data
$query = "SELECT 
            ies.id,
            ies.attended,
            ies.attended_time,
            ies.pack_collected,
            ap.student_registration_id,
            ies.student_code,
            CONCAT(s.title, ' ', s.first_name, ' ', s.last_name) AS full_name,
            pt.program_name AS programme,
            bt.batch_name AS batch,
            ies.nic,
            s.mobile AS contact_no,
            s.telephone AS landline_no,
            ies.fees_paid,
            s.qualifications,
            s.organization,
            s.title AS gender_title,
            s.date_of_birth,
            s.nationality,
            ies.email,
            s.bms_email,
            s.permanent_address,
            s.current_address,
            ies.sent_at AS email_sent_time,
            ies.sent_by AS email_sent_by
          FROM induction_emails_sent ies
          INNER JOIN allocate_programme ap ON ies.allocate_programme_id = ap.id
          INNER JOIN students s ON ap.student_code = s.student_code
          INNER JOIN program_table pt ON ies.program_id = pt.program_code
          INNER JOIN batch_table bt ON ies.batch_id = bt.id
          " . ($role !== 'super_admin' ? "INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code" : "") . "
          " . ($role !== 'super_admin' ? "" : "LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id") . "
          " . ($role === 'super_admin' ? "LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id" : "") . "
          " . ($whereClause . " " . ($role === 'super_admin' ? "AND (iat.status = 'active' OR iat.status IS NULL)" : "LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id WHERE (iat.status = 'active' OR iat.status IS NULL) AND " . substr($whereClause, 6))) . "
          ORDER BY ies.id DESC";

// Wait, let's fix that query properly:
if ($role === 'super_admin') {
    $query = "SELECT 
                ies.id,
                ies.attended,
                ies.attended_time,
                ies.pack_collected,
                ap.student_registration_id,
                ies.student_code,
                CONCAT(s.title, ' ', s.first_name, ' ', s.last_name) AS full_name,
                pt.program_name AS programme,
                bt.batch_name AS batch,
                ies.nic,
                s.mobile AS contact_no,
                s.telephone AS landline_no,
                ies.fees_paid,
                s.qualifications,
                s.organization,
                s.title AS gender_title,
                s.date_of_birth,
                s.nationality,
                ies.email,
                s.bms_email,
                s.permanent_address,
                s.current_address,
                ies.sent_at AS email_sent_time,
                ies.sent_by AS email_sent_by
              FROM induction_emails_sent ies
              INNER JOIN allocate_programme ap ON ies.allocate_programme_id = ap.id
              INNER JOIN students s ON ap.student_code = s.student_code
              INNER JOIN program_table pt ON ies.program_id = pt.program_code
              INNER JOIN batch_table bt ON ies.batch_id = bt.id
              LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
              " . (!empty($whereClause) ? $whereClause . " AND (iat.status = 'active' OR iat.status IS NULL)" : "WHERE (iat.status = 'active' OR iat.status IS NULL)") . "
              ORDER BY ies.id DESC";
} else {
    $query = "SELECT 
                ies.id,
                ies.attended,
                ies.attended_time,
                ies.pack_collected,
                ap.student_registration_id,
                ies.student_code,
                CONCAT(s.title, ' ', s.first_name, ' ', s.last_name) AS full_name,
                pt.program_name AS programme,
                bt.batch_name AS batch,
                ies.nic,
                s.mobile AS contact_no,
                s.telephone AS landline_no,
                ies.fees_paid,
                s.qualifications,
                s.organization,
                s.title AS gender_title,
                s.date_of_birth,
                s.nationality,
                ies.email,
                s.bms_email,
                s.permanent_address,
                s.current_address,
                ies.sent_at AS email_sent_time,
                ies.sent_by AS email_sent_by
              FROM induction_emails_sent ies
              INNER JOIN allocate_programme ap ON ies.allocate_programme_id = ap.id
              INNER JOIN students s ON ap.student_code = s.student_code
              INNER JOIN program_table pt ON ies.program_id = pt.program_code
              INNER JOIN batch_table bt ON ies.batch_id = bt.id
              INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
              LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
              " . (!empty($whereClause) ? $whereClause . " AND (iat.status = 'active' OR iat.status IS NULL)" : "WHERE pau.user_id = " . intval($user_id) . " AND (iat.status = 'active' OR iat.status IS NULL)") . "
              ORDER BY ies.id DESC";
}

$result = $conn->query($query);

$data = [];

if ($result) {
    $i = 1;
    while ($row = $result->fetch_assoc()) {
        $feesValue = !empty($row['fees_paid']) ? ucfirst($row['fees_paid']) : 'Unpaid';
        $isPaid = (strtolower($row['fees_paid']) === 'paid');

        // Address mapping
        $address1 = $row['permanent_address'] ?? '';
        $address2 = $row['current_address'] ?? '';

        $data[] = [
            $i++,
            // Attended
            (strtolower(trim($row['attended'] ?? '')) === 'yes'
                ? '<span class="badge bg-success">Yes</span>'
                : '<span class="badge bg-danger">No</span>'
            ),
            htmlspecialchars($row['attended_time'] ?? ''),
            // Pack Collected
            (($row['pack_collected'] ?? 0) == 1
                ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Yes</span>'
                : '<span class="badge bg-warning"><i class="fas fa-times-circle"></i> No</span>'
            ),
            htmlspecialchars($row['student_registration_id'] ?? $row['student_code'] ?? ''),
            htmlspecialchars($row['full_name'] ?? ''),
            htmlspecialchars($row['programme'] . ' - ' . $row['batch']),
            htmlspecialchars($row['nic'] ?? ''),
            htmlspecialchars($row['contact_no'] ?? ''),
            htmlspecialchars($row['landline_no'] ?? ''),
            // Fees
            $isPaid
                ? '<span class="badge bg-success">' . htmlspecialchars($feesValue) . '</span>'
                : '<span class="badge bg-danger">' . htmlspecialchars($feesValue) . '</span>',
            htmlspecialchars($row['qualifications'] ?? ''),
            htmlspecialchars($row['organization'] ?? ''),
            htmlspecialchars($row['gender_title'] ?? ''),
            htmlspecialchars($row['date_of_birth'] ?? ''),
            htmlspecialchars($row['nationality'] ?? ''),
            htmlspecialchars($row['email'] ?? ''),
            htmlspecialchars($row['bms_email'] ?? ''),
            htmlspecialchars($address1),
            htmlspecialchars($address2),
            // Email sent column (always sent as these are from induction_emails_sent)
            '<span class="badge bg-info">Sent</span>',
            htmlspecialchars($row['email_sent_time'] ?? ''),
            htmlspecialchars($row['email_sent_by'] ?? '')
        ];
    }
}

echo json_encode([
    "data" => $data
], JSON_UNESCAPED_UNICODE);
$conn->close();
?>
