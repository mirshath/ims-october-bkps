<?php
include("../database/connection.php");

$prog = $_POST['prog'];

$res = mysqli_query($conn,"SELECT id,batch_name FROM batch_table WHERE programme='$prog'");

echo "<option value=''>Select Batch</option>";
while($row=mysqli_fetch_assoc($res)){
echo "<option value='".$row['id']."'>".$row['batch_name']."</option>";
}
?>