<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // =========================================================
    // SMTP CONFIGURATION
    // =========================================================

    $mail->isSMTP();

    // Example: Microsoft 365 / Outlook SMTP
   
    // $mail->Host       = 'smtp.office365.com';
    
  
    // $mail->SMTPAuth   = true;
    // $mail->Username   = 'noreply@bms.ac.lk';
    // $mail->Password   = 'gqfxxrphvjnlmwrn';
    
     $mail->Host       = 'mail.hazz.lk';
      $mail->SMTPAuth   = true;
   $mail->Username   = 'info@hazz.lk'; 
    $mail->Password   = 'Hazz@2025'; 
    

    // TLS encryption
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;




   



    // =========================================================
    // SENDER
    // =========================================================

    $mail->setFrom(
        'info@hazz.lk',
        'Business Management School'
    );


    // =========================================================
    // RECIPIENT
    // =========================================================

    $mail->addAddress(
        'yournumplz@gmail.com',
        'Mirshath Mmm TESTING'
    );


    // =========================================================
    // EMAIL CONTENT
    // =========================================================

    $mail->isHTML(true);

    $mail->Subject = 'Test Email from PHP Mailer';

    $mail->Body = '
        <html>
        <body style="font-family: Arial, sans-serif;">

            <h2 style="color:#333;">Test Email</h2>

            <p>Hello,</p>

            <p>
                This is a test email sent using
                <strong>PHPMailer</strong>.
            </p>

            <p>
                If you received this email successfully,
                the SMTP configuration is working correctly.
            </p>

            <br>

            <p>
                Regards,<br>
                <strong>Your System</strong>
            </p>

        </body>
        </html>
    ';

    // Plain-text fallback
    $mail->AltBody =
        "Hello,\n\n" .
        "This is a test email sent using PHPMailer.\n\n" .
        "Regards,\n" .
        "Your System";


    // =========================================================
    // SEND EMAIL
    // =========================================================

    if ($mail->send()) {

        echo '<div style="
            padding:15px;
            background:#d4edda;
            color:#155724;
            border:1px solid #c3e6cb;
            border-radius:5px;
            font-family:Arial;
        ">
            <strong>Success!</strong><br>
            Email sent successfully.
        </div>';
    } else {

        echo '<div style="
            padding:15px;
            background:#f8d7da;
            color:#721c24;
            border:1px solid #f5c6cb;
            border-radius:5px;
            font-family:Arial;
        ">
            <strong>Failed!</strong><br>
            Email could not be sent.
        </div>';
    }
} catch (Exception $e) {

    echo '<div style="
        padding:15px;
        background:#f8d7da;
        color:#721c24;
        border:1px solid #f5c6cb;
        border-radius:5px;
        font-family:Arial;
    ">
        <strong>Mailer Error:</strong><br>'
        . htmlspecialchars($mail->ErrorInfo) .
        '</div>';
}
