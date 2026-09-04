<?php
include("../database/connection.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Adjust the path if necessary

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = $_POST['email'];
    $student_name = $_POST['student_name'];
    $assessment_id = $_POST['assessment_id']; // Get the assessment ID
    $as_main_component_name = $_POST['as_main_component_name']; // Get the assessment ID
    $sub_component_name = $_POST['sub_component_name']; // Get the assessment ID
    $program_name = $_POST['program_name']; // Get the assessment ID

    // Fetch attachments for the given assessment ID
    $attachmentQuery = "SELECT attachment_1, attachment_2, attachment_3, attachment_4 FROM assessments WHERE id = ?";
    $stmt = $conn->prepare($attachmentQuery);
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $stmt->bind_result($attachment1, $attachment2, $attachment3, $attachment4);
    $stmt->fetch();
    $stmt->close();

    // Get all non-empty attachments
    $attachments = [];
    if (!empty($attachment4)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment4; // Latest attachment
    }
    if (!empty($attachment3)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment3; // Second last
    }
    if (!empty($attachment2)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment2; // Third last
    }
    if (!empty($attachment1)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment1; // Oldest
    }

    $subject = "$program_name Assesment Alert";
    $message = "Hello $student_name,\nThis is an important Assesment email regarding your program and batch..\n\n $subject \n\nBest regards,\nBMS Team";

    $mail = new PHPMailer(true); // Create a new PHPMailer instance

    try {
        //Server settings
        // $mail->isSMTP(); // Set mailer to use SMTP
        // // $mail->Host = 'mail.graduatejob.lk'; // Specify main and backup SMTP servers
        // $mail->Host = 'smtp.office365.com';
        // $mail->SMTPAuth = true; // Enable SMTP authentication
        // $mail->Username = 'noreply@graduatejob.lk'; // SMTP username
        // $mail->Password = 'Hasni@2024'; // SMTP password
        // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        // $mail->Port = 587; // TCP port to connect to


        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
        $mail->Password = 'Lox51527'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to


        //Recipients
        $mail->setFrom('noreply@bms.ac.lk', 'Business Management School'); // Replace with your email and name
        $mail->addAddress($to, $student_name); // Add a recipient

        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // Attach files
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment); // Attach the file
            }
        }

        $mail->send();
        echo "Email sent successfully to $student_name."; // If the email is sent successfully, display a success message   

        // echo '<button onclick="window.location.href=\'../' . $assessment_id . '\';">Redirect to Back Page</button>';
        echo '<button onclick="window.location.href=\'../view_details_of_exams.php?id=' . $assessment_id . '\';">Redirect to Back Page</button>';
    } catch (Exception $e) {
        echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    echo "Invalid request.";
}
