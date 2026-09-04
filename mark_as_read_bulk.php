<?php
include("database/connection.php");

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['ids']) && count($data['ids']) > 0) {
    $ids = array_map('intval', $data['ids']);
    $idList = implode(",", $ids);

    $sql = "UPDATE notifications SET status='read' WHERE id IN ($idList)";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "no_ids";
}
?>
