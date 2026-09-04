<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
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
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Offer Letter Generation</h4>
                </div>

                <!-- Offer Letter Form and Preview -->
                <div class="row mb-5">
                    <!-- Form Column -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-file-alt"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Generate Offer Letter</h6>
                            </div>
                            <div class="card-body">
                                <form id="offerLetterForm" method="post" action="process_offer_letter.php">
                                    <div class="row">
                                        <!-- Student Selection -->
                                        <div class="col-md-12 mb-3">
                                            <label for="student" class="form-label">Student:</label>
                                            <select id="student" name="student" class="form-select select2" required>
                                                <option value="">Select Student...</option>
                                                <?php
                                                // Fetch active students from allocate_programme table
                                                $query = "SELECT a.id, a.student_code, a.student_registration_id, s.first_name, s.last_name, s.nic 
                                                          FROM allocate_programme a 
                                                          JOIN students s ON a.student_code = s.student_code 
                                                          WHERE a.status = 'active'";
                                                $result = mysqli_query($conn, $query);

                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . $row['student_code'] . '" 
                                                                data-reg-id="' . $row['student_registration_id'] . '"
                                                                data-nic="' . $row['nic'] . '">
                                                                ' . $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['student_registration_id'] . ')
                                                              </option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Student NIC -->
                                        <div class="col-md-12 mb-3">
                                            <label for="studentNIC" class="form-label">Student NIC:</label>
                                            <input type="text" id="studentNIC" name="studentNIC" class="form-control" readonly>
                                        </div>

                                        <!-- Program Selection -->
                                        <div class="col-md-12 mb-3">
                                            <label for="program" class="form-label">Programme:</label>
                                            <select id="program" name="program" class="form-select select2" required>
                                                <option value="">Select Programme...</option>
                                                <?php
                                                // Fetch all programs from program_table
                                                // $query = "SELECT program_code, program_name, duration, 
                                                //           course_fee_lkr, course_fee_gbp, course_fee_usd, course_fee_euro 
                                                //           FROM program_table";

                                                // $result = mysqli_query($conn, $query);

                                                // if ($result && mysqli_num_rows($result) > 0) {
                                                //     while ($row = mysqli_fetch_assoc($result)) {
                                                //         echo '<option value="' . $row['program_code'] . '" 
                                                //                 data-duration="' . $row['duration'] . '"
                                                //                 data-fee-lkr="' . $row['course_fee_lkr'] . '"
                                                //                 data-fee-gbp="' . $row['course_fee_gbp'] . '"
                                                //                 data-fee-usd="' . $row['course_fee_usd'] . '"
                                                //                 data-fee-euro="' . $row['course_fee_euro'] . '"
                                                //                 data-name="' . $row['program_name'] . '">
                                                //                 ' . $row['program_name'] . '
                                                //               </option>';
                                                //     }
                                                // }



                                                $user_id = $_SESSION['user_id'] ?? 0;
                                                $role = $_SESSION['role'] ?? '';

                                                if ($role === 'super_admin') {
                                                    // Super admin: get all programs
                                                    $query = "
                                                    SELECT program_code, program_name, duration, 
                                                        course_fee_lkr, course_fee_gbp, course_fee_usd, course_fee_euro
                                                    FROM program_table
                                                    ORDER BY program_name
                                                ";
                                                    $result = mysqli_query($conn, $query);
                                                } else {
                                                    // Other users: only allocated programs
                                                    $query = "
                                                    SELECT pt.program_code, pt.program_name, pt.duration, 
                                                        pt.course_fee_lkr, pt.course_fee_gbp, pt.course_fee_usd, pt.course_fee_euro
                                                    FROM program_allocation_user AS pau
                                                    INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
                                                    WHERE pau.user_id = $user_id
                                                    ORDER BY pt.program_name
                                                ";
                                                    $result = mysqli_query($conn, $query);
                                                }

                                                // Output <option> tags with data attributes
                                                if ($result && mysqli_num_rows($result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo '<option value="' . $row['program_code'] . '" 
                                                        data-duration="' . $row['duration'] . '"
                                                        data-fee-lkr="' . $row['course_fee_lkr'] . '"
                                                        data-fee-gbp="' . $row['course_fee_gbp'] . '"
                                                        data-fee-usd="' . $row['course_fee_usd'] . '"
                                                        data-fee-euro="' . $row['course_fee_euro'] . '"
                                                        data-name="' . htmlspecialchars($row['program_name'], ENT_QUOTES) . '">
                                                        ' . htmlspecialchars($row['program_name']) . '
                                                    </option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Batch Selection -->
                                        <div class="col-md-12 mb-3">
                                            <label for="batch" class="form-label">Batch:</label>
                                            <select id="batch" name="batch" class="form-select select2" required>
                                                <option value="">Select Batch...</option>
                                                <!-- Batches will be populated via JavaScript -->
                                            </select>
                                        </div>

                                        <!-- Duration -->
                                        <div class="col-md-12 mb-3">
                                            <label for="duration" class="form-label">Duration:</label>
                                            <input type="text" id="duration" name="duration" class="form-control" readonly>
                                        </div>

                                        <!-- Programme Fee -->
                                        <div class="col-md-12 mb-3">
                                            <label for="programmeFee" class="form-label">Programme Fee:</label>
                                            <textarea id="programmeFee" name="programmeFee" class="form-control" readonly rows="4"></textarea>
                                        </div>

                                        <!-- Minimum Payment -->
                                        <div class="col-md-12 mb-3">
                                            <label for="minimumPayment" class="form-label">Minimum Payment (LKR):</label>
                                            <input type="number" id="minimumPayment" name="minimumPayment" class="form-control" value="" required>
                                        </div>

                                        <!-- Programme Start Date -->
                                        <div class="col-md-12 mb-3">
                                            <label for="startDate" class="form-label">Programme Start Date:</label>
                                            <input type="text" id="startDate" name="startDate" class="form-control" readonly>
                                        </div>

                                        <!-- Programme End Date -->
                                        <div class="col-md-12 mb-3">
                                            <label for="endDate" class="form-label">Programme End Date:</label>
                                            <input type="text" id="endDate" name="endDate" class="form-control" readonly>
                                        </div>

                                        <!-- Attendance Mode -->
                                        <div class="col-md-12 mb-3">
                                            <label for="attendanceMode" class="form-label">Attendance:</label>
                                            <select id="attendanceMode" name="attendanceMode" class="form-select select2" required>
                                                <option value="">Select...</option>
                                                <option value="Full time">Full time</option>
                                                <option value="Part time">Part time</option>
                                            </select>
                                        </div>

                                        <!-- Offer Letter Deadline -->
                                        <div class="col-md-12 mb-3">
                                            <label for="deadline" class="form-label">Offer Letter Deadline:</label>
                                            <input type="date" id="deadline" name="deadline" class="form-control" required>
                                        </div>

                                        <!-- Awarded By -->
                                        <div class="col-md-12 mb-3">
                                            <label for="awardedBy" class="form-label">Awarded By:</label>
                                            <select id="awardedBy" name="awardedBy" class="form-select select2" required>
                                                <option value="">Select...</option>
                                                <option value="Pearson BTEC">Pearson BTEC</option>
                                                <option value="BMS">BMS</option>
                                            </select>
                                        </div>

                                        <!-- Accredited By -->
                                        <div class="col-md-12 mb-3">
                                            <label for="accreditedBy" class="form-label">Accredited By / Assured by / Recognized By:</label>
                                            <select id="accreditedBy" name="accreditedBy" class="form-select select2" required>
                                                <option value="">Select...</option>
                                                <option value="Pearson, UK">Pearson, UK</option>
                                                <option value="Institute of Biomedical Science, UK">Institute of Biomedical Science, UK</option>
                                                <option value="Royal Society of Biology, UK">Royal Society of Biology, UK</option>
                                                <option value="CMI, UK">CMI, UK</option>
                                            </select>
                                        </div>

                                        <!-- Student Email -->
                                        <div class="col-md-12 mb-3" style="display: none;">
                                            <label for="studentEmail" class="form-label">Student Email:</label>
                                            <input type="email" id="studentEmail" name="studentEmail" class="form-control" readonly>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Column -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between" style="height: 60px;">
                                <div class="d-flex align-items-center">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-eye"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Offer Letter Preview</h6>
                                </div>
                                <div>
                                    <button id="emailOfferLetter" type="button" class="btn btn-sm btn-success me-2">
                                        <i class="fas fa-envelope"></i> Email Offer Letter
                                    </button>
                                    <button id="printOfferLetter" type="button" class="btn btn-sm btn-primary">
                                        <i class="fas fa-print"></i> Print Offer Letter
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="offerLetterPreview" class="a4-preview">
                                    <div class="preview-content">
                                        <div class="letterhead">
                                            <div class="logo-container">
                                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRGkKtuN4jwLcLnigNTh1sGRqxzyEdf_HGDow&s" alt="BMS Logo" class="logo">
                                            </div>
                                            <div class="text-container">
                                                <p>Business Management School</p>
                                                <p>591, Galle Road, Colombo 06, Sri Lanka</p>
                                                <p>Tel: +94 xxxxxxxxx | Email: info@bms.edu.lk</p>
                                            </div>
                                        </div>
                                        <hr>

                                        <div class="letter-date">
                                            <p>Date: <span id="currentDate"><?php echo date('d F Y'); ?></span></p>
                                        </div>

                                        <div class="student-address">
                                            <p id="previewStudentName"></p>
                                        </div>

                                        <div class="letter-subject">
                                            <h5 class="small-font">OFFER LETTER FOR <span id="previewProgramme"></span> </h5>
                                        </div>

                                        <div class="letter-body">
                                            <p>Dear <span id="previewStudentFirstName"></span>,</p>

                                            <p>We are pleased to confirm that Business Management School has accepted your
                                                application unconditionally onto the above programme. Details of the course are
                                                specified below.</p>

                                            <div class="programme-details">
                                                <table class="details-table">

                                                    <tr>
                                                        <td>Duration:</td>
                                                        <td id="previewDuration"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Start Date:</td>
                                                        <td id="previewStartDate"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>End Date:</td>
                                                        <td id="previewEndDate"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Attendance:</td>
                                                        <td id="previewAttendance"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Awarded By:</td>
                                                        <td id="previewAwardedBy"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Accredited By:</td>
                                                        <td id="previewAccreditedBy"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Programme Fee:</td>
                                                        <td id="previewFee"></td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <p>A minimum payment of LKR <span id="previewMinimumPayment"></span> is required to confirm a place on the programme.
                                                Fees can be paid in full at the time of your enrolment; alternatively, you can obtain a
                                                payment plan from BMS Finance department.</p>

                                            <p class="mt-3">
                                                This offer has been issued in accordance with the regulations set out by Business
                                                Management School and is subject to the terms and conditions agreed and stipulated
                                                in the relevant documents.
                                            </p>

                                            <p class="mt-3">You shall accept the offer on or before <span id="previewDeadline"></span> (DEADLINE) with the payment of
                                                the first instalment.
                                            </p>

                                            <div class="mt-4">
                                                <p>With best wishes, </p>
                                                <p class="fw-bolder">Academic Registrar, BMS</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Add custom CSS for A4 preview -->
<style>
    .a4-preview {
        width: 100%;
        height: 842px;
        /* A4 height in pixels at 96 DPI */
        overflow-y: auto;
        background-color: white;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.5;
        position: relative;
    }

    .preview-content {
        max-width: 100%;
    }

    .letterhead {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 10px;
    }

    .logo-container {
        flex: 0 0 auto;
        /* Prevent the logo from growing */
    }

    .text-container {
        flex: 1;
        /* Allow the text to take the remaining space */
        text-align: right;
        /* Align text to the right */
    }

    .logo {
        max-width: 100px;
        /* Adjust logo size as needed */
        height: auto;
        /* Maintain aspect ratio */
    }

    .letterhead h2 {
        margin: 5px 0;
        color: #003366;
    }

    .letterhead p {
        margin: 2px 0;
        font-size: 11px;
    }

    .letter-date,
    .letter-ref {
        text-align: left;
        margin-bottom: 10px;
    }

    .student-address {
        margin-bottom: 20px;
    }

    .letter-subject {
        text-align: center;
        margin: 20px 0;
    }

    .letter-subject h4 {
        margin: 0;
        text-decoration: underline;
        font-weight: bold;
    }

    .letter-body p {
        margin-bottom: 10px;
        text-align: justify;
    }

    .programme-details {
        margin: 15px 0;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
    }

    .details-table td {
        padding: 5px;
        border: 1px solid #ddd;
    }

    .details-table td:first-child {
        font-weight: bold;
        width: 30%;
    }

    .signature {
        margin-top: 30px;
    }

    .signature-img {
        max-width: 150px;
        height: auto;
        margin: 10px 0;
    }

    .select2-container {
        width: 100% !important;
    }

    .small-font {
        font-size: 14px;
        /* Adjust this value as needed */
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Add Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Add html2canvas and jsPDF libraries from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            width: '100%',
            placeholder: 'Select an option',
            allowClear: true
        });

        // Function to update preview
        function updatePreview() {
            // Get student details
            var studentSelect = $('#student');
            var selectedStudent = studentSelect.find('option:selected');
            var studentName = selectedStudent.text();

            if (studentName && studentName !== 'Select Student...') {
                var nameParts = studentName.split(' (');
                var fullName = nameParts[0];
                var firstName = fullName.split(' ')[0];

                // Set the first name immediately
                $('#previewStudentFirstName').text(firstName);
                $('#previewRefNo').text('BMS/OL/2025/' + (selectedStudent.val() || ''));

                // Fetch student details including current address
                var studentId = selectedStudent.val();
                if (studentId) {
                    $.ajax({
                        url: 'get_student_details.php',
                        type: 'POST',
                        data: {
                            student_id: studentId
                        },
                        dataType: 'json',
                        success: function(data) {
                            // Update the first name if available in the database
                            if (data.first_name) {
                                $('#previewStudentFirstName').text(data.first_name);
                            }

                            if (data.current_address) {
                                $('#previewStudentName').html(fullName + '<br>' + data.current_address);
                            } else {
                                $('#previewStudentName').text(fullName);
                            }

                            // Set the student email if available
                            if (data.email) {
                                $('#studentEmail').val(data.email);
                            }
                        },
                        error: function() {
                            $('#previewStudentName').text(fullName);
                        }
                    });
                } else {
                    $('#previewStudentName').text(fullName);
                    $('#studentEmail').val('');
                }
            } else {
                $('#previewStudentName').text('');
                $('#previewStudentFirstName').text('');
                $('#previewRefNo').text('BMS/OL/2025/');
                $('#studentEmail').val('');
            }

            $('#previewStudentNIC').text($('#studentNIC').val() || '');

            // Get program details
            var programSelect = $('#program');
            var selectedProgram = programSelect.find('option:selected');
            var programName = selectedProgram.data('name') || '';

            $('#previewProgramme').text(programName);

            // Get batch details
            var batchSelect = $('#batch');
            var selectedBatch = batchSelect.find('option:selected');
            var batchName = selectedBatch.text();

            if (batchName && batchName !== 'Select Batch...') {
                $('#previewBatch').text(batchName);
            } else {
                $('#previewBatch').text('');
            }

            // Get other details
            $('#previewDuration').text($('#duration').val() || '');
            $('#previewAttendance').text($('#attendanceMode').val() || '');
            $('#previewStartDate').text($('#startDate').val() || '');
            $('#previewEndDate').text($('#endDate').val() || '');

            // Format fee for preview
            var feeText = $('#programmeFee').val();
            $('#previewFee').html(feeText ? feeText.replace(/\n/g, '<br>') : '');

            $('#previewAwardedBy').text($('#awardedBy').val() || '');
            $('#previewAccreditedBy').text($('#accreditedBy').val() || '');

            // Format deadline date
            var deadlineInput = $('#deadline').val();
            if (deadlineInput) {
                var deadlineDate = new Date(deadlineInput);
                var options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                $('#previewDeadline').text(deadlineDate.toLocaleDateString('en-US', options));
            } else {
                $('#previewDeadline').text('');
            }

            // Format minimum payment for preview
            var minimumPayment = $('#minimumPayment').val();
            if (minimumPayment) {
                // Format the number with commas
                var formattedPayment = new Intl.NumberFormat('en-US').format(minimumPayment);
                $('#previewMinimumPayment').text(formattedPayment);
            } else {
                $('#previewMinimumPayment').text('');
            }
        }

        // When student is selected, populate the NIC field and update preview
        $('#student').change(function() {
            var selectedOption = $(this).find('option:selected');
            var nic = selectedOption.data('nic');
            $('#studentNIC').val(nic);

            // Also try to fetch student email
            var studentId = $(this).val();


            updatePreview();
        });

        // When program is selected, populate duration and fees
        $('#program').change(function() {
            var selectedOption = $(this).find('option:selected');
            var duration = selectedOption.data('duration');
            var feeLKR = parseFloat(selectedOption.data('fee-lkr'));
            var feeGBP = parseFloat(selectedOption.data('fee-gbp'));
            var feeUSD = parseFloat(selectedOption.data('fee-usd'));
            var feeEURO = parseFloat(selectedOption.data('fee-euro'));

            $('#duration').val(duration);

            // Build the fee text with all available currencies
            var feeText = '';
            if (feeLKR > 0) {
                feeText += 'LKR ' + feeLKR.toFixed(2) + '\n';
            }
            if (feeGBP > 0) {
                feeText += 'GBP ' + feeGBP.toFixed(2) + '\n';
            }
            if (feeUSD > 0) {
                feeText += 'USD ' + feeUSD.toFixed(2) + '\n';
            }
            if (feeEURO > 0) {
                feeText += 'EUR ' + feeEURO.toFixed(2);
            }

            $('#programmeFee').val(feeText);

            // Clear batch dropdown and related fields
            $('#batch').html('<option value="">Select Batch...</option>');
            $('#startDate').val('');
            $('#endDate').val('');

            // Fetch batches for the selected program
            var programId = $(this).val();
            if (programId) {
                $.ajax({
                    url: 'get_batches.php',
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.length > 0) {
                            $.each(data, function(index, batch) {
                                $('#batch').append('<option value="' + batch.id + '" ' +
                                    'data-start="' + batch.intake_date + '" ' +
                                    'data-end="' + batch.end_date + '">' +
                                    batch.batch_name + '</option>');
                            });
                            // Refresh Select2 to show new options
                            $('#batch').trigger('change');
                        }
                    }
                });
            }
            updatePreview();
        });

        // When batch is selected, populate start and end dates
        $('#batch').change(function() {
            var selectedOption = $(this).find('option:selected');
            var startDate = selectedOption.data('start');
            var endDate = selectedOption.data('end');

            $('#startDate').val(startDate);
            $('#endDate').val(endDate);

            updatePreview();
        });

        // Update preview when any form field changes
        $('#attendanceMode, #deadline, #awardedBy, #accreditedBy').change(function() {
            updatePreview();
        });

        // Update preview when minimum payment changes
        $('#minimumPayment').on('input', function() {
            updatePreview();
        });

        // Print functionality
        $('#printOfferLetter').click(function() {
            // Create a new window for printing
            const printWindow = window.open('', '_blank');

            // Get the offer letter content
            const letterContent = document.getElementById('offerLetterPreview').innerHTML;

            // Create the print document with proper styling
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>BMS Offer Letter</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 0;
                            
                        }
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif;
                            font-size: 12pt;
                            line-height: 20.0pt;
                        }
                        .a4-preview {
                            width: 210mm;
                            min-height: 297mm;
                            padding: 20mm;
                            margin: 0 auto;
                            background-color: white;
                        }
                        .preview-content {
                            max-width: 100%;
                        }
                        .letterhead {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            margin-bottom: 20px;
                            border-bottom: 1px solid #ccc;
                            padding-bottom: 10px;
                        }
                        .logo-container {
                            flex: 0 0 auto;
                        }
                        .text-container {
                            flex: 1;
                            text-align: right;
                           
                        }
                        .logo {
                            max-width: 100px;
                            height: auto;
                        }
                        .letterhead p {
                            margin: 2px 0;
                            font-size: 15px;
                        }
                        .letter-date,
                        .letter-ref {
                            text-align: left;
                            margin-bottom: 10px;
                        }
                        .student-address {
                            margin-bottom: 20px;
                        }
                        .letter-subject {
                            text-align: center;
                            margin: 20px 0;
                        }
                        .letter-subject h5 {
                            margin: 0;
                            text-decoration: underline;
                            font-weight: bold;
                        }
                        .letter-body p {
                            margin-bottom: 10px;
                            text-align: justify;
                        }
                        .programme-details {
                            margin: 15px 0;
                        }
                        .details-table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        .details-table td {
                            padding: 5px;
                            border: 1px solid #ddd;
                        }
                        .details-table td:first-child {
                            font-weight: bold;
                            width: 30%;
                        }
                        .signature {
                            margin-top: 30px;
                        }
                        .signature-img {
                            max-width: 150px;
                            height: auto;
                            margin: 10px 0;
                        }
                        @media print {
                            body {
                                -webkit-print-color-adjust: exact;
                                print-color-adjust: exact;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="a4-preview">
                        ${letterContent}
                    </div>
                </body>
                </html>
            `);

            // Wait for content to load then print
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
            };
        });

        // Email functionality with PDF generation
        $('#emailOfferLetter').click(function() {
            // Check if all required fields are filled
            let isValid = true;
            let emptyFields = [];

            // Check all required fields
            $('#offerLetterForm').find('select, input, textarea').each(function() {
                if ($(this).prop('required') && !$(this).val()) {
                    isValid = false;
                    let fieldName = $(this).prev('label').text().replace(':', '').trim();
                    emptyFields.push(fieldName);
                }
            });

            // If any required field is empty, show error message
            if (!isValid) {
                alert('Please fill in all required fields:\n\n' + emptyFields.join('\n'));
                return;
            }

            // Get the student email
            const studentEmail = $('#studentEmail').val();

            if (!studentEmail) {
                alert('No student email available. Please select a student with a valid email address.');
                return;
            }

            // Get the student name
            const studentName = $('#previewStudentName').text().split('\n')[0];

            // Get the program name
            const programName = $('#previewProgramme').text();

            // Show loading state
            const originalButtonText = $(this).html();
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Generating PDF...');
            $(this).prop('disabled', true);

            // Get the offer letter content element
            const offerLetterElement = document.getElementById('offerLetterPreview');

            // Generate PDF using html2canvas and jsPDF
            html2canvas(offerLetterElement, {
                scale: 1.5, // Reduced scale from 2 to 1.5 for better fit
                useCORS: true,
                logging: false,
                letterRendering: true,
                x: 20,
                y: 10,
                width: offerLetterElement.offsetWidth - 40,
                height: offerLetterElement.offsetHeight - 20
            }).then(function(canvas) {
                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');

                // Calculate the width and height to maintain aspect ratio
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 297; // A4 height in mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                // Add the image to the PDF with proper scaling
                const imgData = canvas.toDataURL('image/png');

                // Calculate scaling factor to fit on one page
                const scaleFactor = Math.min(
                    (imgWidth - 40) / canvas.width, // Width scaling
                    (pageHeight - 20) / canvas.height // Height scaling
                );

                const scaledWidth = canvas.width * scaleFactor;
                const scaledHeight = canvas.height * scaleFactor;

                // Center the content on the page
                const xOffset = (imgWidth - scaledWidth) / 2;
                const yOffset = (pageHeight - scaledHeight) / 2;

                // Add the image to the PDF with proper scaling and centering
                pdf.addImage(imgData, 'PNG', xOffset, yOffset, scaledWidth, scaledHeight);

                // Convert PDF to base64 string
                const pdfBase64 = pdf.output('datauristring').split(',')[1];

                // Update button text
                $('#emailOfferLetter').html('<i class="fas fa-spinner fa-spin"></i> Sending Email...');

                // Send the email with PDF attachment using AJAX
                $.ajax({
                    url: 'send_offer_letter_email.php',
                    type: 'POST',
                    data: {
                        email: studentEmail,
                        name: studentName,
                        program: programName,
                        content: offerLetterElement.innerHTML,
                        pdf_data: pdfBase64,
                        pdf_name: 'BMS_Offer_Letter_' + studentName.replace(/\s+/g, '_') + '.pdf'
                    },
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert('Offer letter has been sent to ' + studentEmail + ' with PDF attachment');
                            } else {
                                alert('Failed to send email: ' + result.message);
                            }
                        } catch (e) {
                            alert('An error occurred while sending the email.');
                            console.error(response);
                        }
                    },
                    error: function() {
                        alert('An error occurred while sending the email.');
                    },
                    complete: function() {
                        // Restore button state
                        $('#emailOfferLetter').html(originalButtonText);
                        $('#emailOfferLetter').prop('disabled', false);
                    }
                });
            });
        });

        // Form submission handler
        $('#offerLetterForm').on('submit', function(e) {
            e.preventDefault();

            let isValid = true;
            let emptyFields = [];

            // Check all required fields
            $(this).find('select, input, textarea').each(function() {
                if ($(this).prop('required') && !$(this).val()) {
                    isValid = false;
                    let fieldName = $(this).prev('label').text().replace(':', '').trim();
                    emptyFields.push(fieldName);
                }
            });

            // If any required field is empty, show error message
            if (!isValid) {
                alert('Please fill in all required fields:\n\n' + emptyFields.join('\n'));
                return false;
            }

            // If all fields are valid, submit the form
            this.submit();
        });

        // Add required attribute to all necessary fields
        $('#studentNIC, #duration, #programmeFee, #startDate, #endDate, #studentEmail').prop('required', true);

        // Initialize preview
        updatePreview();
    });
</script>

</body>

</html>