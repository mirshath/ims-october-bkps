<?php
include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $sub_component_name = mysqli_real_escape_string($conn, $_POST['sub_component_name']);

    if (!empty($sub_component_name)) {
        $sql = "UPDATE sub_assign_components SET sub_component_name = '$sub_component_name' WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            echo "Sub-Component updated successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Please fill all fields!";
    }
}
?>
