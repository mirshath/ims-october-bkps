<?php
session_start();
include("../database/connection.php");

$programme_id = $_POST['programme_id'];
$role = $_SESSION['role'] ?? '';

if ($role === 'super_admin') {
    $query = "SELECT * FROM batch_table WHERE programme  = '$programme_id' ORDER BY id";
} else {
    $query = "SELECT * FROM batch_table WHERE programme  = '$programme_id' AND batch_hide_active = 'active' ORDER BY id";
}
$result = mysqli_query($conn, $query);

$batches = [];
while ($row = mysqli_fetch_assoc($result)) {
    $batches[] = $row;
}

echo json_encode($batches);
?>
