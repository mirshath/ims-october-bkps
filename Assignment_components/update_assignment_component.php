<?php
include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $assessment_code = mysqli_real_escape_string($conn, $_POST['assessment_code']);
    $as_main_component_name = mysqli_real_escape_string($conn, $_POST['as_main_component_name']);

    if (!empty($assessment_code) && !empty($as_main_component_name)) {
        $sql = "UPDATE assignment_components SET assessment_code = '$assessment_code', as_main_component_name = '$as_main_component_name' WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            echo "Main Component updated successfully!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Please fill all fields!";
    }
}
?>
