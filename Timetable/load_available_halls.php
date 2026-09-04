<?php
include("../database/connection.php");

$date = $_POST['date'] ?? '';
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';
$students = $_POST['students'] ?? 0;
$type = $_POST['type'] ?? '';

/* ================= FETCH HALLS ================= */
$q = mysqli_query($conn,"
SELECT *
FROM classroom
ORDER BY branch, lectuerhallname
");

echo "<option value=''>Select Hall</option>";

$bmsPrinted = false;
$cgsPrinted = false;

while($h=mysqli_fetch_assoc($q)){

    $hall_id = $h['class_id'];
    $seat = (int)$h['totalseat'];
    $status = $h['status'];
    $branch = $h['branch'];

    /* ================= BRANCH DIVIDER ================= */
    if($branch=='BMS' && !$bmsPrinted){

        echo "<optgroup label='BMS Classrooms'>";
        $bmsPrinted = true;
    }

    if($branch=='CGS' && !$cgsPrinted){

        if($bmsPrinted){
            echo "</optgroup>";
        }

        echo "<optgroup label='CGS Classrooms'>";
        $cgsPrinted = true;
    }

    /* ================= BOOKED CHECK ================= */
    $booked = false;

    if($date!='' && $start!='' && $end!=''){

        $check = mysqli_query($conn,"
        SELECT id
        FROM class_reservations
        WHERE hall_id='$hall_id'
        AND date='$date'
        AND (
            ('$start' < end_time)
            AND
            ('$end' > start_time)
        )
        ");

        if(mysqli_num_rows($check)>0){
            $booked = true;
        }
    }

    /* ================= LOW SEAT CHECK ================= */

    $lowSeat = false;

    if(
        $type=="Lectuer" ||
        $type=="Guest Lectuer"
    ){

        if($students > $seat){
            $lowSeat = true;
        }
    }

    /* ================= DISABLE ================= */

    $disabled = false;

    if(
        $status==0 ||
        $booked ||
        $lowSeat
    ){
        $disabled = true;
    }


    /* ================= COLORS ================= */

if($branch=='BMS'){

    /* ACTIVE */
    if(!$disabled){

        $bg = '#042d5c';
        $color = '#ffffff';

    } else {

        /* DISABLED */
        $bg = '#cfe2ff';
        $color = '#000000';
    }

} else {

    /* ACTIVE */
    if(!$disabled){

        $bg = '#dc3545';
        $color = '#ffffff';

    } else {

        /* DISABLED */
        $bg = '#f8d7da';
        $color = '#000000';
    }
}
    /* ================= STATUS TEXT ================= */

    $statusText = '';

    if($status == 0){

        // $statusText = ' - Deactive';
        $statusText = ' - Not in operation';

    }

    if($booked){

        $statusText = ' - Booked';

    }

    if($lowSeat){

        $statusText = ' - Low Seats';

    }

    /* ================= OPTION ================= */

$hallName = $h['lectuerhallname'] . " (Seats: ".$seat.") " . $statusText;

echo "
<option
value='".$h['class_id']."'
data-color='".$color."'
".($disabled ? 'disabled' : '')."
>

".$hallName."

</option>
";
}

if($cgsPrinted || $bmsPrinted){
    echo "</optgroup>";
}
?>