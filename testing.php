<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Loads PHPMailer classes automatically

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug  = 2;  // Debug: 0 = off, 2 = client/server msgs
    $mail->isSMTP();        
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@bms.ac.lk'; 
    $mail->Password   = 'gqfxxrphvjnlmwrn'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    $mail->Port       = 587;
    
    // $mail->SMTPDebug  = 2;  // Debug: 0 = off, 2 = client/server msgs
    // $mail->isSMTP();        
    // $mail->Host       = 'mail.hazz.lk';
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'info@hazz.lk'; 
    // $mail->Password   = 'Hazz@2025'; 
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    // $mail->Port       = 587;
    
    //     $mail->SMTPDebug  = 2;  // Debug: 0 = off, 2 = client/server msgs
    // $mail->isSMTP();        
    // $mail->Host       = 'smtp.gmail.com';
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'mirshath.mmm@gmail.com'; 
    // $mail->Password   = 'elmhqrfmiyyixitg'; 
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
    // $mail->Port       = 587;

    // Optional: stronger SSL checks
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ]
    ];

    // Recipients
    $mail->setFrom('noreply@bms.ac.lk', 'BMS IMS');
    // $mail->setFrom('info@hazz.lk', 'BMS IMS');
    //  $mail->setFrom('mirshath.mmm@gmail.com', 'BMS IMS');
    $mail->addAddress('webmaster@bms.ac.lk', 'Student Name'); 

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email via Office365';
    $mail->Body    = '<h3>This is a test email</h3><p>Sent from PHPMailer v6.9.1 with Office365.</p>';
    $mail->AltBody = 'This is a plain-text version for non-HTML clients';

    $mail->send();
    echo 'Message sent successfully!';
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
