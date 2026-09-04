<?php

// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pdf'])) {
    // Define the upload directory
    $uploadDir = 'payment_rcpt_uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Get the original file extension
    $fileExtension = pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION);

    // Generate a unique filename using timestamp and a unique ID
    $uniqueFilename = time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadFile = $uploadDir . $uniqueFilename;

    // Move the uploaded PDF to the desired folder
    if (move_uploaded_file($_FILES['pdf']['tmp_name'], $uploadFile)) {
        // Get additional data from the POST request
        $studentName = $_POST['student_name'] ?? 'Student Name Not Available';
        $studentRegID = $_POST['student_regID'] ?? 'N/A';
        $programBatch = $_POST['program_batch'] ?? 'N/A';
        $paymentMethod = $_POST['payment_method'] ?? 'Not selected';
        $studentBmsEmail = $_POST['student_bms_email'] ?? 'N/A'; // Changed to match the FormData key

        // Validate email address
        if (!filter_var($studentBmsEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
            exit;
        }

        try {
            // Now send the PDF file via email using PHPMailer
            $mail = new PHPMailer(true); // Enable exceptions

            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@bms.ac.lk';
            $mail->Password = 'Lox51527';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('noreply@bms.ac.lk', 'Business Management School');
            $mail->addAddress($studentBmsEmail);
            $mail->addAttachment($uploadFile);

            // Set email format to HTML
            $mail->isHTML(true);
            $mail->Subject = 'Payment Receipt - Business Management School';

            // Email body using a table format
            // $mail->Body = '
            // <html>
            // <head>
            //     <style>
            //         .header-image {
            //             width: 30%;
            //             max-width: 600px;
            //             height: auto;
            //             margin: auto;
            //             margin-bottom: 20px;
            //         }
            //         table {
            //             width: 100%;
            //             border-collapse: collapse;
            //             margin-top: 20px;
            //         }
            //         table, th, td {
            //             border: 1px solid #ddd;
            //             padding: 10px;
            //             text-align: left;
            //         }
            //         th {
            //             background-color: #f2f2f2;
            //             color: #333;
            //         }
            //         td {
            //             background-color: #f9f9f9;
            //         }
            //         h2 {
            //             color: #333;
            //         }
            //     </style>
            // </head>
            // <body>
            //     <img src="https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png" alt="BMS Logo" class="header-image">
            //     <h2>Thank you for your payment! Please find the receipt details below:</h2>
            //     <table>
            //         <tr>
            //             <th>Student Name</th>
            //             <td>' . htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') . '</td>
            //         </tr>
            //         <tr>
            //             <th>Student Registration ID</th>
            //             <td>' . htmlspecialchars($studentRegID, ENT_QUOTES, 'UTF-8') . '</td>
            //         </tr>
            //         <tr>
            //             <th>Program Batch</th>
            //             <td>' . htmlspecialchars($programBatch, ENT_QUOTES, 'UTF-8') . '</td>
            //         </tr>
            //         <tr>
            //             <th>Payment Method</th>
            //             <td>' . htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') . '</td>
            //         </tr>
            //     </table>
            //     <p>Please find your payment receipt attached to this email.</p>
            //     <p>If you have any questions or need further assistance, feel free to contact us.</p>
            //     <p>Best regards,<br>Business Management School,<br>591 Galle Rd, Colombo 00600</p>
            // </body>
            // </html>';



            $mail->Body = '
<html>
<head>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #333333;
      margin: 0;
      padding: 0;
      background-color: #f6f6f6;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background-color: #ffffff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    .header {
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 1px solid #dddddd;
    }
    .header img {
      max-width: 200px;
      margin-bottom: 10px;
    }
    h2 {
      color: #0a0a23;
      margin-top: 30px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      font-size: 15px;
    }
    th, td {
      text-align: left;
      padding: 12px;
      border-bottom: 1px solid #eeeeee;
    }
    th {
      background-color: #f2f2f2;
      color: #0a0a23;
    }
    .footer {
      margin-top: 30px;
      font-size: 14px;
      color: #555555;
      text-align: center;
      border-top: 1px solid #dddddd;
      padding-top: 20px;
    }
    .footer p {
      margin: 5px 0;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png" alt="BMS Logo">
    </div>
    <h2>Payment Receipt Confirmation</h2>
    <p>Dear ' . htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') . ',</p>
    <p>Thank you for your payment. Please find the details of your transaction below and the official receipt attached to this email.</p>
    
    <table>
      <tr>
        <th>Student Name</th>
        <td>' . htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
      <tr>
        <th>Registration ID</th>
        <td>' . htmlspecialchars($studentRegID, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
      <tr>
        <th>Program / Batch</th>
        <td>' . htmlspecialchars($programBatch, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
      <tr>
        <th>Payment Method</th>
        <td>' . htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') . '</td>
      </tr>
    </table>

    <p>If you have any questions or need further assistance, please do not hesitate to contact us.</p>

    <div class="footer">
      <p>Business Management School</p>
      <p>591 Galle Road, Colombo 00600, Sri Lanka</p>
      <p><a href="mailto:info@bms.ac.lk">info@bms.ac.lk</a> | <a href="https://www.bms.ac.lk">www.bms.ac.lk</a></p>
    </div>
  </div>
</body>
</html>';


            // Send the email
            if ($mail->send()) {
                echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email sending failed: ' . $mail->ErrorInfo]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'File upload failed!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request!']);
}
