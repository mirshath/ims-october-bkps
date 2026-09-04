<?php
include("database/connection.php");

$notifications = [];
// Modified SQL query to limit results to 5
// $notifSql = "SELECT * FROM notifications WHERE DATE(created_at) = CURDATE() AND status = 'unread' ORDER BY created_at DESC LIMIT 5";

$notifSql = "SELECT * FROM notifications WHERE status = 'unread' ORDER BY created_at DESC LIMIT 9";
$notifResult = mysqli_query($conn, $notifSql);

if ($notifResult && mysqli_num_rows($notifResult) > 0) {
    while ($row = mysqli_fetch_assoc($notifResult)) {
        $notifications[] = $row;
    }
}

echo json_encode($notifications);
