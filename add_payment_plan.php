<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------





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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Payment Plan</h4>
                </div>

                <!-- Alert Container for Payment Plan Status -->
                <div id="paymentPlanAlert" style="display: none;" class="mb-4">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>No Payment Plan Allocated!</strong>
                        <span id="alertMessage">This student doesn't have an allocated payment plan. Please contact the administration to set up program fees.</span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Payment Plan Form -->
                <div class="row mb-5">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-coins"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Payment Plan Details</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="display_payment_plan.php" id="fm">
                                    <!-- Student Dropdown -->
                                    <div class="mb-3 row">
                                        <label for="student" class="col-sm-3 col-form-label">Student:</label>
                                        <div class="col-sm-9">
                                            <select class="form-select select2" id="student" name="student_code">
                                                <option value="">Select Student</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Programme and Batch Input -->
                                    <div class="mb-3 row">
                                        <label for="programmeBatch" class="col-sm-3 col-form-label">Programme and Batch:</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="programmeBatch" id="programmeBatch" readonly>
                                        </div>
                                    </div>

                                    <!-- Program ID Input -->
                                    <div class="mb-3 row" style="display: none;">
                                        <label for="programId" class="col-sm-3 col-form-label">Program ID:</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="programId" id="programId" readonly>
                                        </div>
                                    </div>

                                    <!-- Batch ID Input -->
                                    <div class="mb-3 row" style="display: none;">
                                        <label for="batchId" class="col-sm-3 col-form-label">Batch ID:</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="batchId" id="batchId" readonly>
                                        </div>
                                    </div>

                                    <!-- University Fee Section -->
                                    <div class="mb-3 border p-3">
                                        <h5>University Fee:</h5>
                                        <div class="row">
                                            <!-- Fee Details for LKR, GBP, USD -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeLKR" onchange="toggleInput('universityFeeLKR', 'uniFeeLKR')">
                                                    <label class="form-check-label" for="universityFeeLKR">LKR</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeLKR" id="uniFeeLKR" placeholder="Uni. Fee in LKR" >
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeGBP" onchange="toggleInput('universityFeeGBP', 'uniFeeGBP')">
                                                    <label class="form-check-label" for="universityFeeGBP">GBP</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeGBP" id="uniFeeGBP" placeholder="Uni. Fee in GBP" >
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="universityFeeUSD" onchange="toggleInput('universityFeeUSD', 'uniFeeUSD')">
                                                    <label class="form-check-label" for="universityFeeUSD">USD</label>
                                                </div>
                                                <input type="text" class="form-control" name="uniFeeUSD" id="uniFeeUSD" placeholder="Uni. Fee in USD" >
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
                                                    <input class="form-check-input" type="radio" name="courseFeeLKR_type" id="courseFeeLKRInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputLKR', true); calculateFinalCourseFee()">
                                                    <label class="form-check-label" for="courseFeeLKRInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputLKR" name="installmentsLKR" placeholder="installment month (ex: 5)" disabled>

                                                <div class="form-check mt-3">
                                                    <label class="form-check-label">Register Date</label>
                                                    <input type="date" class="form-control" id="courseFeeLKRInstallmentDateFirst" name="courseFeeLKRInstallmentDateFirst" required>
                                                </div>
                                                
                                                 <p style="color: red; font-weight: bolder;">1st Installment Payment Date : <br>Registered Date + 1 month</p>
                                            </div>
                                            <!-- Fee Details for GBP -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="courseFeeGBP" name="courseFeeGBP_checkbox" onchange="toggleInput('courseFeeGBP', 'courseFeeInputGBP');">
                                                    <label class="form-check-label" for="courseFeeGBP">GBP</label>
                                                </div>
                                                <input type="text" class="form-control" id="courseFeeInputGBP" name="courseFeeGBP" disabled placeholder="Enter Course Fee" oninput="storeInitialCourseFee_GBP()" autocomplete="off">
                                                <input type="hidden" class="form-control" id="courseFeeInputGBP_initial_value" name="courseFeeInputGBP_initial_value" readonly placeholder="Entered Course Fee" oninput="storeInitialCourseFee_GBP()" autocomplete="off">

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeGBP_type" id="courseFeeGBPFull" value="full" onchange="toggleInstallmentInput('installmentsInputGBP', false);">
                                                    <label class="form-check-label" for="courseFeeGBPFull">Full</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeGBP_type" id="courseFeeGBPInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputGBP', true);">
                                                    <label class="form-check-label" for="courseFeeGBPInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputGBP" name="installmentsGBP" placeholder="installment month" disabled>
                                            </div>
                                            <!-- Fee Details for USD -->
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="courseFeeUSD" name="courseFeeUSD_checkbox" onchange="toggleInput('courseFeeUSD', 'courseFeeInputUSD');">
                                                    <label class="form-check-label" for="courseFeeUSD">USD</label>
                                                </div>
                                                <input type="text" class="form-control" id="courseFeeInputUSD" name="courseFeeUSD" placeholder="Enter Course Fee" disabled oninput="storeInitialCourseFee_USD()" autocomplete="off">
                                                <input type="hidden" class="form-control" id="courseFeeInputUSD_initial_value" name="courseFeeInputUSD_initial_value" placeholder="Enter Course Fee" readonly oninput="storeInitialCourseFee_USD()" autocomplete="off">

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeUSD_type" id="courseFeeUSDFull" value="full" onchange="toggleInstallmentInput('installmentsInputUSD', false);">
                                                    <label class="form-check-label" for="courseFeeUSDFull">Full</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="courseFeeUSD_type" id="courseFeeUSDInstallment" value="installment" onchange="toggleInstallmentInput('installmentsInputUSD', true);">
                                                    <label class="form-check-label" for="courseFeeUSDInstallment">Installment</label>
                                                </div>
                                                <input type="text" class="form-control mt-2" id="installmentsInputUSD" name="installmentsUSD" placeholder="installment month" disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Registration Fee Section -->
                                    <div class="mb-3 border p-3">
                                        <h5>Registration Fee:</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="registrationFeeLKR" required name="registrationFeeLKR_checkbox" onchange="toggleInput('registrationFeeLKR', 'registrationFeeInputLKR');">
                                                    <label class="form-check-label" for="registrationFeeLKR">LKR</label>
                                                </div>
                                                <input type="text" class="form-control" id="registrationFeeInputLKR" required name="registrationFeeLKR" disabled placeholder="Enter Registration Fee" oninput="calculateFinalCourseFee_LKR()" autocomplete="off">
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

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary w-25" name="saveButton">Save</button>
                                    </div>
                                </form>
                            </div>

                            <script>
                                $(document).ready(function() {
                                    // Function to check the validation of required fields
                                    function checkRequiredFields() {
                                        let isValid = true;

                                        // Check if the Student dropdown is selected
                                        if ($("#student").val() === "") {
                                            isValid = false;
                                        }

                                        // Check if any of the university fees are selected and have value
                                        let universityFeeSelected = false;
                                        if ($("#universityFeeLKR").is(":checked") && $("#uniFeeLKR").val() !== "") {
                                            universityFeeSelected = true;
                                        }
                                        if ($("#universityFeeGBP").is(":checked") && $("#uniFeeGBP").val() !== "") {
                                            universityFeeSelected = true;
                                        }
                                        if ($("#universityFeeUSD").is(":checked") && $("#uniFeeUSD").val() !== "") {
                                            universityFeeSelected = true;
                                        }

                                        if (!universityFeeSelected) {
                                            isValid = false;
                                        }

                                        // Check if any of the course fees are selected and have value
                                        let courseFeeSelected = false;
                                        if ($("#courseFeeLKR").is(":checked") && $("#courseFeeInputLKR").val() !== "") {
                                            courseFeeSelected = true;
                                        }
                                        if ($("#courseFeeGBP").is(":checked") && $("#courseFeeInputGBP").val() !== "") {
                                            courseFeeSelected = true;
                                        }
                                        if ($("#courseFeeUSD").is(":checked") && $("#courseFeeInputUSD").val() !== "") {
                                            courseFeeSelected = true;
                                        }

                                        if (!courseFeeSelected) {
                                            isValid = false;
                                        }

                                        // Check if any of the registration fees are selected and have value
                                        let registrationFeeSelected = false;
                                        if ($("#registrationFeeLKR").is(":checked") && $("#registrationFeeInputLKR").val() !== "") {
                                            registrationFeeSelected = true;
                                        }
                                        if ($("#registrationFeeGBP").is(":checked") && $("#registrationFeeInputGBP").val() !== "") {
                                            registrationFeeSelected = true;
                                        }
                                        if ($("#registrationFeeUSD").is(":checked") && $("#registrationFeeInputUSD").val() !== "") {
                                            registrationFeeSelected = true;
                                        }

                                        if (!registrationFeeSelected) {
                                            isValid = false;
                                        }

                                        // Enable or disable the Save button based on validation
                                        if (isValid) {
                                            $("button[name='saveButton']").prop("disabled", false);
                                        } else {
                                            $("button[name='saveButton']").prop("disabled", true);
                                        }
                                    }

                                    // Trigger validation on input change
                                    $("#student, #uniFeeLKR, #uniFeeGBP, #uniFeeUSD, #courseFeeLKR, #courseFeeGBP, #courseFeeUSD, #registrationFeeLKR, #registrationFeeGBP, #registrationFeeUSD, input[type='text']").on('change keyup input', function() {
                                        checkRequiredFields();
                                    });

                                    // Trigger initial validation
                                    checkRequiredFields();

                                    // First AJAX call - Check for existing payment plan
                                    $('#student').change(function() {
                                        var student_id = $(this).val();

                                        if (student_id !== '') {
                                            $.ajax({
                                                url: 'add_payment_plan_folder/get_student_payment_plan.php',
                                                method: 'POST',
                                                data: {
                                                    student_id: student_id
                                                },
                                                dataType: 'json',
                                                success: function(response) {
                                                    console.log(response);
                                                    if (response.success) {
                                                        // Hide alert when valid payment plan is found
                                                        $('#paymentPlanAlert').hide();

                                                        // Populate the form fields with the fetched values
                                                        $('#programmeBatch').val(response.programme_batch).prop('readonly', true);
                                                        $('#programId').val(response.program_id).prop('readonly', true);
                                                        $('#batchId').val(response.batch_id).prop('readonly', true);

                                                        $('#uniFeeLKR').val(response.university_fee_LKR > 0 ? response.university_fee_LKR : '').prop('disabled', response.university_fee_LKR <= 0);
                                                        $('#uniFeeGBP').val(response.university_fee_GBP > 0 ? response.university_fee_GBP : '').prop('disabled', response.university_fee_GBP <= 0);
                                                        $('#uniFeeUSD').val(response.university_fee_USD > 0 ? response.university_fee_USD : '').prop('disabled', response.university_fee_USD <= 0);

                                                        $('#courseFeeInputLKR').val(response.course_fee_LKR > 0 ? response.course_fee_LKR : '').prop('disabled', response.course_fee_LKR <= 0);
                                                        $('#courseFeeInputGBP').val(response.course_fee_GBP > 0 ? response.course_fee_GBP : '').prop('disabled', response.course_fee_GBP <= 0);
                                                        $('#courseFeeInputUSD').val(response.course_fee_USD > 0 ? response.course_fee_USD : '').prop('disabled', response.course_fee_USD <= 0);

                                                        // Set radio button selection based on course fee type
                                                        if (response.course_fee_type_LKR === 'full') {
                                                            $('#courseFeeLKRFull').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputLKR', false);
                                                        } else if (response.course_fee_type_LKR === 'installment') {
                                                            $('#courseFeeLKRInstallment').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputLKR', true);
                                                        }
                                                        if (response.course_fee_type_GBP === 'full') {
                                                            $('#courseFeeGBPFull').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputGBP', false);
                                                        } else if (response.course_fee_type_GBP === 'installment') {
                                                            $('#courseFeeGBPInstallment').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputGBP', true);
                                                        }
                                                        if (response.course_fee_type_USD === 'full') {
                                                            $('#courseFeeUSDFull').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputUSD', false);
                                                        } else if (response.course_fee_type_USD === 'installment') {
                                                            $('#courseFeeUSDInstallment').prop('checked', true);
                                                            toggleInstallmentInput('installmentsInputUSD', true);
                                                        }
                                                        $('#installmentsInputLKR').val(response.installment_month_LKR > 0 ? response.installment_month_LKR : '').prop('disabled', response.installment_month_LKR <= 0);
                                                        $('#installmentsInputGBP').val(response.installment_month_GBP > 0 ? response.installment_month_GBP : '').prop('disabled', response.installment_month_GBP <= 0);
                                                        $('#installmentsInputUSD').val(response.installment_month_USD > 0 ? response.installment_month_USD : '').prop('disabled', response.installment_month_USD <= 0);

                                                        $('#registrationFeeInputLKR').val(response.registration_fee_LKR > 0 ? response.registration_fee_LKR : '').prop('disabled', response.registration_fee_LKR <= 0);
                                                        $('#registrationFeeInputGBP').val(response.registration_fee_GBP > 0 ? response.registration_fee_GBP : '').prop('disabled', response.registration_fee_GBP <= 0);
                                                        $('#registrationFeeInputUSD').val(response.registration_fee_USD > 0 ? response.registration_fee_USD : '').prop('disabled', response.registration_fee_USD <= 0);

                                                        // Display payment details in the specified order
                                                        $('#paymentDetails').html(`
                                                            <div class="">
                                                                <table class="table table-bordered table-striped table-hover">
                                                                    <tbody>
                                                                        ${response.student_id ? `<tr><th>Student ID</th><td>${response.student_id}</td></tr>` : ''}
                                                                        ${response.programme_batch ? `<tr><th>Programme Batch</th><td>${response.programme_batch}</td></tr>` : ''}
                                                                        ${response.program_id ? `<tr><th>Program ID</th><td>${response.program_id}</td></tr>` : ''}
                                                                        ${response.batch_id ? `<tr><th>Batch ID</th><td>${response.batch_id}</td></tr>` : ''}
                                                                        ${response.courseFeeLKR_total > 0 ? `<tr><th>Total Course Fee (LKR)</th><td>${response.courseFeeLKR_total}.00</td></tr>` : ''}
                                                                        ${response.course_fee_LKR > 0 ? `<tr><th>Course Fee (LKR)</th><td>${response.course_fee_LKR}.00</td></tr>` : ''}
                                                                        ${response.course_fee_type_LKR ? `<tr><th>Course Fee Type (LKR)</th><td>${response.course_fee_type_LKR}</td></tr>` : ''}
                                                                        ${response.installment_month_LKR > 0 ? `<tr><th>Installment Months (LKR)</th><td>${response.installment_month_LKR}</td></tr>` : ''}
                                                                        ${response.registration_fee_LKR > 0 ? `<tr><th>Registration Fee (LKR)</th><td>${response.registration_fee_LKR}.00</td></tr>` : ''}
                                                                        ${response.university_fee_GBP > 0 ? `<tr><th>University Fee (GBP)</th><td>${response.university_fee_GBP}.00</td></tr>` : ''}
                                                                        ${response.entered_by ? `<tr><th>Entered By</th><td>${response.entered_by}</td></tr>` : ''}
                                                                        ${response.created_at ? `<tr><th>Created At</th><td>${response.created_at}</td></tr>` : ''}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        `);
                                                    } else {
                                                        // If no existing payment plan, fetch program fees (V4 CODE)
                                                        fetchProgramFees(student_id);
                                                    }
                                                },
                                                error: function() {
                                                    // Show alert for connection/server errors
                                                    $('#alertMessage').text('Unable to check existing payment plans. Attempting to load program fees...');
                                                    $('#paymentPlanAlert').show();

                                                    // If error, fetch program fees (V4 CODE)
                                                    fetchProgramFees(student_id);
                                                }
                                            });
                                        } else {
                                            // Hide alert when no student is selected
                                            $('#paymentPlanAlert').hide();
                                            // Reset the form if no student is selected
                                            clearAllFeeFields();
                                        }
                                    });

                                    // V4 CODE - Function to fetch program fees
                                    function fetchProgramFees(studentCode) {
                                        $.ajax({
                                            url: 'add_payment_plan_folder/fetch_programme_batch.php',
                                            method: 'GET',
                                            data: {
                                                student_code: studentCode
                                            },
                                            dataType: 'json',
                                            success: function(data) {
                                                if (data && data.success !== false) {
                                                    // Hide alert if data is found
                                                    $('#paymentPlanAlert').hide();

                                                    // Show the programme_name and batch_name in the input field
                                                    $('#programmeBatch').val(data.program_name + ' - ' + data.batch_name).prop('readonly', true);
                                                    $('#programId').val(data.programme_code).prop('readonly', true);
                                                    $('#batchId').val(data.batch_id).prop('readonly', true);

                                                    // ===== UNIVERSITY FEE SECTION =====
                                                    // Only populate international currencies (GBP, USD, EUR)

                                                    // GBP University Fee
                                                    if (data.program_fee_gbp > 0) {
                                                        $('#uniFeeGBP').val(data.program_fee_gbp);
                                                        $('#universityFeeGBP').prop('checked', true);
                                                        $('#uniFeeGBP').prop('disabled', false);
                                                    } else {
                                                        $('#uniFeeGBP').val('').prop('disabled', true);
                                                        $('#universityFeeGBP').prop('checked', false);
                                                    }

                                                    // USD University Fee
                                                    if (data.program_fee_usd > 0) {
                                                        $('#uniFeeUSD').val(data.program_fee_usd);
                                                        $('#universityFeeUSD').prop('checked', true);
                                                        $('#uniFeeUSD').prop('disabled', false);
                                                    } else {
                                                        $('#uniFeeUSD').val('').prop('disabled', true);
                                                        $('#universityFeeUSD').prop('checked', false);
                                                    }

                                                    // EUR University Fee (if you have this field in your form)
                                                    if (data.program_fee_euro > 0 && $('#uniFeeEUR').length) {
                                                        $('#uniFeeEUR').val(data.program_fee_euro);
                                                        $('#universityFeeEUR').prop('checked', true);
                                                        $('#uniFeeEUR').prop('disabled', false);
                                                    }

                                                    // Clear LKR University Fee (since it shouldn't be used for university fees)
                                                    $('#uniFeeLKR').val('').prop('disabled', true);
                                                    $('#universityFeeLKR').prop('checked', false);

                                                    // ===== COURSE FEE SECTION =====
                                                    // Only populate LKR currency

                                                    // LKR Course Fee
                                                    if (data.program_fee_lkr > 0) {
                                                        $('#courseFeeInputLKR').val(data.program_fee_lkr);
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

                                                    // Show program fee details
                                                    $('#paymentDetails').html(`
                                                        <div class="card">
                                                            <div class="card-header"><h6>Program Fee Details</h6></div>
                                                            <div class="card-body">
                                                                <table class="table table-sm table-bordered">
                                                                    <tbody>
                                                                        <tr><th>Program</th><td>${data.program_name}</td></tr>
                                                                        <tr><th>Batch</th><td>${data.batch_name}</td></tr>
                                                                        ${data.program_fee_lkr > 0 ? `<tr><th>Course Fee (LKR)</th><td>${data.program_fee_lkr}</td></tr>` : ''}
                                                                        ${data.program_fee_gbp > 0 ? `<tr><th>University Fee (GBP)</th><td>${data.program_fee_gbp}</td></tr>` : ''}
                                                                        ${data.program_fee_usd > 0 ? `<tr><th>University Fee (USD)</th><td>${data.program_fee_usd}</td></tr>` : ''}
                                                                        ${data.program_fee_euro > 0 ? `<tr><th>University Fee (EUR)</th><td>${data.program_fee_euro}</td></tr>` : ''}
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    `);

                                                    // Trigger validation check after populating fields
                                                    checkRequiredFields();

                                                } else {
                                                    // Show danger alert when no program data is found
                                                    $('#alertMessage').text('This student doesn\'t have an allocated payment plan. Please contact the administration to set up program fees.');
                                                    $('#paymentPlanAlert').show();

                                                    $('#programmeBatch').val('No programme and batch found').prop('readonly', true);
                                                    clearAllFeeFields();
                                                    $('#paymentDetails').html('<div class="alert alert-warning">No Payment Plan Allocated for this Student.</div>');
                                                }
                                            },
                                            error: function() {
                                                // Show danger alert for connection errors
                                                $('#alertMessage').text('Error fetching programme and batch details. Please try again or contact support.');
                                                $('#paymentPlanAlert').show();

                                                clearAllFeeFields();
                                                $('#paymentDetails').html('<div class="alert alert-danger">Error loading student data.</div>');
                                            }
                                        });
                                    }

                                    // V4 CODE - Function to clear all fee fields
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

                                        // Clear payment details
                                        $('#paymentDetails').html('');
                                        $('#programmeBatch').val('');
                                    }
                                });
                            </script>
                        </div>
                    </div>

                    <div class="col-md-4" style="font-size: 11px;">
                        <div class="card-body">
                            <div id="paymentDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let initialCourseFee_LKR = 0;
    let initialCourseFee_GBP = 0;
    let initialCourseFee_USD = 0;

    function storeInitialCourseFee_LKR() {
        let courseFee_LKR = parseFloat(document.getElementById("courseFeeInputLKR").value) || 0;
        initialCourseFee_LKR = courseFee_LKR;
    }

    function calculateFinalCourseFee_LKR() {
        let registrationFee_LKR = parseFloat(document.getElementById("registrationFeeInputLKR").value) || 0;
        let finalFee_LKR = initialCourseFee_LKR - registrationFee_LKR;

        if (finalFee_LKR < 0) {
            finalFee_LKR = 0;
        }

        document.getElementById("courseFeeInputLKR_initial_value").value = initialCourseFee_LKR;
        document.getElementById("courseFeeInputLKR").value = finalFee_LKR;
    }

    function storeInitialCourseFee_GBP() {
        let courseFee_GBP = parseFloat(document.getElementById("courseFeeInputGBP").value) || 0;
        initialCourseFee_GBP = courseFee_GBP;
    }

    function calculateFinalCourseFee_GBP() {
        let registrationFee_GBP = parseFloat(document.getElementById("registrationFeeInputGBP").value) || 0;
        let finalFee_GBP = initialCourseFee_GBP - registrationFee_GBP;

        if (finalFee_GBP < 0) {
            finalFee_GBP = 0;
        }

        document.getElementById("courseFeeInputGBP_initial_value").value = initialCourseFee_GBP;
        document.getElementById("courseFeeInputGBP").value = finalFee_GBP;
    }

    function storeInitialCourseFee_USD() {
        let courseFee_USD = parseFloat(document.getElementById("courseFeeInputUSD").value) || 0;
        initialCourseFee_USD = courseFee_USD;
    }

    function calculateFinalCourseFee_USD() {
        let registrationFee_USD = parseFloat(document.getElementById("registrationFeeInputUSD").value) || 0;
        let finalFee_USD = initialCourseFee_USD - registrationFee_USD;

        if (finalFee_USD < 0) {
            finalFee_USD = 0;
        }

        document.getElementById("courseFeeInputUSD_initial_value").value = initialCourseFee_USD;
        document.getElementById("courseFeeInputUSD").value = finalFee_USD;
    }
</script>

<script>
    $(document).ready(function() {
        $('.select2').select2();

        // Load students with active status from allocate_programme table
        $.ajax({
            url: 'add_payment_plan_folder/fetch_students.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    data.forEach(function(student) {
                        $('#student').append('<option value="' + student.student_code + '">' + student.student_registration_id + ' | ' + student.first_name + ' ' + student.last_name + '</option>');
                    });
                } else {
                    $('#student').append('<option value="">No students found</option>');
                }
            },
            error: function() {
                alert('Error fetching students');
            }
        });
    });

    function toggleInput(checkboxId, inputId) {
        const checkbox = document.getElementById(checkboxId);
        const input = document.getElementById(inputId);
        input.disabled = !checkbox.checked;
    }

    function toggleInstallmentInput(inputId, isEnabled) {
        const input = document.getElementById(inputId);
        input.disabled = !isEnabled;
    }
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>