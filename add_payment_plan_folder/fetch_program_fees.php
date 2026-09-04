<?php
// Database connection
include '../database/connection.php';

if (isset($_GET['program_code'])) {
    $program_code = mysqli_real_escape_string($conn, $_GET['program_code']);

    // Fetch program fees from program_table
    $sql = "SELECT * FROM program_table WHERE program_code = '$program_code' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Return the program fees
        $response = array(
            'success' => true,
            'program_code' => $row['program_code'],
            'program_name' => $row['program_name'],
            // Program fees
            'course_fee_lkr' => floatval($row['course_fee_lkr'] ?? 0),      // For Course Fee section
            'course_fee_gbp' => floatval($row['course_fee_gbp'] ?? 0),      // For University Fee section
            'course_fee_usd' => floatval($row['course_fee_usd'] ?? 0),      // For University Fee section
            'course_fee_euro' => floatval($row['course_fee_euro'] ?? 0)     // For University Fee section
        );
        echo json_encode($response);
    } else {
        echo json_encode(array('success' => false, 'message' => 'No program found'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => 'No program code provided'));
}
?>
