<?php
require 'database/connection.php';

// Auto-sync new students to stu_login
echo "<h3>Auto-sync new students to stu_login</h3>";

// Select active students who do NOT have login yet
$sql = "SELECT s.student_code, s.bms_email, s.nic, s.date_of_birth
        FROM students s
        LEFT JOIN stu_login sl ON s.student_code = sl.student_code
        WHERE sl.student_code IS NULL
        AND s.active = 1
        AND s.bms_email IS NOT NULL
        AND s.nic IS NOT NULL";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<b>No new students to sync.</b>";
    exit;
}

$count = 0;
while ($row = $result->fetch_assoc()) {
    $student_code = trim($row['student_code']);
    $email        = trim($row['bms_email']);
    $nic          = trim($row['nic']);
    $dob          = trim($row['date_of_birth']);

    // Skip if email or NIC empty
    if (empty($email) || empty($nic)) continue;

    // Temporary password = DOB YYYYMMDD
    $temp_password = date('Ymd', strtotime($dob));
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO stu_login (student_code, password, bms_email, nic, is_temp_password, created_at) 
                            VALUES (?, ?, ?, ?, 1, NOW())");
    $stmt->bind_param("ssss", $student_code, $hashed_password, $email, $nic);
    if ($stmt->execute()) {
        echo "✔ Synced $student_code ($email)<br>";
        $count++;
    } else {
        echo "❌ Error syncing $student_code<br>";
    }
}

echo "<hr><b>Total synced students: $count</b>";
?>
