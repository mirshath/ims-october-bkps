<?php

session_start();

$admin_name = $_SESSION['admin_name'] ?? 'Unknown Admin';
$admin_id   = intval($_SESSION['admin_id'] ?? 0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("../database/connection.php");
require '../vendor/autoload.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {

    // =========================================================
    // 1. COLLECT POST DATA
    // =========================================================

    $student_id         = trim($_POST['student_id'] ?? '');
    $program_name       = trim($_POST['program_name'] ?? '');
    $graduation_fee     = floatval($_POST['graduation_fee'] ?? 0);
    $free_ticket_count  = intval($_POST['free_ticket'] ?? 0);
    $extra_ticket_count = intval($_POST['extra_tickets'] ?? 0);
    $extra_ticket_fee   = floatval($_POST['extra_ticket_fee'] ?? 0);
    $total_amount       = floatval($_POST['total_amount'] ?? 0);
    $receipt_number      = trim($_POST['receipt_number'] ?? '');
    $pdf_data            = $_POST['pdf_data'] ?? '';

    // =========================================================
    // 2. BASIC VALIDATION
    // =========================================================

    if (
        empty($student_id) ||
        empty($program_name) ||
        $total_amount <= 0 ||
        empty($pdf_data) ||
        empty($receipt_number)
    ) {
        throw new Exception('Missing or invalid payment data.');
    }

    // =========================================================
    // 3. VERIFY STUDENT
    // =========================================================

    $stmt = $conn->prepare("
        SELECT *
        FROM registered_students
        WHERE student_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new Exception(
            'Student query preparation failed: ' . $conn->error
        );
    }

    $stmt->bind_param("s", $student_id);

    if (!$stmt->execute()) {
        throw new Exception(
            'Student query failed: ' . $stmt->error
        );
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Student not found.');
    }

    $student = $result->fetch_assoc();

    $full_name       = $student['name_in_full'] ?? '';
    $university_email = trim($student['email_address'] ?? '');

    $stmt->close();

    if (empty($university_email)) {
        throw new Exception('Student email address not found.');
    }

    // =========================================================
    // 4. CALCULATE EXTRA TICKET TOTAL
    // =========================================================

    $extra_ticket_total =
        $extra_ticket_count * $extra_ticket_fee;

    // =========================================================
    // 5. PREPARE PDF
    // =========================================================

    $email_date = date('Y-m-d_H-i-s');

    $pdf_name =
        "Receipt-" .
        $receipt_number .
        "-" .
        $student_id .
        "-" .
        $email_date .
        ".pdf";

    // Remove possible data URL prefix
    if (strpos($pdf_data, ',') !== false) {
        $pdf_data = substr($pdf_data, strpos($pdf_data, ',') + 1);
    }

    $pdf_binary = base64_decode($pdf_data, true);

    if ($pdf_binary === false) {
        throw new Exception('Invalid PDF data.');
    }

    // =========================================================
    // 6. SAVE PDF
    // =========================================================

    $folder = __DIR__ . '/saved_receipts/';

    if (!is_dir($folder)) {

        if (!mkdir($folder, 0755, true)) {
            throw new Exception(
                'Unable to create saved_receipts directory.'
            );
        }
    }

    if (!is_writable($folder)) {
        throw new Exception(
            'saved_receipts directory is not writable.'
        );
    }

    $pdf_path = $folder . $pdf_name;

    $pdf_saved = file_put_contents(
        $pdf_path,
        $pdf_binary
    );

    if ($pdf_saved === false) {
        throw new Exception(
            'Failed to save PDF receipt.'
        );
    }

    // =========================================================
    // 7. INSERT PAYMENT RECORD
    // =========================================================
    // IMPORTANT:
    // Payment is stored BEFORE sending email.
    // Therefore an email failure will NOT lose the payment.

    $stmt2 = $conn->prepare("
        INSERT INTO payment_records
        (
            student_id,
            program_name,
            graduation_fee,
            free_ticket_count,
            extra_ticket_count,
            extra_ticket_fee,
            total_amount,
            receipt_number,
            payment_date,
            created_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ");

    if (!$stmt2) {
        throw new Exception(
            'Payment INSERT preparation failed: ' . $conn->error
        );
    }

    /*
     * Variables:
     *
     * s = student_id
     * s = program_name
     * d = graduation_fee
     * i = free_ticket_count
     * i = extra_ticket_count
     * d = extra_ticket_total
     * d = total_amount
     * s = receipt_number
     * s = admin_name
     */

    $stmt2->bind_param(
        "ssdiiddss",
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
        throw new Exception(
            'Failed to record payment: ' . $stmt2->error
        );
    }

    $payment_record_id = $stmt2->insert_id;

    $stmt2->close();

    // =========================================================
    // 8. UPDATE STUDENT PAYMENT STATUS
    // =========================================================

    $stmt3 = $conn->prepare("
        UPDATE registered_students
        SET graduation_payment_status = 'paid'
        WHERE student_id = ?
    ");

    if (!$stmt3) {
        throw new Exception(
            'Payment status query preparation failed: ' .
                $conn->error
        );
    }

    $stmt3->bind_param("s", $student_id);

    if (!$stmt3->execute()) {
        throw new Exception(
            'Failed to update payment status: ' .
                $stmt3->error
        );
    }

    $stmt3->close();

    // =========================================================
    // 9. GET SEAT / SESSION INFORMATION
    // =========================================================

    $seat_no     = null;
    $session_time = null;

    $stmtSeat = $conn->prepare("
        SELECT seat_no, session_time
        FROM bulk_data_table
        WHERE student_id = ?
        LIMIT 1
    ");

    if ($stmtSeat) {

        $stmtSeat->bind_param("s", $student_id);

        if ($stmtSeat->execute()) {

            $seatRes = $stmtSeat->get_result();

            if ($seatRes && $seatRes->num_rows > 0) {

                $seatRow = $seatRes->fetch_assoc();

                $seat_no =
                    $seatRow['seat_no'] ?? null;

                $session_time =
                    $seatRow['session_time'] ?? null;
            }
        }

        $stmtSeat->close();
    }

    // =========================================================
    // 10. SEND EMAIL
    // =========================================================

    $email_sent = false;
    $email_error = null;

    try {

        $mail = new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host       = 'smtp.office365.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bmsgraduation@bms.ac.lk';

        // IMPORTANT:
        // Put your actual password here.
        // Better: load it from .env/config instead.
        $mail->Password   = 'YOUR_MICROSOFT_365_PASSWORD';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Optional but useful
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            'bmsgraduation@bms.ac.lk',
            'BMS Finance Department'
        );

        $mail->addAddress(
            $university_email,
            $full_name
        );

        $mail->isHTML(true);

        $mail->Subject =
            "Graduation Payment Receipt (Ref: " .
            $receipt_number .
            ") - " .
            $program_name;

        // =====================================================
        // EMAIL BODY
        // =====================================================

        $safe_name =
            htmlspecialchars(
                $full_name,
                ENT_QUOTES,
                'UTF-8'
            );

        $safe_student_id =
            htmlspecialchars(
                $student_id,
                ENT_QUOTES,
                'UTF-8'
            );

        $safe_program =
            htmlspecialchars(
                $program_name,
                ENT_QUOTES,
                'UTF-8'
            );

        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Graduation Payment Receipt</title>
        </head>

        <body style="
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        ">

            <div style="
                max-width: 700px;
                margin: 20px auto;
                background: #ffffff;
                border-radius: 8px;
                overflow: hidden;
            ">

                <div style="
                    text-align: center;
                    background: #667eea;
                    padding: 25px;
                    color: #ffffff;
                ">

                    <h2 style="margin:0;">
                        Graduation Payment Receipt
                    </h2>

                    <p>
                        Payment Successful
                    </p>

                </div>

                <div style="padding:25px;">

                    <p>
                        Dear <strong>' . $safe_name . '</strong>,
                    </p>

                    <p>
                        Thank you for your payment for
                        <strong>AWARD CEREMONY 2026</strong>.
                    </p>

                    <p>
                        Please find your payment receipt attached
                        to this email.
                    </p>

                    <p>
                        Please provide your QR code to collect
                        your invitation.
                    </p>

                    <table style="
                        width:100%;
                        border-collapse:collapse;
                        margin-top:20px;
                    ">

                        <tr>
                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                <strong>Student ID</strong>
                            </td>

                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                ' . $safe_student_id . '
                            </td>
                        </tr>

                        <tr>
                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                <strong>Program</strong>
                            </td>

                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                ' . $safe_program . '
                            </td>
                        </tr>

                        <tr>
                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                <strong>Free Tickets</strong>
                            </td>

                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                ' . $free_ticket_count . '
                            </td>
                        </tr>';

        if ($extra_ticket_count > 0) {

            $body .= '
                        <tr>
                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                <strong>Extra Tickets</strong>
                            </td>

                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                ' . $extra_ticket_count . '
                            </td>
                        </tr>';
        }

        $body .= '
                        <tr>
                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                <strong>Total Paid</strong>
                            </td>

                            <td style="
                                padding:10px;
                                border:1px solid #ddd;
                            ">
                                LKR ' .
            number_format(
                $total_amount,
                2
            ) .
            '
                            </td>
                        </tr>

                    </table>

                    <p style="margin-top:25px;">

                        Best regards,<br>

                        <strong>
                            BMS Finance Department
                        </strong><br>

                        BMS Graduation Team 2026

                    </p>

                </div>

            </div>

        </body>
        </html>';

        $mail->Body = $body;

        $mail->addStringAttachment(
            $pdf_binary,
            $pdf_name,
            PHPMailer::ENCODING_BASE64,
            'application/pdf'
        );

        // SEND
        $mail->send();

        $email_sent = true;
    } catch (Exception $mailException) {

        $email_sent = false;

        $email_error =
            $mailException->getMessage();

        // Log the actual PHPMailer error
        error_log(
            'Graduation email failed for student ' .
                $student_id .
                ': ' .
                $email_error
        );

        if (isset($mail) && !empty($mail->ErrorInfo)) {

            error_log(
                'PHPMailer ErrorInfo: ' .
                    $mail->ErrorInfo
            );
        }
    }

    // =========================================================
    // 11. INSERT EMAIL LOG
    // =========================================================

    $email_status =
        $email_sent ? 'sent' : 'failed';

    $log_error =
        $email_sent ? null : $email_error;

    $logStmt = $conn->prepare("
        INSERT INTO email_log
        (
            student_id,
            name_in_full,
            email_address,
            program_name,
            seat_no,
            session_time,
            email_type,
            status,
            error_message,
            sent_by
        )
        VALUES (?, ?, ?, ?, ?, ?, 'single', ?, ?, ?)
    ");

    if (!$logStmt) {

        error_log(
            'Email log preparation failed: ' .
                $conn->error
        );
    } else {

        $logStmt->bind_param(
            "ssssssssi",
            $student_id,
            $full_name,
            $university_email,
            $program_name,
            $seat_no,
            $session_time,
            $email_status,
            $log_error,
            $admin_id
        );

        if (!$logStmt->execute()) {

            error_log(
                'Email log insert failed: ' .
                    $logStmt->error
            );
        }

        $logStmt->close();
    }

    // =========================================================
    // 12. RETURN JSON
    // =========================================================

    if ($email_sent) {

        echo json_encode([
            'success' => true,
            'payment_saved' => true,
            'email_sent' => true,
            'payment_record_id' => $payment_record_id,
            'pdf_path' => $pdf_path,
            'pdf_name' => $pdf_name,
            'message' =>
            'Payment recorded and email sent successfully.'
        ]);
    } else {

        // Payment is still successful.
        // Only email failed.

        echo json_encode([
            'success' => true,
            'payment_saved' => true,
            'email_sent' => false,
            'payment_record_id' => $payment_record_id,
            'pdf_path' => $pdf_path,
            'pdf_name' => $pdf_name,
            'message' =>
            'Payment recorded successfully, but the email could not be sent.',
            'email_error' => $email_error
        ]);
    }

    exit;
} catch (Exception $e) {

    error_log(
        'Graduation payment error: ' .
            $e->getMessage()
    );

    echo json_encode([
        'success' => false,
        'payment_saved' => false,
        'email_sent' => false,
        'message' => $e->getMessage()
    ]);

    exit;
}
