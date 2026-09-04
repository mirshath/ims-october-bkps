<?php
include("database/connection.php"); // Ensure the correct path to your database connection script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if 'students' data is available in the POST request
    if (isset($_POST['students']) && is_array($_POST['students'])) {
        // Loop through the student data
        foreach ($_POST['students'] as $student_data) {
            $student_id = $student_data['student_id'];
            $program_id = $_POST['program_id'];
            $batch_id = $_POST['batch_id'];
            $module_id = $_POST['module_id'];
            $main_comp_id = $_POST['main_component_id'];
            $sub_component_id = $_POST['sub_component_id'] ?? NULL; // Handle optional sub component

            // Loop through the questions for each student
            foreach ($student_data['questions'] as $question_no => $marks) {
                $examiner1_marks = $marks['examiner1'] ?? NULL;
                $examiner2_marks = $marks['examiner2'] ?? NULL; // Handle optional second examiner
                $final_marks = $marks['final'] ?? NULL; // Make sure final marks are also considered

                // Prepare the SQL query to insert data into the database
                $query = "INSERT INTO bbm_result_details (student_id, program_id, batch_id, module_id, main_comp_id, sub_component_id, question_no, examiner1_marks, examiner2_marks, final_marks, status)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiiiiiidd", $student_id, $program_id, $batch_id, $module_id, $main_comp_id, $sub_component_id, $question_no, $examiner1_marks, $examiner2_marks, $final_marks);

                // Execute the query
                if (!$stmt->execute()) {
                    echo "Error: " . $stmt->error;
                    exit();
                }
            }
        }
        echo "Results saved successfully!";
    } else {
        echo "No student data received.";
    }
}
?>
