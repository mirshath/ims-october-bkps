<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check required fields
if (empty($_POST['email']) || empty($_POST['content'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Get form data
$email = $_POST['email'];
$name = $_POST['name'] ?? 'Student';
$program = $_POST['program'] ?? 'Program';
$content = $_POST['content'];
$pdfData = $_POST['pdf_data'] ?? '';
$pdfName = $_POST['pdf_name'] ?? 'offer_letter.pdf';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@bms.ac.lk';
    $mail->Password = 'Lox51527';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('noreply@bms.ac.lk', 'BMS Academic Registrar');
    $mail->addAddress($email, $name);
    
    // Attach PDF if data is provided
    if (!empty($pdfData)) {
        // Decode base64 data
        $pdfContent = base64_decode($pdfData);
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        file_put_contents($tempFile, $pdfContent);
        
        // Add attachment
        $mail->addAttachment($tempFile, $pdfName);
    }

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Offer Letter for $program - BMS";

    // Create email body with proper styling
    $emailBody = <<<EOD
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            background-color: #003366;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Business Management School</h2>
        </div>
        <div class="content">
            <p>Dear $name,</p>
            
            <p>Please find attached your offer letter for the $program program at Business Management School.</p>
            
            <p>This offer letter contains important information about your program, including fees, duration, and deadlines. Please review it carefully.</p>
            
            <p>If you have any questions or need further assistance, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>
            Academic Registrar<br>
            Business Management School</p>
        </div>
        <div class="footer">
            <p>Business Management School | 591, Galle Road, Colombo 06, Sri Lanka | Tel: +94 xxxxxxxxx</p>
            <p>This email and any attachments are confidential and may also be privileged. If you are not the intended recipient, please delete all copies and notify the sender immediately.</p>
        </div>
    </div>
</body>
</html>
EOD;

    $mail->Body = $emailBody;

    // Create a plain text version of the email
    $mail->AltBody = "Dear $name,\n\nPlease find attached your offer letter for the $program program at Business Management School.\n\nThis offer letter contains important information about your program, including fees, duration, and deadlines. Please review it carefully.\n\nIf you have any questions or need further assistance, please don't hesitate to contact us.\n\nBest regards,\nAcademic Registrar\nBusiness Management School";

    // Send the email
    $mail->send();
    
    // Delete temporary file if it exists
    if (!empty($pdfData) && file_exists($tempFile)) {
        unlink($tempFile);
    }

    echo json_encode(['success' => true, 'message' => 'Email sent successfully with PDF attachment']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
}
?>
