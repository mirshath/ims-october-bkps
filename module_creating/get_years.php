<?php
include("../database/connection.php");

$query = "SELECT * FROM year_table";
$result = mysqli_query($conn, $query);

$years = [];
while ($row = mysqli_fetch_assoc($result)) {
    $years[] = $row;
}

echo json_encode($years);
?>