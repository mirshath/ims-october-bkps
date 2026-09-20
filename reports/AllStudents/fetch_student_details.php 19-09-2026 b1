<?php
include("../../database/connection.php");

// Get filter values
$program_id = $_POST['program_id'] ?? null;
$batch_id = $_POST['batch_id'] ?? null;
$status = $_POST['status'] ?? null;

// Build the query with proper JOIN conditions (no university join!)
$query = "SELECT 
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    ap.student_registration_id,ap.*,
    pt.program_name,
    bt.batch_name,
    s.*
FROM students s
INNER JOIN allocate_programme ap ON s.student_code = ap.student_code
INNER JOIN program_table pt ON ap.programme_code = pt.program_code
INNER JOIN batch_table bt ON ap.batch_id = bt.id
WHERE 1=1
";

// Add filters if they are set
if ($program_id) {
    $query .= " AND ap.programme_code = '" . mysqli_real_escape_string($conn, $program_id) . "'";
}
if ($batch_id) {
    $query .= " AND ap.batch_id = '" . mysqli_real_escape_string($conn, $batch_id) . "'";
}
if ($status) {
    // Convert status to match your database values
    $statusMap = [
        'active' => 'Active',
        'drop' => 'drop',
        'transferred' => 'transferred',
        'completed' => 'completed'
    ];
    $mappedStatus = $statusMap[$status] ?? $status;
    $query .= " AND LOWER(ap.status) = LOWER('" . mysqli_real_escape_string($conn, $mappedStatus) . "')";
}

// For debugging
// echo $query; // Uncomment this line to see the actual query being executed

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Generate table rows
$output = "";
while ($row = mysqli_fetch_assoc($result)) {
    $output .= "<tr>";
    $output .= "<td>" . htmlspecialchars($row['new_student_registration_id']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['student_registration_id']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['student_code']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['nic']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['title']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['student_name']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['certificate_name']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['date_of_birth']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['nationality']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['permanent_address']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['current_address']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['mobile']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['telephone']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['personal_email']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['bms_email']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['occupation']) . "</td>";

    $output .= "<td>" . htmlspecialchars($row['status']) . "</td>";
    $output .= "<td>" . htmlspecialchars($row['entered_by']) . "</td>";
    $output .= "</tr>";
}

if (mysqli_num_rows($result) == 0) {
    $output = "<tr><td colspan='17' class='text-center'>No results found</td></tr>";
}

echo $output;
mysqli_close($conn);
