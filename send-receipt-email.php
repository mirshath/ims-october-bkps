<?php
session_start();
require 'database/connection.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['payment_id']) || !isset($_POST['student_id']) || !isset($_POST['payment_type']) || !isset($_POST['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

$paymentId = $_POST['payment_id'];
$studentId = $_POST['student_id'];
$paymentType = $_POST['payment_type'];
$recipientEmail = $_POST['email'];

// Validate email
if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
    exit;
}

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Server settings
    // $mail->isSMTP();
    // $mail->Host       = 'mail.graduatejob.lk';
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'noreply@graduatejob.lk';
    // $mail->Password   = 'Hasni@2024';
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    // $mail->Port       = 587;

    // // Recipients
    // $mail->setFrom('noreply@graduatejob.lk', 'BMS Finance Department');


    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
    $mail->Password = 'Lox51527'; // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port = 587; // TCP port to connect to

    //Recipients
    $mail->setFrom('noreply@bms.ac.lk', 'Business Management School'); // Replace with your email and name




    $mail->addAddress($recipientEmail);
    // $mail->addReplyTo('finance@bms.edu.lk', 'BMS Finance Department');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Payment Receipt';

    // Email body
    $mail->Body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Business Management School</h2>
                <p>Payment Receipt</p>
            </div>
            <div class="content">
                <p>Dear Student,</p>
                <p>Please find attached your payment receipt.</p>
                <p>If you have any questions, please contact our finance department.</p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
                <p>Business Management School - Sri Lanka</p>
            </div>
        </div>
    </body>
    </html>';

    // Attach PDF if provided
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $mail->addAttachment(
            $_FILES['pdf']['tmp_name'],
            'payment-receipt.pdf',
            PHPMailer::ENCODING_BASE64,
            'application/pdf'
        );
    }

    $mail->send();
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo]);
}
