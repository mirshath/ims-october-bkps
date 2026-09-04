<?php
include("../database/connection.php");

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Fetch the allocated component data based on the provided ID
    $result = $conn->query("SELECT * FROM allocated_components WHERE id = $id");

    if ($result->num_rows > 0) {
        // Fetch the data as an associative array
        $data = $result->fetch_assoc();

        // Return the data as a JSON response
        echo json_encode($data);
    } else {
        // If no data found, return an empty array
        echo json_encode([]);
    }
} else {
    // If the ID is not provided in the POST request, return an error
    echo json_encode(['error' => 'No ID provided']);
}
?>
