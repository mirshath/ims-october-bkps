<?php
session_start();
include 'database/connection.php';

// Retrieve and sanitize NIC value from POST
$nic = trim($_POST['nic'] ?? '');

if ($nic === '') {
    echo "<div class='alert alert-danger text-center'>Please enter or scan a NIC value.</div>";
    exit;
}

// Prepare and execute query to fetch student by NIC
$stmt = $conn->prepare("SELECT * FROM induction_students WHERE nic = ?");
$stmt->bind_param("s", $nic);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (strtolower($row['attended']) === 'yes') {
        // Already marked as attended
        $feesStatus = strtolower(trim($row['fees']));
        if ($feesStatus === 'paid') {
            // Attended & paid: warning
            $alertClass = 'alert-warning';
            $feesColor = '#20603f';
            $feesMsg = "<div><h5><b>Fees:</b> <span style='color:{$feesColor};'>" . htmlspecialchars($row['fees']) . "</span></h5></div>";
        } else {
            // Attended & unpaid: danger
            $alertClass = 'alert-danger';
            $feesColor = '#c4453e';
            $feesMsg = "<div><h5><b>Fees:</b> <span style='color:{$feesColor};'>" . htmlspecialchars($row['fees']) . "</span></h5></div>";
        }

        echo "
            <div class='alert {$alertClass} text-center'>
                <h5 class='mb-1' style='color:#0d4365'>{$row['full_name']}</h5>
                <p style='margin-bottom:0.3em'>Already Marked as <b>Attended</b></p>
                <div><b>Programme:</b> " . htmlspecialchars($row['programme']) . "</div>
                <div><b>NIC:</b> " . htmlspecialchars($row['nic']) . "</div>
                {$feesMsg}
            </div>
        ";
    } else {
        // Mark as attended
        $update = $conn->prepare("UPDATE induction_students SET attended='Yes', attended_time=NOW() WHERE id=?");
        $update->bind_param("i", $row['id']);
        $update->execute();

        // Check paid status for alert type
        $feesStatus = strtolower(trim($row['fees']));
        if ($feesStatus === 'paid') {
            // Set pack_collected=1 if paid when marking attendance
            $packUpdate = $conn->prepare("UPDATE induction_students SET pack_collected='1' WHERE id=?");
            $packUpdate->bind_param("i", $row['id']);
            $packUpdate->execute();

            $alertClass = 'alert-success';
            $feesColor = '#20603f';
            $feesMsg = "<div><h5><b>Fees:</b> " . htmlspecialchars($row['fees']) . "</h5></div>";

            // Instruction pack icon with checkbox (checked)
            $packIcon = "<span style='font-size:2rem; color:#29a300; vertical-align:middle;'>
                            <i class='fas fa-box-open'></i> <i class='fas fa-check-circle' style='position:relative; left:-8px; color: #29a300;'></i>
                         </span>
                         <span style='margin-left:8px;font-weight:500;font-size:1.1rem;'>Induction Pack: <span style='color:#29a300;'>Issued</span></span>";
        } else {
            $alertClass = 'alert-danger';
            $feesColor = '#c4453e';
            $feesMsg = "<div><h5><b>Fees:</b> <span style='color:#c4453e;'>" . htmlspecialchars($row['fees']) . "</span></h5></div>";

            // Instruction pack icon with cross (not checked)
            $packIcon = "<span style='font-size:2rem; color:#b1001a; vertical-align:middle;'>
                            <i class='fas fa-box-open'></i> <i class='fas fa-times-circle' style='position:relative; left:-8px; color:#b1001a;'></i>
                         </span>
                         <span style='margin-left:8px;font-weight:500;font-size:1.1rem;'>Induction Pack: <span style='color:#b1001a;'>Not Issued</span></span>";
        }

        echo "
            <div class='alert {$alertClass} text-center'>
            <div class='d-flex flex-column align-items-center' style='margin-bottom:0.8em;'>
                <span style='color:#29a300; font-size:3.2em; display:block;'>
                    <i class='fas fa-check-circle'></i>
                </span>
                <b style='font-size:1.15em; margin-top:0.25em; display:block;'>
                    Attendance Marked Successfully!
                </b>
            </div>
                <h5 class='mb-1' style='color:{$feesColor}'>{$row['full_name']}</h5>
                <div><b>Programme:</b> " . htmlspecialchars($row['programme']) . "</div>
                <div><b>NIC:</b> " . htmlspecialchars($row['nic']) . "</div>
                {$feesMsg}
                <div class='mt-3'>{$packIcon}</div>
            </div>
        ";
    }
} else {
    // Student not found
    echo "<div class='alert alert-danger text-center'>Student Not Found.<br>Double-check the NIC or try again.</div>";
}
