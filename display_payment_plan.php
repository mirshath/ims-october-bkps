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
    $student_code = $_POST['student_code'];
    $programme_batch = $_POST['programmeBatch'];

    // ----------------------------------------------------- 

    $batch_id = $_POST['batchId'] ?? NULL;
    $program_id = $_POST['programId'] ?? NULL;



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

    // University Fee   
    $university_fee_LKR = $_POST['uniFeeLKR'] ?? 0;
    $university_fee_GBP = $_POST['uniFeeGBP'] ?? 0;
    $uniFeeUSD = $_POST['uniFeeUSD'] ?? 0;

    // Retrieve hidden input values for course fees
    $courseFeeLKR_total = $_POST['courseFeeInputLKR_initial_value'] ?? 0; // Added to capture LKR course fee
    $courseFeeGBP_total = $_POST['courseFeeInputGBP_initial_value'] ?? 0; // Added to capture GBP course fee
    $courseFeeUSD_total = $_POST['courseFeeInputUSD_initial_value'] ?? 0; // Added to capture USD course fee

    // Course Fee
    $course_fee_LKR = $_POST['courseFeeLKR'] ?? 0;
    $course_fee_GBP = $_POST['courseFeeGBP'] ?? 0;
    $course_fee_USD = $_POST['courseFeeUSD'] ?? 0;

    // Registration Fee
    $registration_fee_LKR = $_POST['registrationFeeLKR'] ?? 0;
    $registration_fee_GBP = $_POST['registrationFeeGBP'] ?? 0;
    $registration_fee_USD = $_POST['registrationFeeUSD'] ?? 0;

    // Installment Data
    $installment_month_LKR = $_POST['installmentsLKR'] ?? 0;
    $installment_month_GBP = $_POST['installmentsGBP'] ?? 0;
    $installment_month_USD = $_POST['installmentsUSD'] ?? 0;

    // Payment Type Data
    $course_fee_type_GBP = $_POST['courseFeeGBP_type'] ?? 0;
    $course_fee_type_LKR = $_POST['courseFeeLKR_type'] ?? 0;
    $course_fee_type_USD = $_POST['courseFeeUSD_type'] ?? 0;

    // Entered Date
    $enteredDate = $_POST['courseFeeLKRInstallmentDateFirst'] ?? date('Y-m-d');
    $dateTime_for_insert = new DateTime($enteredDate);

    $program_id = $program_id ?? ''; // Default to an empty string if not set
    $batch_id = $batch_id ?? ''; // Default to an empty string if not set
echo  "$university_fee_GBP";
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
                                    <form method="POST" action="add_payment_plan_folder/insert_payment_plan.php">
                                        <input type="hidden" name="username" value="<?php echo $Session_username; ?>">
                                        <div class="row">
                                            <!-- Student Details  -->
                                            <div class="col-md-6 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6> Student Details</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Student Code:</strong> <?php echo $student_code; ?></p>
                                                        <p><strong>Programme and Batch:</strong> <?php echo $programme_batch; ?></p>
                                                    </div>
                                                </div>
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
                                                                    if ($courseFeeLKR_total) echo "<strong>Full Rs:</strong> $courseFeeLKR_total.00 ";
                                                                    if ($courseFeeGBP_total) echo "<strong>Full £:</strong> $courseFeeGBP_total.00 ";
                                                                    if ($courseFeeUSD_total) echo "<strong>Full $:</strong> $courseFeeUSD_total.00";
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Registration fee:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($registration_fee_LKR) echo "<strong>Rs:</strong> $registration_fee_LKR.00 ";
                                                                    if ($registration_fee_GBP) echo "<strong>£:</strong> $registration_fee_GBP.00 ";
                                                                    if ($registration_fee_USD) echo "<strong>$:</strong> $registration_fee_USD.00";
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Balance Course fee:</td>
                                                                <td>
                                                                    <?php
                                                                    if ($course_fee_LKR) echo "<strong>Rs:</strong> $course_fee_LKR.00 ";
                                                                    if ($course_fee_GBP) echo "<strong>£:</strong> $course_fee_GBP.00 ";
                                                                    if ($course_fee_USD) echo "<strong>$:</strong> $course_fee_USD.00";
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
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ------------------------------------------------------------------------------------------------------  -->

                                            <!-- Installment Details: -->
                                            <div class="col-md-12 mb-3">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6>Installment Details: </h6>
                                                    </div>

                                                    <div class="card-body">
                                                        <?php

                                                        function calculateInstallments($amount, $installments, $currency, &$dateTime, $paymentType)
                                                        {
                                                            if ($amount) {
                                                                // If full payment, show one row
                                                                if ($paymentType == 'full') {
                                                                    $installments = 1;
                                                                }

                                                                $installmentAmount = $amount / $installments;

                                                                for ($i = 1; $i <= $installments; $i++) {
                                                                    $dueDate = clone $dateTime;
                                                                    // $dueDate->modify("+30 days");
                                                                    $dueDate->modify("+1 month"); // Change this line to add one month for each installment
                                                                    

                                                        ?>
                                                                    <!-- --------------------------------------------------------------------------------------  -->
                                                                    <table class='table table-bordered table-striped table-hover'>
                                                                        <tr class="installment-row">
                                                                            <td><?php echo ($paymentType == 'full') ? "Full Payment" : "Installment $i"; ?></td>

                                                                            <td>
                                                                                <!-- Hidden input for installment number -->
                                                                                <input type='hidden' name='installment_number[]' value='installment_<?php echo $i; ?>'>
                                                                                <!-- Installment Amount (readonly) -->
                                                                                <input type='text' class='form-control installment-amount' id='installmentAmount_<?php echo $i; ?>' name='installment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>' readonly>
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
                                                                                <input type='text' class='form-control discount-value' name='installment<?php echo $currency; ?>_discount_value[]' style='display: none;' placeholder='Enter discount' oninput="validateDiscountInput(this)" onkeypress="return event.charCode >= 48 && event.charCode <= 57 || event.charCode === 46">
                                                                            </td>

                                                                            <td class="row_hidden" style="display: none;">
                                                                                <input type='text' class='form-control installment-amount' id='HiddeninstallmentAmount_<?php echo $i; ?>' name='Hiddeninstallment<?php echo $currency; ?>_amount[]' value='<?php echo number_format($installmentAmount, 2); ?>' readonly>
                                                                            </td>

                                                                            <td>
                                                                                <!-- Due Date (readonly) -->
                                                                                <input type='date' class='form-control' name='installment<?php echo $currency; ?>_date[]' value='<?php echo $dueDate->format('Y-m-d'); ?>' readonly>
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


                                                                        // -------------------------- 

                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                            const form = document.querySelector('form'); // Select the form element
                                                                            const installmentRows = document.querySelectorAll('.installment-row'); // All rows with class 'installment-row'

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

                                                                                    // Add hidden input for Hiddeninstallment amount
                                                                                    const hiddenInstallmentAmount = row.querySelector('input[name="Hiddeninstallment<?php echo $currency; ?>_amount[]"]').value;
                                                                                    addHiddenInput('Hiddeninstallment<?php echo $currency; ?>_amount[]', hiddenInstallmentAmount);
                                                                                });
                                                                            });
                                                                        });

                                                                        // -------------------------- 

                                                                        // Toggles the visibility of the discount input field based on discount type selection
                                                                        function toggleDiscountInput(selectElement) {
                                                                            var inputField = selectElement.closest('tr').querySelector('.discount-value');
                                                                            var installmentAmountInput = selectElement.closest('tr').querySelector('.installment-amount');
                                                                            var originalAmount = parseFloat(installmentAmountInput.getAttribute('data-original-amount'));

                                                                            if (selectElement.value === 'N/A') {
                                                                                inputField.style.display = 'none';
                                                                                inputField.value = ''; // Clear value when hidden
                                                                                // Reset installment amount to original
                                                                                installmentAmountInput.value = originalAmount.toFixed(2);
                                                                            } else {
                                                                                inputField.style.display = 'block';
                                                                                // Clear previous value when switching between Value and Percentage
                                                                                inputField.value = '';
                                                                                // Reset installment amount to original
                                                                                installmentAmountInput.value = originalAmount.toFixed(2);
                                                                            }
                                                                        }

                                                                        // Calculates the discount and updates the installment amount
                                                                        function calculateDiscount(inputElement) {
                                                                            var row = inputElement.closest('tr');
                                                                            var installmentAmountInput = row.querySelector('.installment-amount');
                                                                            var originalAmount = parseFloat(installmentAmountInput.getAttribute('data-original-amount'));
                                                                            var discountType = row.querySelector('.discount-type').value;
                                                                            var discountValue = parseFloat(inputElement.value) || 0;
                                                                            var finalAmount = originalAmount;

                                                                            // Validation for Value discount
                                                                            if (discountType === 'Value') {
                                                                                if (discountValue > originalAmount) {
                                                                                    alert('Discount value cannot be greater than the installment amount!');
                                                                                    inputElement.value = originalAmount; // Reset to max allowed value
                                                                                    discountValue = originalAmount;
                                                                                }
                                                                                finalAmount = originalAmount - discountValue;
                                                                            }
                                                                            // Validation for Percentage discount
                                                                            else if (discountType === 'Percentage') {
                                                                                if (discountValue > 100) {
                                                                                    alert('Percentage cannot be greater than 100%!');
                                                                                    inputElement.value = 100; // Reset to max allowed value
                                                                                    discountValue = 100;
                                                                                }
                                                                                finalAmount = originalAmount - (originalAmount * (discountValue / 100));
                                                                            }

                                                                            // Update the installment amount input field with the final amount after discount
                                                                            installmentAmountInput.value = finalAmount.toFixed(2);
                                                                        }

                                                                        // Add input validation to prevent invalid characters
                                                                        function validateDiscountInput(inputElement) {
                                                                            var discountType = inputElement.closest('tr').querySelector('.discount-type').value;

                                                                            // Only allow numbers and decimal point
                                                                            inputElement.value = inputElement.value.replace(/[^0-9.]/g, '');

                                                                            // Prevent multiple decimal points
                                                                            var parts = inputElement.value.split('.');
                                                                            if (parts.length > 2) {
                                                                                inputElement.value = parts[0] + '.' + parts.slice(1).join('');
                                                                            }

                                                                            // Calculate discount after validation
                                                                            calculateDiscount(inputElement);
                                                                        }

                                                                        // Initialize original amount in a data attribute
                                                                        document.addEventListener('DOMContentLoaded', function() {
                                                                            var installmentAmountInputs = document.querySelectorAll('.installment-amount');
                                                                            installmentAmountInputs.forEach(function(input) {
                                                                                var originalAmount = parseFloat(input.value.replace(/,/g, '')); // Remove commas for calculation
                                                                                input.setAttribute('data-original-amount', originalAmount); // Store original amount
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
                                                        if ($course_fee_type_GBP) {
                                                            calculateInstallments($course_fee_GBP, ($course_fee_type_GBP == 'installment' ? $installment_month_GBP : 1), 'GBP', $dateTime_for_insert, $course_fee_type_GBP);
                                                        }
                                                        if ($course_fee_type_LKR) {
                                                            calculateInstallments($course_fee_LKR, ($course_fee_type_LKR == 'installment' ? $installment_month_LKR : 1), 'LKR', $dateTime_for_insert, $course_fee_type_LKR);
                                                        }
                                                        if ($course_fee_type_USD) {
                                                            calculateInstallments($course_fee_USD, ($course_fee_type_USD == 'installment' ? $installment_month_USD : 1), 'USD', $dateTime_for_insert, $course_fee_type_USD);
                                                        }

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
                                        <!-- Hidden inputs to pass values to insert_payment_plan.php -->
                                        <input type="hidden" name="student_code" value="<?php echo $student_code; ?>">
                                        <input type="hidden" name="programmeBatch" value="<?php echo $programme_batch; ?>">
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


                                        <input type="hidden" name="programme_id" value="<?php echo htmlspecialchars($program_id); ?>">
                                        <input type="hidden" name="batch_id" value="<?php echo htmlspecialchars($batch_id); ?>">

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
    </div>
    </body>

    </html>