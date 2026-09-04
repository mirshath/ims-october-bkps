<?php
include("../database/connection.php");

// Check if the ID is set
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Prepare the delete statement
    $stmt = $conn->prepare("DELETE FROM allocated_components WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();

    // Return a success message
    echo "Record deleted successfully!";
}
?>
