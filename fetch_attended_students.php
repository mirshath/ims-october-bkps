<?php
session_start();
include 'database/connection.php';

// $result = $conn->query("
//     SELECT 
//         *
//     FROM induction_students
//     WHERE attended = 'Yes'
//     ORDER BY attended_time DESC
// ");



// Get program filter from GET parameter
$program = isset($_GET['program']) && $_GET['program'] !== 'all' ? $_GET['program'] : null;

// Build query with optional program filter using prepared statements
if ($program !== null) {
    $stmt = $conn->prepare("SELECT * FROM induction_students WHERE attended = 'Yes' AND programme = ? ORDER BY attended_time DESC");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM induction_students WHERE attended = 'Yes' ORDER BY attended_time DESC");
}

$data = [];

$i = 1;
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $i++,
        htmlspecialchars($row['full_name']),
        htmlspecialchars($row['nic']),
        htmlspecialchars($row['programme']),
        htmlspecialchars($row['email']),
        htmlspecialchars($row['contact_no']),
        // htmlspecialchars($row['fees']),
        (strtolower(trim($row['fees'])) == 'paid'
            ? '<span class="badge bg-success">Paid</span>'
            : '<span class="badge bg-danger">' . htmlspecialchars(trim($row['fees'])) . '</span>'
        ),
        '<span class="badge bg-success">Yes</span>',
        // htmlspecialchars($row['pack_collected'])
        ($row['pack_collected'] == 1
            ? '<span class="badge bg-success"><i class="fas fa-box-open"></i> <i class="fas fa-check-circle" style="margin-left:-8px; color:#29a300;"></i> Issued</span>'
            : '<span class="badge bg-danger"><i class="fas fa-box-open"></i> <i class="fas fa-times-circle" style="margin-left:-8px; color:#b1001a;"></i> Not issued</span>'
        ),
        htmlspecialchars($row['attended_time'])

    ];
}

echo json_encode([
    "data" => $data
]);
