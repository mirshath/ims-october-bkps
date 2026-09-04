<?php
include '../database/connection.php';

$response = ['success' => false];

if (isset($_POST['program_code'], $_POST['batch_name'])) {
    $program_code = $_POST['program_code'];
    $batch_name = $_POST['batch_name'];

    // Join batch_table with program_table to get prog_code
    $query = "
        SELECT 
            bt.batch_no, bt.intake_no, bt.year_no, 
            pt.prog_code 
        FROM 
            batch_table bt
        INNER JOIN 
            program_table pt ON bt.programme = pt.program_code
        WHERE 
            bt.programme = ? AND bt.batch_name = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $program_code, $batch_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['prog_code'] = $row['prog_code']; // Now fetched from program_table
        $response['batch_data'] = [
            'batch_no' => $row['batch_no'],
            'intake_no' => $row['intake_no'],
            'year_no' => $row['year_no']
        ];
    }
}

echo json_encode($response);
?>
