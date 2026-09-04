<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

$Session_username = $_SESSION['username'];

if (isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];

    $program = $_POST['program'] ?? '';
    $batch = $_POST['batch'] ?? '';
    $university_id = $_POST['university_id'] ?? '';

    $compulsoryModules = $_POST['compulsory_modules'] ?? [];
    $electiveModules = $_POST['elective_modules'] ?? [];

    // $compulsoryModulesString = !empty($compulsoryModules) ? implode(',', $compulsoryModules) : '';
    // $electiveModulesString = !empty($electiveModules) ? implode(',', $electiveModules) : '';
    
     $compulsoryModulesString = !empty($compulsoryModules) ? implode(',', array_map('trim', $compulsoryModules)) : '';
$electiveModulesString = !empty($electiveModules) ? implode(',', array_map('trim', $electiveModules)) : '';


    // Start transaction
    $conn->begin_transaction();

    try {
        if (($handle = fopen($file, 'r')) !== FALSE) {
            fgetcsv($handle); // Skip header
            $row_num = 1;

            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                $row_num++;

                // ✅ Convert date (5/11/1998 → 1998-11-05)
                $dob = null;
                if (!empty($data[6])) {
                    $timestamp = strtotime(str_replace('/', '-', trim($data[6])));
                    $dob = $timestamp ? date('Y-m-d', $timestamp) : '2000-01-01';
                } else {
                    $dob = '2000-01-01'; // Default if no DOB
                }

                // Sanitize and set defaults for required fields
                $title = !empty(trim($data[1])) ? trim($data[1]) : 'Mr';
                $first_name = !empty(trim($data[2])) ? trim($data[2]) : 'Unknown';
                $last_name = !empty(trim($data[3])) ? trim($data[3]) : 'Unknown';
                $certificate_name = !empty(trim($data[4])) ? trim($data[4]) : $first_name . ' ' . $last_name;
                $preferred_name = !empty(trim($data[5])) ? trim($data[5]) : $certificate_name;
                $nationality = !empty(trim($data[7])) ? trim($data[7]) : 'Sri Lankan';
                $permanent_address = !empty(trim($data[8])) ? trim($data[8]) : '';
                $current_address = !empty(trim($data[9])) ? trim($data[9]) : $permanent_address;
                $mobile = !empty(trim($data[10])) ? trim($data[10]) : '0000000000';
                $telephone = !empty(trim($data[11])) ? trim($data[11]) : '';
                $emergency_contact_name = !empty(trim($data[12])) ? trim($data[12]) : 'N/A';
                $emergency_contact_number = !empty(trim($data[13])) ? trim($data[13]) : '0000000000';
                $english_ability = !empty(trim($data[14])) ? trim($data[14]) : '1';
                $minimum_entry_qualification = !empty(trim($data[15])) ? trim($data[15]) : '1';
                $nic = !empty(trim($data[16])) ? trim($data[16]) : '';
                $passport = !empty(trim($data[17])) ? trim($data[17]) : '';
                $personal_email = !empty(trim($data[18])) ? trim($data[18]) : '';
                $bms_email = !empty(trim($data[19])) ? trim($data[19]) : '';
                $occupation = !empty(trim($data[20])) ? trim($data[20]) : '';
                $organization = !empty(trim($data[21])) ? trim($data[21]) : '';
                $previous_organization = !empty(trim($data[22])) ? trim($data[22]) : '';
                $qualifications = !empty(trim($data[23])) ? trim($data[23]) : '';
                $active = !empty(trim($data[24])) ? trim($data[24]) : '1';

                // ✅ Insert into students table (exclude student_code — auto_increment)
                $stmt1 = $conn->prepare("INSERT INTO students (
                    title, first_name, last_name, certificate_name, preferred_name, date_of_birth, nationality, 
                    permanent_address, current_address, mobile, telephone, emergency_contact_name, emergency_contact_number, 
                    english_ability, minimum_entry_qualification, nic, passport, personal_email, bms_email, occupation, organization, 
                    previous_organization, qualifications, active, entered_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt1->bind_param(
                    "sssssssssssssssssssssssss",
                    $title,
                    $first_name,
                    $last_name,
                    $certificate_name,
                    $preferred_name,
                    $dob,
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
                    $Session_username
                );

                if (!$stmt1->execute()) {
                    throw new Exception("Error inserting into students (row $row_num): " . $stmt1->error);
                }

                $student_id = $conn->insert_id;

                // ----------------------------------------------
                // Insert into allocate_programme
                // ----------------------------------------------
                $registration_number = $data[25] ?? null;
                $elective_subs = !empty($electiveModulesString) ? $electiveModulesString : ($data[26] ?? '');
                $compulsory_sub = !empty($compulsoryModulesString) ? $compulsoryModulesString : ($data[27] ?? '');

                if (!empty($program) && !empty($batch) && !empty($university_id)) {
                    $stmt2 = $conn->prepare("INSERT INTO allocate_programme (
                        student_code, student_registration_id, programme_code, batch_id, university_id, 
                        elective_subs, compulsory_sub, entered_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt2->bind_param(
                        "isiiisss",
                        $student_id,
                        $registration_number,
                        $program,
                        $batch,
                        $university_id,
                        $elective_subs,
                        $compulsory_sub,
                        $Session_username
                    );

                    if (!$stmt2->execute()) {
                        throw new Exception("Error inserting into allocate_programme (row $row_num): " . $stmt2->error);
                    }
                    $stmt2->close();
                }

                // ----------------------------------------------
                // Insert into add_payment_plan_table
                // ----------------------------------------------
                $programme_batch = ($_POST['programme_name'] ?? '') . " - " . ($_POST['batch_name'] ?? '');
                $course_fee_LKR = $_POST['only_course_fee'] ?? 0;
                $registration_fee_LKR = $_POST['registration_fee'] ?? 0;
                $courseFeeLKR_total = $_POST['course_fee_lkr'] ?? 0;
                $university_fee_LKR = $_POST['uni_fee_lkr'] ?? 0;
                $university_fee_GBP = $_POST['uni_fee_gbp'] ?? 0;
                $university_fee_USD = $_POST['uni_fee_usd'] ?? 0;
                $installment_month_LKR = $_POST['installment_no'] ?? 0;
                $lkr_reg_date = $_POST['register_date'] ?? date('Y-m-d');
                $lkr_reg_due_date = date('Y-m-d', strtotime($lkr_reg_date . ' +7 days'));
                $fee_type = 'installment';

                $stmt3 = $conn->prepare("INSERT INTO add_payment_plan_table (
                    student_id, programme_batch, university_fee_LKR, courseFeeLKR_total, course_fee_LKR, 
                    course_fee_type_LKR, installment_month_LKR, registration_fee_LKR,
                    university_fee_GBP, university_fee_USD, entered_by, lkr_reg_date, lkr_reg_due_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt3->bind_param(
                    "isiiisiiiisss",
                    $student_id,
                    $programme_batch,
                    $university_fee_LKR,
                    $courseFeeLKR_total,
                    $course_fee_LKR,
                    $fee_type,
                    $installment_month_LKR,
                    $registration_fee_LKR,
                    $university_fee_GBP,
                    $university_fee_USD,
                    $Session_username,
                    $lkr_reg_date,
                    $lkr_reg_due_date
                );

                if (!$stmt3->execute()) {
                    throw new Exception("Error inserting into add_payment_plan_table (row $row_num): " . $stmt3->error);
                }

                $payment_plans_tb_id = $conn->insert_id;

                // ----------------------------------------------
                // Insert into installment_payment_table
                // ----------------------------------------------
                $stmt4 = $conn->prepare("INSERT INTO installment_payment_table (
                    payment_plans_tb_id, student_id, programme_batch, 
                    unifee_lkr_total, unifee_lkr, unifee_gbp_total, unifee_gbp, 
                    unifee_usd_total, unifee_usd, fee_type, coursefee_total, 
                    coursefee, registrationfee, discounted_percentage, dis_yes_no
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $discounted_percentage = 0.00;
                $dis_yes_no = 0;

                $stmt4->bind_param(
                    "iisiiiiiisiiidi",
                    $payment_plans_tb_id,
                    $student_id,
                    $programme_batch,
                    $university_fee_LKR,
                    $university_fee_LKR,
                    $university_fee_GBP,
                    $university_fee_GBP,
                    $university_fee_USD,
                    $university_fee_USD,
                    $fee_type,
                    $course_fee_LKR,
                    $course_fee_LKR,
                    $registration_fee_LKR,
                    $discounted_percentage,
                    $dis_yes_no
                );

                if (!$stmt4->execute()) {
                    throw new Exception("Installment Payment Insert Error (row $row_num): " . $stmt4->error);
                }

                $installment_payment_table_id = $conn->insert_id;

                // ----------------------------------------------
                // Insert into installment_details_table
                // ----------------------------------------------
                $installments = (int)$installment_month_LKR;
                $start_date = new DateTime($lkr_reg_date);
                $installment_interval = isset($_POST['installment_interval']) ? floatval($_POST['installment_interval']) : 1;

                // Check if program is final year
                $is_final_year = false;
                $final_year_installments = [];
                $program_id = (int)$program;
                $batch_id = (int)$batch;
                $program_check_stmt = $conn->prepare("SELECT cetegory FROM program_table WHERE program_code = ?");
                $program_check_stmt->bind_param("i", $program_id);
                $program_check_stmt->execute();
                $program_result = $program_check_stmt->get_result();
                if ($program_result->num_rows > 0) {
                    $program_row = $program_result->fetch_assoc();
                    $is_final_year = ($program_row['cetegory'] === 'final_year');
                }
                $program_check_stmt->close();

                if ($is_final_year) {
                    // Fetch final year installments
                    $final_inst_stmt = $conn->prepare("SELECT installment_no, instalment_date, instalment_amount FROM final_yeat_instalment_data WHERE program_id = ? AND batch_id = ? ORDER BY installment_no ASC");
                    $final_inst_stmt->bind_param("ii", $program_id, $batch_id);
                    $final_inst_stmt->execute();
                    $final_inst_result = $final_inst_stmt->get_result();
                    while ($inst_row = $final_inst_result->fetch_assoc()) {
                        $final_year_installments[] = $inst_row;
                    }
                    $final_inst_stmt->close();
                }

                $stmt5 = $conn->prepare("INSERT INTO installment_details_table (
                    installment_payment_table_id, student_id, programme_batch, installment_numbers, 
                    devided_values, installment_amount, discount_type, discount_value, due_date, remark, entered_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($is_final_year && !empty($final_year_installments)) {
                    // Use final year installments
                    foreach ($final_year_installments as $inst_row) {
                        $installment_label = "installment_" . $inst_row['installment_no'];
                        $installment_amount = floatval($inst_row['instalment_amount']);
                        $due_date = $inst_row['instalment_date'];
                        $devided_values = $installment_amount;
                        $discount_type = 'N/A';
                        $discount_value = 0;
                        $remark = '';

                        $stmt5->bind_param(
                            "isssddsisss",
                            $installment_payment_table_id,
                            $student_id,
                            $programme_batch,
                            $installment_label,
                            $devided_values,
                            $installment_amount,
                            $discount_type,
                            $discount_value,
                            $due_date,
                            $remark,
                            $Session_username
                        );

                        if (!$stmt5->execute()) {
                            throw new Exception("Installment Details Insert Error (row $row_num): " . $stmt5->error);
                        }
                    }
                } else {
                    // Original calculation for non-final-year programs
                    $per_installment = $installments > 0 ? $course_fee_LKR / $installments : 0;
                    
                    for ($i = 1; $i <= $installments; $i++) {
                        $dueDateObj = clone $start_date;
                        
                        if ($installment_interval == 1) {
                            // For 1 month interval, first installment is registration date + 1 month, then each subsequent +1 month
                            $dueDateObj->modify("+$i months");
                        } else if ($i > 1) {
                            // For other intervals, first installment is registration date, then use fixed 15th day
                            $fixedDay = 15;
                            $months_to_add = ($i - 1) * $installment_interval;
                            $rounded_months = round($months_to_add);
                            $dueDateObj->modify("+$rounded_months months");
                            $dueDateObj->setDate($dueDateObj->format('Y'), $dueDateObj->format('m'), $fixedDay);
                        }
                        
                        $due_date = $dueDateObj->format('Y-m-d');
                        $installment_label = "installment_$i";
                        $discount_type = 'N/A';
                        $discount_value = 0;
                        $remark = '';

                        $stmt5->bind_param(
                            "isssddsisss",
                            $installment_payment_table_id,
                            $student_id,
                            $programme_batch,
                            $installment_label,
                            $per_installment,
                            $per_installment,
                            $discount_type,
                            $discount_value,
                            $due_date,
                            $remark,
                            $Session_username
                        );

                        if (!$stmt5->execute()) {
                            throw new Exception("Installment Details Insert Error (row $row_num): " . $stmt5->error);
                        }
                    }
                }

                // ----------------------------------------------
                // Insert into payment_withheld_table
                // ----------------------------------------------
                $stmt6 = $conn->prepare("INSERT INTO payment_withheld_table (
                    student_code, program_id, batch_id, payment_status
                ) VALUES (?, ?, ?, ?)");

                $program_id = (int)$program;
                $batch_id = (int)$batch;
                $payment_status = 'withheld';

                $stmt6->bind_param("siis", $student_id, $program_id, $batch_id, $payment_status);

                if (!$stmt6->execute()) {
                    throw new Exception("Error inserting into payment_withheld_table (row $row_num): " . $stmt6->error);
                }

                // ✅ Close all prepared statements
                $stmt1->close();
                $stmt3->close();
                $stmt4->close();
                $stmt5->close();
                $stmt6->close();
            }

            fclose($handle);

            // Commit transaction if all are successful
            $conn->commit();
            echo "<script>alert('✅ Student Data Imported Successfully');</script>";
            echo '<script>window.location.href = "uploadStudents";</script>';
            exit;
        } else {
            throw new Exception("Error opening the file.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_msg = addslashes($e->getMessage());
        echo "<script>alert('❌ Import Failed: " . $error_msg . "'); window.history.back();</script>";
        exit;
    }
} else {
    echo "<script>alert('⚠️ No file uploaded.'); window.history.back();</script>";
}

$conn->close();
