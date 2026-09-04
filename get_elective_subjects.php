<?php

// include("./database/connection.php");

// Fetch elective subjects based on the program name
// if (isset($_POST['programme'])) {
//     $programName = $_POST['programme'];

//     // Fetch elective subjects based on the selected program
//     $sql = "SELECT * FROM modules WHERE program_name = '$programName' AND type = 'Elective'";
//     $result = $conn->query($sql);

//     if ($result->num_rows > 0) {
//         // Iterate through the results and display each module as a checkbox
//         while ($row = $result->fetch_assoc()) {
//             $moduleName = $row['module_name']; // Assuming 'module_name' is the field for the module's name

//             // Create a checkbox for each elective module
//             echo '<div class="form-check">';
//             echo '<input class="form-check-input" type="checkbox" name="elective_subjects[]" value="' . $moduleName . '" id="module_' . $moduleName . '">';
//             echo '<label class="form-check-label" for="module_' . $moduleName . '">' . $moduleName . '</label>';
//             echo '</div>';
//         }
//     } else {
//         echo 'No elective subjects found for this program.';
//     }

//     $conn->close();
// }
?>
