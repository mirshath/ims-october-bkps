<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/connection.php';

function emptyToNull($value)
{
    return ($value === '' || $value === null) ? NULL : $value;
}

if (!isset($_POST['saveButton'])) {
    die("Form not submitted");
}

// Get all values
$student_code = (int)$_POST['student_code'];
$programme_batch = mysqli_real_escape_string($conn, $_POST['programmeBatch']);
$entered_by = mysqli_real_escape_string($conn, $_POST['username']);

$university_fee_LKR = emptyToNull($_POST['uniFeeLKR']);
$university_fee_GBP = emptyToNull($_POST['uniFeeGBP']);
$university_fee_USD = emptyToNull($_POST['uniFeeUSD']);

$courseFeeLKR_total = emptyToNull($_POST['courseFeeInputLKR_initial_value']);
$courseFeeGBP_total = emptyToNull($_POST['courseFeeInputGBP_initial_value']);
$courseFeeUSD_total = emptyToNull($_POST['courseFeeInputUSD_initial_value']);

$course_fee_LKR = emptyToNull($_POST['courseFeeLKR']);
$course_fee_GBP = emptyToNull($_POST['courseFeeGBP']);
$course_fee_USD = emptyToNull($_POST['courseFeeUSD']);

$registration_fee_LKR = emptyToNull($_POST['registrationFeeLKR']);
$registration_fee_GBP = emptyToNull($_POST['registrationFeeGBP']);
$registration_fee_USD = emptyToNull($_POST['registrationFeeUSD']);

$course_fee_type_LKR = emptyToNull($_POST['courseFeeLKR_type']);
$course_fee_type_GBP = emptyToNull($_POST['courseFeeGBP_type']);
$course_fee_type_USD = emptyToNull($_POST['courseFeeUSD_type']);

$installment_month_LKR = emptyToNull($_POST['installmentsLKR']);
$installment_month_GBP = emptyToNull($_POST['installmentsGBP']);
$installment_month_USD = emptyToNull($_POST['installmentsUSD']);

$courseFeeLKR_InstallmentDateFirst = emptyToNull($_POST['courseFeeLKR_InstallmentDateFirst']);
$courseFeeLKR_InstallmentDateFirst_DUE = emptyToNull($_POST['courseFeeLKR_InstallmentDateFirst_DUE']);

mysqli_begin_transaction($conn);

try {
    // Check duplicate
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM add_payment_plan_table WHERE student_id = ? AND programme_batch = ?");
    mysqli_stmt_bind_param($stmt, "is", $student_code, $programme_batch);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row['cnt'] > 0) {
        throw new Exception("Duplicate entry: A payment plan already exists for this student and batch.");
    }

    // ==================== TABLE 1: add_payment_plan_table ====================
    $sql1 = "INSERT INTO add_payment_plan_table 
        (student_id, programme_batch, university_fee_LKR, courseFeeLKR_total, course_fee_LKR, 
        course_fee_type_LKR, installment_month_LKR, registration_fee_LKR, university_fee_GBP, 
        courseFeeGBP_total, course_fee_GBP, course_fee_type_GBP, installment_month_GBP, 
        registration_fee_GBP, university_fee_USD, courseFeeUSD_total, course_fee_USD, 
        course_fee_type_USD, installment_month_USD, registration_fee_USD, entered_by, 
        lkr_reg_date, lkr_reg_due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt1 = mysqli_prepare($conn, $sql1);
    if (!$stmt1) {
        throw new Exception("Prepare error (table 1): " . mysqli_error($conn));
    }

    $types1 = array('i','s','i','i','i','s','i','i','i','i','i','s','i','i','i','i','i','s','i','i','s','s','s');
    $type_string1 = implode('', $types1);
    
    $params1 = array(
        $student_code, $programme_batch, $university_fee_LKR, $courseFeeLKR_total, $course_fee_LKR,
        $course_fee_type_LKR, $installment_month_LKR, $registration_fee_LKR, $university_fee_GBP,
        $courseFeeGBP_total, $course_fee_GBP, $course_fee_type_GBP, $installment_month_GBP,
        $registration_fee_GBP, $university_fee_USD, $courseFeeUSD_total, $course_fee_USD,
        $course_fee_type_USD, $installment_month_USD, $registration_fee_USD, $entered_by,
        $courseFeeLKR_InstallmentDateFirst, $courseFeeLKR_InstallmentDateFirst_DUE
    );

    $bind_params1 = array($type_string1);
    foreach ($params1 as $key => $value) {
        $bind_params1[] = &$params1[$key];
    }
    call_user_func_array(array($stmt1, 'bind_param'), $bind_params1);

    if (!mysqli_stmt_execute($stmt1)) {
        throw new Exception("Execute error (table 1): " . mysqli_stmt_error($stmt1));
    }

    $last_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt1);

    // Check duplicate in table 2
    $check2 = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM installment_payment_table WHERE student_id = ? AND programme_batch = ?");
    mysqli_stmt_bind_param($check2, "is", $student_code, $programme_batch);
    mysqli_stmt_execute($check2);
    $result2 = mysqli_stmt_get_result($check2);
    $row2 = mysqli_fetch_assoc($result2);
    mysqli_stmt_close($check2);

    if ($row2['cnt'] > 0) {
        throw new Exception("Duplicate entry in installment_payment_table.");
    }

    // ==================== TABLE 2: installment_payment_table ====================
    // NOW INCLUDING: discounted_percentage and dis_yes_no
    $sql2 = "INSERT INTO installment_payment_table 
        (payment_plans_tb_id, student_id, programme_batch, unifee_lkr_total, unifee_lkr, 
        unifee_gbp_total, unifee_gbp, unifee_usd_total, unifee_usd, fee_type, 
        coursefee_total, coursefee, registrationfee, discounted_percentage, dis_yes_no) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt2 = mysqli_prepare($conn, $sql2);
    if (!$stmt2) {
        throw new Exception("Prepare error (table 2): " . mysqli_error($conn));
    }

    // Default values for discount fields
    $discounted_percentage = 0.00;  // decimal(5,2) - default 0.00
    $dis_yes_no = 0;                // tinyint - default 0
    
    // Build type string: 13 original + 2 new = 15 parameters
    // i i s i i i i i i s i i i d i
    $types2 = array('i','i','s','i','i','i','i','i','i','s','i','i','i','d','i');
    $type_string2 = implode('', $types2); // 15 characters
    
    $params2 = array(
        $last_id,                   // 1  - i
        $student_code,              // 2  - i
        $programme_batch,           // 3  - s
        $university_fee_LKR,        // 4  - i
        $university_fee_LKR,        // 5  - i
        $university_fee_GBP,        // 6  - i
        $university_fee_GBP,        // 7  - i
        $university_fee_USD,        // 8  - i
        $university_fee_USD,        // 9  - i
        $course_fee_type_LKR,       // 10 - s
        $courseFeeLKR_total,        // 11 - i
        $course_fee_LKR,            // 12 - i
        $registration_fee_LKR,      // 13 - i
        $discounted_percentage,     // 14 - d (decimal)
        $dis_yes_no                 // 15 - i (tinyint)
    );

    $bind_params2 = array($type_string2);
    foreach ($params2 as $key => $value) {
        $bind_params2[] = &$params2[$key];
    }
    call_user_func_array(array($stmt2, 'bind_param'), $bind_params2);

    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception("Execute error (table 2): " . mysqli_stmt_error($stmt2));
    }

    $installment_payment_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt2);

    // ==================== TABLE 3: installment_details_table ====================
    if (isset($_POST['installmentLKR_amount']) && is_array($_POST['installmentLKR_amount'])) {
        $amounts = $_POST['installmentLKR_amount'];
        $dates = $_POST['installmentLKR_date'];
        $numbers = $_POST['installment_number'];
        $hiddenAmounts = isset($_POST['HiddeninstallmentLKR_amount']) ? $_POST['HiddeninstallmentLKR_amount'] : array();
        $discount_types = isset($_POST['installmentLKR_discount_type']) ? $_POST['installmentLKR_discount_type'] : array();
        $discount_values = isset($_POST['installmentLKR_discount_value']) ? $_POST['installmentLKR_discount_value'] : array();
        $remarks = isset($_POST['installmentLKR_remark']) ? $_POST['installmentLKR_remark'] : array();

        for ($i = 0; $i < count($amounts); $i++) {
            $amount = str_replace(',', '', $amounts[$i]);
            $hidden_amount = isset($hiddenAmounts[$i]) ? str_replace(',', '', $hiddenAmounts[$i]) : $amount;
            $installment_num = isset($numbers[$i]) ? $numbers[$i] : ($i + 1);
            $due_date = isset($dates[$i]) ? $dates[$i] : null;
            $discount_type = isset($discount_types[$i]) ? emptyToNull($discount_types[$i]) : null;
            $discount_value = isset($discount_values[$i]) ? emptyToNull($discount_values[$i]) : null;
            $remark = isset($remarks[$i]) ? emptyToNull($remarks[$i]) : null;

            $amount = emptyToNull($amount);
            $hidden_amount = emptyToNull($hidden_amount);

            $sql3 = "INSERT INTO installment_details_table 
                (installment_payment_table_id, student_id, programme_batch, installment_numbers, 
                devided_values, installment_amount, discount_type, discount_value, due_date, remark, entered_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt3 = mysqli_prepare($conn, $sql3);
            if (!$stmt3) {
                throw new Exception("Prepare error (table 3): " . mysqli_error($conn));
            }

            $student_str = (string)$student_code;
            
            $types3 = array('i','s','s','s','d','d','s','i','s','s','s');
            $type_string3 = implode('', $types3);
            
            $params3 = array(
                $installment_payment_id, $student_str, $programme_batch, $installment_num,
                $hidden_amount, $amount, $discount_type, $discount_value,
                $due_date, $remark, $entered_by
            );

            $bind_params3 = array($type_string3);
            foreach ($params3 as $key => $value) {
                $bind_params3[] = &$params3[$key];
            }
            call_user_func_array(array($stmt3, 'bind_param'), $bind_params3);

            if (!mysqli_stmt_execute($stmt3)) {
                throw new Exception("Execute error (table 3): " . mysqli_stmt_error($stmt3));
            }
            mysqli_stmt_close($stmt3);
        }
    }

    // ==================== TABLE 4: payment_withheld_table ====================
    $program_id = emptyToNull($_POST['programme_id']);
    $batch_id = emptyToNull($_POST['batch_id']);

    if ($program_id && $batch_id) {
        $sql4 = "INSERT INTO payment_withheld_table (student_code, student_registration_id, program_id, batch_id, payment_status) 
                 VALUES (?, ?, ?, ?, ?)";

        $stmt4 = mysqli_prepare($conn, $sql4);
        if (!$stmt4) {
            throw new Exception("Prepare error (table 4): " . mysqli_error($conn));
        }

        $student_str = (string)$student_code;
        $reg_id = '';
        $status = 'withheld';

        $types4 = array('s','s','i','i','s');
        $type_string4 = implode('', $types4);
        
        $params4 = array($student_str, $reg_id, $program_id, $batch_id, $status);

        $bind_params4 = array($type_string4);
        foreach ($params4 as $key => $value) {
            $bind_params4[] = &$params4[$key];
        }
        call_user_func_array(array($stmt4, 'bind_param'), $bind_params4);

        if (!mysqli_stmt_execute($stmt4)) {
            throw new Exception("Execute error (table 4): " . mysqli_stmt_error($stmt4));
        }
        mysqli_stmt_close($stmt4);
    }

    mysqli_commit($conn);

    echo "<script>alert('Payment plan has been successfully added!');</script>";
    echo "<script>window.location.href='../add_payment_plan.php';</script>";

} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Payment Plan Error: " . $e->getMessage());
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    echo "<script>window.location.href='../add_payment_plan.php';</script>";
}
?>