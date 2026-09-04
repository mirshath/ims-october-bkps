<?php
include("../database/connection.php");

$query = "SELECT id, year_name as name FROM year_table ORDER BY year_name";
$result = mysqli_query($conn, $query);

$years = [];
while ($row = mysqli_fetch_assoc($result)) {
    $years[] = $row;
}

echo json_encode($years);
?>
