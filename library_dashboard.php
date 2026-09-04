<?php
session_start();
ob_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

include 'librarypro/timezone.php'; 
$today = date('Y-m-d');
$year = date('Y');
if(isset($_GET['year'])){
    $year = $_GET['year'];
}

// Add email_sent_count column to borrow table if not exists
$check_column = "SHOW COLUMNS FROM borrow LIKE 'email_sent_count'";
$column_result = $conn->query($check_column);
if($column_result->num_rows == 0) {
    $add_column = "ALTER TABLE borrow ADD COLUMN email_sent_count INT DEFAULT 0";
    $conn->query($add_column);
}

$check_column_date = "SHOW COLUMNS FROM borrow LIKE 'last_email_sent'";
$column_date_result = $conn->query($check_column_date);
if($column_date_result->num_rows == 0) {
    $add_column_date = "ALTER TABLE borrow ADD COLUMN last_email_sent DATETIME NULL";
    $conn->query($add_column_date);
}

// Handle email sending via AJAX
if(isset($_POST['send_email']) && isset($_POST['borrow_id'])) {

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');

    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    require 'PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {

        $borrow_id = $_POST['borrow_id'];

        $sql = "SELECT b.*, 
                       s.student_code, s.first_name, s.last_name, s.bms_email as email, s.mobile, s.nic,
                       ap.programme_code, ap.batch_id, ap.student_registration_id,
                       bt.batch_name,
                       pm.program_name,
                       bk.id as book_id, bk.title as book_title, bk.accession_number as book_accession,
                       a.full_name as staff_name, a.nic as staff_nic, a.admin_email as staff_email
                FROM borrow b 
                LEFT JOIN students s ON b.student_id = s.student_code AND b.borrower_type = 'student'
                LEFT JOIN admin a ON b.student_id = a.id AND b.borrower_type = 'staff'
                LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                LEFT JOIN batch_table bt ON ap.batch_id = bt.id 
                LEFT JOIN program_table pm ON ap.programme_code = pm.program_code 
                LEFT JOIN books bk ON b.book_id = bk.id
                WHERE b.id = '$borrow_id'";

        $result = $conn->query($sql);

        if(!$result || $result->num_rows == 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'Borrow record not found'
            ]);
            exit;
        }

        $row = $result->fetch_assoc();
        
        // Determine borrower type and set email recipient
        $borrower_type = isset($row['borrower_type']) ? $row['borrower_type'] : 'student';
        if($borrower_type == 'staff') {
            $recipient_email = $row['staff_email'];
            $recipient_name = $row['staff_name'];
            $recipient_id = $row['staff_nic'];
            $borrower_label = 'Staff';
        } else {
            $recipient_email = $row['email'];
            $recipient_name = $row['first_name'] . ' ' . $row['last_name'];
            $recipient_id = $row['student_registration_id'];
            $borrower_label = 'Student';
        }

        $sql_book = "SELECT * FROM books WHERE id='{$row['book_id']}'";
        $book_result = $conn->query($sql_book);
        $book = $book_result->fetch_assoc();

        $borrow_date = $row['date_borrow'];
        $due_date = date('Y-m-d', strtotime($borrow_date . ' + 7 days'));

        $days_overdue = (
            strtotime(date('Y-m-d')) - strtotime($due_date)
        ) / (60 * 60 * 24);

        $current_count = $row['email_sent_count'] ?: 0;
        $new_count = $current_count + 1;

        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'library@bms.ac.lk';
        $mail->Password = 'wgkhtfchjbwydrzj';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('library@bms.ac.lk', 'BMS Library');
        $mail->addAddress($recipient_email, $recipient_name);

        $mail->isHTML(true);
        $mail->Subject = 'Library Book Return Reminder';

        $mail->Body = "
<html>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>

    <style>
        /* General Table Styling */
        .table-container {
            border-radius: 12px;       /* Controls the carved corner look */
            overflow: hidden;          /* Forces contents to snap to the curved border */
            border: 1px solid #d1d5db; /* Outer border color */
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }

        .tdwidth {
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse; /* Merges borders seamlessly */
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb; /* Grid lines */
            border-right: 1px solid #e5e7eb;
            color: #374151;
            font-size: 14px;
        }

        /* Remove the rightmost border to keep it clean */
        td:last-child {
            border-right: none;
        }

        /* Remove the bottom border from the last row */
        tr:last-child td {
            border-bottom: none;
        }

        /* Optional: Give the first row a slightly different background if it acts as a header */
        tr:first-child {
            background-color: #f9fafb;
            font-weight: 600;
        }

        /* Alternating row colors for the remaining rows */
        tbody tr:nth-child(even) {
            background-color: #f3f4f6;
        }

        /* Specific Widths for Table 1 columns */
        .table-1 td:nth-child(1) {
            width: 30%;
        }
        
        .table-1 td:nth-child(2) {
            width: 70%;
        }
    </style>

    </head>

            <body>
                <h2 style='color: #042d5c;'>BMS Library Book Return Reminder</h2>
                <p>Dear {$recipient_name},</p>
                
                <p>This is reminder #<strong style='color: #dc3545'>" . $new_count . "</strong></p>
                <p>This is a reminder that the library book in your possession is now significantly overdue.</p>
    
    <div class='table-container'>
        <table class='table-1'>
            <tbody>
                <tr>
                    <td class='tdwidth'>Borrower ID</th>
                    <td><strong>{$recipient_id}</strong></th>
                </tr>
                <tr>
                    <td class='tdwidth'>Programme / Other</td>
                    <td>" . ($borrower_type == 'student' ? ($row['program_name'] ?: 'N/A') : 'Staff') . "</td>
                </tr>
                <tr>
                    <td class='tdwidth'>Batch / Other</td>
                    <td>" . ($borrower_type == 'student' ? ($row['batch_name'] ?: 'N/A') : 'Staff') . "</td>
                </tr>
            </tbody>
        </table>
    </div>

        <div class='table-container'>
        <table class='table-1'>
            <tbody>
                <tr>
                    <td class='tdwidth'>Book Title</th>
                    <td><strong>" . $book['title'] . "</strong></th>
                </tr>
                <tr>
                    <td class='tdwidth'>Accession Number</td>
                    <td>" . $book['accession_number'] . "</td>
                </tr>
                <tr>
                    <td class='tdwidth'>Borrow Date</td>
                    <td>" . date('F d, Y', strtotime($row['date_borrow'])) . "</td>
                </tr>
                <tr>
                    <td class='tdwidth'>Due Date</td>
                    <td>" . date('F d, Y', strtotime($due_date)) . "</td>
                </tr>
                <tr>
                    <td class='tdwidth'>Days Overdue</td>
                    <td><strong style='color: #ca2333;'>" . ceil($days_overdue) . " Days</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

                <!--<table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
                    <tr style='background-color: #f2f2f2;'>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Details</th>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Borrower Type</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$borrower_label}</strong></td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Borrower ID</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'><strong>{$recipient_id}</strong></td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Programme</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . ($borrower_type == 'student' ? ($row['program_name'] ?: 'N/A') : 'Staff') . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Batch</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . ($borrower_type == 'student' ? ($row['batch_name'] ?: 'N/A') : 'Staff') . "</td>
                    </tr>

                </table>

                <table style='border-collapse: collapse; width: 100%; margin: 20px 0;'>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Book Title</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'><strong>" . $book['title'] . "</strong></td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Accession Number</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'><strong>" . $book['accession_number'] . "</strong></td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Borrow Date</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . date('F d, Y', strtotime($row['date_borrow'])) . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Due Date</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . date('F d, Y', strtotime($due_date)) . "</td>
                    </tr>
                    <tr style='background-color: #ffe6e6;'>
                        <td style='border: 1px solid #ddd; padding: 8px;'>Days Overdue</td>
                        <td style='border: 1px solid #ddd; padding: 8px; color: red;'><strong>" . ceil($days_overdue) . " days</strong></td>
                    </tr>
                </table>-->
                <p>Please return the book to the library as soon as possible to avoid further penalties. Please contact your library officer for more information.</p>
                
                <br>
                <p>Thank you for your cooperation.</p>
                <p style='color: #dc3545'><strong>BMS Library</strong></p>
                <hr>
                <small>This is an automated message. Please do not reply.</small>
            </body>
</html>
        ";

        $mail->send();

        $update_sql = "
            UPDATE borrow
            SET email_sent_count = '$new_count',
                last_email_sent = NOW()
            WHERE id = '$borrow_id'
        ";

        $conn->query($update_sql);

        echo json_encode([
            'status' => 'success',
            'count' => $new_count
        ]);

    } catch (Exception $e) {

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);

    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .small-box {
            border-radius: 10px;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            margin-bottom: 20px;
            position: relative;
            padding: 20px;
            color: white;
            transition: transform 0.3s;
        }
        .small-box:hover {
            transform: translateY(-5px);
        }
        .small-box .inner h3 {
            font-size: 38px;
            font-weight: bold;
            margin: 0 0 10px 0;
        }
        .small-box .inner p {
            font-size: 15px;
            margin: 0;
        }
        .small-box .icon {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 60px;
            opacity: 0.4;
        }
        .bg-primary-custom { background-color: #4e73df; }
        .bg-success-custom { background-color: #614f5a; }
        .bg-info-custom { background-color: #e2af21; }
        .bg-warning-custom { background-color: #29b481; }
        .bg-danger-custom { background-color: #f02461; }
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        .alert {
            border-radius: 10px;
        }
        .box {
            position: relative;
            border-radius: 3px;
            background: #ffffff;
            border-top: 3px solid #d2d6de;
            margin-bottom: 20px;
            width: 100%;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .box-header {
            color: #444;
            display: block;
            padding: 10px;
            position: relative;
            border-bottom: 1px solid #f4f4f4;
        }
        .box-title {
            display: inline-block;
            font-size: 18px;
            margin: 0;
            line-height: 1;
        }
        .box-tools {
            position: absolute;
            right: 10px;
            top: 5px;
        }
        .box-body {
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            border-bottom-right-radius: 3px;
            border-bottom-left-radius: 3px;
            padding: 20px;
        }
        .form-inline .form-group {
            display: inline-block;
            margin-bottom: 0;
            vertical-align: middle;
        }
        .form-control {
            display: block;
            width: 100%;
            height: 34px;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            color: #555;
            background-color: #fff;
            background-image: none;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .input-sm {
            height: 30px;
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1.5;
            border-radius: 3px;
        }
        .pull-right {
            float: right !important;
        }
        .btn-send {
            background-color: #d32828;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            cursor: pointer;
        }
        .btn-send:hover {
            background-color: #c0392b;
        }
        .btn-send.disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        .modal-content {
            border-radius: 10px;
        }
        .email-details {
            background-color: #f8f9fc;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .email-count-badge {
            background-color: #617072;
            color: white;
            padding: 2px 2px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-student {
            background-color: #042d5c;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            width: 100%;
            text-align: center;
        }
        .badge-staff {
            background-color: #990014;
            width: 100%;
            text-align: center;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>

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
                    <h4 class="h4 mb-0 text-gray-800">Library Dashboard</h4>
                </div>

                <!-- Statistics Cards Row -->
                <div class="row">
                    <div class="col">
                        <div class="small-box bg-primary-custom">
                            <div class="inner">
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM books";
                                $query = $conn->query($sql);
                                $row = $query->fetch_assoc();
                                echo "<h3>".$row['total']."</h3>";
                                ?>
                                <p>Total Books</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="small-box bg-success-custom">
                            <div class="inner">
                                <?php
                                $sql = "SELECT COUNT(*) as active_count FROM allocate_programme WHERE status='active'";
                                $query = $conn->query($sql);
                                $row = $query->fetch_assoc();
                                echo "<h3>".$row['active_count']."</h3>";
                                ?>
                                <p>Total Active Students</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="small-box bg-info-custom">
                            <div class="inner">
                                <?php
                                $sql = "SELECT COUNT(*) AS total FROM borrow WHERE status=0";
                                $query = $conn->query($sql);
                                $row = $query->fetch_assoc();
                                echo "<h3>".$row['total']."</h3>";
                                ?>
                                <p>Non-Returned Books</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="small-box bg-warning-custom">
                            <div class="inner">
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM returns WHERE date_return = '$today'";
                                $query = $conn->query($sql);
                                $row = $query->fetch_assoc();
                                echo "<h3>".$row['total']."</h3>";
                                ?>
                                <p>Returned Today</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-undo-alt"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="small-box bg-danger-custom">
                            <div class="inner">
                                <?php
                                $sql = "SELECT COUNT(*) as total FROM borrow WHERE date_borrow = '$today'";
                                $query = $conn->query($sql);
                                $row = $query->fetch_assoc();
                                echo "<h3>".$row['total']."</h3>";
                                ?>
                                <p>Borrowed Today</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-share"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Row with Old Style Box -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h3 class="box-title">Monthly Transaction Report</h3>
                                <div class="box-tools pull-right">
                                    <form class="form-inline">
                                        <div class="form-group">
                                            <label>Go For: </label>
                                            <select class="form-control input-sm" id="select_year">
                                                <?php
                                                for($i=2020; $i<=2050; $i++){
                                                    $selected = ($i==$year)?'selected':'';
                                                    echo "<option value='".$i."' ".$selected.">".$i."</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="chart">
                                    <canvas id="barChart" style="height: 250px; width: 100%;"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-Returned Books Table - Updated for Student & Staff -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <span class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <h5 class="mb-0 ms-3">Non-Returned Books (Overdue)</h5>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="nonReturnedTable" class="table table-striped table-bordered" style="width: 100%; font-size: 12px;">
                                        <thead>
                                            <tr style="background-color: #f8f9fc;">
                                                <th>Borrower Type</th>
                                                <th>Borrower ID</th>
                                                <th>Borrower Name</th>
                                                <th>Programme</th>
                                                <th>Batch</th>
                                                <th>Phone Number</th>
                                                <th>Accession Number</th>
                                                <th>Book Name</th>
                                                <th>Borrow Date</th>
                                                <th>Borrow Days</th>
                                                <th>Deadline (7 days)</th>
                                                <th>Overdue</th>
                                                <th>Email Sent</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Query for students with non-returned books
                                            $sql_students = "SELECT b.*, 
                                                           s.student_code, s.first_name, s.last_name, s.mobile, s.bms_email, s.nic,
                                                           ap.programme_code, ap.batch_id, ap.student_registration_id,
                                                           bt.batch_name,
                                                           pm.program_name,
                                                           bk.title as book_title, bk.accession_number as book_code,
                                                           'student' as borrower_type
                                                    FROM borrow b 
                                                    LEFT JOIN students s ON b.student_id = s.student_code 
                                                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                                                    LEFT JOIN batch_table bt ON ap.batch_id = bt.id 
                                                    LEFT JOIN program_table pm ON ap.programme_code = pm.program_code 
                                                    LEFT JOIN books bk ON b.book_id = bk.id 
                                                    WHERE b.status = 0 AND b.borrower_type = 'student' AND ap.status = 'active'
                                                    ORDER BY b.date_borrow ASC";
                                                    
                                            $query_students = $conn->query($sql_students);
                                            
                                            // Query for staff with non-returned books
                                            $sql_staff = "SELECT b.*, 
                                                           a.id as staff_id, a.full_name as staff_name, a.nic as staff_nic, 
                                                           a.admin_email as staff_email,
                                                           bk.title as book_title, bk.accession_number as book_code,
                                                           'staff' as borrower_type
                                                    FROM borrow b 
                                                    LEFT JOIN admin a ON b.student_id = a.id 
                                                    LEFT JOIN books bk ON b.book_id = bk.id 
                                                    WHERE b.status = 0 AND b.borrower_type = 'staff'
                                                    ORDER BY b.date_borrow ASC";
                                                    
                                            $query_staff = $conn->query($sql_staff);
                                            
                                            // Combine and display results
                                            $all_borrowers = [];
                                            
                                            // Add students
                                            if($query_students && $query_students->num_rows > 0) {
                                                while($row = $query_students->fetch_assoc()) {
                                                    $all_borrowers[] = $row;
                                                }
                                            }
                                            
                                            // Add staff
                                            if($query_staff && $query_staff->num_rows > 0) {
                                                while($row = $query_staff->fetch_assoc()) {
                                                    $all_borrowers[] = $row;
                                                }
                                            }
                                            
                                            // Sort by borrow date
                                            usort($all_borrowers, function($a, $b) {
                                                return strtotime($a['date_borrow']) - strtotime($b['date_borrow']);
                                            });
                                            
                                            foreach($all_borrowers as $row) {
                                                $borrow_date = $row['date_borrow'];
                                                $due_date = date('Y-m-d', strtotime($borrow_date . ' + 7 days'));
                                                $borrow_days = (strtotime($today) - strtotime($borrow_date)) / (60 * 60 * 24);
                                                $days_overdue = (strtotime($today) - strtotime($due_date)) / (60 * 60 * 24);
                                                $show_email_button = ($days_overdue >= 7) ? true : false;
                                                
                                                $status_color = ($days_overdue >= 7) ? 'danger' : (($days_overdue >= 0) ? 'warning' : 'success');
                                                $status_text = ($days_overdue >= 7) ? 'Overdue' : (($days_overdue >= 0) ? 'Due Soon' : 'On Time');
                                                
                                                // Get borrower details based on type
                                                $borrower_type = isset($row['borrower_type']) ? $row['borrower_type'] : 'student';
                                                
                                                if($borrower_type == 'staff') {
                                                    $borrower_id = $row['staff_nic'];
                                                    $borrower_name = $row['staff_name'];
                                                    $phone = isset($row['staff_mobile']) ? $row['staff_mobile'] : 'N/A';
                                                    $email = $row['staff_email'];
                                                    $programme = 'Staff';
                                                    $batch = 'Staff';
                                                    $type_badge = '<span class="badge-staff">Staff</span>';
                                                } else {
                                                    $borrower_id = $row['student_registration_id'];
                                                    $borrower_name = $row['first_name'] . ' ' . $row['last_name'];
                                                    $phone = $row['mobile'] ?: 'N/A';
                                                    $email = $row['bms_email'];
                                                    $programme = $row['program_name'] ?: 'N/A';
                                                    $batch = $row['batch_name'] ?: 'N/A';
                                                    $type_badge = '<span class="badge-student">Student</span>';
                                                }
                                                
                                                $email_count = $row['email_sent_count'] ?: 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo $type_badge; ?></td>
                                                    <td><?php echo $borrower_id; ?></td>
                                                    <td><?php echo $borrower_name; ?></td>
                                                    <td><?php echo $programme; ?></td>
                                                    <td><?php echo $batch; ?></td>
                                                    <td><?php echo $phone; ?></td>
                                                    <td><?php echo $row['book_code']; ?></td>
                                                    <td><?php echo $row['book_title']; ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($borrow_date)); ?></td>
                                                    <td><?php echo round($borrow_days); ?> days</td>
                                                    <td><?php echo date('Y-m-d', strtotime($due_date)); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $status_color; ?>">
                                                            <?php echo $status_text; ?>
                                                            <?php if($days_overdue >= 7): ?>
                                                                <?php echo round($days_overdue); ?> 
                                                            <?php endif; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="email-count-badge">
                                                            <?php echo $email_count; ?> time(s)
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if($show_email_button && !empty($email)): ?>
                                                            <button class="btn-send send-email-btn" 
                                                                    data-id="<?php echo $row['id']; ?>"
                                                                    data-name="<?php echo $borrower_name; ?>"
                                                                    data-email="<?php echo $email; ?>"
                                                                    data-book="<?php echo $row['book_title']; ?>"
                                                                    data-accession="<?php echo $row['book_code']; ?>"
                                                                    data-days="<?php echo round($days_overdue); ?>"
                                                                    data-count="<?php echo $email_count; ?>"
                                                                    data-type="<?php echo $borrower_type; ?>">
                                                                <i class="fas fa-envelope"></i> Send Email
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn-send disabled" disabled>
                                                                <i class="fas fa-clock"></i> 
                                                                <?php if($days_overdue < 7): ?>
                                                                    Wait (<?php echo ceil(7 - $days_overdue); ?> days)
                                                                <?php else: ?>
                                                                    No Email
                                                                <?php endif; ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Confirmation Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <h5 class="modal-title" id="emailModalLabel">
                    <i class="fas fa-envelope"></i> Confirm Send Email
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-Secondary">
                    <i class="fas fa-info-circle"></i> Please review the email details before sending.
                </div>
                
                <div class="email-details">
                    <h6 style='color: #016d5b;'><i class="fas fa-user"></i> Borrower Information:</h6>
                    <p><strong>Type:</strong> <span id="modal_borrower_type" class=""></span></p>
                    <p><strong>Name:</strong> <span id="modal_borrower_name"></span></p>
                    <p><strong>Email:</strong> <span id="modal_borrower_email"></span></p>
                    <p><strong>Times Email Sent:</strong> <span id="modal_email_count" class="badge bg-danger"></span></p>
                    <br>
                    <h6 class="mt-3"  style='color: #016d5b;'><i class="fas fa-book"></i> Book Information:</h6>
                    <p><strong>Book Title:</strong> <span id="modal_book_title"></span></p>
                    <p><strong>Accession Number:</strong> <span id="modal_book_accession"></span></p>
                    <p><strong>Days Overdue:</strong> <span id="modal_days_overdue" class="text-danger fw-bold"></span></p>
                </div>
                
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> 
                    This email will be sent to the borrower as a reminder to return the overdue book.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSendEmail">
                    <i class="fas fa-paper-plane"></i> Send Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Chart Data -->
<?php
$months = array();
$return_data = array();
$borrow_data = array();

for($m = 1; $m <= 12; $m++) {
    $sql = "SELECT COUNT(*) as count FROM returns WHERE MONTH(date_return) = '$m' AND YEAR(date_return) = '$year'";
    $rresult = $conn->query($sql);
    $rrow = $rresult->fetch_assoc();
    array_push($return_data, $rrow['count']);

    $sql = "SELECT COUNT(*) as count FROM borrow WHERE MONTH(date_borrow) = '$m' AND YEAR(date_borrow) = '$year'";
    $bresult = $conn->query($sql);
    $brow = $bresult->fetch_assoc();
    array_push($borrow_data, $brow['count']);

    $month = date('M', mktime(0, 0, 0, $m, 1));
    array_push($months, $month);
}

$months_json = json_encode($months);
$return_json = json_encode($return_data);
$borrow_json = json_encode($borrow_data);
?>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#nonReturnedTable').DataTable({
        pageLength: 10,
        ordering: true,
        responsive: true,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
    });
    
    // Initialize Chart
    var ctx = document.getElementById('barChart').getContext('2d');
    
    var barChartData = {
        labels: <?php echo $months_json; ?>,
        datasets: [
            {
                label: 'Borrow',
                data: <?php echo $borrow_json; ?>,
                backgroundColor: '#042d5c',
                borderColor: '#042d5c',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            },
            {
                label: 'Return',
                data: <?php echo $return_json; ?>,
                backgroundColor: '#e01e1e',
                borderColor: '#e01e1e',
                borderWidth: 1,
                borderRadius: 4,
                barPercentage: 0.7,
                categoryPercentage: 0.8
            }
        ]
    };
    
    var barChartOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 10,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        let value = context.raw;
                        return label + ': ' + value;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,.05)',
                    drawBorder: true
                },
                ticks: {
                    stepSize: 1,
                    precision: 0,
                    font: {
                        size: 10
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 10
                    }
                }
            }
        }
    };
    
    window.barChart = new Chart(ctx, {
        type: 'bar',
        data: barChartData,
        options: barChartOptions
    });
    
    // Variable to store current borrow ID
    var currentBorrowId = null;
    var currentButton = null;
    
    // Send email button click handler
    $(document).on('click', '.send-email-btn', function() {
        currentBorrowId = $(this).data('id');
        currentButton = $(this);
        var borrowerName = $(this).data('name');
        var borrowerEmail = $(this).data('email');
        var bookTitle = $(this).data('book');
        var bookAccession = $(this).data('accession');
        var daysOverdue = $(this).data('days');
        var emailCount = $(this).data('count');
        var borrowerType = $(this).data('type');
        
        $('#modal_borrower_name').text(borrowerName);
        $('#modal_borrower_email').text(borrowerEmail);
        $('#modal_book_title').text(bookTitle);
        $('#modal_book_accession').text(bookAccession);
        $('#modal_days_overdue').text(daysOverdue + ' days');
        $('#modal_email_count').text(emailCount + ' time(s)');
        
        // Set borrower type badge
        var typeBadge = $('#modal_borrower_type');
        if(borrowerType === 'staff') {
            typeBadge.text('Staff').removeClass('badge-student').addClass('badge-staff');
        } else {
            typeBadge.text('Student').removeClass('badge-staff').addClass('badge-student');
        }
        
        $('#emailModal').modal('show');
    });
    
    // Confirm send email
    $('#confirmSendEmail').click(function() {
        if (currentBorrowId) {
            $('#emailModal').modal('hide');
            
            // Show loading alert
            Swal.fire({
                title: 'Sending Email...',
                text: 'Please wait while we send the email',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: {
                    send_email: true,
                    borrow_id: currentBorrowId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Sent Successfully!',
                            text: 'Reminder email has been sent to the borrower.',
                            confirmButtonColor: '#4e73df',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Send Email',
                            text: response.message || 'An error occurred while sending the email.',
                            confirmButtonColor: '#e74a3b',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.log("Status:", status);
                    console.log("Error:", error);
                    console.log("Response:", xhr.responseText);

                    Swal.fire({
                        icon: 'error',
                        title: 'AJAX Error',
                        html: '<pre style="text-align:left;">' +
                            xhr.responseText +
                            '</pre>'
                    });
                }
            });
        }
    });
});

// Year select change handler
$('#select_year').change(function(){
    window.location.href = 'library_dashboard.php?year=' + $(this).val();
});
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>