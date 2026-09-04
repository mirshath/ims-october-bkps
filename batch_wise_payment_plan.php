<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 

?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
                    <h4 class="h4 mb-0 text-gray-800">Batch wise Payment Plan</h4>
                </div>

                <!-- Payment Plan Form -->
                <div class="row mb-5">
                    <div class="col-md-10">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-coins"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Batch Wise Payment Plan</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="display_batchwise_payment_plan.php">
                                    <!-- Hidden Input for Student Codes -->
                                    <!-- <input type="hidden" name="student_codes" id="student_codes"> -->
                                    <div class="mb-3 row">
                                        <label for="programme" class="col-sm-3 col-form-label">Programme:</label>
                                        <div class="col-sm-9">
                                            <select name="programme_id" id="programme" class="form-control select2" required>
                                                <option value="">Select Programme</option>
                                                <!-- Add Programme Options Here -->
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Batch Section -->
                                    <div class="mb-3 row">
                                        <label for="batch" class="col-sm-3 col-form-label">Batch:</label>
                                        <div class="col-sm-9">
                                            <select name="batch_id" id="batch" class="form-control select2" required>
                                                <option value="">Select Batch</option>
                                                <!-- Add Batch Options Here -->
                                            </select>
                                        </div>
                                    </div>

                                    <!-- University Fee Section -->
                                    <div class="mb-3 border p-3">
                                        <h5>University Fee:</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeLKR" onchange="toggleInput('universityFeeLKR', 'uniFeeLKR')">
                                                    <label class="form-check-label" for="universityFeeLKR">LKR</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeLKR" id="uniFeeLKR" placeholder="Uni. Fee in LKR" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeGBP" onchange="toggleInput('universityFeeGBP', 'uniFeeGBP')">
                                                    <label class="form-check-label" for="universityFeeGBP">GBP</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeGBP" id="uniFeeGBP" placeholder="Uni. Fee in GBP" disabled>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeUSD" onchange="toggleInput('universityFeeUSD', 'uniFeeUSD')">
                                                    <label class="form-check-label" for="universityFeeUSD">USD</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeUSD" id="uniFeeUSD" placeholder="Uni. Fee in USD" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Course Fee Section -->
                                    <div class="mb-3 border p-3">
                                        <h5>Course Fee:</h5>
                                        <div class="row">
                                            <!-- Fee Details for LKR -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="courseFeeLKR" name="courseFeeLKR_checkbox" onchange="toggleInput('courseFeeLKR', 'courseFeeInputLKR');">
                                                    <label class="form-check-label" for="courseFeeLKR">LKR</label>
                                                </div>
                                                <input type="text" class="form-control" id="courseFeeInputLKR" name="courseFeeLKR" disabled placeholder="Enter Course Fee" oninput="storeInitialCourseFee_LKR()" autocomplete="off">
                                                <input type="hidden" class="form-control" id="courseFeeInputLKR_initial_value" name="courseFeeInputLKR_initial_value" readonly placeholder="Entered Course Fee" oninput="storeInitialCourseFee_LKR()" autocomplete="off">

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeLKR_type" id="courseFeeLKRFull" value="full" onchange="toggleInstallmentInput('installmentsInputLKR', false);">
                                                    <label class="form-check-label" for="courseFeeLKRFull">Full</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeLKR_type" id="courseFeeLKRInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputLKR', true);">
                                                    <label class="form-check-label" for="courseFeeLKRInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputLKR" name="installmentsLKR" placeholder="Installments Month" disabled>

                                                <div class="form-check mt-3">
                                                    <label class="form-check-label">Registered Date</label>
                                                    <input type="date" class="form-control" id="courseFeeLKRInstallmentDateFirst" name="courseFeeLKRInstallmentDateFirst" required>
                                                </div>
                                                <p id="installmentNote" style="color: red; font-weight: bolder;">1st installment payment : <br>registered Date + 1 month</p>
                                            </div>

                                            <!-- Fee Details for GBP -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="courseFeeGBP" name="courseFeeGBP_checkbox" onchange="toggleInput('courseFeeGBP', 'courseFeeInputGBP');">
                                                    <label class="form-check-label" for="courseFeeGBP">GBP</label>
                                                </div>
                                                <input type="text" class="form-control" id="courseFeeInputGBP" name="courseFeeGBP" disabled placeholder="Enter Course Fee" oninput="storeInitialCourseFee_GBP()" autocomplete="off">
                                                <input type="hidden" class="form-control" id="courseFeeInputGBP_initial_value" name="courseFeeInputGBP_initial_value" readonly>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeGBP_type" id="courseFeeGBPFull" value="full" onchange="toggleInstallmentInput('installmentsInputGBP', false);">
                                                    <label class="form-check-label" for="courseFeeGBPFull">Full</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeGBP_type" id="courseFeeGBPInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputGBP', true);">
                                                    <label class="form-check-label" for="courseFeeGBPInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputGBP" name="installmentsGBP" placeholder="Installments in GBP" disabled>
                                            </div>

                                            <!-- Fee Details for USD -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="courseFeeUSD" name="courseFeeUSD_checkbox" onchange="toggleInput('courseFeeUSD', 'courseFeeInputUSD');">
                                                    <label class="form-check-label" for="courseFeeUSD">USD</label>
                                                </div>
                                                <input type="text" class="form-control" id="courseFeeInputUSD" name="courseFeeUSD" disabled placeholder="Enter Course Fee" oninput="storeInitialCourseFee_USD()" autocomplete="off">
                                                <input type="hidden" class="form-control" id="courseFeeInputUSD_initial_value" name="courseFeeInputUSD_initial_value" readonly>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeUSD_type" id="courseFeeUSDFull" value="full" onchange="toggleInstallmentInput('installmentsInputUSD', false);">
                                                    <label class="form-check-label" for="courseFeeUSDFull">Full</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeUSD_type" id="courseFeeUSDInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputUSD', true);">
                                                    <label class="form-check-label" for="courseFeeUSDInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputUSD" name="installmentsUSD" placeholder="Installments in USD" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Registration Fee Section -->
                                    <div class="mb-3 border p-3">
                                        <h5>Registration Fee:</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="registrationFeeLKR" name="registrationFeeLKR_checkbox" required onchange="toggleInput('registrationFeeLKR', 'registrationFeeInputLKR');">
                                                    <label class="form-check-label" for="registrationFeeLKR">LKR</label>
                                                </div>
                                                <input type="text" class="form-control" id="registrationFeeInputLKR" name="registrationFeeLKR" required disabled placeholder="Enter Registration Fee" oninput="calculateFinalCourseFee_LKR()" autocomplete="off">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="registrationFeeGBP" name="registrationFeeGBP_checkbox" onchange="toggleInput('registrationFeeGBP', 'registrationFeeInputGBP');">
                                                    <label class="form-check-label" for="registrationFeeGBP">GBP</label>
                                                </div>
                                                <input type="text" class="form-control" id="registrationFeeInputGBP" name="registrationFeeGBP" disabled placeholder="Enter Registration Fee" oninput="calculateFinalCourseFee_GBP()" autocomplete="off">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="registrationFeeUSD" name="registrationFeeUSD_checkbox" onchange="toggleInput('registrationFeeUSD', 'registrationFeeInputUSD');">
                                                    <label class="form-check-label" for="registrationFeeUSD">USD</label>
                                                </div>
                                                <input type="text" class="form-control" id="registrationFeeInputUSD" name="registrationFeeUSD" disabled placeholder="Enter Registration Fee" oninput="calculateFinalCourseFee_USD()" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary w-25" name="saveButton">Save</button>
                                    </div>


                                    <!-- Hidden Inputs for Student Code, Batch ID, Programme Batch, Program ID -->
                                    <!-- <input type="hidden" name="student_code" id="student_code" value="Student Code goes here"> -->
                                    <input type="hidden" name="batch_id" id="batch_id" value="<!-- Batch ID goes here -->">
                                    <input type="hidden" name="programme_batch" id="programme_batch" value="<!-- Programme Batch goes here -->">
                                    <input type="hidden" name="program_id" id="program_id" value="<!-- Program ID goes here -->">
                                    <input type="hidden" name="student_codes" id="student_codes" value="">

                                </form>
                            </div>

                            <script>
                                // Function to toggle enabling/disabling the input fields for fee
                                function toggleInput(checkboxId, inputId) {
                                    var checkbox = document.getElementById(checkboxId);
                                    var input = document.getElementById(inputId);
                                    input.disabled = !checkbox.checked;
                                }


                                $('#programme').change(function() {
                                    let programmeId = $(this).val();
                                    $('#program_id').val(programmeId);
                                });

                                $('#batch').change(function() {
                                    let batchId = $(this).val();
                                    $('#batch_id').val(batchId);
                                });

                                function updateStudentCodes(studentCodes) {
                                    $('#student_code').val(studentCodes.join(',')); // Join multiple codes if needed
                                }

                                // Store initial course fee values
                                function storeInitialCourseFee_LKR() {
                                    var fee = document.getElementById("courseFeeInputLKR").value;
                                    document.getElementById("courseFeeInputLKR_initial_value").value = fee;
                                }

                                function storeInitialCourseFee_GBP() {
                                    var fee = document.getElementById("courseFeeInputGBP").value;
                                    document.getElementById("courseFeeInputGBP_initial_value").value = fee;
                                }

                                function storeInitialCourseFee_USD() {
                                    var fee = document.getElementById("courseFeeInputUSD").value;
                                    document.getElementById("courseFeeInputUSD_initial_value").value = fee;
                                }

                                // Function to toggle installment input field visibility
                                function toggleInstallmentInput(inputId, enable) {
                                    var input = document.getElementById(inputId);
                                    input.disabled = !enable;
                                }

                                // NEW: Function to fetch program fees and apply fee separation logic
                                function fetchProgramFees(programCode) {
                                    if (programCode) {
                                        $.ajax({
                                            url: 'add_payment_plan_folder/fetch_program_fees.php', // You'll need to create this file
                                            method: 'GET',
                                            data: {
                                                program_code: programCode
                                            },
                                            dataType: 'json',
                                            success: function(data) {
                                                if (data && data.success !== false) {
                                                    // ===== UNIVERSITY FEE SECTION =====
                                                    // Only populate international currencies (GBP, USD, EUR)

                                                    // Clear LKR University Fee (since it shouldn't be used for university fees)
                                                    $('#uniFeeLKR').val('').prop('disabled', true);
                                                    $('#universityFeeLKR').prop('checked', false);

                                                    // GBP University Fee
                                                    if (data.course_fee_gbp > 0) {
                                                        $('#uniFeeGBP').val(data.course_fee_gbp);
                                                        $('#universityFeeGBP').prop('checked', true);
                                                        $('#uniFeeGBP').prop('disabled', false);
                                                    } else {
                                                        $('#uniFeeGBP').val('').prop('disabled', true);
                                                        $('#universityFeeGBP').prop('checked', false);
                                                    }

                                                    // USD University Fee
                                                    if (data.course_fee_usd > 0) {
                                                        $('#uniFeeUSD').val(data.course_fee_usd);
                                                        $('#universityFeeUSD').prop('checked', true);
                                                        $('#uniFeeUSD').prop('disabled', false);
                                                    } else {
                                                        $('#uniFeeUSD').val('').prop('disabled', true);
                                                        $('#universityFeeUSD').prop('checked', false);
                                                    }

                                                    // ===== COURSE FEE SECTION =====
                                                    // Only populate LKR currency

                                                    // LKR Course Fee
                                                    if (data.course_fee_lkr > 0) {
                                                        $('#courseFeeInputLKR').val(data.course_fee_lkr);
                                                        $('#courseFeeLKR').prop('checked', true);
                                                        $('#courseFeeInputLKR').prop('disabled', false);
                                                        // Store initial value for calculations
                                                        storeInitialCourseFee_LKR();
                                                    } else {
                                                        $('#courseFeeInputLKR').val('').prop('disabled', true);
                                                        $('#courseFeeLKR').prop('checked', false);
                                                    }

                                                    // Clear GBP and USD Course Fees (since course fees should be in LKR only)
                                                    $('#courseFeeInputGBP').val('').prop('disabled', true);
                                                    $('#courseFeeGBP').prop('checked', false);
                                                    $('#courseFeeInputUSD').val('').prop('disabled', true);
                                                    $('#courseFeeUSD').prop('checked', false);

                                                    // Reset course fee type radio buttons
                                                    $('input[name="courseFeeLKR_type"]').prop('checked', false);
                                                    $('input[name="courseFeeGBP_type"]').prop('checked', false);
                                                    $('input[name="courseFeeUSD_type"]').prop('checked', false);

                                                    // Reset installment fields
                                                    $('#installmentsInputLKR, #installmentsInputGBP, #installmentsInputUSD').val('').prop('disabled', true);

                                                    console.log('Program fees loaded successfully:', data);
                                                } else {
                                                    console.log('No program fee data found');
                                                    clearAllFeeFields();
                                                }
                                            },
                                            error: function() {
                                                console.error('Error fetching program fees');
                                                clearAllFeeFields();
                                            }
                                        });
                                    } else {
                                        clearAllFeeFields();
                                    }
                                }

                                // Function to clear all fee fields
                                function clearAllFeeFields() {
                                    // Clear and disable University Fee fields
                                    $('#uniFeeLKR, #uniFeeGBP, #uniFeeUSD').val('').prop('disabled', true);
                                    $('#universityFeeLKR, #universityFeeGBP, #universityFeeUSD').prop('checked', false);

                                    // Clear and disable Course Fee fields
                                    $('#courseFeeInputLKR, #courseFeeInputGBP, #courseFeeInputUSD').val('').prop('disabled', true);
                                    $('#courseFeeLKR, #courseFeeGBP, #courseFeeUSD').prop('checked', false);

                                    // Clear and disable Registration Fee fields
                                    $('#registrationFeeInputLKR, #registrationFeeInputGBP, #registrationFeeInputUSD').val('').prop('disabled', true);
                                    $('#registrationFeeLKR, #registrationFeeGBP, #registrationFeeUSD').prop('checked', false);

                                    // Reset radio buttons
                                    $('input[name="courseFeeLKR_type"], input[name="courseFeeGBP_type"], input[name="courseFeeUSD_type"]').prop('checked', false);

                                    // Reset installment fields
                                    $('#installmentsInputLKR, #installmentsInputGBP, #installmentsInputUSD').val('').prop('disabled', true);
                                }
                            </script>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let initialCourseFee_LKR = 0; // Temporary variable

    function storeInitialCourseFee_LKR() {
        let courseFee_LKR = parseFloat(document.getElementById("courseFeeInputLKR").value) || 0;
        initialCourseFee_LKR = courseFee_LKR; // Store initial value
    }

    function calculateFinalCourseFee_LKR() {
        let registrationFee_LKR = parseFloat(document.getElementById("registrationFeeInputLKR").value) || 0;
        let finalFee_LKR = initialCourseFee_LKR - registrationFee_LKR;

        if (finalFee_LKR < 0) {
            finalFee_LKR = 0; // Prevent negative values
        }

        document.getElementById("courseFeeInputLKR_initial_value").value = initialCourseFee_LKR; // Update course fee input dynamically
        document.getElementById("courseFeeInputLKR").value = finalFee_LKR; // Update course fee input dynamically
    }
    // ---------------------------------------------------------------------------- 
    // GBP
    // ---------------------------------------------------------------------------- 
    let initialCourseFee_GBP = 0; // Temporary variable

    function storeInitialCourseFee_GBP() {
        let courseFee_GBP = parseFloat(document.getElementById("courseFeeInputGBP").value) || 0;
        initialCourseFee_GBP = courseFee_GBP; // Store initial value
    }

    function calculateFinalCourseFee_GBP() {
        let registrationFee_GBP = parseFloat(document.getElementById("registrationFeeInputGBP").value) || 0;
        let finalFee_GBP = initialCourseFee_GBP - registrationFee_GBP;

        if (finalFee_GBP < 0) {
            finalFee_GBP = 0; // Prevent negative values
        }

        document.getElementById("courseFeeInputGBP_initial_value").value = initialCourseFee_GBP;
        document.getElementById("courseFeeInputGBP").value = finalFee_GBP; // Update course fee input dynamically
    }

    // ---------------------------------------------------------------------------- 
    // USD
    // ---------------------------------------------------------------------------- 
    let initialCourseFee_USD = 0; // Temporary variable

    function storeInitialCourseFee_USD() {
        let courseFee_USD = parseFloat(document.getElementById("courseFeeInputUSD").value) || 0;
        initialCourseFee_USD = courseFee_USD; // Store initial value
    }

    function calculateFinalCourseFee_USD() {
        let registrationFee_USD = parseFloat(document.getElementById("registrationFeeInputUSD").value) || 0;
        let finalFee_USD = initialCourseFee_USD - registrationFee_USD;

        if (finalFee_USD < 0) {
            finalFee_USD = 0; // Prevent negative values
        }

        document.getElementById("courseFeeInputUSD_initial_value").value = initialCourseFee_USD;
        document.getElementById("courseFeeInputUSD").value = finalFee_USD; // Update course fee input dynamically
    }
</script>

<script>
    $(document).ready(function() {
        $('.select2').select2({

        });

        // Fetch Programmes
        $.ajax({
            url: "transection_exams/fetch_programmes.php",
            method: "GET",
            dataType: "json",
            success: function(data) {
                let programmeDropdown = $('#programme');
                programmeDropdown.empty().append('<option value="">Select Programme</option>');
                data.forEach(function(programme) {
                    let selected = <?php echo isset($isEdit) && $isEdit ? 'programme.program_code == ' . $assessment['programme_id'] : 'false'; ?> ? 'selected' : '';
                    programmeDropdown.append(`<option value="${programme.program_code}" ${selected}>${programme.program_name}</option>`);
                });
                <?php if (isset($isEdit) && $isEdit): ?>
                    programmeDropdown.trigger('change');
                <?php endif; ?>
            }
        });

        let studentCodes = []; // Store fetched student codes

        // Fetch student codes when programme or batch changes
        function fetchStudentCode(programmeId, batchId) {
            if (programmeId && batchId) {
                $.ajax({
                    url: "add_payment_plan_folder/batchwise_fetch_students.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(data) {
                        studentCodes = data.map(student => student.student_code);
                        console.log("Fetched Student Codes:", studentCodes); // Log student codes in console
                        $('#student_codes').val(studentCodes.join(',')); // Update hidden input with student codes
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching student codes:", error);
                    }
                });
            }
        }

        // Programme change event - ONLY FETCH FROM batch_payment_folder
        $('#programme').change(function() {
            let programmeId = $(this).val();
            let batchDropdown = $('#batch');

            if (programmeId) {
                // Fetch program fees first (NEW FUNCTIONALITY) - COMMENTED OUT
                // fetchProgramFees(programmeId);

                // Then fetch batches (existing functionality)
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        data.forEach(function(batch) {
                            batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                        });

                        let batchId = batchDropdown.val();
                        fetchStudentCode(programmeId, batchId);
                        // Also fetch payment allocation if a batch is selected
                        if (batchId) {
                            fetchPaymentAllocation(programmeId, batchId);
                        }
                    }
                });
            } else {
                // Clear fee fields when no programme is selected
                clearAllFeeFields();
            }
        });

        // Batch change event
        $('#batch').change(function() {
            let programmeId = $('#programme').val();
            let batchId = $(this).val();
            fetchStudentCode(programmeId, batchId);
            // Also fetch payment allocation data
            fetchPaymentAllocation(programmeId, batchId);
        });

        // Function to fetch payment allocation data
        function fetchPaymentAllocation(programmeId, batchId) {
            if (programmeId && batchId) {
                $.ajax({
                    url: "batch_payment_folder/fetch_payment_allocation.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success && response.data) {
                            // Auto-fill the form with the data
                            fillFormWithPaymentData(response.data);
                        } else {
                            // No data found, keep form as is for manual entry
                            console.log("No payment plan found for this programme and batch");
                        }
                    },
                    error: function() {
                        console.error("Error fetching payment allocation");
                    }
                });
            }
        }

        // Function to fill the form with payment data
        function fillFormWithPaymentData(data) {
            // Clear all fee fields first
            clearAllFeeFields();

            // Check if we have installment_no to determine if installment is selected
            const hasInstallments = (data.installment_no !== null && data.installment_no !== '' && parseInt(data.installment_no) > 0);
            
            // Get Installment Interval, default to 1 if not available
            const installmentInterval = data.Installment_Interval || data.installment_interval || 1;
            
            // Update the note
            const installmentNote = document.getElementById('installmentNote');
            if (installmentNote) {
                let intervalText = installmentInterval + ' month';
                if (installmentInterval != 1) {
                    intervalText += 's';
                }
                installmentNote.innerHTML = '1st installment payment : <br>registered Date + ' + intervalText;
            }

            // University Fee - only fill if value is present and >0
            if (data.uni_fee_lkr !== null && data.uni_fee_lkr !== '' && parseFloat(data.uni_fee_lkr) > 0) {
                $('#uniFeeLKR').val(data.uni_fee_lkr);
                $('#universityFeeLKR').prop('checked', true);
                $('#uniFeeLKR').prop('disabled', false);
            }
            if (data.uni_fee_gbp !== null && data.uni_fee_gbp !== '' && parseFloat(data.uni_fee_gbp) > 0) {
                $('#uniFeeGBP').val(data.uni_fee_gbp);
                $('#universityFeeGBP').prop('checked', true);
                $('#uniFeeGBP').prop('disabled', false);
            }
            if (data.uni_fee_usd !== null && data.uni_fee_usd !== '' && parseFloat(data.uni_fee_usd) > 0) {
                $('#uniFeeUSD').val(data.uni_fee_usd);
                $('#universityFeeUSD').prop('checked', true);
                $('#uniFeeUSD').prop('disabled', false);
            }

            // Course Fee - only fill if value is present and >0
            if (data.course_fee_lkr !== null && data.course_fee_lkr !== '' && parseFloat(data.course_fee_lkr) > 0) {
                $('#courseFeeInputLKR').val(data.course_fee_lkr);
                $('#courseFeeLKR').prop('checked', true);
                $('#courseFeeInputLKR').prop('disabled', false);
                storeInitialCourseFee_LKR();

                // Set radio button based on installments
                if (hasInstallments) {
                    $('#courseFeeLKRInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputLKR', true);
                    $('#installmentsInputLKR').val(data.installment_no);
                } else if (data.course_fee_type_lkr === 'full') {
                    $('#courseFeeLKRFull').prop('checked', true);
                    toggleInstallmentInput('installmentsInputLKR', false);
                } else if (data.course_fee_type_lkr === 'installment') {
                    $('#courseFeeLKRInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputLKR', true);
                    $('#installmentsInputLKR').val(data.installment_no);
                }
            }

            if (data.course_fee_gbp !== null && data.course_fee_gbp !== '' && parseFloat(data.course_fee_gbp) > 0) {
                $('#courseFeeInputGBP').val(data.course_fee_gbp);
                $('#courseFeeGBP').prop('checked', true);
                $('#courseFeeInputGBP').prop('disabled', false);
                storeInitialCourseFee_GBP();

                // Set radio button based on installments
                if (hasInstallments) {
                    $('#courseFeeGBPInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputGBP', true);
                    $('#installmentsInputGBP').val(data.installment_no);
                } else if (data.course_fee_type_gbp === 'full') {
                    $('#courseFeeGBPFull').prop('checked', true);
                    toggleInstallmentInput('installmentsInputGBP', false);
                } else if (data.course_fee_type_gbp === 'installment') {
                    $('#courseFeeGBPInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputGBP', true);
                    $('#installmentsInputGBP').val(data.installment_no);
                }
            }

            if (data.course_fee_usd !== null && data.course_fee_usd !== '' && parseFloat(data.course_fee_usd) > 0) {
                $('#courseFeeInputUSD').val(data.course_fee_usd);
                $('#courseFeeUSD').prop('checked', true);
                $('#courseFeeInputUSD').prop('disabled', false);
                storeInitialCourseFee_USD();

                // Set radio button based on installments
                if (hasInstallments) {
                    $('#courseFeeUSDInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputUSD', true);
                    $('#installmentsInputUSD').val(data.installment_no);
                } else if (data.course_fee_type_usd === 'full') {
                    $('#courseFeeUSDFull').prop('checked', true);
                    toggleInstallmentInput('installmentsInputUSD', false);
                } else if (data.course_fee_type_usd === 'installment') {
                    $('#courseFeeUSDInstallment').prop('checked', true);
                    toggleInstallmentInput('installmentsInputUSD', true);
                    $('#installmentsInputUSD').val(data.installment_no);
                }
            }

            // Registration Date
            if (data.register_date) {
                $('#courseFeeLKRInstallmentDateFirst').val(data.register_date);
            }

            // Registration Fee - only fill if value is present
            if (data.registration_fee !== null && data.registration_fee !== '' && parseFloat(data.registration_fee) > 0) {
                $('#registrationFeeInputLKR').val(data.registration_fee);
                $('#registrationFeeLKR').prop('checked', true);
                $('#registrationFeeInputLKR').prop('disabled', false);
                // Calculate final course fee for LKR
                if (data.course_fee_lkr) {
                    initialCourseFee_LKR = parseFloat(data.course_fee_lkr);
                    calculateFinalCourseFee_LKR();
                }
            }

            // Final Course Fee (only course fee)
            if (data.only_course_fee) {
                $('#courseFeeInputLKR_initial_value').val(data.only_course_fee);
            }
        }

    });
</script>