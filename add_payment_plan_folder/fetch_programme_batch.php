<?php
// Database connection
include '../database/connection.php';

if (isset($_GET['student_code'])) {
    $student_code = mysqli_real_escape_string($conn, $_GET['student_code']);

    // Fetch programme_code, batch_id, programme_name, batch_name, and program fees
    $sql = "
        SELECT ap.*, p.*, b.*
        FROM allocate_programme ap
        INNER JOIN program_table p ON ap.programme_code = p.program_code
        INNER JOIN batch_table b ON ap.batch_id = b.id
        WHERE ap.student_code = '$student_code' AND ap.status = 'active'
        LIMIT 1
    ";

    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Add the program fees to the response
        $response = array(
            'success' => true,
            'program_name' => $row['program_name'] ?? '',
            'batch_name' => $row['batch_name'] ?? '',
            'programme_code' => $row['programme_code'] ?? '',
            'batch_id' => $row['batch_id'] ?? '',
            // Program fees from program_table
            'program_fee_lkr' => floatval($row['course_fee_lkr'] ?? 0),      // For Course Fee section
            'program_fee_gbp' => floatval($row['course_fee_gbp'] ?? 0),      // For University Fee section
            'program_fee_usd' => floatval($row['course_fee_usd'] ?? 0),      // For University Fee section
            'program_fee_euro' => floatval($row['course_fee_euro'] ?? 0)     // For University Fee section
        );
        echo json_encode($response);
    } else {
        echo json_encode(array('success' => false, 'message' => 'No data found'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'No student code provided'));
}
?>
