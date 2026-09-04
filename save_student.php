<?php
session_start();
include("database/connection.php"); // Make sure this works

// Helper function to sanitize input
function sanitize($conn, $data)
{
    return mysqli_real_escape_string($conn, trim($data));
}

// Helper function to convert input to float or NULL
function floatOrNull($conn, $value)
{
    if (!isset($value) || $value === '' || !is_numeric($value)) {
        return null;
    }
    return floatval(mysqli_real_escape_string($conn, $value));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn->begin_transaction();

    try {
        $entered_by = $_SESSION['username'] ?? 'unknown_user';

        // ===================== STUDENT DATA =====================
        $qualifications = isset($_POST['qualifications']) ? implode(',', $_POST['qualifications']) : '';
        $english_ability = isset($_POST['english_ability']) ? 1 : 0;
        $minimum_entry_qualification = isset($_POST['minimum_entry_qualification']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;
        $student_status = $active ? 'Active' : 'Inactive';

        // Improved address concatenation to avoid ", , ,"
        $permanent_address = implode(', ', array_filter([
            $_POST['street_address_1'] ?? '',
            $_POST['street_address_2'] ?? '',
            $_POST['city'] ?? '',
            $_POST['district'] ?? ''
        ]));
        $current_address = implode(', ', array_filter([
            $_POST['current_street_address_1'] ?? '',
            $_POST['current_street_address_2'] ?? '',
            $_POST['current_city'] ?? '',
            $_POST['current_district'] ?? ''
        ]));

        $student_code = isset($_POST['student_code']) ? sanitize($conn, $_POST['student_code']) : null;

        // ===================== INSERT OR UPDATE STUDENT =====================
        if ($student_code) {
            // UPDATE EXISTING STUDENT
            $stmt = $conn->prepare("
                UPDATE students SET
                    title = ?, first_name = ?, last_name = ?, certificate_name = ?, preferred_name = ?, 
                    date_of_birth = ?, nationality = ?, permanent_address = ?, current_address = ?, 
                    mobile = ?, telephone = ?, emergency_contact_name = ?, emergency_contact_number = ?, 
                    english_ability = ?, minimum_entry_qualification = ?, nic = ?, passport = ?, 
                    personal_email = ?, bms_email = ?, occupation = ?, organization = ?, 
                    previous_organization = ?, qualifications = ?, active = ?, student_status = ?, 
                    entered_by = ?
                WHERE student_code = ?
            ");
            $stmt->bind_param(
                "sssssssssssssiissssssssissi",
                $_POST['title'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['certificate_name'],
                $_POST['preferred_name'],
                $_POST['dob'],
                $_POST['nationality'],
                $permanent_address,
                $current_address,
                $_POST['mobile'],
                $_POST['telephone'],
                $_POST['emergency_contact_name'],
                $_POST['emergency_contact_number'],
                $english_ability,
                $minimum_entry_qualification,
                $_POST['nic'],
                $_POST['passport'],
                $_POST['personal_email'],
                $_POST['bms_email'],
                $_POST['occupation'],
                $_POST['organization'],
                $_POST['previous_organization'],
                $qualifications,
                $active,
                $student_status,
                $entered_by,
                $student_code
            );
            if (!$stmt->execute()) throw new Exception("Student Update Error: " . $stmt->error);
            $stmt->close();
        } else {
            // INSERT NEW STUDENT
            $stmt = $conn->prepare("
                INSERT INTO students
                (title, first_name, last_name, certificate_name, preferred_name, date_of_birth, nationality,
                 permanent_address, current_address, mobile, telephone, emergency_contact_name, emergency_contact_number,
                 english_ability, minimum_entry_qualification, nic, passport, personal_email, bms_email, occupation,
                 organization, previous_organization, qualifications, active, student_status, entered_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sssssssssssssiissssssssiss",
                $_POST['title'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['certificate_name'],
                $_POST['preferred_name'],
                $_POST['dob'],
                $_POST['nationality'],
                $permanent_address,
                $current_address,
                $_POST['mobile'],
                $_POST['telephone'],
                $_POST['emergency_contact_name'],
                $_POST['emergency_contact_number'],
                $english_ability,
                $minimum_entry_qualification,
                $_POST['nic'],
                $_POST['passport'],
                $_POST['personal_email'],
                $_POST['bms_email'],
                $_POST['occupation'],
                $_POST['organization'],
                $_POST['previous_organization'],
                $qualifications,
                $active,
                $student_status,
                $entered_by
            );
            if (!$stmt->execute()) throw new Exception("Student Insert Error: " . $stmt->error);
            $student_code = $conn->insert_id; // Get new student_code
            $stmt->close();

            // ===================== ALLOCATE PROGRAMME =====================
            // $university_id = sanitize($conn, $_POST['university_id'] ?? null);
            $university_id = isset($_POST['university_id']) && !empty($_POST['university_id']) ? sanitize($conn, $_POST['university_id']) : 1;
            $programme_code = sanitize($conn, $_POST['programme_code'] ?? null);
            $batch_id = sanitize($conn, $_POST['batch_id'] ?? null);

            if (empty($university_id) || empty($programme_code) || empty($batch_id)) {
                throw new Exception("<script>alert('Please choose University, Programme and Batch!'); window.history.back();</script>");
            }

            $student_registration_id = !empty($_POST['student_registration_id']) ? sanitize($conn, $_POST['student_registration_id']) : null;
            $compulsory_sub = isset($_POST['compulsory_modules']) ? implode(',', $_POST['compulsory_modules']) : '';
            $elective_subs = isset($_POST['elective_modules']) ? implode(',', $_POST['elective_modules']) : '';
            $dm_remark = '';

            $stmt2 = $conn->prepare("
            INSERT INTO allocate_programme
            (student_code, university_id, programme_code, batch_id, student_registration_id, elective_subs, compulsory_sub, dm_remark, entered_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt2->bind_param(
                "iiiisssss",
                $student_code,
                $university_id,
                $programme_code,
                $batch_id,
                $student_registration_id,
                $elective_subs,
                $compulsory_sub,
                $dm_remark,
                $entered_by
            );
            if (!$stmt2->execute()) throw new Exception("Allocate Programme Insert Error: " . $stmt2->error);
            $stmt2->close();

            // ===================== COMMON PROGRAMME BATCH (CHANGED) =====================
            // This is the fix: set once and reuse for all subsequent inserts
            $programme_name = sanitize($conn, $_POST['programme_name'] ?? '');
            $batch_name = sanitize($conn, $_POST['batch_name'] ?? '');
            $programme_batch = trim($programme_name . ' - ' . $batch_name); // <<<<< FIX

            // ===================== ADD PAYMENT PLAN =====================
            $register_date = sanitize($conn, $_POST['register_date'] ?? date('Y-m-d'));
            $installment_no = isset($_POST['installment_no']) && is_numeric($_POST['installment_no']) ? (int)$_POST['installment_no'] : 0;
            $installment_interval = isset($_POST['installment_interval']) && is_numeric($_POST['installment_interval']) ? floatval($_POST['installment_interval']) : 1;
            $fee_type = 'installment';
            $lkr_reg_date = $register_date;
            $lkr_reg_due_date = date('Y-m-d', strtotime("$register_date +7 days"));

            $university_fee_lkr = floatOrNull($conn, $_POST['university_fee_lkr'] ?? null);
            $course_fee_lkr = floatOrNull($conn, $_POST['course_fee_lkr'] ?? null);
            $only_course_fee = floatOrNull($conn, $_POST['only_course_fee'] ?? null);
            $registration_fee = floatOrNull($conn, $_POST['registration_fee'] ?? 0.0);
            $uni_fee_gbp = floatOrNull($conn, $_POST['uni_fee_gbp'] ?? null);
            $uni_fee_usd = floatOrNull($conn, $_POST['uni_fee_usd'] ?? null);

            $stmt3 = $conn->prepare("
            INSERT INTO add_payment_plan_table
            (student_id, programme_batch, university_fee_LKR, courseFeeLKR_total, course_fee_LKR, 
             course_fee_type_LKR, installment_month_LKR, registration_fee_LKR, university_fee_GBP, university_fee_USD, 
             entered_by, lkr_reg_date, lkr_reg_due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt3->bind_param(
                "isdddsisddsss",
                $student_code,
                $programme_batch, // <<<<< SAME VARIABLE
                $university_fee_lkr,
                $course_fee_lkr,
                $only_course_fee,
                $fee_type,
                $installment_no,
                $registration_fee,
                $uni_fee_gbp,
                $uni_fee_usd,
                $entered_by,
                $lkr_reg_date,
                $lkr_reg_due_date
            );
            if (!$stmt3->execute()) throw new Exception("Payment Plan Insert Error: " . $stmt3->error);
            $payment_plans_tb_id = $conn->insert_id;
            $stmt3->close();

            // ===================== INSTALLMENT PAYMENT TABLE =====================
            $stmt4 = $conn->prepare("
            INSERT INTO installment_payment_table 
            (payment_plans_tb_id, student_id, programme_batch, 
            unifee_lkr_total, unifee_lkr, unifee_gbp_total, unifee_gbp, 
            unifee_usd_total, unifee_usd, fee_type, coursefee_total, 
            coursefee, registrationfee, discounted_percentage, dis_yes_no) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $unifee_lkr_total = $university_fee_lkr ?? 0;
            $unifee_lkr = $university_fee_lkr ?? 0;
            $unifee_gbp_total = $uni_fee_gbp ?? 0;
            $unifee_gbp = $uni_fee_gbp ?? 0;
            $unifee_usd_total = $uni_fee_usd ?? 0;
            $unifee_usd = $uni_fee_usd ?? 0;
            $coursefee_total = $only_course_fee ?? 0;
            $coursefee = $only_course_fee ?? 0;
            $registrationfee = $registration_fee ?? 0;
            $discounted_percentage = 0.00;
            $dis_yes_no = 0;

            $stmt4->bind_param(
                "iisiiiiiisiiidi",
                $payment_plans_tb_id,
                $student_code,
                $programme_batch, // <<<<< REUSE SAME VARIABLE
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
            if (!$stmt4->execute()) throw new Exception("Installment Payment Insert Error: " . $stmt4->error);
            $installment_payment_table_id = $conn->insert_id;
            $stmt4->close();

            // ===================== INSTALLMENT DETAILS TABLE =====================
            $stmt5 = $conn->prepare("
            INSERT INTO installment_details_table 
            (installment_payment_table_id, student_id, programme_batch, installment_numbers, 
             devided_values, installment_amount, discount_type, discount_value, due_date, remark, entered_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt5) throw new Exception("Installment Details Prepare Error: " . $conn->error);

            $discount_type = 'N/A';
            $discount_value = null;
            $remark = '';

            $is_final_year = isset($_POST['is_final_year']) && $_POST['is_final_year'] === '1';
            if ($is_final_year && isset($_POST['final_installment_no']) && is_array($_POST['final_installment_no'])) {
                // Use final year installments
                $final_installment_nos = $_POST['final_installment_no'];
                $final_installment_dates = $_POST['final_installment_date'] ?? [];
                $final_installment_amounts = $_POST['final_installment_amount'] ?? [];
                
                foreach ($final_installment_nos as $index => $inst_no) {
                    $installment_number = "installment_" . $inst_no;
                    $due_date = sanitize($conn, $final_installment_dates[$index] ?? '');
                    $installment_amount = floatOrNull($conn, $final_installment_amounts[$index] ?? 0);
                    $devided_values = $installment_amount;

                    $stmt5->bind_param(
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
                        $entered_by
                    );
                    if (!$stmt5->execute()) throw new Exception("Installment Details Insert Error on installment $inst_no: " . $stmt5->error);
                }
            } else {
                // Original calculation for non-final-year programs (old code)
                $devided_values = $installment_no > 0 ? $coursefee / $installment_no : 0;
                $installment_amount = $devided_values;
                $discount_type = 'N/A';
                $discount_value = null;
                $remark = '';

                // Create initial DateTime object from lkr_reg_date
                $baseDate = new DateTime($lkr_reg_date);

                for ($i = 1; $i <= $installment_no; $i++) {
                    $installment_number = "installment_" . $i;
                    
                    // Calculate the due date for this installment
                    $dueDateObj = clone $baseDate;
                    
                    if ($installment_interval == 1) {
                        // For interval =1: add i months to registration date, keep the same day
                        $dueDateObj->modify('+' . $i . ' months');
                    } else if ($i > 1) {
                        $fixedDay = 15;
                        $months_to_add = ($i - 1) * $installment_interval;
                        $rounded_months = round($months_to_add);
                        $dueDateObj->modify('+' . $rounded_months . ' months');
                        $dueDateObj->setDate($dueDateObj->format('Y'), $dueDateObj->format('m'), $fixedDay);
                    }
                    
                    $due_date = $dueDateObj->format('Y-m-d');

                    $stmt5->bind_param(
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
                        $entered_by
                    );
                    if (!$stmt5->execute()) throw new Exception("Installment Details Insert Error on installment $i: " . $stmt5->error);
                }
            }
            $stmt5->close();

            // ===================== PAYMENT WITHHELD TABLE =====================
            $stmt6 = $conn->prepare("
            INSERT INTO payment_withheld_table 
            (student_code, program_id, batch_id, payment_status)
            VALUES (?, ?, ?, ?)
            ");
            if (!$stmt6) throw new Exception("Withheld Table Prepare Error: " . $conn->error);

            $payment_status = 'withheld';
            $stmt6->bind_param(
                "siis",
                $student_code,
                $programme_code,
                $batch_id,
                $payment_status
            );
            if (!$stmt6->execute()) throw new Exception("Withheld Table Insert Error: " . $stmt6->error);
            $stmt6->close();

            // ===================== PAYMENT DUE TABLES =====================
            $student_name = trim(($_POST['first_name'] ?? '') . ' ' . ($_POST['last_name'] ?? ''));
            $due_count_bms_fees = 0;
            $due_count_uni_fees = 0;
            $remaining_full_amount = $course_fee_lkr;
            $payment_method_due = '1';

            $stmt7 = $conn->prepare("
            INSERT INTO payment_due_tables
            (student_code, student_name, student_registration_id, programme_batch, due_count_bms_fees, due_count_uni_fees, remaining_full_amount, created_at, updated_at, payment_method)
            VALUES (?, ?, NULL, ?, ?, ?, ?, NOW(), NOW(), ?)
            ");
            if (!$stmt7) throw new Exception("Payment Due Tables Prepare Error: " . $conn->error);

            $student_code_str = (string)$student_code;
            $stmt7->bind_param(
                "sssiids",
                $student_code_str,
                $student_name,
                $programme_batch,
                $due_count_bms_fees,
                $due_count_uni_fees,
                $remaining_full_amount,
                $payment_method_due
            );
            if (!$stmt7->execute()) throw new Exception("Payment Due Tables Insert Error: " . $stmt7->error);
            $stmt7->close();
        }

        // ===================== COMMIT =====================
        $conn->commit();
        echo "<script>alert('Student Successfully Registered !'); window.location.href='studentRegister.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        $msg = str_replace("'", "\\'", $e->getMessage());
        echo "<script>alert('Transaction failed: $msg'); window.history.back();</script>";
    }

    $conn->close();
}
