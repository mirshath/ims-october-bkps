<?php
session_start();
header('Content-Type: application/json');

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

include 'database/connection.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get form data
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$studentCode = isset($_POST['student_code']) ? intval($_POST['student_code']) : 0;
$programmeCode = isset($_POST['programme_code']) ? trim($_POST['programme_code']) : '';
$batchId = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
$personalEmail = isset($_POST['personal_email']) ? trim($_POST['personal_email']) : '';
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$nic = isset($_POST['nic']) ? trim($_POST['nic']) : '';
$sentBy = $_SESSION['username'];

$studentName = trim("$title $firstName $lastName");

// Validate required fields
if (!$id || !$studentCode || !$programmeCode || !$batchId || !$personalEmail) {
    echo json_encode(['success' => false, 'message' => 'Invalid student data']);
    exit;
}

// Fetch email template from induction_email_body_db_table
$template = null;
try {
    $templateQuery = "
        SELECT 
            t.*, 
            pt.program_name 
        FROM induction_email_body_db_table t
        INNER JOIN program_table pt ON t.program_id = pt.program_code
        WHERE t.program_id = ? AND t.batch_id = ?
        ORDER BY t.created_at DESC
        LIMIT 1
    ";
    $templateStmt = $conn->prepare($templateQuery);
    $templateStmt->bind_param("si", $programmeCode, $batchId);
    $templateStmt->execute();
    $templateResult = $templateStmt->get_result();
    if ($templateResult->num_rows > 0) {
        $template = $templateResult->fetch_assoc();
    }
    $templateStmt->close();
} catch (Exception $e) {
    // If template fetch fails, just proceed without template (use defaults)
}

$emailSent = false;
$errorMessage = '';

try {
    // Load SMTP config from config.ini
    $config = parse_ini_file(__DIR__ . '/config.ini');

    // Get base URL from config or fall back to auto-detect
    $baseUrl = isset($config['BASE_URL']) ? $config['BASE_URL'] : 'http://localhost/ims/';
    // Ensure baseUrl ends with a slash
    if (substr($baseUrl, -1) !== '/') {
        $baseUrl .= '/';
    }

    // Send email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['SMTP_USER'];
    $mail->Password = $config['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $config['SMTP_PORT'];
    $mail->setFrom($config['SMTP_FROM_EMAIL'], $config['SMTP_FROM_NAME']);
    $mail->addAddress($personalEmail, "$title $firstName $lastName");
    $mail->isHTML(true);
    $mail->Subject = 'Induction Programme Invitation';

    // Embed banner image if available
    $bannerCid = '';
    if ($template && !empty($template['banner_image_path'])) {
        $bannerPath = __DIR__ . '/' . $template['banner_image_path'];
        if (file_exists($bannerPath)) {
            $bannerCid = 'banner_' . md5($template['banner_image_path']);
            $mail->AddEmbeddedImage($bannerPath, $bannerCid);
        }
    }

    // Use NIC if available, otherwise student code
    $qrData = !empty($nic) ? $nic : $studentCode;
    $studentQR = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&bgcolor=FFFFFF&margin=40&data=" . urlencode($qrData);

    $mail->Body = '
        <!DOCTYPE html>
        <html>
        <body style="margin:0;padding:0;background-color:#f4f6f8;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:20px 0;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                        <tr>
                            <td style="padding:32px 36px;color:#222;font-size:15px;line-height:1.6;">
                                ' . (!empty($bannerCid) ? '<img src="cid:' . $bannerCid . '" alt="Banner" style="width:100%;display:block;margin-bottom:20px;border-radius:8px;">' : '') . '
                                <p style="font-size:20px;font-weight:bold;color:#052c65;text-align:center;margin:0 0 20px 0;">
                                    Induction Programme Invitation
                                </p>
                                <p>
                                    Dear <strong>' . htmlspecialchars("$title $firstName $lastName") . '</strong>,
                                </p>
                                ' . ($template && !empty($template['email_body']) ? $template['email_body'] : '<p>We are pleased to invite you to the induction programme.</p>') . '
                                <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f6fb; border-left:5px solid #0a66c2; margin:20px 0; border-radius:6px;">
                                    ' . ($template && !empty($template['date']) ? '
                                    <tr>
                                        <td style="padding:8px 20px;"><strong>Date:</strong></td>
                                        <td style="padding:8px 20px;">' . htmlspecialchars($template['date']) . '</td>
                                    </tr>
                                    ' : '') . '
                                    ' . ($template && !empty($template['time']) ? '
                                    <tr>
                                        <td style="padding:8px 20px;"><strong>Time:</strong></td>
                                        <td style="padding:8px 20px;">' . $template['time'] . '</td>
                                    </tr>
                                    ' : '') . '
                                    <tr>
                                        <td style="padding:8px 20px;"><strong>Venue:</strong></td>
                                        <td style="padding:8px 20px;">
                                            <a href="https://maps.app.goo.gl/tdDthwMJZnRXxd9s8" target="_blank" style="color:#0a66c2; text-decoration:underline; font-size:15px;">
                                                <img src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/icons/geo-alt-fill.svg" width="15" height="15" alt="Location" style="margin-right:5px;display:inline;vertical-align:-3px;">
                                                BMS - Colombo Graduate School
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:8px 20px;"><strong>Programme:</strong></td>
                                        <td style="padding:8px 20px;">' . ($template && !empty($template['program_name']) ? htmlspecialchars($template['program_name']) : '') . '</td>
                                    </tr>
                                    ' . ($template && !empty($template['dress_code']) ? '
                                    <tr>
                                        <td style="padding:8px 20px;"><strong>Dress Code:</strong></td>
                                        <td style="padding:8px 20px;">' . htmlspecialchars($template['dress_code']) . '</td>
                                    </tr>
                                    ' : '') . '
                                    ' . ((!$template || (empty($template['date']) && empty($template['time']) && empty($template['dress_code']))) ? '
                                    <tr>
                                        <td style="padding:20px;" colspan="2">
                                            <p style="margin:0;"><strong>Please contact the admissions team for more details about the induction schedule.</strong></p>
                                        </td>
                                    </tr>
                                    ' : '') . '
                                </table>
                                <table width="100%" cellpadding="0" cellspacing="0" style="margin:30px 0 20px 0;">
                                    <tr>
                                        <td align="center">
                                            <p style="font-size:16px;font-weight:bold;margin:20px 0 10px 0;text-align:center;">
                                                Your Attendance QR Code
                                            </p>
                                            <img src="' . $studentQR . '"
                                                alt="QR Code"
                                                width="180"
                                                height="180"
                                                style="display:block;border:2px solid #0a66c2;border-radius:8px;margin:0 auto;">
                                        </td>
                                    </tr>
                                </table>
                                ' . ($template && !empty($template['important_note']) ? '
                                <div style="border:1px solid #ffd59e;background-color:#fff4e5;margin:25px 0;border-radius:5px;">
                                    <div style="padding:18px;font-size:14px;color:#333;">
                                        <strong>Important:</strong>
                                        <div style="margin-top:8px;">' . $template['important_note'] . '</div>
                                    </div>
                                </div>
                                ' : '
                                <div style="border:1px solid #ffd59e;background-color:#fff4e5;margin:25px 0;border-radius:5px;">
                                    <div style="padding:18px;font-size:14px;color:#333;">
                                        <strong>Important:</strong>
                                        <ul style="margin:8px 0 0 18px;padding:0;">
                                            <li>Attendance at the Induction Programme is <strong>mandatory</strong> for all students.</li>
                                        </ul>
                                    </div>
                                </div>
                                ') . '
                                <p style="text-align:center;margin-top:25px;font-size:15px;">
                                    We look forward to welcoming you.
                                </p>
                                <p style="text-align:center;font-weight:bold;color:#052c65;margin-top:10px;">
                                    BMS CAMPUS
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="background-color:#f0f2f5;text-align:center;padding:14px;font-size:12px;color:#888;">
                                &copy; ' . date("Y") . ' BMS CAMPUS
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        </body>
        </html>
    ';

    $mail->send();
    $emailSent = true;
} catch (PHPMailerException $e) {
    $errorMessage = $e->getMessage();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}

// Now handle database operations
try {
    $conn->begin_transaction();

    // Log to new audit table (first, before any other operations)
    $logStatus = $emailSent ? 'sent' : 'failed';
    $logStmt = $conn->prepare("INSERT INTO induction_db_email_send_log_table 
        (allocate_programme_id, student_code, student_name, program_id, batch_id, email_address, status, error_message, sent_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("iisiissss", 
            $id, 
            $studentCode, 
            $studentName, 
            $programmeCode, 
            $batchId, 
            $personalEmail, 
            $logStatus, 
            $errorMessage, 
            $sentBy
        );
        $logStmt->execute();
        $logStmt->close();
    }

    // Check if the NIC is empty and try to get it from students table
    if (empty($nic)) {
        $getNicStmt = $conn->prepare("SELECT nic FROM students WHERE student_code = ?");
        if ($getNicStmt) {
            $getNicStmt->bind_param("i", $studentCode);
            $getNicStmt->execute();
            $getNicResult = $getNicStmt->get_result();
            if ($getNicResult->num_rows > 0) {
                $nicRow = $getNicResult->fetch_assoc();
                $nic = $nicRow['nic'];
            }
            $getNicStmt->close();
        }
    }

    $checkStmt = $conn->prepare("SELECT id FROM induction_emails_sent WHERE allocate_programme_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkStmt->close();

    if ($checkResult->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE induction_emails_sent SET email = ?, sent_at = NOW(), sent_by = ?, program_id = ?, batch_id = ?, nic = ? WHERE allocate_programme_id = ?");
        if (!$updateStmt) {
            throw new Exception("Prepare update failed: " . $conn->error);
        }
        $updateStmt->bind_param("ssiisi", $personalEmail, $sentBy, $programmeCode, $batchId, $nic, $id);
        if (!$updateStmt->execute()) {
            throw new Exception("Update failed: " . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO induction_emails_sent (allocate_programme_id, student_code, nic, program_id, batch_id, email, sent_at, sent_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        if (!$insertStmt) {
            throw new Exception("Prepare insert failed: " . $conn->error);
        }
        $insertStmt->bind_param("iisisss", $id, $studentCode, $nic, $programmeCode, $batchId, $personalEmail, $sentBy);
        if (!$insertStmt->execute()) {
            throw new Exception("Insert failed: " . $insertStmt->error);
        }
        $insertStmt->close();
    }

    $conn->commit();
    if ($emailSent) {
        echo json_encode(['success' => true, 'message' => 'Email sent and saved successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email: ' . $errorMessage]);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno == 0 && $conn->inTransaction()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
