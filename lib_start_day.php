<?php
// lib_start_day.php
session_start();
ob_start();

date_default_timezone_set('Asia/Colombo');

require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/lib_functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

/*----------------------------------
LOGIN CHECK
----------------------------------*/

if (!isset($_SESSION['username'])) {
    header("Location: login");
    exit();
}

$today = date('Y-m-d');

/*----------------------------------
CHECK ACTIVE DAY
(only latest record today with no end_count)
----------------------------------*/

$activeDay = false;

$stmt = $conn->prepare("
    SELECT id
    FROM lib_machine_log
    WHERE log_date = ?
    AND end_count IS NULL
    LIMIT 1
");

$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $activeDay = true;
}
$stmt->close();

if ($activeDay) {
    header("Location: lib_dashboard.php");
    exit();
}

/*----------------------------------
GET LAST END MACHINE COUNT
(FIXED: Get the latest end_count from any date)
----------------------------------*/

$lastMachineCount = '';

// First, try to get the latest end_count (from any date)
$stmt = $conn->prepare("
    SELECT end_count
    FROM lib_machine_log
    WHERE end_count IS NOT NULL
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $lastMachineCount = $row['end_count'];
}
$stmt->close();

// If no end_count found, get the latest count
if ($lastMachineCount === '') {
    $stmt = $conn->prepare("
        SELECT count
        FROM lib_machine_log
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $lastMachineCount = $row['count'];
    }
    $stmt->close();
}

/*----------------------------------
GET THE LAST RECORD FOR TODAY
(to get the correct previous count for today's entry)
----------------------------------*/

$lastTodayCount = '';

$stmt = $conn->prepare("
    SELECT count, end_count
    FROM lib_machine_log
    WHERE log_date = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // If there's an end_count, use it as the starting count
    if (!empty($row['end_count'])) {
        $lastTodayCount = $row['end_count'];
    } else {
        // If no end_count, use the count (shouldn't happen if day is ended)
        $lastTodayCount = $row['count'];
    }
}
$stmt->close();

// If we have a today record with end_count, use it as the starting count
if ($lastTodayCount !== '') {
    $lastMachineCount = $lastTodayCount;
}

/*----------------------------------
GET NEXT DAY ENTRY
----------------------------------*/

$nextEntry = 1;

$stmt = $conn->prepare("
    SELECT MAX(day_entry) max_entry
    FROM lib_machine_log
    WHERE log_date = ?
");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!empty($row['max_entry'])) {
    $nextEntry = intval($row['max_entry']) + 1;
}
$stmt->close();

/*----------------------------------
START DAY SAVE
----------------------------------*/

$error = '';

if (isset($_POST['start_day'])) {
    $startCount = trim($_POST['start_count']);

    if ($startCount === '') {
        $error = 'Enter machine count';
    } else {
        $startCount = intval($startCount);

        // First, check if there's already a record for today with NULL end_count
        // (This shouldn't happen because we redirect earlier, but just in case)
        $checkStmt = $conn->prepare("
            SELECT id FROM lib_machine_log 
            WHERE log_date = ? AND end_count IS NULL
        ");
        $checkStmt->bind_param("s", $today);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        
        if ($checkRes->num_rows > 0) {
            // There's an active day record - should not happen
            $error = 'An active day already exists. Please end it first.';
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO lib_machine_log
                (log_date, count, day_entry)
                VALUES (?, ?, ?)
            ");

            if (!$stmt) {
                $error = $conn->error;
            } else {
                $stmt->bind_param("sii", $today, $startCount, $nextEntry);

                if ($stmt->execute()) {
                    $stmt->close();
                    ob_end_clean();
                    header("Location: lib_dashboard.php");
                    exit();
                } else {
                    $error = $stmt->error;
                }
                $stmt->close();
            }
        }
        $checkStmt->close();
    }
}

include("includes/header.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Day – BMS Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7fc; }
        .card { border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .start-day-box { max-width: 500px; margin: 0 auto; }
        .form-control-lg { border-radius: 10px; padding: 12px 16px; font-size: 1.1rem; }
        .btn-start { padding: 12px; font-size: 1.1rem; border-radius: 10px; }
        .info-box { background: #e8f0fe; border-radius: 10px; padding: 15px; border-left: 4px solid #042d5c; }
    </style>
</head>
<body>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Start Day</h4>
                </div>
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-play"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Start New Day</h6>
                            </div>
                            <div class="card-body">
                                <div class="start-day-box">
                                    <div class="text-center mb-3">
                                        <h5 class="mb-1"><?= date('l, F j, Y') ?></h5>
                                        <p class="text-muted small">Enter the initial machine count to start the day</p>
                                    </div>
                                    
                                    <div class="info-box mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Last End Count:</span>
                                            <strong><?= ($lastMachineCount != '' ? $lastMachineCount : 'No Data') ?></strong>
                                        </div>
                                        <?php if ($nextEntry > 1): ?>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span>Day Entry #:</span>
                                                <strong><?= $nextEntry ?></strong>
                                            </div>
                                            <small class="text-muted">This is entry #<?= $nextEntry ?> for today</small>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($error): ?>
                                        <div class="alert alert-danger py-2"><?= $error ?></div>
                                    <?php endif; ?>

                                    <form method="post">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Machine Count</label>
                                            <input type="number" required name="start_count" class="form-control form-control-lg" 
                                                   value="<?= $lastMachineCount ?>" placeholder="Enter machine count..." autofocus>
                                        </div>
                                        <button name="start_day" class="btn btn-primary btn-start w-100">
                                            <i class="bi bi-play-circle me-2"></i> Start Day
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>