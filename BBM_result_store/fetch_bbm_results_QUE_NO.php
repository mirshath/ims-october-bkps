<?php


include('../database/connection.php');

// Decode the JSON data from the AJAX request
$data = json_decode(file_get_contents('php://input'), true);

// Extract data from the request
$programmeId = $data['programmeId'];
$batchId = $data['batchId'];
$moduleId = $data['moduleId'];
$mainComponentId = $data['mainComponentId'];
$matchedColumn = $data['matchedColumn'];
$selectedValue = $data['selectedValue'];
$studentResults = $data['student_Results']; // Array of all students' results

// Prepare an array to hold all student data to return
$responseData = [];

// Extract all unique student codes to query only once
$studentCodes = [];
foreach ($studentResults as $studentResult) {
    $studentCode = $studentResult['studentCode'];
    if (!in_array($studentCode, $studentCodes)) {
        $studentCodes[] = $studentCode;
    }
}

// Create a placeholder for student codes (to be used in SQL IN clause)
$placeholders = implode(',', array_fill(0, count($studentCodes), '?'));

// Prepare SQL based on conditions
if ($matchedColumn == 'examinor_1' && $selectedValue == 'no') {
    // Query for examiner 1
    $sql = "
        SELECT `student_id`, `100marksEx1`, `examiner1_marks`
        FROM `bbm_result_details`
        WHERE `student_id` IN ($placeholders) 
        AND `program_id` = ? 
        AND `batch_id` = ? 
        AND `module_id` = ? 
        AND `main_comp_id` = ? 
        AND `with_Ques` = 'no' 
    ";
} elseif ($matchedColumn == 'examinor_2' && $selectedValue == 'no') {
    // Query for examiner 2
    $sql = "
        SELECT `student_id`, `100marksEx2`, `examiner2_marks`
        FROM `bbm_result_details`
        WHERE `student_id` IN ($placeholders) 
        AND `program_id` = ? 
        AND `batch_id` = ? 
        AND `module_id` = ? 
        AND `main_comp_id` = ? 
        AND `with_Ques` = 'no'
    ";
} else {
    $responseData[] = ["error" => "No matching criteria found for the selected column"];
    echo json_encode($responseData);
    exit;
}

// Prepare the database query
$stmt = $conn->prepare($sql);

// Check if the statement was prepared correctly
if ($stmt === false) {
    $responseData[] = ["error" => "Failed to prepare the SQL statement"];
    echo json_encode($responseData);
    exit;
}

// Bind parameters: student codes + other parameters
$params = array_merge($studentCodes, [$programmeId, $batchId, $moduleId, $mainComponentId]);

// Dynamically bind the parameters using call_user_func_array
$types = str_repeat('i', count($studentCodes)) . 'iiii';  // e.g., 'iiiii' for the other params
$stmt->bind_param($types, ...$params);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Loop through the result set and match with student codes
while ($row = $result->fetch_assoc()) {
    $studentCode = $row['student_id']; // Assuming student_id in result is the student code

    // Find the student from the original request array to match and add the results
    foreach ($studentResults as $studentResult) {
        if ($studentResult['studentCode'] == $studentCode) {
            if ($matchedColumn == 'examinor_1' && $selectedValue == 'no') {
                $responseData[] = [
                    "student_id" => $studentCode,
                    "marks100" => $row['100marksEx1'],
                    "examiner_marks" => $row['examiner1_marks']
                ];
            } elseif ($matchedColumn == 'examinor_2' && $selectedValue == 'no') {
                $responseData[] = [
                    "student_id" => $studentCode,
                    "marks100" => $row['100marksEx2'],
                    "examiner_marks" => $row['examiner2_marks']
                ];
            }
        }
    }
}

// Return all the results for students
echo json_encode($responseData);

// Close the statement
$stmt->close();
