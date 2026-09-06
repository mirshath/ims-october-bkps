<?php
include '../database/connection.php';

$response = [
    'success' => false,
    'student_reg_ids' => [],
    'new_student_reg_ids' => [],
    'last_entered_id' => null,
    'last_new_entered_id' => null
];

if (isset($_POST['program_code'], $_POST['batch_id'])) {
    $program_code = $_POST['program_code'];
    $batch_id = $_POST['batch_id'];

    // ------------------------------
    // 1. Fetch student_registration_id (old IDs)
    // ------------------------------
    $query1 = "SELECT student_registration_id 
               FROM allocate_programme 
               WHERE programme_code = ? 
                 AND batch_id = ? 
                 AND student_registration_id IS NOT NULL 
                 AND student_registration_id != '' 
               ORDER BY student_registration_id DESC";

    $stmt1 = $conn->prepare($query1);
    $stmt1->bind_param("si", $program_code, $batch_id);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    $reg_ids = [];
    while ($row = $result1->fetch_assoc()) {
        $reg_ids[] = $row['student_registration_id'];
    }

    // ------------------------------
    // 2. Fetch new_student_registration_id (continuous, ignore smaller manual changes)
    // ------------------------------
    $query2 = "SELECT new_student_registration_id 
               FROM allocate_programme 
               WHERE new_student_registration_id IS NOT NULL 
                 AND new_student_registration_id != '' 
               ORDER BY id DESC";

    $result2 = $conn->query($query2);

    $new_reg_ids = [];
    $maxSuffix = 0;
    $matched_new_id = null;

    while ($row = $result2->fetch_assoc()) {
        $id = $row['new_student_registration_id'];
        $suffix = intval(substr($id, -6)); // last 6 digits as number

        // Skip invalid suffix (smaller than max found)
        if ($suffix <= $maxSuffix) continue;

        $maxSuffix = $suffix;
        $matched_new_id = $id;
        $new_reg_ids[] = $id;
    }

    // ------------------------------
    // 3. Handle empty results
    // ------------------------------
    if (empty($reg_ids)) $reg_ids[] = null;
    if (empty($new_reg_ids)) {
        $new_reg_ids = [];
        $matched_new_id = null;
    }

    // ------------------------------
    // 4. Prepare response
    // ------------------------------
    $response['last_entered_id'] = $reg_ids[0] ?? null;          // latest old ID
    $response['last_new_entered_id'] = $matched_new_id;          // latest valid continuous ID
    $response['student_reg_ids'] = array_reverse($reg_ids);
    $response['new_student_reg_ids'] = array_reverse($new_reg_ids);
    $response['success'] = true;
}

echo json_encode($response);
