<?php
include("database/connection.php");
session_start();

$Session_username = $_SESSION['username'] ?? null;
$current_user_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$user_role        = $_SESSION['role'] ?? ''; 

if (!$Session_username) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

require_once 'PermissionChecking.php';

/* =====================
   AJAX: Get Grid Calendar
===================== */
if (isset($_POST['action']) && $_POST['action'] == 'get_calendar_grid') {
    $month = (int)($_POST['month'] ?? date('m'));
    $year  = (int)($_POST['year'] ?? date('Y'));
    
    // Logic: Filter by Programs allocated to this specific user
    $is_super_admin = ($user_role == 'super_admin' || $user_role == 'admin');

    if ($is_super_admin) {
        $where_clause = "1=1"; // Admins see everything
    } else {
        // Only show sessions where the program_code is allocated to the logged-in user
        $where_clause = "tsa.program_code IN (SELECT pau.program_code FROM program_allocation_user pau WHERE user_id = '$current_user_id')";
    }

    $firstDayOfMonth = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $startDayOfWeek = date('w', strtotime($firstDayOfMonth));

    $sql = "SELECT tsa.*, ts.session_name, bt.batch_name, pt.program_name, a.username AS Lecturee, a.id
            FROM tutor_session_allocation tsa
            JOIN tutor_session ts ON tsa.session_id = ts.session_id
            JOIN batch_table bt ON tsa.batch_id = bt.id
            JOIN program_table pt ON tsa.program_code = pt.program_code
            JOIN admin a ON a.id = tsa.tutor_id
            WHERE ($where_clause)
              AND (tsa.session_start_date <= LAST_DAY('$firstDayOfMonth'))
              AND (tsa.session_end_date >= '$firstDayOfMonth')
            ORDER BY tsa.session_start_date ASC";
    
    $result = $conn->query($sql);
    $events = [];
    while($row = $result->fetch_assoc()) { $events[] = $row; }

    // Custom Japanese Traditional Color Palette
    $session_palette = [
        

        '#ffc1dc', // Sakura (Pink)
        '#a7d28d', // Wakatake (Soft Green)
        '#95a5a6', // Gin-nezumi (Grey)
        '#87ceeb', // Mizu (Light Blue)
        '#82b1ff', // Sora (Sky Blue)
        '#f9cf8b', // Tamago (Yellow)
        '#ff5387', // Wakatake (Soft Green)
        '#ef7159', // Akane (Tomato Red)
        '#d1c4e9', // Deep Purple Mist (New)
        '#ffe082', // Amber (New)
        '#c8e6c9', // Pale Mint (New)
        '#ffccbc'  // Deep Peach (New)
        


    ];

    $html = '<div class="calendar-grid">';
    for ($i = 0; $i < $startDayOfWeek; $i++) {
        $html .= '<div class="calendar-day empty bg-light"></div>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = "$year-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
        $html .= "<div class='calendar-day'><div class='day-number'>$day</div>";

        foreach ($events as $event) {
            if ($currentDate >= $event['session_start_date'] && $currentDate <= $event['session_end_date']) {
                
                $color = $session_palette[$event['allocation_id'] % count($session_palette)];
                $isStart = ($currentDate == $event['session_start_date']);
                $isEnd = ($currentDate == $event['session_end_date']);
                
                // Styling for connected bars
                $style = "background-color: $color; color: #222222; margin-bottom: 2px; z-index: -1
                padding: 6px 6px; font-size: 0.65rem; min-height: 65px; display: flex; 
                flex-direction: column; justify-content: center; border: 1px solid rgba(0,0,0,0.05); 
                line-height: 1.2; overflow: ellipsis; cursor: pointer; text-wrap: wrap; ";
                
                if ($isStart) $style .= " border-top-left-radius: 10px; border-bottom-left-radius: 10px; margin-left: 3px;";
                if ($isEnd) $style .= " border-top-right-radius: 10px; border-bottom-right-radius: 10px; margin-right: 3px;";

                // Show detailed text only on first day, start of week, or start of month
                $showText = ($isStart || $day == 1 || date('w', strtotime($currentDate)) == 0);
                
                if ($showText) {
                    $details = "<strong>" . htmlspecialchars($event['session_name']) . " <br> " . htmlspecialchars($event['Lecturee']) . "</strong>";
                    $details .= "<div style='font-size: 0.6rem; opacity: 0.9;'>" . htmlspecialchars($event['program_name']) . " - " . htmlspecialchars($event['batch_name']) . "</div>";
                } else {
                    $details = "&nbsp;";
                }

                $fullTitle = "Program: {$event['program_name']} \nBatch: {$event['batch_name']} \nSession: {$event['session_name']}";
                $html .= "<div class='event-block' style='$style' title='".htmlspecialchars($fullTitle)."'>$details</div>";
            }
        }
        $html .= '</div>';
    }
    echo $html . '</div>';
    exit();
}

/* =====================
   AJAX: Get Sessions Checkboxes
===================== */
if (isset($_POST['action']) && $_POST['action'] == 'get_sessions') {
    $program_code = $conn->real_escape_string($_POST['program_code']);
    $tutor_id = $conn->real_escape_string($_POST['tutor_id']);
    $batch_id = $conn->real_escape_string($_POST['batch_id']);

    $sql = "SELECT ts.session_id, ts.session_name, ts.session_type, tsa.session_start_date, tsa.session_end_date,
            CASE WHEN tsa.allocation_id IS NULL THEN 0 ELSE 1 END AS allocated
            FROM tutor_session ts
            LEFT JOIN tutor_session_allocation tsa ON ts.session_id = tsa.session_id
            AND tsa.program_code = '$program_code' AND tsa.tutor_id = '$tutor_id' AND tsa.batch_id = '$batch_id'
            WHERE ts.program_code = '$program_code'";

    $result = $conn->query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) { $rows[] = $row; }
    echo json_encode($rows);
    exit();
}

/* =====================
   Save Allocations
===================== */
if (isset($_POST['save_allocation'])) {
    $program_code = $_POST['program_code'];
    $tutor_id = $_POST['tutor_id'];
    $batch_id = $_POST['batch_id'];
    $allocated = $_POST['allocated'] ?? [];
    $start_dates = $_POST['start_date'] ?? [];
    $end_dates = $_POST['end_date'] ?? [];

    $conn->query("DELETE FROM tutor_session_allocation WHERE tutor_id='$tutor_id' AND program_code='$program_code' AND batch_id='$batch_id'");

    foreach ($allocated as $sid) {
        $s = !empty($start_dates[$sid]) ? $start_dates[$sid] : null;
        $e = !empty($end_dates[$sid]) ? $end_dates[$sid] : null;
        $stmt = $conn->prepare("INSERT INTO tutor_session_allocation (tutor_id, program_code, batch_id, session_id, session_start_date, session_end_date, created_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("isisssi", $tutor_id, $program_code, $batch_id, $sid, $s, $e, $current_user_id);
        $stmt->execute();
    }
    $_SESSION['success_message'] = "Saved successfully!";
    header("Location: tutor_session.php");
    exit();
}

        // Fetch Initial Data
       // Fetch Initial Data
$is_super_admin = ($user_role == 'super_admin' || $user_role == 'admin');

if ($is_super_admin) {
    // Admin: Load all programs
    $programs = $conn->query("SELECT program_code, program_name FROM program_table");
} else {
    // Regular User: Load only programs allocated to them in program_allocation_user
    $programs = $conn->query("
        SELECT p.program_code, p.program_name 
        FROM program_table p
        JOIN program_allocation_user pau ON p.program_code = pau.program_code
        WHERE pau.user_id = '$current_user_id'
    ");
}

$tutor_query = $conn->query("SELECT id, username FROM admin WHERE role='lecture'");
$batch_query = $conn->query("SELECT id, batch_name, programme FROM batch_table");
$batch_list = [];
while($r = $batch_query->fetch_assoc()){ $batch_list[$r['programme']][] = $r; }

        include("includes/header.php");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Allocation Grid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <style>
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); background-color: #dee2e6; gap: 1px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; }
        .calendar-day { background-color: #fff; padding: 5px 0px; width: 183px; }
        .day-number { text-align: right; padding-right: 10px; font-size: 0.85rem; font-weight: 600; color: #adb5bd; margin-bottom: 6px; }
        .event-block { transition: filter 0.2s; white-space: nowrap; text-overflow: ellipsis; border: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .event-block:hover { filter: brightness(90%); z-index: 10; }
        .calendar-header-days { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; background: #e4e7e9; border: 1px solid #dee2e6; color: #777; font-size: 0.75rem; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .calendar-header-days div { padding: 10px; }
        .select2-container--bootstrap-5 { font-size: 0.875rem; }
        .tablerow {
            height: 48px;
            display: flex;
            justify-content: center; /* Horizontal center */
            align-items: center;     /* Vertical center */
        }
        
    </style>
</head>
<body class="bg-light">
    <div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Session's Tutor, Date & Time Allocation</h4>
                </div>
                    <form method="POST">
                        <div class="card p-3 mb-4 shadow-sm border-0">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="small fw-bold">Program</label>
                                    <select name="program_code" id="program" class="form-control select2">
                                        <option value="">Select Program</option>
                                        <?php while($p = $programs->fetch_assoc()){ echo "<option value='{$p['program_code']}'>{$p['program_name']}</option>"; } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Batch</label>
                                    <select name="batch_id" id="batch" class="form-control select2"></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold">Tutor</label>
                                    <select name="tutor_id" id="tutor" class="form-control select2">
                                        <option value="">Select Tutor</option>
                                        <?php while($t = $tutor_query->fetch_assoc()){ echo "<option value='{$t['id']}'>{$t['username']}</option>"; } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mb-4 border-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="sessionTable">
                                    <thead class="table-dark">
                                        <tr><th>Session Name</th><th>Type</th><th class="text-center">Allocate</th><th>Start Date</th><th>End Date</th></tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <button type="submit" name="save_allocation" class="btn btn-primary px-4 shadow-sm">Save Allocation</button>
                    </form>

                    <div class="card mt-5 border-0 shadow-sm">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0 text-dark">Schedule Overview</h6>
                            <div class="d-flex gap-2">
                                <select id="calMonth" class="form-select form-select-sm border-light bg-light" style="width:130px;">
                                    <?php for($m=1; $m<=12; $m++) echo "<option value='$m' ".(date('m')==$m?'selected':'').">".date('F', mktime(0,0,0,$m,1))."</option>"; ?>
                                </select>
                                <select id="calYear" class="form-select form-select-sm border-light bg-light" style="width:100px;">
                                    <?php for($y=2024; $y<=2027; $y++) echo "<option value='$y' ".($y==date('Y')?'selected':'').">$y</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="calendar-header-days">
                            <div>SUN</div><div>MON</div><div>TUE</div><div>WED</div><div>THU</div><div>FRI</div><div>SAT</div>
                        </div>
                        <div id="calendarGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
        
        const batches = <?php echo json_encode($batch_list); ?>;

        $('#program').change(function() {
            const pid = $(this).val();
            const bSelect = $('#batch');
            bSelect.empty().append('<option value="">Select Batch</option>');
            if(batches[pid]) {
                batches[pid].forEach(b => { bSelect.append(`<option value="${b.id}">${b.batch_name}</option>`); });
            }
        });

        $('#tutor, #batch').change(function() {
            const p = $('#program').val();
            const t = $('#tutor').val();
            const b = $('#batch').val();
            if(!p || !t || !b) return;

            $.post('tutor_session.php', { action: 'get_sessions', program_code: p, tutor_id: t, batch_id: b }, function(data) {
                let html = '';
                data.forEach(row => {
                    const check = row.allocated == 1 ? 'checked' : '';
                    const hide = row.allocated == 1 ? '' : 'd-none';
                    html += `<tr>
                        <td class="align-middle small fw-bold tablerow">${row.session_name}</td>
                        <td class="align-middle small text-muted">${row.session_type}</td>
                        <td class="text-center align-middle"><input type="checkbox" name="allocated[]" value="${row.session_id}" class="form-check-input allocateSwitch" ${check}></td>
                        <td><input type="date" name="start_date[${row.session_id}]" class="form-control form-control-sm start-date ${hide}" value="${row.session_start_date || ''}"></td>
                        <td><input type="date" name="end_date[${row.session_id}]" class="form-control form-control-sm end-date ${hide}" value="${row.session_end_date || ''}"></td>
                    </tr>`;
                });
                $('#sessionTable tbody').html(html);
            }, 'json');
        });

        $(document).on('change', '.allocateSwitch', function() {
            $(this).closest('tr').find('input[type="date"]').toggleClass('d-none', !this.checked);
        });

        function loadGrid() {
            $.post('tutor_session.php', { 
                action: 'get_calendar_grid', 
                month: $('#calMonth').val(), 
                year: $('#calYear').val() 
            }, function(data) {
                $('#calendarGrid').html(data);
            });
        }

        loadGrid();
        $('#calMonth, #calYear').change(loadGrid);
    });
    </script>
</body>
</html>