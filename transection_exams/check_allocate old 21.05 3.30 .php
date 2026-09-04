<?php
// Include your database connection
include("../database/connection.php");

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: application/json'); // Ensure the response is JSON

$response = ['success' => false, 'message' => '', 'failedEmails' => []];

if (isset($_POST['id'])) {
    $rowId = $_POST['id'];
    $removedEmails = isset($_POST['removed_emails']) ? $_POST['removed_emails'] : []; // Get removed emails

    // Fetch assessment details including programme, module, component names, year_id, and semester_id
    $detailsQuery = "SELECT a.*, 
                    p.program_name, 
                    m.module_name, 
                    ac.as_main_component_name,
                    sc.sub_component_name,
                    a.description,
                    a.year_id,
                    a.semester_id
                FROM assessments a 
                LEFT JOIN program_table p ON a.programme_id = p.program_code 
                LEFT JOIN modules m ON a.module_id = m.id 
                LEFT JOIN assignment_components ac ON a.main_component_id = ac.id 
                LEFT JOIN sub_assign_components sc ON a.sub_component_id = sc.id 
                WHERE a.id = ?";
    
    $stmt = $conn->prepare($detailsQuery);
    $stmt->bind_param("i", $rowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessmentDetails = $result->fetch_assoc();
    $stmt->close();

    if (!$assessmentDetails) {
        $response['message'] = 'Assessment details not found.';
        echo json_encode($response);
        exit;
    }

    // Extract details for email
    $programName = $assessmentDetails['program_name'];
    $moduleName = $assessmentDetails['module_name'];
    $mainComponentName = $assessmentDetails['as_main_component_name'];
    $subComponentName = $assessmentDetails['sub_component_name'] ?? 'N/A';
    $description = $assessmentDetails['description'];
    $assessmentDate = $assessmentDetails['assessment_date'];
    $yearId = $assessmentDetails['year_id'];
    $semesterId = $assessmentDetails['semester_id'];

    // Fetch attachments
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

    // Update query to set the mail_sent field with the current date and time
    $query = "UPDATE assessments SET mail_sent = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $rowId);

    if ($stmt->execute()) {
        // Fetch programme_id and batch_id
        $query = "SELECT programme_id, batch_id FROM assessments WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $rowId);
        $stmt->execute();
        $stmt->bind_result($programme_id, $batch_id);

        if ($stmt->fetch()) {
            $stmt->close();

            // Fetch students for the given program and batch
            $studentQuery = "SELECT s.first_name, s.last_name, ap.student_code, s.bms_email, ap.student_registration_id 
                             FROM allocate_programme ap
                             JOIN students s ON ap.student_code = s.student_code
                             WHERE ap.programme_code = ? AND ap.batch_id = ? AND ap.status = 'active'";
            $studentStmt = $conn->prepare($studentQuery);
            $studentStmt->bind_param("ii", $programme_id, $batch_id);
            $studentStmt->execute();
            $result = $studentStmt->get_result();
            
            // Initialize PHPMailer
            $mail = new PHPMailer(true);
         
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
            $mail->Password = 'Lox51527'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port = 587; // TCP port to connect to

            $mail->setFrom('noreply@bms.ac.lk', 'BMS Campus');

            $emailCount = 0;
            $failedEmails = []; // Array to store failed emails

            while ($student = $result->fetch_assoc()) {
                $firstName = $student['first_name'];
                $lastName = $student['last_name'];
                $fullName = $firstName . ' ' . $lastName;
                $bms_email = $student['bms_email'];
                $studentId = $student['student_registration_id'];
                
                // Skip removed students
                if (in_array($bms_email, $removedEmails)) {
                    continue;
                }

                $mail->addAddress($bms_email, $fullName);
                $mail->isHTML(true);
                $mail->Subject = "Assessment : $moduleName - $mainComponentName";
                
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
                            <h3>Assessment Notification</h3>
                        </div>
                        <div class='content'>
                            <p>Dear $fullName (ID: $studentId),</p>
                            
                            <p>This is to inform you about an upcoming assessment for your program:</p>
                            
                            <table>
                                <tr>
                                    <th>Program Name:</th>
                                    <td>$programName</td>
                                </tr>
                                <tr>
                                    <th>Module Name:</th>
                                    <td>$moduleName</td>
                                </tr>
                                <tr>
                                    <th>Components:</th>
                                    <td>$mainComponentName</td>
                                </tr>";
                
                if ($subComponentName != 'N/A') {
                    $emailBody .= "
                                <tr>
                                    <th>Sub Component:</th>
                                    <td>$subComponentName</td>
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

                // Add attachments
                foreach ($attachments as $filePath) {
                    if (file_exists($filePath)) {
                        $mail->addAttachment($filePath);
                    }
                }

                try {
                    if (!$mail->send()) {
                        $failedEmails[] = ['email' => $bms_email, 'error' => $mail->ErrorInfo];
                    } else {
                        $emailCount++;
                    }
                } catch (Exception $e) {
                    $failedEmails[] = ['email' => $bms_email, 'error' => $mail->ErrorInfo];
                }

                $mail->clearAddresses();
                $mail->clearAttachments();
            }

            $studentStmt->close();
            $response['success'] = true;
            $response['message'] = "$emailCount emails were successfully sent.";

            // Store failed emails in the database
            if (!empty($failedEmails)) {
                $insertStmt = $conn->prepare("INSERT INTO failed_emails (assessment_id, student_email, error_message) VALUES (?, ?, ?)");

                foreach ($failedEmails as $failed) {
                    $insertStmt->bind_param("iss", $rowId, $failed['email'], $failed['error']);
                    $insertStmt->execute();
                }
                $insertStmt->close();

                $response['failedEmails'] = $failedEmails;
            }
        } else {
            $response['message'] = 'No data found for the given row ID.';
        }
    } else {
        $response['message'] = 'Failed to update mail sent date.';
    }
} else {
    $response['message'] = 'Invalid request. Row ID is missing.';
}

echo json_encode($response);
