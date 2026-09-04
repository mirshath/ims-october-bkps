<?php
session_start();

include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}
$Session_username = $_SESSION['username'];

// Retrieve values from the URL
$studentCode = isset($_GET['studentCode']) ? $_GET['studentCode'] : '';
$programmeId = isset($_GET['programmeId']) ? $_GET['programmeId'] : '';
$batchId = isset($_GET['batchId']) ? $_GET['batchId'] : '';
$regID = isset($_GET['registrationId']) ? $_GET['registrationId'] : '';

// Retrieve program name and batch name based on IDs
$programName = '';
$batchName = '';
$studentName = '';
$courseFeeLKR = 0;

// Query to get student name
$studentQuery = "SELECT first_name, last_name FROM students WHERE student_code = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("s", $studentCode);
$stmt->execute();
$stmt->bind_result($firstName, $lastName);
$stmt->fetch();
$stmt->close();
$studentName = htmlspecialchars($firstName . ' ' . $lastName);

// Query to get program name and course fee
$programQuery = "SELECT program_name, course_fee_lkr FROM program_table WHERE program_code = ?";
$stmt = $conn->prepare($programQuery);
$stmt->bind_param("i", $programmeId);
$stmt->execute();
$stmt->bind_result($programName, $courseFeeLKR);
$stmt->fetch();
$stmt->close();

// Query to get batch name
$batchQuery = "SELECT batch_name FROM batch_table WHERE id = ?";
$stmt = $conn->prepare($batchQuery);
$stmt->bind_param("i", $batchId);
$stmt->execute();
$stmt->bind_result($batchName);
$stmt->fetch();
$stmt->close();


// Store program and batch names in a single variable
$programBatchInfo = htmlspecialchars($programName) . ' - ' . htmlspecialchars($batchName);

// ---------------- installment details --------------------------------
$query = "SELECT * FROM installment_details_table WHERE student_id = ? AND programme_batch = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $studentCode, $programBatchInfo);
$stmt->execute();
$result = $stmt->get_result();
$installments = [];
while ($row = $result->fetch_assoc()) {
    $installments[] = $row;
}
$stmt->close();


// Query to get discounted percentage
$discountQuery = "SELECT discounted_percentage, dis_yes_no FROM installment_payment_table WHERE student_id = ? AND programme_batch = ?";
$stmt = $conn->prepare($discountQuery);
$stmt->bind_param("ss", $studentCode, $programBatchInfo);
$stmt->execute();
$stmt->bind_result($discountedPercentage, $disYesNo);
$stmt->fetch();
$stmt->close();

// ---------------------------- 26.09.2025 ------- 

$totalDiscount = 0;

if ($studentCode && $programmeId && $batchId) {
    $historyQuery = "SELECT SUM(discount_value) AS total_discount
                     FROM payment_plan_history
                     WHERE student_id = ? AND program_id = ? AND batch_id = ?";

    $stmt = $conn->prepare($historyQuery);
    $stmt->bind_param("sii", $studentCode, $programmeId, $batchId);
    $stmt->execute();
    $stmt->bind_result($totalDiscount);
    $stmt->fetch();
    $stmt->close();
}



$totalDiscount_regfee = 0;

if ($studentCode && $programmeId && $batchId) {

    $historyRegQuery = "SELECT SUM(discount_value) AS total_reg_discount
                     FROM payment_plan_regfee_discount
                     WHERE student_id = ? AND program_id = ? AND batch_id = ?";

    $stmt = $conn->prepare($historyRegQuery);
    $stmt->bind_param("sii", $studentCode, $programmeId, $batchId);
    $stmt->execute();
    $stmt->bind_result($totalDiscount_regfee);
    $stmt->fetch();
    $stmt->close();
}

//  Now $totalDiscount contains the sum of discount_value for that student/program/batch

$query = "SELECT i.registrationfee, p.registration_fee_LKR 
          FROM installment_payment_table i
          LEFT JOIN add_payment_plan_table p 
            ON i.student_id = p.student_id 
           AND i.programme_batch = p.programme_batch
          WHERE i.student_id = ? AND i.programme_batch = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $studentCode, $programBatchInfo);
$stmt->execute();
$result = $stmt->get_result();

$registrationFee = 0;
$registrationFeeLKR = 0;

if ($row = $result->fetch_assoc()) {
    $registrationFee   = $row['registrationfee'] ?? 0;
    $registrationFeeLKR = $row['registration_fee_LKR'] ?? 0;
}

$stmt->close();
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Edit Payment Plans</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="add_payment_plan_folder/edit_insert_payment_plan.php">
                                    <input type="hidden" name="username" value="<?php echo $Session_username; ?>">

                                    <div class="row">
                                        <!-- Student Info Card -->
                                        <div class="col-lg-6">
                                            <div class="card shadow-sm border-0 rounded-4">
                                                <div class="card-header bg-gradient-primary rounded-top-4"></div>
                                                <div class="card-body">
                                                    <div class="card shadow-sm border-0 rounded-4">
                                                        <div class="card-body d-flex align-items-center">
                                                            <div class="me-4 text-center">
                                                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRISuukVSb_iHDfPAaDKboFWXZVloJW9XXiwGYFab-QwlAYQ3zFsx4fToY9ijcVNU5ieKk&usqp=CAU"
                                                                    alt="Student Photo"
                                                                    class="rounded-circle"
                                                                    style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <table class="table table-hover">
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($studentName); ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><?php echo $programName; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><?php echo $batchName; ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><?php echo $regID; ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--/ Student Info Card -->

                                        <!-- Program Fee Details -->
                                        <div class="col-md-6 mb-3">
                                            <div class="card shadow">
                                                <div class="card-header">
                                                    <h6>Program Fee Details</h6>
                                                    <small class="text-muted">(Discount will deduct from full amount Not Separately )</small>
                                                </div>

                                                <?php if ($disYesNo == 1): ?>
                                                    <!-- Already Discounted Display -->
                                                    <div class="card-body">
                                                        <table class="table table-striped table-hover">
                                                            <p class="text-center">
                                                                <span style="color: red; font-size: 60px;">
                                                                    ✔️
                                                                </span>
                                                            </p>
                                                            <tr>
                                                                <td colspan="5" class="text-center fw-bolder" style="font-size: 13px; color: red;">Already Discounted for this person</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="5" class="text-center fw-bolder mb-4" style="font-size: 25px; color: red;"><?php echo number_format($discountedPercentage, 2); ?> % From Total Amount ( <?= number_format($courseFeeLKR, 2) ?> )</td>
                                                            </tr>
                                                            <tr style="text-align: center;">
                                                                <td style="display: flex; align-items: center;"> <b>Deduct from Amount </b><input type="text" placeholder="Again Discount value" class="form-control ml-2 w-50" id="deductAmountInput"></td>
                                                                <td><b>OR </b></td>
                                                                <td><b>Full Amount </b><input type="checkbox" name="" id="fullAmountCheckbox"></td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Discount Form Display -->
                                                    <div class="card-body">
                                                        <table class="table table-striped table-hover">
                                                            <tr>
                                                                <td><strong>Course Fee (LKR)</strong></td>
                                                                <td>
                                                                    <input type="text" class="form-control" id="originalFee" value="<?php echo number_format($courseFeeLKR, 2); ?>" readonly>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Discount Type</strong></td>
                                                                <td>
                                                                    <select class="form-control select2" id="discountType" onchange="toggleProgramDiscount()">
                                                                        <option value="N/A">N/A</option>
                                                                        <option value="Value">Value</option>
                                                                        <option value="Percentage">Percentage</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <tr id="discountValueRow" style="display: none;">
                                                                <td><strong>Discount Value</strong></td>
                                                                <td>
                                                                    <input type="text" class="form-control" id="discountValue" placeholder="Enter discount" oninput="calculateProgramDiscount()">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Final Fee (LKR)</strong></td>
                                                                <td>
                                                                    <input type="text" class="form-control" id="finalFee" value="<?php echo number_format($courseFeeLKR, 2); ?>" readonly>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <!--/ Program Fee Details -->

                                        <!-- Registration Fee Section -->
                                        <div class="mt-4 mb-4">
                                            <div class="card shadow">
                                                <div class="card-header d-flex justify-content-between">
                                                    <h6><b>Edit for Registration Fee</b></h6>
                                                    <b class="text-danger">Total Discount From Registration / Initial Amount: <?= !empty($totalDiscount_regfee) ? $totalDiscount_regfee : 'N/A'; ?></b>
                                                    <button class="btn btn-sm btn-secondary" id="regfeeDiscountDatas"><i class="fas fa-history"></i> View Logs for Initial Amount</button>
                                                </div>
                                                <div class="card-body">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Type</th>
                                                                <th>Amount of Registration Fee</th>
                                                                <th>Discount Value of Reg Fee</th>
                                                                <th>Amount Due</th>
                                                                <th>Remark</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Registration Fee</td>
                                                                <td id="regFeeAmount"><?php echo number_format($registrationFeeLKR, 2); ?></td>
                                                                <td>
                                                                    <input type="number" class='form-control' name="regFeeDiscount" id="regFeeDiscount" value="<?php echo max(0, round($registrationFeeLKR - $registrationFee, 2)); ?>" min="0" style="width:80px;">
                                                                </td>
                                                                <td id="regFeeDue"><?php echo number_format($registrationFee, 2); ?></td>
                                                                <td>
                                                                    <input type="text" name="regFeeRemark" class='form-control' id="regFeeRemark" placeholder="Enter remark">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>

                                                    <!-- Hidden fields -->
                                                    <input type="hidden" name="student_id" value="<?php echo $studentCode; ?>">
                                                    <input type="hidden" name="programme_batch" value="<?php echo htmlspecialchars($programBatchInfo); ?>">
                                                    <input type="hidden" name="dtype" value="regfeeDiscount">
                                                    <input type="hidden" name="program_name" value="<?php echo htmlspecialchars($programName); ?>">
                                                    <input type="hidden" name="batch_name" value="<?php echo htmlspecialchars($batchName); ?>">

                                                    <div class="text-right">
                                                        <!-- <button type="submit" class="btn btn-primary btn-sm" formaction="process_regfee.php">Submit For registration Discount</button> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <!--/ Registration Fee Section -->

                                        <!-- Installment Details -->
                                        <div class="col-md-12 mb-3">
                                            <div class="card shadow">
                                                <div class="card-header d-flex justify-content-between">
                                                    <h6> <b>Edit for Installment Details</b> </h6>
                                                    <b class="text-danger"> Total Discount From Installment Amount: <?= !empty($totalDiscount) ? $totalDiscount : 'N/A'; ?></b>
                                                    <button class="btn btn-sm  btn-secondary" id="viewLogsBtn"><i class="fas fa-history"></i> View Logs for installment Amount</button>
                                                </div>
                                                <input type="hidden" id="studentId" value="<?php echo htmlspecialchars($studentCode); ?>">
                                                <input type="hidden" id="programId" value="<?php echo htmlspecialchars($programmeId); ?>">
                                                <input type="hidden" id="batchId" value="<?php echo htmlspecialchars($batchId); ?>">
                                                <div class="card-body">
                                                    <?php if (empty($installments)): ?>
                                                        <p class="text-danger fw-bolder">No payment data available. Please add the payment plan first.</p>
                                                        <a href="add_payment_plan" class="btn btn-primary btn-sm">Add Payment Plan</a>
                                                    <?php else: ?>
                                                        <form method="post" action="">
                                                            <table class='table  table-striped table-hover'>
                                                                <tr>
                                                                    <th style="display: none;">id</th>
                                                                    <th>Installment</th>
                                                                    <th style="display: nones;">Devided Amount</th>
                                                                    <th>Amount</th>
                                                                    <th>Discount Type</th>
                                                                    <th>Discount Value</th>
                                                                    <th>Due Date</th>
                                                                    <th>Remark</th>
                                                                </tr>
                                                                <?php foreach ($installments as $installment): ?>
                                                                    <tr class="installment-row">
                                                                        <td style="display: none;"><?php echo $installment['id']; ?></td>
                                                                        <td><?php echo " " . str_replace('_', ' ', $installment['installment_numbers']); ?></td>
                                                                        <td style="display: nones;">
                                                                            <input type='text' class='form-control installment-amounts'
                                                                                value='<?php echo number_format($installment['devided_values'], 2); ?>'
                                                                                data-original-divided='<?php echo $installment['devided_values']; ?>' readonly>
                                                                        </td>
                                                                        <td>
                                                                            <input type='text' class='form-control installment-amount' readonly
                                                                                value='<?php echo number_format($installment['installment_amount'], 2); ?>'
                                                                                data-original-installment='<?php echo $installment['installment_amount']; ?>'
                                                                                <?php echo ($installment['installment_amount'] == 0 || $installment['installment_amount'] == 0.00) ? 'readonly' : ''; ?>>
                                                                        </td>
                                                                        <td>
                                                                            <select class='form-control discount-type select2' name='installment_discount_type[]'
                                                                                onchange="toggleDiscountInput(this)"
                                                                                <?php echo ($installment['installment_amount'] == 0 || $installment['installment_amount'] == 0.00) ? 'disabled' : ''; ?>>
                                                                                <option value='N/A' <?php echo $installment['discount_type'] == 'N/A' ? 'selected' : ''; ?>>N/A</option>
                                                                                <option value='Value' <?php echo $installment['discount_type'] == 'Value' || $installment['discount_type'] == 'Percentage' ? 'selected' : ''; ?>>Value</option>
                                                                                <option value='Percentage' disabled <?php echo $installment['discount_type'] == 'Percentage' ? '' : ''; ?>>Percentage</option>
                                                                            </select>
                                                                        </td>
                                                                        <td>
                                                                            <input type='text' class='form-control discount-value' name='installment_discount_value[]'
                                                                                value='<?php echo ($installment['discount_type'] != 'N/A' && isset($installment['discount_value'])) ? round($installment['discount_value'], 2) : ''; ?>'
                                                                                style='display: <?php echo $installment['discount_type'] == 'N/A' ? 'none' : 'block'; ?>;'
                                                                                placeholder='Enter discount'
                                                                                oninput="calculateDiscount(this)"
                                                                                <?php echo ($installment['installment_amount'] == 0 || $installment['installment_amount'] == 0.00) ? 'disabled' : ''; ?>>
                                                                        </td>
                                                                        <td>
                                                                            <input type='date' class='form-control' name='installment_due_date[]'
                                                                                value='<?php echo $installment['due_date']; ?>'
                                                                                <?php echo ($installment['installment_amount'] == 0 || $installment['installment_amount'] == 0.00) ? 'disabled' : ''; ?>>
                                                                        </td>
                                                                        <td>
                                                                            <input type='text' class='form-control' name='installment_remark[]'
                                                                                value='<?php echo $installment['remark']; ?>'
                                                                                placeholder='Enter remark'
                                                                                <?php echo ($installment['installment_amount'] == 0 || $installment['installment_amount'] == 0.00) ? 'disabled' : ''; ?>>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </table>
                                                            <div style="text-align: right;">
                                                                <!-- <button type="button" class="btn btn-primary btn-sm" onclick="submitUpdatedRows(event)">Submit For Installment Discount</button> -->
                                                                <button type="button" class="btn btn-success btn-sm" onclick="submitCombinedDiscounts(event)">Submit All Discounts</button>
                                                            </div>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <!--/ Installment Details -->

                                    </div>
                                </form>
                                <!-- Scripts -->
                                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                <script>
                                    $(document).ready(function() {
                                        $(".select2").select2({
                                            width: '100%'
                                        });
                                    });

                                    // Save registration LKR amount and the current discount in JS
                                    let registrationFeeLKR = <?php echo json_encode($registrationFeeLKR); ?>;
                                    let registrationFee = <?php echo json_encode($registrationFee); ?>;
                                    let regFeeDiscountInput = null,
                                        regFeeDueCell = null;

                                    $(document).ready(function() {
                                        regFeeDiscountInput = document.getElementById('regFeeDiscount');
                                        regFeeDueCell = document.getElementById('regFeeDue');

                                        // If discount entered here, update the amount due
                                        regFeeDiscountInput.addEventListener('input', function() {
                                            let discount = parseFloat(this.value) || 0;
                                            if (discount > registrationFee) {
                                                discount = registrationFee;
                                                this.value = discount.toFixed(2);
                                            }
                                            const newDue = registrationFee - discount;
                                            regFeeDueCell.textContent = newDue.toFixed(2);
                                        });
                                    });

                                    // -- AUTO APPLY: When program discount is entered, affect installments and reg fee
                                    function toggleProgramDiscount() {
                                        const discountType = document.getElementById('discountType').value;
                                        const discountRow = document.getElementById('discountValueRow');
                                        const discountInput = document.getElementById('discountValue');
                                        if (discountType === 'N/A') {
                                            discountRow.style.display = 'none';
                                            discountInput.value = '';
                                            resetInstallmentsToOriginal();
                                            resetRegistrationDiscount(); // custom
                                        } else {
                                            discountRow.style.display = 'table-row';
                                            discountInput.value = '';
                                        }
                                        calculateProgramDiscount();
                                    }

                                    function resetRegistrationDiscount() {
                                        if (regFeeDiscountInput && regFeeDueCell) {
                                            regFeeDiscountInput.value = '';
                                            regFeeDueCell.textContent = Number(registrationFee).toFixed(2);
                                            regFeeDiscountInput.readOnly = false;
                                            regFeeDiscountInput.style.backgroundColor = '';
                                        }
                                    }

                                    function lockRegistrationDiscount(val) {
                                        if (regFeeDiscountInput && regFeeDueCell) {
                                            regFeeDiscountInput.value = val;
                                            regFeeDueCell.textContent = (Number(registrationFee) - Number(val)).toFixed(2);
                                            regFeeDiscountInput.readOnly = true;
                                            regFeeDiscountInput.style.backgroundColor = '#f8f9fa';
                                        }
                                    }

                                    function unlockRegistrationDiscount() {
                                        if (regFeeDiscountInput && regFeeDueCell) {
                                            regFeeDiscountInput.readOnly = false;
                                            regFeeDiscountInput.style.backgroundColor = '';
                                        }
                                    }

                                    function calculateProgramDiscount() {
                                        const discountType = document.getElementById('discountType').value;
                                        const discountValueInput = document.getElementById('discountValue');
                                        const originalFee = parseFloat(document.getElementById('originalFee').value.replace(/,/g, ''));
                                        const discountValue = parseFloat(discountValueInput.value) || 0;

                                        let finalFee = originalFee;
                                        let totalDiscount = 0;

                                        if (discountType === 'Value') {
                                            if (discountValue > originalFee) {
                                                alert("Discount value can't be greater than the original fee.");
                                                discountValueInput.value = originalFee;
                                                totalDiscount = originalFee;
                                            } else {
                                                totalDiscount = discountValue;
                                            }
                                        } else if (discountType === 'Percentage') {
                                            if (discountValue > 100) {
                                                alert("Discount percentage can't exceed 100%.");
                                                discountValueInput.value = 100;
                                                totalDiscount = originalFee;
                                            } else {
                                                totalDiscount = originalFee * (discountValue / 100);
                                            }
                                        }

                                        finalFee = originalFee - totalDiscount;
                                        document.getElementById('finalFee').value = finalFee.toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });

                                        // ----------- Modified logic for registration fee and installment
                                        if ((discountType === 'Value' || discountType === 'Percentage') && totalDiscount > 0) {
                                            applyFullDiscountToAll(totalDiscount, discountType, discountValue);
                                        } else {
                                            resetInstallmentsToOriginal();
                                            resetRegistrationDiscount();
                                        }
                                    }

                                    // New: This distributes discount to both installments and to registration fee in suitable order
                                    function applyFullDiscountToAll(totalDiscount, programDiscountType, programDiscountValue) {
                                        // 1. Distribute as much as possible to ALL fees (installments+regfee), starting with installments and then to registration fee
                                        let installmentRows = Array.from(document.querySelectorAll(".installment-row"));
                                        let allDiscountTargets = [];
                                        for (let i = installmentRows.length - 1; i >= 0; i--) {
                                            // Reverse order (as before)
                                            allDiscountTargets.push({
                                                type: "installment",
                                                row: installmentRows[i],
                                                amountInput: installmentRows[i].querySelector(".installment-amount"),
                                                discountTypeSelect: installmentRows[i].querySelector(".discount-type"),
                                                discountValueInput: installmentRows[i].querySelector(".discount-value"),
                                                original: Number.parseFloat(installmentRows[i].querySelector(".installment-amount").getAttribute("data-original-installment")) || 0
                                            });
                                        }
                                        // Then registration fee
                                        allDiscountTargets.push({
                                            type: "regfee",
                                            regFeeDiscountInput: regFeeDiscountInput,
                                            original: Number(registrationFee)
                                        });

                                        let remainingDiscount = totalDiscount;

                                        // First, reset everything
                                        resetInstallmentsToOriginal();
                                        if (regFeeDiscountInput) {
                                            regFeeDiscountInput.value = "";
                                            regFeeDiscountInput.readOnly = false;
                                            regFeeDiscountInput.style.backgroundColor = '';
                                        }

                                        // 2. Distribute discount
                                        for (let t of allDiscountTargets) {
                                            if (remainingDiscount <= 0) break;
                                            if (t.type === "installment") {
                                                if (t.original <= 0) continue;
                                                let thisDiscount = Math.min(t.original, remainingDiscount);
                                                remainingDiscount -= thisDiscount;
                                                t.amountInput.value = (t.original - thisDiscount).toLocaleString("en-US", {
                                                    minimumFractionDigits: 2,
                                                    maximumFractionDigits: 2
                                                });
                                                t.discountTypeSelect.value = programDiscountType;
                                                t.discountValueInput.style.display = "block";
                                                if (programDiscountType === "Percentage") {
                                                    let perc = (thisDiscount / t.original) * 100;
                                                    t.discountValueInput.value = perc.toFixed(2);
                                                    t.discountValueInput.setAttribute("data-program-percentage", "true");
                                                } else {
                                                    t.discountValueInput.value = thisDiscount.toFixed(2);
                                                    t.discountValueInput.removeAttribute("data-program-percentage");
                                                }
                                                t.row.classList.add("program-discount-applied");
                                                t.discountTypeSelect.disabled = true;
                                                t.discountValueInput.readOnly = true;
                                                t.discountValueInput.style.backgroundColor = "#f8f9fa";
                                                t.row.style.backgroundColor = "#f8f9fa";
                                                t.row.classList.add("modified");
                                            } else if (t.type === "regfee" && regFeeDiscountInput) {
                                                let regFeeLeft = t.original;
                                                let thisDiscount = Math.min(regFeeLeft, remainingDiscount);
                                                // Assign discount to reg fee
                                                lockRegistrationDiscount(thisDiscount.toFixed(2));
                                                // If not fully discounted, unlock
                                                if (thisDiscount === 0) {
                                                    unlockRegistrationDiscount();
                                                }
                                                remainingDiscount -= thisDiscount;
                                            }
                                        }
                                    }

                                    // Reset installments to original
                                    function resetInstallmentsToOriginal() {
                                        document.querySelectorAll('.installment-row').forEach(row => {
                                            const amountInput = row.querySelector('.installment-amount');
                                            const discountTypeSelect = row.querySelector('.discount-type');
                                            const discountValueInput = row.querySelector('.discount-value');
                                            const originalInstallmentAmount = parseFloat(amountInput.getAttribute('data-original-installment')) || 0;
                                            amountInput.value = originalInstallmentAmount.toLocaleString('en-US', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            });
                                            if (row.classList.contains('program-discount-applied')) {
                                                discountTypeSelect.value = 'N/A';
                                                discountValueInput.value = '';
                                                discountValueInput.style.display = 'none';
                                                discountTypeSelect.disabled = false;
                                                discountValueInput.readOnly = false;
                                                discountValueInput.style.backgroundColor = '';
                                                row.style.backgroundColor = '';
                                                row.classList.remove('program-discount-applied');
                                                row.classList.add('modified');
                                            }
                                        });
                                    }

                                    // When reg fee input manually changes, remove program-applied readonly style
                                    document.addEventListener("DOMContentLoaded", function() {
                                        if (regFeeDiscountInput) {
                                            regFeeDiscountInput.addEventListener("focus", function() {
                                                unlockRegistrationDiscount();
                                            });
                                        }
                                    });

                                    // -- The rest: LOGS, DEDUCT FROM AMOUNT, FULL AMOUNT checkboxes
                                    $(document).ready(function() {
                                        $('#fullAmountCheckbox').on('change', function() {
                                            if ($(this).is(':checked')) {
                                                // Distribute full program fee discount across all installments + regfee
                                                var totalAvailable = 0;
                                                $('.installment-amount').each(function() {
                                                    let amount = parseFloat($(this).data('original-installment'));
                                                    if (!isNaN(amount)) {
                                                        totalAvailable += amount;
                                                    }
                                                });
                                                totalAvailable += registrationFee; // include reg fee
                                                applyFullDiscountToAll(totalAvailable, 'Value', totalAvailable); // Full
                                                $('#deductAmountInput').val('');
                                            } else {
                                                resetInstallmentsToOriginal();
                                                resetRegistrationDiscount();
                                            }
                                        });

                                        $('#deductAmountInput').on('input', function() {
                                            $('#fullAmountCheckbox').prop('checked', false);
                                            let inputVal = parseFloat($(this).val());
                                            let totalAvailable = 0;
                                            $('.installment-amount').each(function() {
                                                let amount = parseFloat($(this).data('original-installment'));
                                                if (!isNaN(amount)) {
                                                    totalAvailable += amount;
                                                }
                                            });
                                            totalAvailable += registrationFee; // include reg fee
                                            if (isNaN(inputVal) || inputVal <= 0) {
                                                resetInstallmentsToOriginal();
                                                resetRegistrationDiscount();
                                                return;
                                            }
                                            if (inputVal > totalAvailable) {
                                                alert('Deduction amount exceeds total installment + registration amount (LKR ' + totalAvailable.toFixed(2) + ')');
                                                $(this).val('');
                                                resetInstallmentsToOriginal();
                                                resetRegistrationDiscount();
                                                return;
                                            }
                                            applyFullDiscountToAll(inputVal, 'Value', inputVal);
                                        });
                                    });

                                    // Log buttons
                                    $(document).ready(function() {
                                        $('#viewLogsBtn').click(function(e) {
                                            e.preventDefault();
                                            let studentIds = $('#studentId').val();
                                            let programIds = $('#programId').val();
                                            let batchIds = $('#batchId').val();
                                            let url = 'payment_log_view.php?student_id=' + encodeURIComponent(studentIds) +
                                                '&program_id=' + encodeURIComponent(programIds) +
                                                '&batch_id=' + encodeURIComponent(batchIds);
                                            window.open(url, '_blank');
                                        });

                                        $('#regfeeDiscountDatas').click(function(e) {
                                            e.preventDefault();
                                            let studentId = $('#studentId').val();
                                            let programId = $('#programId').val();
                                            let batchId = $('#batchId').val();
                                            let url = 'payment_log_view.php?student_id=' + encodeURIComponent(studentId) +
                                                '&program_id=' + encodeURIComponent(programId) +
                                                '&batch_id=' + encodeURIComponent(batchId);
                                            window.open(url, '_blank');
                                        });
                                    });

                                    // -- Normal discount field per-row
                                    function toggleDiscountInput(selectElement) {
                                        const row = selectElement.closest('tr');
                                        if (row.classList.contains('program-discount-applied')) {
                                            alert('This discount was applied from Program Fee Details and cannot be edited manually.');
                                            return;
                                        }
                                        var inputField = selectElement.closest('tr').querySelector('.discount-value');
                                        var previousType = inputField.getAttribute('data-previous-type') || '';
                                        var currentType = selectElement.value;
                                        if (previousType !== 'N/A' && currentType !== 'N/A' && previousType !== currentType) {
                                            inputField.value = '';
                                            calculateDiscount(inputField);
                                        }
                                        if (currentType === 'N/A') {
                                            inputField.style.display = 'none';
                                            inputField.value = '';
                                            calculateDiscount(inputField);
                                        } else {
                                            inputField.style.display = 'block';
                                        }
                                        inputField.setAttribute('data-previous-type', currentType);
                                        row.classList.add('modified');
                                    }

                                    function calculateDiscount(inputElement) {
                                        const row = inputElement.closest('tr');
                                        if (row.classList.contains('program-discount-applied')) {
                                            alert('This discount was applied from Program Fee Details and cannot be edited manually.');
                                            inputElement.value = inputElement.getAttribute('data-original-value') || '';
                                            return;
                                        }
                                        var installmentAmountInput = row.querySelector('.installment-amount');
                                        var originalInstallmentAmount = parseFloat(installmentAmountInput.getAttribute('data-original-installment')) || 0;
                                        var discountType = row.querySelector('.discount-type').value;
                                        var discountValue = parseFloat(inputElement.value) || 0;
                                        if (!inputElement.value.trim() || discountValue === 0) {
                                            installmentAmountInput.value = originalInstallmentAmount.toLocaleString('en-US', {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            });
                                            row.classList.add('modified');
                                            return;
                                        }
                                        var finalAmount = originalInstallmentAmount;
                                        if (discountType === 'Value') {
                                            if (discountValue > originalInstallmentAmount) {
                                                alert(`Discount value cannot be greater than the installment amount (${originalInstallmentAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})!`);
                                                inputElement.value = originalInstallmentAmount.toFixed(2);
                                                finalAmount = 0;
                                            } else {
                                                finalAmount = originalInstallmentAmount - discountValue;
                                            }
                                        } else if (discountType === 'Percentage') {
                                            if (discountValue > 100) {
                                                alert('Discount percentage cannot be greater than 100%!');
                                                inputElement.value = 100;
                                                finalAmount = 0;
                                            } else {
                                                finalAmount = originalInstallmentAmount - (originalInstallmentAmount * (discountValue / 100));
                                            }
                                        }
                                        finalAmount = Math.max(0, finalAmount);
                                        installmentAmountInput.value = finalAmount.toLocaleString('en-US', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        });
                                        row.classList.add('modified');
                                    }

                                    // Mark row as modified on any manual edit
                                    document.addEventListener('DOMContentLoaded', function() {
                                        document.querySelectorAll('.installment-row').forEach(row => {
                                            row.querySelectorAll('input, select').forEach(input => {
                                                input.addEventListener('change', function() {
                                                    row.classList.add('modified');
                                                });
                                            });
                                        });
                                    });

                                    // Submit logic: same as before
                                    function submitUpdatedRows(event) {
                                        event.preventDefault();
                                        const modifiedRows = [];
                                        document.querySelectorAll(".installment-row.modified").forEach((row) => {
                                            const discountValueInput = row.querySelector(".discount-value");
                                            const isPercentageFromProgram = discountValueInput.getAttribute("data-program-percentage") === "true";
                                            const rowData = {
                                                id: row.querySelector("td:first-child").innerText.trim(),
                                                installment_amount: row.querySelector(".installment-amount").value.replace(/,/g, ""),
                                                discount_type: row.querySelector(".discount-type").value,
                                                due_date: row.querySelector('input[name="installment_due_date[]"]').value,
                                                remark: row.querySelector('input[name="installment_remark[]"]').value,
                                                discount_value: discountValueInput.value,
                                                program_discount_percentage: isPercentageFromProgram ? parseFloat(discountValueInput.value) || 0 : null,
                                            };
                                            modifiedRows.push(rowData);
                                        });

                                        if (modifiedRows.length === 0) {
                                            alert("No changes detected.");
                                            return;
                                        }

                                        fetch("add_payment_plan_folder/update_payment_paln_details.php", {
                                                method: "POST",
                                                headers: {
                                                    "Content-Type": "application/json"
                                                },
                                                body: JSON.stringify(modifiedRows),
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (!data.success) {
                                                    alert("Error updating installments: " + (data.message || "Unknown error"));
                                                    return;
                                                }
                                                const studentId = "<?php echo $studentCode; ?>";
                                                const programId = "<?php echo $programmeId; ?>";
                                                const batchId = "<?php echo $batchId; ?>";
                                                const sessionUsername = "<?php echo $Session_username; ?>";
                                                const discountTypeElem = document.getElementById('discountType');
                                                const discountValueElem = document.getElementById('discountValue');
                                                const originalFeeElem = document.getElementById('originalFee');
                                                let percentagePromise = Promise.resolve();
                                                if (discountTypeElem && discountValueElem && originalFeeElem) {
                                                    const discountType = discountTypeElem.value;
                                                    const discountValue = parseFloat(discountValueElem.value) || 0;
                                                    const originalFee = parseFloat(originalFeeElem.value.replace(/,/g, ''));
                                                    if (discountType === "Percentage" && discountValue > 0) {
                                                        const programmeBatch = "<?php echo $programBatchInfo; ?>";
                                                        percentagePromise = fetch('add_payment_plan_folder/update_discounted_percentage.php', {
                                                                method: "POST",
                                                                headers: {
                                                                    "Content-Type": "application/x-www-form-urlencoded"
                                                                },
                                                                body: new URLSearchParams({
                                                                    student_id: studentId,
                                                                    programme_batch: programmeBatch,
                                                                    percentage: discountValue
                                                                })
                                                            })
                                                            .then(res => res.json())
                                                            .then(resData => {
                                                                if (!resData.success) console.warn("Failed to update program discount:", resData.message);
                                                            })
                                                            .catch(err => {});
                                                    }
                                                }
                                                percentagePromise.then(() => {
                                                    let completed = 0;
                                                    let successCount = 0;
                                                    modifiedRows.forEach(rowData => {
                                                        const historyData = {
                                                            student_id: studentId,
                                                            program_id: programId,
                                                            batch_id: batchId,
                                                            discount_type: rowData.discount_type,
                                                            discount_value: rowData.discount_value,
                                                            remark: rowData.remark,
                                                            session_username: sessionUsername
                                                        };
                                                        fetch("add_payment_plan_folder/insert_payment_plan_history.php", {
                                                                method: "POST",
                                                                headers: {
                                                                    "Content-Type": "application/json"
                                                                },
                                                                body: JSON.stringify(historyData),
                                                            })
                                                            .then(historyRes => historyRes.json())
                                                            .then(historyData => {
                                                                if (historyData.success) successCount++;
                                                                completed++;
                                                                if (completed === modifiedRows.length) {
                                                                    alert(`Updated installments: ${successCount}/${modifiedRows.length}`);
                                                                    location.reload();
                                                                }
                                                            })
                                                            .catch(err => {
                                                                completed++;
                                                                if (completed === modifiedRows.length) {
                                                                    alert(`Some history updates failed. Successful: ${successCount}`);
                                                                    location.reload();
                                                                }
                                                            });
                                                    });
                                                });
                                            })
                                            .catch(err => {
                                                alert("Error updating installments. Please check console for details.");
                                            });
                                    }

                                    // Combined submit: like before
                                    function submitCombinedDiscounts(event) {
                                        event.preventDefault();
                                        const modifiedRows = [];
                                        document.querySelectorAll(".installment-row.modified").forEach((row) => {
                                            const discountValueInput = row.querySelector(".discount-value");
                                            const rowData = {
                                                id: row.querySelector("td:first-child").innerText.trim(),
                                                installment_amount: row.querySelector(".installment-amount").value.replace(/,/g, ""),
                                                discount_type: row.querySelector(".discount-type").value,
                                                due_date: row.querySelector('input[name="installment_due_date[]"]').value,
                                                remark: row.querySelector('input[name="installment_remark[]"]').value,
                                                discount_value: discountValueInput.value
                                            };
                                            modifiedRows.push(rowData);
                                        });

                                        const studentId = "<?php echo $studentCode; ?>";
                                        const programmeBatch = "<?php echo $programBatchInfo; ?>";
                                        const programId = "<?php echo $programmeId; ?>";
                                        const batchId = "<?php echo $batchId; ?>";

                                        const regFeeDiscount = parseFloat(document.getElementById('regFeeDiscount').value) || 0;
                                        const regFeeRemark = document.getElementById('regFeeRemark').value || '';
                                        const dtype = 'regfeeDiscount';

                                        let programDiscountPercentage = 0;
                                        const discountTypeElem = document.getElementById('discountType');
                                        const discountValueElem = document.getElementById('discountValue');
                                        if (discountTypeElem && discountValueElem) {
                                            const dt = discountTypeElem.value;
                                            const dv = parseFloat(discountValueElem.value) || 0;
                                            if (dt === 'Percentage' && dv > 0) {
                                                programDiscountPercentage = dv;
                                            }
                                        }

                                        const payload = {
                                            student_id: studentId,
                                            programme_batch: programmeBatch,
                                            program_id: programId,
                                            batch_id: batchId,
                                            registration: {
                                                discount_value: regFeeDiscount,
                                                remark: regFeeRemark,
                                                dtype
                                            },
                                            installments: modifiedRows,
                                            program_discount_percentage: programDiscountPercentage
                                        };

                                        fetch("add_payment_plan_folder/submit_combined_discounts.php", {
                                                method: "POST",
                                                headers: {
                                                    "Content-Type": "application/json"
                                                },
                                                body: JSON.stringify(payload)
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (!data || !data.success) {
                                                    alert("Error submitting discounts");
                                                    return;
                                                }
                                                alert("Discounts submitted successfully");
                                                location.reload();
                                            })
                                            .catch(() => {
                                                alert("Network error");
                                            });
                                    }
                                </script>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>

</html>