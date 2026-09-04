<?php
//session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

require_once 'PermissionChecking.php';

/* =========================
FILTER DATE
========================= */
$filterDate = $_GET['filter_date'] ?? date('Y-m-d');

/* =========================
DATA - Only Approved Reservations
========================= */

include("includes/header.php");
?>

<!DOCTYPE html>
<html>
<head>
<title>Timetable Calendar - Classroom Allocation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
.card{ border:none; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.08); }
.fc-event{ padding:2px 4px; font-size:10px; white-space:normal; line-height:1.3; cursor:pointer; }
.fc-daygrid-event{ white-space:normal; }

/* Approved only - #042d5c background with white font */
.fc-event.approved { 
    background-color: #042d5c !important; 
    border-color: #042d5c !important; 
    color: #ffffff !important; 
}
.fc-event.approved .fc-event-main,
.fc-event.approved .fc-event-main * { 
    color: #ffffff !important; 
}

/* Weekday headers color */
.fc-col-header-cell-custom a,
.fc-col-header-cell .fc-col-header-cell-custom,
.fc-day-header {
    color: #dc3545 !important;
    font-weight: bold !important;
}

.fc-col-header-cell {
    background-color: #f8f9fa;
}

/* Calendar event styling */
.fc-daygrid-day-events { min-height: 40px; }
.fc-daygrid-event { white-space: normal !important; margin: 1px 2px !important; padding: 2px !important; font-size: 9px !important; }
.fc-daygrid-event .fc-event-title { white-space: normal !important; word-break: break-word !important; }
.fc-event-main { overflow: wrap; }
.fc-daygrid-day-number { font-size: 11px; }

/* Date styling in calendar body */
.fc-daygrid-day-frame {
    background-color: #fff;
}
.fc-daygrid-day-number {
    font-size: 12px;
    font-weight: 500;
}

/* Make calendar take full width of card */
#calendar {
    width: 100%;
}

.fc {
    width: 100% !important;
}

.fc-view-harness {
    width: 100% !important;
}

/* Lecturer row red color */
.lecturer-red {
    color: #dc3545 !important;
    font-weight: bold;
}

/* Modal table full width */
.modal-body .table {
    width: 100%;
    margin-bottom: 1px;
}

.modal-body .table td {
    word-break: break-word;
}

/* Event card internal styling */
.fc-event-main-inner {
    width: 100%;
}

.event-room {
    font-size: 11px;
    font-weight: bold;
    word-wrap: break-word;
    white-space: normal;
    line-height: 1.2;
    margin-bottom: 2px;
}

.event-time {
    font-weight: bold;
    font-size: 10.5px;
    margin-bottom: 2px;
}

.event-programme {
    font-weight: 600;
    font-size: 10px;
    word-wrap: break-word;
    white-space: normal;
    line-height: 1.2;
    margin-bottom: 1px;
}

.event-batch {
    font-weight: bold;
    font-size: 9.5px;
    word-wrap: break-word;
    white-space: normal;
    margin-bottom: 2px;
}

.event-type {
    font-size: 10px;
    margin-top: 4px;
}

.event-type-badge {
    background-color: #fd0d0d;
    padding: 1px 4px 2px 2px;
    border-radius: 4px;
    font-size: 9.5px;
    font-weight: bolder ;
    letter-spacing: 0.15em;
}

.event-note {
    font-size: 9px;
    margin-top: 2px;
    color: #ffd966;
    font-style: italic;
    word-wrap: break-word;
    line-height: 1.2;
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
                    <h4 class="h4 mb-0 text-gray-800">Timetable - Approved Reservations</h4>
                </div>

                <div class="row g-2">
                    <div class="col-md-12">
                        <!-- CALENDAR -->
                        <div class="card shadow p-2 mb-2">
                            <h5 class="mb-1">Approved Reservations Calendar</h5>
                            <div id="calendar" style="height:550px; width:100%;"></div>
                        </div>
                        
                        <!-- FILTER -->
                        <div class="card shadow p-2 mb-2">
                            <form method="GET" class="row g-1" id="filterForm">
                                <div class="col-md-3">
                                    <input type="date" name="filter_date" id="filter_date_input" class="form-control form-control-sm" value="<?= $filterDate ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100" style="height: 32px; font-size: 12px; padding: 0.2rem 0.5rem;">Go to Date</button>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" id="todayBtn" class="btn btn-secondary w-100" style="height: 32px; font-size: 12px; padding: 0.2rem 0.5rem;">Today</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
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

<script>
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
            
            let titleHtml = `<div style="line-height: 1.3; padding: 2px; width: 100%;">
                        <div class="event-room">${room}</div>
                        <div class="event-time">${timeText}</div>
                        <div class="event-programme">${programme}</div>
                        <div class="event-batch">${batch}</div>
                        ${note ? `<div class="event-note"># ${note.substring(0, 30)}${note.length > 30 ? '...' : ''}</div>` : ''}
                        <div class="event-type"><span class="event-type-badge">${type}</span></div>
                     </div>`;
            
            eventDiv.innerHTML = titleHtml;
            return { domNodes: [eventDiv] };
        },
        events: [
            <?php
            // Only fetch Approved reservations
            $cal = mysqli_query($conn,"SELECT r.*, c.lectuerhallname, p.program_name, b.batch_name 
                                       FROM class_reservations r 
                                       LEFT JOIN classroom c ON c.class_id = r.hall_id
                                       LEFT JOIN program_table p ON r.programme = p.program_code
                                       LEFT JOIN batch_table b ON r.batch = b.id
                                       WHERE r.approve_status = 'Approved'
                                       ORDER BY r.date ASC");
            while($c = mysqli_fetch_assoc($cal)){
                $hall = htmlspecialchars($c['lectuerhallname'] ?? 'Not Assigned', ENT_QUOTES);
                $programmeName = htmlspecialchars($c['program_name'] ?? $c['programme'], ENT_QUOTES);
                $batchName = htmlspecialchars($c['batch_name'] ?? $c['batch'], ENT_QUOTES);
                $type = htmlspecialchars($c['type'] ?? '', ENT_QUOTES);
                $note = htmlspecialchars($c['note'] ?? '', ENT_QUOTES);
                $lecturer = htmlspecialchars($c['lecturer'] . (!empty($c['lecturer_text']) && $c['lecturer_text'] != $c['lecturer'] ? ' | ' . $c['lecturer_text'] : ''), ENT_QUOTES);
                $module = htmlspecialchars($c['module'] . (!empty($c['module_text']) && $c['module_text'] != $c['module'] ? ' | ' . $c['module_text'] : ''), ENT_QUOTES);
            ?>
            {
                id: '<?= $c['id'] ?>',
                title: '<?= addslashes($hall) ?>',
                start: '<?= $c['date'] ?>T<?= $c['start_time'] ?>',
                end: '<?= $c['date'] ?>T<?= $c['end_time'] ?>',
                className: 'approved',
                extendedProps: {
                    room: '<?= addslashes($hall) ?>',
                    status: '<?= $c['approve_status'] ?>',
                    module: '<?= addslashes($module) ?>',
                    lecturer: '<?= addslashes($lecturer) ?>',
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
            var statusHtml = status == 'Approved' ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-secondary">' + status + '</span>';
            var inClass = (parseInt(e.extendedProps.studentCount) || 0) - (parseInt(e.extendedProps.onlineStudent) || 0);
            
            $('#eventDetails').html(`
                <table class="table table-sm" style="width: 100%;">
                    <tr><th style="width: 35%;">Type</th><td style="width: 65%;">${e.extendedProps.type || ''}</td></tr>
                    <tr><th>Room</th><td>${e.extendedProps.room}</td></tr>
                    <tr><th>Time</th><td>${e.extendedProps.startTime} - ${e.extendedProps.endTime}</td></tr>
                    <tr><th>Programme</th><td>${e.extendedProps.programme}</td></tr>
                    <tr><th>Batch</th><td>${e.extendedProps.batch}</td></tr>
                    <tr><th>Module</th><td>${e.extendedProps.module}</td></tr>
                    <tr><th>Lecturer</th><td class="lecturer-red">${e.extendedProps.lecturer}</td></tr>
                    <tr><th>Students</th><td>Total: ${e.extendedProps.studentCount} | Online: ${e.extendedProps.onlineStudent} | In-Class: ${inClass}</td></tr>
                    <tr><th>Date</th><td>${e.extendedProps.date}</td></tr>
                    <tr><th>Status</th><td>${statusHtml}</td></tr>
                    ${e.extendedProps.note ? `<tr><th>Note</th><td>${e.extendedProps.note}</td></tr>` : ''}
                </table>
            `);
            new bootstrap.Modal(document.getElementById('eventModal')).show();
        }
    });
    calendar.render();
    
    // Apply weekday header color after calendar renders
    setTimeout(function() {
        $('.fc-col-header-cell .fc-col-header-cell-custom a, .fc-col-header-cell .fc-day-header').css('color', '#dc3545');
        $('.fc-col-header-cell').css('backgroundColor', '#f8f9fa');
    }, 100);
    
    // Go to specific date
    var filterDate = '<?= $filterDate ?>';
    if(filterDate && filterDate !== '<?= date('Y-m-d') ?>'){
        calendar.gotoDate(filterDate);
        calendar.changeView('timeGridDay', filterDate);
    }
    
    // Today button
    $('#todayBtn').on('click', function(){
        var today = new Date().toISOString().split('T')[0];
        calendar.gotoDate(today);
        calendar.changeView('dayGridMonth');
        $('#filter_date_input').val(today);
    });
    
    // Filter form submission
    $('#filterForm').on('submit', function(e){
        e.preventDefault();
        var date = $('#filter_date_input').val();
        if(date){
            calendar.gotoDate(date);
            calendar.changeView('timeGridDay', date);
        }
    });
});
</script>

</body>
</html>