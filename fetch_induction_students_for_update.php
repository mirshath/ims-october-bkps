<?php
session_start();
include 'database/connection.php';

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Get all induction students with required fields
$programmeFilter = isset($_GET['programme']) ? trim($_GET['programme']) : '';

$sql = "SELECT id, full_name, date_of_birth, nic, programme, contact_no, fees, pack_collected 
          FROM induction_students";

if ($programmeFilter !== '') {
    $sql .= " WHERE programme = '" . mysqli_real_escape_string($conn, $programmeFilter) . "'";
}

$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get the fees value from database
        $feesValue = trim($row['fees'] ?? '');

        // Determine if fees is Paid or Unpaid
        // Check if the value contains "paid" (case insensitive)
        // If empty, null, or doesn't contain "paid", consider it as Unpaid
        $feesStatus = 'Unpaid'; // Default to Unpaid

        if (!empty($feesValue)) {
            $feesValueLower = strtolower($feesValue);
            // Check if it contains "paid" (handles "Paid", "Paid ", "Unpaid", etc.)
            if (strpos($feesValueLower, 'paid') !== false && strpos($feesValueLower, 'unpaid') === false) {
                $feesStatus = 'Paid';
            } else if (strpos($feesValueLower, 'unpaid') !== false) {
                $feesStatus = 'Unpaid';
            }
        }

        $data[] = [
            'id' => $row['id'],
            'full_name' => htmlspecialchars($row['full_name'] ?? ''),
            'date_of_birth' => htmlspecialchars($row['date_of_birth'] ?? ''),
            'nic' => htmlspecialchars($row['nic'] ?? ''),
            'programme' => htmlspecialchars($row['programme'] ?? ''),
            'contact_no' => htmlspecialchars($row['contact_no'] ?? ''),
            'fees' => $feesStatus,
            'fees_raw' => $feesValue,
            'pack_collected' => intval($row['pack_collected'] ?? 0)
        ];
    }
}

echo json_encode([
    "data" => $data
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>