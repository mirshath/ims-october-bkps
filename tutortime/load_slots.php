<?php
//session_start();
require_once "../database/connection.php";
include("../includes/header.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= ROLE HANDLING (ONLY ADDITION) ================= */

if (!isset($_SESSION['user_id'])) {
    throw new Exception("Unauthorized");
}

$role = $_SESSION['role'] ?? '';

if ($role === 'super_admin') {
    $tutor_id = (int)($_GET['tutor_id'] ?? $_SESSION['user_id']);
} else {
    if ($role !== 'lecture') {
        throw new Exception("Unauthorized");
    }
    $tutor_id = (int)$_SESSION['user_id'];
}

/* ================= ORIGINAL CODE (UNCHANGED BELOW) ================= */
$program_id = $_GET['program_id'] ?? '';
$batch_id   = $_GET['batch_id'] ?? '';
$session_id = $_GET['session_id'] ?? '';

// Validate inputs
if(!$program_id || !$batch_id || !$session_id){
    echo "<div class='text-danger'>Invalid selection</div>";
    exit;
}

/* ================= GET allocation_id ================= */
$alloc_stmt = $conn->prepare("
    SELECT allocation_id 
    FROM tutor_session_allocation 
    WHERE tutor_id=? 
    AND program_code=? 
    AND batch_id=? 
    AND session_id=?
");

$alloc_stmt->bind_param("iiii", $tutor_id, $program_id, $batch_id, $session_id);
$alloc_stmt->execute();
$alloc_res = $alloc_stmt->get_result();
$alloc_row = $alloc_res->fetch_assoc();

if(!$alloc_row){
    echo "<div class='text-danger'>Allocation not found</div>";
    exit;
}

$alloc_id = $alloc_row['allocation_id'];

/* ================= FETCH SLOTS ================= */
$stmt = $conn->prepare("
    SELECT t.*, st.first_name, st.last_name, st.bms_email
    FROM tutor_time_slots t
    LEFT JOIN students st ON st.student_code = t.student_id
    WHERE t.tutor_id = ?
    AND t.allocation_id = ?
    AND t.is_hidden = 0
    ORDER BY t.slot_date, t.start_time
");

$stmt->bind_param("ii", $tutor_id, $alloc_id);
$stmt->execute();
$res = $stmt->get_result();

/* ================= GROUP BY DATE ================= */
$cards = [];

while ($row = $res->fetch_assoc()) {
    $status = $row['student_id'] ? 'booked' : 'pending';

    $cards[$row['slot_date']][] = [
        'id' => $row['id'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'status' => $status
    ];
}


// Display slots grouped by date
 foreach ($cards as $date => $slotArray): ?>
<div class="slot-date mt-4 mb-3"><strong><?= htmlspecialchars($date) ?></strong></div>

<ul class="product-plans">








<style>

.slot-date{
    font-size:20px;
    font-weight:700;
    margin-top:25px;
    margin-bottom:10px;
}

ul.product-plans{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    list-style:none;
    padding:0;

}

.product-plan{
    --overlap-size:1rem;
    width:200px;
    min-height: 150px;
    padding:15px;
    border-radius:15px;
    background:#f8f9fa;
    box-shadow:0 6px 14px rgba(0,0,0,0.15);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    border-color: #6c757d;
    border-style: solid;
    border-width: 1px;
    border-radius: 1px 25px 1px 25px;
}

.product-plan .title{
    font-size:18px;
    font-weight:700;
    text-align:center;
}

.product-plan .name{
    margin-top:5px;
    text-align:center;
    font-size:14px;
    padding:4px;
    background:var(--accent-color);
    color:white;
    border-radius:5px;
}

.features{
    margin-top:10px;
    padding-left:10px;
}

.features li{
    font-size:13px;
    margin-bottom:4px;
}

.features li.check::before{
    content:"✔ ";
    color:green;
}

.features li.cross::before{
    content:"✖ ";
    color:red;
}



.slot-buttons{
    display:flex;
    gap:5px;
    margin-top:10px;
}

.slot-buttons .btn{
    flex:1;
    padding:5px;
    border:none;
    border-radius:6px;
    font-size:12px;
    cursor:pointer;
}

.add-btn{
    background:#28a745;
    color:white;
}

.cancel-btn{
    background:#6c757d;
    color:white;
}

.deny-btn{
    background:#dc3545;
    color:white;
}



</style>



<?php foreach ($slotArray as $slot):
    $bgColor = $slot['status'] === 'pending' ? '#dc3545' : '#31694E';
    $borderColor = '#535353';
    $textColor = $slot['status'] === 'pending' ? '#dc3545' : '#31694E';
?>

<li class="product-plan" style="--accent-color: <?= $bgColor ?>; color: <?= $textColor ?>">

    <div class="title">
        <?= date('H:i', strtotime($slot['start_time'])) ?> - <?= date('H:i', strtotime($slot['end_time'])) ?>
    </div>

    <div class="name">
        <?= $slot['first_name'] ? htmlspecialchars($slot['first_name'].' '.$slot['last_name']) : 'Pending' ?>
    </div>

    <ul class="features">
        <li class="<?= $slot['first_name'] ? 'check' : 'cross' ?>">
            Student Not Assigned
        </li>
        <!-- <li class="check">
            Slot Available
        </li> -->
    </ul>

    <div class="slot-buttons">

        <?php if($slot['first_name']): ?>
            <button class="btn deny-btn"
                onclick="confirmAction(<?= $slot['id'] ?>,'denied')">
                Deny
            </button>

            <button class="btn cancel-btn"
                onclick="confirmAction(<?= $slot['id'] ?>,'cancelled')">
                Cancel
            </button>

        <?php else: ?>

            <button class="btn add-btn"
                onclick="openAddStudent(<?= $slot['id'] ?>)">
                Add
            </button>

            <button class="btn cancel-btn"
                onclick="confirmAction(<?= $slot['id'] ?>,'cancelled')">
                Cancel
            </button>

        <?php endif; ?>

    </div>

</li>

<?php endforeach; ?>

</ul>
<?php endforeach; ?>




