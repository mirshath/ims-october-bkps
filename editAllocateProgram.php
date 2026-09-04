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

            <div class="p-3">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Edit Allocate Programs</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Edit Allocate Programs</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mt-3 mb-4">
                                    <div class="col-md-6">
                                        <!-- Student Dropdown -->
                                        <div class="mb-3">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label for="student" class="form-label">Select Student:</label>
                                                </div>
                                                <div class="col">

                                                    <select id="student" name="student"
                                                        class="form-select form-control">
                                                        <option value="">-- Select Student --</option>
                                                        <?php
                                                        // Fetch students from the database
                                                        // $conn = new mysqli('localhost', 'root', '', 'demo_db');
                                                        $result = $conn->query("SELECT student_code, first_name, last_name FROM students");
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<option value='{$row['student_code']}'>{$row['first_name']} {$row['last_name']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                        <script>
                                            $(document).ready(function() {
                                                $('#student').select2(); // Initialize Select2 on the student dropdown
                                            });
                                        </script>
                                    </div>
                                </div>
                                <!-- Form Fields for Allocation Details -->
                                <form id="edit_allocate_form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="student_name" class="form-label">Student:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="student_name" name="student_name"
                                                            class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="university" class="form-label">University:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="university" name="university"
                                                            class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="programme" class="form-label">Programme:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="programme" name="programme"
                                                            class="form-control" disabled>
                                                        <input type="hidden" id="programme_id" name="programme_id"
                                                            value="">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="batch" class="form-label">Batch:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="batch" name="batch" class="form-control"
                                                            disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="registration_code" class="form-label">Registration Code:</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" id="registration_code"
                                                            name="registration_code" class="form-control">
                                                    </div>
                                                </div>
                                            </div>


                                            <!-- -  -->
                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col">
                                                        <label id="bms_registration_id" name="bms_registration_id"
                                                            class="text-center text-danger fw-bolder" style="font-size: 25px; line-height: 1.5;">Please make the payment and get the BMS Registration ID</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- -  -->

                                        </div>
                                        <div class="col-md-6">
                                            <!-- Display Compulsory Subjects -->
                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="compulsory_subjects" class="form-label">Compulsory
                                                            Subjects:</label>
                                                    </div>
                                                    <div class="col">
                                                        <div id="compulsory_subjects" class="form-control"
                                                            style="height: auto;"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Elective Subjects (with checkboxes) -->
                                            <div class="mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="elective_subjects" class="form-label">Elective
                                                            Subjects:</label>
                                                    </div>
                                                    <div class="col">
                                                        <!-- <div id="elective_subjects" class="form-control" style="height: auto;"></div> Holds the checkboxes -->
                                                        <div id="elective_subjects" class="form-control"
                                                            style="height: auto;"></div> <!-- Holds the checkboxes -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- <button style="float: right;">Update</button> -->
                                    <input type="submit" id="update_edit_allocation_btn" class="btn btn-primary float-right" value="Update">

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mb-5">
                <div class="card mt-5">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span
                            class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">List</h6>
                    </div>

                    <div class="card-body">
                        <!-- Coordinators Table -->
                        <table class="table table-bordered" id="leadsTable">
                        </table>
                    </div>
                </div>
                <!-- ---------------------------------------------------------------------------  -->
            </div>
            <!-- ---------------------------------------------------------------------------  -->

        </div>
    </div>
</div>

<!-- <script>
    $(document).ready(function() {
        $('#student').change(function() {
            var student_code = $(this).val();
            if (student_code !== '') {
                $.ajax({
                    url: 'fetch_allocation_details.php',
                    type: 'POST',
                    data: {
                        student_code: student_code
                    },
                    success: function(response) {
                        var data = JSON.parse(response);

                        // Check if the student has any allocations
                        if (!data.allocated) {
                            alert('The selected student has not been allocated any programs.');
                            // Clear the form fields if no allocations
                            $('#student_name').val('');
                            $('#university').val('');
                            $('#programme').val(''); // Use programme_name for display
                            $('#programme_id').val(''); // Clear the hidden programme ID
                            $('#batch').val('');
                            $('#registration_code').val('');
                            $('#elective_subjects').empty();
                            $('#compulsory_subjects').empty();
                            return; // Exit the function
                        }

                        // If allocated, populate the fields
                        $('#student_name').val(data.student_name);
                        $('#university').val(data.university);
                        $('#programme').val(data.programme); // Display programme name
                        $('#programme_id').val(data.program_id); // Store programme ID
                        $('#batch').val(data.batch);
                        if (!data.registration_code) {
                            console.log("Please update the REG ID.");
                            
                        } else {
                            $('#registration_code').val(data.registration_code);
                        }

                        // Clear previous subjects
                        $('#elective_subjects').empty();
                        $('#compulsory_subjects').empty();

                        // Fetch compulsory and elective modules based on the programme ID and student registration code
                        $.ajax({
                            url: 'fetch_compulsory_modules.php',
                            type: 'POST',
                            data: {
                                programme_id: data.program_id, // Use the program_id from the allocation details
                                student_registration_id: data.registration_code // Pass the registration_code here as well
                            },
                            success: function(response) {
                                const result = JSON.parse(response); // Parse the JSON response

                                // Update the compulsory subjects
                                $('#compulsory_subjects').html(result.compulsory);

                                // Update the elective subjects
                                $('#elective_subjects').html(result.elective);
                            }
                        });
                    }
                });
            }
        });




        $(document).ready(function() {
            $('form').on('submit', function(e) {
                e.preventDefault(); // Prevent form from submitting the traditional way

                // Collect form data
                var formData = {
                    student_code: $('#student').val(), // Fixing student_code retrieval
                    registration_code: $('#registration_code').val(),

                    compulsory_subjects: [], // Initialize compulsory_subjects array
                    elective_subjects: [] // Initialize elective_subjects array
                };

                // Collect compulsory subjects (if they are checkboxes)
                $('#compulsory_subjects input[type="checkbox"]:checked').each(function() {
                    formData.compulsory_subjects.push($(this).val());
                });

                // Collect elective subjects (if they are checkboxes)
                $('#elective_subjects input[type="checkbox"]:checked').each(function() {
                    formData.elective_subjects.push($(this).val());
                });

                // Send data to PHP via AJAX
                $.ajax({
                    url: 'update_student.php', // PHP file to handle the update
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert(data.message);
                        }
                    },
                    error: function() {
                        alert('Error while sending data.');
                    }
                });
            });
        });




    });
</script> -->



<script>
    $(document).ready(function() {
        $('#bms_registration_id').hide();
        $('#update_edit_allocation_btn').hide();

        function clearFormFields() {
            $('#student_name, #university, #programme, #batch, #registration_code').val('');
            $('#programme_id').val('');
            $('#compulsory_subjects, #elective_subjects').empty();
        }

        $('#student').change(function() {
            const student_code = $(this).val();
            clearFormFields();


            if (student_code !== '') {
                $.ajax({
                    url: 'fetch_allocation_details.php',
                    type: 'POST',
                    data: {
                        student_code: student_code
                    },
                    success: function(response) {
                        let data;
                        try {
                            data = JSON.parse(response);
                        } catch (e) {
                            alert('Server Error: Invalid response!');
                            return;
                        }

                        if (!data.allocated) {
                            alert('No program allocated for this student.');
                            return;
                        }

                        $('#student_name').val(data.student_name || '');
                        $('#university').val(data.university || '');
                        $('#programme').val(data.programme || '');
                        $('#programme_id').val(data.program_id || '');
                        $('#batch').val(data.batch || '');
                        $('#registration_code').val(data.registration_code || '');
                        if (!data.registration_code) {
                            $('#registration_code').prop('readonly', true);
                            $('#update_edit_allocation_btn').hide();
                            $('#registration_code').hide();
                            $('#bms_registration_id').show();
                        }
                        // $('#update_edit_allocation_btn').show();

                        if (data.program_id && data.registration_code) {
                            $.ajax({
                                url: 'fetch_compulsory_modules.php',
                                type: 'POST',
                                data: {
                                    programme_id: data.program_id,
                                    student_registration_id: data.registration_code
                                },
                                success: function(moduleResponse) {
                                    let modules;
                                    try {
                                        modules = JSON.parse(moduleResponse);
                                    } catch (e) {
                                        alert('Error: Invalid module response!');
                                        return;
                                    }

                                    $('#compulsory_subjects').html(modules.compulsory || '');
                                    $('#elective_subjects').html(modules.elective || '');
                                    $('#update_edit_allocation_btn').show();
                                },
                                error: function() {
                                    alert('Failed to fetch modules.');
                                }
                            });
                        }
                    },
                    error: function() {
                        alert('Failed to fetch allocation data.');
                    }
                });
            }
        });

        $('#edit_allocate_form').on('submit', function(e) {
            e.preventDefault();
            const formData = {
                student_code: $('#student').val(),
                registration_code: $('#registration_code').val(),
                compulsory_subjects: [],
                elective_subjects: []
            };

            $('#compulsory_subjects input[type="checkbox"]:checked').each(function() {
                formData.compulsory_subjects.push($(this).val());
            });
            $('#elective_subjects input[type="checkbox"]:checked').each(function() {
                formData.elective_subjects.push($(this).val());
            });

            $.ajax({
                url: 'update_student.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    let data;
                    try {
                        data = JSON.parse(response);
                    } catch (e) {
                        alert('Invalid server response!');
                        return;
                    }

                    alert(data.message);
                    if (data.success) location.reload();
                },
                error: function() {
                    alert('Something went wrong while saving.');
                }
            });
        });
    });
</script>

</body>

</html>