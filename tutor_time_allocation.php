<?php
session_start();
include("database/connection.php");
include("includes/header.php");

// Determine if user is Super Admin
$is_admin = ($_SESSION['role'] === 'super_admin'); // Adjust 'role' key based on your session variable
$Session_username = $_SESSION['username'];

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

// Initial ID for the current user
$logged_in_tutor_id = $_SESSION['user_id'];

/* ===================== FETCH ALL TUTORS (FOR ADMIN ONLY) ===================== */
$all_tutors = [];
if ($is_admin) {
    $tutor_query = mysqli_query($conn, "SELECT id, username,admin_email FROM admin WHERE role = 'lecture' OR role = 'lecturer'"); // Adjust table/role names
    while($row = mysqli_fetch_assoc($tutor_query)) {
        $all_tutors[] = $row;
    }
}

/* ===================== FETCH PROGRAMS (Initial Load) ===================== */
// If admin, we wait for selection. If tutor, we load their programs.
$programs = mysqli_query(
    $conn,
    "SELECT DISTINCT p.program_code, p.program_name
     FROM tutor_session_allocation tsa
     INNER JOIN program_table p ON tsa.program_code = p.program_code
     WHERE tsa.tutor_id = $logged_in_tutor_id"
);

/* ===================== FETCH BATCHES ===================== */
$batches = mysqli_query($conn, "SELECT id, batch_name, programme FROM batch_table");

/* ===================== FETCH SESSIONS ===================== */
$sessions = mysqli_query(
    $conn,
    "SELECT tsa.allocation_id, tsa.program_code, tsa.session_id, tsa.session_start_date, tsa.session_end_date, 
            ts.session_name, tsa.batch_id
     FROM tutor_session_allocation tsa
     INNER JOIN tutor_session ts on tsa.session_id = ts.session_id
     WHERE tsa.tutor_id = $logged_in_tutor_id"
);

/* ===================== FETCH SESSION STATUS TABLE ===================== */
$table_where = $is_admin ? "1=1" : "tsa.tutor_id = $logged_in_tutor_id";

$table_query = mysqli_query($conn, "
    SELECT tsa.*, ts.session_name, bt.batch_name, pt.program_name
    FROM tutor_session_allocation tsa
    INNER JOIN tutor_session ts ON tsa.session_id = ts.session_id
    INNER JOIN batch_table bt ON tsa.batch_id = bt.id
    INNER JOIN program_table pt ON tsa.program_code = pt.program_code
    WHERE $table_where
    ORDER BY tsa.session_start_date DESC
");


?>

<!DOCTYPE html>
<html>
<head>
    <title>Tutor Time Allocation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <style>.stat-card{border-left:5px solid #0d6efd;}</style>
</head>
<body class="bg-light">

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Tutor Time Allocation <?= $is_admin ? '(Admin Mode)' : '' ?></h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;
                                <h6 class="mb-0">Create Time Slot</h6>
                            </div>
                            <div class="card-body">

                                <?php if($is_admin): ?>
                                <div class="alert alert-info mb-4">
                                    <label class="fw-bold">Select Lecturer to Manage:</label>
                                    <select id="adminTutorSelect" class="form-select select2">
                                        <option value="">-- Search Lecturer (Name/Email) --</option>
                                        <?php foreach($all_tutors as $t): ?>
                                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['username']) ?> (<?= htmlspecialchars($t['admin_email']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <div class="card mb-4">
                                    <div class="card-header fw-bold">Create Time Slots</div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label>Program</label>
                                                <select id="programSelect" class="form-select" <?= $is_admin ? 'disabled' : '' ?>>
                                                    <option value="">Select Program</option>
                                                    <?php while($p = mysqli_fetch_assoc($programs)){ ?>
                                                        <option value="<?= $p['program_code'] ?>"><?= htmlspecialchars($p['program_name']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label>Batch</label>
                                                <select id="batchSelect" class="form-select" disabled>
                                                    <option value="">Select Batch</option>
                                                    <?php while($b = mysqli_fetch_assoc($batches)){ ?>
                                                        <option value="<?= $b['id'] ?>" data-program="<?= $b['programme'] ?>" style="display:none;"><?= htmlspecialchars($b['batch_name']) ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label>Session</label>
                                                <select id="sessionSelect" class="form-select" disabled>
                                                    <option value="">Select Session</option>
                                                    <?php 
                                                    mysqli_data_seek($sessions, 0);
                                                    while($s = mysqli_fetch_assoc($sessions)){ ?>
                                                        <option value="<?= $s['allocation_id'] ?>" 
                                                                data-program="<?= $s['program_code'] ?>" 
                                                                data-batch="<?= $s['batch_id'] ?>" 
                                                                data-start="<?= $s['session_start_date'] ?>" 
                                                                data-end="<?= $s['session_end_date'] ?>" 
                                                                style="display:none;">
                                                            <?= htmlspecialchars($s['session_name']) ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label>Session Start Date</label>
                                                <div id="sessionStart" class="form-control bg-light">—</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Session End Date</label>
                                                <div id="sessionEnd" class="form-control bg-light">—</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Date</label>
                                                <input type="date" id="slotDate" class="form-control" disabled>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Start Time</label>
                                                <input type="time" id="startTime" class="form-control" disabled>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Duration (mins)</label>
                                                <select id="duration" class="form-select" disabled>
                                                    <option value="">Select</option>
                                                    <option value="10">10</option><option value="15">15</option>
                                                    <option value="20">20</option><option value="30">30</option>
                                                    <option value="40">40</option><option value="45">45</option>
                                                    <option value="60">60</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label>End Slot</label>
                                                <select id="endSlot" class="form-select" disabled><option value="">Select</option></select>
                                            </div>
                                        </div>
                                        <div class="text-end mt-3">
                                            <button class="btn btn-success" id="generateSlots">Save Time Slots</button>
                                        </div>
                                    </div>
                                </div>


<div class="card mt-4 border-0 shadow-sm">
    <div class="card-header bg-white fw-bold">Allocated Sessions Status</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Program & Batch</th>
                        <th>Session Name</th>
                        <th>Duration</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $today = date('Y-m-d');
                    while($row = mysqli_fetch_assoc($table_query)): 
                        $start = $row['session_start_date'];
                        $end = $row['session_end_date'];
                        
                        // Status Logic
                        if ($today < $start) {
                            $status = '<span class="badge bg-success">Upcoming</span>';
                        } elseif ($today >= $start && $today <= $end) {
                            $status = '<span class="badge bg-warning text-dark">Going on</span>';
                        } else {
                            $status = '<span class="badge bg-danger">Session End</span>';
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold small"><?= htmlspecialchars($row['program_name']) ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($row['batch_name']) ?></div>
                        </td>
                        <td class="small"><?= htmlspecialchars($row['session_name']) ?></td>
                        <td class="small text-muted">
                            <?= date('M d Y', strtotime($start)) ?> - <?= date('M d Y', strtotime($end)) ?>
                        </td>
                        <td class="text-center"><?= $status ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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

<script>
$(document).ready(function() {
    $('.select2').select2();
    
    // Global active tutor ID (defaults to logged in user)
    let activeTutorId = "<?= $logged_in_tutor_id ?>";

    /* ================= ADMIN: CHANGE ACTIVE TUTOR ================= */
    $('#adminTutorSelect').on('change', function() {
        let selectedTutor = $(this).val();
        if(!selectedTutor) return;
        
        activeTutorId = selectedTutor;
        
        // Fetch new data for the selected tutor via AJAX
        $.ajax({
            url: 'tutortime/fetch_tutor_context.php',
            type: 'POST',
            data: { tutor_id: activeTutorId },
            success: function(response) {
                let data = JSON.parse(response);
                
                // Refresh Program Dropdown
                let progHtml = '<option value="">Select Program</option>';
                data.programs.forEach(p => {
                    progHtml += `<option value="${p.program_code}">${p.program_name}</option>`;
                });
                $('#programSelect').html(progHtml).prop('disabled', false);

                // Refresh Session Dropdown data (hidden in logic)
                let sessHtml = '<option value="">Select Session</option>';
                data.sessions.forEach(s => {
                    sessHtml += `<option value="${s.allocation_id}" 
                        data-program="${s.program_code}" 
                        data-batch="${s.batch_id}" 
                        data-start="${s.session_start_date}" 
                        data-end="${s.session_end_date}" 
                        style="display:none;">${s.session_name}</option>`;
                });
                $('#sessionSelect').html(sessHtml);

                // Reset UI
                $('#batchSelect, #sessionSelect, #slotDate, #startTime, #duration, #endSlot').prop('disabled', true).val('');
                $('#sessionStart, #sessionEnd').text('—');
            }
        });
    });

    /* ================= TIME HELPERS ================= */
    function t2m(t){ let a=t.split(':'); return (+a[0])*60 + (+a[1]); }
    function m2t(m){ return String(Math.floor(m/60)).padStart(2,'0')+':'+String(m%60).padStart(2,'0'); }
    let bookedSlots = [];

    function isOverlapping(start, end, booked) {
        let s1 = t2m(start), e1 = t2m(end);
        for (let b of booked) {
            let s2 = t2m(b.start_time), e2 = t2m(b.end_time);
            if (s1 < e2 && e1 > s2) return true;
        }
        return false;
    }

    /* ================= CASCADE LOGIC ================= */
    $('#programSelect').on('change', function(){
        let program = $(this).val();
        $('#batchSelect').prop('disabled', true).val('');
        $('#batchSelect option[data-program]').hide();
        if(program){
            $('#batchSelect option[data-program="'+program+'"]').show();
            $('#batchSelect').prop('disabled', false);
        }
        $('#sessionSelect').prop('disabled', true).val('');
        $('#sessionStart,#sessionEnd').text('—');
    });

    $('#batchSelect').on('change', function(){
        let program = $('#programSelect').val();
        let batch = $(this).val();
        $('#sessionSelect option').hide();
        $('#sessionSelect option[data-program="'+program+'"][data-batch="'+batch+'"]').show();
        if(batch) $('#sessionSelect').prop('disabled', false);
    });

    $('#sessionSelect').on('change', function(){
        let opt = $(this).find(':selected');
        let start = opt.data('start'), end = opt.data('end');
        $('#sessionStart').text(start || '—');
        $('#sessionEnd').text(end || '—');
        if(start && end){
            $('#slotDate').prop('disabled', false).attr('min', start).attr('max', end).val('');
        }
    });

    $('#slotDate').on('change', function(){
        let selected=$(this).val();
        if(!selected) return;
        $.ajax({
            url: 'tutortime/get_available_slots.php',
            type: 'POST',
            data: { date: selected, tutor_id: activeTutorId }, // Use activeTutorId
            success: function(res){ bookedSlots = JSON.parse(res || '[]'); }
        });
        $('#startTime,#duration').prop('disabled',false);
    });

    $('#startTime,#duration').on('change', function(){
        let s = $('#startTime').val(), d = parseInt($('#duration').val());
        $('#endSlot').html('<option value="">Select</option>').prop('disabled', !s || !d);
        if(!s || !d) return;
        let startM = t2m(s), maxM = 24*60;
        for(let t = startM + d; t <= maxM; t += d){
            let st = m2t(t - d), et = m2t(t);
            let disabled = isOverlapping(st, et, bookedSlots);
            $('#endSlot').append(`<option value="${t}" ${disabled ? 'disabled' : ''}>${st} - ${et}</option>`);
        }
    });

    $('#generateSlots').click(function(){
        let date = $('#slotDate').val(), session = $('#sessionSelect').val(),
            start = $('#startTime').val(), dur = $('#duration').val(), end = $('#endSlot').val();
        if(!date || !start || !end){ alert('Fill all fields'); return; }

        let slots=[], s=t2m(start), e=parseInt(end), d=parseInt(dur);
        for(let t=s; t<e; t+=d){ slots.push({start:m2t(t), end:m2t(t+d)}); }

        $.ajax({
            url:'tutortime/save_time_slots.php',
            type:'POST',
            data:{
                slot_date: date,
                slots: JSON.stringify(slots),
                session_id: session,
                tutor_id: activeTutorId, // Pass the active tutor ID
                program_code: $('#programSelect').val(),
                batch_id: $('#batchSelect').val()
            },
            success:function(res){
                if(res.trim()=='success') alert('Time slots saved successfully');
                else alert('Error: '+res);
            }
        });
    });
});
</script>
</body>
</html>