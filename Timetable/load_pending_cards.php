<?php
include("../database/connection.php");

$date = $_POST['date'];
$main_id = $_POST['main_id'];

// Get current date
$today = date('Y-m-d');

// BLOCK PAST DATES - Cannot access or modify past dates
if($date < $today){
    echo '<div class="alert alert-danger">❌ ACCESS DENIED: Cannot combine past date reservations. Only today and future dates can be modified.</div>';
    exit();
}

// Get main reservation time
$main_res = mysqli_fetch_assoc(mysqli_query($conn,"SELECT start_time, end_time FROM class_reservations WHERE id='$main_id'"));
$main_start = $main_res['start_time'];
$main_end = $main_res['end_time'];

// ONLY load reservations with EXACT same time
$q = mysqli_query($conn,"
SELECT r.*, c.lectuerhallname as hall_name, p.program_name, b.batch_name
FROM class_reservations r
LEFT JOIN classroom c ON c.class_id = r.hall_id
LEFT JOIN program_table p ON r.programme = p.program_code
LEFT JOIN batch_table b ON r.batch = b.id
WHERE r.date='$date'
AND (r.approve_status='Pending' OR r.approve_status IS NULL)
AND (r.combine_group IS NULL OR r.combine_group = '')
AND r.id != '$main_id'
AND r.start_time = '$main_start'
AND r.end_time = '$main_end'
AND r.date >= '$today'
ORDER BY r.start_time
");

if(mysqli_num_rows($q) > 0){

    while($r = mysqli_fetch_assoc($q)){
?>
<div class="card p-2 mb-2 border">
    <div class="form-check">
        <input class="form-check-input combine-checkbox" type="checkbox" name="combine_ids[]" value="<?= $r['id'] ?>" id="card_<?= $r['id'] ?>" data-time="<?= $r['start_time'] . '-' . $r['end_time'] ?>">
        <label class="form-check-label" for="card_<?= $r['id'] ?>">
            <div class="fw-bold">Module: <?= htmlspecialchars($r['module']) ?></div>
            <div class="small">
                <strong>Programme:</strong> <?= htmlspecialchars($r['program_name'] ?? $r['programme']) ?><br>
                <strong>Batch:</strong> <?= htmlspecialchars($r['batch_name'] ?? $r['batch']) ?><br>
                <strong>Time:</strong> <?= $r['start_time'] ?> - <?= $r['end_time'] ?><br>
                <strong>Students:</strong> <?= $r['student_count'] ?> (Online: <?= $r['online_student'] ?>)
            </div>
        </label>
    </div>
</div>

<?php 
    }
    
} else {
    echo '<div class="alert alert-warning">No other pending reservations found with the "SAME TIME SLOT" for this date.</div>';
}
?>