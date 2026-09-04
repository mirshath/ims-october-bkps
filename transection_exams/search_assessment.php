<?php
include("../database/connection.php");

// Get search parameters
$programme_id = $_POST['programme_id'] ?? '';
$batch_id = $_POST['batch_id'] ?? '';
$module_id = $_POST['module_id'] ?? '';
$year_id = $_POST['year_id'] ?? '';
$semester_id = $_POST['semester_id'] ?? '';
$main_component_id = $_POST['main_component_id'] ?? '';
$sub_component_id = $_POST['sub_component_id'] ?? '';

// Build query with JOIN to get component names
// $query = "SELECT a.*, 
//           ac.as_main_component_name as main_component_name,
//           sac.sub_component_name as sub_component_name
//           FROM assessments a
//           LEFT JOIN assignment_components ac ON a.main_component_id = ac.id
//           LEFT JOIN sub_assign_components sac ON a.sub_component_id = sac.id
//           WHERE 1=1";

$query = "SELECT a.*, 
          ac.as_main_component_name as main_component_name,
          sac.sub_component_name as sub_component_name,
          p.program_name as programme_name,
          b.batch_name as batch_name,
          m.module_name as module_name
          FROM assessments a
          LEFT JOIN assignment_components ac ON a.main_component_id = ac.id
          LEFT JOIN sub_assign_components sac ON a.sub_component_id = sac.id
          LEFT JOIN program_table p ON a.programme_id = p.program_code
          LEFT JOIN batch_table b ON a.batch_id = b.id
          LEFT JOIN modules m ON a.module_id = m.id
          WHERE 1=1";


if (!empty($programme_id)) {
    $query .= " AND a.programme_id = " . intval($programme_id);
}
if (!empty($batch_id)) {
    $query .= " AND a.batch_id = " . intval($batch_id);
}
if (!empty($module_id)) {
    $query .= " AND a.module_id = " . intval($module_id);
}
if (!empty($year_id)) {
    $query .= " AND a.year_id = '" . mysqli_real_escape_string($conn, $year_id) . "'";
}
if (!empty($semester_id)) {
    $query .= " AND a.semester_id = '" . mysqli_real_escape_string($conn, $semester_id) . "'";
}
if (!empty($main_component_id)) {
    $query .= " AND a.main_component_id = " . intval($main_component_id);
}
if (!empty($sub_component_id)) {
    $query .= " AND a.sub_component_id = " . intval($sub_component_id);
}

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $assessments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format attachments
        $attachments = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($row["attachment_$i"])) {
                $attachments[] = $row["attachment_$i"];
            }
        }
        $row['attachments'] = $attachments;
        $assessments[] = $row;
    }
    echo json_encode(['success' => true, 'assessments' => $assessments]);
} else {
    echo json_encode(['success' => false, 'message' => 'No matching assessment found']);
}

mysqli_close($conn);
