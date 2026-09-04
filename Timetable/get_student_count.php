<?php
include("../database/connection.php");

if(isset($_POST['batch_id'])){

    $batch_id = $_POST['batch_id'];

    $q = mysqli_query($conn,"
        SELECT COUNT(DISTINCT student_code) AS total
        FROM allocate_programme
        WHERE batch_id = '$batch_id'
    ");

    $row = mysqli_fetch_assoc($q);

    echo $row['total'] ?? 0;
}
?>