<?php
 //session_start();


 if(isset($_SESSION['message'])){
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}


    include("database/connection.php");


    if (!isset($_SESSION['username'])) {
        // header("location: login.php");
        echo '<script>window.location.href = "login";</script>';
        // exit();
    }


    
// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------

/* =========================
BLOCK ALL ACTIONS ON PAST DATES (Including Past Months & Years)
========================= */
$today_date = date('Y-m-d');

// Function to check if date is past (compares year, month, day)
function isPastDate($conn, $id) {
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT date FROM class_reservations WHERE id='$id'"));
    if($check) {
        $reservation_date = $check['date'];
        $today_date = date('Y-m-d');
        
        // Compare dates properly
        if(strtotime($reservation_date) < strtotime($today_date)) {
            return true;
        }
    }
    return false;
}

// Function to check if a given date is past
function isDatePast($date) {
    return (strtotime($date) < strtotime(date('Y-m-d')));
}

// Block CREATE on past dates
if(isset($_POST['save_request'])){
    $request_date = $_POST['date'];
    if(isDatePast($request_date)){
        $_SESSION['message'] = "❌ Cannot create reservation for past dates!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block EDIT on past dates
if(isset($_GET['edit'])){
    $edit_id = $_GET['edit'];
    if(isPastDate($conn, $edit_id)){
        $_SESSION['message'] = "❌ Cannot edit past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block APPROVE on past dates
if(isset($_GET['approve'])){
    $approve_id = $_GET['approve'];
    if(isPastDate($conn, $approve_id)){
        $_SESSION['message'] = "❌ Cannot approve past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block CANCEL on past dates
if(isset($_GET['cancel'])){
    $cancel_id = $_GET['cancel'];
    if(isPastDate($conn, $cancel_id)){
        $_SESSION['message'] = "❌ Cannot cancel past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block PENDING on past dates
if(isset($_GET['pending'])){
    $pending_id = $_GET['pending'];
    if(isPastDate($conn, $pending_id)){
        $_SESSION['message'] = "❌ Cannot change status of past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block APPROVE COMBINED on past dates
if(isset($_GET['approve_combined']) && isset($_GET['combine_group'])){
    $combine_group = $_GET['combine_group'];
    $check_any = mysqli_fetch_assoc(mysqli_query($conn, "SELECT date FROM class_reservations WHERE combine_group='$combine_group' LIMIT 1"));
    if($check_any && isDatePast($check_any['date'])){
        $_SESSION['message'] = "❌ Cannot approve combined group - contains past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}

// Block UPDATE REQUEST on past dates
if(isset($_POST['update_request'])){
    $update_id = $_POST['id'];
    if(isPastDate($conn, $update_id)){
        $_SESSION['message'] = "❌ Cannot update past date reservations!";
        $_SESSION['message_type'] = "danger";
        echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
        exit();
    }
}


/* =========================
MESSAGE
========================= */
$message = "";
$messageType = "success";

/* =========================
SAVE REQUEST
========================= */
if(isset($_POST['save_request'])){

    $type = $_POST['type'];
    $programme = $_POST['programme'];
    $batch = $_POST['batch'];
    
    $module = !empty($_POST['module_select'])
        ? $_POST['module_select']
        : $_POST['module_text'];
    
    $module_text = $_POST['module_text'];

    $lecturer = !empty($_POST['lecturer_select'])
        ? $_POST['lecturer_select']
        : $_POST['lecturer_text'];
    
    $lecturer_text = $_POST['lecturer_text'];

    $date = $_POST['date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $hall = $_POST['hall'];
    $note = $_POST['note'];
    
    $student_count = $_POST['student_count'] ?? 0;
    $online_student = $_POST['online_student'] ?? 0;

    mysqli_query($conn," 
    INSERT INTO class_reservations
    (
        type,
        programme,
        batch,
        module,
        module_text,
        lecturer,
        lecturer_text,
        note,
        date,
        start_time,
        end_time,
        hall_id,
        approve_status,
        student_count,
        online_student
    )
    VALUES
    (
        '$type',
        '$programme',
        '$batch',
        '$module',
        '$module_text',
        '$lecturer',
        '$lecturer_text',
        '$note',
        '$date',
        '$start',
        '$end',
        '$hall',
        'Pending',
        '$student_count',
        '$online_student'
    )
    ");

    $_SESSION['message'] = "Reservation Request Created Successfully";
    $_SESSION['message_type'] = "success";
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
COMBINE RESERVATIONS
========================= */
if(isset($_POST['combine_reservations'])){

    $main_id = $_POST['main_id'];
    
    // Get all selected IDs from the form
    $combine_ids = isset($_POST['all_selected_ids']) ? $_POST['all_selected_ids'] : [];
    
    if(empty($combine_ids) && isset($_POST['combine_ids'])){
        $combine_ids = $_POST['combine_ids'];
    }
    
    // Ensure main_id is included
    if(!in_array($main_id, $combine_ids)){
        $combine_ids[] = $main_id;
    }
    
    // Remove duplicates and empty values
    $combine_ids = array_unique(array_filter($combine_ids));
    
    if(count($combine_ids) >= 2){
        $combine_group = 'COMB-' . time() . '-' . rand(100, 999);
        
        // Update each selected reservation
        $success_count = 0;
        foreach($combine_ids as $cid){
            $update_query = "UPDATE class_reservations SET combine_group='$combine_group' WHERE id='$cid'";
            if(mysqli_query($conn, $update_query)){
                $success_count++;
            }
        }
        
        $_SESSION['message'] = $success_count . " Reservations Combined Successfully with ID: $combine_group";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Please select at least 2 reservations to combine";
        $_SESSION['message_type'] = "danger";
    }
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
REMOVE COMBINE - Remove entire group (all reservations with same combine_group)
========================= */
if(isset($_POST['remove_combine'])){

    $remove_id = $_POST['remove_id'];
    
    // Get the current combine_group of this reservation
    $check = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT combine_group FROM class_reservations WHERE id='$remove_id'
    "));
    
    if(!empty($check['combine_group'])){
        // Remove combine_group from ALL reservations in this group
        mysqli_query($conn,"
        UPDATE class_reservations
        SET combine_group=NULL
        WHERE combine_group='{$check['combine_group']}'
        ");
        
        $_SESSION['message'] = "Entire combined group has been removed. All reservations are now independent.";
        $_SESSION['message_type'] = "info";
    } else {
        $_SESSION['message'] = "Reservation is not in any combine group";
        $_SESSION['message_type'] = "warning";
    }
    
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
APPROVE
========================= */
if(isset($_GET['approve'])){

    $id = $_GET['approve'];

    // get combine group
    $res = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT combine_group
    FROM class_reservations
    WHERE id='$id'
    "));

    if(!empty($res['combine_group'])){

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Approved'
        WHERE combine_group='{$res['combine_group']}'
        ");

    }else{

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Approved'
        WHERE id='$id'
        ");
    }

    $_SESSION['message'] = "Reservation Approved Successfully";
    $_SESSION['message_type'] = "success";
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
APPROVE COMBINED
========================= */
if(isset($_GET['approve_combined']) && isset($_GET['combine_group'])){

    $combine_group = $_GET['combine_group'];

    mysqli_query($conn," 
    UPDATE class_reservations
    SET approve_status='Approved'
    WHERE combine_group='$combine_group'
    ");

    $_SESSION['message'] = "All Combined Reservations Approved Successfully";
    $_SESSION['message_type'] = "success";
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
PENDING AGAIN
========================= */
if(isset($_GET['pending'])){

    $id = $_GET['pending'];

    $res = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT combine_group
    FROM class_reservations
    WHERE id='$id'
    "));

    if(!empty($res['combine_group'])){

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Pending'
        WHERE combine_group='{$res['combine_group']}'
        ");

    }else{

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Pending'
        WHERE id='$id'
        ");
    }

    $_SESSION['message'] = "Reservation Changed to Pending";
    $_SESSION['message_type'] = "success";
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
CANCEL
========================= */
if(isset($_GET['cancel'])){

    $id = $_GET['cancel'];

    $res = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT combine_group
    FROM class_reservations
    WHERE id='$id'
    "));

    if(!empty($res['combine_group'])){

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Cancelled',
            hall_id=NULL
        WHERE combine_group='{$res['combine_group']}'
        ");

    }else{

        mysqli_query($conn,"
        UPDATE class_reservations
        SET approve_status='Cancelled',
            hall_id=NULL
        WHERE id='$id'
        ");
    }

    $_SESSION['message'] = "Reservation Cancelled";
    $_SESSION['message_type'] = "danger";
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
UPDATE RESERVATION
========================= */
if(isset($_POST['update_request'])){

    $id = $_POST['id'];
    $hall = $_POST['hall'];

    // Check if this is a combined reservation
    $check_group = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT combine_group FROM class_reservations WHERE id='$id'
    "));

    if(!empty($check_group['combine_group'])){
        // Update all reservations in the group with the same hall
        mysqli_query($conn," 
        UPDATE class_reservations 
        SET hall_id='$hall'
        WHERE combine_group='{$check_group['combine_group']}'
        ");
        $_SESSION['message'] = "All Combined Reservations Hall Updated Successfully";
        $_SESSION['message_type'] = "success";
    } else {
        // Update single reservation
        $module = !empty($_POST['module_select'])
            ? $_POST['module_select']
            : $_POST['module_text'];
        
        $module_text = $_POST['module_text'];

        $lecturer = !empty($_POST['lecturer_select'])
            ? $_POST['lecturer_select']
            : $_POST['lecturer_text'];
        
        $lecturer_text = $_POST['lecturer_text'];

        mysqli_query($conn," 
        UPDATE class_reservations SET

        module='$module',
        module_text='$module_text',
        lecturer='$lecturer',
        lecturer_text='$lecturer_text',
        note='{$_POST['note']}',
        date='{$_POST['date']}',
        start_time='{$_POST['start_time']}',
        end_time='{$_POST['end_time']}',
        hall_id='$hall',
        student_count='{$_POST['student_count']}',
        online_student='{$_POST['online_student']}'

        WHERE id='$id'
        ");
        $_SESSION['message'] = "Reservation Updated Successfully";
        $_SESSION['message_type'] = "success";
    }
    
    echo '<script>window.location.href = window.location.href.split("?")[0];</script>';
    exit();
}

/* =========================
EDIT DATA
========================= */
$edit = null;
$group_reservations = [];

if(isset($_GET['edit'])){

    $id = $_GET['edit'];

    $main = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT *
    FROM class_reservations
    WHERE id='$id'
    "));

    if(!empty($main['combine_group'])){

        $group = $main['combine_group'];

        $q = mysqli_query($conn,"
        SELECT r.*,
               p.program_name,
               b.batch_name,
               c.lectuerhallname
        FROM class_reservations r
        LEFT JOIN program_table p ON r.programme = p.program_code
        LEFT JOIN batch_table b ON r.batch = b.id
        LEFT JOIN classroom c ON c.class_id = r.hall_id
        WHERE r.combine_group='$group'
        ORDER BY r.id
        ");

        $total_students = 0;
        $total_online = 0;
        $combined_hall = null;
        
        while($row = mysqli_fetch_assoc($q)){
            $group_reservations[] = $row;
            $total_students += intval($row['student_count']);
            $total_online += intval($row['online_student']);
            $combined_hall = $row['hall_id'];
            $edit = $row;
        }
        
        $edit['student_count'] = $total_students;
        $edit['online_student'] = $total_online;
        $edit['hall_id'] = $combined_hall;
        $edit['is_combined'] = true;

    }else{

        $q = mysqli_query($conn,"
        SELECT r.*,
               p.program_name,
               b.batch_name,
               c.lectuerhallname
        FROM class_reservations r
        LEFT JOIN program_table p ON r.programme = p.program_code
        LEFT JOIN batch_table b ON r.batch = b.id
        LEFT JOIN classroom c ON c.class_id = r.hall_id
        WHERE r.id='$id'
        ");

        $edit = mysqli_fetch_assoc($q);
        $edit['is_combined'] = false;
    }
}

/* =========================
FILTER DATE
========================= */
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

/* =========================
DATA
========================= */
$lecturers = mysqli_query($conn," 
SELECT *
FROM lecturer_table
ORDER BY lecturer_name
");

// Get classroom inventory status
$inventoryStatus = [];
$invQuery = mysqli_query($conn, "SELECT class_id, working_qty FROM class_inventory");
if($invQuery){
    while($inv = mysqli_fetch_assoc($invQuery)){
        $inventoryStatus[$inv['class_id']] = $inv['working_qty'];
    }
}

/* =========================
WEEK FILTER LOGIC
========================= */
$currentWeekStart = isset($_GET['week_start']) 
    ? $_GET['week_start'] 
    : date('Y-m-d', strtotime('monday this week'));

$currentWeekStart = date('Y-m-d', strtotime('monday this week', strtotime($currentWeekStart)));
$currentWeekEnd = date('Y-m-d', strtotime($currentWeekStart . ' +6 days'));
$prevWeekStart = date('Y-m-d', strtotime($currentWeekStart . ' -7 days'));
$nextWeekStart = date('Y-m-d', strtotime($currentWeekStart . ' +7 days'));

    include("includes/header.php");
?>

<!DOCTYPE html>
<html>
<head>
<title>Classroom Allocation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<style>

.card{ border:none; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
.fc-event{ padding:2px 4px; font-size:10px; white-space:normal; line-height:1.3; cursor:pointer; }
.fc-daygrid-event{ white-space:normal; }
.pending{ background:#ffc107 !important; border:none !important; color:#000 !important; }
.approved{ background:#198754 !important; border:none !important; color:#fff !important; }
.cancelled{ background:#dc3545 !important; border:none !important; color:#fff !important; }
.select2-container .select2-selection--single{ height:32px !important; }
.form-control, .form-select, .btn{ font-size:12px; padding:0.25rem 0.5rem; }

.btn-group-sm>.btn, .btn-sm{ padding:0.2rem 0.4rem; font-size:11px; }
.week-nav{ display:flex; gap:5px; justify-content:flex-end; margin-bottom:8px; }
.hall-info{ font-size:10px; }
h5{ font-size:1rem; margin-bottom:0.75rem; }
.modal-body{ padding:0.75rem; }

.table .text-nowrap {
    white-space: nowrap !important;
}

.table .text-wrap {
    white-space: normal !important;
}

.table td {
    white-space: normal !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
}

.option-danger {
    color: #dc3545 !important;
    font-weight: bold;
    background-color: #fff5f5 !important;
}
.option-warning {
    color: #fd7e14 !important;
    font-weight: bold;
    background-color: #fff8f0 !important;
}

.fc-daygrid-day-events {
    min-height: 40px;
}
.fc-daygrid-event {
    white-space: normal !important;
    margin: 1px 2px !important;
    padding: 2px !important;
    font-size: 9px !important;
}
.fc-daygrid-event .fc-event-title {
    white-space: normal !important;
    word-break: break-word !important;
}
.fc-event-main {
    overflow: wrap;
}
.fc-daygrid-day-number {
    font-size: 11px;
}

.fc-event.approved {
    background-color: #198754 !important;
    border-color: #198754 !important;
    color: #ffffff !important;
}
.fc-event.approved .fc-event-main,
.fc-event.approved .fc-event-main * {
    color: #ffffff !important;
}

.fc-event.pending {
    background-color: #ffc107 !important;
    border-color: #ffc107 !important;
    color: #042d5c !important;
}
.fc-event.pending .fc-event-main,
.fc-event.pending .fc-event-main * {
    color: #042d5c !important;
}

.fc-event.cancelled {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    color: #ffffff !important;
}
.fc-event.cancelled .fc-event-main,
.fc-event.cancelled .fc-event-main * {
    color: #ffffff !important;
}

.combine-badge {
    background-color: #6f42c1;
    color: white;
    font-size: 8px;
    padding: 2px 4px;
    border-radius: 3px;
    margin-left: 4px;
}

.combine-group {
    border-left: 3px solid #6f42c1;
    background-color: #f8f9fa;
}

.group-details-box {
    background-color: #f8f9fa;
    border-left: 4px solid #6f42c1;
    padding: 10px;
    margin-bottom: 15px;
    max-height: 300px;
    overflow-y: auto;
}
</style>
</head>
<body>

    <div id="wrapper">
        <?php include("nav.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("includes/topnav.php"); ?>

                <div class="p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">Reservation - Allocation</h4>
                    </div>

<?php if($message!=''){ ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show py-1 mb-2" style="font-size:12px">
    <?= $message ?>
    <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
</div>
<?php } ?>

<div class="row g-2">

<!-- LEFT PANEL - FORM -->
<div class="col-md-4">
    <div class="card shadow p-2 mb-2">
        <h5 class="mb-2">
            <?php if(isset($edit)): ?>
                <?php if($edit['is_combined']): ?>
                    Edit Combined Group (<?= count($group_reservations) ?> Reservations)
                <?php else: ?>
                    Edit Reservation
                <?php endif; ?>
            <?php else: ?>
                Create Reservation
            <?php endif; ?>
        </h5>
        
        <?php if(isset($edit) && $edit['is_combined'] && !empty($group_reservations)): ?>
        <div class="group-details-box mb-3">
            <strong class="text-primary">Combined Group: <?= $edit['combine_group'] ?></strong>
            <hr class="my-2">
            <div class="small">
                <?php foreach($group_reservations as $grp): ?>
                <div class="mb-2 pb-2 border-bottom">
                    <strong>Reservation ID: <?= $grp['id'] ?></strong><br>
                    <strong>Programme:</strong> <?= htmlspecialchars($grp['program_name'] ?? $grp['programme']) ?><br>
                    <strong>Batch:</strong> <?= htmlspecialchars($grp['batch_name'] ?? $grp['batch']) ?><br>
                    <strong>Module:</strong> <?= htmlspecialchars($grp['module']) ?><br>
                    <strong>Time:</strong> <?= $grp['start_time'] ?> - <?= $grp['end_time'] ?><br>
                    <strong>Students:</strong> <?= $grp['student_count'] ?> (Online: <?= $grp['online_student'] ?>)
                    <form method="POST" style="display:inline-block; float:right;">
                        <input type="hidden" name="remove_id" value="<?= $grp['id'] ?>">
                        <button type="submit" name="remove_combine" class="btn btn-danger btn-sm" onclick="return confirm('WARNING: This will remove the ENTIRE combined group from ALL reservations!')">Remove Entire Group</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <hr class="my-2">
            <strong>Combined Total Students: <?= $edit['student_count'] ?></strong><br>
            <strong>Combined Total Online: <?= $edit['online_student'] ?></strong><br>
            <strong>In-Class Total: <?= $edit['student_count'] - $edit['online_student'] ?></strong>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="reservationForm">
            <?php if(isset($edit)){ ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php } ?>
            
            <div class="row g-1 mb-2">
                <div class="mb-2">
                    <label class="form-label small fw-bold">Type</label>
                    <input type="text" class="form-control form-control-sm" value="<?= $edit['type'] ?? '' ?>" disabled>
                    <input type="hidden" name="type" id="type_val" value="<?= $edit['type'] ?? '' ?>">
                </div>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Programme</label>
                <input type="text" class="form-control form-control-sm" value="<?= isset($edit['program_name']) ? $edit['program_name'] : ($edit['programme'] ?? '') ?>" disabled>
                <input type="hidden" name="programme" id="programme_name" value="<?= $edit['programme'] ?? '' ?>">
            </div>
            <div class="mb-2">
                <label class="form-label small fw-bold">Batch</label>
                <input type="text" class="form-control form-control-sm" value="<?= isset($edit['batch_name']) ? $edit['batch_name'] : ($edit['batch'] ?? '') ?>" disabled>
                <input type="hidden" name="batch" id="batch_name" value="<?= $edit['batch'] ?? '' ?>">
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Module</label>
                <select name="module_select" id="module_select" class="form-select form-select-sm select2">
                    <option value="">Select Module</option>
                </select>
                <input type="text" name="module_text" id="module_text" class="form-control form-control-sm mt-1" placeholder="Or Enter New Module" value="<?= isset($edit['module_text']) ? htmlspecialchars($edit['module_text']) : '' ?>">
                <?php if(isset($edit) && !empty($edit['module']) && !$edit['is_combined']): ?>
                <small class="text-muted d-block mt-1 text-info">✓ Current Module: <?= htmlspecialchars($edit['module']) ?></small>
                <?php endif; ?>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Lecturer</label>
                <select name="lecturer_select" id="lecturer_select" class="form-select form-select-sm select2">
                    <option value="">Select Lecturer</option>
                    <?php 
                    $lecturers = mysqli_query($conn,"SELECT * FROM lecturer_table ORDER BY lecturer_name");
                    while($l=mysqli_fetch_assoc($lecturers)){ ?>
                        <option value="<?= htmlspecialchars($l['lecturer_name']) ?>" <?= (($edit['lecturer'] ?? '')==$l['lecturer_name'])?'selected':'' ?>><?= htmlspecialchars($l['lecturer_name']) ?></option>
                    <?php } ?>
                </select>
                <input type="text" name="lecturer_text" id="lecturer_text" class="form-control form-control-sm mt-1" placeholder="Or Enter New Lecturer" value="<?= $edit['lecturer_text'] ?? '' ?>">
                <?php if(isset($edit) && !empty($edit['lecturer']) && !$edit['is_combined']): ?>
                <small class="text-muted d-block mt-1 text-info">✓ Current Lecturer: <?= htmlspecialchars($edit['lecturer']) ?></small>
                <?php endif; ?>
            </div>
            
            <div class="row g-1 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Total Students</label>
                    <input type="number" name="student_count" id="student_count" class="form-control form-control-sm" value="<?= isset($edit['student_count']) ? $edit['student_count'] : '0' ?>" readonly style="background:#e9ecef">
                    <?php if(isset($edit) && $edit['is_combined']): ?>
                    <small class="text-muted">(Sum of all combined reservations)</small>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Online Students</label>
                    <input type="number" name="online_student" id="online_student" class="form-control form-control-sm" value="<?= isset($edit['online_student']) ? $edit['online_student'] : '0' ?>" min="0" <?= isset($edit) && $edit['is_combined'] ? 'readonly' : '' ?> style="<?= isset($edit) && $edit['is_combined'] ? 'background:#e9ecef' : '' ?>">
                    <?php if(isset($edit) && $edit['is_combined']): ?>
                    <small class="text-muted">(Sum of all combined)</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">In-Class Students</label>
                <input type="text" id="inclass_student" class="form-control form-control-sm" readonly style="background:#e9ecef; font-weight:bold; color:#0d6efd;" value="0">
                <small class="text-muted">Total - Online = In-Class</small>
            </div>
            
            <div class="row g-1 mb-2">
                <div class="col-5">
                    <label class="form-label small fw-bold">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" required value="<?= $edit['date'] ?? '' ?>" <?= isset($edit) && $edit['is_combined'] ? 'readonly' : '' ?>>
                </div>
                <div class="col-3">
                    <label class="form-label small fw-bold">Start</label>
                    <input type="time" name="start_time" class="form-control form-control-sm" required value="<?= $edit['start_time'] ?? '' ?>" <?= isset($edit) && $edit['is_combined'] ? 'readonly' : '' ?>>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-bold">End</label>
                    <input type="time" name="end_time" class="form-control form-control-sm" required value="<?= $edit['end_time'] ?? '' ?>" <?= isset($edit) && $edit['is_combined'] ? 'readonly' : '' ?>>
                </div>
            </div>
            
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" id="showAllHall">
                <label class="form-check-label small">Show All Classrooms</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" id="showLowCapacity">
                <label class="form-check-label small">Show Low Capacity Classrooms</label>
            </div>
            <div class="form-check mb-1">
                <input class="form-check-input" type="checkbox" id="showInactive">
                <label class="form-check-label small">Show Inactive Classrooms</label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="showBooked">
                <label class="form-check-label small">⚠️ Show Booked/BRACKDOWN Classrooms</label>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Lecture Hall</label>
                <select name="hall" id="hall_select" class="form-select form-select-sm select2 hall-select" required>
                    <option value="Null">Class Unassigned</option>
                    <option value="">Select Hall</option>
                    <?php
                    $bmsPrinted = false;
                    $cgsPrinted = false;
                    $halls = mysqli_query($conn,"SELECT * FROM classroom ORDER BY branch, lectuerhallname");
                    $savedHallId = isset($edit['hall_id']) ? $edit['hall_id'] : '';
                    
                    while($h=mysqli_fetch_assoc($halls)){
                        $status = $h['status'];
                        $classId = $h['class_id'];
                        $isBooked = isset($inventoryStatus[$classId]) && $inventoryStatus[$classId] == 0;
                        
                        if($h['branch']=='BMS' && !$bmsPrinted){
                            echo "<optgroup label='BMS Classrooms'>";
                            $bmsPrinted = true;
                        }
                        if($h['branch']=='CGS' && !$cgsPrinted){
                            if($bmsPrinted) echo "</optgroup>";
                            echo "<optgroup label='CGS Classrooms'>";
                            $cgsPrinted = true;
                        }
                        
                        $selected = ($savedHallId == $classId) ? 'selected' : '';
                        
                        $statusBadges = [];
                        if($status==0){
                            $statusBadges[] = '[INACTIVE]';
                        }
                        if($isBooked){
                            $statusBadges[] = '[BOOKED]';
                            $statusBadges[] = '[BRACKDOWN]';
                        }
                        $badgeText = !empty($statusBadges) ? ' ' . implode(' ', $statusBadges) : '';
                        $warningSymbol = '';
                        
                        if($isBooked){
                            $warningSymbol = '⚠️ ';
                        } elseif($status==0){
                            $warningSymbol = '🔴 ';
                        }
                        
                        $disabled = ($status==0 && $savedHallId != $classId) ? true : false;
                        ?>
                        <option value="<?= $h['class_id'] ?>" <?= $selected ?> <?= $disabled?'disabled':'' ?> data-seat="<?= $h['totalseat'] ?>" data-branch="<?= $h['branch'] ?>" data-name="<?= htmlspecialchars($h['lectuerhallname']) ?>" data-booked="<?= $isBooked ? '1' : '0' ?>" data-inactive="<?= ($status==0)?'1':'0' ?>">
                            <?= $warningSymbol ?><?= $h['branch'] ?> - <?= htmlspecialchars($h['lectuerhallname']) ?> (<?= $h['totalseat'] ?> seats)<?= $badgeText ?>
                        </option>
                    <?php } ?>
                </select>
                <small class="text-muted hall-info"></small>
                <?php if(isset($edit) && $edit['is_combined']): ?>
                <small class="text-info d-block mt-1">⚠️ This hall will be assigned to ALL reservations in this combined group</small>
                <?php endif; ?>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Note</label>
                <textarea name="note" class="form-control form-control-sm" rows="2" <?= isset($edit) && $edit['is_combined'] ? 'readonly' : '' ?>><?= $edit['note'] ?? '' ?></textarea>
                <?php if(isset($edit) && $edit['is_combined']): ?>
                <small class="text-muted">Notes are individual per reservation</small>
                <?php endif; ?>
            </div>
            
            <?php if(isset($edit)){ ?>
                <button type="submit" name="update_request" class="btn btn-warning w-100 btn-sm">
                    <?= $edit['is_combined'] ? 'Update Hall for All Combined' : 'Update Reservation' ?>
                </button>
            <?php } else { ?>
                <button type="submit" name="save_request" class="btn btn-primary w-100 btn-sm">Wait for Update...</button>
            <?php } ?>
        </form>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="col-md-8">
    <div class="card shadow p-2 mb-2">
        <h5 class="mb-1">Reservation Calendar</h5>
        <div id="calendar" style="height:420px"></div>
    </div>
    
    <div class="card shadow p-2 mb-2">
        <form method="GET" class="row g-1" id="filterForm">
            <div class="col-md-5">
                <input type="date" name="filter_date" id="filter_date_input" class="form-control form-control-sm" value="<?= $filterDate ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-danger w-100" style="height: 28px;">Filter</button>
            </div>
            <div class="col-md-2">
                <button type="button" id="prevDayBtn" class="btn btn-dark w-100" style="height: 28px;">&lt; Before</button>
            </div>
            <div class="col-md-2">
                <button type="button" id="nextDayBtn" class="btn btn-dark w-100" style="height: 28px;">Next &gt;</button>
            </div>
        </form>
    </div>
 </div>
 
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Reservation Requests</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">

    <div class="card shadow p-2">
        <div class="week-nav">
            <a href="?week_start=<?= $prevWeekStart ?>" class="btn btn-outline-secondary btn-sm">← Prev Week</a>
            <a href="?week_start=<?= date('Y-m-d', strtotime('monday this week')) ?>" class="btn btn-outline-primary btn-sm">Current Week</a>
            <a href="?week_start=<?= $nextWeekStart ?>" class="btn btn-outline-secondary btn-sm">Next Week →</a>
        </div>
        <div class="small text-muted mb-1 text-end">Week: <?= date('M d', strtotime($currentWeekStart)) ?> - <?= date('M d, Y', strtotime($currentWeekEnd)) ?></div>
        
        <ul class="nav nav-tabs mb-1" style="font-size:12px">
            <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="tab" data-bs-target="#all">All</button></li>
            <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#pending">Pending</button></li>
            <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#approved">Approved</button></li>
            <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="tab" data-bs-target="#cancelled">Cancelled</button></li>
        </ul>
        
    <div class="tab-content">
    <!-- ALL TAB - Weekly Filter with Navigation -->
    <div class="tab-pane fade show active" id="all">
        <?php 
        $allQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                    FROM class_reservations r 
                    LEFT JOIN classroom c ON c.class_id = r.hall_id
                    LEFT JOIN program_table p ON r.programme = p.program_code
                    LEFT JOIN batch_table b ON r.batch = b.id
                    WHERE r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' 
                    ORDER BY r.date ASC, r.start_time ASC";
        $allResult = mysqli_query($conn, $allQuery);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 10%">Type</th>
                        <th style="width: 20%">Programme / Batch</th>
                        <th style="width: 20%">Module / Lecturer</th>
                        <th style="width: 10%">Date</th>
                        <th style="width: 10%">Time</th>
                        <th style="width: 10%">Hall</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $displayedGroups = [];
                while($r = mysqli_fetch_assoc($allResult)){ 
                    $status = $r['approve_status'] ?? 'Pending';
                    $programmeName = $r['program_name'] ?? $r['programme'];
                    $batchName = $r['batch_name'] ?? $r['batch'];
                    $combineBadge = !empty($r['combine_group']) ? '<span class="combine-badge">Combined</span>' : '';
                    $isPastDate = isDatePast($r['date']);
                    
                    // Skip if already displayed as part of a group
                    if(!empty($r['combine_group']) && in_array($r['combine_group'], $displayedGroups)){
                        continue;
                    }
                    if(!empty($r['combine_group'])){
                        $displayedGroups[] = $r['combine_group'];
                    }
                ?>
                    <tr>
                        <td class="text-wrap">
                            <?= htmlspecialchars($r['type'] ?? '') ?> <?= $combineBadge ?>
                            <?php if(!empty($r['combine_group'])){ ?>
                            <div><small class="text-danger"><?= $r['combine_group'] ?></small></div>
                            <?php } ?>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($programmeName) ?></strong><br>
                            <small class="text-muted">Batch: <?= htmlspecialchars($batchName) ?></small>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($r['module'] ?? 'N/A') ?></strong><br>
                            <small><?= htmlspecialchars($r['lecturer'] ?? 'N/A') ?></small>
                            <?php if(!empty($r['note'])): ?>
                                <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 30)) ?></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= date('d M Y', strtotime($r['date'])) ?></td>
                        <td class="text-nowrap"><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($r['lectuerhallname'] ?? 'Not Assigned') ?></td>
                        <td class="text-nowrap">
                            <?php if($status=='Approved'){ ?>
                                <span class="badge bg-success">Approved</span>
                            <?php } elseif($status=='Cancelled'){ ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php } else { ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php } ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if(!$isPastDate): ?>
                            <div class="btn-group btn-group-sm">
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                <?php if($status!='Approved'){ ?>
                                    <a href="?approve=<?= $r['id'] ?>" class="btn btn-success" onclick="return confirm('Approve?')">Approve</a>
                                <?php } ?>
                                <?php if($status=='Approved'){ ?>
                                    <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Pending?')">Pending</a>
                                <?php } ?>
                                <?php if($status!='Cancelled'){ ?>
                                    <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                                <?php } ?>
                            </div>
                            <?php else: ?>
                            <span class="text-danger small">Past Date</span>
                            <?php endif; ?>
                            <?php if(!empty($r['combine_group'])){ ?>
                                <div class="mt-1">
                                    <form method="POST" style="display:inline-block; width:100%;">
                                        <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="remove_combine" class="btn btn-secondary btn-sm w-100" onclick="return confirm('Remove entire combined group?')">Remove Group</button>
                                    </form>
                                    <a href="?approve_combined=1&combine_group=<?= $r['combine_group'] ?>" class="btn btn-info btn-sm w-100 mt-1" onclick="return confirm('Approve all combined?')">Approve All</a>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if(mysqli_num_rows($allResult)==0){ ?>
                    <tr><td colspan="8" class="text-center">No records for this week</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- PENDING TAB - ALL records grouped by date (No weekly filter) -->
    <div class="tab-pane fade" id="pending">
        <?php 
        $pendingQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                        FROM class_reservations r 
                        LEFT JOIN classroom c ON c.class_id = r.hall_id
                        LEFT JOIN program_table p ON r.programme = p.program_code
                        LEFT JOIN batch_table b ON r.batch = b.id
                        WHERE (r.approve_status='Pending' OR r.approve_status IS NULL)
                        ORDER BY r.date ASC, r.start_time ASC";
        $pendingResult = mysqli_query($conn, $pendingQuery);
        
        $pendingByDate = [];
        while($row = mysqli_fetch_assoc($pendingResult)){
            $pendingByDate[$row['date']][] = $row;
        }
        ?>
        
        <?php if(empty($pendingByDate)): ?>
            <div class="alert alert-info text-center">No pending reservations found</div>
        <?php else: ?>
            <?php foreach($pendingByDate as $date => $reservations): ?>
            <div class="mb-4">
                <div class="bg-light p-2 rounded mb-2" style="background-color: #e9ecef !important;">
                    <strong style="color: #dc3545;">📅 <?= date('l, d F Y', strtotime($date)) ?></strong>
                    <span class="badge bg-success ms-2"><?= count($reservations) ?> Reservation(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 10%">Type</th>
                                <th style="width: 20%">Programme / Batch</th>
                                <th style="width: 20%">Module / Lecturer</th>
                                <th style="width: 10%">Time</th>
                                <th style="width: 10%">Hall</th>
                                <th style="width: 10%">Students</th>
                                <th style="width: 10%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $displayedPendingGroups = [];
                        foreach($reservations as $r): 
                            $programmeName = $r['program_name'] ?? $r['programme'];
                            $batchName = $r['batch_name'] ?? $r['batch'];
                            $combineBadge = !empty($r['combine_group']) ? '<span class="combine-badge">Combined</span>' : '';
                            $isPastDate = isDatePast($r['date']);
                            
                            if(!empty($r['combine_group']) && in_array($r['combine_group'], $displayedPendingGroups)){
                                continue;
                            }
                            if(!empty($r['combine_group'])){
                                $displayedPendingGroups[] = $r['combine_group'];
                            }
                        ?>
                            <tr>
                                <td class="text-wrap">
                                    <?= htmlspecialchars($r['type'] ?? '') ?> <?= $combineBadge ?>
                                    <?php if(!empty($r['combine_group'])){ ?>
                                    <div><small class="text-danger"><?= $r['combine_group'] ?></small></div>
                                    <?php } ?>
                                </td>
                                <td class="text-wrap">
                                    <strong><?= htmlspecialchars($programmeName) ?></strong><br>
                                    <small class="text-muted">Batch: <?= htmlspecialchars($batchName) ?></small>
                                </td>
                                <td class="text-wrap">
                                    <strong><?= htmlspecialchars($r['module'] ?? 'N/A') ?></strong><br>
                                    <small><?= htmlspecialchars($r['lecturer'] ?? 'N/A') ?></small>
                                    <?php if(!empty($r['note'])): ?>
                                        <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 30)) ?></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                                <td class="text-wrap"><?= htmlspecialchars($r['lectuerhallname'] ?? 'Not Assigned') ?></td>
                                <td class="text-nowrap">
                                    Total: <?= $r['student_count'] ?><br>
                                    Online: <?= $r['online_student'] ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if(!$isPastDate): ?>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                        <a href="?approve=<?= $r['id'] ?>" class="btn btn-success" onclick="return confirm('Approve?')">Approve</a>
                                        <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-danger small">Past Date</span>
                                    <?php endif; ?>
                                    <?php if(!empty($r['combine_group'])){ ?>
                                        <div class="mt-1">
                                            <form method="POST" style="display:inline-block; width:100%;">
                                                <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                                                <button type="submit" name="remove_combine" class="btn btn-secondary btn-sm w-100" onclick="return confirm('Remove entire combined group?')">Remove Group</button>
                                            </form>
                                            <a href="?approve_combined=1&combine_group=<?= $r['combine_group'] ?>" class="btn btn-info btn-sm w-100 mt-1" onclick="return confirm('Approve all combined?')">Approve All</a>
                                        </div>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- APPROVED TAB - Weekly Filter with Navigation -->
    <div class="tab-pane fade" id="approved">
        <?php 
        $approvedQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                        FROM class_reservations r 
                        LEFT JOIN classroom c ON c.class_id = r.hall_id
                        LEFT JOIN program_table p ON r.programme = p.program_code
                        LEFT JOIN batch_table b ON r.batch = b.id
                        WHERE r.approve_status='Approved' AND r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' 
                        ORDER BY r.date ASC";
        $approvedResult = mysqli_query($conn, $approvedQuery);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 10%">Type</th>
                        <th style="width: 20%">Programme / Batch</th>
                        <th style="width: 20%">Module / Lecturer</th>
                        <th style="width: 10%">Date</th>
                        <th style="width: 10%">Time</th>
                        <th style="width: 10%">Hall</th>
                        <th style="width: 10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $displayedApprovedGroups = [];
                while($r = mysqli_fetch_assoc($approvedResult)){ 
                    $programmeName = $r['program_name'] ?? $r['programme'];
                    $batchName = $r['batch_name'] ?? $r['batch'];
                    $combineBadge = !empty($r['combine_group']) ? '<span class="combine-badge">Combined</span>' : '';
                    $isPastDate = isDatePast($r['date']);
                    
                    if(!empty($r['combine_group']) && in_array($r['combine_group'], $displayedApprovedGroups)){
                        continue;
                    }
                    if(!empty($r['combine_group'])){
                        $displayedApprovedGroups[] = $r['combine_group'];
                    }
                ?>
                    <tr>
                        <td class="text-wrap">
                            <?= htmlspecialchars($r['type'] ?? '') ?> <?= $combineBadge ?>
                            <?php if(!empty($r['combine_group'])){ ?>
                            <div><small class="text-danger"><?= $r['combine_group'] ?></small></div>
                            <?php } ?>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($programmeName) ?></strong><br>
                            <small class="text-muted">Batch: <?= htmlspecialchars($batchName) ?></small>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($r['module'] ?? 'N/A') ?></strong><br>
                            <small><?= htmlspecialchars($r['lecturer'] ?? 'N/A') ?></small>
                            <?php if(!empty($r['note'])): ?>
                                <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 30)) ?></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= date('d M Y', strtotime($r['date'])) ?></td>
                        <td class="text-nowrap"><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($r['lectuerhallname'] ?? 'Not Assigned') ?></td>
                        <td class="text-nowrap">
                            <?php if(!$isPastDate): ?>
                            <div class="btn-group btn-group-sm">
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Pending?')">Pending</a>
                                <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                            </div>
                            <?php else: ?>
                            <span class="text-danger small">Past Date</span>
                            <?php endif; ?>
                            <?php if(!empty($r['combine_group'])){ ?>
                                <div class="mt-1">
                                    <form method="POST" style="display:inline-block; width:100%;">
                                        <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="remove_combine" class="btn btn-secondary btn-sm w-100" onclick="return confirm('Remove entire combined group?')">Remove Group</button>
                                    </form>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if(mysqli_num_rows($approvedResult)==0){ ?>
                    <tr><td colspan="7" class="text-center">No approved records for this week</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- CANCELLED TAB - Weekly Filter with Navigation -->
    <div class="tab-pane fade" id="cancelled">
        <?php 
        $cancelledQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                          FROM class_reservations r 
                          LEFT JOIN classroom c ON c.class_id = r.hall_id
                          LEFT JOIN program_table p ON r.programme = p.program_code
                          LEFT JOIN batch_table b ON r.batch = b.id
                          WHERE r.approve_status='Cancelled' AND r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' 
                          ORDER BY r.date ASC";
        $cancelledResult = mysqli_query($conn, $cancelledQuery);
        ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 10%">Type</th>
                        <th style="width: 20%">Programme / Batch</th>
                        <th style="width: 20%">Module / Lecturer</th>
                        <th style="width: 10%">Date</th>
                        <th style="width: 10%">Time</th>
                        <th style="width: 10%">Hall</th>
                        <th style="width: 10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $displayedCancelledGroups = [];
                while($r = mysqli_fetch_assoc($cancelledResult)){ 
                    $programmeName = $r['program_name'] ?? $r['programme'];
                    $batchName = $r['batch_name'] ?? $r['batch'];
                    $combineBadge = !empty($r['combine_group']) ? '<span class="combine-badge">Combined</span>' : '';
                    $isPastDate = isDatePast($r['date']);
                    
                    if(!empty($r['combine_group']) && in_array($r['combine_group'], $displayedCancelledGroups)){
                        continue;
                    }
                    if(!empty($r['combine_group'])){
                        $displayedCancelledGroups[] = $r['combine_group'];
                    }
                ?>
                    <tr>
                        <td class="text-wrap">
                            <?= htmlspecialchars($r['type'] ?? '') ?> <?= $combineBadge ?>
                            <?php if(!empty($r['combine_group'])){ ?>
                            <div><small class="text-danger"><?= $r['combine_group'] ?></small></div>
                            <?php } ?>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($programmeName) ?></strong><br>
                            <small class="text-muted">Batch: <?= htmlspecialchars($batchName) ?></small>
                        </td>
                        <td class="text-wrap">
                            <strong><?= htmlspecialchars($r['module'] ?? 'N/A') ?></strong><br>
                            <small><?= htmlspecialchars($r['lecturer'] ?? 'N/A') ?></small>
                            <?php if(!empty($r['note'])): ?>
                                <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 30)) ?></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= date('d M Y', strtotime($r['date'])) ?></td>
                        <td class="text-nowrap"><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($r['lectuerhallname'] ?? 'Not Assigned') ?></td>
                        <td class="text-nowrap">
                            <div class="btn-group btn-group-sm">
                                <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Move to Pending?')">Pending</a>
                            </div>
                            <?php if(!empty($r['combine_group'])){ ?>
                                <div class="mt-1">
                                    <form method="POST" style="display:inline-block; width:100%;">
                                        <input type="hidden" name="remove_id" value="<?= $r['id'] ?>">
                                        <button type="submit" name="remove_combine" class="btn btn-secondary btn-sm w-100" onclick="return confirm('Remove entire combined group?')">Remove Group</button>
                                    </form>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                <?php if(mysqli_num_rows($cancelledResult)==0){ ?>
                    <tr><td colspan="7" class="text-center">No cancelled records for this week</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
    </div>



<!-- MODALS -->
<div class="modal fade" id="eventModal">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header py-1">
                <h6 class="modal-title">Reservation Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetails" style="font-size:12px"></div>
        </div>
    </div>
</div>

<!-- COMBINE MODAL -->
<div class="modal fade" id="combineModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Combine Reservations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <strong>Current Reservation (Will be included automatically)</strong>
                        </div>
                        <div id="mainCardArea"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <strong>Select additional reservations to combine:</strong>
                        </div>
                        <form method="POST" id="combineForm" action="">
                            <input type="hidden" name="main_id" id="combine_main_id">
                            <input type="hidden" name="combine_date" id="combine_date_val">
                            <div id="combineCardsArea"></div>
                            <button type="submit" name="combine_reservations" class="btn btn-success mt-3 w-100">Combine Selected</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    </div>

<script>
$(document).ready(function(){
    $('.select2').select2({width:'100%', dropdownAutoWidth:true});
    
    function loadModules(programmeName) {
        if(programmeName && programmeName !== ''){
            $.ajax({
                url: 'Timetable/load_module.php',
                method: 'POST',
                data: {programme: programmeName},
                success: function(response){
                    $('#module_select').html(response);
                    $('#module_select').trigger('change.select2');
                    
                    var savedModule = '<?= isset($edit['module']) ? addslashes($edit['module']) : '' ?>';
                    if(savedModule && savedModule !== ''){
                        setTimeout(function(){
                            $('#module_select').val(savedModule).trigger('change');
                        }, 100);
                    }
                },
                error: function(){
                    $('#module_select').html('<option value="">Select Module</option>');
                }
            });
        }
    }
    
    var programmeVal = $('#programme_name').val();
    if(programmeVal && programmeVal !== ''){
        loadModules(programmeVal);
    }
    
    var savedHallId = '<?= isset($edit['hall_id']) ? $edit['hall_id'] : '' ?>';
    if(savedHallId && savedHallId !== ''){
        setTimeout(function(){
            $('#hall_select').val(savedHallId).trigger('change');
        }, 500);
    }
    
    $('#prevDayBtn').on('click', function(){
        var currentDate = $('#filter_date_input').val();
        var prevDate = new Date(currentDate);
        prevDate.setDate(prevDate.getDate() - 1);
        var formattedDate = prevDate.toISOString().split('T')[0];
        window.location.href = '?filter_date=' + formattedDate;
    });
    
    $('#nextDayBtn').on('click', function(){
        var currentDate = $('#filter_date_input').val();
        var nextDate = new Date(currentDate);
        nextDate.setDate(nextDate.getDate() + 1);
        var formattedDate = nextDate.toISOString().split('T')[0];
        window.location.href = '?filter_date=' + formattedDate;
    });
    
    function calculateInClass(){
        var total = parseInt($('#student_count').val()) || 0;
        var online = parseInt($('#online_student').val()) || 0;
        if(online > total){ online = total; $('#online_student').val(online); }
        var inClass = total - online;
        $('#inclass_student').val(inClass);
        return inClass;
    }
    
    function filterHallsByCapacity(){
        var inClass = parseInt($('#inclass_student').val()) || 0;
        var showAll = $('#showAllHall').is(':checked');
        var showLowCapacity = $('#showLowCapacity').is(':checked');
        var showInactive = $('#showInactive').is(':checked');
        var showBooked = $('#showBooked').is(':checked');
        
        $('.hall-select option').each(function(){
            var $opt = $(this);
            var seat = parseInt($opt.data('seat')) || 0;
            var isBooked = $opt.data('booked') == 1;
            var isInactive = $opt.data('inactive') == 1;
            var hasLowCapacity = (seat < inClass) && inClass > 0;
            var origDisabled = $opt.attr('data-original-disabled') === 'true';
            
            if(typeof $opt.attr('data-original-disabled') === 'undefined'){
                $opt.attr('data-original-disabled', $opt.prop('disabled'));
                origDisabled = $opt.prop('disabled');
            }
            
            if(showAll){
                $opt.prop('disabled', false);
                $opt.show();
            } else {
                var shouldShow = true;
                
                if(isInactive && !showInactive){
                    shouldShow = false;
                }
                if(isBooked && !showBooked){
                    shouldShow = false;
                }
                if(hasLowCapacity && !showLowCapacity){
                    shouldShow = false;
                }
                if(origDisabled && !showInactive){
                    shouldShow = false;
                }
                
                if(shouldShow){
                    $opt.show();
                    $opt.prop('disabled', false);
                } else {
                    $opt.hide();
                    $opt.prop('disabled', true);
                }
            }
        });
        
        var selected = $('.hall-select option:selected');
        if(selected.prop('disabled') || !selected.is(':visible')){ 
            $('.hall-select').val(''); 
        }
        $('.hall-select').trigger('change.select2');
    }
    
    function loadBatchTotal(){
        var batchName = $('#batch_name').val();
        if(batchName && batchName.trim() !== '' && batchName !== '0'){
            $.ajax({
                url: 'Timetable/get_batch_student.php',
                method: 'POST',
                data: {batch_name: batchName},
                dataType: 'json',
                success: function(data){
                    if(data && data.total_student){
                        $('#student_count').val(data.total_student);
                    }
                    calculateInClass();
                    filterHallsByCapacity();
                },
                error: function(){
                    calculateInClass();
                    filterHallsByCapacity();
                }
            });
        } else {
            calculateInClass();
            filterHallsByCapacity();
        }
    }
    
    $('#online_student').on('input', function(){
        var val = parseInt($(this).val()) || 0;
        var total = parseInt($('#student_count').val()) || 0;
        if(val > total){ $(this).val(total); }
        calculateInClass();
        filterHallsByCapacity();
    });
    
    $('#showAllHall, #showLowCapacity, #showInactive, #showBooked').on('change', function(){
        filterHallsByCapacity();
        var msg = [];
        if($('#showAllHall').is(':checked')) msg.push('All classrooms');
        if($('#showLowCapacity').is(':checked')) msg.push('Low capacity');
        if($('#showInactive').is(':checked')) msg.push('Inactive');
        if($('#showBooked').is(':checked')) msg.push('⚠️ Booked');
        if(msg.length > 0){
            $('.hall-info').html('<span class="text-info">Showing: ' + msg.join(', ') + '</span>');
        } else {
            $('.hall-info').html('');
        }
    });
    
    $('.hall-select').on('change', function(){
        var inClass = parseInt($('#inclass_student').val()) || 0;
        var opt = $(this).find('option:selected');
        var seat = parseInt(opt.data('seat')) || 0;
        var showAll = $('#showAllHall').is(':checked');
        var isBooked = opt.data('booked') == 1;
        var isInactive = opt.data('inactive') == 1;
        
        if(isBooked){
            $('.hall-info').html('<span class="text-danger">⚠️ DANGER: This classroom is BOOKED/BRACKDOWN!</span>');
        } else if(isInactive && !showAll){
            $('.hall-info').html('<span class="text-warning">⚠️ Warning: This classroom is INACTIVE!</span>');
        } else if(!showAll && seat > 0 && inClass > seat){
            $('.hall-info').html('<span class="text-danger">❌ ERROR: ' + inClass + ' students exceed ' + seat + ' seats!</span>');
        } else if(seat > 0 && inClass > seat){
            $('.hall-info').html('<span class="text-warning">⚠️ Warning: ' + inClass + ' students exceed ' + seat + ' seats. Overcrowding!</span>');
        } else if(seat > 0){
            $('.hall-info').html('<span class="text-success">✓ ' + seat + ' seats - ' + (seat >= inClass ? 'Sufficient' : 'Insufficient') + '</span>');
        }
    });
    
    $('.hall-select option').each(function(){
        if(typeof $(this).attr('data-original-disabled') === 'undefined'){
            $(this).attr('data-original-disabled', $(this).prop('disabled'));
        }
    });
    
    loadBatchTotal();
    calculateInClass();
});

function openCombineModal(id, date){
    // Check if date is today or future
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var checkDate = new Date(date);
    checkDate.setHours(0, 0, 0, 0);
    
    if(checkDate < today){
        alert('Cannot combine past date reservations. Only today and future dates can be modified.');
        return false;
    }
    
    $('#combine_main_id').val(id);
    $('#combine_date_val').val(date);
    
    // Get current reservation time first
    $.ajax({
        url: 'Timetable/get_reservation_details.php',
        type: 'POST',
        data: { id: id },
        success: function(mainData){
            $('#mainCardArea').html(mainData);
            
            // Extract time from main reservation
            var mainTime = $('.main-time').data('time');
            
            // Load other pending reservations with time validation
            $.ajax({
                url: 'Timetable/load_pending_cards.php',
                type: 'POST',
                data: {
                    date: date,
                    main_id: id
                },
                success: function(res){
                    $('#combineCardsArea').html(res);
                    
                    // Add time validation - disable checkboxes with different times
                    var hasDifferentTime = false;
                    $('.combine-checkbox').each(function(){
                        var cardTime = $(this).data('time');
                        if(mainTime !== cardTime){
                            $(this).prop('disabled', true);
                            $(this).closest('.card').addClass('border-danger');
                            hasDifferentTime = true;
                        }
                    });
                    
                    if(hasDifferentTime){
                        $('#combineCardsArea').prepend('<div class="alert alert-danger mb-2">⚠️ ERROR: Cannot combine reservations with different time slots! Only reservations with the SAME time can be combined. Disabled cards have different times.</div>');
                    } else {
                        $('#combineCardsArea').prepend('<div class="alert alert-success mb-2">✓ All displayed reservations have the same time slot and can be combined.</div>');
                    }
                    
                    new bootstrap.Modal(document.getElementById('combineModal')).show();
                },
                error: function(){
                    alert('Error loading reservations. Please refresh and try again.');
                }
            });
        },
        error: function(){
            $('#mainCardArea').html('<div class="alert alert-danger">Error loading main reservation</div>');
        }
    });
}

$(document).on('submit', '#combineForm', function(e){
    var selectedDate = $('#combine_date_val').val();
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var checkDate = new Date(selectedDate);
    checkDate.setHours(0, 0, 0, 0);
    
    if(selectedDate && checkDate < today){
        alert('Cannot combine past date reservations. Only today and future dates can be modified.');
        e.preventDefault();
        return false;
    }
    
    // Collect all checked values
    var selectedIds = [];
    $('input[name="combine_ids[]"]:checked').each(function() {
        selectedIds.push($(this).val());
    });
    
    var mainId = $('#combine_main_id').val();
    
    // Add main ID if not already in selected
    if(selectedIds.indexOf(mainId) === -1) {
        selectedIds.push(mainId);
    }
    
    // Check if any selected card has different time (disabled cards shouldn't be selectable, but double-check)
    var hasDisabledSelected = false;
    $('input[name="combine_ids[]"]:checked').each(function() {
        if($(this).prop('disabled')) {
            hasDisabledSelected = true;
        }
    });
    
    if(hasDisabledSelected) {
        alert('Cannot combine reservations with different time slots! Please only select reservations with the same time.');
        e.preventDefault();
        return false;
    }
    
    // Remove any existing temp inputs
    $('#combineForm').find('.temp-id-input').remove();
    
    // Create hidden inputs for all selected IDs
    for(var i = 0; i < selectedIds.length; i++) {
        $('<input>').attr({
            type: 'hidden',
            name: 'all_selected_ids[]',
            value: selectedIds[i],
            class: 'temp-id-input'
        }).appendTo('#combineForm');
    }
    
    if(selectedIds.length < 2) {
        alert('Please select at least 2 reservations to combine (including current)');
        e.preventDefault();
        return false;
    }
    
    var confirmMsg = 'Combine ' + selectedIds.length + ' reservations? They will share the same Combine Group ID.';
    if(!confirm(confirmMsg)){
        e.preventDefault();
        return false;
    }
    
    return true;
});

window.openCombineModal = openCombineModal;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        height: 'auto',
        contentHeight: 'auto',
        aspectRatio: 1.5,
        headerToolbar: { 
            left: 'prev,next today', 
            center: 'title', 
            right: 'dayGridMonth,timeGridWeek,timeGridDay' 
        },
        eventDisplay: 'block',
        displayEventTime: true,
        displayEventEnd: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventContent: function(arg) {
            let eventDiv = document.createElement('div');
            eventDiv.className = 'fc-event-main';
            
            let timeText = '';
            if (arg.event.start) {
                let startTime = arg.event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
                let endTime = arg.event.end ? arg.event.end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false }) : '';
                timeText = startTime + (endTime ? '-' + endTime : '');
            }
            
            let room = arg.event.extendedProps.room || '';
            let programme = arg.event.extendedProps.programme || '';
            let batch = arg.event.extendedProps.batch || '';
            let type = arg.event.extendedProps.type || '';
            let note = arg.event.extendedProps.note || '';
            let combineGroup = arg.event.extendedProps.combineGroup || '';
            let combineBadge = combineGroup ? '<span class="combine-badge">Combined</span>' : '';
            
            let titleHtml = `<div style="line-height: 1.3; padding: 2px;">
                        <div style="font-size: 11px;font-weight: bold;">${room}${combineBadge}</div>
                        <div style="font-weight: bold; font-size: 11px;">${timeText}</div>
                        <div style="font-weight: 600; font-size: 9px;">${programme}</div>
                        <div style="font-weight: bold; font-size: 10px;">${batch}</div>
                        <div style="font-size: 9px; margin-top: 2px;"><span class="badge bg-secondary">${type}</span></div>
                        ${combineGroup ? `<div style="font-size:7px;color:#ff0000;font-weight:bold;">${combineGroup}</div>` : ''}
                        ${note ? `<div style="font-size: 8px; margin-top: 2px; color: #666;">${note.substring(0, 30)}${note.length > 30 ? '...' : ''}</div>` : ''}
                     </div>`;
            
            eventDiv.innerHTML = titleHtml;
            return { domNodes: [eventDiv] };
        },
        events: [
            <?php
            $cal = mysqli_query($conn,"SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                                       FROM class_reservations r 
                                       LEFT JOIN classroom c ON c.class_id = r.hall_id
                                       LEFT JOIN program_table p ON r.programme = p.program_code
                                       LEFT JOIN batch_table b ON r.batch = b.id");
            while($c = mysqli_fetch_assoc($cal)){
                $class = $c['approve_status'] == 'Approved' ? 'approved' : ($c['approve_status'] == 'Cancelled' ? 'cancelled' : 'pending');
                $hall = htmlspecialchars($c['lectuerhallname'] ?? 'Class Not Assigned', ENT_QUOTES);
                $programmeName = htmlspecialchars($c['program_name'] ?? $c['programme'], ENT_QUOTES);
                $batchName = htmlspecialchars($c['batch_name'] ?? $c['batch'], ENT_QUOTES);
                $type = htmlspecialchars($c['type'] ?? '', ENT_QUOTES);
                $note = htmlspecialchars($c['note'] ?? '', ENT_QUOTES);
                $lecturer = htmlspecialchars($c['lecturer'] . (!empty($c['lecturer_text']) && $c['lecturer_text'] != $c['lecturer'] ? ' | ' . $c['lecturer_text'] : ''), ENT_QUOTES);
                $combineGroup = $c['combine_group'] ?? '';
            ?>
            {
                id: '<?= $c['id'] ?>',
                title: '<?= addslashes($hall) ?>',
                start: '<?= $c['date'] ?>T<?= $c['start_time'] ?>',
                end: '<?= $c['date'] ?>T<?= $c['end_time'] ?>',
                className: '<?= $class ?>',
                extendedProps: {
                    id: '<?= $c['id'] ?>',
                    room: '<?= addslashes($hall) ?>',
                    status: '<?= $c['approve_status'] ?>',
                    module: '<?= addslashes($c['module'] . (!empty($c['module_text']) && $c['module_text'] != $c['module'] ? ' | ' . $c['module_text'] : '')) ?>',
                    lecturer: '<?= addslashes($lecturer) ?>',
                    programme: '<?= addslashes($programmeName) ?>',
                    batch: '<?= addslashes($batchName) ?>',
                    date: '<?= $c['date'] ?>',
                    startTime: '<?= date('H:i', strtotime($c['start_time'])) ?>',
                    endTime: '<?= date('H:i', strtotime($c['end_time'])) ?>',
                    note: '<?= addslashes($c['note']) ?>',
                    studentCount: '<?= $c['student_count'] ?? 0 ?>',
                    onlineStudent: '<?= $c['online_student'] ?? 0 ?>',
                    type: '<?= addslashes($type) ?>',
                    combineGroup: '<?= addslashes($combineGroup) ?>'
                }
            },
            <?php } ?>
        ],
        eventClick: function(info){
            var e = info.event;
            var eventDate = e.extendedProps.date;
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var eventDateObj = new Date(eventDate);
            eventDateObj.setHours(0, 0, 0, 0);
            var isPastDate = eventDateObj < today;
            
            var status = e.extendedProps.status;
            var statusHtml = status == 'Approved' ? '<span class="badge bg-success">Approved</span>' : (status == 'Cancelled' ? '<span class="badge bg-danger">Cancelled</span>' : '<span class="badge bg-warning text-dark">Pending</span>');
            var inClass = (parseInt(e.extendedProps.studentCount) || 0) - (parseInt(e.extendedProps.onlineStudent) || 0);
            var combineGroup = e.extendedProps.combineGroup;
            var currentDate = e.extendedProps.date;
            var currentId = e.extendedProps.id;
            
            var showCombineButton = (status == 'Pending' && !combineGroup && !isPastDate);
            
            var modalHtml = `
                <table class="table table-sm">
                    <tr><th>Type</th><td>${e.extendedProps.type || ''} ${combineGroup ? '<span class="combine-badge">Combined Group</span>' : ''}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Room</th><td>${e.extendedProps.room}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Time</th><td>${e.extendedProps.startTime}-${e.extendedProps.endTime}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Programme</th><td>${e.extendedProps.programme}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Batch</th><td>${e.extendedProps.batch}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Module</th><td>${e.extendedProps.module}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Lecturer</th><td><span style="color:#0066cc;">${e.extendedProps.lecturer}</span></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Students</th><td>Total:${e.extendedProps.studentCount} Online:${e.extendedProps.onlineStudent} In-Class:${inClass}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                    <tr><th>Status</th><td>${statusHtml}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>
                ${e.extendedProps.note ? `<tr><th>Note</th><td>${e.extendedProps.note}</div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div></div> </div> </div> </div> </div> </div> </div> </div> </div> </div</div>` : ''}
            </table>
            <div class="d-flex gap-1 mt-2 flex-wrap">
                ${!isPastDate ? `<a href="?edit=${e.id}" class="btn btn-sm btn-primary">Edit</a>` : '<button class="btn btn-sm btn-secondary" disabled>Edit (Past Date)</button>'}
                ${status != 'Approved' && !isPastDate ? `<a href="?approve=${e.id}" class="btn btn-sm btn-success" onclick="return confirm('Approve?')">Approve</a>` : (status != 'Approved' && isPastDate ? '<button class="btn btn-sm btn-secondary" disabled>Approve (Past)</button>' : '')}
                ${status == 'Approved' && !isPastDate ? `<a href="?pending=${e.id}" class="btn btn-sm btn-warning" onclick="return confirm('Pending?')">Pending</a>` : (status == 'Approved' && isPastDate ? '<button class="btn btn-sm btn-secondary" disabled>Pending (Past)</button>' : '')}
                ${status != 'Cancelled' && !isPastDate ? `<a href="?cancel=${e.id}" class="btn btn-sm btn-danger" onclick="return confirm('Cancel?')">Cancel</a>` : (status != 'Cancelled' && isPastDate ? '<button class="btn btn-sm btn-secondary" disabled>Cancel (Past)</button>' : '')}
        `;

        if(showCombineButton && !isPastDate){
            modalHtml += `<button type="button" class="btn btn-sm" style="background-color:#6f42c1; color:white;" onclick="openCombineModal('${currentId}', '${currentDate}')">🔗 Combine with Other Reservations</button>`;
        }

        if(combineGroup && !isPastDate){
            modalHtml += `<form method="POST" style="display:inline-block"><input type="hidden" name="remove_id" value="${e.id}"><button type="submit" name="remove_combine" class="btn btn-sm btn-secondary" onclick="return confirm('WARNING: This will remove the ENTIRE combined group from ALL reservations!')">Remove Entire Group</button></form>`;
            modalHtml += `<a href="?approve_combined=1&combine_group=${combineGroup}" class="btn btn-sm btn-info" onclick="return confirm('Approve all combined reservations?')">Approve All</a>`;
        }

        if(isPastDate){
            modalHtml += `<div class="alert alert-danger mt-2 mb-0">⚠️ This is a PAST DATE reservation. No modifications allowed.</div>`;
        }

        modalHtml += `</div>`;
            
            $('#eventDetails').html(modalHtml);
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }
    });
    calendar.render();
    
    var filterDate = '<?= $filterDate ?>';
    if(filterDate && filterDate !== '<?= date('Y-m-d') ?>'){
        calendar.gotoDate(filterDate);
        calendar.changeView('timeGridDay', filterDate);
    }
});
</script>

</body>
</html>