<?php
session_start();
include("database/connection.php"); // Adjust path as needed

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "../login";</script>';
    exit;
}

$updatedBy = $_SESSION['username'];

// Get POST values
$student_id      = $_POST['student_id'] ?? '';
$programme_batch = $_POST['programme_batch'] ?? '';
$regFeeDiscount  = isset($_POST['regFeeDiscount']) ? floatval($_POST['regFeeDiscount']) : 0.0;
$regFeeRemark    = $_POST['regFeeRemark'] ?? '';
$d_type          = $_POST['dtype'] ?? '';
$programName     = $_POST['program_name'] ?? '';
$batchName       = $_POST['batch_name'] ?? '';

// Get program_id and batch_id
$program_id = 0;
$batch_id   = 0;

if ($programName) {
    $stmt = $conn->prepare("SELECT program_code FROM program_table WHERE program_name = ?");
    $stmt->bind_param("s", $programName);
    $stmt->execute();
    $stmt->bind_result($program_id);
    $stmt->fetch();
    $stmt->close();
}

if ($batchName) {
    $stmt = $conn->prepare("SELECT id FROM batch_table WHERE batch_name = ?");
    $stmt->bind_param("s", $batchName);
    $stmt->execute();
    $stmt->bind_result($batch_id);
    $stmt->fetch();
    $stmt->close();
}

// ================== INSERT INTO payment_plan_regfee_discount ==================
$stmt = $conn->prepare("INSERT INTO payment_plan_regfee_discount 
    (student_id, program_id, batch_id, d_type, discount_value, remarks, created_at, updated_by)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
$stmt->bind_param("iiisdss", $student_id, $program_id, $batch_id, $d_type, $regFeeDiscount, $regFeeRemark, $updatedBy);

if ($stmt->execute()) {
    $stmt->close();

    // ================== UPDATE installment_payment_table ==================
    $updateSql = "UPDATE installment_payment_table 
                  SET registrationfee = GREATEST(registrationfee - ?, 0)
                  WHERE student_id = ? AND programme_batch = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("dis", $regFeeDiscount, $student_id, $programme_batch);

    if ($updateStmt->execute()) {
        // Trigger due table recalculation to reflect registration discount immediately
        $postData = http_build_query([
            'student_id' => $student_id,
            'programme_batch' => $programme_batch,
            'program_Code' => $program_id
        ]);

        $ch = curl_init('http://localhost/ims/payment_folder/update_payment_due_tables.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        echo "<script>alert('Registration Fee Discount saved and installment table updated successfully!'); window.location.href = '" . $_SERVER['HTTP_REFERER'] . "';</script>";
    } else {
        echo "Error updating installment_payment_table: " . $updateStmt->error;
    }
    $updateStmt->close();
} else {
    echo "Error inserting into payment_plan_regfee_discount: " . $stmt->error;
    $stmt->close();
}

$conn->close();
