<?php
include("database/connection.php");

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $sql = "UPDATE notifications SET status='read' WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error";
    }
}
