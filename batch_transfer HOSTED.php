<?php
session_start();
include("database/connection.php");
include("includes/header.php");

$Session_username = $_SESSION['username'];

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $student_id              = $_POST['student_id'];
    $from_student            = $_POST['from_student'];
    $from_university         = $_POST['from_university'];
    $from_programme          = $_POST['from_programme'];
    $from_batch              = $_POST['from_batch'];
    $from_registration_code  = $_POST['from_registration_code'];
    $bms_registration_code   = isset($_POST['bms_registration_code']) ? $_POST['bms_registration_code'] : null;
    $university_id           = $_POST['university_id'];
    $programme_code          = $_POST['programme_code'];
    $batch_id                = $_POST['batch_id'];

    // ------------------------------------------------------------------------------------------------------ 
    $paid_amount_initial     = isset($_POST['paid_amount_initial']) ? $_POST['paid_amount_initial'] : 0;
    $paid_amount_installment = isset($_POST['paid_amount_installment']) ? $_POST['paid_amount_installment'] : 0;
    $paid_amount_uni         = isset($_POST['paid_amount_uni']) ? $_POST['paid_amount_uni'] : 0;
    $program_status          = isset($_POST['program_status']) ? $_POST['program_status'] : '';

    // Fetch program_name and batch_name correctly for constructing programme_batch
    $programName = '';
    $batchName = '';

    $programQuery = "SELECT program_name FROM program_table WHERE program_code = ?";
    if ($stmt = $conn->prepare($programQuery)) {
        $stmt->bind_param("i", $programme_code);
        $stmt->execute();
        $stmt->bind_result($programName);
        $stmt->fetch();
        $stmt->close();
    }
    $batchQuery = "SELECT batch_name FROM batch_table WHERE id = ?";
    if ($stmt = $conn->prepare($batchQuery)) {
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $stmt->bind_result($batchName);
        $stmt->fetch();
        $stmt->close();
    }

    // Compose programme_batch properly
    $programme_batch = $programName . ' - ' . $batchName;

    // Capture compulsory subjects
    $compulsory_subjects = isset($_POST['compulsory_subjects']) ? $_POST['compulsory_subjects'] : [];
    $compulsory_subjects_string = implode(',', $compulsory_subjects);

    // Reset transfer_status if needed
    $checkTransferStatusQuery = "SELECT transfer_status FROM students WHERE student_code = ?";
    $checkTransferStatusStmt = $conn->prepare($checkTransferStatusQuery);
    $checkTransferStatusStmt->bind_param("s", $student_id);
    $checkTransferStatusStmt->execute();
    $checkTransferStatusStmt->bind_result($transfer_status);
    $checkTransferStatusStmt->fetch();
    $checkTransferStatusStmt->close();

    if ($transfer_status == 1) {
        $resetTransferStatusQuery = "UPDATE students SET transfer_status = 0 WHERE student_code = ?";
        $resetTransferStatusStmt = $conn->prepare($resetTransferStatusQuery);
        $resetTransferStatusStmt->bind_param("s", $student_id);
        $resetTransferStatusStmt->execute();
        $resetTransferStatusStmt->close();
    }

    // Insert into allocate_programme
    $allocateQuery = "INSERT INTO allocate_programme 
                      (student_code, university_id, programme_code, batch_id, student_registration_id, new_student_registration_id, compulsory_sub, entered_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if ($allocateStmt = $conn->prepare($allocateQuery)) {
        $student_registration_id = $from_registration_code;
        $allocateStmt->bind_param(
            "iiiissss",
            $student_id,
            $university_id,
            $programme_code,
            $batch_id,
            $student_registration_id,
            $bms_registration_code,
            $compulsory_subjects_string,
            $Session_username
        );

        if ($allocateStmt->execute()) {
            $allocated_id = $allocateStmt->insert_id;

            // ======= Add Payment Plan: insert correct programme_batch ======
            $university_fee_lkr = $_POST['university_fee_lkr'] ?? null;
            $course_fee_lkr     = $_POST['course_fee_lkr'] ?? null;
            $only_course_fee    = $_POST['only_course_fee'] ?? null;
            $fee_type           = $_POST['fee_type'] ?? 'installment';
            $installment_no     = $_POST['installment_no'] ?? 0;
            $registration_fee   = $_POST['registration_fee'] ?? 0.0;
            $uni_fee_gbp        = $_POST['uni_fee_gbp'] ?? null;
            $uni_fee_usd        = $_POST['uni_fee_usd'] ?? null;
            $lkr_reg_date       = $_POST['register_date'] ?? null;
            $lkr_reg_due_date   = date('Y-m-d', strtotime("$lkr_reg_date +7 days"));
            $entered_by         = $Session_username;

            $stmt3 = $conn->prepare("INSERT INTO add_payment_plan_table
                (student_id, programme_batch, university_fee_LKR, courseFeeLKR_total, course_fee_LKR, course_fee_type_LKR,
                 installment_month_LKR, registration_fee_LKR, university_fee_GBP, university_fee_USD,
                 entered_by, lkr_reg_date, lkr_reg_due_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt3) {
                $stmt3->bind_param(
                    "isdddsisddsss",
                    $student_id,
                    $programme_batch,
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
                if (!$stmt3->execute()) {
                    echo "<script>alert('Payment Plan Insert Error: " . $stmt3->error . "');</script>";
                }
                $payment_plans_tb_id = $stmt3->insert_id;
                $stmt3->close();
            }

            // ======= Add Installment Payment correct programme_batch ======
            $student_code = $student_id;
            $stmt4 = $conn->prepare("INSERT INTO installment_payment_table 
                (payment_plans_tb_id, student_id, programme_batch, 
                unifee_lkr_total, unifee_lkr, unifee_gbp_total, unifee_gbp, 
                unifee_usd_total, unifee_usd, fee_type, coursefee_total, 
                coursefee, registrationfee, discounted_percentage, dis_yes_no) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt4) {
                $unifee_lkr_total      = $university_fee_lkr ?? 0;
                $unifee_lkr            = $university_fee_lkr ?? 0;
                $unifee_gbp_total      = $uni_fee_gbp ?? 0;
                $unifee_gbp            = $uni_fee_gbp ?? 0;
                $unifee_usd_total      = $uni_fee_usd ?? 0;
                $unifee_usd            = $uni_fee_usd ?? 0;
                $coursefee_total       = $only_course_fee ?? 0;
                $coursefee             = $only_course_fee ?? 0;
                $registrationfee       = $registration_fee ?? 0;
                $discounted_percentage = 0.00;
                $dis_yes_no            = 0;

                $stmt4->bind_param(
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

                if ($stmt4->execute()) {
                    $installment_payment_table_id = $stmt4->insert_id;
                    // Adjust registration fee if same program
                    $registrationfee = floatval($registration_fee ?? 0);
                    $paid_amount_initial = floatval($paid_amount_initial ?? 0);

                    if ($program_status === 'same_program') {
                        $adjusted_registration_fee = $registrationfee - $paid_amount_initial;
                        if ($adjusted_registration_fee < 0) $adjusted_registration_fee = 0;

                        $updateStmt = $conn->prepare("UPDATE installment_payment_table SET registrationfee = ? WHERE id = ?");
                        if ($updateStmt) {
                            $updateStmt->bind_param("di", $adjusted_registration_fee, $installment_payment_table_id);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }

                        // Uni fees adjustment
                        if ($paid_amount_uni > 0) {
                            $checkStmt = $conn->prepare("SELECT unifee_lkr, unifee_gbp, unifee_usd FROM installment_payment_table WHERE id = ?");
                            $checkStmt->bind_param("i", $installment_payment_table_id);
                            $checkStmt->execute();
                            $checkStmt->bind_result($uniLKR, $uniGBP, $uniUSD);
                            $checkStmt->fetch();
                            $checkStmt->close();

                            if ($uniLKR > 0) {
                                $newUniLKR = $uniLKR - $paid_amount_uni;
                                if ($newUniLKR < 0) $newUniLKR = 0;
                                $updateUniStmt = $conn->prepare("UPDATE installment_payment_table SET unifee_lkr = ? WHERE id = ?");
                                $updateUniStmt->bind_param("di", $newUniLKR, $installment_payment_table_id);
                                $updateUniStmt->execute();
                                $updateUniStmt->close();
                            } elseif ($uniGBP > 0) {
                                $newUniGBP = $uniGBP - $paid_amount_uni;
                                if ($newUniGBP < 0) $newUniGBP = 0;
                                $updateUniStmt = $conn->prepare("UPDATE installment_payment_table SET unifee_gbp = ? WHERE id = ?");
                                $updateUniStmt->bind_param("di", $newUniGBP, $installment_payment_table_id);
                                $updateUniStmt->execute();
                                $updateUniStmt->close();
                            } elseif ($uniUSD > 0) {
                                $newUniUSD = $uniUSD - $paid_amount_uni;
                                if ($newUniUSD < 0) $newUniUSD = 0;
                                $updateUniStmt = $conn->prepare("UPDATE installment_payment_table SET unifee_usd = ? WHERE id = ?");
                                $updateUniStmt->bind_param("di", $newUniUSD, $installment_payment_table_id);
                                $updateUniStmt->execute();
                                $updateUniStmt->close();
                            }
                        }
                    }
                } else {
                    echo "<script>alert('Installment Payment Insert Error: " . $stmt4->error . "');</script>";
                }
                $stmt4->close();
            }

            // ======= Add Installment Details correct programme_batch ======
            if ($fee_type === 'installment' && $installment_no > 0 && isset($installment_payment_table_id)) {
                $coursefee = $only_course_fee;
                $devided_values   = $coursefee / $installment_no;
                $installment_amount = $devided_values;
                $stmt5 = $conn->prepare("INSERT INTO installment_details_table 
                    (installment_payment_table_id, student_id, programme_batch, installment_numbers, 
                    devided_values, installment_amount, discount_type, discount_value, due_date, remark, entered_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt5) {
                    $discount_type  = 'N/A';
                    $discount_value = null;
                    $remark         = '';

                    // Paid amount already received
                    $remaining_paid = floatval($_POST['paid_amount_installment'] ?? 0);

                    for ($i = 1; $i <= $installment_no; $i++) {
                        $installment_number = "installment_" . $i;
                        $due_date = date('Y-m-d', strtotime("+$i month", strtotime($lkr_reg_date)));

                        // Calculate actual installment amount after deducting already paid amount
                        if ($program_status === 'same_program' && $remaining_paid > 0) {
                            if ($remaining_paid >= $installment_amount) {
                                $installment_amount_adjusted = 0;
                                $remaining_paid -= $installment_amount;
                            } else {
                                $installment_amount_adjusted = $installment_amount - $remaining_paid;
                                $remaining_paid = 0;
                            }
                        } else {
                            $installment_amount_adjusted = $installment_amount;
                        }
                        $stmt5->bind_param(
                            "isssddissss",
                            $installment_payment_table_id,
                            $student_code,
                            $programme_batch, // use correct value here
                            $installment_number,
                            $devided_values,
                            $installment_amount_adjusted,
                            $discount_type,
                            $discount_value,
                            $due_date,
                            $remark,
                            $Session_username
                        );
                        if (!$stmt5->execute()) {
                            echo "<script>alert('Installment Details Insert Error on installment $i: " . $stmt5->error . "');</script>";
                        }
                    }
                    $stmt5->close();
                }
            }

            // ======= PAYMENT WITHHELD TABLE with correct IDs ======
            $stmt6 = $conn->prepare("INSERT INTO payment_withheld_table (
                student_code, program_id, batch_id, payment_status
            ) VALUES (?, ?, ?, ?)");
            if ($stmt6) {
                $payment_status = 'withheld';
                $stmt6->bind_param(
                    "siis",
                    $student_code,
                    $programme_code,
                    $batch_id,
                    $payment_status
                );
                if (!$stmt6->execute()) {
                    echo "<script>alert('Withheld Table Insert Error: " . $stmt6->error . "');</script>";
                }
                $stmt6->close();
            }

            // ======= STUDENT TRANSFER ENTRY ======
            $query = "INSERT INTO student_transfer 
                (student_id, from_student, from_university, from_programme, from_batch, 
                 from_registration_code, university_id, programme_code, batch_id, allocated_id, entered_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param(
                    "sssssssssss",
                    $student_id,
                    $from_student,
                    $from_university,
                    $from_programme,
                    $from_batch,
                    $from_registration_code,
                    $university_id,
                    $programme_code,
                    $batch_id,
                    $allocated_id,
                    $Session_username
                );
                if ($stmt->execute()) {
                    echo "<script>
                        alert('Student Transfer, Allocation & Payment Plan have been successfully recorded!');
                        window.location.href = window.location.href;
                    </script>";
                    $updateQuery = "UPDATE students SET transfer_status = 1 WHERE student_code = ?";
                    if ($updateStmt = $conn->prepare($updateQuery)) {
                        $updateStmt->bind_param("s", $student_id);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
                $stmt->close();
            }
        } else {
            echo "<script>alert('Error inserting into allocate_programme: " . $allocateStmt->error . "');</script>";
        }
        $allocateStmt->close();
    }
}
?>

<!-- The rest of your page (HTML form etc.) remains unchanged - the JS handles user input and population as before -->

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
                    <h4 class="h4 mb-0 text-gray-800">Student Batch Transfer</h4>
                </div>

                <!-- Filter Form -->
                <form action="batch_transfer.php" method="POST">
                    <div class="row mb-5">
                        <div class="col-md-6 mb-3">
                            <label for="select_student" class="form-label">Select Student:</label>
                            <select name="student_id" id="select_student" class="form-control select2" required>
                                <option value="">-- Select Student --</option>
                                <?php
                                $query = "SELECT student_code, nic, first_name, last_name FROM students WHERE student_status = 'transferred' AND transfer_status = 0";

                                $result = $conn->query($query);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['student_code']}'>{$row['nic']} - {$row['first_name']} {$row['last_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- From Transfer -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    From
                                </div>
                                <div class="card-body">
                                    <!-- Student -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="from_student">Student</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" id="from_student" name="from_student" class="form-control" placeholder="Student Name" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- University -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="from_university">University</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" id="from_university" name="from_university" class="form-control" placeholder="From University" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Programme -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="from_programme">Programme</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" id="from_programme" name="from_programme" class="form-control" placeholder="From Programme" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Batch -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="from_batch">Batch</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" id="from_batch" name="from_batch" class="form-control" placeholder="From Batch" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <!-- Paid amount initial -->
                                            <div class="col-md-4">
                                                <label for="paid_amount_initial" class="text-danger">Paid Amount (initial)</label>
                                                <input type="text" id="paid_amount_initial" name="paid_amount_initial" class="form-control" placeholder="Paid Amount initial" readonly>
                                            </div>

                                            <!-- Paid amount installment -->
                                            <div class="col-md-4">
                                                <label for="paid_amount_installment" class="text-danger">Paid Amount (installment)</label>
                                                <input type="text" id="paid_amount_installment" name="paid_amount_installment" class="form-control" placeholder="Paid Amount installment" readonly>
                                            </div>

                                            <!-- Paid amount uni -->
                                            <div class="col-md-4">
                                                <label for="paid_amount_uni" class="text-danger">Paid Amount (uni)</label>
                                                <input type="text" id="paid_amount_uni" name="paid_amount_uni" class="form-control" placeholder="Paid Amount Uni" readonly>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- To Transfer -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    Transfer To
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4"> <label for="university">University</label></div>
                                            <div class="col-md-8">
                                                <select id="university" name="university_id" class="form-control select2" required></select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4"> <label for="programme">Programme</label></div>
                                            <div class="col-md-8">
                                                <select id="programme" name="programme_code" class="form-control select2" required></select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4"> <label for="batch">Batch</label></div>
                                            <div class="col-md-8">
                                                <select required id="batch" name="batch_id" class="form-control select2" required></select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Registration Code -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="from_registration_code">Registration Code</label>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" readonly id="from_registration_code" name="from_registration_code" class="form-control" placeholder="New Registration Number" style="border: 2px solid red;">
                                            </div>
                                        </div>
                                    </div>
                                    <!-- New BMS Registration Code Auto Gen -->
                                    <!-- New BMS Registration Code Auto Gen -->
                                    <div class="form-group row">
                                        <label for="bms_registration_code" class="col-md-4 col-form-label">NEW BMS Registration ID (AG)</label>
                                        <div class="col-md-8">
                                            <input type="text"
                                                id="bms_registration_code"
                                                name="bms_registration_code"
                                                class="form-control"
                                                placeholder="Auto Generated Registration Number"
                                                readonly
                                                style="border: 2px solid red;">
                                        </div>
                                    </div>



                                    <!-- New section to display subjects -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-8" id="compulsory_subjects"></div>
                                        </div>
                                    </div>
                                </div>


                                <input type="hidden" id="programme_name" name="programme_name" value="">
                                <input type="hidden" id="batch_name" name="batch_name" value="">
                                <input type="hidden" id="course_fee_lkr" name="course_fee_lkr" value="">
                                <input type="hidden" id="uni_fee_gbp" name="uni_fee_gbp" value="">
                                <input type="hidden" id="uni_fee_usd" name="uni_fee_usd" value="">
                                <input type="hidden" id="uni_fee_euro" name="uni_fee_euro" value="">
                                <input type="hidden" id="register_date" name="register_date" value="">
                                <input type="hidden" id="installment_no" name="installment_no" value="">
                                <input type="hidden" id="registration_fee" name="registration_fee" value="">
                                <input type="hidden" id="only_course_fee" name="only_course_fee" value="">
                                <input type="hidden" id="program_status" name="program_status" value="">



                                <div class="card-body">
                                    <!-- -------- new ------  -->
                                    <!-- New section for displaying modules -->
                                    <div class="row" id="modules-container" style="display: none;">
                                        <div class="col-md-6">
                                            <h6>Compulsory Modules</h6>
                                            <div id="compulsory-modules-container"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Elective Modules</h6>
                                            <div id="elective-modules-container"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <div id="paymentTableContainer"></div>
                                </div>

                            </div>
                            <div class="mt-4 text-right">
                                <button type="submit" class="btn btn-primary" id="transfer_sbt_btn">Submit Transfer</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row mb-5 mt-5">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                            <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="fas fa-plus-circle"></i>
                            </span> &nbsp;&nbsp;&nbsp;&nbsp;
                            <h6 class="mb-0 me-2"></h6>
                        </div>
                        <div class="card-body">
                            <!-- Students Table -->
                            <div class="table-responsive mb-4">
                                <table id="studentsTable" class="table table-striped table-bordered" style="width: 100%; font-size: 11px;">
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#select_student').on('change', function() {
                const fromProg = $(this).find(':selected').data('from-programme') || '';
                $('#from_programme').val(fromProg).trigger('input');
            });

            $('#programme').on('change', function() {
                let fromValue = $('#from_programme').val().trim().toLowerCase();

                // Get the text of the selected option from the #programme dropdown
                const selectedProgramText = $(this).find('option:selected').text().trim();

                // Update the #programme_name input field with the selected text.
                // This ensures the input correctly displays the chosen program name.
                $('#programme_name').val(selectedProgramText);

                // Now, get the 'toValue' from the #programme_name input field,
                // as per the instruction, ensuring it uses the correctly displayed value.
                let toValue = $('#programme_name').val().trim().toLowerCase();

                console.log("FROM:", fromValue, "TO:", toValue); // for debugging

                if (!fromValue || !toValue) return;

                let programStatus;
                if (fromValue === toValue) {
                    programStatus = "same_program";
                    $('#program_status').val(programStatus);
                    console.log(programStatus);

                } else {
                    programStatus = "different_program";
                    $('#program_status').val(programStatus);
                    console.log(programStatus);
                }
            });
        });
    </script>




    <script>
        $(document).ready(function() {
            $('.select2').select2();
            $('#transfer_sbt_btn').hide();

            // Load universities on page load
            $.ajax({
                url: 'Batch_transer/fetch_universities.php',
                type: 'GET',
                success: function(data) {
                    $('#university').html(data);
                }
            });

            // When university is selected, load programs
            $('#university').on('change', function() {
                const universityId = $(this).val();
                if (universityId) {
                    $.ajax({
                        // url: 'Batch_transer/fetch_programs.php',
                        url: 'Batch_transer/fetch_programs_all_programs.php',
                        type: 'POST',
                        data: {
                            university_id: universityId
                        },
                        success: function(data) {
                            $('#programme').html(data);
                            $('#batch').html('<option value="">-- Select Batch --</option>');
                        }
                    });
                } else {
                    $('#programme').html('<option value="">-- Select Programme --</option>');
                    $('#batch').html('<option value="">-- Select Batch --</option>');
                }
            });

            // When program is selected, load batches
            $('#programme').on('change', function() {
                const programId = $(this).val();
                const universityId = $('#university').val();
                if (programId && universityId) {
                    $.ajax({
                        url: 'Batch_transer/fetch_batches.php',
                        type: 'POST',
                        data: {
                            program_id: programId,
                            university_id: universityId
                        },
                        success: function(data) {
                            $('#batch').html(data);
                        }
                    });
                } else {
                    $('#batch').html('<option value="">-- Select Batch --</option>');
                }
            });

            // When a student is selected from the dropdown
            $('#select_student').on('change', function() {
                const studentId = $(this).val();
                console.log('Selected student ID:', studentId); // Debug log

                if (studentId) {
                    $.ajax({
                        url: 'Batch_transer/fetch_student_data.php',
                        type: 'POST',
                        data: {
                            id: studentId
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Response received:', response); // Debug log
                            if (!response.error) {
                                // Update the form fields with student data
                                $('#from_student').val(`${response.first_name} ${response.last_name}`).prop('readonly', true);
                                $('#from_university').val(response.university).prop('readonly', true);
                                $('#from_programme').val(response.programme).prop('readonly', true);
                                $('#from_batch').val(response.batch).prop('readonly', true);
                                $('#from_registration_code').val(response.student_registration_id).prop('readonly', false);
                                $('#bms_registration_code').val(response.new_student_registration_id).prop('readonly', false);

                                // Set default red background color
                                $('#from_registration_code').css({
                                    'background-color': 'red',
                                    'color': 'white'
                                });



                                let totalInstallment = 0;

                                for (const [installment, amount] of Object.entries(response.installments)) {
                                    const key = installment.toLowerCase().replace(/\s/g, ''); // remove spaces and lowercase

                                    if (key === 'initialpayment') {
                                        // Initial Payment goes here
                                        $('#paid_amount_initial').val(amount).prop('readonly', true);
                                    } else {
                                        // Sum all other installments
                                        totalInstallment += parseFloat(amount);
                                    }

                                    console.log(`${installment}: ${amount}`);
                                }

                                // Set the summed total to the installment input
                                $('#paid_amount_installment').val(totalInstallment.toFixed(2)).prop('readonly', true);

                                // Set university total
                                console.log('Total University Paid:', response.total_uni_paid);
                                $('#paid_amount_uni').val(response.total_uni_paid).prop('readonly', true);


                                // ---------------------------- 


                                // Real-time checking for registration code
                                $('#from_registration_code').on('input', function() {
                                    const inputValue = $(this).val();
                                    if (inputValue === response.student_registration_id) {
                                        $(this).css('background-color', 'red'); // Keep background red if it matches
                                    } else {
                                        $(this).css('background-color', 'green'); // Reset to default color if it doesn't match
                                        $('button[type="submit"]').show();
                                    }
                                });

                            } else {
                                console.log('Error in response:', response.error); // Debug log
                                $('#from_student, #from_university, #from_programme, #from_batch, #from_registration_code,#bms_registration_code').val('');
                                alert('Student data not found');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', xhr.responseText); // More detailed error log
                            console.log('Status:', status);
                            console.log('Error:', error);
                            alert('Error fetching student data. Please try again.');
                        }
                    });
                } else {
                    // Clear fields if no student selected
                    $('#from_student, #from_university, #from_programme, #from_batch, #from_registration_code,#bms_registration_code').val('');
                }
            });

            // When program is selected, load subjects
            $('#programme').on('change', function() {
                const programId = $(this).val();
                const universityId = $('#university').val();
                if (programId && universityId) {
                    $.ajax({
                        url: 'Batch_transer/fetch_subjects.php',
                        type: 'POST',
                        data: {
                            program_id: programId,
                            university_id: universityId
                        },
                        success: function(data) {
                            const subjects = JSON.parse(data);
                            // Clear the existing subjects
                            $('#compulsory_subjects').html('');
                            // Hide the compulsory subjects initially
                            $('#compulsory_subjects').hide();

                            subjects.compulsory.forEach(function(subject) {
                                $('#compulsory_subjects').append(`<div><input type="checkbox" name="compulsory_subjects[]" value="${subject}" checked> ${subject}</div>`);
                            });

                        }
                    });
                } else {
                    $('#compulsory_subjects').html('');

                }
            });
        });
    </script>

    <script>
        // On programme change -> load modules
        $('#programme').on('change', function() {
            const programId = $(this).val();
            const universityId = $('#university').val();
            $('#batch').html('<option value="">-- Select Batch --</option>');
            $('#modules-container').hide(); // Hide modules container initially

            if (programId && universityId) {
                $.ajax({
                    url: 'allocateProgram_files/get_modules.php',
                    type: 'GET',
                    data: {
                        programme_code: programId
                    },
                    dataType: 'json',
                    success: function(data) {
                        // Clear both sections
                        $('#compulsory-modules-container').empty();
                        $('#elective-modules-container').empty();
                        $('#modules-container').show(); // Show modules container

                        if (data.length > 0) {
                            $.each(data, function(key, value) {
                                const moduleType = value.type; // 'Compulsory' or 'Elective'
                                const moduleName = value.module_name;

                                // Create the module checkbox HTML
                                let moduleHTML;

                                if (moduleType === 'Compulsory') {
                                    moduleHTML = `
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="compulsory_modules[]" value="${moduleName}" checked>
                                                <label class="form-check-label">${moduleName}</label>
                                            </div>
                                        `;
                                    $('#compulsory-modules-container').append(moduleHTML);
                                } else if (moduleType === 'Elective') {
                                    moduleHTML = `
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="elective_modules[]" value="${moduleName}">
                                                <label class="form-check-label">${moduleName}</label>
                                            </div>
                                        `;
                                    $('#elective-modules-container').append(moduleHTML);
                                }
                            });
                        } else {
                            $('#modules-container').append('<p>No modules found for this programme.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#modules-container').empty().append('<p>Error fetching modules.</p>');
                        console.error('Error:', error);
                    }
                });
            }
        });

        $('#programme, #batch').change(function() {
            let programmeId = $('#programme').val();
            let batchId = $('#batch').val();

            if (programmeId !== '' && batchId !== '') {
                $.ajax({
                    url: 'fetch_payment_data.php',
                    type: 'POST',
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#paymentTableContainer').html(response.table);

                        if (response.data) {
                            $('#programme_name').val(response.data.program_name);
                            $('#batch_name').val(response.data.batch_name);
                            $('#course_fee_lkr').val(response.data.course_fee_lkr);
                            $('#uni_fee_gbp').val(response.data.uni_fee_gbp);
                            $('#uni_fee_usd').val(response.data.uni_fee_usd);
                            $('#uni_fee_euro').val(response.data.uni_fee_euro);
                            $('#register_date').val(response.data.register_date);
                            $('#installment_no').val(response.data.installment_no);
                            $('#registration_fee').val(response.data.registration_fee);
                            $('#only_course_fee').val(response.data.only_course_fee);
                            // $('#sbt_btn').show();
                            $('#transfer_sbt_btn').show();
                        } else {
                            $('#programme_name, #batch_name, #course_fee_lkr, #uni_fee_gbp, #uni_fee_usd, #uni_fee_euro, #register_date, #installment_no, #registration_fee, #only_course_fee').val('');
                            // $('#sbt_btn').hide();
                            $('#transfer_sbt_btn').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error fetching payment data: ' + error);
                    }
                });
            } else {
                $('#paymentTableContainer').html('');
                $('#programme_name, #batch_name, #course_fee_lkr, #uni_fee_gbp, #uni_fee_usd, #uni_fee_euro, #register_date, #installment_no, #registration_fee, #only_course_fee').val('');
                // $('#sbt_btn').hide();
                $('#transfer_sbt_btn').hide();
            }
        });
    </script>
</div>
</body>

</html>