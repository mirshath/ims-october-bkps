<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'database/connection.php';


  if (!isset($_SESSION['username'])) {
        // header("location: login.php");
        echo '<script>window.location.href = "login";</script>';
        // exit();
    }


// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------



/* ================== LOAD PHPMailer ================== */
// require 'PHPMailer/PHPMailer.php';
// require 'PHPMailer/SMTP.php';
// require 'PHPMailer/Exception.php';

// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;


// me uploaded new on 19.03.2026 
// Include PHPMailer files
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$message = "";

/* =========================================================
   FUNCTION: SEND LOGIN EMAIL
========================================================= */
function sendStudentMail($email, $first_name, $last_name, $temp_password) {

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email skipped: $email";
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();

            $mail->Host       = 'smtp.office365.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreply@bms.ac.lk';
            $mail->Password   = 'Lox51527';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use STARTTLS
            $mail->Port       = 587;

            // Anti-spam headers
            $mail->setFrom('noreply@bms.ac.lk', 'BMS Student Portal');
            $mail->addReplyTo('noreply@bms.ac.lk', 'BMS Support');
            $mail->addAddress($email, $first_name);

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);

            $mail->Subject = "LMS Account Credentials";

            //this code for rooting the email logo

            

        // HTML Body
        $mail->Body = "

    <head>

        <style>

                        .css-selector {
                background: linear-gradient(141deg, #042d5c, #042d5c);
                background-size: 400% 400%;

                -webkit-animation: AnimationName 2s ease infinite;
                -moz-animation: AnimationName 2s ease infinite;
                animation: AnimationName 2s ease infinite;
            }

            @-webkit-keyframes AnimationName {
                0%{background-position:0% 84%}
                50%{background-position:100% 17%}
                100%{background-position:0% 84%}
            }
            @-moz-keyframes AnimationName {
                0%{background-position:0% 84%}
                50%{background-position:100% 17%}
                100%{background-position:0% 84%}
            }
            @keyframes AnimationName {
                0%{background-position:0% 84%}
                50%{background-position:100% 17%}
                100%{background-position:0% 84%}
            }

            body {font-family:Arial,sans-serif; margin:0; padding:0; background-color:#f4f4f4;}
            .card {background-color: #fff; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); overflow:hidden; max-width:600px; margin:auto; border: 2px solid #b1b5c0;}
            .header {background-color: #fefefe; background-size: 100% auto ; color: #fff; padding:20px; text-align:center;}
            .headerx {background-color: #042d5c;  background-size: 100% auto ; color: #fff; padding:20px; text-align:center;}

            .content {padding:30px;}
            .btn-login {display:inline-block; background-color: #042d5c; color: #ffffff; padding:12px 24px; border-radius:8px; font-weight:bold;}
           table {
                        border: 1.5px solid #a7a4a4;
                        border-radius: 10px;
                        width: 65%;
                        /* Changed from collapse to separate to allow border-radius */
                        border-collapse: separate; 
                        border-spacing: 0;
                        overflow: hidden;
                    }

                    /* Rounded corners for the top and bottom of the table */
                    table tr:first-child td:first-child { border-top-left-radius: 10px; }
                    table tr:first-child td:last-child { border-top-right-radius: 10px; }
                    table tr:last-child td:first-child { border-bottom-left-radius: 10px; }
                    table tr:last-child td:last-child { border-bottom-right-radius: 10px; }

                    table td {
                        padding: 10px;
                        /* This adds a border between rows since we aren't using collapse */
                        border-bottom: 0.5px solid #a7a4a4; 
                    }

                    /* Remove the bottom border from the very last row so it doesn't double up with the table border */
                    table tr:last-child td {
                        border-bottom: none;
                    }

                    table tr:nth-child(odd) {
                        background-color: #f4f4f4;
                    }
              .footer {background-color: #042d5c; color: #fff; padding:15px; text-align:center; font-size:12px;}

        </style>
    </head>
    <body>
        
<div class='card'>
    <div class='header' style='color:#042d5c;'>
        <img src='https://www.bms.ac.lk/assets/images/logo/BMS-Logo.png' alt='BMS Logo' style='width:150px;'>

    </div>
    <div class='headerx css-selector' style='color: #042d5c;'>
        <h2 style='margin:10px 0 0 0; font-size:28px; color: #ffffff;'>LMS Account Credentials</h2>
    </div>
    <div class='content'>
        <p>Dear <strong>$first_name $last_name</strong>,</p>
        <p>Your LMS account has been successfully created. Please find your login credentials below: </p>
        <table>
            <tr>
                <td style='font-weight:bold; color:#042d5c;'>Username</td>
                <td>$email</td>
            </tr>
            <tr>
                <td style='font-weight:bold; color:#042d5c;'>Temporary Password</td>
                <td>$temp_password</td>
            </tr>
        </table class='table table-bordered'>
        <p>Please log in and update your password upon first access.</p>
        <p style='font-size:12px; color:#888;'><i>If you did not request this account, please contact the IT Department immediately.</i></p>
        <p style='text-align:center; margin:30px 0;'>
            <a href='http://localhost/One_To_One/login.php' target='_blank' class='btn-login'>Login Now</a>
        </p>
       
        <p>Thank you,<br><strong>BMS Campus</strong></p>
    </div>
    <div class='footer'>
        &copy; BMS. All rights reserved.
    </div>
</div>
    </body> 
        ";

        // Plain-text version
        $mail->AltBody = "BMS Student Portal Login\nUsername: $email\nTemp Password: $temp_password\nLogin: https://yourdomain.com/login.php";

        $mail->send();
        return "📧 Sent to $email";

    } catch (Exception $e) {
        return "❌ Mail failed for $email — {$mail->ErrorInfo}";
    }
}

/* =========================================================
   SEND EMAIL TO SELECTED STUDENTS
========================================================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    foreach ($_POST['student_id'] as $code) {

        $stmt = $conn->prepare("SELECT first_name,last_name,bms_email,date_of_birth FROM students WHERE student_code=?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $s = $stmt->get_result()->fetch_assoc();

        $temp_password = date('Ymd', strtotime($s['date_of_birth']));
        $hash = password_hash($temp_password, PASSWORD_DEFAULT);

        $up = $conn->prepare("UPDATE stu_login SET password=?, is_temp_password=1 WHERE student_code=?");
        $up->bind_param("ss", $hash, $code);
        $up->execute();

        $message .= sendStudentMail($s['bms_email'], $s['first_name'], $s['last_name'], $temp_password) . "<br>";
    }

    $_SESSION['msg'] = $message;
    header("Location: ".$_SERVER['PHP_SELF']."?program=".$_GET['program']."&batch=".$_GET['batch']);
    exit;
}

/* =========================================================
   RESET PASSWORD + EMAIL
========================================================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {

    $code = $_POST['reset_student'];

    $stmt = $conn->prepare("SELECT first_name,last_name,bms_email,date_of_birth FROM students WHERE student_code=?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();

    $temp_password = date('Ymd', strtotime($s['date_of_birth']));
    $hash = password_hash($temp_password, PASSWORD_DEFAULT);

    $up = $conn->prepare("UPDATE stu_login SET password=?, is_temp_password=1 WHERE student_code=?");
    $up->bind_param("ss", $hash, $code);
    $up->execute();

    $_SESSION['msg'] = "🔁 Password reset.<br>" . sendStudentMail($s['bms_email'], $s['first_name'], $s['last_name'], $temp_password);
    header("Location: ".$_SERVER['PHP_SELF']."?program=".$_GET['program']."&batch=".$_GET['batch']);
    exit;
}

/* =========================================================
   SESSION MESSAGE
========================================================= */
if(isset($_SESSION['msg'])){ $message=$_SESSION['msg']; unset($_SESSION['msg']); }

/* LOAD PROGRAMS */
$programs = $conn->query("SELECT program_code, program_name FROM program_table ORDER BY program_name");

$filter_program = $_GET['program'] ?? '';
$filter_batch   = $_GET['batch'] ?? '';
$students=null; $batches=[];

if($filter_program){
    $bq=$conn->prepare("SELECT DISTINCT bt.id,bt.batch_name FROM batch_table bt
                        JOIN allocate_programme ap ON bt.id=ap.batch_id
                        WHERE ap.programme_code=?");
    $bq->bind_param("s",$filter_program);
    $bq->execute();
    $batches=$bq->get_result();

    if($filter_batch){
        $sq=$conn->prepare("SELECT s.student_code,s.first_name,s.last_name,s.bms_email,sl.is_temp_password
                            FROM students s
                            JOIN allocate_programme ap ON s.student_code=ap.student_code
                            LEFT JOIN stu_login sl ON s.student_code=sl.student_code
                            WHERE ap.programme_code=? AND ap.batch_id=?");
        $sq->bind_param("si",$filter_program,$filter_batch);
        $sq->execute();
        $students=$sq->get_result();
    }
}

 include("includes/header.php");
?>


<html>
<head>
<title>Student Login Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="">

<!-- --------------------------------------------
 ------------------------------------------------->

<!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("nav.php"); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("includes/topnav.php"); ?>

                <!-- Begin Page Content -->
                <div class="p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">Student Login Management</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Find Program | Batch</h6>
                                </div>
                                <div class="card-body">



<!-----------------------------------------------
-------------------------------------------------->

<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<script>
setTimeout(()=>{location.reload()},5500); // auto refresh to prevent duplicate email
</script>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3">
<div class="col-md-6">
<select name="program" class="form-select" onchange="this.form.submit()">
<option value="">Select Program</option>
<?php while($p=$programs->fetch_assoc()): ?>
<option value="<?= $p['program_code'] ?>" <?=($filter_program==$p['program_code'])?'selected':''?>><?= $p['program_name'] ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="col-md-6">
<select name="batch" class="form-select" onchange="this.form.submit()">
<option value="">Select Batch</option>
<?php if($batches) while($b=$batches->fetch_assoc()): ?>
<option value="<?= $b['id'] ?>" <?=($filter_batch==$b['id'])?'selected':''?>><?= $b['batch_name'] ?></option>
<?php endwhile; ?>
</select>
</div>
</form>

<!-----------------------------------------------
-------------------------------------------------->

</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Student Login Data</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Students Table -->
                                    <div class="table-responsive mb-4">
                                       





<!-----------------------------------------------
-------------------------------------------------->

<?php if($students): ?>
<form method="POST">
<table id="studentsTable" class="table table-striped table-bordered" style="width: 100%; font-size: auto;">
<tr><th></th><th>Code</th><th>Name</th><th>Email</th><th>Status</th><th>Reset</th></tr>
<?php while($s=$students->fetch_assoc()): ?>
<tr>
<td><input type="checkbox" name="student_id[]" value="<?= $s['student_code'] ?>"></td>
<td><?= $s['student_code'] ?></td>
<td><?= $s['first_name'].' '.$s['last_name'] ?></td>
<td><?= $s['bms_email'] ?></td>
<td><?= ($s['is_temp_password']==1)?"<span class='badge bg-warning'>Temporary</span>":"<span class='badge bg-success'>Changed</span>" ?></td>
<td>
<button type="button" class="btn btn-sm btn-danger" onclick="resetPass('<?= $s['student_code'] ?>')">Reset</button>
</td>
</tr>
<?php endwhile; ?>
</table>
<button type="submit" name="send_email" class="btn btn-primary">Send Login Email</button>
</form>
<?php endif; ?>

<!-----------------------------------------------
-------------------------------------------------->

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />


    </div>



<!-----------------------------------------------
-------------------------------------------------->


</div>

<form method="POST" id="resetForm">
<input type="hidden" name="reset_student" id="reset_student">
<input type="hidden" name="reset_password">
</form>

<script>
function resetPass(code){
 if(confirm("Reset password and send email?")){
  document.getElementById('reset_student').value=code;
  document.getElementById('resetForm').submit();
 }
}
</script>
</body>
</html>
