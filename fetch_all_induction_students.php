<?php
session_start();
include 'database/connection.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Get filter parameters
$programmeFilter = isset($_GET['programme']) ? trim($_GET['programme']) : '';
$feesFilter = isset($_GET['fees']) ? $_GET['fees'] : '';
$attendedFilter = isset($_GET['attended']) ? $_GET['attended'] : '';
$emailSentFilter = isset($_GET['email_sent']) ? $_GET['email_sent'] : '';
$packCollectedFilter = isset($_GET['pack_collected']) ? $_GET['pack_collected'] : '';

// Build WHERE clause
$whereConditions = [];

if ($programmeFilter !== '') {
    $whereConditions[] = "programme = '" . mysqli_real_escape_string($conn, $programmeFilter) . "'";
}

if ($feesFilter !== '') {
    if ($feesFilter === 'paid') {
        // Show records where fees or paid column contains 'paid' (case insensitive)
        $whereConditions[] = "(
            LOWER(TRIM(COALESCE(fees, ''))) LIKE '%paid%' OR 
            LOWER(TRIM(COALESCE(paid, ''))) LIKE '%paid%'
        ) AND (
            LOWER(TRIM(COALESCE(fees, ''))) NOT LIKE '%unpaid%' AND
            LOWER(TRIM(COALESCE(paid, ''))) NOT LIKE '%unpaid%'
        )";
    } elseif ($feesFilter === 'unpaid') {
        // Show records where fees/paid is unpaid, empty, or null (but NOT paid)
        $whereConditions[] = "(
            LOWER(TRIM(COALESCE(fees, ''))) LIKE '%unpaid%' OR
            LOWER(TRIM(COALESCE(paid, ''))) LIKE '%unpaid%' OR
            (
                LOWER(TRIM(COALESCE(fees, ''))) NOT LIKE '%paid%' AND
                LOWER(TRIM(COALESCE(paid, ''))) NOT LIKE '%paid%' AND
                (fees IS NULL OR fees = '' OR paid IS NULL OR paid = '')
            )
        )";
    }
}

if ($attendedFilter !== '') {
    if ($attendedFilter === 'yes') {
        $whereConditions[] = "LOWER(TRIM(COALESCE(attended, ''))) = 'yes'";
    } elseif ($attendedFilter === 'no') {
        $whereConditions[] = "(LOWER(TRIM(COALESCE(attended, ''))) = 'no' OR attended IS NULL OR attended = '')";
    }
}

if ($emailSentFilter !== '') {
    if ($emailSentFilter === '1') {
        $whereConditions[] = "email_sent = 1";
    } elseif ($emailSentFilter === '0') {
        $whereConditions[] = "(email_sent = 0 OR email_sent IS NULL)";
    }
}

if ($packCollectedFilter !== '') {
    if ($packCollectedFilter === '1') {
        $whereConditions[] = "pack_collected = 1";
    } elseif ($packCollectedFilter === '0') {
        $whereConditions[] = "(pack_collected = 0 OR pack_collected IS NULL)";
    }
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all data
$query = "SELECT * FROM induction_students $whereClause ORDER BY id DESC";
$result = $conn->query($query);

$data = [];

if ($result) {
    $i = 1;
    while ($row = $result->fetch_assoc()) {
        // Format the data for display
        $feesValue = trim($row['fees'] ?? '') ?: trim($row['paid'] ?? '') ?: 'N/A';
        $isPaid = (strpos(strtolower($feesValue), 'paid') !== false);

        $data[] = [
            $i++,
                // Attended column
            (strtolower(trim($row['attended'] ?? '')) === 'yes'
                ? '<span class="badge bg-success">Yes</span>'
                : '<span class="badge bg-danger">No</span>'
            ),
            htmlspecialchars($row['attended_time'] ?? ''),
                // Pack collected column
            (($row['pack_collected'] ?? 0) == 1
                ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Yes</span>'
                : '<span class="badge bg-warning"><i class="fas fa-times-circle"></i> No</span>'
            ),
            htmlspecialchars($row['ref_no'] ?? ''),
            htmlspecialchars($row['full_name'] ?? ''),
            htmlspecialchars($row['programme'] ?? ''),
            htmlspecialchars($row['nic'] ?? ''),
            htmlspecialchars($row['contact_no'] ?? ''),
            htmlspecialchars($row['landline_no'] ?? ''),
            // Fees/Paid column
            $isPaid
            ? '<span class="badge bg-success">' . htmlspecialchars($feesValue) . '</span>'
            : '<span class="badge bg-danger">' . htmlspecialchars($feesValue) . '</span>',
            htmlspecialchars($row['qualification'] ?? ''),
            htmlspecialchars($row['institute'] ?? ''),
            htmlspecialchars($row['gender'] ?? ''),
            htmlspecialchars($row['date_of_birth'] ?? ''),
            htmlspecialchars($row['country_of_residence'] ?? ''),
            htmlspecialchars($row['nationality'] ?? ''),
            htmlspecialchars($row['email'] ?? ''),
            htmlspecialchars($row['bms_email'] ?? ''),
            htmlspecialchars($row['address1'] ?? ''),
            htmlspecialchars($row['address2'] ?? ''),
            htmlspecialchars($row['address_country'] ?? ''),
            htmlspecialchars($row['postal_code'] ?? ''),
                // Email sent column
            (($row['email_sent'] ?? 0) == 1
                ? '<span class="badge bg-info">Sent</span>'
                : '<span class="badge bg-secondary">Not Sent</span>'
            ),
            htmlspecialchars($row['email_sent_time'] ?? ''),
            htmlspecialchars($row['email_sent_by'] ?? ''),
            htmlspecialchars($row['created_at'] ?? '')
        ];
    }
}

echo json_encode([
    "data" => $data
], JSON_UNESCAPED_UNICODE);
