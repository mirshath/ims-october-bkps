<?php
session_start(); // Added session_start to access username
include("../database/connection.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Check if the request is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    @set_time_limit(120);

    $lockFilePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assi_email_send.lock';
    $lockHandle = @fopen($lockFilePath, 'c');
    if ($lockHandle) {
        if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            $busyMsg = 'Email sender is busy. Please try again in a moment.';
            if ($isAjax) {
                echo json_encode(['success' => false, 'message' => $busyMsg]);
            } else {
                echo $busyMsg;
            }
            exit;
        }
    }
    $to = $_POST['email'];
    $student_name = $_POST['student_name'];
    $assessment_id = $_POST['assessment_id'];
    $as_main_component_name = $_POST['as_main_component_name'];
    $sub_component_name = isset($_POST['sub_component_name']) ? $_POST['sub_component_name'] : 'N/A';
    $program_name = $_POST['program_name'];

    // Get student registration ID and student code
    $studentQuery = "SELECT ap.student_registration_id, s.student_code 
                     FROM students s 
                     JOIN allocate_programme ap ON s.student_code = ap.student_code 
                     WHERE s.bms_email = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("s", $to);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentData = $result->fetch_assoc();
    $stmt->close();

    $studentId = $studentData['student_registration_id'] ?? '';
    $student_code = $studentData['student_code'] ?? '';

    // Fetch additional assessment details
    $detailsQuery = "SELECT a.*, 
                m.module_name,
                a.description,
                a.subject_body,
                a.assessment_date,
                a.year_id,
                a.semester_id
            FROM save_assessment_document_send a 
            LEFT JOIN modules m ON a.module_id = m.id 
            WHERE a.id = ?";

    $stmt = $conn->prepare($detailsQuery);
    $stmt->bind_param("i", $assessment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessmentDetails = $result->fetch_assoc();
    $stmt->close();

    if (!$assessmentDetails) {
        $errorMessage = "Assessment details not found.";

        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit;
        } else {
            echo $errorMessage;
            exit;
        }
    }

    $moduleName = $assessmentDetails['module_name'];
    $description = $assessmentDetails['description'];
    $subjectBody = $assessmentDetails['subject_body'];
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
        $mail->SMTPKeepAlive = true;
        $mail->Timeout = 60;

        $mail->setFrom('noreply@bms.ac.lk', 'BMS Campus');
        $mail->addAddress($to, $student_name);

        $mail->isHTML(true);
        // Use subject_body if available, otherwise fallback to default
        $subjectPrefix = !empty($subjectBody) ? $subjectBody : "Pre-seen Case Study";
        $mail->Subject = "$subjectPrefix - $moduleName - $as_main_component_name";

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
                .header { padding: 20px; text-align: center; background-color: #f8f9fa; border-bottom: 2px solid #007bff; }
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
                    <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' alt='BMS Logo' style='width: 150px; margin-bottom: 10px;'>
                    <h2 style='color: #007bff; margin: 0;'>" . htmlspecialchars($subjectPrefix) . "</h2>
                </div>
                <div class='content'>
                    <p>Dear $fullName (ID: $studentId),</p>
 
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
                            <th>Year:</th>
                            <td>$yearId</td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td>$semesterId</td>
                        </tr>
                    </table>
                    
                    <div style='margin-top: 15px;'>
                    <p>$description</p>
                    </div>
                    
                     <p>Best regards,<br>
                        <strong>BMS Campus Academic Team</strong></p>
                        </div>
                        <div class='footer'>
                        <p><strong>This is an automated email. Please do not reply to this message.</strong></p>
                        <p>© " . date('Y') . " BMS Campus. All rights reserved.</p>
                        <p>If you received this email in error, please contact the IT department immediately.</p>
                    </div>
                    </div>
                    </body>
                    </html>";

        // <h3 style='color: #007bff; margin-top: 0;'>Description</h3>
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $emailBody));

        // Attach files
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
        }

        $mail->send();
        usleep(500000);
        $mail->clearAddresses();
        $mail->clearAttachments();
        if (method_exists($mail, 'smtpClose')) {
            $mail->smtpClose();
        }

        // Record successful email sending in the assesment_document_send_email_log table
        $sentBy = $_SESSION['username'] ?? 'system';
        $logQuery = "INSERT INTO assesment_document_send_email_log 
                    (assessment_id, student_id, student_registration_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, ?, 'sent', NOW(), ?, NULL)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iisss", $assessment_id, $student_code, $studentId, $to, $sentBy);
        $logStmt->execute();
        $logStmt->close();

        if ($lockHandle) { @flock($lockHandle, LOCK_UN); @fclose($lockHandle); }

        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => "Email sent successfully to $student_name"]);
        } else {
            // Success message with redirect button
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<h3 style='color: green;'>Email sent successfully to $student_name!</h3>";
            echo "<p>The assessment details have been sent to the student's email address.</p>";
            echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
            onclick=\"window.location.href='../view_details_of_exams.php?id=$assessment_id'\">
            Return to Assessment Details</button>";
            echo "</div>";
        }
    } catch (Exception $e) {
        // Record failed email sending in the assesment_document_send_email_log table
        $errorMsg = $mail->ErrorInfo;
        $sentBy = $_SESSION['username'] ?? 'system';
        $logQuery = "INSERT INTO assesment_document_send_email_log 
                    (assessment_id, student_id, student_registration_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, ?, 'failed', NOW(), ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iissss", $assessment_id, $student_code, $studentId, $to, $sentBy, $errorMsg);
        $logStmt->execute();
        $logStmt->close();

        if ($lockHandle) { @flock($lockHandle, LOCK_UN); @fclose($lockHandle); }

        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => "Failed to send email: " . $mail->ErrorInfo]);
        } else {
            // Error message with redirect button
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<h3 style='color: red;'>Failed to send email!</h3>";
            echo "<p>Error: " . $mail->ErrorInfo . "</p>";
            echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
                onclick=\"window.location.href='../view_details_of_exams.php?id=$assessment_id'\">
                Return to Assessment Details</button>";
            echo "</div>";
        }

        // Log the error to the database (keeping the original logging as well)
        $stmt = $conn->prepare("INSERT INTO failed_emails (assessment_id, student_email, error_message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $assessment_id, $to, $mail->ErrorInfo);
        $stmt->execute();
        $stmt->close();
    }
} else {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => "Invalid request method"]);
    } else {
        echo "<div style='text-align: center; margin-top: 20px;'>";
        echo "<h3 style='color: red;'>Invalid request method!</h3>";
        echo "<p>This page should be accessed through the proper form submission.</p>";
        echo "<button style='padding: 10px 20px; background-color: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;' 
            onclick=\"window.history.back()\">
            Go Back</button>";
        echo "</div>";
    }
}
