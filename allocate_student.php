<?php
ob_start(); // Buffer output to prevent "Headers already sent" errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display to avoid breaking JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error_log.txt'); // Log to a local file for diagnosis

session_start();
date_default_timezone_set('Asia/Colombo');
include("database/connection.php");


// ---- PHPMailer for email notifications ----
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("vendor/autoload.php");

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$entered_by = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$temp_registration_id = isset($_POST['temp_registration_id']) ? intval($_POST['temp_registration_id']) : 0;
$selected_batch_id = isset($_POST['selected_batch_id']) ? intval($_POST['selected_batch_id']) : 0;

if ($temp_registration_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid registration ID']);
    exit();
}

if ($selected_batch_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No batch selected for
        allocation'
    ]);
    exit();
}

$batch_id = $selected_batch_id;

try {
    $conn->begin_transaction();

    // ---------------------------
    // 1. Fetch student data
    $fetch_query = "SELECT * FROM students_temporary_registration WHERE id = ?";
    $stmt = $conn->prepare($fetch_query);
    $stmt->bind_param("i", $temp_registration_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Student registration not found');
    }

    $temp_student = $result->fetch_assoc();
    $stmt->close();

    // ---------------------------
    // 2. Resolve university ID and programme_code
    $university_id = 1;
    $programme_code = null;
    if (!empty($temp_student['program'])) {
        $uni_prog_query = "SELECT university_id, program_code, duration, course_fee_lkr, course_fee_gbp, course_fee_usd,
        course_fee_euro FROM program_table WHERE program_name = ? LIMIT 1";
        $uni_prog_stmt = $conn->prepare($uni_prog_query);
        $uni_prog_stmt->bind_param('s', $temp_student['program']);
        $uni_prog_stmt->execute();
        $uni_prog_result = $uni_prog_stmt->get_result();
        if ($uni_prog_row = $uni_prog_result->fetch_assoc()) {
            $university_id = $uni_prog_row['university_id'];
            $programme_code = $uni_prog_row['program_code'];
            $program_duration = $uni_prog_row['duration'];
            $p_fee_lkr = $uni_prog_row['course_fee_lkr'];
            $p_fee_gbp = $uni_prog_row['course_fee_gbp'];
            $p_fee_usd = $uni_prog_row['course_fee_usd'];
            $p_fee_euro = $uni_prog_row['course_fee_euro'];
        }
        $uni_prog_stmt->close();
    }

    if (!$programme_code) {
        throw new Exception('Programme not found');
    }

    // ---------------------------
    // 3. Verify batch
    $batch_verify_query = "SELECT batch_name, batch_intake, attendance, awarded_by, qualification_level, intake_no,
        year_no, recognized_by, accredited_by FROM batch_table WHERE id = ? LIMIT 1";
    $batch_verify_stmt = $conn->prepare($batch_verify_query);
    $batch_verify_stmt->bind_param('i', $batch_id);
    $batch_verify_stmt->execute();
    $batch_verify_result = $batch_verify_stmt->get_result();
    if ($batch_row = $batch_verify_result->fetch_assoc()) {
        $batch_name = $batch_row['batch_name'];
        $batch_intake = $batch_row['batch_intake'];
        $attendance = $batch_row['attendance'];
        $awarded_by = $batch_row['awarded_by'];
        $qualification_level = $batch_row['qualification_level'];
        $recognized_by = $batch_row['recognized_by'];
        $accredited_by = $batch_row['accredited_by'];
        $intake_no = $batch_row['intake_no'];
        $year_no = $batch_row['year_no'];
    } else {
        throw new Exception('Selected batch not found in database');
    }
    $batch_verify_stmt->close();

    // ---------------------------
    // 4. Fetch qualifications
    $qualifications = [];

    // O/L
    $ol_query = "SELECT id FROM student_ol_results WHERE registration_id = ?";
    $ol_stmt = $conn->prepare($ol_query);
    $ol_stmt->bind_param("i", $temp_registration_id);
    $ol_stmt->execute();
    $ol_result = $ol_stmt->get_result();
    if ($ol_result->num_rows > 0)
        $qualifications[] = 'O/L';
    $ol_stmt->close();

    // A/L
    $al_query = "SELECT id FROM student_al_results WHERE registration_id = ?";
    $al_stmt = $conn->prepare($al_query);
    $al_stmt->bind_param("i", $temp_registration_id);
    $al_stmt->execute();
    $al_result = $al_stmt->get_result();
    if ($al_result->num_rows > 0)
        $qualifications[] = 'A/L';
    $al_stmt->close();

    // Academic
    $acad_query = "SELECT qualification FROM student_academic_qualifications WHERE registration_id = ?";
    $acad_stmt = $conn->prepare($acad_query);
    $acad_stmt->bind_param("i", $temp_registration_id);
    $acad_stmt->execute();
    $acad_result = $acad_stmt->get_result();
    while ($acad_row = $acad_result->fetch_assoc()) {
        if (!empty($acad_row['qualification']))
            $qualifications[] = $acad_row['qualification'];
    }
    $acad_stmt->close();

    // Other
    $other_query = "SELECT details FROM student_other_qualifications WHERE registration_id = ?";
    $other_stmt = $conn->prepare($other_query);
    $other_stmt->bind_param("i", $temp_registration_id);
    $other_stmt->execute();
    $other_result = $other_stmt->get_result();
    while ($other_row = $other_result->fetch_assoc()) {
        if (!empty($other_row['details']))
            $qualifications[] = 'Other: ' . $other_row['details'];
    }
    $other_stmt->close();

    $qualifications_str = implode(',', array_unique($qualifications));

    // ---------------------------
    // 5. Prepare student data
    $title = $temp_student['title'] ?? '';
    $first_name = $temp_student['firstname'] ?? '';
    $last_name = $temp_student['lastname'] ?? '';
    $certificate_name = $temp_student['certificate_name'] ?? $temp_student['fullname'] ?? '';
    $preferred_name = $temp_student['fullname'] ?? ($first_name . ' ' . $last_name);
    $date_of_birth = $temp_student['dob'] ?? '1990-01-01';
    $nationality = $temp_student['nationality'] ?? '';
    $permanent_address = $temp_student['permanent_address'] ?? '';
    $current_address = $temp_student['current_address'] ?? $permanent_address;
    $mobile = $temp_student['mobile'] ?? '';
    $telephone = $temp_student['home_number'] ?? $temp_student['office_number'] ?? '';
    $emergency_contact_name = $temp_student['emergency_contact'] ?? '';
    $emergency_contact_number = $temp_student['mobile'] ?? '';
    $english_ability = 1;
    $minimum_entry_qualification = 1;
    $nic = $temp_student['nic'] ?? '';
    $passport = $temp_student['passport'] ?? '';
    $personal_email = $temp_student['email'] ?? '';
    $bms_email = $temp_student['email'] ?? '';
    $occupation = '';
    $organization = '';
    $previous_organization = '';
    $active = 1;
    $student_status = 'Active';
    $transfer_status = 0;
    $remark = '';

    // ---------------------------
    // 6. Insert student
    $insert_student_query = "INSERT INTO students (
        title, first_name, last_name, certificate_name, preferred_name,
        date_of_birth, nationality, permanent_address, current_address,
        mobile, telephone, emergency_contact_name, emergency_contact_number,
        english_ability, minimum_entry_qualification, nic, passport,
        personal_email, bms_email, occupation, organization,
        previous_organization, qualifications, active, student_status,
        transfer_status, remark, entered_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $student_stmt = $conn->prepare($insert_student_query);
    $student_stmt->bind_param(
        "sssssssssssssiissssssssisiss",
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
        $qualifications_str,
        $active,
        $student_status,
        $transfer_status,
        $remark,
        $entered_by
    );

    if (!$student_stmt->execute()) {
        throw new Exception('Failed to insert student: ' . $student_stmt->error);
    }

    $new_student_code = $conn->insert_id;
    $student_stmt->close();

    // ---------------------------
    // 7. Get compulsory subjects
    $compulsory_subjects = '';
    $comp_query = "SELECT module_name FROM modules WHERE programme_id = ? AND type = 'Compulsory'";
    $comp_stmt = $conn->prepare($comp_query);
    $comp_stmt->bind_param('i', $programme_code);
    $comp_stmt->execute();
    $comp_result = $comp_stmt->get_result();
    $subjects_array = [];
    while ($comp_row = $comp_result->fetch_assoc()) {
        $subjects_array[] = $comp_row['module_name'];
    }
    $compulsory_subjects = implode(',', $subjects_array);
    $comp_stmt->close();

    // ---------------------------
    // 8. Insert allocate_programme
    $insert_allocate_query = "INSERT INTO allocate_programme (
        student_code, university_id, programme_code, batch_id,
        student_registration_id, new_student_registration_id,
        elective_subs, compulsory_sub, status, dm_remark, entered_by
        ) VALUES (?, ?, ?, ?, '', NULL, '', ?, 'active', '', ?)";

    $allocate_stmt = $conn->prepare($insert_allocate_query);
    $allocate_stmt->bind_param(
        "iiiiss",
        $new_student_code,
        $university_id,
        $programme_code,
        $batch_id,
        $compulsory_subjects,
        $entered_by
    );

    if (!$allocate_stmt->execute()) {
        throw new Exception('Failed to allocate programme: ' . $allocate_stmt->error);
    }
    $allocation_id = $conn->insert_id;
    $allocate_stmt->close();

    // ---------------------------
    // 9. Fetch payment plan from payment_batch_allocation
    $fee_query = "SELECT * FROM payment_batch_allocation WHERE programme_id = ? AND batch_id = ? LIMIT 1";
    $fee_stmt = $conn->prepare($fee_query);
    $fee_stmt->bind_param('ii', $programme_code, $batch_id);
    $fee_stmt->execute();
    $fee_result = $fee_stmt->get_result();

    if ($fee_row = $fee_result->fetch_assoc()) {
        $course_fee_lkr = $fee_row['only_course_fee'] ?? 0;
        $university_fee_LKR = 0;
        $uni_fee_gbp = $fee_row['uni_fee_gbp'] ?? 0;
        $uni_fee_usd = $fee_row['uni_fee_usd'] ?? 0;
        $uni_fee_euro = $fee_row['uni_fee_euro'] ?? 0;
        $registration_fee_LKR = $fee_row['registration_fee'] ?? 0;
        $installment_no = $fee_row['installment_no'] ?? 0;
        $register_date = $fee_row['register_date'] ?? date('Y-m-d');
    } else {
        throw new Exception('Payment plan not found for this programme and batch combination');
    }
    $fee_stmt->close();

    // ---------------------------
    // 10. Prepare payment plan variables
    $courseFeeLKR_total = $course_fee_lkr + $registration_fee_LKR;
    $course_fee_type_LKR = ($installment_no > 0) ? 'installment' : 'full';
    $installment_month_LKR = $installment_no;
    $lkr_reg_date = $register_date;
    $lkr_reg_due_date = date('Y-m-d', strtotime($lkr_reg_date . ' +07 days'));
    $programme_batch_var = $temp_student['program'] . ' - ' . $batch_name;

    // ---------------------------
    // 11. Insert into add_payment_plan_table
    $payment_plan_query = "INSERT INTO add_payment_plan_table (
        student_id, programme_batch, university_fee_LKR, courseFeeLKR_total,
        course_fee_LKR, course_fee_type_LKR, installment_month_LKR, registration_fee_LKR,
        university_fee_GBP, courseFeeGBP_total, course_fee_GBP, course_fee_type_GBP,
        installment_month_GBP, registration_fee_GBP, university_fee_USD, courseFeeUSD_total,
        course_fee_USD, course_fee_type_USD, installment_month_USD, registration_fee_USD,
        entered_by, lkr_reg_date, lkr_reg_due_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $university_fee_GBP = $uni_fee_gbp;
    $courseFeeGBP_total = 0;
    $course_fee_GBP = 0;
    $course_fee_type_GBP = 'full';
    $installment_month_GBP = 0;
    $registration_fee_GBP = 0;

    $university_fee_USD = $uni_fee_usd;
    $courseFeeUSD_total = 0;
    $course_fee_USD = 0;
    $course_fee_type_USD = 'full';
    $installment_month_USD = 0;
    $registration_fee_USD = 0;

    $payment_stmt = $conn->prepare($payment_plan_query);
    $payment_stmt->bind_param(
        "isiiisiiiiisiiiiisiisss",
        $new_student_code,
        $programme_batch_var,
        $university_fee_LKR,
        $courseFeeLKR_total,
        $course_fee_lkr,
        $course_fee_type_LKR,
        $installment_month_LKR,
        $registration_fee_LKR,
        $university_fee_GBP,
        $courseFeeGBP_total,
        $course_fee_GBP,
        $course_fee_type_GBP,
        $installment_month_GBP,
        $registration_fee_GBP,
        $university_fee_USD,
        $courseFeeUSD_total,
        $course_fee_USD,
        $course_fee_type_USD,
        $installment_month_USD,
        $registration_fee_USD,
        $entered_by,
        $lkr_reg_date,
        $lkr_reg_due_date
    );

    if (!$payment_stmt->execute()) {
        throw new Exception('Failed to create payment plan: ' . $payment_stmt->error);
    }

    $payment_plan_id = $conn->insert_id;
    $payment_stmt->close();

    // ---------------------------
    // 12. Insert into installment_payment_table safely
    $discounted_percentage = 0;
    $dis_yes_no = 'NO';

    $installment_stmt_query = "
        INSERT INTO installment_payment_table (
        payment_plans_tb_id, student_id, programme_batch,
        unifee_lkr_total, unifee_lkr, unifee_gbp_total, unifee_gbp,
        unifee_usd_total, unifee_usd, fee_type,
        coursefee_total, coursefee, registrationfee, discounted_percentage, dis_yes_no
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

    $payment_plans_tb_id_var = $payment_plan_id;
    $student_id_var = $new_student_code;
    $programme_batch_var2 = $programme_batch_var;
    $unifee_lkr_total_var = $university_fee_LKR;
    $unifee_lkr_var = $university_fee_LKR;
    $unifee_gbp_total_var = $university_fee_GBP;
    $unifee_gbp_var = $university_fee_GBP;
    $unifee_usd_total_var = $university_fee_USD;
    $unifee_usd_var = $university_fee_USD;
    $fee_type_var = $course_fee_type_LKR;
    $coursefee_total_var = $course_fee_lkr;
    $coursefee_var = $course_fee_lkr;
    $registrationfee_var = $registration_fee_LKR;
    $discounted_percentage_var = $discounted_percentage;
    $dis_yes_no_var = $dis_yes_no;

    $installment_stmt = $conn->prepare($installment_stmt_query);
    $installment_stmt->bind_param(
        "iisiiiiiisiiidi",
        $payment_plans_tb_id_var,
        $student_id_var,
        $programme_batch_var2,
        $unifee_lkr_total_var,
        $unifee_lkr_var,
        $unifee_gbp_total_var,
        $unifee_gbp_var,
        $unifee_usd_total_var,
        $unifee_usd_var,
        $fee_type_var,
        $coursefee_total_var,
        $coursefee_var,
        $registrationfee_var,
        $discounted_percentage_var,
        $dis_yes_no_var
    );

    if (!$installment_stmt->execute()) {
        throw new Exception('Failed to insert installment payment plan: ' . $installment_stmt->error);
    }
    $installment_payment_id = $conn->insert_id;
    $installment_stmt->close();


    // ---------------------------
    // 12.1 Insert into installment_details_table for each installment
    $installment_numbers = $installment_month_LKR > 0 ? $installment_month_LKR : 1; // if not installment, 1
    $discount_type = '';
    $discount_value = 0;
    $remark = '';
    $entered_by_val = $entered_by;
    $updated_by = null;

    $installment_amount = 0;
    $devided_values = "";

    // Calculate installment breakups for details table
    $installment_amounts = [];
    if ($installment_numbers > 1) {
        $base_each = floor($coursefee_var / $installment_numbers);
        $each_last = $coursefee_var - ($base_each * ($installment_numbers - 1));
        for ($i = 1; $i <= $installment_numbers; $i++) {
            if ($i == $installment_numbers) {
                $installment_amounts[] = $each_last;
            } else {
                $installment_amounts[] = $base_each;
            }
        }
    } else {
        $installment_amounts[] = $coursefee_var;
    }
    $devided_values = implode(',', $installment_amounts);
    $discount_type = 'N/A';
    $remark = '';

    for ($i = 1; $i <= $installment_numbers; $i++) {
        $installment_number = "installment_" . $i;
        // For due_date: for first installment, it's the reg date; others: +N months
        if ($i == 1) {
            $due_date = date('Y-m-d', strtotime($lkr_reg_date));
        } else {
            $due_date = date('Y-m-d', strtotime("+" . ($i - 1) . " month", strtotime($lkr_reg_date)));
        }
        $installment_amount = $installment_amounts[$i - 1];
        $discount_value = null;
        $entered_by_val = $entered_by;
        $stmt_details = $conn->prepare("
                INSERT INTO installment_details_table
                (installment_payment_table_id, student_id, programme_batch, installment_numbers, devided_values,
                installment_amount, discount_type, discount_value, due_date, remark, entered_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
        if (!$stmt_details) {
            throw new Exception('Prepare failed for installment_details_table: ' . $conn->error);
        }
        $stmt_details->bind_param(
            "isssddsisss",
            $installment_payment_id,
            $new_student_code,
            $programme_batch_var,
            $installment_number,
            $devided_values,
            $installment_amount,
            $discount_type,
            $discount_value,
            $due_date,
            $remark,
            $entered_by_val
        );
        if (!$stmt_details->execute()) {
            throw new Exception('Failed to insert installment details: ' . $stmt_details->error);
        }
    }

    $student_name_for_dues = trim($first_name . ' ' . $last_name);
    $student_registration_id = $temp_registration_id;
    $programme_batch_for_dues = $programme_batch_var;

    // Due count for bms (installments) is number of installments; for uni it's 0 (default, unless you have logic for it)
    $due_count_bms_fees = $installment_numbers;
    $due_count_uni_fees = 0;

    $remaining_full_amount = $registration_fee_LKR + $coursefee_var;
    $now = date('Y-m-d H:i:s');
    $updated_at = $now;

    $payment_method = 'N/A';

    $insert_due_query = "INSERT INTO payment_due_tables 
            (student_code, student_name, student_registration_id, programme_batch, due_count_bms_fees, due_count_uni_fees, remaining_full_amount, created_at, updated_at, payment_method)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt_payment_due = $conn->prepare($insert_due_query);
    if (!$stmt_payment_due) {
        throw new Exception('Prepare failed for payment_due_tables: ' . $conn->error);
    }

    $stmt_payment_due->bind_param(
        "ssssiidsss",
        $new_student_code,
        $student_name_for_dues,
        $student_registration_id,
        $programme_batch_for_dues,
        $due_count_bms_fees,
        $due_count_uni_fees,
        $remaining_full_amount,
        $now,
        $updated_at,
        $payment_method
    );

    if (!$stmt_payment_due->execute()) {
        throw new Exception('Failed to insert into payment_due_tables: ' . $stmt_payment_due->error);
    }
    $stmt_payment_due->close();

    // ---------------------------
    // 13. Mark temporary registration as approved

    $update_temp_query = "UPDATE students_temporary_registration SET approved = '1', approved_by = ?, batch = ?,
            std_doc_upload_btn = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_temp_query);
    $update_stmt->bind_param("ssi", $entered_by, $batch_name, $temp_registration_id);
    $update_stmt->execute();
    $update_stmt->close();

    // ---------------------------------------------------
    // 14. Generate Offer Letter PDF & Send Email
    // ---------------------------------------------------

    $email_sent_status = "Not attempted";
    $offer_letter_path = "";

    if (!empty($personal_email) && filter_var($personal_email, FILTER_VALIDATE_EMAIL)) {
        // -------------------------------------
        // PDF Generation using TCPDF (Native PHP)
        // -------------------------------------

        $cleanProgram = preg_replace('/[^A-Za-z0-9_\-]/', '', $temp_student['program']);
        if (empty($cleanProgram))
            $cleanProgram = "General";

        $cleanBatch = preg_replace('/[^A-Za-z0-9_\-]/', '', $batch_name);
        if (empty($cleanBatch))
            $cleanBatch = "Batch";

        $cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temp_student['temp_id']);
        if (empty($cleanTempId))
            $cleanTempId = "TempID_" . time();

        // Define Output Path
        $rootAppDir = __DIR__ . "/application_pdfs";
        if (!is_dir($rootAppDir)) {
            if (!mkdir($rootAppDir, 0755, true)) {
                error_log("CRITICAL: Failed to create root application_pdfs directory");
            }
        }

        $outputDir = $rootAppDir . "/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}";
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                error_log("WARNING: Failed to create nested output directory: " . $outputDir);
                // Try fallback to root folder if nested fails
                $outputDir = $rootAppDir;
            }
        }

        $pdfFilename = "Offer_Letter_{$cleanTempId}_{$new_student_code}.pdf";
        $pdfPath = $outputDir . "/" . $pdfFilename;

        // Prepare data
        $start_date_str = isset($register_date) ? date('F Y', strtotime($register_date)) : date('F Y');
        $end_date_str = isset($register_date) ? date('F Y', strtotime($register_date . ' +1 year')) : date(
            'F Y',
            strtotime('+1 year')
        );
        $deadline_str = date('d F Y', strtotime('+3 weeks'));

        $pdf_fee_str = 'LKR ' . number_format($p_fee_lkr);
        if ($p_fee_gbp > 0)
            $pdf_fee_str .= ' + GBP ' . number_format($p_fee_gbp);
        elseif ($p_fee_usd > 0)
            $pdf_fee_str .= ' + USD ' . number_format($p_fee_usd);
        elseif ($p_fee_euro > 0)
            $pdf_fee_str .= ' + EURO ' . number_format($p_fee_euro);

        $formatted_batch_intake = ($intake_no && $year_no) ? date('F', mktime(0, 0, 0, $intake_no, 1)) . ' ' . '20'
            . $year_no : '-';

        try {
            // Create new PDF document
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('BMS');
            $pdf->SetTitle('Offer Letter - ' . $temp_student['fullname']);

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins (20mm left/right, 15mm top/bottom)
            $pdf->SetMargins(20, 15, 20);
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Add a page
            $pdf->AddPage();

            // --- Logo (smaller) ---
            $logoPath = __DIR__ . '/admin/uploads/company_profiles/BMSCAMPUSLOGOFINAL.jpg';
            $logoHtml = '';

            if (file_exists($logoPath)) {
                $logoHtml = '<img src="' . $logoPath . '" height="35" />';
            } else {
                error_log("Logo file not found at: " . $logoPath);
                $logoHtml = '<div style="color:#003366; font-weight:bold; font-size:12pt;">BMS</div>';
            }

            $html = $logoHtml;
            $html .= '<div style="margin-top:5px;"></div>';

            // --- Date ---
            $current_date = date('d F Y');
            $html .= '<p style="font-family:helvetica; font-size:10pt; margin-bottom:5px;">' . $current_date . '</p>';

            // --- Student Address Block ---
            // Format address like online registration view:
            // - escape HTML
            // - break lines after commas
            // - keep any original newlines as <br />
            $address_formatted = htmlspecialchars(trim($permanent_address));
            $address_formatted = preg_replace('/,\s*/', ',<br />', $address_formatted);
            $address_formatted = nl2br($address_formatted);

            $html .= '<p style="font-family:helvetica; font-size:10pt; line-height:1.2; margin-bottom:8px;">';
            $html .= '<strong>' . htmlspecialchars($temp_student['fullname']) . '</strong><br />';
            $html .= $address_formatted;
            $html .= '</p>';

            // --- Salutation (smaller spacing) ---
            $html .= '<p style="font-family:helvetica; font-size:10pt; margin-bottom:8px;">Dear ' .
                htmlspecialchars($first_name) . ',</p>';

            // --- Title (smaller font) ---
            $is_conditional = (int) ($temp_student['conditional_offer_letter'] ?? 0);
            $cond_label = $is_conditional ? "CONDITIONAL" : "UNCONDITIONAL";
            $program_upper = strtoupper($temp_student['program']);
            $html .= '<p style="font-family:helvetica; font-size:10pt; font-weight:bold; margin-bottom:3px;"><u>' .
                $cond_label . ' OFFER - ' . $program_upper . '</u></p>';

            // --- Intro (smaller font, compact line-height) ---
            $cond_text = $is_conditional ? "conditional" : "unconditional";
            $attendance_lower = strtolower($attendance ?? 'full time');
            $intro_text = "Thank you for your application for admission to Business Management School. I am pleased to
            offer you a <strong>{$cond_text}</strong> place on the {$attendance_lower} taught programme specified above.
            Details of your programme, important dates, fees and cost are as follows:";
            $html .= '<p
                style="font-family:helvetica; font-size:10pt; line-height:1.2; text-align:justify; margin-bottom:5px;">'
                . $intro_text . '</p>';



            // --- Details Table (smaller font, less padding) ---
            $table_data = [
                ['Duration', $program_duration ?? '-'],
                ['Student NIC', $temp_student['nic'] ?? '-'],
                ['Programme Intake', $formatted_batch_intake],
                ['Attendance', $attendance ?? '-'],
                ['Awarded by', $awarded_by ?? '-']
            ];



            // Conditional rows based on program
            if ($temp_student['program'] == 'Executive Certificate in Management') {
                $table_data[] = ['Recognized By', $recognized_by ?? '-'];
            } elseif (
                in_array($temp_student['program'], [
                    'Higher Diploma in Biomedical Science',
                    'Higher Diploma in
            Biotechnology'
                ])
            ) {
                $table_data[] = ['Accredited By', $accredited_by ?? '-'];
            } elseif (
                in_array($temp_student['program'], [
                    'Graduate Diploma in Management (Level 6)',
                    'International Foundation Diploma (Applied Science) - ATHE Level 3',
                    'International Foundation Diploma (Business) - ATHE Level 3',
                    'BTEC Higher National Diploma in Business'
                ])
            ) {
                $table_data[] = ['Qualification Level', $qualification_level ?? '-'];
            }

            $table_data[] = ['Programme Fee', $pdf_fee_str];

            $html .= '<table border="1" cellpadding="3" cellspacing="0"
                style="border-collapse:collapse; width:100%; margin-bottom:5px;">';
            foreach ($table_data as $row) {
                $html .= '<tr>';
                $html .= '<td style="background-color:#f8fafc; font-weight:bold; width:35%; font-size:10pt;">' .
                    htmlspecialchars($row[0]) . '</td>';
                $html .= '<td style="font-size:10pt; width:65%;">' . htmlspecialchars($row[1]) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            // --- Conditions (if conditional) - smaller font ---
            if ($is_conditional) {
                $all_conditions = [];
                $c1 = $temp_student['conditional_offer_letter_text'] ?? '';
                $c2 = $temp_student['conditional_offer_letter_text_02'] ?? '';
                $c3 = $temp_student['conditional_offer_letter_text_03'] ?? '';
                $c4 = $temp_student['conditional_offer_letter_text_04'] ?? '';

                if ($c1)
                    $all_conditions[] = $c1;
                if ($c2)
                    $all_conditions[] = $c2;
                if ($c3)
                    $all_conditions[] = $c3;
                if ($c4)
                    $all_conditions[] = $c4;

                if (!empty($all_conditions)) {
                    $html .= '<p style="font-family:helvetica; font-size:10pt; font-weight:bold;">CONDITIONS</p>';
                    foreach ($all_conditions as $cond) {
                        if (trim($cond)) {
                            $html .= '<p style="font-family:helvetica; font-size:10pt; line-height:0.5 ">• ' .
                                htmlspecialchars(trim($cond)) . '</p>';
                        }
                    }
                }
            }

            // --- Extra Paragraphs (smaller font, compact) ---
            $extra_paragraphs = [
                "A minimum payment of LKR " . number_format($registration_fee_LKR) . " is required to confirm a place on the
            programme. Fees can be paid in full at the time of your enrolment; alternatively, you can obtain a payment
            plan from BMS Finance department.",
                "This offer has been issued in accordance with the regulations set out by Business Management School and is
            subject to the terms and conditions agreed and stipulated in the relevant documents."
            ];

            foreach ($extra_paragraphs as $para) {
                $html .= '<p style="font-family:helvetica; font-size:10pt; text-align:justify; margin-top:5px;">' .
                    htmlspecialchars($para) . '</p>';
            }

            // --- Deadline (smaller font) ---
            $html .= '<p style="font-family:helvetica; font-size:10pt; text-align:justify; margin-top:5px;">You shall
                accept the offer on or before <strong>' . $deadline_str . '</strong> with the payment of the first
                instalment.</p>';

            // --- Sign Off (reduced spacing) ---
            $html .= '<p style="font-family:helvetica; font-size:10pt; margin-top:15px;">With best wishes,</p>';
            $html .= '<div style="margin-top:20px;"></div>';
            $html .= '<p style="font-family:helvetica; font-size:10pt;"><strong>Academic Registrar,</strong><br />BMS
            </p>';


            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');

            // Output PDF to file
            $pdf->Output($pdfPath, 'F');

            $offer_letter_path = $pdfPath;

            // --- Send Email with Attachment ---
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.office365.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'alumni@bms.ac.lk';
                $mail->Password = 'prcmsddbsyxymsps';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('alumni@bms.ac.lk', 'BMS Registration');
                $mail->addAddress($personal_email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = "BMS Registration Successful - Offer Letter";

                $mail->Body = '
            <!DOCTYPE html>
            <html>

            <head>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                    }

                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        padding: 20px;
                    }

                    .header {
                        background-color: #f8f9fa;
                        padding: 0;
                        text-align: center;
                        border-bottom: 1px solid #003366;
                    }

                    .banner-img {
                        width: 100%;
                        max-width: 100%;
                        display: block;
                        margin: 0;
                    }

                    .content {
                        padding: 20px;
                    }

                    .footer {
                        font-size: 12px;
                        color: #777;
                        text-align: center;
                        margin-top: 20px;
                        border-top: 1px solid #ddd;
                        padding-top: 10px;
                    }
                </style>
            </head>

            <body>
                <div class="container">
                    <div class="header">
                        <img src="https://ims.bms.ac.lk//admin/uploads/img/Registration-form-Banner.jpg"
                            alt="BMS Banner" class="banner-img">
                    </div>
                    <div class="content">
                        <h2 style="color: #003366; margin-bottom: 20px;">Registration Successful</h2>
                        <p>Dear <strong>' . htmlspecialchars($first_name) . '</strong>,</p>
                        <p>We are pleased to inform you that your registration with BMS has been successfully processed.
                        </p>
                        <p>Please accept our congratulations on securing your place in the <strong>' .
                    htmlspecialchars($temp_student['program']) . '</strong>.</p>
                        <p>Your official <strong>Letter of Offer</strong> is attached to this email. Please review it
                            carefully as it contains important details regarding your programme, fees, and the next
                            steps.</p>
                            
                        <div style="text-align: center; margin: 20px 0;">
                            <p style="margin-bottom: 10px;">  <strong>Use this QR Code for Payment</strong></p>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&bgcolor=FFFFFF&margin=30&format=jpg&data=' . urlencode($nic) . '" 
                                 alt="Student QR Code" 
                                 style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; width: 150px; height: 150px;">
                           
                        </div>

                        <p>If you have any questions, please do not hesitate to contact our administration office.</p>
                        <br>
                        <p>Best regards,</p>
                        <p><strong>Academic Registrar</strong><br>BMS</p>
                    </div>
                    <div class="footer">
                        &copy; ' . date("Y") . ' Business Management School. All rights reserved.
                    </div>
                </div>
            </body>

            </html>';

                $mail->AltBody = "Dear $first_name, Your registration has been successful. Please find your Offer Letter PDF
            attached.";

                $mail->addAttachment($pdfPath, 'BMS_Offer_Letter.pdf');

                $mail->send();
                $email_sent_status = "Sent";
            } catch (Exception $e) {
                $email_sent_status = "Failed: " . $mail->ErrorInfo;
            }

        } catch (Exception $e) {
            error_log("TCPDF Offer Letter Error: " . $e->getMessage());
            $email_sent_status = "Failed to generate PDF: " . $e->getMessage();
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Student allocated successfully. Email status: ' . $email_sent_status,
        'student_code' => $new_student_code,
        'payment_plan_id' => $payment_plan_id,
        'data' => [
            'student_name' => $first_name . ' ' . $last_name,
            'programme' => $temp_student['program'],
            'batch' => $batch_name
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();