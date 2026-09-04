<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

include("../database/connection.php");

if (!isset($conn) || !$conn) {
    echo json_encode(['error' => true, 'message' => 'Database connection failed', 'data' => ['bms' => [], 'cgs' => []]]);
    exit;
}

$filter_date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');

// Get hall branch info
$hall_info = [];
$result = $conn->query("SELECT class_id, lectuerhallname, branch FROM classroom");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $hall_info[$row['class_id']] = ['name' => $row['lectuerhallname'], 'branch' => strtoupper(trim($row['branch']))];
    }
}

// Get programme names
$program_map = [];
$result = $conn->query("SELECT program_code, program_name FROM program_table");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $program_map[$row['program_code']] = $row['program_name'];
    }
}

// Get batch names
$batch_map = [];
$result = $conn->query("SELECT id, batch_name FROM batch_table");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $batch_map[$row['id']] = $row['batch_name'];
    }
}

$query = "SELECT id, hall_id, programme, batch, module, module_text, note, type, start_time, end_time, date, approve_status 
          FROM class_reservations 
          WHERE approve_status = 'Approved' AND date = '$filter_date' 
          ORDER BY start_time ASC";
$result = $conn->query($query);

$bms = [];
$cgs = [];

while ($row = $result->fetch_assoc()) {
    $hall = isset($hall_info[$row['hall_id']]) ? $hall_info[$row['hall_id']] : ['name' => $row['hall_id'], 'branch' => ''];
    $programme_name = isset($program_map[$row['programme']]) ? $program_map[$row['programme']] : $row['programme'];
    $batch_name = isset($batch_map[$row['batch']]) ? $batch_map[$row['batch']] : $row['batch'];
    $start_time = date('H:i', strtotime($row['start_time']));
    $end_time = date('H:i', strtotime($row['end_time']));
    
    // Get module_name, module_text, note
    $module_name = isset($row['module']) ? $row['module'] : '';
    $module_text = isset($row['module_text']) ? $row['module_text'] : '';
    $note = isset($row['note']) ? $row['note'] : '';
    
    // Get type from database
    $type = isset($row['type']) ? $row['type'] : '';
    
    // Build module display by combining all three
    $module_parts = [];
    if (!empty($module_name) && trim($module_name) != '') {
        $module_parts[] = trim($module_name);
    }
    if (!empty($module_text) && trim($module_text) != '') {
        $module_parts[] = trim($module_text);
    }
    if (!empty($note) && trim($note) != '') {
        $module_parts[] = trim($note);
    }
    $module_parts = array_unique($module_parts);
    $module_display = !empty($module_parts) ? implode(' | ', $module_parts) : '';
    
    $class_data = [
        'time_slot' => $start_time . ' - ' . $end_time,
        'programme_name' => $programme_name,
        'batch_name' => $batch_name,
        'module_display' => $module_display,
        'hall_name' => $hall['name'],
        'type' => $type
    ];
    
    if ($hall['branch'] == 'BMS') {
        $bms[] = $class_data;
    } elseif ($hall['branch'] == 'CGS') {
        $cgs[] = $class_data;
    } elseif (stripos($programme_name, 'BMS') !== false) {
        $bms[] = $class_data;
    } elseif (stripos($programme_name, 'CGS') !== false) {
        $cgs[] = $class_data;
    } else {
        $bms[] = $class_data;
    }
}

echo json_encode(['error' => false, 'data' => ['bms' => $bms, 'cgs' => $cgs], 'date' => $filter_date]);
$conn->close();
?>