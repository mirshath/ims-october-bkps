<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Ensure this path is correct for PHPMailer
include("../database/connection.php");

header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email_data'], $data['programme_id'], $data['batch_id'])) {
    echo json_encode(["status" => "error", "message" => "Invalid request data"]);
    exit;
}

$programme_id = mysqli_real_escape_string($conn, $data['programme_id']);
$batch_id = mysqli_real_escape_string($conn, $data['batch_id']);
$emailData = $data['email_data'];

$response = [];

foreach ($emailData as $entry) {
    // $full_name = mysqli_real_escape_string($conn, $entry['full_name']);
    // $email = mysqli_real_escape_string($conn, $entry['email']);
    // $student_id = mysqli_real_escape_string($conn, $entry['student_id']);

    // // Fetch program name and batch name
    // $programme_query = "SELECT program_name FROM program_table WHERE program_code = '$programme_id'";
    // $batch_query = "SELECT batch_name FROM batch_table WHERE id = '$batch_id'";

    // $programme_result = mysqli_query($conn, $programme_query);
    // $batch_result = mysqli_query($conn, $batch_query);

    // $programme_name = $programme_result && mysqli_num_rows($programme_result) > 0 ? mysqli_fetch_assoc($programme_result)['program_name'] : 'Unknown Program';
    // $batch_name = $batch_result && mysqli_num_rows($batch_result) > 0 ? mysqli_fetch_assoc($batch_result)['batch_name'] : 'Unknown Batch';

    // // Fetch results from final_student_results table and join program_table and batch_table
    // if ($programme_id == 47) {
    //     // For BBM, fetch from bbm_final_results_tbl
    //     $query = "SELECT 
    //                 m.module_name, 
    //                 bfr.final_result 
    //               FROM bbm_final_results_tbl bfr
    //               JOIN modules m ON bfr.module_id = m.id
    //               WHERE bfr.student_id = '$student_id' 
    //                 AND bfr.program_id = '$programme_id' 
    //                 AND bfr.batch_id = '$batch_id'";
    // } else {
    //     // For other programmes, fetch from final_student_results
    //     $query = "SELECT 
    //                 m.module_name, 
    //                 fsr.final_result 
    //               FROM final_student_results fsr
    //               JOIN modules m ON fsr.module_id = m.id
    //               WHERE fsr.student_registration_id = '$student_id' 
    //                 AND fsr.program_id = '$programme_id' 
    //                 AND fsr.batch_id = '$batch_id'";
    // }




    // ------------------------------------------------------------------ 
    $full_name = mysqli_real_escape_string($conn, $entry['full_name']);
    $email = mysqli_real_escape_string($conn, $entry['email']);
    $student_id = mysqli_real_escape_string($conn, $entry['student_id']);

    // Fetch program name and batch name
    $programme_query = "SELECT program_name FROM program_table WHERE program_code = '$programme_id'";
    $batch_query = "SELECT batch_name FROM batch_table WHERE id = '$batch_id'";

    $programme_result = mysqli_query($conn, $programme_query);
    $batch_result = mysqli_query($conn, $batch_query);

    $programme_name = $programme_result && mysqli_num_rows($programme_result) > 0 ? mysqli_fetch_assoc($programme_result)['program_name'] : 'Unknown Program';
    $batch_name = $batch_result && mysqli_num_rows($batch_result) > 0 ? mysqli_fetch_assoc($batch_result)['batch_name'] : 'Unknown Batch';

    // Fetch results from final_student_results table and join program_table and batch_table
    if ($programme_id == 47) {
        // For BBM, fetch from bbm_final_results_tbl
        // IMPORTANT: First get the numeric student_id from the registration_id
        $student_query = "SELECT student_code FROM allocate_programme WHERE student_registration_id = '$student_id' AND programme_code = '$programme_id' AND batch_id = '$batch_id'";
        $student_result = mysqli_query($conn, $student_query);

        if ($student_result && mysqli_num_rows($student_result) > 0) {
            $numeric_student_id = mysqli_fetch_assoc($student_result)['student_code'];

            $query = "SELECT 
                        m.module_name, 
                        bfr.final_result 
                      FROM bbm_final_results_tbl bfr
                      JOIN modules m ON bfr.module_id = m.id
                      WHERE bfr.student_id = '$numeric_student_id' 
                        AND bfr.program_id = '$programme_id' 
                        AND bfr.batch_id = '$batch_id'";
        } else {
            // Fallback if student not found
            $query = "SELECT 1 WHERE 0"; // Empty result set
        }
    } else {
        // For other programmes, fetch from final_student_results
        $query = "SELECT 
                    m.module_name, 
                    fsr.final_result 
                  FROM final_student_results fsr
                  JOIN modules m ON fsr.module_id = m.id
                  WHERE fsr.student_registration_id = '$student_id' 
                    AND fsr.program_id = '$programme_id' 
                    AND fsr.batch_id = '$batch_id'";
    }
    // ------------------------------------------------------------------ 


    $query_result = mysqli_query($conn, $query);

    $emailContent = "<h3>Dear $full_name,</h3>";
    $emailContent .= "<p>Here are your results for: </p>";
    $emailContent .= "<p>Full Name: <b>$full_name</b>,<br>BMS_ID: <b>$student_id</b> <br> Programme: <b>$programme_name</b> <br> Batch: <b>$batch_name</b></p>";
    $emailContent .= "<table class='table-hover table-striped' border='1' cellpadding='5' cellspacing='0'>";
    $emailContent .= "<tr><th>Module Name</th><th>Result</th></tr>";

    if ($query_result && mysqli_num_rows($query_result) > 0) {
        while ($row = mysqli_fetch_assoc($query_result)) {
            $emailContent .= "<tr><td>{$row['module_name']}</td><td>{$row['final_result']}</td></tr>";
        }
        $emailContent .= "</table>";
    } else {
        $emailContent .= "<tr><td colspan='2'>No results found</td></tr></table>";
    }

    $emailContent .= "<p>Best Regards,<br>BMS Team</p>";


    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    try {
        // $mail->isSMTP();
        // $mail->Host = 'mail.graduatejob.lk'; // SMTP server
        // $mail->SMTPAuth = true;
        // $mail->Username = 'noreply@graduatejob.lk'; // SMTP email
        // $mail->Password = 'Hasni@2024'; // SMTP password
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port = 587;

        // $mail->setFrom('noreply@graduatejob.lk', 'Business Management School');

        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
        $mail->Password = 'Lox51527'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to


        //Recipients
        $mail->setFrom('noreply@bms.ac.lk', 'Business Management School'); // Replace with your email and name
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Your Program Results Module-wise";
        $mail->Body    = $emailContent;

        if ($mail->send()) {
            $response[] = ["email" => $email, "status" => "success", "message" => "Email sent"];
        } else {
            $response[] = ["email" => $email, "status" => "error", "message" => "Email failed"];
        }
    } catch (Exception $e) {
        $response[] = ["email" => $email, "status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo];
    }
}

// Ensure clean JSON output
ob_clean();
echo json_encode(["status" => "completed", "results" => $response]);
exit;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>