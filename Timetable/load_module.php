<!-- <?php 
// include("../database/connection.php"); 
// $batch = $_POST['batch']; 

// $res = mysqli_query($conn,"SELECT module_name FROM modules WHERE programme_id='$batch'"); 

// echo "<option value=''>Select Module</option>"; 
// while($row=mysqli_fetch_assoc($res))
//     { echo "<option>".$row['module_name']."</option>"; }

?> -->



<?php
include("../database/connection.php");

$programme = $_POST['programme'];

$res = mysqli_query($conn,"
SELECT module_name
FROM modules
WHERE programme_id='$programme'
ORDER BY module_name ASC
");

echo "<option value=''>Select Module</option>";

while($row=mysqli_fetch_assoc($res)){

    echo "<option value='".$row['module_name']."'>
    ".$row['module_name']."
    </option>";

}
?>