<?php
session_start();
include("database/connection.php");
include("includes/header.php");

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 

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
            <div class="p-3" style="font-size: 14px;">
                <div class="mb-4">
                    <h1 class="text-center fw-bolder">Students's Payment Data Check Report</h1>
                </div>

                <!-- Student Dropdown -->
                <style>
                    .google-center {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                    }

                    .google-label {
                        font-size: 1.35rem;
                        font-weight: 500;
                        margin-bottom: 1rem;
                        text-align: center;
                        color: #333;
                        letter-spacing: 0.5px;
                    }

                    .google-select {
                        width: 100%;
                        max-width: 450px;
                        background: #f8fafc;
                        border: 1.5px solid #e0e3e6;
                        border-radius: 12px;
                        padding: 12px 14px;
                        font-size: 1.1rem;
                        color: #222;
                        transition: border .2s;
                        margin-bottom: 1rem;
                        box-shadow: 0 2px 6px rgba(60, 64, 67, .08);
                    }

                    .google-select:focus {
                        border-color: #4285f4;
                        outline: none;
                        box-shadow: 0 0 0 2px #a6c8ff29;
                    }

                    .export-btns-section {
                        margin: 15px 0;
                        display: flex;
                        gap: 5px;
                        flex-wrap: wrap;
                        justify-content: center;
                    }
                </style>
                <div class="mb-4 google-center">
                    <label for="studentDropdown" class="form-label fw-semibold google-label">
                        Select Student <span class="text-danger">*</span>
                    </label>
                    <select name="student_code" id="studentDropdown" class="form-select select2 google-select" required>
                        <option value="">Select Student</option>
                        <?php
                        $sql = "
                            SELECT 
                                ap.student_code,
                                ap.student_registration_id,
                                s.first_name,
                                s.last_name,
                                s.nic
                            FROM allocate_programme ap
                            LEFT JOIN students s ON ap.student_code = s.student_code
                            GROUP BY ap.student_code
                            ORDER BY s.first_name ASC
                        ";
                        $result = $conn->query($sql);

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $student_code = $row['student_code'];
                                $bms_id       = $row['student_registration_id'] ?: "No BMS ID";
                                $full_name    = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                                if ($full_name === '') $full_name = "No Name";
                                $nic          = $row['nic'] ?: "No NIC";
                                $displayText = htmlspecialchars("$bms_id | $student_code | $full_name | $nic");
                                echo '<option value="' . htmlspecialchars($student_code) . '">' . $displayText . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <!-- Export Options Section -->
                    <div id="exportBtns" class="export-btns-section" style="display:none;">
                        <button class="btn btn-outline-success btn-sm" id="export-all" title="Export All Tables to Excel"><i class="fas fa-file-excel"></i> Export All</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-studentDetails"><i class="fas fa-file-excel"></i> Export Student Details</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-programmeDetails"><i class="fas fa-file-excel"></i> Export Programme Allocation</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-paymentPlanTable"><i class="fas fa-file-excel"></i> Export Payment Plan</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-installmentPayment"><i class="fas fa-file-excel"></i> Export Installment Payment</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-installmentDetails"><i class="fas fa-file-excel"></i> Export Installment Details</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-installmentwithheldTable"><i class="fas fa-file-excel"></i> Export Withheld Table</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-paymentWiseInfoTable"><i class="fas fa-file-excel"></i> Export Payment Wise</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-uniFeePayments"><i class="fas fa-file-excel"></i> Export Uni Fee</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-regFeeDiscount"><i class="fas fa-file-excel"></i> Export Reg Fee Discount</button>
                        <button class="btn btn-outline-primary btn-sm" id="export-paymentPlanHistory"><i class="fas fa-file-excel"></i> Export Plan History</button>
                    </div>
                </div>

                <!-- AJAX Data Containers -->
                <div id="studentDetails" class="mt-4"></div>
                <div id="programmeDetails" class="mt-4"></div>
                <div id="paymentPlanTable" class="mt-4"></div>
                <div id="installmentPayment" class="mt-4"></div>
                <div id="installmentDetails" class="mt-4"></div>
                <div id="installmentwithheldTable" class="mt-4"></div>
                <div id="paymentWiseInfoTable" class="mt-4"></div>
                <div id="uniFeePayments" class="mt-4"></div>
                <div id="regFeeDiscount" class="mt-4"></div>
                <div id="paymentPlanHistory" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<!-- JS Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.19.3/xlsx.full.min.js"></script>
<!-- For export icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<script>
    // Export table to excel utility
    function exportTableToExcel(table, filename = 'export.xlsx') {
        // table: jQuery element or HTML node
        let wb = XLSX.utils.book_new();
        let ws = XLSX.utils.table_to_sheet($(table)[0]);
        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
        XLSX.writeFile(wb, filename);
    }
    // Export all tables as one Excel file, each table in a different sheet
    function exportAllTablesToExcel() {
        let wb = XLSX.utils.book_new();
        const sections = [{
                id: "studentDetails",
                name: "Student Details"
            },
            {
                id: "programmeDetails",
                name: "Programme Allocation"
            },
            {
                id: "paymentPlanTable",
                name: "Payment Plan"
            },
            {
                id: "installmentPayment",
                name: "Installment Payment"
            },
            {
                id: "installmentDetails",
                name: "Installment Details"
            },
            {
                id: "installmentwithheldTable",
                name: "Withheld Table"
            },
            {
                id: "paymentWiseInfoTable",
                name: "Payment Wise"
            },
            {
                id: "uniFeePayments",
                name: "Uni Fee"
            },
            {
                id: "regFeeDiscount",
                name: "Reg Fee Discount"
            },
            {
                id: "paymentPlanHistory",
                name: "Plan History"
            }
        ];
        let foundTable = false;
        sections.forEach(sec => {
            // Pick only the first visible table in each section
            let $wrapper = $("#" + sec.id + " table:visible").first();
            if ($wrapper.length > 0) {
                let ws = XLSX.utils.table_to_sheet($wrapper[0]);
                XLSX.utils.book_append_sheet(wb, ws, sec.name);
                foundTable = true;
            }
        });
        if (foundTable) XLSX.writeFile(wb, "student_payments_all_export.xlsx");
        else alert('No table data available to export.');
    }

    $(document).ready(function() {
        $('#studentDropdown').select2({
            placeholder: "Select Student",
            allowClear: true,
            width: 'resolve'
        });

        // Export button actions
        $("#export-all").click(function() {
            exportAllTablesToExcel();
        });
        $("#export-studentDetails").click(function() {
            let t = $("#studentDetails table:visible").first();
            if (t.length) exportTableToExcel(t, "student_details.xlsx");
            else alert('No data.');
        });
        $("#export-programmeDetails").click(function() {
            let t = $("#programmeDetails table:visible").first();
            if (t.length) exportTableToExcel(t, "programme_allocation.xlsx");
            else alert('No data.');
        });
        $("#export-paymentPlanTable").click(function() {
            let t = $("#paymentPlanTable table:visible").first();
            if (t.length) exportTableToExcel(t, "payment_plan.xlsx");
            else alert('No data.');
        });
        $("#export-installmentPayment").click(function() {
            let t = $("#installmentPayment table:visible").first();
            if (t.length) exportTableToExcel(t, "installment_payment.xlsx");
            else alert('No data.');
        });
        $("#export-installmentDetails").click(function() {
            let t = $("#installmentDetails table:visible").first();
            if (t.length) exportTableToExcel(t, "installment_details.xlsx");
            else alert('No data.');
        });
        $("#export-installmentwithheldTable").click(function() {
            let t = $("#installmentwithheldTable table:visible").first();
            if (t.length) exportTableToExcel(t, "withheld_table.xlsx");
            else alert('No data.');
        });
        $("#export-paymentWiseInfoTable").click(function() {
            let t = $("#paymentWiseInfoTable table:visible").first();
            if (t.length) exportTableToExcel(t, "payment_wise.xlsx");
            else alert('No data.');
        });
        $("#export-uniFeePayments").click(function() {
            let t = $("#uniFeePayments table:visible").first();
            if (t.length) exportTableToExcel(t, "uni_fee_payments.xlsx");
            else alert('No data.');
        });
        $("#export-regFeeDiscount").click(function() {
            let t = $("#regFeeDiscount table:visible").first();
            if (t.length) exportTableToExcel(t, "reg_fee_discount.xlsx");
            else alert('No data.');
        });
        $("#export-paymentPlanHistory").click(function() {
            let t = $("#paymentPlanHistory table:visible").first();
            if (t.length) exportTableToExcel(t, "payment_plan_history.xlsx");
            else alert('No data.');
        });

        $('#studentDropdown').on('change', function() {
            let student_code = $(this).val();

            // Clear sections if no student selected
            if (!student_code) {
                $('#studentDetails, #programmeDetails, #paymentPlanTable, #installmentPayment,#installmentDetails, #installmentwithheldTable, #paymentWiseInfoTable ,#uniFeePayments, #regFeeDiscount, #paymentPlanHistory').html('');
                $("#exportBtns").hide();
                return;
            }
            $("#exportBtns").show();

            // ---- Fetch Payment Plan Table ----
            $.getJSON("for_admin/fetch_payment_plan.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#paymentPlanTable').html("<div class='alert alert-info'>No payment plan found</div>");
                    return;
                }
                let html = `
                    <div class="card shadow">
                        <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Add Payment Plan Table</h3>
                        <div class="card-header fw-bold">Payment Plan Details</div>
                        <div class="card-body">`;
                data.forEach((row, idx) => {
                    html += `<div class="mb-4">
                        <h5 class="text-primary fw-bold">Plan ${idx + 1}</h5>
                        <table class="table table-bordered"><tbody>`;
                    Object.keys(row).forEach(key => {
                        html += `
                            <tr>
                                <th>${key.replace(/_/g, " ").toUpperCase()}</th>
                                <td>${row[key]}</td>
                            </tr>`;
                    });
                    html += "</tbody></table></div>";
                });
                html += "</div></div>";
                $('#paymentPlanTable').html(html);
            });

            // ---- Fetch Student Details ----
            $.getJSON("for_admin/fetch_student_details.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#studentDetails').html("<div class='alert alert-danger'>No student data found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Student Details</h3>    
                    <div class="card-header fw-bold">Student Details</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><th>Student Code</th><td>${data.student_code}</td></tr>
                            <tr><th>Full Name</th><td>${data.title || ''} ${data.first_name || ''} ${data.last_name || ''}</td></tr>
                            <tr><th>Certificate Name</th><td>${data.certificate_name || ''}</td></tr>
                            <tr><th>Preferred Name</th><td>${data.preferred_name || ''}</td></tr>
                            <tr><th>Date of Birth</th><td>${data.date_of_birth || ''}</td></tr>
                            <tr><th>Nationality</th><td>${data.nationality || ''}</td></tr>
                            <tr><th>NIC</th><td>${data.nic || ''}</td></tr>
                            <tr><th>Passport</th><td>${data.passport || ''}</td></tr>
                            <tr><th>Mobile</th><td>${data.mobile || ''}</td></tr>
                            <tr><th>Occupation</th><td>${data.occupation || ''}</td></tr>
                            <tr><th>Qualifications</th><td>${data.qualifications || ''}</td></tr>
                        </table>
                    </div>
                </div>
                `;
                $('#studentDetails').html(html);
            });

            // ---- Fetch Allocated Programmes ----
            $.getJSON("for_admin/fetch_allocate_programme.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#programmeDetails').html("<div class='alert alert-warning'>No program allocation found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Allocated Programs</h3>
                    <div class="card-header fw-bold">Programme Allocation Details</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>BMS Student ID</th>
                                    <th>Programme Name</th>
                                    <th>Batch Name</th>
                                    <th>Compulsory Subjects</th>
                                    <th>Elective Subjects</th>
                                    <th>Status</th>
                                    <th>DM Remark</th>
                                    <th>Entered By</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.forEach(row => {
                    html += `
                    <tr>
                        <td>${row.id}</td>
                        <td>${row.student_registration_id || "N/A"}</td>
                        <td>${row.program_name || ""}</td>
                        <td>${row.batch_name || ""}</td>
                        <td>${row.compulsory_sub || "N/A"}</td>
                        <td>${row.elective_subs || "N/A"}</td>
                        <td>${row.status || ""}</td>
                        <td>${row.dm_remark || ""}</td>
                        <td>${row.entered_by || ""}</td>
                    </tr>`;
                });
                html += `</tbody></table></div></div>`;
                $('#programmeDetails').html(html);
            });

            // ---- Fetch Installment Payments ----
            $.getJSON("for_admin/fetch_installment_payments.php", {
                student_id: student_code
            }, function(data) {
                if (!data || data.status === false || !Array.isArray(data) || data.length === 0) {
                    $('#installmentPayment').html("<div class='alert alert-info'>No installment payment found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Installment Payment Table</h3>

                    <div class="card-header fw-bold">Installment Payment Plan</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Payment Plan ID</th>
                                    <th>Student ID</th>
                                    <th>Programme Batch</th>
                                    <th>Uni Fee Total (LKR)</th>
                                    <th>Uni Fee (LKR)</th>
                                    <th>Uni Fee Total (GBP)</th>
                                    <th>Uni Fee (GBP)</th>
                                    <th>Uni Fee Total (USD)</th>
                                    <th>Uni Fee (USD)</th>
                                    <th>Fee Type</th>
                                    <th>Course Fee Total</th>
                                    <th>Course Fee</th>
                                    <th>Registration Fee</th>
                                    <th>Discount Percentage</th>
                                    <th>Discount Y/N</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.forEach(row => {
                    const numericKeys = [
                        "unifee_lkr_total", "unifee_lkr", "unifee_gbp_total", "unifee_gbp", "unifee_usd_total", "unifee_usd",
                        "coursefee_total", "coursefee", "registrationfee", "discounted_percentage"
                    ];
                    let showRow = false;
                    for (let k of numericKeys) {
                        let v = Number(row[k]);
                        if (!isNaN(v) && v > 0) {
                            showRow = true;
                            break;
                        }
                    }
                    if (!showRow) return;
                    html += `
                    <tr>
                        <td>${row.id ?? ""}</td>
                        <td>${row.payment_plans_tb_id ?? ""}</td>
                        <td>${row.student_id ?? ""}</td>
                        <td>${row.programme_batch ?? ""}</td>
                        <td>${Number(row.unifee_lkr_total) > 0 ? row.unifee_lkr_total : ""}</td>
                        <td>${Number(row.unifee_lkr) > 0 ? row.unifee_lkr : ""}</td>
                        <td>${Number(row.unifee_gbp_total) > 0 ? row.unifee_gbp_total : ""}</td>
                        <td>${Number(row.unifee_gbp) > 0 ? row.unifee_gbp : ""}</td>
                        <td>${Number(row.unifee_usd_total) > 0 ? row.unifee_usd_total : ""}</td>
                        <td>${Number(row.unifee_usd) > 0 ? row.unifee_usd : ""}</td>
                        <td>${row.fee_type ?? ""}</td>
                        <td>${Number(row.coursefee_total) > 0 ? row.coursefee_total : ""}</td>
                        <td>${Number(row.coursefee) > 0 ? row.coursefee : ""}</td>
                        <td>${Number(row.registrationfee) > 0 ? row.registrationfee : ""}</td>
                        <td>${Number(row.discounted_percentage) > 0 ? row.discounted_percentage : ""}</td>
                        <td>${row.dis_yes_no ?? ""}</td>
                    </tr>`;
                });
                html += `</tbody></table></div></div>`;
                $('#installmentPayment').html(html);
            });

            // ---- Fetch Payment Withheld ----
            $.getJSON("for_admin/fetch_payment_withheld.php", {
                student_code
            }, function(data) {
                if (!Array.isArray(data) || data.length === 0) {
                    $('#installmentwithheldTable').html("<div class='alert alert-info'>No payment withheld records found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Withheld Table</h3>
                    <div class="card-header fw-bold">Payment Withheld Details</div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student Code</th>
                                    <th>BMS Reg ID</th>
                                    <th>Programme ID</th>
                                    <th>Batch ID</th>
                                    <th>Payment Status</th>
                                    <th>Due BMS Fees</th>
                                    <th>Due Uni Fees</th>
                                    <th>Last Payment Date</th>
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.forEach(row => {
                    html += `
                    <tr>
                        <td>${row.id || ""}</td>
                        <td>${row.student_code || ""}</td>
                        <td>${row.student_registration_id || ""}</td>
                        <td>${row.program_name || ""}</td>
                        <td>${row.batch_name || ""}</td>
                        <td>${row.payment_status || ""}</td>
                        <td>${row.due_count_bms_fees || ""}</td>
                        <td>${row.due_count_uni_fees || ""}</td>
                        <td>${row.last_payment_date || ""}</td>
                        <td>${row.created_at || ""}</td>
                        <td>${row.updated_at || ""}</td>
                    </tr>`;
                });
                html += `</tbody></table></div></div>`;
                $('#installmentwithheldTable').html(html);
            }).fail(function() {
                $('#installmentwithheldTable').html("<div class='alert alert-danger'>Error fetching payment withheld data</div>");
            });

            // ---- Installment Details ----
            $.getJSON("for_admin/fetch_installment_details.php", {
                student_code: student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#installmentDetails').html("<div class='alert alert-info'>No installment details found</div>");
                    return;
                }
                let html = `<div class="card shadow">
                    <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Installment  Details Table</h3>
                        <div class="card-header fw-bold">Installment  Details </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ID</th>
                                        <th>Payment Plan Course Fee</th>
                                        <th>Fee Type</th>
                                        <th>Registration Fee</th>
                                        <th>Installment Numbers</th>
                                        <th>Devided Values</th>
                                        <th>Installment Amount</th>
                                        <th>Discount Type</th>
                                        <th>Discount Value</th>
                                        <th>Due Date</th>
                                        <th>Remark</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                data.forEach(row => {
                    html += `<tr>
                        <td>${row.id}</td>
                        <td>${row.programme_batch}</td>
                        <td>${row.payment_plan_course_fee || "0.00"}</td>
                        <td>${row.payment_plan_fee_type || "N/A"}</td>
                        <td>${row.payment_plan_registration_fee || "0.00"}</td>
                        <td>${row.installment_numbers || "N/A"}</td>
                        <td>${row.devided_values || "0.00"}</td>
                        <td>${row.installment_amount || "0.00"}</td>
                        <td>${row.discount_type || "N/A"}</td>
                        <td>${row.discount_value || "0"}</td>
                        <td>${row.due_date || ""}</td>
                        <td>${row.remark || ""}</td>
                        <td>${row.updated_by || ""}</td>
                    </tr>`;
                });
                html += `</tbody></table></div></div>`;
                $('#installmentDetails').html(html);
            });

            // ---- Payment Wise Info Table ----
            $.getJSON("for_admin/fetch_payment_wise_info.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#paymentWiseInfoTable').html("<div class='alert alert-info'>No payment-wise payments found</div>");
                    return;
                }
                let html = `
                    <div class="card shadow">
                        <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Payment Wise Data</h3>
                        <div class="card-header fw-bold">Payment Wise Information</div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>`;
                if (data.length > 0) {
                    Object.keys(data[0]).forEach(key => {
                        html += `<th>${key.replace(/_/g, " ").toUpperCase()}</th>`;
                    });
                }
                html += `</tr></thead><tbody>`;
                data.forEach(row => {
                    html += "<tr>";
                    Object.keys(row).forEach(key => {
                        html += `<td>${row[key]}</td>`;
                    });
                    html += "</tr>";
                });
                html += "</tbody></table></div></div>";
                $('#paymentWiseInfoTable').html(html);
            });

            // ---- Uni Fee Payments ----
            $.getJSON("for_admin/fetch_payment_uni_fee.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#uniFeePayments').html("<div class='alert alert-info'>No university fee payments found</div>");
                    return;
                }
                let html = `
                    <div class="card shadow">
                        <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Uni Payment Data</h3>
                        <div class="card-header fw-bold">University Fee Payments</div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>`;
                if (data.length > 0) {
                    Object.keys(data[0]).forEach(key => {
                        html += `<th>${key.replace(/_/g, " ").toUpperCase()}</th>`;
                    });
                }
                html += `</tr></thead><tbody>`;
                data.forEach(row => {
                    html += "<tr>";
                    Object.keys(row).forEach(key => {
                        html += `<td>${row[key] || ""}</td>`;
                    });
                    html += "</tr>";
                });
                html += "</tbody></table></div></div>";
                $('#uniFeePayments').html(html);
            });

            // ---- Registration Fee Discount ----
            $.getJSON("for_admin/fetch_payment_plan_regfee_discount.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#regFeeDiscount').html("<div class='alert alert-info'>No registration fee discount data found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                    <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Registration Fee Discount Data</h3>
                    <div class="card-header fw-bold">Registration Fee Discount Details</div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Program Name</th>
                                    <th>Batch Name</th>
                                    <th>Discount Type</th>
                                    <th>Discount Value</th>
                                    <th>Remarks</th>
                                    <th>Created At</th>
                                    <th>Updated By</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.forEach(row => {
                    html += `
                    <tr>
                        <td>${row.id || ""}</td>
                        <td>${row.student_id || ""}</td>
                        <td>${row.student_name || ""}</td>
                        <td>${row.program_name || ""}</td>
                        <td>${row.batch_name || ""}</td>
                        <td>${row.d_type || ""}</td>
                        <td>${row.discount_value || ""}</td>
                        <td>${row.remarks || ""}</td>
                        <td>${row.created_at || ""}</td>
                        <td>${row.updated_by || ""}</td>
                    </tr>`;
                });
                html += "</tbody></table></div></div>";
                $('#regFeeDiscount').html(html);
            });

            // ---- Payment Plan History ----
            $.getJSON("for_admin/fetch_payment_plan_history.php", {
                student_code
            }, function(data) {
                if (!data || data.status === false) {
                    $('#paymentPlanHistory').html("<div class='alert alert-info'>No payment plan history found</div>");
                    return;
                }
                let html = `
                <div class="card shadow">
                    <h3 class="text-danger fw-bolder text-center mt-3 mb-3">Installment Fee Discount Data</h3>
                    <div class="card-header fw-bold">Payment Plan History</div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Program Name</th>
                                    <th>Batch Name</th>
                                    <th>Discount Type</th>
                                    <th>Discount Value</th>
                                    <th>Created At</th>
                                    <th>Updated By</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                data.forEach(row => {
                    html += `
                    <tr>
                        <td>${row.id || ""}</td>
                        <td>${row.student_id || ""}</td>
                        <td>${row.student_name || ""}</td>
                        <td>${row.program_name || ""}</td>
                        <td>${row.batch_name || ""}</td>
                        <td>${row.discount_type || ""}</td>
                        <td>${row.discount_value || ""}</td>
                        <td>${row.created_at || ""}</td>
                        <td>${row.updated_by || ""}</td>
                        <td>${row.re_marks || ""}</td>
                    </tr>`;
                });
                html += "</tbody></table></div></div>";
                $('#paymentPlanHistory').html(html);
            });
        }); // end on change
    }); // end ready
</script>

<?php include("includes/footer.php"); ?>