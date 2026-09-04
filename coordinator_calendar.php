<?php
//session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

require_once 'PermissionChecking.php';

/* ================= FETCH USER DATA ================= */
$current_username = $_SESSION['username'];
$user_query = mysqli_query($conn, "SELECT id, role FROM admin WHERE username = '$current_username'");
$user_data = mysqli_fetch_assoc($user_query);
$user_id = $user_data['id'] ?? 0;
$user_role = $user_data['role'] ?? 'data_enter';

// Initialize programmes query
$programmes_query = "SELECT program_code, program_name FROM program_table ORDER BY program_name";

// If role is manager or data_enter, filter programmes from program_allocation_user
if ($user_role == 'manager' || $user_role == 'data_enter') {
    $programmes_query = "
        SELECT DISTINCT pt.program_code, pt.program_name
        FROM program_table pt
        INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
        WHERE pau.user_id = '$user_id'
        ORDER BY pt.program_name
    ";
}

$programmes = mysqli_query($conn, $programmes_query);

// Get allocated programme codes for filtering
$allocated_programmes = [];
if ($user_role == 'manager' || $user_role == 'data_enter') {
    $prog_query = mysqli_query($conn, "SELECT DISTINCT program_code FROM program_allocation_user WHERE user_id = '$user_id'");
    while($prog = mysqli_fetch_assoc($prog_query)){
        $allocated_programmes[] = $prog['program_code'];
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
    
    // Hall is optional - if empty, set to NULL
    $hall = !empty($_POST['hall']) ? $_POST['hall'] : 'NULL';
    
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
        $hall,
        'Pending',
        '$student_count',
        '$online_student'
    )
    ");

    $message = "Reservation Request Created Successfully";
}

/* =========================
APPROVE
========================= */
if(isset($_GET['approve'])){

    $id = $_GET['approve'];
    
    // Only super_admin can approve
    if($user_role == 'super_admin'){
        mysqli_query($conn," 
        UPDATE class_reservations
        SET approve_status='Approved'
        WHERE id='$id'
        ");
        $message = "Reservation Approved Successfully";
    } else {
        $message = "You don't have permission to approve reservations";
        $messageType = "danger";
    }
}

/* =========================
PENDING AGAIN
========================= */
if(isset($_GET['pending'])){

    $id = $_GET['pending'];
    
    // Only super_admin can move to pending
    if($user_role == 'super_admin'){
        mysqli_query($conn," 
        UPDATE class_reservations
        SET approve_status='Pending'
        WHERE id='$id'
        ");
        $message = "Reservation Changed to Pending";
    } else {
        $message = "You don't have permission to change status";
        $messageType = "danger";
    }
}

/* =========================
CANCEL
========================= */
if(isset($_GET['cancel'])){

    $id = $_GET['cancel'];
    
    // Only super_admin can cancel
    if($user_role == 'super_admin'){
        mysqli_query($conn," 
        UPDATE class_reservations
        SET approve_status='Cancelled',
            hall_id = NULL
        WHERE id='$id'
        ");
        $message = "Reservation Cancelled - Classroom removed from reservation";
        $messageType = "danger";
    } else {
        $message = "You don't have permission to cancel reservations";
        $messageType = "danger";
    }
}

/* =========================
UPDATE
========================= */
if(isset($_POST['update_request'])){

    $id = $_POST['id'];
    
    // Check if user can edit this reservation
    $checkRes = mysqli_query($conn, "SELECT approve_status, date FROM class_reservations WHERE id='$id'");
    $resData = mysqli_fetch_assoc($checkRes);
    
    $canEdit = false;
    $editError = "";
    
    // Super admin can edit anything
    if($user_role == 'super_admin'){
        $canEdit = true;
    } 
    // Other users can only edit Pending reservations before 32 hours
    else if($resData['approve_status'] == 'Pending'){
        $resDate = strtotime($resData['date']);
        $currentDate = strtotime(date('Y-m-d'));
        $hoursDiff = ($resDate - $currentDate) / 3600;
        
        if($hoursDiff >= 32){
            $canEdit = true;
        } else {
            $editError = "Cannot edit reservation less than 32 hours before the scheduled date";
        }
    } else {
        $editError = "You can only edit Pending reservations";
    }
    
    if($canEdit){
        $module = !empty($_POST['module_select'])
            ? $_POST['module_select']
            : $_POST['module_text'];
        
        $module_text = $_POST['module_text'];

        $lecturer = !empty($_POST['lecturer_select'])
            ? $_POST['lecturer_select']
            : $_POST['lecturer_text'];
        
        $lecturer_text = $_POST['lecturer_text'];
        
        // Hall is optional - if empty, set to NULL
        $hall = !empty($_POST['hall']) ? "'{$_POST['hall']}'" : 'NULL';

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
        hall_id=$hall,
        student_count='{$_POST['student_count']}',
        online_student='{$_POST['online_student']}'

        WHERE id='$id'
        ");

        $message = "Reservation Updated Successfully";
        
        // Refresh edit data after update
        $q = mysqli_query($conn," 
        SELECT r.*, 
               p.program_name,
               b.batch_name
        FROM class_reservations r
        LEFT JOIN program_table p ON r.programme = p.program_code
        LEFT JOIN batch_table b ON r.batch = b.id
        WHERE r.id='$id'
        ");
        $edit = mysqli_fetch_assoc($q);
    } else {
        $message = $editError;
        $messageType = "danger";
    }
}

/* =========================
EDIT DATA
========================= */
$edit = null;
$canEditReservation = false;
$editTimeWarning = "";

if(isset($_GET['edit'])){

    $id = $_GET['edit'];

    $q = mysqli_query($conn," 
    SELECT r.*, 
           p.program_name,
           b.batch_name
    FROM class_reservations r
    LEFT JOIN program_table p ON r.programme = p.program_code
    LEFT JOIN batch_table b ON r.batch = b.id
    WHERE r.id='$id'
    ");

    $edit = mysqli_fetch_assoc($q);
    
    // Check if user can edit this reservation
    if($user_role == 'super_admin'){
        $canEditReservation = true;
    } else if($edit && $edit['approve_status'] == 'Pending'){
        $resDate = strtotime($edit['date']);
        $currentDate = strtotime(date('Y-m-d'));
        $hoursDiff = ($resDate - $currentDate) / 3600;
        
        if($hoursDiff >= 32){
            $canEditReservation = true;
        } else {
            $editTimeWarning = "⚠️ Cannot edit: Less than 32 hours remaining until scheduled date";
        }
    } else if($edit && $edit['approve_status'] != 'Pending'){
        $editTimeWarning = "⚠️ Cannot edit: Only Pending reservations can be edited";
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
WEEK FILTER LOGIC FOR TABLE - FIXED (NO DOUBLE MOVEMENT)
========================= */
// Current selected week start (Monday)
$currentWeekStart = isset($_GET['week_start']) 
    ? $_GET['week_start'] 
    : date('Y-m-d', strtotime('monday this week'));

// Force Monday alignment
$currentWeekStart = date('Y-m-d', strtotime('monday this week', strtotime($currentWeekStart)));

// Week end (Sunday)
$currentWeekEnd = date('Y-m-d', strtotime($currentWeekStart . ' +6 days'));

// Navigation weeks (exactly +/- 7 days)
$prevWeekStart = date('Y-m-d', strtotime($currentWeekStart . ' -7 days'));
$nextWeekStart = date('Y-m-d', strtotime($currentWeekStart . ' +7 days'));

// Build WHERE clause for programme filtering
$programmeFilter = "";
if(!empty($allocated_programmes)){
    $programmeFilter = "AND r.programme IN ('" . implode("','", $allocated_programmes) . "')";
}

include("includes/header.php");
?>

<!DOCTYPE html>
<html>
<head>
<title>Coordinator Calendar - Classroom Allocation</title>
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
.table .text-nowrap { white-space: nowrap !important; }
.table .text-wrap { white-space: normal !important; }
.table td { white-space: normal !important; word-wrap: break-word !important; word-break: break-word !important; }

/* Calendar event styling */
.fc-daygrid-day-events { min-height: 40px; }
.fc-daygrid-event { white-space: normal !important; margin: 1px 2px !important; padding: 2px !important; font-size: 9px !important; }
.fc-daygrid-event .fc-event-title { white-space: normal !important; word-break: break-word !important; }
.fc-event-main { overflow: wrap; }
.fc-daygrid-day-number { font-size: 11px; }

/* Calendar event font colors */
.fc-event.approved { background-color: #198754 !important; border-color: #198754 !important; color: #ffffff !important; }
.fc-event.approved .fc-event-main,
.fc-event.approved .fc-event-main * { color: #ffffff !important; }
.fc-event.pending { background-color: #ffc107 !important; border-color: #ffc107 !important; color: #042d5c !important; }
.fc-event.pending .fc-event-main,
.fc-event.pending .fc-event-main * { color: #042d5c !important; }
.fc-event.cancelled { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #ffffff !important; }
.fc-event.cancelled .fc-event-main,
.fc-event.cancelled .fc-event-main * { color: #ffffff !important; }

.edit-disabled {
    opacity: 0.6;
    pointer-events: none;
}
.warning-text {
    color: #dc3545;
    font-size: 11px;
    margin-top: 5px;
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
                    <h4 class="h4 mb-0 text-gray-800">Faculty Timetable - Calendar | Edit</h4>
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
        <h5 class="mb-2"><?= isset($edit) ? 'Edit Reservation' : 'Edit Reservation' ?></h5>
        
        <?php if($editTimeWarning): ?>
        <div class="alert alert-warning py-1 mb-2" style="font-size:12px"><?= $editTimeWarning ?></div>
        <?php endif; ?>
        
        <form method="POST" id="reservationForm">
            <?php if(isset($edit)){ ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php } ?>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Type</label>
                <input type="text" class="form-control form-control-sm" value="<?= $edit['type'] ?? '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                <input type="hidden" name="type" id="type_val" value="<?= $edit['type'] ?? '' ?>">
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
                <select name="module_select" id="module_select" class="form-select form-select-sm select2" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                    <option value="">Select Module</option>
                </select>
                <input type="text" name="module_text" id="module_text" class="form-control form-control-sm mt-1" placeholder="Or Enter New Module" value="<?= isset($edit['module_text']) ? htmlspecialchars($edit['module_text']) : '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                <?php if(isset($edit) && !empty($edit['module'])): ?>
                <small class="text-muted d-block mt-1 text-info">✓ Saved Module: <?= htmlspecialchars($edit['module']) ?></small>
                <?php endif; ?>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Lecturer</label>
                <select name="lecturer_select" id="lecturer_select" class="form-select form-select-sm select2" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                    <option value="">Select Lecturer</option>
                    <?php 
                    $lecturers = mysqli_query($conn,"SELECT * FROM lecturer_table ORDER BY lecturer_name");
                    while($l=mysqli_fetch_assoc($lecturers)){ ?>
                        <option value="<?= htmlspecialchars($l['lecturer_name']) ?>" <?= (($edit['lecturer'] ?? '')==$l['lecturer_name'])?'selected':'' ?>><?= htmlspecialchars($l['lecturer_name']) ?></option>
                    <?php } ?>
                </select>
                <input type="text" name="lecturer_text" id="lecturer_text" class="form-control form-control-sm mt-1" placeholder="Or Enter New Lecturer" value="<?= $edit['lecturer_text'] ?? '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                <?php if(isset($edit) && !empty($edit['lecturer'])): ?>
                <small class="text-muted d-block mt-1 text-info">✓ Saved Lecturer: <?= htmlspecialchars($edit['lecturer']) ?></small>
                <?php endif; ?>
            </div>
            
            <div class="row g-1 mb-2">
                <div class="col-6">
                    <label class="form-label small fw-bold">Total Students</label>
                    <input type="number" name="student_count" id="student_count" class="form-control form-control-sm" value="<?= isset($edit['student_count']) ? $edit['student_count'] : '0' ?>" readonly style="background:#e9ecef">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold">Online Students</label>
                    <input type="number" name="online_student" id="online_student" class="form-control form-control-sm" value="<?= isset($edit['online_student']) ? $edit['online_student'] : '0' ?>" min="0" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
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
                    <input type="date" name="date" class="form-control form-control-sm" required value="<?= $edit['date'] ?? '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                </div>
                <div class="col-3">
                    <label class="form-label small fw-bold">Start</label>
                    <input type="time" name="start_time" class="form-control form-control-sm" required value="<?= $edit['start_time'] ?? '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                </div>
                <div class="col-4">
                    <label class="form-label small fw-bold">End</label>
                    <input type="time" name="end_time" class="form-control form-control-sm" required value="<?= $edit['end_time'] ?? '' ?>" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
                </div>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Lecture Hall</label>
                <select name="hall" id="hall_select" class="form-select form-select-sm select2 hall-select" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>>
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
                        
                        // Status badges
                        $statusBadges = [];
                        if($status==0){
                            $statusBadges[] = '[INACTIVE]';
                        }
                        if($isBooked){
                            $statusBadges[] = '[BOOKED]';
                            $statusBadges[] = '[BREAKDOWN]';
                        }
                        $badgeText = !empty($statusBadges) ? ' ' . implode(' ', $statusBadges) : '';
                        $warningSymbol = '';
                        
                        if($isBooked){
                            $warningSymbol = '⚠️ ';
                        } elseif($status==0){
                            $warningSymbol = '🔴 ';
                        }
                        
                        // Only disable if not the saved hall and if not active
                        $disabled = ($status==0 && $savedHallId != $classId) ? true : false;
                        ?>
                        <option value="<?= $h['class_id'] ?>" <?= $selected ?> <?= $disabled?'disabled':'' ?> data-seat="<?= $h['totalseat'] ?>" data-branch="<?= $h['branch'] ?>" data-name="<?= htmlspecialchars($h['lectuerhallname']) ?>" data-booked="<?= $isBooked ? '1' : '0' ?>" data-inactive="<?= ($status==0)?'1':'0' ?>">
                            <?= $warningSymbol ?><?= $h['branch'] ?> - <?= htmlspecialchars($h['lectuerhallname']) ?> (<?= $h['totalseat'] ?> seats)<?= $badgeText ?>
                        </option>
                    <?php } ?>
                </select>
                <small class="text-muted hall-info"></small>
            </div>
            
            <div class="mb-2">
                <label class="form-label small fw-bold">Note</label>
                <textarea name="note" class="form-control form-control-sm" rows="2" <?= (!$canEditReservation && isset($edit)) ? 'disabled' : '' ?>><?= $edit['note'] ?? '' ?></textarea>
            </div>
            
            <?php if(isset($edit)){ ?>
                <button type="submit" name="update_request" class="btn btn-warning w-100 btn-sm" <?= (!$canEditReservation) ? 'disabled' : '' ?>>Update Reservation</button>
                <?php if(!$canEditReservation && isset($edit) && $edit['approve_status'] == 'Pending'): ?>
                <small class="text-danger d-block mt-1 text-center">⚠️ Cannot edit: Less than 32 hours before scheduled date</small>
                <?php endif; ?>
            <?php } else { ?>
                <button type="submit" name="save_request" class="btn btn-primary w-100 btn-sm">Wait for edit...</button>
            <?php } ?>
        </form>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="col-md-8">
    <!-- CALENDAR -->
    <div class="card shadow p-2 mb-2">
        <h5 class="mb-1">Reservation Calendar</h5>
        <div id="calendar" style="height:420px"></div>
    </div>
    
    <!-- FILTER -->
    <div class="card shadow p-2 mb-2">
        <form method="GET" class="row g-1" id="filterForm">
            <div class="col-md-5">
                <input type="date" name="filter_date" id="filter_date_input" class="form-control form-control-sm" value="<?= $filterDate ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-danger w-100" style="height: 28px; color: #ffffff; font-weight: bold; font-size: 12px; padding: 0.2rem 0.5rem;">Filter</button>
            </div>
            <div class="col-md-2">
                <button type="button" id="prevDayBtn" class="btn btn-dark w-100" style="height: 28px; color: #ffffff; font-weight: bold; font-size: 12px; padding: 0.2rem 0.5rem;">&lt; Before</button>
            </div>
            <div class="col-md-2">
                <button type="button" id="nextDayBtn" class="btn btn-dark w-100" style="height: 28px; color: #ffffff; font-weight: bold; font-size: 12px; padding: 0.2rem 0.5rem;">Next &gt;</button>
            </div>
        </form>
    </div>
 </div>
 
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex align-items-center" style="height: 60px;">
                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                        <i class="fas fa-plus-circle"></i>
                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                    <h6 class="mb-0 me-2">Reservation Requests</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive mb-4">

    <!-- TABLE -->
    <div class="card shadow p-2">
        <h5 class="mb-1">Reservation Requests</h5>
        
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
        
        <div class="tab-content" style="max-height:350px; overflow-y:auto">
            <!-- ALL TAB -->
            <div class="tab-pane fade show active" id="all">
                <?php 
                $allQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                            FROM class_reservations r 
                            LEFT JOIN classroom c ON c.class_id = r.hall_id
                            LEFT JOIN program_table p ON r.programme = p.program_code
                            LEFT JOIN batch_table b ON r.batch = b.id
                            WHERE r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' $programmeFilter
                            ORDER BY r.date ASC, r.start_time ASC";
                $allResult = mysqli_query($conn, $allQuery);
                ?>
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10%">Type</th>
                            <th style="width: 15%">Programme</th>
                            <th style="width: 8%">Batch</th>
                            <th style="width: 15%">Module</th>
                            <th style="width: 8%">Date</th>
                            <th style="width: 10%">Time</th>
                            <th style="width: 10%">Hall</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = mysqli_fetch_assoc($allResult)){ 
                        $status = $r['approve_status'] ?? 'Pending';
                        $moduleDisplay = $r['module'] . (!empty($r['module_text']) && $r['module_text'] != $r['module'] ? ' | ' . $r['module_text'] : '');
                        $programmeName = $r['program_name'] ?? $r['programme'];
                        $batchName = $r['batch_name'] ?? $r['batch'];
                        $canEdit = ($user_role == 'super_admin') || ($status == 'Pending');
                    ?>
                        <tr>
                            <td class="text-wrap"><?= htmlspecialchars($r['type'] ?? '') ?>
                                <?php if(!empty($r['note'])): ?>
                                    <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 40)) ?><?= strlen($r['note']) > 40 ? '...' : '' ?></i></div>
                                <?php endif; ?>
                             </td>
                            <td class="text-wrap"><?= htmlspecialchars($programmeName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($batchName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($moduleDisplay) ?></td>
                            <td class="text-nowrap"><?= $r['date'] ?></td>
                            <td class="text-nowrap"><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars(substr($r['lectuerhallname'] ?? 'N/A',0,15)) ?></td>
                            <td class="text-nowrap"><?php if($status=='Approved'){ ?><span class="badge bg-success">Approved</span><?php } elseif($status=='Cancelled'){ ?><span class="badge bg-danger">Cancelled</span><?php } else { ?><span class="badge bg-warning text-dark">Pending</span><?php } ?></td>
                            <td class="text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <?php if($canEdit): ?>
                                    <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                    <?php endif; ?>
                                    <?php if($user_role == 'super_admin' && $status!='Approved'): ?>
                                    <a href="?approve=<?= $r['id'] ?>" class="btn btn-success" onclick="return confirm('Approve?')">Approve</a>
                                    <?php endif; ?>
                                    <?php if($user_role == 'super_admin' && $status=='Approved'): ?>
                                    <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Move to Pending?')">Pending</a>
                                    <?php endif; ?>
                                    <?php if($user_role == 'super_admin' && $status!='Cancelled'): ?>
                                    <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- PENDING TAB -->
            <div class="tab-pane fade" id="pending">
                <?php 
                $pendingQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                                FROM class_reservations r 
                                LEFT JOIN classroom c ON c.class_id = r.hall_id
                                LEFT JOIN program_table p ON r.programme = p.program_code
                                LEFT JOIN batch_table b ON r.batch = b.id
                                WHERE COALESCE(approve_status,'Pending')='Pending' $programmeFilter
                                ORDER BY r.date ASC LIMIT 25";
                $pendingResult = mysqli_query($conn, $pendingQuery);
                ?>
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10%">Type</th>
                            <th style="width: 15%">Programme</th>
                            <th style="width: 8%">Batch</th>
                            <th style="width: 15%">Module</th>
                            <th style="width: 8%">Date</th>
                            <th style="width: 10%">Time</th>
                            <th style="width: 10%">Hall</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = mysqli_fetch_assoc($pendingResult)){ 
                        $programmeName = $r['program_name'] ?? $r['programme'];
                        $batchName = $r['batch_name'] ?? $r['batch'];
                    ?>
                        <tr>
                            <td class="text-wrap"><?= htmlspecialchars($r['type'] ?? '') ?>
                                <?php if(!empty($r['note'])): ?>
                                    <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 40)) ?><?= strlen($r['note']) > 40 ? '...' : '' ?></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-wrap"><?= htmlspecialchars($programmeName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($batchName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($r['module'] . (!empty($r['module_text']) && $r['module_text'] != $r['module'] ? ' | ' . $r['module_text'] : '')) ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                            <td><?= htmlspecialchars($r['lectuerhallname'] ?? 'N/A') ?></td>
                            <td class="text-nowrap"><span class="badge bg-warning text-dark">Pending</span></td>
                            <td class="text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                    <?php if($user_role == 'super_admin'): ?>
                                    <a href="?approve=<?= $r['id'] ?>" class="btn btn-success" onclick="return confirm('Approve?')">Approve</a>
                                    <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } if(mysqli_num_rows($pendingResult)==0){ echo '<tr><td colspan="9" class="text-center">No records</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- APPROVED TAB -->
            <div class="tab-pane fade" id="approved">
                <?php 
                $approvedQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                                FROM class_reservations r 
                                LEFT JOIN classroom c ON c.class_id = r.hall_id
                                LEFT JOIN program_table p ON r.programme = p.program_code
                                LEFT JOIN batch_table b ON r.batch = b.id
                                WHERE approve_status='Approved' AND r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' $programmeFilter
                                ORDER BY r.date ASC";
                $approvedResult = mysqli_query($conn, $approvedQuery);
                ?>
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10%">Type</th>
                            <th style="width: 15%">Programme</th>
                            <th style="width: 8%">Batch</th>
                            <th style="width: 15%">Module</th>
                            <th style="width: 8%">Date</th>
                            <th style="width: 10%">Time</th>
                            <th style="width: 10%">Hall</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = mysqli_fetch_assoc($approvedResult)){ 
                        $programmeName = $r['program_name'] ?? $r['programme'];
                        $batchName = $r['batch_name'] ?? $r['batch'];
                    ?>
                        <tr>
                            <td class="text-wrap"><?= htmlspecialchars($r['type'] ?? '') ?>
                                <?php if(!empty($r['note'])): ?>
                                    <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 40)) ?><?= strlen($r['note']) > 40 ? '...' : '' ?></i></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-wrap"><?= htmlspecialchars($programmeName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($batchName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($r['module'] . (!empty($r['module_text']) && $r['module_text'] != $r['module'] ? ' | ' . $r['module_text'] : '')) ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                            <td><?= htmlspecialchars($r['lectuerhallname'] ?? 'N/A') ?></td>
                            <td class="text-nowrap"><span class="badge bg-success">Approved</span></td>
                            <td class="text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <?php if($user_role == 'super_admin'): ?>
                                    <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                    <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Move to Pending?')">Pending</a>
                                    <a href="?cancel=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Cancel?')">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } if(mysqli_num_rows($approvedResult)==0){ echo '<tr><td colspan="9" class="text-center">No records</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- CANCELLED TAB -->
            <div class="tab-pane fade" id="cancelled">
                <?php 
                $cancelledQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                                  FROM class_reservations r 
                                  LEFT JOIN classroom c ON c.class_id = r.hall_id
                                  LEFT JOIN program_table p ON r.programme = p.program_code
                                  LEFT JOIN batch_table b ON r.batch = b.id
                                  WHERE approve_status='Cancelled' AND r.date BETWEEN '$currentWeekStart' AND '$currentWeekEnd' $programmeFilter
                                  ORDER BY r.date ASC";
                $cancelledResult = mysqli_query($conn, $cancelledQuery);
                ?>
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 10%">Type</th>
                            <th style="width: 15%">Programme</th>
                            <th style="width: 8%">Batch</th>
                            <th style="width: 15%">Module</th>
                            <th style="width: 8%">Date</th>
                            <th style="width: 10%">Time</th>
                            <th style="width: 10%">Hall</th>
                            <th style="width: 8%">Status</th>
                            <th style="width: 8%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($r = mysqli_fetch_assoc($cancelledResult)){ 
                        $programmeName = $r['program_name'] ?? $r['programme'];
                        $batchName = $r['batch_name'] ?? $r['batch'];
                    ?>
                        <tr>
                            <td class="text-wrap"><?= htmlspecialchars($r['type'] ?? '') ?>
                                <?php if(!empty($r['note'])): ?>
                                    <div class="small text-muted mt-1"><i><?= htmlspecialchars(substr($r['note'], 0, 40)) ?><?= strlen($r['note']) > 40 ? '...' : '' ?></i></div>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td class="text-wrap"><?= htmlspecialchars($programmeName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($batchName) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($r['module'] . (!empty($r['module_text']) && $r['module_text'] != $r['module'] ? ' | ' . $r['module_text'] : '')) ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= date('H:i', strtotime($r['start_time'])) ?>-<?= date('H:i', strtotime($r['end_time'])) ?></td>
                            <td><?= htmlspecialchars($r['lectuerhallname'] ?? 'N/A') ?></td>
                            <td class="text-nowrap"><span class="badge bg-danger">Cancelled</span></td>
                            <td class="text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <?php if($user_role == 'super_admin'): ?>
                                    <a href="?edit=<?= $r['id'] ?>" class="btn btn-primary">Edit</a>
                                    <a href="?pending=<?= $r['id'] ?>" class="btn btn-warning" onclick="return confirm('Move to Pending?')">Pending</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } if(mysqli_num_rows($cancelledResult)==0){ echo '<tr><td colspan="9" class="text-center">No records</td></tr>'; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- MODAL -->
<div class="modal fade" id="eventModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-1">
                <h6 class="modal-title">Reservation Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="eventDetails" style="font-size:12px"></div>
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
        var showAll = false; // Always false - users can only see halls with sufficient capacity
        
        $('.hall-select option').each(function(){
            var $opt = $(this);
            var seat = parseInt($opt.data('seat')) || 0;
            var isBooked = $opt.data('booked') == 1;
            var isInactive = $opt.data('inactive') == 1;
            var origDisabled = $opt.attr('data-original-disabled') === 'true';
            
            if(typeof $opt.attr('data-original-disabled') === 'undefined'){
                $opt.attr('data-original-disabled', $opt.prop('disabled'));
                origDisabled = $opt.prop('disabled');
            }
            
            // Only show active halls with sufficient capacity (seat >= inClass)
            var hasCapacity = (seat >= inClass) || inClass === 0;
            var shouldShow = !origDisabled && hasCapacity && !isBooked;
            
            if(shouldShow){
                $opt.show();
                $opt.prop('disabled', false);
            } else {
                $opt.hide();
                $opt.prop('disabled', true);
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
    
    $('.hall-select').on('change', function(){
        var inClass = parseInt($('#inclass_student').val()) || 0;
        var opt = $(this).find('option:selected');
        var seat = parseInt(opt.data('seat')) || 0;
        
        if(seat > 0 && inClass > seat){
            $('.hall-info').html('<span class="text-danger">❌ ERROR: ' + inClass + ' students exceed ' + seat + ' seats!</span>');
        } else if(seat > 0){
            $('.hall-info').html('<span class="text-success">✓ ' + seat + ' seats - ' + (seat >= inClass ? 'Sufficient for ' + inClass + ' students' : 'Insufficient') + '</span>');
        }
    });
    
    $('.hall-select option').each(function(){
        if(typeof $(this).attr('data-original-disabled') === 'undefined'){
            $(this).attr('data-original-disabled', $(this).prop('disabled'));
        }
    });
    
    loadBatchTotal();
});

// Calendar
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
            
            let titleHtml = `<div style="line-height: 1.3; padding: 2px;">
                        <div style="font-size: 11px; font-weight: bold; word-wrap: break-word; white-space: normal;">${room}</div>
                        <div style="font-weight: bold; font-size: 11px;">${timeText}</div>
                        <div style="font-weight: 600; font-size: 9px; word-wrap: break-word; white-space: normal;">${programme}</div>
                        <div style="font-weight: bold; font-size: 10px; word-wrap: break-word; white-space: normal;">${batch}</div>
                        <div style="font-size: 9px; margin-top: 2px;"><span class="badge bg-secondary">${type}</span></div>
                        ${note ? `<div style="font-size: 8px; margin-top: 2px; color: #666; font-style: italic;">${note.substring(0, 30)}${note.length > 30 ? '...' : ''}</div>` : ''}
                     </div>`;
            
            eventDiv.innerHTML = titleHtml;
            return { domNodes: [eventDiv] };
        },
        events: [
            <?php
            $calQuery = "SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                        FROM class_reservations r 
                        LEFT JOIN classroom c ON c.class_id = r.hall_id
                        LEFT JOIN program_table p ON r.programme = p.program_code
                        LEFT JOIN batch_table b ON r.batch = b.id
                        WHERE 1=1 $programmeFilter";
            $cal = mysqli_query($conn, $calQuery);
            while($c = mysqli_fetch_assoc($cal)){
                $class = $c['approve_status'] == 'Approved' ? 'approved' : ($c['approve_status'] == 'Cancelled' ? 'cancelled' : 'pending');
                $hall = htmlspecialchars($c['lectuerhallname'] ?? 'No Hall', ENT_QUOTES);
                $programmeName = htmlspecialchars($c['program_name'] ?? $c['programme'], ENT_QUOTES);
                $batchName = htmlspecialchars($c['batch_name'] ?? $c['batch'], ENT_QUOTES);
                $type = htmlspecialchars($c['type'] ?? '', ENT_QUOTES);
                $note = htmlspecialchars($c['note'] ?? '', ENT_QUOTES);
            ?>
            {
                id: '<?= $c['id'] ?>',
                title: '<?= addslashes($hall) ?>',
                start: '<?= $c['date'] ?>T<?= $c['start_time'] ?>',
                end: '<?= $c['date'] ?>T<?= $c['end_time'] ?>',
                className: '<?= $class ?>',
                extendedProps: {
                    room: '<?= addslashes($hall) ?>',
                    status: '<?= $c['approve_status'] ?>',
                    module: '<?= addslashes($c['module'] . (!empty($c['module_text']) && $c['module_text'] != $c['module'] ? ' | ' . $c['module_text'] : '')) ?>',
                    lecturer: '<?= addslashes($c['lecturer'] . (!empty($c['lecturer_text']) && $c['lecturer_text'] != $c['lecturer'] ? ' | ' . $c['lecturer_text'] : '')) ?>',
                    programme: '<?= addslashes($programmeName) ?>',
                    batch: '<?= addslashes($batchName) ?>',
                    date: '<?= $c['date'] ?>',
                    startTime: '<?= date('H:i', strtotime($c['start_time'])) ?>',
                    endTime: '<?= date('H:i', strtotime($c['end_time'])) ?>',
                    note: '<?= addslashes($c['note']) ?>',
                    studentCount: '<?= $c['student_count'] ?? 0 ?>',
                    onlineStudent: '<?= $c['online_student'] ?? 0 ?>',
                    type: '<?= addslashes($type) ?>'
                }
            },
            <?php } ?>
        ],
        eventClick: function(info){
            var e = info.event;
            var status = e.extendedProps.status;
            var statusHtml = status == 'Approved' ? '<span class="badge bg-success">Approved</span>' : (status == 'Cancelled' ? '<span class="badge bg-danger">Cancelled</span>' : '<span class="badge bg-warning text-dark">Pending</span>');
            var inClass = (parseInt(e.extendedProps.studentCount) || 0) - (parseInt(e.extendedProps.onlineStudent) || 0);
            var userRole = '<?= $user_role ?>';
            
            var editButtons = '';
            if(userRole == 'super_admin' || status == 'Pending'){
                editButtons = `<a href="?edit=${e.id}" class="btn btn-sm btn-primary">Edit</a>`;
            }
            
            var approveButtons = '';
            if(userRole == 'super_admin' && status != 'Approved'){
                approveButtons = `<a href="?approve=${e.id}" class="btn btn-sm btn-success" onclick="return confirm('Approve?')">Approve</a>`;
            }
            
            var pendingButtons = '';
            if(userRole == 'super_admin' && status == 'Approved'){
                pendingButtons = `<a href="?pending=${e.id}" class="btn btn-sm btn-warning" onclick="return confirm('Pending?')">Pending</a>`;
            }
            
            var cancelButtons = '';
            if(userRole == 'super_admin' && status != 'Cancelled'){
                cancelButtons = `<a href="?cancel=${e.id}" class="btn btn-sm btn-danger" onclick="return confirm('Cancel?')">Cancel</a>`;
            }
            
            $('#eventDetails').html(`
                <table class="table table-sm">
                    <tr><th>Type</th><td>${e.extendedProps.type || ''}</td></tr>
                    <tr><th>Room</th><td>${e.extendedProps.room}</td></tr>
                    <tr><th>Time</th><td>${e.extendedProps.startTime}-${e.extendedProps.endTime}</td></tr>
                    <tr><th>Programme</th><td>${e.extendedProps.programme}</td></tr>
                    <tr><th>Batch</th><td>${e.extendedProps.batch}</td></tr>
                    <tr><th>Module</th><td>${e.extendedProps.module}</td></tr>
                    <tr><th>Lecturer</th><td>${e.extendedProps.lecturer}</td></tr>
                    <tr><th>Students</th><td>Total:${e.extendedProps.studentCount} Online:${e.extendedProps.onlineStudent} In-Class:${inClass}</td></tr>
                    <tr><th>Status</th><td>${statusHtml}</td></tr>
                    ${e.extendedProps.note ? `<tr><th>Note</th><td>${e.extendedProps.note}</td></tr>` : ''}
                </table>
                <div class="d-flex gap-1 mt-2">
                    ${editButtons}
                    ${approveButtons}
                    ${pendingButtons}
                    ${cancelButtons}
                </div>
            `);
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