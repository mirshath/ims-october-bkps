<?php
session_start();
include("../database/connection.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

// Check if the request is an AJAX request
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get the assessment ID
$assessment_id = $_POST['id'] ?? '';
if (empty($assessment_id)) {
    echo json_encode(['success' => false, 'message' => 'Assessment ID is required']);
    exit;
}

// Get the list of emails to be removed
$removed_emails = $_POST['removed_emails'] ?? [];

// Get assessment details
$query = "SELECT a.*, p.program_name, b.batch_name, m.module_name, 
          ac.as_main_component_name, sc.sub_component_name 
          FROM assessments a 
          LEFT JOIN program_table p ON a.programme_id = p.program_code 
          LEFT JOIN batch_table b ON a.batch_id = b.id 
          LEFT JOIN modules m ON a.module_id = m.id 
          LEFT JOIN assignment_components ac ON a.main_component_id = ac.id 
          LEFT JOIN sub_assign_components sc ON a.sub_component_id = sc.id 
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $assessment_id);
$stmt->execute();
$result = $stmt->get_result();
$assessment = $result->fetch_assoc();

if (!$assessment) {
    echo json_encode(['success' => false, 'message' => 'Assessment not found']);
    exit;
}

// Get all students for this program and batch
$students_query = "
SELECT s.*, a.compulsory_sub, a.elective_subs
FROM students s 
INNER JOIN allocate_programme a ON s.student_code = a.student_code 
WHERE a.programme_code = ? AND a.batch_id = ? AND a.status = 'active'";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("ii", $assessment['programme_id'], $assessment['batch_id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$all_students = $students_result->fetch_all(MYSQLI_ASSOC);

// Now filter students who have the module in either compulsory_sub or elective_subs
$students = [];
$module_name = $assessment['module_name'];
foreach ($all_students as $student) {
    $compulsory_subs = explode(',', $student['compulsory_sub']);
    $elective_subs_array = explode(',', $student['elective_subs']);
    
    // Trim whitespace from each module name
    $compulsory_subs = array_map('trim', $compulsory_subs);
    $elective_subs_array = array_map('trim', $elective_subs_array);
    
    if (in_array($module_name, $compulsory_subs) || in_array($module_name, $elective_subs_array)) {
        $students[] = $student;
    }
}

if (empty($students)) {
    echo json_encode(['success' => false, 'message' => 'No students found for this program, batch, and module']);
    exit;
}

// Initialize counters and arrays
$success_count = 0;
$failed_count = 0;
$failed_emails = [];

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

// Server settings
$mail->isSMTP();
$mail->Host = 'smtp.office365.com';
$mail->SMTPAuth = true;
$mail->Username = 'noreply@bms.ac.lk';
$mail->Password = 'Lox51527';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->setFrom('noreply@bms.ac.lk', 'BMS Campus');

// Get the current user
$sentBy = $_SESSION['username'] ?? 'system';

// Loop through each student and send email
foreach ($students as $student) {
    // Skip if the student's email is in the removed list
    if (in_array($student['bms_email'], $removed_emails)) {
        continue;
    }
    
    // Removed the check for previously sent emails to allow resending
    
    try {
        // Clear previous recipients
        $mail->clearAddresses();
        $mail->clearAttachments();
        
        // Add this student as recipient
        $mail->addAddress($student['bms_email'], $student['first_name'] . ' ' . $student['last_name']);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Assessment : " . $assessment['module_name'] . " - " . $assessment['as_main_component_name'];
        
        // Get student registration ID
        $studentId = '';
        $studentIdQuery = "SELECT student_registration_id FROM allocate_programme WHERE student_code = ?";
        $studentIdStmt = $conn->prepare($studentIdQuery);
        $studentIdStmt->bind_param("i", $student['student_code']);
        $studentIdStmt->execute();
        $studentIdStmt->bind_result($studentId);
        $studentIdStmt->fetch();
        $studentIdStmt->close();
        
        // Create email body
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
                    <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' alt='BMS Logo' style='width: 150px;'>
                    <h2>Assessment Notification</h2>
                </div>
                <div class='content'>
                    <p>Dear " . $student['first_name'] . " " . $student['last_name'] . " (ID: $studentId),</p>
                    
                    <p>This is to inform you about an upcoming assessment for your program:</p>
                    
                    <table>
                        <tr>
                            <th>Program Name:</th>
                            <td>" . $assessment['program_name'] . "</td>
                        </tr>
                        <tr>
                            <th>Module Name:</th>
                            <td>" . $assessment['module_name'] . "</td>
                        </tr>
                        <tr>
                            <th>Components:</th>
                            <td>" . $assessment['as_main_component_name'] . "</td>
                        </tr>";

        // Only show the Sub Component row if it's not empty
        if (!empty($assessment['sub_component_name'])) {
            $emailBody .= "
                        <tr>
                            <th>Sub Component:</th>
                            <td>" . $assessment['sub_component_name'] . "</td>
                        </tr>";
        }

        $emailBody .= "
                        <tr>
                            <th>Assessment Date:</th>
                            <td>" . date('Y-m-d h:i A', strtotime($assessment['assessment_date'])) . "</td>
                        </tr>
                        <tr>
                            <th>Year:</th>
                            <td>" . $assessment['year_id'] . "</td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td>" . $assessment['semester_id'] . "</td>
                        </tr>
                    </table>
                    
                    <div style='margin-top: 20px;'>
                        <h3>Assessment Details:</h3>
                        <p>" . $assessment['description'] . "</p>
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
        
        // Attach files if they exist
        $attachments = [];
        if (!empty($assessment['attachment_4'])) {
            $attachments[] = "../uploads_exam_assessments/" . $assessment['attachment_4'];
        }
        if (!empty($assessment['attachment_3'])) {
            $attachments[] = "../uploads_exam_assessments/" . $assessment['attachment_3'];
        }
        if (!empty($assessment['attachment_2'])) {
            $attachments[] = "../uploads_exam_assessments/" . $assessment['attachment_2'];
        }
        if (!empty($assessment['attachment_1'])) {
            $attachments[] = "../uploads_exam_assessments/" . $assessment['attachment_1'];
        }
        
        foreach ($attachments as $attachment) {
            if (file_exists($attachment)) {
                $mail->addAttachment($attachment);
            }
        }
        
        // Send the email
        $mail->send();
        
        // Record successful email sending
        $logQuery = "INSERT INTO assessment_email_log 
                    (assessment_id, student_id, email, status, sent_date, sent_by) 
                    VALUES (?, ?, ?, 'sent', NOW(), ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iiss", $assessment_id, $student['student_code'], $student['bms_email'], $sentBy);
        $logStmt->execute();
        
        $success_count++;
    } catch (Exception $e) {
        // Record failed email sending
        $errorMsg = $mail->ErrorInfo;
        $logQuery = "INSERT INTO assessment_email_log 
                    (assessment_id, student_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, 'failed', NOW(), ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iisss", $assessment_id, $student['student_code'], $student['bms_email'], $sentBy, $errorMsg);
        $logStmt->execute();
        
        // Also log to the failed_emails table (keeping original functionality)
        $failedStmt = $conn->prepare("INSERT INTO failed_emails (assessment_id, student_email, error_message) VALUES (?, ?, ?)");
        $failedStmt->bind_param("iss", $assessment_id, $student['bms_email'], $errorMsg);
        $failedStmt->execute();
        $failedStmt->close();
        
        $failed_count++;
        $failed_emails[] = ['email' => $student['bms_email'], 'error' => $errorMsg];
    }
}

// Return the results
$total = $success_count + $failed_count;
$message = "Email sending complete: {$success_count} successful, {$failed_count} failed out of {$total} total.";

echo json_encode([
    'success' => true,
    'message' => $message,
    'successCount' => $success_count,
    'failedCount' => $failed_count,
    'failedEmails' => $failed_emails
]);
?>