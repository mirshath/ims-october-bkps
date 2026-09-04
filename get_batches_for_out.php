<?php
include("database/connection.php");

if(isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];
    $query = "SELECT id, batch_name FROM batch_table WHERE programme = $program_id ORDER BY batch_name";
    $result = $conn->query($query);
    
    echo '<option value="">Select a batch</option>';
    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            echo '<option value="'.$row['id'].'">'.$row['batch_name'].'</option>';
        }
    }
}
?>
