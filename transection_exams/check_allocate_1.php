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

    // Fetch attachments for the given row ID
    $attachmentQuery = "SELECT attachment_1, attachment_2, attachment_3, attachment_4 FROM assessments WHERE id = ?";
    $stmt = $conn->prepare($attachmentQuery);
    $stmt->bind_param("i", $rowId);
    $stmt->execute();
    $stmt->bind_result($attachment1, $attachment2, $attachment3, $attachment4);
    $stmt->fetch();
    $stmt->close();

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
            $studentQuery = "SELECT s.first_name, ap.student_code, s.bms_email 
                             FROM allocate_programme ap
                             JOIN students s ON ap.student_code = s.student_code
                             WHERE ap.programme_code = ? AND ap.batch_id = ? AND ap.status = 'active'";
            $studentStmt = $conn->prepare($studentQuery);
            $studentStmt->bind_param("ii", $programme_id, $batch_id);
            $studentStmt->execute();
            $studentStmt->bind_result($first_name, $student_code, $bms_email);

            // Initialize PHPMailer
            $mail = new PHPMailer(true);
            // $mail->isSMTP();
            // $mail->Host = 'mail.graduatejob.lk';
            // $mail->SMTPAuth = true;
            // $mail->Username = 'noreply@graduatejob.lk';
            // $mail->Password = 'Hasni@2024';
            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            // $mail->Port = 587;

            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
            $mail->Password = 'Lox51527'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
            $mail->Port = 587; // TCP port to connect to



            $mail->setFrom('noreply@bms.ac.lk', 'BMS');

            $emailCount = 0;
            $failedEmails = []; // Array to store failed emails

            while ($studentStmt->fetch()) {
                // Skip removed students
                if (in_array($bms_email, $removedEmails)) {
                    continue;
                }

                $mail->addAddress($bms_email, $first_name);
                $mail->isHTML(true);
                $mail->Subject = 'Assesment Alert';
                $mail->Body = "Dear $first_name,<br><br>This is an important Assesment email regarding your program and batch.<br><br>Regards,<br>BMS Team";

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
