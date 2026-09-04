<?php
include("../database/connection.php");

$batch_id = $_POST['batch_id'];

// First get the program_id from batch_table
$batch_query = "SELECT programme FROM batch_table WHERE id = '$batch_id'";
$batch_result = mysqli_query($conn, $batch_query);
$batch_data = mysqli_fetch_assoc($batch_result);
$programme_id = $batch_data['programme'];

// Then get modules for this programme
$query = "SELECT id, module_name as name FROM modules 
          WHERE programme_id = '$programme_id' 
          ORDER BY module_name";
$result = mysqli_query($conn, $query);

$modules = [];
while ($row = mysqli_fetch_assoc($result)) {
    $modules[] = $row;
}

echo json_encode($modules);
?>