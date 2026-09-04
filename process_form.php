<?php
session_start();
include("database/connection.php");

// UTF8-safe
mysqli_set_charset($conn, "utf8");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$Session_username = $_SESSION['username'] ?? 'unknown_user';

// Sanitize POST input
function sanitize($conn, $data)
{
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($data)));
}
function floatOrNull($conn, $value)
{
    return (isset($value) && is_numeric($value)) ? floatval(mysqli_real_escape_string($conn, $value)) : null;
}

try {
    mysqli_autocommit($conn, FALSE);

    // ===================== STUDENT DATA =========================
    $student_code = $_POST['student_code'] ?? null;
    $title = sanitize($conn, $_POST['title'] ?? '');
    $first_name = sanitize($conn, $_POST['first_name'] ?? '');
    $last_name = sanitize($conn, $_POST['last_name'] ?? '');
    $certificate_name = sanitize($conn, $_POST['certificate_name'] ?? '');
    $preferred_name = sanitize($conn, $_POST['preferred_name'] ?? '');
    $date_of_birth = sanitize($conn, $_POST['dob'] ?? '');
    $nationality = sanitize($conn, $_POST['nationality'] ?? '');
    $street_address_1 = sanitize($conn, $_POST['street_address_1'] ?? '');
    $street_address_2 = sanitize($conn, $_POST['street_address_2'] ?? '');
    $city = sanitize($conn, $_POST['city'] ?? '');
    $district = sanitize($conn, $_POST['district'] ?? '');
    $permanent_address = trim("$street_address_1, $street_address_2, $city, $district");
    $current_street_address_1 = sanitize($conn, $_POST['current_street_address_1'] ?? '');
    $current_street_address_2 = sanitize($conn, $_POST['current_street_address_2'] ?? '');
    $current_city = sanitize($conn, $_POST['current_city'] ?? '');
    $current_district = sanitize($conn, $_POST['current_district'] ?? '');
    $current_address = trim("$current_street_address_1, $current_street_address_2, $current_city, $current_district");
    $mobile = sanitize($conn, $_POST['mobile'] ?? '');
    $telephone = sanitize($conn, $_POST['telephone'] ?? '');
    $emergency_contact_name = sanitize($conn, $_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = sanitize($conn, $_POST['emergency_contact_number'] ?? '');
    $english_ability = isset($_POST['english_ability']) ? 1 : 0;
    $minimum_entry_qualification = isset($_POST['minimum_entry_qualification']) ? 1 : 0;
    $nic = sanitize($conn, $_POST['nic'] ?? '');
    $passport = sanitize($conn, $_POST['passport'] ?? '');
    $personal_email = sanitize($conn, $_POST['personal_email'] ?? '');
    $bms_email = sanitize($conn, $_POST['bms_email'] ?? '');
    $occupation = sanitize($conn, $_POST['occupation'] ?? '');
    $organization = sanitize($conn, $_POST['organization'] ?? '');
    $previous_organization = sanitize($conn, $_POST['previous_organization'] ?? '');
    $qualifications = isset($_POST['qualifications']) ? implode(',', $_POST['qualifications']) : '';
    $active = isset($_POST['active']) ? 1 : 0;
    $student_status = $active ? 'Active' : 'Inactive';
    $compulsory_sub = isset($_POST['compulsory_modules']) ? implode(',', $_POST['compulsory_modules']) : '';
    $elective_subs = isset($_POST['elective_modules']) ? implode(',', $_POST['elective_modules']) : '';

    // ===================== ALLOCATE PROGRAMME =========================
    $university_id = $_POST['university_id'] ?? null;
    $programme_code = $_POST['programme_code'] ?? null;
    $batch_id = $_POST['batch_id'] ?? null;
    $student_registration_id = $_POST['student_registration_id'] ?? null;

    // ===================== PAYMENT PLAN =========================
    $programme_name = sanitize($conn, $_POST['programme_name'] ?? '');
    $batch_name = sanitize($conn, $_POST['batch_name'] ?? '');
    $programme_batch = trim("$programme_name - $batch_name");

    $register_date = $_POST['register_date'] ?? date('Y-m-d');
    $installment_no = max(1, intval($_POST['installment_no'] ?? 1));
    $fee_type = 'installment';
    $lkr_reg_date = $register_date;
    $lkr_reg_due_date = date('Y-m-d', strtotime("$register_date +7 days"));

    $university_fee_lkr = floatOrNull($conn, $_POST['university_fee_lkr'] ?? 0);
    $course_fee_lkr = floatOrNull($conn, $_POST['course_fee_lkr'] ?? 0);
    $only_course_fee = floatOrNull($conn, $_POST['only_course_fee'] ?? 0);
    $registration_fee = floatOrNull($conn, $_POST['registration_fee'] ?? 0);
    $uni_fee_gbp = floatOrNull($conn, $_POST['uni_fee_gbp'] ?? 0);
    $uni_fee_usd = floatOrNull($conn, $_POST['uni_fee_usd'] ?? 0);

    // ===================== INSERT OR UPDATE STUDENT =========================
    if ($student_code) {
        $update_sql = "UPDATE students SET title=?, first_name=?, last_name=?, certificate_name=?, preferred_name=?,
            date_of_birth=?, nationality=?, permanent_address=?, current_address=?, mobile=?, telephone=?,
            emergency_contact_name=?, emergency_contact_number=?, english_ability=?, minimum_entry_qualification=?,
            nic=?, passport=?, personal_email=?, bms_email=?, occupation=?, organization=?, previous_organization=?,
            qualifications=?, active=?, student_status=?, entered_by=? WHERE student_code=?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssssiisssssssssiss",
            $title,
            $first_name,
            $last_name,
            $certificate_name,
            $preferred_name,
            $date_of_birth,
            $nationality,
            $permanent_address,
            $current_address,
            $mobile,
            $telephone,
            $emergency_contact_name,
            $emergency_contact_number,
            $english_ability,
            $minimum_entry_qualification,
            $nic,
            $passport,
            $personal_email,
            $bms_email,
            $occupation,
            $organization,
            $previous_organization,
            $qualifications,
            $active,
            $student_status,
            $Session_username,
            $student_code
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $insert_sql = "INSERT INTO students (title, first_name, last_name, certificate_name, preferred_name,
            date_of_birth, nationality, permanent_address, current_address, mobile, telephone,
            emergency_contact_name, emergency_contact_number, english_ability, minimum_entry_qualification,
            nic, passport, personal_email, bms_email, occupation, organization, previous_organization,
            qualifications, active, student_status, entered_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssssiissssssssiss",
            $title,
            $first_name,
            $last_name,
            $certificate_name,
            $preferred_name,
            $date_of_birth,
            $nationality,
            $permanent_address,
            $current_address,
            $mobile,
            $telephone,
            $emergency_contact_name,
            $emergency_contact_number,
            $english_ability,
            $minimum_entry_qualification,
            $nic,
            $passport,
            $personal_email,
            $bms_email,
            $occupation,
            $organization,
            $previous_organization,
            $qualifications,
            $active,
            $student_status,
            $Session_username
        );
        mysqli_stmt_execute($stmt);
        $student_code = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
    }

    // ===================== ALLOCATE PROGRAMME =========================
    $alloc_sql = "INSERT INTO allocate_programme (student_code, university_id, programme_code, batch_id, student_registration_id, elective_subs, compulsory_sub, entered_by)
        VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE elective_subs=VALUES(elective_subs), compulsory_sub=VALUES(compulsory_sub)";
    $stmt = mysqli_prepare($conn, $alloc_sql);
    mysqli_stmt_bind_param(
        $stmt,
        "iiiissss",
        $student_code,
        $university_id,
        $programme_code,
        $batch_id,
        $student_registration_id,
        $elective_subs,
        $compulsory_sub,
        $Session_username
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ===================== ADD PAYMENT PLAN =========================
    $stmt = $conn->prepare("INSERT INTO add_payment_plan_table
        (student_id, programme_batch, university_fee_LKR, courseFeeLKR_total, course_fee_LKR, course_fee_type_LKR,
         installment_month_LKR, registration_fee_LKR, university_fee_GBP, university_fee_USD, entered_by, lkr_reg_date, lkr_reg_due_date)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "isdddsisddsss",
        $student_code,
        $programme_batch,
        $university_fee_lkr,
        $course_fee_lkr,
        $only_course_fee,
        $fee_type,
        $installment_no,
        $registration_fee,
        $uni_fee_gbp,
        $uni_fee_usd,
        $Session_username,
        $lkr_reg_date,
        $lkr_reg_due_date
    );
    $stmt->execute();
    $payment_plans_tb_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // ===================== INSTALLMENT PAYMENT =========================
    $stmt = $conn->prepare("INSERT INTO installment_payment_table 
        (payment_plans_tb_id, student_id, programme_batch, unifee_lkr_total, unifee_lkr, unifee_gbp_total, unifee_gbp, 
        unifee_usd_total, unifee_usd, fee_type, coursefee_total, coursefee, registrationfee, discounted_percentage, dis_yes_no)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $unifee_lkr_total = $university_fee_lkr;
    $unifee_lkr = $university_fee_lkr;
    $unifee_gbp_total = $uni_fee_gbp;
    $unifee_gbp = $uni_fee_gbp;
    $unifee_usd_total = $uni_fee_usd;
    $unifee_usd = $uni_fee_usd;
    $coursefee_total = $only_course_fee;
    $coursefee = $only_course_fee;
    $registrationfee = $registration_fee;
    $discounted_percentage = 0.00;
    $dis_yes_no = 0;

    $stmt->bind_param(
        "iisiiiiiisiiidi",
        $payment_plans_tb_id,
        $student_code,
        $programme_batch,
        $unifee_lkr_total,
        $unifee_lkr,
        $unifee_gbp_total,
        $unifee_gbp,
        $unifee_usd_total,
        $unifee_usd,
        $fee_type,
        $coursefee_total,
        $coursefee,
        $registrationfee,
        $discounted_percentage,
        $dis_yes_no
    );
    $stmt->execute();
    $installment_payment_table_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // ===================== INSTALLMENT DETAILS =========================
    $stmt = $conn->prepare("INSERT INTO installment_details_table
        (installment_payment_table_id, student_id, programme_batch, installment_numbers, devided_values, installment_amount, discount_type, discount_value, due_date, remark, entered_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)");

    $devided_values = $coursefee / $installment_no;
    $installment_amount = $devided_values;
    $discount_type = 'N/A';
    $discount_value = null;
    $remark = '';

    for ($i = 1; $i <= $installment_no; $i++) {
        $installment_number = "installment_" . $i;
        $due_date = date('Y-m-d', strtotime("+$i month", strtotime($lkr_reg_date)));
        $stmt->bind_param(
            "isssddissss",
            $installment_payment_table_id,
            $student_code,
            $programme_batch,
            $installment_number,
            $devided_values,
            $installment_amount,
            $discount_type,
            $discount_value,
            $due_date,
            $remark,
            $Session_username
        );
        $stmt->execute();
    }
    mysqli_stmt_close($stmt);

    // ===================== PAYMENT WITHHELD =========================
    $stmt = $conn->prepare("INSERT INTO payment_withheld_table (student_code, program_id, batch_id, payment_status)
        VALUES (?,?,?,?)");
    $payment_status = 'withheld';
    $stmt->bind_param("siis", $student_code, $programme_code, $batch_id, $payment_status);
    $stmt->execute();
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    $_SESSION['message'] = "Student, Allocation and Payment Plan added successfully!";
    header("Location: studentRegister");
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error: " . $e->getMessage();
    header("Location: studentRegister");
    exit();
} finally {
    mysqli_autocommit($conn, TRUE);
    mysqli_close($conn);
}
