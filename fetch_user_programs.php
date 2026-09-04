<?php
include("database/connection.php");

if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $result = $conn->query("SELECT program_code FROM program_allocation_user WHERE user_id = $user_id");
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row['program_code'];
    }
    echo json_encode($programs);
}
?>
