<?php
session_start();
include("../database/connection.php");
/** @var mysqli $conn */

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if ($role === 'super_admin') {
    $query = "SELECT DISTINCT pt.* 
              FROM program_table pt
              INNER JOIN feedback_links fl ON pt.program_code = fl.programme_id
              INNER JOIN feedback_submissions fs ON CAST(fl.id AS UNSIGNED) = fs.link_id
              WHERE fl.active = 1
              ORDER BY pt.program_name";
} else {
    $query = "
        SELECT DISTINCT pt.*
        FROM program_allocation_user AS pau
        INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
        INNER JOIN feedback_links fl ON pt.program_code = fl.programme_id
        INNER JOIN feedback_submissions fs ON CAST(fl.id AS UNSIGNED) = fs.link_id
        WHERE pau.user_id = ? AND fl.active = 1
        ORDER BY pt.program_name
    ";
}

if ($role === 'super_admin') {
    $result = mysqli_query($conn, $query);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

$programmes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $programmes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($programmes);
?>
