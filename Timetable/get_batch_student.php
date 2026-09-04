<?php
include("../database/connection.php");

header('Content-Type: application/json');

if(isset($_POST['batch_name']) && !empty($_POST['batch_name'])){
    $batch_name = mysqli_real_escape_string($conn, $_POST['batch_name']);
    
    // Try to get total_student from batch_table
    $query = "SELECT total_student FROM batch_table WHERE batch_name = '$batch_name' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    $total_student = 0;
    if($result && mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $total_student = isset($row['total_student']) ? intval($row['total_student']) : 0;
    }
    
    echo json_encode(['total_student' => $total_student]);
} else {
    echo json_encode(['total_student' => 0, 'error' => 'No batch name provided']);
}
?>