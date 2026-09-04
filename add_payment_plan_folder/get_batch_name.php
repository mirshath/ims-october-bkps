<?php
include '../database/connection.php'; // Adjust path as needed

$response = ['success' => false];

if (isset($_POST['batch_id'])) {
    $batch_id = intval($_POST['batch_id']);

    $query = "SELECT batch_name FROM batch_table WHERE id = $batch_id";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        $response['success'] = true;
        $response['batch_name'] = $row['batch_name'];
    }
}

echo json_encode($response);


