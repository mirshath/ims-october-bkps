<?php
include("database/connection.php");

$notifications = [];

// ✅ Corrected SQL (you missed a closing quote earlier)
$notifSql = "SELECT * FROM notifications ORDER BY created_at DESC";
$notifResult = mysqli_query($conn, $notifSql);

if ($notifResult && mysqli_num_rows($notifResult) > 0) {
    while ($row = mysqli_fetch_assoc($notifResult)) {
        $notifications[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($notifications);
?>
