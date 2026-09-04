<?php
// include("../database/connection.php");

// if (isset($_GET['student_code'])) {
//     $student_code = $_GET['student_code'];
    
//     $sql = "SELECT * FROM allocate_programme WHERE student_code = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("i", $student_code);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows > 0) {
//         $row = $result->fetch_assoc();
//         echo json_encode($row);
//     } else {
//         echo json_encode([]);
//     }

//     $stmt->close();
// }
// $conn->close();
?>
