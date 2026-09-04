<?php
ob_start();
session_start();

header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../database/connection.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

try {

    /* ================= ROLE HANDLING (UPDATED) ================= */

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Unauthorized");
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? '';

    // ✅ SUPER ADMIN SUPPORT
    if ($role === 'super_admin') {
        $tutor_id = (int)($_GET['tutor_id'] ?? $_POST['tutor_id'] ?? 0);
        $created_by = $_SESSION['user_id']; // admin who performs action
    } else {
        // ORIGINAL LOGIC
        if ($role !== 'lecture') {
            throw new Exception("Unauthorized");
        }

        $tutor_id = (int)$_SESSION['user_id'];
        $created_by = $tutor_id;
    }

    $type = $_GET['type'] ?? '';

    /* ---------------- NEW: GET PROGRAMS BY TUTOR (FOR SUPER ADMIN CONTEXT) ---------------- */
    if ($type === 'get_programs_by_tutor') {
        // We use the $tutor_id derived from the Role Handling logic above
        $stmt = $conn->prepare("
            SELECT DISTINCT tsa.program_code, pt.program_name 
            FROM tutor_session_allocation tsa 
            INNER JOIN program_table pt ON pt.program_code = tsa.program_code 
            WHERE tsa.tutor_id = ?
        ");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("i", $tutor_id);
        $stmt->execute();
        
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        
        ob_clean();
        echo json_encode($rows);
        exit;
    }

    /* ---------------- STUDENT LOADING FOR POPUP ---------------- */
    if ($type === 'students') {

        $programme_code = $_GET['program_id'] ?? '';
        $batch_id       = $_GET['batch_id'] ?? '';

        $sql = "
            SELECT 
                s.student_code, s.first_name, s.last_name, s.bms_email, 
                ap.programme_code, ap.batch_id
            FROM students s
            JOIN allocate_programme ap ON ap.student_code = s.student_code
            WHERE ap.programme_code = ? AND ap.batch_id = ?
            ORDER BY s.first_name ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);

        $stmt->bind_param("ii", $programme_code, $batch_id);
        $stmt->execute();

        $res = $stmt->get_result();
        $rows = [];

        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        ob_clean();
        echo json_encode($rows);
        exit;
    }

    /* ---------------- DROPDOWNS ---------------- */
    if ($type === 'batch' && isset($_GET['program_id'])) {
        $stmt = $conn->prepare("
            SELECT DISTINCT ba.id AS batch_id, ba.batch_name
            FROM tutor_session_allocation tsa
            JOIN batch_table ba ON tsa.batch_id = ba.id
            WHERE tsa.tutor_id=? AND tsa.program_code=?
            ORDER BY ba.batch_name
        ");
        $stmt->bind_param("is", $tutor_id, $_GET['program_id']);
        $stmt->execute();
        ob_clean();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($type === 'session' && isset($_GET['program_id'], $_GET['batch_id'])) {
        $stmt = $conn->prepare("
            SELECT DISTINCT ts.session_id, ts.session_name
            FROM tutor_session_allocation tsa
            JOIN tutor_session ts ON ts.session_id = tsa.session_id
            WHERE tsa.tutor_id=? AND tsa.program_code=? AND tsa.batch_id=?
            ORDER BY ts.session_name
        ");
        $stmt->bind_param("iii", $tutor_id, $_GET['program_id'], $_GET['batch_id']);
        $stmt->execute();
        ob_clean();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

/* ---------------- DENY / CANCEL ---------------- */
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $reason  = trim($_POST['reason'] ?? '');

    // Note: We use the $created_by we defined at the top of the script via Session
    // to prevent ID spoofing from POST data.

    if (!$slot_id || !in_array($action, ['add_student','denied', 'cancelled', 'cancel_pending'])) {
        throw new Exception("Invalid request");
    }

    /* -------- DUPLICATE REQUEST PROTECTION -------- */
    $dup = $conn->prepare("
        SELECT COUNT(*) AS cnt 
        FROM slot_change_log 
        WHERE slot_id = ? 
          AND tutor_id = ? 
          AND action_type = ?
          AND action_datetime >= (NOW() - INTERVAL 5 SECOND)
          AND created_by = ?
    ");
    $dup->bind_param("iisi", $slot_id, $tutor_id, $action, $created_by);
    $dup->execute();
    $exists = $dup->get_result()->fetch_assoc()['cnt'] ?? 0;

    if ($exists > 0) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Your progress has been completed']);
        exit;
    }

   
   /* ================= GET SLOT DATA ================= */
$info = $conn->prepare("
                SELECT t.slot_date, t.start_time, t.end_time, t.student_id,
                    s.first_name, s.last_name, s.bms_email, 
                    a.username AS Lecturer_Name, 
                    ts.session_name AS session_name
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

        // Setup variables for the email section later
        
        $lecturerName = $slot['Lecturer_Name'] ?? 'BMS Tutor';
        $sessionName  = $slot['session_name'] ?? 'Not Assigned';
        $slot_date    = date('Y-m-d', strtotime($slot['slot_date']));
        $start_time   = date('H:i', strtotime($slot['start_time']));
        $end_time     = date('H:i', strtotime($slot['end_time']));
        $messageText  = "";
        $headText     = "";
        $note         = "";
    // -----------------------------

    /* -------- EXECUTE ACTIONS -------- */
    /* -------- EXECUTE ACTIONS -------- */
$stmt = null; 

if ($action === 'add_student') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    if (!$student_id) throw new Exception("No student selected");
    
    // Check your DB: does tutor_time_slots have 'student_id', 'status', 'is_hidden', 'created_by'?
    $stmt = $conn->prepare("UPDATE tutor_time_slots SET student_id=?, status='Accepted', is_hidden=0, created_by=? WHERE id=? AND tutor_id=?");
    $stmt->bind_param("iiii", $student_id, $created_by, $slot_id, $tutor_id);
    $headText    = "" ;
    $messageText = "A session has been scheduled for you by your tutor.";
    $note        = "";

    // Refresh student info for email since it was empty in the initial $slot fetch
    $st = $conn->prepare("SELECT first_name, last_name, bms_email FROM students WHERE student_code=?");
    $st->bind_param("i", $student_id);
    $st->execute();
    $new_s = $st->get_result()->fetch_assoc();
    if($new_s) {
        $slot['bms_email'] = $new_s['bms_email'];
        $slot['first_name'] = $new_s['first_name'];
        $slot['last_name'] = $new_s['last_name'];
    }
        
    } elseif ($action === 'denied') {
        $stmt = $conn->prepare("UPDATE tutor_time_slots SET student_id=NULL, status='Denied', created_by=? WHERE id=? AND tutor_id=?");
        $stmt->bind_param("iii", $created_by, $slot_id, $tutor_id);
        $headText      = "Session Request Declined";
        $messageText = "We regret to inform you that your session request has been declined by the tutor.";
        $note        = "You may request a new session at a different time based on tutor availability. ";

    } elseif ($action === 'cancelled' || $action === 'cancel_pending') {
        $stmt = $conn->prepare("UPDATE tutor_time_slots SET student_id=NULL, status='Cancel', is_hidden=1, created_by=? WHERE id=? AND tutor_id=?");
        $stmt->bind_param("iii", $created_by, $slot_id, $tutor_id);
        $headText      = "Session Cancellation";
        $messageText = "Your scheduled session has been cancelled by the tutor.";
        $note        = "We apologise for any inconvenience caused. You may reschedule the session at your convenience.";
    }

    // CRITICAL: This part actually runs the update
    if ($stmt) {
        if (!$stmt->execute()) {
            throw new Exception("Database Update Failed: " . $stmt->error);
        }
        $stmt->close();
    }

    /* ---------------- LOG ---------------- */
    // Ensure 'action_type' column in your DB is at least VARCHAR(50)
    $log = $conn->prepare("
        INSERT INTO slot_change_log (slot_id, tutor_id, action_type, reason, created_by, action_datetime)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    // Structure: i (slot), i (tutor), s (action), s (reason), i (created_by)
    $log->bind_param("iissi", $slot_id, $tutor_id, $action, $reason, $created_by);
    
    if (!$log->execute()) {
        throw new Exception("Log Error: " . $log->error);
    }
    /* ---------------- EMAIL ---------------- */
    if (!empty($slot['bms_email']) && $action !== 'cancel_pending') {

        $start_time = date('H:i', strtotime($slot['start_time']));
        $end_time   = date('H:i', strtotime($slot['end_time']));
        $slot_date  = date('Y-m-d', strtotime($slot['slot_date']));

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = 'smtp.office365.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@bms.ac.lk';
            $mail->Password = 'Lox51527';
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('noreply@bms.ac.lk', 'BMS Session');
            $mail->addReplyTo('noreply@bms.ac.lk', 'BMS Support');
            $mail->addAddress($slot['bms_email'], $slot['first_name']);

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);

            $mail->Subject = "Booking " . ucfirst($headText);

            $actionText = htmlspecialchars($action ?? '');
            $reasonHtml = (!empty($reason)) ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" : "";

            $mail->Body = "
            <html>
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
            <div class='card'>
                <div class='header'>
                    <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' alt='BMS Logo' style='width:150px;'>
                </div>
                <div class='headerx'>
                    <h2 style='margin:10px 0 0 0; font-size:28px; color:#ffffff;'>{$headText}ing</h2>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($slot['first_name'] . " " . $slot['last_name']) . "</strong>,</p>
                    <p>{$messageText}</p>
                    
                    <table style='border:1.5px solid #a7a4a400; border-radius:0px; width:100%; hight: auto; border-collapse:separate; border-spacing:0;'>
                            <tr>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 25px;'>Session</td>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 0.4%;'>:</td>
                                <td style='padding:10px;'><s>" . htmlspecialchars($sessionName) . "</s></td>
                            </tr>
                            <tr>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 25%;;'>Booking Date</td>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 0.4%;'>:</td>
                                <td style='padding:10px;'><s>{$slot_date}</s></td>
                            </tr>
                            <tr>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 25%;'>Booking Time</td>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 0.4%;'>:</td>
                                <td style='padding:10px;'><s>{$start_time} - {$end_time}</s></td>
                            </tr>
                            <tr>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 25%;'>Lecturer</td>
                                <td style='padding:10px; font-weight:bold; color:#dc3545; width: 0.4%;'>:</td>
                                <td style='padding:10px;'><s>" . htmlspecialchars($lecturerName) . "</s></td>
                            </tr>


                    </table>
                    {$reasonHtml}
                    <p><i>{$note}</i></p>
                    <p>Thank you,<br><strong>BMS Campus</strong></p>
                </div>
                <div class='footer'>
                    &copy; BMS. All rights reserved.
                </div>
            </div>
            </body>
            </html>";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email sending failed: " . $mail->ErrorInfo);
        }
    }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

ob_end_flush();