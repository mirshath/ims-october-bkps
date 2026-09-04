<?php
session_start();
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

/* ================= FETCH DATA ================= */
// Get current logged in user's ID and role from admin table
$current_username = $_SESSION['username'];
$user_query = mysqli_query($conn, "SELECT id, role FROM admin WHERE username = '$current_username'");
$user_data = mysqli_fetch_assoc($user_query);
$user_id = $user_data['id'] ?? 0;
$user_role = $user_data['role'] ?? 'data_enter'; // default to data_enter

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

/* ================= SAVE ================= */
if(isset($_POST['save'])){

    $type = $_POST['reservation_type'];
    $date = $_POST['date'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $hall = $_POST['hall'];
    if($hall == 'Null' || empty($hall)) {
    $hall = NULL; // or '' depending on your database schema
}

    $programme = $_POST['programme'] ?? NULL;
    $batch = $_POST['batch'] ?? NULL;

    /* ================= MODULE SAVE (UNCHANGED LOGIC) ================= */

    $module_select = trim($_POST['module_select'] ?? '');
    $module_text = trim($_POST['module_text'] ?? '');

    if($module_select != ''){
        $module = $module_select;   // dropdown wins
    } else {
        $module = $module_text;     // fallback text
    }

    /* ================= LECTURER SAVE (UNCHANGED LOGIC) ================= */

    $lecturer_select = trim($_POST['lecturer_select'] ?? '');
    $lecturer_text = trim($_POST['lecturer_text'] ?? '');

    if($lecturer_select != ''){
        $lecturer = $lecturer_select;   // dropdown wins
    } else {
        $lecturer = $lecturer_text;     // fallback text
    }

    $note = $_POST['note'] ?? NULL;

    /* ================= STUDENT COUNTS ================= */

    $student_count = $_POST['student_count'] ?? 0;
    $online_students = $_POST['online_students'] ?? 0;

    /* ================= 14-DAY BOOKING RESTRICTION FOR LECTURE HALL ================= */
    
    // Get hall info to check if it's a lecture hall
    $hall_query = mysqli_query($conn, "SELECT lectuerhallname FROM classroom WHERE class_id = '$hall'");
    $hall_info = mysqli_fetch_assoc($hall_query);
    $hall_name = $hall_info['lectuerhallname'] ?? '';
    
    // Check if this is a lecture hall (based on hall name)
    $is_lecture_hall = (strpos(strtolower($hall_name), 'lecture') !== false || 
                        strpos(strtolower($hall_name), 'hall') !== false);
    
    if ($is_lecture_hall) {
        $current_date = date('Y-m-d');
        $days_difference = (strtotime($date) - strtotime($current_date)) / (60 * 60 * 24);
        
        if ($days_difference > 14) {
            echo "<script>
            alert('Lecture halls can only be booked up to 14 days in advance!\\nPlease select a date within 14 days from today.');
            window.location='classroom_reservation.php';
            </script>";
            exit;
        }
    }

    /* ================= DOUBLE BOOKING ================= */

    $check = mysqli_query($conn,"
        SELECT id
        FROM class_reservations
        WHERE hall_id='$hall'
        AND date='$date'
        AND (
            ('$start' < end_time)
            AND
            ('$end' > start_time)
        )
    ");

    if(mysqli_num_rows($check) > 0){

        echo "<script>
        alert('Classroom already booked!');
        window.location='classroom_reservation.php';
        </script>";

        exit;
    }

    /* ================= INSERT WITH CREATED_BY AND UPDATED_BY ================= */

    $sql = "
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
        booked_hallid,
        approve_status,
        student_count,
        online_student,
        created_by,
        updated_by
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
        '$hall',
        'Pending',
        '$student_count',
        '$online_students',
        '$current_username',
        '$current_username'
    )
    ";

    if(mysqli_query($conn,$sql)){

        echo "<script>

        if(window.history.replaceState){
            window.history.replaceState(null,null,window.location.href);
        }

        alert('Saved Successfully');

        window.location.href='classroom_reservation.php?t=' + new Date().getTime();

        </script>";

        exit;

    } else {
        die(mysqli_error($conn));
    }
}


include("includes/header.php");
?>

<!DOCTYPE html>
<html>
<head>

<title>Reservation System</title>

<meta http-equiv='Cache-Control' content='no-cache, no-store, must-revalidate'>
<meta http-equiv='Pragma' content='no-cache'>
<meta http-equiv='Expires' content='0'>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>

.select2-container .select2-selection--single{
height:38px !important;
padding-top:4px;
}

/* Warning message style */
.lecture-warning {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 10px;
    margin-top: 10px;
    border-radius: 4px;
    font-size: 13px;
    color: #856404;
}

.capacity-warning {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
    padding: 10px;
    margin-top: 10px;
    border-radius: 4px;
    font-size: 13px;
    color: #721c24;
}

/* Style for disabled select2 when date beyond 14 days */
.select2-container--default.select2-container--disabled .select2-selection--single {
    background-color: #e9ecef !important;
    cursor: not-allowed !important;
}

/* Custom styling for hall options */
.hall-option-disabled {
    opacity: 0.6;
}

</style>

</head>

<body>

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("nav.php"); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("includes/topnav.php"); ?>

                <!-- Begin Page Content -->
                <div class="p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">Collaboration Spaces Reservation (Lectuer Hall | Meeting Space | Auditorium | LAB | Hub)</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Reservation</h6>
                                </div>
                                <div class="card-body">

<!-- ============================================================================== -->

<form method="POST" id="reservationForm">

<!-- ================= TYPE ================= -->

<div class="mb-3">

<label>Type</label>

<select name="reservation_type"
id="type"
class="form-select select2"
required>

<option value="">Select Type</option>

<option>Lectuer</option>
<option>Guest Lectuer</option>
<option>Exam</option>
<option>Review</option>
<option>Prasentation</option>
<option>Parents Meeting</option>
<option>BMS Meeting</option>
<option>Event</option>
<option>Movie</option>

</select>

</div>

<!-- ================= ACADEMIC ================= -->

<div id="academic_form" style="display:none;">

<div class="mb-3">

<label>Programme</label>

<select name="programme"
id="programme"
class="form-select select2 academic-field"
disabled>

<option value="">Select Programme</option>

<?php while($p=mysqli_fetch_assoc($programmes)){ ?>

<option value="<?= $p['program_code'] ?>">

<?= $p['program_name'] ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label>Batch</label>

<select name="batch"
id="batch"
class="form-select select2 academic-field"
disabled>

<option value="">Select Batch</option>

</select>

</div>

<!-- ================= STUDENT COUNT ================= -->

<div id="studentCount"
class="alert alert-info"
style="display:none;">
</div>

<input type="hidden"
name="student_count"
id="student_count">

<!-- ================= ONLINE STUDENTS ================= -->

<div class="mb-3"
id="onlineStudentBox"
style="display:none;">

<label>Online Students</label>

<input type="number"
name="online_students"
id="online_students"
class="form-control"
value="0"
min="0">

<small class="text-primary">

In-person Students :
<span id="inpersonCount">0</span>

</small>

</div>

<!-- ================= MODULE ================= -->

<div class="mb-3">

<label>Module</label>

<select name="module_select"
id="module"
class="form-select select2 academic-field"
disabled>

<option value="">Select Module</option>

</select>

<input type="text"
name="module_text"
class="form-control mt-2 academic-field"
placeholder="Or Enter New Module"
disabled>

</div>

<!-- ================= LECTURER ================= -->

<div class="mb-3">

<label>Lecturer</label>

<select name="lecturer_select"
id="lecturer_select"
class="form-select select2 academic-field"
disabled>

<option value="">Select Lecturer</option>

<!-- This will be populated dynamically based on type and module -->

</select>

<input type="text"
name="lecturer_text"
class="form-control mt-2 academic-field"
placeholder="Or Enter New Lecturer"
disabled>

</div>

</div>

<!-- ================= GENERAL ================= -->

<div id="general_form" style="display:none;">

<div class="mb-3">

<label>Note</label>

<input type="text"
name="note"
class="form-control general-field"
disabled>

</div>

</div>

<!-- ================= COMMON ================= -->

<div class="mb-3">

<label>Date</label>

<input type="date"
name="date"
id="date"
class="form-control"
required
min="<?= date('Y-m-d'); ?>"
max="<?= date('Y-m-d', strtotime('+60 days')); ?>">

</div>

<div class="mb-3">

<label>Start Time</label>

<input type="time"
name="start_time"
id="start_time"
class="form-control"
required>

</div>

<div class="mb-3">

<label>End Time</label>

<input type="time"
name="end_time"
id="end_time"
class="form-control"
required>

</div>

<!-- ================= HALL ================= -->

<div class="mb-3">

<label>Select Hall</label>

<select name="hall"
id="hall"
class="form-select select2"
style="width:100%;">

<option value="Null">Select Hall</option>

</select>

</div>

<div id="lectureHallWarning" style="display:none;"></div>
<div id="hallRestrictionMessage" style="display:none;"></div>

<button type="submit"
name="save"
id="saveButton"
class="btn btn-primary">

Save

</button>

</form>

<!-- =============================================================================== -->

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

    <!-- ======================================================================== -->

<script>

/* ================= SELECT2 ================= */

$(document).ready(function(){

    // Initialize select2 for all elements
    $('.select2').select2({
        width:'100%'
    });
    
    // Enable hall dropdown by default (using prop only)
    $('#hall').prop('disabled', false);
    
    // Enable save button
    $('#saveButton').prop('disabled', false);
    
    // Load halls if date is already selected
    if($('#date').val()) {
        triggerHallLoad();
    }
    
    // If type and module are already selected, load lecturers
    let selectedType = $('#type').val();
    let selectedModule = $('#module').val();
    
    if(selectedType && selectedModule) {
        loadLecturersByTypeAndModule(selectedType, selectedModule);
    } else if(selectedType) {
        loadLecturersByType(selectedType);
    }

});

/* ================= TYPE ================= */

$('#type').change(function(){

    let type = $(this).val();

    $('#programme').val('').trigger('change');

    $('#batch').html('<option value="">Select Batch</option>');

    $('#module').html('<option value="">Select Module</option>');

    $('#hall').html('<option value="Null">Select Hall</option>');

    $('#studentCount').hide();

    $('#online_students').val(0);

    $('#inpersonCount').html(0);

    $('.academic-field').prop('disabled',true);
    $('.general-field').prop('disabled',true);

    $('#academic_form').hide();
    $('#general_form').hide();

    // Academic types (show programme, batch, module, lecturer, student count)
    let academic = [
        "Lectuer",
        "Guest Lectuer",
        "Exam",
        "Review",
        "Prasentation",
        "Parents Meeting"
    ];

    // General types (only show note field)
    let general = [
        "BMS Meeting",
        "Event",
        "Movie"
    ];

    if(academic.includes(type)){

        $('#academic_form').show();

        $('.academic-field').prop('disabled',false);

        $('#onlineStudentBox').show();

        // Load lecturers based on type
        loadLecturersByType(type);

    } else if(general.includes(type)){

        $('#general_form').show();

        $('.general-field').prop('disabled',false);

        $('#onlineStudentBox').hide();
        
        // For general types, clear lecturer dropdown
        $('#lecturer_select').html('<option value="">Select Lecturer</option>');
        $('#lecturer_select').prop('disabled', true);
        
        // Reinitialize select2
        if ($('#lecturer_select').hasClass("select2-hidden-accessible")) {
            $('#lecturer_select').select2('destroy');
        }
        $('#lecturer_select').select2({
            width: '100%'
        });
    }
    
    // Reload halls if date is selected
    if($('#date').val()) {
        triggerHallLoad();
    }

});

/* ================= LOAD BATCH + MODULE ================= */

$('#programme').change(function(){

    let prog = $(this).val();
    let type = $('#type').val();

    /* LOAD BATCH */
    $.post(
    "Timetable/load_batch.php",
    {
        prog: prog
    },
    function(data){
        $('#batch').html(data);
    });

    /* LOAD MODULE FROM PROGRAMME */
    $.post(
    "Timetable/load_module.php",
    {
        programme: prog
    },
    function(data){
        $('#module').html(data);
        
        // After loading modules, if type is Lectuer or Guest Lectuer, load lecturers based on module
        if(type === 'Lectuer' || type === 'Guest Lectuer') {
            let selectedModule = $('#module').val();
            if(selectedModule) {
                loadLecturersByTypeAndModule(type, selectedModule);
            }
        }
    });

});

/* ================= LOAD LECTURERS BASED ON MODULE SELECTION ================= */

$('#module').change(function(){

    let module = $(this).val();
    let type = $('#type').val();

    /* LOAD LECTURERS BASED ON MODULE FOR LECTUER AND GUEST LECTUER */
    if(module && (type === 'Lectuer' || type === 'Guest Lectuer')) {
        loadLecturersByTypeAndModule(type, module);
    } else if(type === 'Exam' || type === 'Review' || type === 'Prasentation' || type === 'Parents Meeting') {
        // For other academic types, load all lecturers
        loadAllLecturers();
    }

});

/* ================= FUNCTION TO LOAD LECTURERS BY TYPE ================= */

function loadLecturersByType(type) {
    let module = $('#module').val();
    
    // For Lectuer and Guest Lectuer - load based on module
    if(type === 'Lectuer' || type === 'Guest Lectuer') {
        if(module) {
            loadLecturersByTypeAndModule(type, module);
        } else {
            // No module selected yet - show message
            $('#lecturer_select').html('<option value="">Select module first</option>');
            $('#lecturer_select').prop('disabled', true);
            
            // Reinitialize select2
            if ($('#lecturer_select').hasClass("select2-hidden-accessible")) {
                $('#lecturer_select').select2('destroy');
            }
            $('#lecturer_select').select2({
                width: '100%'
            });
        }
    } else {
        // For Exam, Review, Prasentation, Parents Meeting - load all lecturers
        loadAllLecturers();
    }
}

/* ================= FUNCTION TO LOAD LECTURERS BY TYPE AND MODULE ================= */

function loadLecturersByTypeAndModule(type, module) {
    // For Lectuer and Guest Lectuer - filter by module
    if(type === 'Lectuer' || type === 'Guest Lectuer') {
        if(module) {
            $('#lecturer_select').html('<option value="">Loading lecturers...</option>');
            $('#lecturer_select').prop('disabled', true);
            
            $.post(
            "Timetable/load_lecturers_by_module.php",
            {
                module: module
            },
            function(data){
                $('#lecturer_select').html(data);
                $('#lecturer_select').prop('disabled', false);
                
                // Reinitialize select2
                if ($('#lecturer_select').hasClass("select2-hidden-accessible")) {
                    $('#lecturer_select').select2('destroy');
                }
                $('#lecturer_select').select2({
                    width: '100%'
                });
            }).fail(function() {
                $('#lecturer_select').html('<option value="">Error loading lecturers</option>');
                $('#lecturer_select').prop('disabled', false);
            });
        }
    } else {
        // For other types - load all lecturers
        loadAllLecturers();
    }
}

/* ================= FUNCTION TO LOAD ALL LECTURERS ================= */

function loadAllLecturers() {
    $('#lecturer_select').html('<option value="">Loading lecturers...</option>');
    $('#lecturer_select').prop('disabled', true);
    
    $.post(
    "Timetable/load_all_lecturers.php",
    {},
    function(data){
        $('#lecturer_select').html(data);
        $('#lecturer_select').prop('disabled', false);
        
        // Reinitialize select2
        if ($('#lecturer_select').hasClass("select2-hidden-accessible")) {
            $('#lecturer_select').select2('destroy');
        }
        $('#lecturer_select').select2({
            width: '100%'
        });
    }).fail(function() {
        $('#lecturer_select').html('<option value="">Error loading lecturers</option>');
        $('#lecturer_select').prop('disabled', false);
    });
}

/* ================= LOAD STUDENT COUNT FROM BATCH ================= */

$('#batch').change(function(){

    let batch = $(this).val();

    let type = $('#type').val();

    $.post(
    "Timetable/get_student_count.php",
    {
        batch_id: batch
    },
    function(count){

        count = parseInt(count || 0);

        if(
            type=="Event" ||
            type=="Parents Meeting" ||
            type=="Movie"
        ){
            count = 1;
        }

        $('#student_count').val(count);

        $('#studentCount')
        .html("Total Students : " + count)
        .show();

        calculateInperson();

    });

});

/* ================= ONLINE STUDENTS ================= */

$('#online_students').on('keyup change', function(){

    calculateInperson();

});

function calculateInperson(){

    let total = parseInt($('#student_count').val() || 0);

    let online = parseInt($('#online_students').val() || 0);

    let inperson = total - online;

    if(inperson < 0){
        inperson = 0;
        $('#online_students').val(total);
    }

    $('#inpersonCount').html(inperson);

    // Trigger hall load after calculating inperson
    triggerHallLoad();

}

/* ================= DATE/TIME ================= */

$('#date,#start_time,#end_time').change(function(){

    calculateInperson();

});

/* ================= LECTURE HALL 14-DAY RESTRICTION ================= */

// Function to calculate days difference from today
function getDaysDifference(dateString) {
    if(!dateString) return null;
    
    let today = new Date();
    let selectedDate = new Date(dateString);
    
    // Reset time to midnight for accurate comparison
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);
    
    let diffTime = selectedDate - today;
    let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    return diffDays;
}

// Function to check if a hall name contains lecture-related keywords
function isLectureHall(hallText) {
    if(!hallText) return false;
    let hallLower = hallText.toLowerCase();
    return hallLower.includes('lecture') || 
           hallLower.includes('hall') ||
           hallLower.includes('auditorium');
}

// Main function to trigger hall loading based on date
function triggerHallLoad() {
    let selectedDate = $('#date').val();
    let inperson = parseInt($('#inpersonCount').text()) || 0;
    
    console.log("Trigger Hall Load - Date:", selectedDate, "Inperson:", inperson);
    
    if(!selectedDate) {
        // No date selected - show message but keep dropdown enabled
        console.log("No date selected");
        $('#hall').html('<option value="Null">Please select a date first</option>');
        $('#hall').prop('disabled', false);
        $('#saveButton').prop('disabled', false);
        $('#lectureHallWarning').html('').hide();
        $('#hallRestrictionMessage').hide();
        
        // Reinitialize select2
        if ($('#hall').hasClass("select2-hidden-accessible")) {
            $('#hall').select2('destroy');
        }
        $('#hall').select2({width: '100%'});
        return;
    }
    
    let daysDiff = getDaysDifference(selectedDate);
    console.log("Days Difference:", daysDiff);
    
    // Check for past date
    if(daysDiff < 0) {
        console.log("Past date - disabling");
        $('#lectureHallWarning').html('<div class="capacity-warning">⚠️ Cannot select a date in the past! Please select a future date.</div>').show();
        
        $('#hall').html('<option value="Null">Hall selection unavailable - Past date selected</option>');
        $('#hall').prop('disabled', true);
        $('#saveButton').prop('disabled', true);
        
        // Reinitialize select2
        if ($('#hall').hasClass("select2-hidden-accessible")) {
            $('#hall').select2('destroy');
        }
        $('#hall').select2({width: '100%'});
        return;
    }
    
    // Check if date is within 14 days
    if(daysDiff >= 0 && daysDiff <= 14) {
        // WITHIN ALLOWED RANGE - Load halls normally
        console.log("Date within range - Loading halls");
        $('#lectureHallWarning').hide();
        loadHalls(inperson);
        
    } else if(daysDiff > 14) {
        // BEYOND 14 DAYS - Disable completely
        console.log("Date beyond 14 days - Disabling");
        $('#lectureHallWarning').html('<div class="capacity-warning">⚠️ <strong>BOOKING RESTRICTION!</strong><br>Reservations can only be made within 14 days from today.<br>Selected date is ' + daysDiff + ' days from today.<br>Please select a date between ' + getTodayDate() + ' and ' + getMaxAllowedDate() + '</div>').show();
        
        $('#hall').html('<option value="Null">Hall selection unavailable - Date exceeds 14 days limit</option>');
        $('#hall').prop('disabled', true);
        $('#saveButton').prop('disabled', true);
        
        // Reinitialize select2
        if ($('#hall').hasClass("select2-hidden-accessible")) {
            $('#hall').select2('destroy');
        }
        $('#hall').select2({width: '100%'});
    }
}

// Helper function to get today's date
function getTodayDate() {
    let today = new Date();
    let year = today.getFullYear();
    let month = String(today.getMonth() + 1).padStart(2, '0');
    let day = String(today.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

// Helper function to get max allowed date
function getMaxAllowedDate() {
    let maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 14);
    let year = maxDate.getFullYear();
    let month = String(maxDate.getMonth() + 1).padStart(2, '0');
    let day = String(maxDate.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

// Load halls function - Calls your load_available_halls.php
function loadHalls(studentCount=0){

    let date = $('#date').val();
    let start = $('#start_time').val();
    let end = $('#end_time').val();
    let type = $('#type').val();
    let online = parseInt($('#online_students').val() || 0);
    
    console.log("Loading halls with params:", {date, start, end, studentCount, online, type});
    
    // Show loading state
    $('#hall').html('<option value="Null">Loading halls...</option>');
    $('#hall').prop('disabled', true);
    
    // Destroy old select2 if exists
    if ($('#hall').hasClass("select2-hidden-accessible")) {
        $('#hall').select2('destroy');
    }
    
    $.ajax({
        url: "Timetable/load_available_halls.php",
        type: "POST",
        data: {
            date: date,
            start: start,
            end: end,
            students: studentCount,
            online_students: online,
            type: type
        },
        success: function(data) {
            console.log("Halls loaded successfully");
            
            // Update options
            $('#hall').html(data);
            
            // Enable dropdown
            $('#hall').prop('disabled', false);
            
            // Reinitialize select2
            $('#hall').select2({
                width: '100%'
            });
            
            // Check if any options are available
            let hasOptions = false;
            $('#hall option').each(function() {
                if($(this).val() !== "Null" && $(this).val() !== "") {
                    hasOptions = true;
                }
            });
            
            if(!hasOptions) {
                $('#hall').html('<option value="Null">No halls available for selected criteria</option>');
                $('#hall').prop('disabled', true);
                $('#saveButton').prop('disabled', true);
                
                // Reinitialize select2
                $('#hall').select2({
                    width: '100%'
                });
            } else {
                $('#saveButton').prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error loading halls:", error);
            $('#hall').html('<option value="Null">Error loading halls. Please try again.</option>');
            $('#hall').prop('disabled', false);
            
            // Reinitialize select2
            $('#hall').select2({
                width: '100%'
            });
        }
    });
}

// When date changes
$('#date').change(function() {
    let selectedDate = $(this).val();
    console.log("Date changed to:", selectedDate);
    
    if(selectedDate) {
        let daysDiff = getDaysDifference(selectedDate);
        
        // Validate date is not in the past
        if(daysDiff < 0) {
            alert('ERROR: Cannot book a date in the past!\n\nSelected date: ' + selectedDate + '\nPlease select a future date.');
            $(this).val('');
            triggerHallLoad();
            return;
        }
        
        // Trigger hall load
        triggerHallLoad();
    } else {
        triggerHallLoad();
    }
});

// When start time or end time changes, reload halls
$('#start_time, #end_time').change(function() {
    let selectedDate = $('#date').val();
    if(selectedDate) {
        let daysDiff = getDaysDifference(selectedDate);
        if(daysDiff >= 0 && daysDiff <= 14) {
            let inperson = parseInt($('#inpersonCount').text()) || 0;
            loadHalls(inperson);
        }
    }
});

// Form submission validation
$('#reservationForm').on('submit', function(e) {
    let selectedDate = $('#date').val();
    let selectedHall = $('#hall').val();
    
    if(!selectedDate) {
        e.preventDefault();
        alert('Please select a date first!');
        return false;
    }
    
    let daysDiff = getDaysDifference(selectedDate);
    
    if(daysDiff > 14) {
        e.preventDefault();
        alert('BOOKING NOT ALLOWED!\n\nReservations can only be made within 14 days from today.\n\n' +
              'Today: ' + getTodayDate() + '\n' +
              'Selected date: ' + selectedDate + ' (' + daysDiff + ' days from today)\n' +
              'Maximum allowed date: ' + getMaxAllowedDate());
        return false;
    }
    
    if(daysDiff < 0) {
        e.preventDefault();
        alert('Cannot book a date in the past!');
        return false;
    }
    
    // if(!selectedHall || selectedHall === 'Null') {
    //     e.preventDefault();
    //     alert('Please select a hall!');
    //     return false;
    // }
    
    return true;
});

// Initialize on page load
$(document).ready(function() {
    // Set date input min and max
    let today = new Date();
    let max60 = new Date();
    max60.setDate(max60.getDate() + 60);
    
    $('#date').attr('min', getTodayDate());
    $('#date').attr('max', max60.toISOString().split('T')[0]);
    
    console.log("Date range set: min=" + getTodayDate() + ", max=" + max60.toISOString().split('T')[0]);
    
    // Initial load if date is selected
    if($('#date').val()) {
        triggerHallLoad();
    }
});

</script>

</body>
</html>