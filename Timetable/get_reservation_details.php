<?php
include("../database/connection.php");

$id = $_POST['id'];

$q = mysqli_query($conn,"
SELECT r.*, c.lectuerhallname as hall_name, p.program_name, b.batch_name
FROM class_reservations r
LEFT JOIN classroom c ON c.class_id = r.hall_id
LEFT JOIN program_table p ON r.programme = p.program_code
LEFT JOIN batch_table b ON r.batch = b.id
WHERE r.id='$id'
");

$r = mysqli_fetch_assoc($q);

?>

<div class="card p-2 mb-2 border-primary main-time" data-time="<?= $r['start_time'] . '-' . $r['end_time'] ?>">
    <div class="fw-bold text-primary">Main Reservation (Will be combined automatically)</div>
    <div class="small">
        <strong>Module:</strong> <?= htmlspecialchars($r['module']) ?><br>
        <strong>Programme:</strong> <?= htmlspecialchars($r['program_name'] ?? $r['programme']) ?><br>
        <strong>Batch:</strong> <?= htmlspecialchars($r['batch_name'] ?? $r['batch']) ?><br>
        <strong>Time:</strong> <?= $r['start_time'] ?> - <?= $r['end_time'] ?><br>
        <strong>Students:</strong> <?= $r['student_count'] ?> (Online: <?= $r['online_student'] ?>)<br>
        <strong>Hall:</strong> <?= htmlspecialchars($r['hall_name'] ?? 'Not Assigned') ?>
    </div>
</div>