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
          FROM save_assessment_document_send a 
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

// Get all students for this program and batch with their registration IDs
$students_query = "
SELECT s.*, ap.student_registration_id 
FROM students s 
INNER JOIN allocate_programme ap ON s.student_code = ap.student_code 
WHERE ap.programme_code = ? AND ap.batch_id = ? AND ap.status = 'active'
ORDER BY s.first_name, s.last_name";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("ii", $assessment['programme_id'], $assessment['batch_id']);
$students_stmt->execute();
$students_result = $students_stmt->get_result();
$students = $students_result->fetch_all(MYSQLI_ASSOC);

if (empty($students)) {
    echo json_encode(['success' => false, 'message' => 'No students found for this program and batch']);
    exit;
}

// Initialize counters and arrays
$success_count = 0;
$failed_count = 0;
$skipped_count = 0;
$failed_emails = [];
$success_emails = [];

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
        $skipped_count++;

        // Log the skipped email
        $logQuery = "INSERT INTO assesment_document_send_email_log 
                    (assessment_id, student_id, student_registration_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, ?, 'not_sent', NOW(), ?, 'Manually excluded from email list')";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iisss", $assessment_id, $student['student_code'], $student['student_registration_id'], $student['bms_email'], $sentBy);
        $logStmt->execute();
        $logStmt->close();

        continue;
    }

    try {
        // Clear previous recipients and attachments
        $mail->clearAddresses();
        $mail->clearAttachments();

        // Add this student as recipient
        $mail->addAddress($student['bms_email'], $student['first_name'] . ' ' . $student['last_name']);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = "Pre-seen Case Study - " . $assessment['module_name'] . " - " . $assessment['as_main_component_name'];

        // Create email body
        $fullName = $student['first_name'] . ' ' . $student['last_name'];
        $studentRegId = $student['student_registration_id'] ?? 'N/A';

        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .header { padding: 20px; text-align: center; background-color: #f8f9fa; border-bottom: 2px solid #007bff; }
                .content { padding: 30px; }
                .footer { background-color: #f1f1f1; padding: 15px; text-align: center; font-size: 12px; color: #666; }
                table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f2f2f2; font-weight: bold; color: #333; }
                .highlight { background-color: #e3f2fd; }
                .assessment-details { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' alt='BMS Logo' style='width: 150px; margin-bottom: 10px;'>
                    <h2 style='color: #007bff; margin: 0;'>Pre-seen Case Study</h2>
                </div>
                <div class='content'>
                    <p><strong>Dear {$fullName}</strong> (Student ID: <strong>{$studentRegId}</strong>),</p>
                    
                    <table>
                        <tr class='highlight'>
                            <th style='width: 40%;'>Program Name:</th>
                            <td><strong>" . htmlspecialchars($assessment['program_name']) . "</strong></td>
                        </tr>
                        <tr>
                            <th>Batch:</th>
                            <td>" . htmlspecialchars($assessment['batch_name']) . "</td>
                        </tr>
                        <tr class='highlight'>
                            <th>Module Name:</th>
                            <td><strong>" . htmlspecialchars($assessment['module_name']) . "</strong></td>
                        </tr>
                        <tr>
                            <th>Main Component:</th>
                            <td>" . htmlspecialchars($assessment['as_main_component_name']) . "</td>
                        </tr>";

        // Only show the Sub Component row if it's not empty
        if (!empty($assessment['sub_component_name'])) {
            $emailBody .= "
                        <tr>
                            <th>Sub Component:</th>
                            <td>" . htmlspecialchars($assessment['sub_component_name']) . "</td>
                        </tr>";
        }

        $emailBody .= "
                        <tr class='highlight'>
                            <th>Academic Year:</th>
                            <td>" . htmlspecialchars($assessment['year_id']) . "</td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td>" . htmlspecialchars($assessment['semester_id']) . "</td>
                        </tr>
                    </table>";

        // Add assessment details if description exists
        if (!empty($assessment['description'])) {
            $emailBody .= "
                    <div class='assessment-details'>
                        <div>" . $assessment['description'] . "</div>
                    </div>";
        }

        $emailBody .= "
                    
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
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>'], ["\n", "\n\n", "\n"], $emailBody));

        // Attach files if they exist
        $attachments = [];
        for ($i = 1; $i <= 4; $i++) {
            $attachmentField = "attachment_$i";
            if (!empty($assessment[$attachmentField])) {
                $attachmentPath = "../uploads_exam_assessments/" . $assessment[$attachmentField];
                if (file_exists($attachmentPath)) {
                    $attachments[] = $attachmentPath;
                }
            }
        }

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        // Send the email
        $mail->send();

        // Record successful email sending in the assesment_document_send_email_log table
        $logQuery = "INSERT INTO assesment_document_send_email_log 
                    (assessment_id, student_id, student_registration_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, ?, 'sent', NOW(), ?, NULL)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iisss", $assessment_id, $student['student_code'], $student['student_registration_id'], $student['bms_email'], $sentBy);
        $logStmt->execute();
        $logStmt->close();

        $success_count++;
        $success_emails[] = [
            'email' => $student['bms_email'],
            'name' => $fullName,
            'student_id' => $studentRegId
        ];

        // Small delay to prevent overwhelming the SMTP server
        usleep(100000); // 0.1 second delay

    } catch (Exception $e) {
        // Record failed email sending
        $errorMsg = $mail->ErrorInfo;
        $logQuery = "INSERT INTO assesment_document_send_email_log 
                    (assessment_id, student_id, student_registration_id, email, status, sent_date, sent_by, error_message) 
                    VALUES (?, ?, ?, ?, 'failed', NOW(), ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("iissss", $assessment_id, $student['student_code'], $student['student_registration_id'], $student['bms_email'], $sentBy, $errorMsg);
        $logStmt->execute();
        $logStmt->close();

        $failed_count++;
        $failed_emails[] = [
            'email' => $student['bms_email'],
            'name' => $fullName,
            'student_id' => $studentRegId,
            'error' => $errorMsg
        ];
    }
}

// Update the mail_sent timestamp in the assessment table if any emails were sent successfully
if ($success_count > 0) {
    $updateQuery = "UPDATE save_assessment_document_send SET mail_sent = NOW() WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("i", $assessment_id);
    $updateStmt->execute();
    $updateStmt->close();
}

// Prepare detailed response message
$total_processed = $success_count + $failed_count;
$total_students = count($students);

$message = "Email sending completed!\n\n";
$message .= "📊 Summary:\n";
$message .= "• Total Students: {$total_students}\n";
$message .= "• Successfully Sent: {$success_count}\n";
$message .= "• Failed: {$failed_count}\n";
$message .= "• Skipped (Excluded): {$skipped_count}\n";
$message .= "• Total Processed: {$total_processed}\n\n";

if ($success_count > 0) {
    $message .= "✅ Successfully sent to:\n";
    foreach (array_slice($success_emails, 0, 5) as $email) { // Show first 5
        $message .= "• {$email['name']} ({$email['student_id']}) - {$email['email']}\n";
    }
    if (count($success_emails) > 5) {
        $message .= "• ... and " . (count($success_emails) - 5) . " more\n";
    }
    $message .= "\n";
}

if ($failed_count > 0) {
    $message .= "❌ Failed to send to:\n";
    foreach (array_slice($failed_emails, 0, 3) as $email) { // Show first 3 failures
        $message .= "• {$email['name']} ({$email['student_id']}) - {$email['email']}\n";
        $message .= "  Error: " . substr($email['error'], 0, 100) . "...\n";
    }
    if (count($failed_emails) > 3) {
        $message .= "• ... and " . (count($failed_emails) - 3) . " more failures\n";
    }
}

// Return the results
echo json_encode([
    'success' => true,
    'message' => $message,
    'successCount' => $success_count,
    'failedCount' => $failed_count,
    'skippedCount' => $skipped_count,
    'totalCount' => $total_students,
    'failedEmails' => $failed_emails,
    'successEmails' => $success_emails
]);
