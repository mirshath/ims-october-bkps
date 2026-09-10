<?php

session_start();
$admin_name = $_SESSION['admin_name'] ?? 'Unknown Admin';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("../database/connection.php");
require '../vendor/autoload.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // 1️⃣ Collect POST data
    $student_id = $_POST['student_id'] ?? '';
    $program_name = $_POST['program_name'] ?? '';
    $graduation_fee = floatval($_POST['graduation_fee'] ?? 0);
    $free_ticket_count = intval($_POST['free_ticket'] ?? 0);
    $extra_ticket_count = intval($_POST['extra_tickets'] ?? 0);
    $extra_ticket_fee = floatval($_POST['extra_ticket_fee'] ?? 0);
    $total_amount = floatval($_POST['total_amount'] ?? 0);
    $receipt_number = $_POST['receipt_number'] ?? '';
    $pdf_data = $_POST['pdf_data'] ?? '';

    if (!$student_id || !$program_name || $total_amount <= 0 || !$pdf_data) {
        throw new Exception('Missing or invalid payment data.');
    }

    // 2️⃣ Verify student
    $stmt = $conn->prepare("SELECT * FROM registered_students WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Student not found.');
    }

    $student = $result->fetch_assoc();
    $full_name = $student['name_in_full'];
    $university_email = $student['email_address'];

    if (!$university_email) {
        throw new Exception('Student email not found.');
    }
    
    $email_date = date('Y-m-d_H-i-s');
    $pdf_name = "Receipt-{$receipt_number}-{$student_id}-{$email_date}.pdf";


    // 4️⃣ Insert payment record
    // $extra_ticket_total = $extra_ticket_count * $extra_ticket_fee;

    // $stmt2 = $conn->prepare("
    //     INSERT INTO payment_records 
    //     (student_id, program_name, graduation_fee, free_ticket_count, extra_ticket_count, extra_ticket_fee, total_amount, receipt_number, payment_date)
    //     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    // ");
    // $stmt2->bind_param(
    //     "ssdiidds",
    //     $student_id,
    //     $program_name,
    //     $graduation_fee,
    //     $free_ticket_count,
    //     $extra_ticket_count,
    //     $extra_ticket_total,
    //     $total_amount,
    //     $receipt_number,
    //     $admin_name
    // );
    // if (!$stmt2->execute()) {
    //     throw new Exception('Failed to record payment: ' . $stmt2->error);
    // }



    // 4️⃣ Insert payment record
    $extra_ticket_total = $extra_ticket_count * $extra_ticket_fee;

//     $stmt2 = $conn->prepare("
//     INSERT INTO payment_records 
//     (student_id, program_name, graduation_fee, free_ticket_count, extra_ticket_count, extra_ticket_fee, total_amount, receipt_number, payment_date, created_by)
//     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
// ");
//     $stmt2->bind_param(
//         "ssdiiidss",
//         $student_id,
//         $program_name,
//         $graduation_fee,
//         $free_ticket_count,
//         $extra_ticket_count,
//         $extra_ticket_total,
//         $total_amount,
//         $receipt_number,
//         $admin_name
//     );
//     if (!$stmt2->execute()) {
//         throw new Exception('Failed to record payment: ' . $stmt2->error);
//     }

    // -----------------------------
    // 5️⃣ Update graduation payment status
    // -----------------------------
    // $stmt3 = $conn->prepare("UPDATE registered_students SET graduation_payment_status='paid' WHERE student_id = ?");
    // $stmt3->bind_param("s", $student_id);
    // $stmt3->execute();

    // -----------------------------
    // 6️⃣ Send Email
    // -----------------------------
    $mail = new PHPMailer(true);
    $mail->SMTPDebug  = 2;
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bmsgraduation@bms.ac.lk';
    $mail->Password   = 'vspcktnnkhtwhxgr';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;


    $mail->setFrom('bmsgraduation@bms.ac.lk', 'BMS Finance Department');
    $mail->addAddress($university_email, $full_name);

    $mail->Subject = "Graduation Payment Receipt (Ref: $receipt_number) - $program_name";

    $body = '
    <html>
    <body style="font-family: Arial, sans-serif; color:#333;">
        <div style="text-align:center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding:20px; color:#fff;">
            <h2>Graduation Payment Receipt</h2>
            <p>Payment Successful</p>
        </div>
        <div style="padding:20px;">
            <p>Dear <strong>' . $full_name . '</strong>,</p>
            <p>Thank you for your payment for AWARD CEREMONY 2026. Please find attached receipt.</p>
            <p>Please provide your QR code to collect your invitation.</p>
            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>Student ID</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">' . $student_id . '</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>Program</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">' . $program_name . '</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>Free Tickets</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">' . $free_ticket_count . '</td>
                </tr>'
        . ($extra_ticket_count > 0 ? '<tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>Extra Tickets</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">' . $extra_ticket_count . '</td>
                </tr>' : '') .
        '<tr>
                    <td style="padding:8px; border:1px solid #ddd;"><strong>Total Paid</strong></td>
                    <td style="padding:8px; border:1px solid #ddd;">LKR ' . $total_amount . '</td>
                </tr>
            </table>
            <p style="margin-top:20px;">Best regards,<br>BMS Finance Department,<br>BMS Graduation Team 2026</p>
        </div>
    </body>
    </html>';

    $mail->isHTML(true);
    $mail->Body = $body;
    $mail->addStringAttachment(base64_decode($pdf_data), $pdf_name, 'base64', 'application/pdf');
    $mail->send();

    // Save PDF locally after successful email send
    $folder = __DIR__ . '/saved_receipts/';
    if (!is_dir($folder)) mkdir($folder, 0777, true);
    $pdf_path = $folder . $pdf_name;
    file_put_contents($pdf_path, base64_decode($pdf_data));
    $compressed_path = $folder . 'compressed_' . $pdf_name;
    if (shell_exec('which gs')) {
        $cmd = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile='$compressed_path' '$pdf_path'";
        shell_exec($cmd);
        if (file_exists($compressed_path)) {
            $pdf_path = $compressed_path;
        }
    }

    // 6️⃣b Store payment only after successful email send
    $stmt2 = $conn->prepare("
        INSERT INTO payment_records 
        (student_id, program_name, graduation_fee, free_ticket_count, extra_ticket_count, extra_ticket_fee, total_amount, receipt_number, payment_date, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt2->bind_param(
        "ssdiiidss",
        $student_id,
        $program_name,
        $graduation_fee,
        $free_ticket_count,
        $extra_ticket_count,
        $extra_ticket_total,
        $total_amount,
        $receipt_number,
        $admin_name
    );
    if (!$stmt2->execute()) {
        throw new Exception('Failed to record payment: ' . $stmt2->error);
    }
    $stmt2->close();

    // 7️⃣ Log email success into email_log
    $admin_id = intval($_SESSION['admin_id'] ?? 0);

    // Try to enrich with seat_no and session_time from bulk_data_table
    $seat_no = null;
    $session_time = null;
    if ($stmtSeat = $conn->prepare("SELECT seat_no, session_time FROM bulk_data_table WHERE student_id = ?")) {
        $stmtSeat->bind_param("s", $student_id);
        $stmtSeat->execute();
        $seatRes = $stmtSeat->get_result();
        if ($seatRes && $seatRes->num_rows > 0) {
            $seatRow = $seatRes->fetch_assoc();
            $seat_no = $seatRow['seat_no'] ?? null;
            $session_time = $seatRow['session_time'] ?? null;
        }
        $stmtSeat->close();
    }

    $logStmt = $conn->prepare("
        INSERT INTO email_log 
            (student_id, name_in_full, email_address, program_name, seat_no, session_time, email_type, status, error_message, sent_by) 
        VALUES (?, ?, ?, ?, ?, ?, 'single', 'sent', NULL, ?)
    ");
    $logStmt->bind_param(
        "ssssssi",
        $student_id,
        $full_name,
        $university_email,
        $program_name,
        $seat_no,
        $session_time,
        $admin_id
    );
    $logStmt->execute();
    $logStmt->close();

    // 7️⃣b Update graduation payment status (post email log)
    $stmt3 = $conn->prepare("UPDATE registered_students SET graduation_payment_status='paid' WHERE student_id = ?");
    $stmt3->bind_param("s", $student_id);
    $stmt3->execute();

    // -----------------------------
    // 8️⃣ Return JSON
    // -----------------------------
    echo json_encode([
        'success' => true,
        'pdf_path' => $pdf_path,
        'pdf_name' => $pdf_name
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
