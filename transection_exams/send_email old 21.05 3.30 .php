<?php
include("../database/connection.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = $_POST['email'];
    $student_name = $_POST['student_name'];
    $assessment_id = $_POST['assessment_id'];
    $as_main_component_name = $_POST['as_main_component_name'];
    $sub_component_name = isset($_POST['sub_component_name']) ? $_POST['sub_component_name'] : 'N/A';
    $program_name = $_POST['program_name'];

    // Get student registration ID
    $studentQuery = "SELECT ap.student_registration_id 
                     FROM students s 
                     JOIN allocate_programme ap ON s.student_code = ap.student_code 
                     WHERE s.bms_email = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("s", $to);
    $stmt->execute();
    $stmt->bind_result($studentId);
    $stmt->fetch();
    $stmt->close();

    // Fetch additional assessment details
    $detailsQuery = "SELECT a.*, 
                m.module_name,
                a.description,
                a.assessment_date,
                a.year_id,
                a.semester_id
            FROM assessments a 
            LEFT JOIN modules m ON a.module_id = m.id 
            WHERE a.id = ?";

    $stmt = $conn->prepare($detailsQuery);
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessmentDetails = $result->fetch_assoc();
    $stmt->close();

    if (!$assessmentDetails) {
        echo "Assessment details not found.";
        exit;
    }

    $moduleName = $assessmentDetails['module_name'];
    $description = $assessmentDetails['description'];
    $assessmentDate = $assessmentDetails['assessment_date'];
    $yearId = $assessmentDetails['year_id'];
    $semesterId = $assessmentDetails['semester_id'];

    // Fetch attachments for the given assessment ID
    $attachment1 = $assessmentDetails['attachment_1'];
    $attachment2 = $assessmentDetails['attachment_2'];
    $attachment3 = $assessmentDetails['attachment_3'];
    $attachment4 = $assessmentDetails['attachment_4'];

    // Get all non-empty attachments
    $attachments = [];
    if (!empty($attachment4)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment4;
    }
    if (!empty($attachment3)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment3;
    }
    if (!empty($attachment2)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment2;
    }
    if (!empty($attachment1)) {
        $attachments[] = "../uploads_exam_assessments/" . $attachment1;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@bms.ac.lk';
        $mail->Password = 'Lox51527';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@bms.ac.lk', 'BMS Campus');
        $mail->addAddress($to, $student_name);

        $mail->isHTML(true);
        $mail->Subject = "Assessment : $moduleName - $as_main_component_name";

        // Split student name into first and last name if needed
        $nameParts = explode(' ', $student_name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        $fullName = $student_name;

        // Create a more informative email body with the requested details
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { padding: 10px; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; }
                table { border-collapse: collapse; width: 100%; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://www.bms.ac.lk/assets/images/BMS-Logo-WB.jpg' alt='BMS Logo' style='width: 150px;'>
                    <h2>Assessment Notification</h2>
                </div>
                <div class='content'>
                    <p>Dear $fullName (ID: $studentId),</p>
                    
                    <p>This is to inform you about an upcoming assessment for your program:</p>
                    
                    <table>
                        <tr>
                            <th>Program Name:</th>
                            <td>$program_name</td>
                        </tr>
                        <tr>
                            <th>Module Name:</th>
                            <td>$moduleName</td>
                        </tr>
                        <tr>
                            <th>Components:</th>
                            <td>$as_main_component_name</td>
                        </tr>";

        // Only show the Sub Component row if it's not 'N/A'
        if ($sub_component_name != 'N/A') {
            $emailBody .= "
                        <tr>
                            <th>Sub Component:</th>
                            <td>$sub_component_name</td>
                        </tr>";
        }

        $emailBody .= "
                        <tr>
                            <th>Assessment Date:</th>
                            <td>$assessmentDate</td>
                        </tr>
                        <tr>
                            <th>Year:</th>
                            <td>$yearId</td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td>$semesterId</td>
                        </tr>
                    </table>
                    
                    <div style='margin-top: 20px;'>
                        <h3>Assessment Details:</h3>
                        <p>$description</p>
                    </div>
                    
                    <p>Best regards,<br>BMS Campus</p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                    <p>" . date('Y') . " BMS Campus. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

        // Attach files
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
        }

        $mail->send();

        // Success message with redirect button
        echo "<div style='text-align: center; margin-top: 20px;'>";
        echo "<h3 style='color: green;'>Email sent successfully to $student_name!</h3>";
        echo "<p>The assessment details have been sent to the student's email address.</p>";
        echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
              onclick=\"window.location.href='../view_details_of_exams.php?id=$assessment_id'\">
              Return to Assessment Details</button>";
        echo "</div>";
    } catch (Exception $e) {
        // Error message with redirect button
        echo "<div style='text-align: center; margin-top: 20px;'>";
        echo "<h3 style='color: red;'>Failed to send email!</h3>";
        echo "<p>Error: " . $mail->ErrorInfo . "</p>";
        echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
              onclick=\"window.location.href='../view_details_of_exams.php?id=$assessment_id'\">
              Return to Assessment Details</button>";
        echo "</div>";

        // Log the error to the database
        $stmt = $conn->prepare("INSERT INTO failed_emails (assessment_id, student_email, error_message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $assessment_id, $to, $mail->ErrorInfo);
        $stmt->execute();
        $stmt->close();
    }
} else {
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<h3 style='color: red;'>Invalid request method!</h3>";
    echo "<p>This page should be accessed through the proper form submission.</p>";
    echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
          onclick=\"window.history.back()\">
          Go Back</button>";
    echo "</div>";
}
