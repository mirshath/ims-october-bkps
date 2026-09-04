<?php
session_start();
include("database/connection.php");

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query to join students with their allocation, program, and batch info
$query = "SELECT 
            s.student_code, 
            s.first_name, 
            s.last_name, 
            s.nic, 
            ap.student_registration_id,
            pt.program_name,
            bt.batch_name
          FROM students s
          LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code
          LEFT JOIN program_table pt ON ap.programme_code = pt.program_code
          LEFT JOIN batch_table bt ON ap.batch_id = bt.id";

if ($search) {
    $query .= " WHERE s.first_name LIKE '%$search%' 
                OR s.last_name LIKE '%$search%' 
                OR s.nic LIKE '%$search%' 
                OR s.student_code LIKE '%$search%'
                OR ap.student_registration_id LIKE '%$search%'";
}

$query .= " GROUP BY s.student_code LIMIT 50";

$result = mysqli_query($conn, $query);
$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    $regId = $row['student_registration_id'] ?? 'No Reg ID';
    $progInfo = ($row['program_name'] ?? 'No Program') . ' (' . ($row['batch_name'] ?? 'No Batch') . ')';

    $students[] = [
        'id' => $row['student_code'],
        'text' => "{$row['student_code']} | {$regId} | {$fullName} | {$row['nic']} | {$progInfo}"
    ];
}

echo json_encode($students);
$conn->close();
?>