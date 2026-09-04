<?php
session_start();
include("./database/connection.php");

// Get the program ID from the AJAX request
$program_id = $_POST['programme_id'];
$registration_code = $_POST['student_registration_id']; // Retrieve the student registration code

// --------------------------
// new updated code checking 



// Fetch the compulsory_sub data from allocate_program table for the given program_id
$allocate_program_query = "
    SELECT 
        * 
    FROM 
        allocate_programme 
    WHERE 
        programme_code  = ? AND student_registration_id = ? AND status = 'active'";

$allocate_stmt = $conn->prepare($allocate_program_query);
// $allocate_stmt->bind_param("ss", $program_id,$registration_code);
$allocate_stmt->bind_param("ss", $program_id, $registration_code); // Ensure registration_code is treated as a string
$allocate_stmt->execute();
$allocate_result = $allocate_stmt->get_result();
$allocated_compulsory_subs = [];

if ($allocate_result->num_rows > 0) {
    $allocate_row = $allocate_result->fetch_assoc();
    // Assuming compulsory_sub is a comma-separated list of module names
    // $allocated_compulsory_subs = explode(',', $allocate_row['compulsory_sub']);
    $allocated_compulsory_subs = array_map('trim', explode(',', $allocate_row['compulsory_sub']));
}

$allocate_stmt->close();

// SQL query to select all modules associated with the program (Compulsory)
$select_Modules = "
    SELECT 
        modules.* 
    FROM 
        modules 
    INNER JOIN 
        program_table ON modules.programme_id = program_table.program_code 
    WHERE 
        modules.type = 'Compulsory' AND
        modules.programme_id = ?";

// Prepare the statement
$stmt = $conn->prepare($select_Modules);
$stmt->bind_param("s", $program_id); // Bind the parameter
$stmt->execute();
$result = $stmt->get_result();

// Prepare output for Compulsory Modules
$output = "";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_checked = in_array($row['module_name'], $allocated_compulsory_subs) ? "checked" : ""; // Check if module is in compulsory_sub

        // Output the checkbox, mark as checked if module is part of compulsory_sub
        $output .= "<div class='form-check'>
                        <input class='form-check-input' type='checkbox' name='modules[]' value='" . htmlspecialchars($row['module_name']) . "' $is_checked>
                        <label class='form-check-label'>" . htmlspecialchars($row['module_name']) . " (" . htmlspecialchars($row['type']) . ")</label>
                    </div>";
    }
} else {
    $output .= "No compulsory modules found for this program.";
}



// ------------------------------------------ 
// new 1 

// $program_id = $_POST['programme_id'];
// $registration_code = $_POST['student_registration_id']; // Retrieve the student registration code

// Fetch the elective_subs data from allocate_program table for the given program_id and student_registration_id
$allocate_program_query_elective = "
    SELECT 
        * 
    FROM 
        allocate_programme 
    WHERE 
        programme_code  = ? AND student_registration_id = ? AND status = 'active'"; // Add student_registration_id and status conditions

$allocate_stmt_elective = $conn->prepare($allocate_program_query_elective);
$allocate_stmt_elective->bind_param("ss", $program_id, $registration_code); // Bind both programme_code and student_registration_id
$allocate_stmt_elective->execute();
$allocate_result_elective = $allocate_stmt_elective->get_result();
$allocated_elective_subs = [];

if ($allocate_result_elective->num_rows > 0) {
    $allocate_row_elective = $allocate_result_elective->fetch_assoc();
    // Assuming elective_subs is a comma-separated list of module names
    // $allocated_elective_subs = explode(',', $allocate_row_elective['elective_subs']);
     $allocated_elective_subs = array_map('trim', explode(',', $allocate_row_elective['elective_subs']));
}

$allocate_stmt_elective->close();

// SQL query to select all Elective modules associated with the program
$select_modules_elective = "
    SELECT 
        modules.* 
    FROM 
        modules 
    INNER JOIN 
        program_table ON modules.programme_id = program_table.program_code 
    WHERE 
        modules.type = 'Elective' AND
        modules.programme_id = ?";

// Prepare the statement for Elective Modules
$stmt = $conn->prepare($select_modules_elective);
$stmt->bind_param("s", $program_id); // Bind the programme_id parameter
$stmt->execute();
$result_elective = $stmt->get_result();

// Prepare output for Elective Modules
$output_elective = "";
if ($result_elective->num_rows > 0) {
    while ($row = $result_elective->fetch_assoc()) {
        $is_checked = in_array($row['module_name'], $allocated_elective_subs) ? "checked" : ""; // Check if module is in elective_subs

        // Output the checkbox, mark as checked if module is part of elective_subs
        $output_elective .= "<div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='modules_elective[]' value='" . htmlspecialchars($row['module_name']) . "' $is_checked>
                                <label class='form-check-label'>" . htmlspecialchars($row['module_name']) . " (" . htmlspecialchars($row['type']) . ")</label>
                             </div>";
    }
} else {
    $output_elective .= "No elective modules found for this program.";
}


// ------------------------ 
// Close the statement
$stmt->close();

// Return the outputs as a JSON response
echo json_encode([
    'compulsory' => $output,
    'elective' => $output_elective
]);
