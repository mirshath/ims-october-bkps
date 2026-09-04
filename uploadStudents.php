<?php
session_start();
include("database/connection.php");
include("includes/header.php");
$Session_username = $_SESSION['username'];


$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? '';


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


// Fetch all programs
// $programs_query = "SELECT * FROM program_table";
// $programs_result = mysqli_query($conn, $programs_query);
// $programs = mysqli_fetch_all($programs_result, MYSQLI_ASSOC);


if ($role === 'super_admin') {
    // Super admin: get all programs
    $programs_query = "SELECT * FROM program_table ORDER BY program_name";
} else {
    // Other users: only allocated programs
    $programs_query = "
        SELECT pt.*
        FROM program_allocation_user AS pau
        INNER JOIN program_table AS pt ON pau.program_code = pt.program_code
        WHERE pau.user_id = $user_id
        ORDER BY pt.program_name
    ";
}

$programs_result = mysqli_query($conn, $programs_query);
$programs = mysqli_fetch_all($programs_result, MYSQLI_ASSOC);



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
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Upload Student</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Allocate Programs</h6>
                            </div>

                            <div class="card-body">
                                <form action="process_upload.php" method="post" enctype="multipart/form-data" class="mb-3">

                                    <div class="form-group">
                                        <div class="form-check">
                                            <input type="checkbox" id="allocate" class="form-check-input">
                                            <label for="allocate" class="form-check-label">Allocate</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="program">Program:</label>
                                        <select id="program" name="program" class="form-control select2" required disabled>
                                            <option value="">Select Program</option>
                                            <?php foreach ($programs as $program): ?>
                                                <option value="<?= $program['program_code']; ?>" data-university-id="<?= $program['university_id']; ?>">
                                                    <?= $program['program_name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="batch">Batch:</label>
                                        <select id="batch" name="batch" class="form-control select2" required disabled>
                                            <option value="">Select Batch</option>
                                        </select>
                                    </div>

                                    <!-- Modules Section with Two Columns -->
                                    <div id="modules-section" class="form-group" style="display: none;">
                                        <label>Modules:</label>
                                        <div class="row">
                                            <!-- Compulsory Modules (Left Side) -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0">Compulsory Modules</h6>
                                                        <div class="form-check">
                                                            <input type="checkbox" id="select-all-compulsory" class="form-check-input">
                                                            <label for="select-all-compulsory" class="form-check-label text-white">Select All</label>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                                        <div id="compulsory-modules-container">
                                                            <div class="text-center text-muted">
                                                                <i>Select a program to view modules</i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Elective Modules (Right Side) -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0">Elective Modules</h6>
                                                        <div class="form-check">
                                                            <input type="checkbox" id="select-all-elective" class="form-check-input">
                                                            <label for="select-all-elective" class="form-check-label text-white">Select All</label>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                                                        <div id="elective-modules-container">
                                                            <div class="text-center text-muted">
                                                                <i>Select a program to view modules</i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="form-group mt-3">
                                        <label for="csv_file">Spread Sheet (CSV):</label>
                                        <input type="file" id="csv_file" name="csv_file" class="form-control" accept=".csv" required>
                                    </div>

                                    <!-- Hidden input to store the selected university ID -->
                                    <input type="hidden" id="university_id" name="university_id" value="">

                                    <!-- Hidden inputs to send values -->
                                    <input type="hidden" id="hidden_programme_name" name="programme_name" value="">
                                    <input type="hidden" id="hidden_batch_name" name="batch_name" value="">
                                    <input type="hidden" id="hidden_course_fee_lkr" name="course_fee_lkr" value="">
                                    <input type="hidden" id="hidden_uni_fee_gbp" name="uni_fee_gbp" value="">
                                    <input type="hidden" id="hidden_uni_fee_usd" name="uni_fee_usd" value="">
                                    <input type="hidden" id="hidden_uni_fee_euro" name="uni_fee_euro" value="">
                                    <input type="hidden" id="hidden_register_date" name="register_date" value="">
                                    <input type="hidden" id="hidden_installment_no" name="installment_no" value="">
                                    <input type="hidden" id="hidden_registration_fee" name="registration_fee" value="">
                                    <input type="hidden" id="hidden_only_course_fee" name="only_course_fee" value="">

                                    <button type="submit" id="upload_student_sbt_btn" class="btn btn-primary mt-3 float-right">Upload Student</button>
                                </form>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <!-- Programme Details Card -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <!-- Left Column -->
                                    <div class="col-md-6">
                                        <div class="mb-3 font-weight-bolder">
                                            <label for="programme_name" class="form-label" style="color: red;">Programme Name:</label>
                                            <input type="text" id="programme_name" name="programme_name" class="form-control" readonly>
                                        </div>

                                        <div class="mb-3">
                                            <label for="course_fee_lkr" class="form-label">Course Fee (LKR):</label>
                                            <input type="text" id="course_fee_lkr" name="course_fee_lkr" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uni_fee_gbp" class="form-label">University Fee (GBP):</label>
                                            <input type="text" id="uni_fee_gbp" name="uni_fee_gbp" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uni_fee_usd" class="form-label">University Fee (USD):</label>
                                            <input type="text" id="uni_fee_usd" name="uni_fee_usd" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uni_fee_euro" class="form-label">University Fee (Euro):</label>
                                            <input type="text" id="uni_fee_euro" name="uni_fee_euro" class="form-control" readonly>
                                        </div>
                                    </div>

                                    <!-- Right Column -->
                                    <div class="col-md-6">
                                        <div class="mb-3 font-weight-bolder">
                                            <label for="batch_name" class="form-label " style="color: red;">Batch Name:</label>
                                            <input type="text" id="batch_name" name="batch_name" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="register_date" class="form-label">Registration Date:</label>
                                            <input type="text" id="register_date" name="register_date" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="installment_no" class="form-label">Installment Number:</label>
                                            <input type="text" id="installment_no" name="installment_no" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="registration_fee" class="form-label">Registration Fee:</label>
                                            <input type="text" id="registration_fee" name="registration_fee" class="form-control" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="only_course_fee" class="form-label">Only Course Fee:</label>
                                            <input type="text" id="only_course_fee" name="only_course_fee" class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Instruction Card -->
                        <div class="card">
                            <div class="card-body">
                                <div class="alert alert-danger mb-3">
                                    <p class="mb-1" style="text-align: justify;">
                                        <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                                        Use only dropdowns in the provided Excel sheet to enter information for <strong>Title, Gender, Nationality,</strong> and <strong>Active</strong> Columns.
                                    </p>
                                    <p class="mb-1" style="text-align: justify;">
                                        <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                                        Please refrain from adding or deleting columns or modifying data types in the provided Excel sheet.
                                    </p>
                                    <p class="mb-0" style="text-align: justify;">
                                        <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                                        Clear the sample data from the Excel sheet before entering new information.
                                    </p>
                                </div>

                                <!-- Download Button -->
                                <a href="./Excel/Uplaod_Student_updated.csv" download class="text-decoration-none">
                                    <div class="d-flex align-items-center justify-content-between border rounded p-2 bg-light">
                                        <div class="fw-bold text-success">DOWNLOAD EXCEL FORMAT</div>
                                        <img src="./img/pngegg.png" alt="Excel Image" class="img-fluid" style="max-width: 50px;">
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- End of Main Content -->


    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->

<!-- jQuery for handling checkbox functionality and fetching batches -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#upload_student_sbt_btn').hide();
        // Enable or disable Program and Batch dropdowns based on checkbox state
        $('#allocate').change(function() {
            if ($(this).is(':checked')) {
                $('#program').prop('disabled', false);
                $('#batch').prop('disabled', false);
                $('#modules-section').show();
            } else {
                $('#program').prop('disabled', true).val('');
                $('#batch').prop('disabled', true).val('');
                $('#modules-section').hide();
                $('#compulsory-modules-container').html('<div class="text-center text-muted"><i>Select a program to view modules</i></div>');
                $('#elective-modules-container').html('<div class="text-center text-muted"><i>Select a program to view modules</i></div>');
                // Reset select all checkboxes
                $('#select-all-compulsory').prop('checked', false);
                $('#select-all-elective').prop('checked', false);
            }
        });

        // Program dropdown change handler
        $('#program').change(function() {
            var selectedOption = $(this).find(':selected');
            var programId = $(this).val();
            var universityId = selectedOption.data('university-id'); // jQuery way to access the data attribute

            // Set the hidden university_id field
            $('#university_id').val(universityId);
            console.log("Program ID: " + programId);
            console.log("University ID: " + universityId);

            // Reset select all checkboxes
            $('#select-all-compulsory').prop('checked', false);
            $('#select-all-elective').prop('checked', false);

            if (programId) {
                // Fetch batches
                $.ajax({
                    url: 'uploadSection/fetch_batches.php', // PHP file to fetch batches
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#batch').empty().append('<option value="">Select Batch</option>');
                        $.each(response, function(index, batch) {
                            $('#batch').append('<option value="' + batch.id + '">' + batch.batch_name + '</option>');
                        });
                        $('#batch').prop('disabled', false); // Enable the batch dropdown
                    }
                });

                // Fetch modules
                $.ajax({
                    url: 'uploadSection/fetch_modules.php', // PHP file to fetch modules
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    dataType: 'json',
                    success: function(response) {
                        var compulsoryHtml = '';
                        var electiveHtml = '';

                        if (response.compulsory && response.compulsory.length > 0) {
                            $.each(response.compulsory, function(index, module) {
                                compulsoryHtml += '<div class="form-check">';
                                compulsoryHtml += '<input type="checkbox" class="form-check-input compulsory-module" id="module_' + module.id + '" name="compulsory_modules[]" value="' + module.module_name + '" data-id="' + module.id + '" checked>';
                                compulsoryHtml += '<label class="form-check-label" for="module_' + module.id + '">' + module.module_name + '</label>';
                                compulsoryHtml += '</div>';
                            });
                        } else {
                            compulsoryHtml = '<div class="text-center text-muted"><i>No compulsory modules found</i></div>';
                        }

                        if (response.elective && response.elective.length > 0) {
                            $.each(response.elective, function(index, module) {
                                electiveHtml += '<div class="form-check">';
                                electiveHtml += '<input type="checkbox" class="form-check-input elective-module" id="module_' + module.id + '" name="elective_modules[]" value="' + module.module_name + '" data-id="' + module.id + '">';
                                electiveHtml += '<label class="form-check-label" for="module_' + module.id + '">' + module.module_name + '</label>';
                                electiveHtml += '</div>';
                            });
                        } else {
                            electiveHtml = '<div class="text-center text-muted"><i>No elective modules found</i></div>';
                        }

                        $('#compulsory-modules-container').html(compulsoryHtml);
                        $('#elective-modules-container').html(electiveHtml);
                        $('#modules-section').show();
                    },
                    error: function() {
                        $('#compulsory-modules-container').html('<div class="text-center text-danger"><i>Error loading modules</i></div>');
                        $('#elective-modules-container').html('<div class="text-center text-danger"><i>Error loading modules</i></div>');
                    }
                });
            } else {
                $('#batch').empty().append('<option value="">Select Batch</option>').prop('disabled', true);
                $('#compulsory-modules-container').html('<div class="text-center text-muted"><i>Select a program to view modules</i></div>');
                $('#elective-modules-container').html('<div class="text-center text-muted"><i>Select a program to view modules</i></div>');
                $('#modules-section').hide();
            }
        });

        // Log batch selection when batch is changed
        $('#batch').change(function() {
            var batchId = $(this).val();
            console.log("Batch ID: " + batchId);
        });

        // Select All Compulsory Modules
        $(document).on('change', '#select-all-compulsory', function() {
            var isChecked = $(this).is(':checked');
            $('.compulsory-module').prop('checked', isChecked);
        });

        // Select All Elective Modules
        $(document).on('change', '#select-all-elective', function() {
            var isChecked = $(this).is(':checked');
            $('.elective-module').prop('checked', isChecked);
        });

        // Update "Select All" checkbox when individual checkboxes change
        $(document).on('change', '.compulsory-module', function() {
            var allChecked = $('.compulsory-module:checked').length === $('.compulsory-module').length;
            $('#select-all-compulsory').prop('checked', allChecked);
        });

        $(document).on('change', '.elective-module', function() {
            var allChecked = $('.elective-module:checked').length === $('.elective-module').length;
            $('#select-all-elective').prop('checked', allChecked);
        });
    });
</script>

<!-- Include necessary CSS and JS for Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Initialize Select2 for all dropdowns
    });
</script>

<!-- -----------------------  -->



<script>
    $('#program, #batch').change(function() {
        let programId = $('#program').val();
        let batchId = $('#batch').val();

        if (programId !== '' && batchId !== '') {
            $.ajax({
                url: 'fetch_payment_data.php',
                type: 'POST',
                data: {
                    programme_id: programId,
                    batch_id: batchId
                },
                dataType: 'json',
                success: function(response) {
                    $('#paymentTableContainer').html(response.table);

                    console.log(response.data);
                    if (response.data) {
                        $('#programme_name').val(response.data.program_name);
                        $('#hidden_programme_name').val(response.data.program_name);

                        $('#batch_name').val(response.data.batch_name);
                        $('#hidden_batch_name').val(response.data.batch_name);

                        $('#course_fee_lkr').val(response.data.course_fee_lkr);
                        $('#hidden_course_fee_lkr').val(response.data.course_fee_lkr);

                        $('#uni_fee_gbp').val(response.data.uni_fee_gbp);
                        $('#hidden_uni_fee_gbp').val(response.data.uni_fee_gbp);

                        $('#uni_fee_usd').val(response.data.uni_fee_usd);
                        $('#hidden_uni_fee_usd').val(response.data.uni_fee_usd);

                        $('#uni_fee_euro').val(response.data.uni_fee_euro);
                        $('#hidden_uni_fee_euro').val(response.data.uni_fee_euro);

                        $('#register_date').val(response.data.register_date);
                        $('#hidden_register_date').val(response.data.register_date);

                        $('#installment_no').val(response.data.installment_no);
                        $('#hidden_installment_no').val(response.data.installment_no);

                        $('#registration_fee').val(response.data.registration_fee);
                        $('#hidden_registration_fee').val(response.data.registration_fee);

                        $('#only_course_fee').val(response.data.only_course_fee);
                        $('#hidden_only_course_fee').val(response.data.only_course_fee);

                        $('#upload_student_sbt_btn').show();
                        $('#paymentDetailsSection').show(); // Show the payment details section

                    } else {
                        $('#programme_name, #batch_name, #course_fee_lkr, #uni_fee_gbp, #uni_fee_usd, #uni_fee_euro, #register_date, #installment_no, #registration_fee, #only_course_fee').val('');
                        $('#upload_student_sbt_btn').hide();
                        $('#paymentDetailsSection').hide(); // Hide the payment details section
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error fetching payment data: ' + error);
                }
            });
        } else {
            $('#paymentTableContainer').html('');
            $('#programme_name, #batch_name, #course_fee_lkr, #uni_fee_gbp, #uni_fee_usd, #uni_fee_euro, #register_date, #installment_no, #registration_fee, #only_course_fee').val('');
            $('#upload_student_sbt_btn').hide();
            $('#paymentDetailsSection').hide(); // Hide the payment details section
        }
    });
</script>



<!-- -----------------------  -->

</body>

</html>