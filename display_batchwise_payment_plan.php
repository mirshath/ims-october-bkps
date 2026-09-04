<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}
$Session_username = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data

    $student_code = $_POST['student_codes'] ?? NULL;
    $batch_id = $_POST['batch_id'] ?? NULL;
    $program_id = $_POST['program_id'] ?? NULL;

    // ----------------------------------------------------- 

    // Reg start date
    $courseFeeLKR_InstallmentDateFirst = $_POST['courseFeeLKRInstallmentDateFirst'] ?? NULL;

    // Create a DateTime object from the existing date
    $dateTimeFirst = new DateTime($courseFeeLKR_InstallmentDateFirst);

    // Add 7 days to the date
    $dateTimeFirst->modify('+7 days');

    // Create the new variable
    $courseFeeLKR_InstallmentDateFirst_DUE = $dateTimeFirst->format('Y-m-d');

    // echo "Reg Date Here: " . $courseFeeLKR_InstallmentDateFirst;
    // echo "<br>";
    // echo "Reg + 7 Days: " . $courseFeeLKR_InstallmentDateFirst_DUE;
    // ----------------------------------------------------- 



    $batch_query = "SELECT * FROM batch_table WHERE id = ?";
    $batch_stmt = $conn->prepare($batch_query);
    $batch_stmt->bind_param("s", $batch_id);
    $batch_stmt->execute();
    $batch_result = $batch_stmt->get_result();
    $batch = $batch_result->fetch_assoc();

    $program_query = "SELECT * FROM program_table WHERE program_code = ?";
    $program_stmt = $conn->prepare($program_query);
    $program_stmt->bind_param("s", $program_id);
    $program_stmt->execute();
    $program_result = $program_stmt->get_result();
    $program = $program_result->fetch_assoc();
    
    $is_final_year = ($program['cetegory'] === 'final_year');
    $final_year_installments = [];
    
    if ($is_final_year) {
        $final_inst_query = "SELECT * FROM final_yeat_instalment_data WHERE program_id = ? AND batch_id = ? ORDER BY installment_no ASC";
        $final_inst_stmt = $conn->prepare($final_inst_query);
        $final_inst_stmt->bind_param("ii", $program_id, $batch_id);
        $final_inst_stmt->execute();
        $final_inst_result = $final_inst_stmt->get_result();
        while ($inst = $final_inst_result->fetch_assoc()) {
            $final_year_installments[] = $inst;
        }
        $final_inst_stmt->close();
    }

    // Fetch Installment Interval from payment_batch_allocation
    $payment_allocation_query = "SELECT Installment_Interval FROM payment_batch_allocation WHERE programme_id = ? AND batch_id = ?";
    $payment_allocation_stmt = $conn->prepare($payment_allocation_query);
    $payment_allocation_stmt->bind_param("si", $program_id, $batch_id);
    $payment_allocation_stmt->execute();
    $payment_allocation_result = $payment_allocation_stmt->get_result();
    $payment_allocation = $payment_allocation_result->fetch_assoc();
    $installment_interval = $payment_allocation['Installment_Interval'] ?? 1;

    // University Fee   
    $university_fee_LKR = $_POST['uniFeeLKR'] ?? NULL;
    $university_fee_GBP = $_POST['uniFeeGBP'] ?? NULL;
    $uniFeeUSD = $_POST['uniFeeUSD'] ?? NULL;

    // Retrieve hidden input values for course fees
    $courseFeeLKR_total = $_POST['courseFeeInputLKR_initial_value'] ?? NULL; // Added to capture LKR course fee
    $courseFeeGBP_total = $_POST['courseFeeInputGBP_initial_value'] ?? NULL; // Added to capture GBP course fee
    $courseFeeUSD_total = $_POST['courseFeeInputUSD_initial_value'] ?? NULL; // Added to capture USD course fee

    // Course Fee
    $course_fee_LKR = $_POST['courseFeeLKR'] ?? NULL;
    $course_fee_GBP = $_POST['courseFeeGBP'] ?? NULL;
    $course_fee_USD = $_POST['courseFeeUSD'] ?? NULL;

    // Registration Fee
    $registration_fee_LKR = $_POST['registrationFeeLKR'] ?? NULL;
    $registration_fee_GBP = $_POST['registrationFeeGBP'] ?? NULL;
    $registration_fee_USD = $_POST['registrationFeeUSD'] ?? NULL;

    // Installment Data
    $installment_month_LKR = $_POST['installmentsLKR'] ?? NULL;
    $installment_month_GBP = $_POST['installmentsGBP'] ?? NULL;
    $installment_month_USD = $_POST['installmentsUSD'] ?? NULL;

    // Payment Type Data
    $course_fee_type_GBP = $_POST['courseFeeGBP_type'] ?? NULL;
    $course_fee_type_LKR = $_POST['courseFeeLKR_type'] ?? NULL;
    $course_fee_type_USD = $_POST['courseFeeUSD_type'] ?? NULL;

    // Entered Date
    $enteredDate = $_POST['courseFeeLKRInstallmentDateFirst'] ?? date('Y-m-d');
    $dateTime_for_insert = new DateTime($enteredDate);

?>

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
                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Payment Plan Details</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="add_payment_plan_folder/batchwise_insert_payment_plan.php">
                                        <input type="hidden" name="username" value="<?php echo $Session_username; ?>">

                                        <div class="row">
                                            <!-- Student Details  -->
                                            <div class="col-md-12">
                                                <?php
                                                echo "<h4 class='text-center mt-2 mb-4'>" . $program['program_name'] . " - " . $batch['batch_name'] . "</h4>";
                                                ?>

                                            </div>
                                            <!-- University Fee: -->
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>University Fee:</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php
                                                        if ($university_fee_LKR) echo "<p><strong>Rs:</strong> $university_fee_LKR.00</p>";
                                                        if ($university_fee_GBP) echo "<p><strong>£:</strong> $university_fee_GBP.00</p>";
                                                        if ($uniFeeUSD) echo "<p><strong>$:</strong> $uniFeeUSD.00</p>";
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Full Course Fee & Remind Amount: -->
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>Full Course Fee & Remind Amount:</h6>
                                                    </div>
                                                    <div class="card-body">

                                                        <table class="table table-striped table-hover">
                                                            <tr>
                                                                <td>Payment Types:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($course_fee_type_GBP) {
                                                                        echo "<strong></strong> $course_fee_type_GBP ";
                                                                        echo "<script>console.log('GBP Fee Type: $course_fee_type_GBP');</script>";
                                                                    }
                                                                    if ($course_fee_type_LKR) {
                                                                        echo "<strong></strong> $course_fee_type_LKR ";
                                                                        echo "<script>console.log('LKR Fee Type: $course_fee_type_LKR');</script>";
                                                                    }
                                                                    if ($course_fee_type_USD) {
                                                                        echo "<strong></strong> $course_fee_type_USD";
                                                                        echo "<script>console.log('USD Fee Type: $course_fee_type_USD');</script>";
                                                                    }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Installement Months:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($installment_month_LKR) echo "<strong></strong>  $installment_month_LKR ";
                                                                    if ($installment_month_GBP) echo "<strong></strong> $installment_month_GBP ";
                                                                    if ($installment_month_USD) echo "<strong></strong> $installment_month_USD";
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Full Course fee:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($courseFeeLKR_total) echo "<strong>Full Rs:</strong> $courseFeeLKR_total ";
                                                                    if ($courseFeeGBP_total) echo "<strong>Full £:</strong> $courseFeeGBP_total ";
                                                                    if ($courseFeeUSD_total) echo "<strong>Full $:</strong> $courseFeeUSD_total";
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Registration fee:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($registration_fee_LKR) echo "<strong>Rs:</strong> $registration_fee_LKR ";
                                                                    if ($registration_fee_GBP) echo "<strong>£:</strong> $registration_fee_GBP ";   
                                                                    if ($registration_fee_USD) echo "<strong>$:</strong> $registration_fee_USD";
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Balance Course fee:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($course_fee_LKR) echo "<strong>Rs:</strong> $course_fee_LKR ";
                                                                    if ($course_fee_GBP) echo "<strong>£:</strong> $course_fee_GBP ";
                                                                    if ($course_fee_USD) echo "<strong>$:</strong> $course_fee_USD";
                                                                    ?>
                                                                </td>
                                                            </tr>



                                                            <tr>
                                                                <td>Registration Date:</td>
                                                                <td><?php echo $courseFeeLKR_InstallmentDateFirst; ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Registration Date + 7 Days:</td>
                                                                <td><?php echo $courseFeeLKR_InstallmentDateFirst_DUE; ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Installment Interval:</td>
                                                                <td>
                                                                    <?php 
                                                                    $interval_text = $installment_interval . ' month';
                                                                    if ($installment_interval != 1) {
                                                                        $interval_text .= 's';
                                                                    }
                                                                    echo "<strong>$interval_text</strong>";
                                                                    ?>
                                                                </td>
                                                            </tr>

                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Installment Interval Selection: -->
                                            <div class="col-md-12 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>Installment Interval :</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-2">
                                                                <label for="installmentInterval">Select Interval:</label>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <select id="installmentInterval" class="form-control">
                                                                    <option value="1" <?php echo ($installment_interval == 1) ? 'selected' : ''; ?>>1 Month</option>
                                                                    <option value="1.5" <?php echo ($installment_interval == 1.5) ? 'selected' : ''; ?>>1.5 Months</option>
                                                                    <option value="2" <?php echo ($installment_interval == 2) ? 'selected' : ''; ?>>2 Months</option>
                                                                    <option value="2.5" <?php echo ($installment_interval == 2.5) ? 'selected' : ''; ?>>2.5 Months</option>
                                                                    <option value="3" <?php echo ($installment_interval == 3) ? 'selected' : ''; ?>>3 Months</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ------------------------------------------------------------------------------------------------------  -->

                                            <!-- Installment Details: -->
                                            <div class="col-md-12 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>Installment Details :</h6>
                                                    </div>

                                                    <div class="card-body">
                                                        <?php

                                                        function calculateInstallments($amount, $installments, $currency, &$dateTime, $paymentType, $installment_interval)
                                                        {
                                                            if ($amount) {
                                                                // If full payment, show one row
                                                                if ($paymentType == 'full') {
                                                                    $installments = 1;
                                                                }

                                                                $installmentAmount = $amount / $installments;

                                                                for ($i = 1; $i <= $installments; $i++) {
                                                                    $dueDate = clone $dateTime;
                                                                    
                                                                    if ($installment_interval == 1) {
                                                                        // For interval =1: add i months to registration date, keep the same day
                                                                        $dueDate->modify('+' . $i . ' months');
                                                                    } else if ($i > 1) {
                                                                        // For other intervals, original code: first is registration date, then fixed 15th
                                                                        $fixedDay = 15;
                                                                        $months_to_add = ($i - 1) * $installment_interval;
                                                                        $rounded_months = round($months_to_add);
                                                                        $dueDate->modify('+' . $rounded_months . ' months');
                                                                        $dueDate->setDate($dueDate->format('Y'), $dueDate->format('m'), $fixedDay);
                                                                    }

                                                        ?>
                                                                    <!-- --------------------------------------------------------------------------------------  -->
                                                                    <table class='table table-bordered table-striped table-hover'>
                                                                        <tr class="installment-row">
                                                                            <td><?php echo ($paymentType == 'full') ? "Full Payment" : "Installment $i"; ?></td>

                                                                            <td>
                                                                                <!-- Hidden input for installment number -->
                                                                                <input type='hidden' name='installment_number[]' value='installment_<?php echo $i; ?>'>
                                                                                <!-- Installment Amount (readonly) -->
                                                                                <input type='text' class='form-control installment-amount' id='installmentAmount_<?php echo $i; ?>' name='installment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>'>
                                                                            </td>

                                                                            <td>
                                                                                <!-- Discount Type Selection -->
                                                                                <select class='form-control discount-type select2' name='installment<?php echo $currency; ?>_discount_type[]' onchange="toggleDiscountInput(this)">
                                                                                    <option value='N/A'>N/A</option>
                                                                                    <option value='Value'>Value</option>
                                                                                    <option value='Percentage'>Percentage</option>
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <!-- Discount Value (hidden by default) -->
                                                                                <input type='text' class='form-control discount-value' name='installment<?php echo $currency; ?>_discount_value[]' style='display: none;' placeholder='Enter discount' oninput="calculateDiscount(this)">
                                                                            </td>

                                                                            <td class="row_hidden" style="display: none;">
                                                                                <input type='text' class='form-control installment-amount' id='HiddeninstallmentAmount_<?php echo $i; ?>' name='Hiddeninstallment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>' readonly>
                                                                            </td>

                                                                            <td>
                                                                                <!-- Due Date (editable) -->
                                                                                <input type='date' class='form-control installment-date' data-index='<?php echo $i; ?>' name='installment<?php echo $currency; ?>_date[]' value='<?php echo $dueDate->format('Y-m-d'); ?>'>
                                                                            </td>
                                                                            <td>
                                                                                <!-- Remark (editable) -->
                                                                                <input type='text' class='form-control' name='installment<?php echo $currency; ?>_remark[]' placeholder='Enter remark'>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                    <script>
                                                                        $(document).ready(function() {
                                                                            $(".select2").select2({
                                                                                width: '100%'
                                                                            });
                                                                        })

                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                            const form = document.querySelector('form'); // Select the form element
                                                                            const installmentRows = document.querySelectorAll('.installment-row'); // All rows with the class 'installment-row'

                                                                            // Function to add hidden inputs dynamically
                                                                            function addHiddenInput(name, value) {
                                                                                const hiddenInput = document.createElement('input');
                                                                                hiddenInput.type = 'hidden';
                                                                                hiddenInput.name = name;
                                                                                hiddenInput.value = value;
                                                                                form.appendChild(hiddenInput);
                                                                            }

                                                                            // Add hidden inputs dynamically on form submission
                                                                            form.addEventListener('submit', function() {
                                                                                installmentRows.forEach(function(row, index) {

                                                                                    // Add hidden input for discount type
                                                                                    const discountType = row.querySelector('select[name="installment<?php echo $currency; ?>_discount_type[]"]').value;
                                                                                    addHiddenInput('installment<?php echo $currency; ?>_discount_type[]', discountType);

                                                                                    // Add hidden input for discount value
                                                                                    const discountValue = row.querySelector('input[name="installment<?php echo $currency; ?>_discount_value[]"]').value;
                                                                                    addHiddenInput('installment<?php echo $currency; ?>_discount_value[]', discountValue);

                                                                                    // Add hidden input for remark
                                                                                    const remark = row.querySelector('input[name="installment<?php echo $currency; ?>_remark[]"]').value;
                                                                                    addHiddenInput('installment<?php echo $currency; ?>_remark[]', remark);
                                                                                });
                                                                            });
                                                                        });

                                                                        // Toggles the visibility of the discount input field based on discount type selection
                                                                        function toggleDiscountInput(selectElement) {
                                                                            var inputField = selectElement.closest('tr').querySelector('.discount-value');
                                                                            var previousType = inputField.getAttribute('data-previous-type') || 'N/A';
                                                                            var currentType = selectElement.value;

                                                                            // Clear the input value when switching between Value and Percentage
                                                                            if (previousType !== currentType && currentType !== 'N/A') {
                                                                                inputField.value = '';
                                                                                calculateDiscount(inputField); // Recalculate with empty value
                                                                            }

                                                                            if (currentType === 'N/A') {
                                                                                inputField.style.display = 'none';
                                                                                inputField.value = ''; // Clear value when hidden
                                                                                calculateDiscount(inputField); // Recalculate installment amount
                                                                            } else {
                                                                                inputField.style.display = 'block';
                                                                                // Update placeholder based on type
                                                                                inputField.placeholder = currentType === 'Value' ? 'Enter discount amount' : 'Enter discount percentage';
                                                                            }

                                                                            // Store the current type for next comparison
                                                                            inputField.setAttribute('data-previous-type', currentType);
                                                                        }

                                                                        // Calculates the discount and updates the installment amount
                                                                        function calculateDiscount(inputElement) {
                                                                            var row = inputElement.closest('tr');
                                                                            var installmentAmountInput = row.querySelector('.installment-amount');
                                                                            var originalAmount = parseFloat(installmentAmountInput.getAttribute('data-original-amount')); // Store original amount in a data attribute
                                                                            var discountType = row.querySelector('.discount-type').value;
                                                                            var discountValue = parseFloat(inputElement.value) || 0; // Default to 0 if empty or invalid
                                                                            var finalAmount = originalAmount;

                                                                            // Validation for Value type discount
                                                                            if (discountType === 'Value') {
                                                                                if (discountValue > originalAmount) {
                                                                                    alert('Discount value cannot be greater than the installment amount of ' + originalAmount.toFixed(2));
                                                                                    inputElement.value = originalAmount.toFixed(2); // Set to max allowed value
                                                                                    discountValue = originalAmount;
                                                                                }
                                                                                finalAmount = originalAmount - discountValue;
                                                                            }
                                                                            // Validation for Percentage type discount
                                                                            else if (discountType === 'Percentage') {
                                                                                if (discountValue > 100) {
                                                                                    alert('Percentage discount cannot be greater than 100%');
                                                                                    inputElement.value = '100';
                                                                                    discountValue = 100;
                                                                                }
                                                                                finalAmount = originalAmount - (originalAmount * (discountValue / 100));
                                                                            }

                                                                            // Update the installment amount input field with the final amount after discount
                                                                            installmentAmountInput.value = finalAmount.toFixed(2); // Format to 2 decimal places
                                                                        }

                                                                        // Initialize original amount in a data attribute
                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                            var installmentAmountInputs = document.querySelectorAll('.installment-amount');
                                                                            installmentAmountInputs.forEach(function(input) {
                                                                                var originalAmount = parseFloat(input.value.replace(/,/g, '')); // Remove commas for calculation
                                                                                input.setAttribute('data-original-amount', originalAmount); // Store original amount
                                                                                
                                                                                // Add event listener to handle manual edits to installment amount
                                                                                input.addEventListener('input', function() {
                                                                                    var newAmount = parseFloat(this.value.replace(/,/g, ''));
                                                                                    if (!isNaN(newAmount)) {
                                                                                        // Update the data-original-amount to the new value
                                                                                        this.setAttribute('data-original-amount', newAmount);
                                                                                    }
                                                                                });
                                                                            });
                                                                        });
                                                                    </script>
                                                                    <!-- --------------------------------------------------------------------------------------  -->
                                                        <?php
                                                                    $dateTime = $dueDate;
                                                                }
                                                            }
                                                        }

                                                        // Check each currency's payment type and call the function
                                                        if ($is_final_year && !empty($final_year_installments)) {
                                                            // Display final year installments
                                                            foreach ($final_year_installments as $index => $inst) {
                                                                $i = $inst['installment_no'];
                                                                $installmentAmount = $inst['instalment_amount'];
                                                                $dueDate = new DateTime($inst['instalment_date']);
                                                                $currency = 'LKR'; // Assuming LKR for final year, adjust if needed
                                                        ?>
                                                                <!-- --------------------------------------------------------------------------------------  -->
                                                                <table class='table table-bordered table-striped table-hover'>
                                                                    <tr class="installment-row">
                                                                        <td>Installment <?php echo $i; ?></td>

                                                                        <td>
                                                                            <!-- Hidden input for installment number -->
                                                                            <input type='hidden' name='installment_number[]' value='installment_<?php echo $i; ?>'>
                                                                            <!-- Installment Amount (readonly) -->
                                                                            <input type='text' class='form-control installment-amount' id='installmentAmount_<?php echo $i; ?>' name='installment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>'>
                                                                        </td>

                                                                        <td>
                                                                            <!-- Discount Type Selection -->
                                                                            <select class='form-control discount-type select2' name='installment<?php echo $currency; ?>_discount_type[]' onchange="toggleDiscountInput(this)">
                                                                                <option value='N/A'>N/A</option>
                                                                                <option value='Value'>Value</option>
                                                                                <option value='Percentage'>Percentage</option>
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <!-- Discount Value (hidden by default) -->
                                                                            <input type='text' class='form-control discount-value' name='installment<?php echo $currency; ?>_discount_value[]' style='display: none;' placeholder='Enter discount' oninput="calculateDiscount(this)">
                                                                        </td>

                                                                        <td class="row_hidden" style="display: none;">
                                                                            <input type='text' class='form-control installment-amount' id='HiddeninstallmentAmount_<?php echo $i; ?>' name='Hiddeninstallment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>' readonly>
                                                                        </td>

                                                                        <td>
                                                                            <!-- Due Date (editable) -->
                                                                            <input type='date' class='form-control installment-date' data-index='<?php echo $i; ?>' name='installment<?php echo $currency; ?>_date[]' value='<?php echo $dueDate->format('Y-m-d'); ?>'>
                                                                        </td>
                                                                        <td>
                                                                            <!-- Remark (editable) -->
                                                                            <input type='text' class='form-control' name='installment<?php echo $currency; ?>_remark[]' placeholder='Enter remark'>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                        <?php
                                                            }
                                                        } else {
                                                            // Original calculation for non-final-year programs
                                                            if ($course_fee_type_GBP) {
                                                                calculateInstallments($course_fee_GBP, ($course_fee_type_GBP == 'installment' ? $installment_month_GBP : 1), 'GBP', $dateTime_for_insert, $course_fee_type_GBP, $installment_interval);
                                                            }
                                                            if ($course_fee_type_LKR) {
                                                                calculateInstallments($course_fee_LKR, ($course_fee_type_LKR == 'installment' ? $installment_month_LKR : 1), 'LKR', $dateTime_for_insert, $course_fee_type_LKR, $installment_interval);
                                                            }
                                                            if ($course_fee_type_USD) {
                                                                calculateInstallments($course_fee_USD, ($course_fee_type_USD == 'installment' ? $installment_month_USD : 1), 'USD', $dateTime_for_insert, $course_fee_type_USD, $installment_interval);
                                                            }
                                                        }

                                                        // ----------------------------------------------------------------
                                                        // ----------------------------------------------------------------
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ----------------------------------------------------------------------------  -->
                                            <div class="col-md-6">
                                                <!-- Add hidden inputs for installment amounts and dates -->
                                                <div class="text-right">
                                                    <button type="submit" class="btn btn-success w-25" name="saveButton">Save</button>
                                                </div>
                                            </div>
                                        </div>


                                        <?php

                                        if (is_string($student_code)) {
                                            $student_code = explode(',', $student_code); // Convert to array
                                        }

                                        // Now you can safely loop through $student_code if it is an array
                                        foreach ($student_code as $code): ?>
                                            <input type="hidden" name="student_codes[]" value="<?php echo htmlspecialchars($code); ?>">
                                        <?php endforeach;
                                        ?>

                                        <input type="hidden" name="programmeBatch" value="<?php echo $program['program_name'] . ' - ' . $batch['batch_name']; ?>">
                                        <input type="hidden" name="uniFeeLKR" value="<?php echo $university_fee_LKR; ?>">
                                        <input type="hidden" name="uniFeeGBP" value="<?php echo $university_fee_GBP; ?>">
                                        <input type="hidden" name="uniFeeUSD" value="<?php echo $uniFeeUSD; ?>">
                                        <input type="hidden" name="courseFeeInputLKR_initial_value" value="<?php echo $courseFeeLKR_total; ?>">
                                        <input type="hidden" name="courseFeeInputGBP_initial_value" value="<?php echo $courseFeeGBP_total; ?>">
                                        <input type="hidden" name="courseFeeInputUSD_initial_value" value="<?php echo $courseFeeUSD_total; ?>">
                                        <input type="hidden" name="courseFeeLKR" value="<?php echo $course_fee_LKR; ?>">
                                        <input type="hidden" name="courseFeeGBP" value="<?php echo $course_fee_GBP; ?>">
                                        <input type="hidden" name="courseFeeUSD" value="<?php echo $course_fee_USD; ?>">
                                        <input type="hidden" name="registrationFeeLKR" value="<?php echo $registration_fee_LKR; ?>">
                                        <input type="hidden" name="registrationFeeGBP" value="<?php echo $registration_fee_GBP; ?>">
                                        <input type="hidden" name="registrationFeeUSD" value="<?php echo $registration_fee_USD; ?>">
                                        <input type="hidden" name="courseFeeLKR_type" value="<?php echo $course_fee_type_LKR; ?>">
                                        <input type="hidden" name="courseFeeGBP_type" value="<?php echo $course_fee_type_GBP; ?>">
                                        <input type="hidden" name="courseFeeUSD_type" value="<?php echo $course_fee_type_USD; ?>">
                                        <input type="hidden" name="installmentsLKR" value="<?php echo $installment_month_LKR; ?>">
                                        <input type="hidden" name="installmentsGBP" value="<?php echo $installment_month_GBP; ?>">
                                        <input type="hidden" name="installmentsUSD" value="<?php echo $installment_month_USD; ?>">
                                        <input type="hidden" name="installmentInterval" id="installmentIntervalHidden" value="<?php echo $installment_interval; ?>">
                                        <input type="hidden" name="programme_id" value="<?php echo $program_id; ?>">
                                        <input type="hidden" name="batch_id" value="<?php echo $batch_id; ?>">

                                        <input type="hidden" name="courseFeeLKR_InstallmentDateFirst" value="<?php echo $courseFeeLKR_InstallmentDateFirst; ?>">
                                        <input type="hidden" name="courseFeeLKR_InstallmentDateFirst_DUE" value="<?php echo $courseFeeLKR_InstallmentDateFirst_DUE; ?>">
                                       

                                    </form>
                                <?php
                            } else {
                                echo "<p>No data received.</p>";
                            }
                                ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <!-- Ensure course fee initial value is sent -->
    <script>
        $(document).ready(function() {
            $('#courseFeeInputLKR').on('input', function() {
                $('#courseFeeInputLKR_initial_value').val($(this).val());
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Initialize with first date
            var firstDateInput = $('.installment-date[data-index="1"]');
            var firstDate = firstDateInput.val();
            
            // Function to update all subsequent dates
            function updateDates() {
                var interval = parseFloat($('#installmentInterval').val());
                var allDateInputs = $('.installment-date');
                var firstDate = new Date($(allDateInputs[0]).val());
                
                for (var i = 1; i < allDateInputs.length; i++) {
                    var newDate = new Date(firstDate);
                    
                    if (interval === 1) {
                        // For 1 month interval, keep the original day
                        newDate.setMonth(firstDate.getMonth() + i);
                    } else {
                        // For other intervals, use fixed 15th day
                        var monthsToAdd = i * interval;
                        var roundedMonths = Math.round(monthsToAdd);
                        newDate.setMonth(firstDate.getMonth() + roundedMonths);
                        newDate.setDate(15);
                    }
                    
                    // Format as YYYY-MM-DD
                    var year = newDate.getFullYear();
                    var month = String(newDate.getMonth() + 1).padStart(2, '0');
                    var day = String(newDate.getDate()).padStart(2, '0');
                    $(allDateInputs[i]).val(year + '-' + month + '-' + day);
                }
            }

            // When interval changes
            $('#installmentInterval').change(function() {
                $('#installmentIntervalHidden').val($(this).val());
                updateDates();
            });
            
            // When any date changes, update subsequent ones
            $(document).on('change', '.installment-date', function() {
                var index = parseInt($(this).data('index'));
                var interval = parseFloat($('#installmentInterval').val());
                var allDateInputs = $('.installment-date');
                var firstDate = new Date($(allDateInputs[0]).val());
                
                for (var i = index; i < allDateInputs.length; i++) {
                    var newDate = new Date(firstDate);
                    
                    if (interval === 1) {
                        // For 1 month interval, keep the original day
                        newDate.setMonth(firstDate.getMonth() + i);
                    } else {
                        // For other intervals, use fixed 15th day
                        var monthsToAdd = i * interval;
                        var roundedMonths = Math.round(monthsToAdd);
                        newDate.setMonth(firstDate.getMonth() + roundedMonths);
                        newDate.setDate(15);
                    }
                    
                    // Format as YYYY-MM-DD
                    var year = newDate.getFullYear();
                    var month = String(newDate.getMonth() + 1).padStart(2, '0');
                    var day = String(newDate.getDate()).padStart(2, '0');
                    $(allDateInputs[i]).val(year + '-' + month + '-' + day);
                }
            });

            // Initial update
            updateDates();
        });
    </script>
</div>
</body>

</html>