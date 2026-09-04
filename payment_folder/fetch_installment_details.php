<?php
    // Include your database connection
;
include("../database/connection.php");
// Check if student_id is set
if (isset($_POST['student_id']) && isset($_POST['program_batch'])) {
    $student_id = $_POST['student_id'];
    $program_batch = $_POST['program_batch']; // Get the program_batch from POST data

    // Prepare SQL query to fetch installment details for the student and program_batch
    $sql = "SELECT * FROM installment_details_table WHERE student_id = ? AND programme_batch = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $student_id, $program_batch); // Bind both student_id and program_batch to the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there are any results
    if ($result->num_rows > 0) {
        $installment_details = [];

        // Fetch data and store it in an array
        while ($row = $result->fetch_assoc()) {
            $installment_details[] = [
                'row_id' => $row['id'],
                'installment_numbers' => $row['installment_numbers'],
                'installment_amount' => $row['installment_amount'],
                'due_date' => $row['due_date'],
                'remark' => $row['remark']
            ];
        }

        // Return the data as JSON
        echo json_encode(['success' => true, 'installment_details' => $installment_details]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No installment details found.']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Student ID not provided.']);
}

$conn->close();
