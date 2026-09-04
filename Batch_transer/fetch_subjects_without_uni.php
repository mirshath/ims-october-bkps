<?php
// include("../database/connection.php");

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $program_id = $_POST['program_id'];
//     $university_id = $_POST['university_id'];

//     // Fetch compulsory subjects
//     $compulsoryQuery = "SELECT module_name FROM modules WHERE programme_id = ? AND type = 'Compulsory'";
//     $stmt = $conn->prepare($compulsoryQuery);
//     $stmt->bind_param("i", $program_id);
//     $stmt->execute();
//     $compulsoryResult = $stmt->get_result();
//     $compulsorySubjects = [];
//     while ($row = $compulsoryResult->fetch_assoc()) {
//         $compulsorySubjects[] = $row['module_name'];
//     }



//     // Return the subjects as JSON
//     echo json_encode([
//         'compulsory' => $compulsorySubjects,

//     ]);
// }


// ------------



include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only use program_id, not university_id, to align with JS at programProgression.php:1226-1251
    $program_id = isset($_POST['program_id']) ? $_POST['program_id'] : null;

    $compulsorySubjects = [];

    if ($program_id) {
        // Fetch compulsory subjects for the program only (ignore university)
        $compulsoryQuery = "SELECT module_name FROM modules WHERE programme_id = ? AND type = 'Compulsory'";
        $stmt = $conn->prepare($compulsoryQuery);
        $stmt->bind_param("i", $program_id);
        $stmt->execute();
        $compulsoryResult = $stmt->get_result();
        while ($row = $compulsoryResult->fetch_assoc()) {
            $compulsorySubjects[] = $row['module_name'];
        }
    }
    // Return compulsory subjects as JSON even if empty (to keep JS robust)
    echo json_encode([
        'compulsory' => $compulsorySubjects
    ]);
}
