<?php
// CRITICAL: No spaces or lines before this opening tag
// Start output buffering to catch any stray characters
ob_start();
session_start();


// Disable error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once "../database/connection.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$response = ['status' => 'error', 'message' => 'Unknown process error'];

try {

    /* ================= SESSION ================= */
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized");
    }
    $current_user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? '';

    if ($role === 'super_admin') {
        $tutor_id = (int)($_POST['tutor_id'] ?? 0);
        if (!$tutor_id) {
            throw new Exception("Tutor not selected");
        }
    } else {
        if ($role !== 'lecture') {
            throw new Exception("Unauthorized");
        }
        $tutor_id = (int)$_SESSION['user_id'];
    }

    /* ================= INPUT ================= */
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $reason  = trim($_POST['reason'] ?? '');

    if (!$slot_id || !$action) {
        throw new Exception("Invalid request");
    }

    /* ================= NORMALIZE ACTION ================= */
    $actionMap = [
        'cancel' => 'cancel_pending',
        'deny'   => 'denied'
    ];

    if (isset($actionMap[$action])) {
        $action = $actionMap[$action];
    }

    if (!in_array($action, ['add_student', 'cancel_pending', 'denied', 'cancelled'])) {
        throw new Exception("Invalid action");
    }

    /* ================= GET SLOT ================= */
    $info = $conn->prepare("
        SELECT t.slot_date, t.start_time, t.end_time, t.student_id,
               s.first_name, s.last_name, s.bms_email, a.username AS Lecturer_Name, ts.session_name AS session_name
        FROM tutor_time_slots t
        LEFT JOIN students s ON s.student_code = t.student_id

        LEFT JOIN admin a ON a.id = t.tutor_id
        LEFT JOIN tutor_session_allocation tsa ON tsa.allocation_id = t.allocation_id
        LEFT JOIN tutor_session ts ON ts.session_id = tsa.session_id

        WHERE t.id=? AND t.tutor_id=?
    ");
    $info->bind_param("ii", $slot_id, $tutor_id);
    $info->execute();
    $slot = $info->get_result()->fetch_assoc();

    if (!$slot) throw new Exception("Slot not found");

    $hasStudent = !empty($slot['student_id']);

    $start_time = date('H:i', strtotime($slot['start_time']));
    $end_time   = date('H:i', strtotime($slot['end_time']));
    $slot_date  = date('Y-m-d', strtotime($slot['slot_date']));

    /* ================= ADD STUDENT ================= */
    /* ================= ADD STUDENT ================= */
    if ($action === 'add_student') {

        $student_id = (int)($_POST['student_id'] ?? 0);
        if (!$student_id) throw new Exception("Student not selected");

        // 1. GET STUDENT DETAILS
        $studentStmt = $conn->prepare("SELECT first_name, last_name, bms_email FROM students WHERE student_code=?");
        $studentStmt->bind_param("i", $student_id);
        $studentStmt->execute();
        $student = $studentStmt->get_result()->fetch_assoc();

        if (!$student) throw new Exception("Student not found");

        // 2. UPDATE SLOT AND CHECK IF IT ACTUALLY CHANGED
        // We add "AND (student_id IS NULL OR student_id != ?)" to ensure we only update if needed
        $stmt = $conn->prepare("
            UPDATE tutor_time_slots 
            SET student_id=?, status='Accepted', created_by=?
            WHERE id=? AND tutor_id=? AND (student_id IS NULL OR student_id != ?)
        ");
        $stmt->bind_param("iiiii", $student_id, $current_user_id, $slot_id, $tutor_id, $student_id);
        $stmt->execute();

        // 3. ONLY SEND EMAIL IF THE DATABASE WAS UPDATED JUST NOW
        if ($stmt->affected_rows > 0) {
            sendEmail(
                $student['bms_email'],
                "Session Allocation",
                $student['first_name'],
                $student['last_name'],
                "New Session Scheduled by Tutor ",
                "A session has been scheduled for you by your tutor. Please find the details below:",
                $slot_date, $start_time, $end_time, $reason,

                $slot['Lecturer_Name'], 
                $slot['session_name'],
                "Please ensure your availability for this session."
            );
            $response = ['status' => 'success', 'message' => 'Student Added'];
        } else {
            // If affected_rows is 0, it means the student was already there.
            $response = ['status' => 'success', 'message' => 'Student Booked'];
        }
    }

    /* ================= CANCEL BEFORE ================= */
    elseif ($action === 'cancel_pending') {

        $stmt = $conn->prepare("
            UPDATE tutor_time_slots
            SET is_hidden=1, created_by=?
            WHERE id=? AND tutor_id=?
        ");
        $stmt->bind_param("iii", $current_user_id, $slot_id, $tutor_id);
        $stmt->execute();

        $response = ['status' => 'success', 'message' => 'Slot cancelled'];
    }

    /* ================= DENY ================= */
    elseif ($action === 'denied' && $hasStudent) {

        $stmt = $conn->prepare("
            UPDATE tutor_time_slots
            SET student_id=NULL, status='Denied', created_by=?
            WHERE id=? AND tutor_id=?
        ");
        $stmt->bind_param("iii",$current_user_id, $slot_id, $tutor_id);
        $stmt->execute();

        sendEmail(
            $slot['bms_email'],
            "Session Request Declined", // body headding
            $slot['first_name'],
            $slot['last_name'],
            "Session Request Declined", //subject
            "We regret to inform you that your session request has been declined by the tutor.",
            $slot_date, $start_time, $end_time, $reason,

                $slot['Lecturer_Name'], 
                $slot['session_name'],
                "You may request a new session at a different time based on tutor availability."
        );

        $response = ['status' => 'success', 'message' => 'Student denied'];
    }

    /* ================= CANCEL AFTER ================= */
    elseif ($action === 'cancelled' && $hasStudent) {

        $stmt = $conn->prepare("
            UPDATE tutor_time_slots
            SET student_id=NULL, is_hidden=1, created_by=?
            WHERE id=? AND tutor_id=?
        ");
        $stmt->bind_param("iii",$current_user_id, $slot_id, $tutor_id);
        $stmt->execute();

        sendEmail(
            $slot['bms_email'],
            " Session Cancellation",
            $slot['first_name'],
            $slot['last_name'],
            "Booking Cancelled",
            "Your scheduled session has been cancelled by the tutor.",
            $slot_date, $start_time, $end_time, $reason,

                $slot['Lecturer_Name'], 
                $slot['session_name'],
                "We apologise for any inconvenience caused. You may reschedule the session at your convenience."
        );

        $response = ['status' => 'success', 'message' => 'Booking cancelled'];
    }

} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

// FIX: Clean the buffer of any accidental whitespace/notices, then echo JSON
// At the very bottom, after the catch block:
ob_end_clean(); // Clears any accidental white spaces that cause "Invalid Data"
echo json_encode($response);
exit;


/* ================= EMAIL FUNCTION (UNCHANGED) ================= */
function sendEmail($to, $headText, $fname, $lname, $subject, $messageText, $date, $start, $end, $reason, $lecturerName, $sessionName, $note) {

    if (empty($to)) return;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@bms.ac.lk';
        $mail->Password = 'Lox51527';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@bms.ac.lk', 'BMS Session');
        $mail->addAddress($to, $fname);

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $reasonHtml = (!empty($reason)) ? "<p><strong>Reason:</strong> ".htmlspecialchars($reason)."</p>" : "";

        $mail->Body = "
        <head>
        <style>
            body {font-family:Arial,sans-serif; margin:0; padding:0; background-color:#f4f4f4;}
            .card {background-color:#fff; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); overflow:hidden; max-width:600px; margin:auto; border:2px solid #b1b5c0;}
            .header {background-color:#fefefe; padding:20px; text-align:center;}
            .headerx {background-color:#042d5c; color:#fff; padding:20px; text-align:center;}
            .content {padding:30px;}
            table {border:1.5px solid #a7a4a4; border-radius:10px; width:60%; border-collapse:separate; border-spacing:0;}
            table td {padding:10px; border-bottom:0.5px solid #a7a4a4;}
            table tr:last-child td {border-bottom:none;}
            .footer {background-color:#042d5c; color:#fff; padding:15px; text-align:center; font-size:12px;}
        </style>
        </head>
        <body>
        <div style='background-color:#fff; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); overflow:hidden; max-width:600px; margin:auto; border:2px solid #b1b5c0;'>
            <div style='background-color:#fefefe; padding:20px; text-align:center;'>
                <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' style='width:150px;'>
            </div>
            <div style='background-color:#042d5c; color:#fff; padding:20px; text-align:center;'>
                <h2 style='margin:10px 0 0 0; font-size:28px;'>{$headText}</h2>
            </div>
            <div style='padding:30px;'>
                <p>Dear <strong>" . htmlspecialchars($fname . " " . $lname) . "</strong>,</p>
                <p>{$messageText}</p>
                <table style='border:1.5px solid #a7a4a400; border-radius:0px; width:100%; hight: auto; border-collapse:separate; border-spacing:0;'>
                    <tr>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 25px;'>Session</td>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 0.4%;'>:</td>
                        <td style='padding:10px;'>" . htmlspecialchars($sessionName) . "</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 25%;;'>Booking Date</td>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 0.4%;'>:</td>
                        <td style='padding:10px;'>{$date}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 25%;'>Booking Time</td>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 0.4%;'>:</td>
                        <td style='padding:10px;'>{$start} - {$end}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 25%;'>Lecturer</td>
                        <td style='padding:10px; font-weight:bold; color:#042d5c; width: 0.4%;'>:</td>
                        <td style='padding:10px;'>" . htmlspecialchars($lecturerName) . "</td>
                    </tr>
                </table>
                {$reasonHtml}

                <p><i>{$note}</i></p>

                <p>Thank you,<br><strong>BMS Campus</strong></p>
            </div>
            <div style='background-color:#042d5c; color:#fff; padding:15px; text-align:center; font-size:12px;'>
                &copy; BMS. All rights reserved.
            </div>
        </div>
        </body>";

       $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
    }
} // <--- END OF FUNCTION

/* --- FINAL OUTPUT SECTION (Must be here at the very end) --- */
ob_end_clean();          
echo json_encode($response); 
exit;