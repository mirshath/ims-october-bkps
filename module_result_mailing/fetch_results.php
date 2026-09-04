<?php

include("../database/connection.php");

$programme_id = $_POST['programme_id'] ?? null;
$batch_id = $_POST['batch_id'] ?? null;

if ($programme_id == 47 && $batch_id) {
    // 1. Get all allocated students for this program and batch
    $student_query = "SELECT 
                        ap.student_code, 
                        ap.student_registration_id, 
                        s.first_name, 
                        s.last_name, 
                        s.personal_email, 
                        s.bms_email
                      FROM allocate_programme ap
                      JOIN students s ON ap.student_code = s.student_code
                      WHERE ap.programme_code = '$programme_id' 
                        AND ap.batch_id = '$batch_id'
                        AND ap.status = 'active'";

    $student_result = mysqli_query($conn, $student_query);

    $students = [];
    while ($row = mysqli_fetch_assoc($student_result)) {
        $students[$row['student_code']] = [
            'student_id' => $row['student_code'],
            'student_registration_id' => $row['student_registration_id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'personal_email' => $row['personal_email'],
            'bms_email' => $row['bms_email'],
            'modules' => []
        ];
    }

    // 2. Get all modules for this program
    $module_query = "SELECT id, module_name FROM modules WHERE programme_id = '$programme_id'";
    $module_result = mysqli_query($conn, $module_query);

    $modules = [];
    while ($row = mysqli_fetch_assoc($module_result)) {
        $modules[$row['id']] = $row['module_name'];
    }

    // 3. Get results for each student and module (from bbm_final_results_tbl)
    $results_query = "SELECT 
                        student_id, 
                        module_id, 
                        final_result 
                      FROM bbm_final_results_tbl 
                      WHERE program_id = '$programme_id' 
                        AND batch_id = '$batch_id'";
    $results_result = mysqli_query($conn, $results_query);

    $results = [];
    while ($row = mysqli_fetch_assoc($results_result)) {
        $results[$row['student_id']][$row['module_id']] = $row['final_result'];
    }

    // 4. Attach modules (and results if available) to each student
    foreach ($students as $student_id => &$student) {
        foreach ($modules as $module_id => $module_name) {
            $student['modules'][] = [
                'module_name' => $module_name,
                'final_result' => isset($results[$student_id][$module_id]) ? $results[$student_id][$module_id] : null
            ];
        }
    }
    unset($student); // break reference

    // 5. Prepare the FLAT response (like the else block)
    $response = [];
    foreach ($students as $student) {
        $moduleCount = count($student['modules']);
        foreach ($student['modules'] as $index => $module) {
            if ($index == 0) {
                $response[] = [
                    'student_id' => $student['student_id'],
                    'student_registration_id' => $student['student_registration_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'personal_email' => $student['personal_email'],
                    'bms_email' => $student['bms_email'],
                    'module_name' => $module['module_name'],
                    'final_result' => $module['final_result'],
                    'module_count' => $moduleCount
                ];
            } else {
                $response[] = [
                    'module_name' => $module['module_name'],
                    'final_result' => $module['final_result'],
                    'module_count' => $moduleCount
                ];
            }
        }
    }

    echo json_encode($response);
} else if ($programme_id && $batch_id) {

    // Query to get student results with their modules and all student details
    $query = "SELECT 
                fr.student_id, 
                ap.student_registration_id, 
                fr.final_result,    
                m.module_name, 
                s.*
              FROM final_student_results fr
              JOIN modules m ON fr.module_id = m.id
              JOIN allocate_programme ap 
                  ON fr.student_id = ap.student_code 
                  AND fr.program_id = ap.programme_code 
                  AND fr.batch_id = ap.batch_id
              JOIN students s ON ap.student_code = s.student_code
              WHERE fr.program_id = '$programme_id' 
                AND fr.batch_id = '$batch_id'
                AND ap.status = 'active'";

    $result = mysqli_query($conn, $query);

    $students = [];

    // Loop through the query result and organize the data by student
    while ($row = mysqli_fetch_assoc($result)) {
        // If the student is not already in the $students array, add them
        if (!isset($students[$row['student_id']])) {
            $students[$row['student_id']] = [
                'student_id' => $row['student_id'],
                'student_registration_id' => $row['student_registration_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'personal_email' => $row['personal_email'],
                'bms_email' => $row['bms_email'],
                'modules' => []
            ];
        }

        // Add the module and result to the student's record
        $students[$row['student_id']]['modules'][] = [
            'module_name' => $row['module_name'],
            'final_result' => $row['final_result']
        ];
    }

    // Prepare the response
    $response = [];
    foreach ($students as $student) {
        // Get the number of modules for the student
        $moduleCount = count($student['modules']);
        foreach ($student['modules'] as $index => $module) {
            // For the first module, include the student ID and registration ID
            if ($index == 0) {
                $response[] = [
                    'student_id' => $student['student_id'],
                    'student_registration_id' => $student['student_registration_id'],
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'personal_email' => $student['personal_email'],
                    'bms_email' => $student['bms_email'],
                    'module_name' => $module['module_name'],
                    'final_result' => $module['final_result'],
                    'module_count' => $moduleCount
                ];
            } else {
                // For subsequent modules, omit the student ID and registration ID
                $response[] = [
                    'module_name' => $module['module_name'],
                    'final_result' => $module['final_result'],
                    'module_count' => $moduleCount
                ];
            }
        }
    }

    // Return the response as JSON
    echo json_encode($response);
}
