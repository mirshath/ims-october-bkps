<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------


// Initialize variables for edit mode
$isEdit = false;
$assessment = null;
?>


<style>
    .hidden-field {
        display: none;
    }
</style>
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">
                        Exam / Assignment Adding Result
                    </h4>
                </div>

                <!-- Form Section -->
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row mb-5">
                        <div class="col-md-7">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-3">
                                            <label for="programme">Programme</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="programme_id" id="programme" style="font-size: 12px;" class="form-control select2"
                                                    required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" style="font-size: 12px;" class="form-control select2"
                                                    required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="module">Module</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="module_id" id="module" style="font-size: 12px;" class="form-control select2"
                                                    required>
                                                    <option value="">Select Module</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3 hidden-field">
                                            <label for="year">Year</label>
                                        </div>
                                        <div class="col-md-9 hidden-field">
                                            <div class="form-group mb-3">
                                                <input type="text" name="year_id" style="font-size: 12px;" id="year" class="form-control"
                                                    required readonly
                                                    value="">
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3 hidden-field">
                                            <label for="semester">Semester</label>
                                        </div>
                                        <div class="col-md-9 hidden-field">
                                            <div class="form-group mb-3">
                                                <input type="text" name="semester_id" style="font-size: 12px;" id="semester" class="form-control"
                                                    required readonly
                                                    value="">
                                            </div>
                                        </div>

                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="mainComponent">Main Components</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">

                                                <select name="main_component_id" id="mainComponent" class="form-control select2" required>
                                                    <option value="">Select Main Component</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="subComponent">Sub Components</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">

                                                <select name="sub_component_id" id="subComponent" class="form-control select2">
                                                    <option value="">Select Sub Component</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- button section here  -->
                                    <!-- Add View Button -->
                                    <div class="col-12 mt-3">
                                        <button type="button" id="viewButton" class="btn btn-primary btn-sm">View</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Add DataTable Container -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <table id="assessmentTable" class="table table-bordered table-striped" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Programme</th>
                                            <th>Batch</th>
                                            <th>Module</th>
                                            <th>Main Component</th>
                                            <th>Sub Component</th>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Assessment Date</th>
                                            <!--<th>Description</th>-->
                                            <th>Add Result</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // Initialize DataTable
            let assessmentTable = $('#assessmentTable').DataTable({
                "order": [
                    [0, "asc"]
                ], // Sort by the first column (Type) in ascending order
                "pageLength": 10, // Show 10 entries per page
                "language": {
                    "lengthMenu": "Show _MENU_ entries per page",
                    "zeroRecords": "No assessments found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No assessments available",
                    "infoFiltered": "(filtered from _MAX_ total records)"
                },
                "searching": true, // Enable searching
                "paging": true // Enable pagination
            });

            // Add click handler for view button
            $('#viewButton').click(function() {
                // Get all form values
                // Get all form values
                let programme_id = $('#programme').val();
                let batch_id = $('#batch').val();
                let module_id = $('#module').val();
                let year_id = $('#year').val();
                let semester_id = $('#semester').val();
                let main_component_id = $('#mainComponent').val();
                let sub_component_id = $('#subComponent').val();

                // Check if required fields are empty
                if (!programme_id || !batch_id || !module_id || !year_id ||
                    !semester_id || !main_component_id) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required Fields Empty',
                        text: 'Please select all the required details!',
                        confirmButtonColor: '#3085d6'
                    });
                    return; // Stop execution if validation fails
                }

                // If validation passes, proceed with AJAX call
                let searchData = {
                    programme_id: programme_id,
                    batch_id: batch_id,
                    module_id: module_id,
                    year_id: year_id,
                    semester_id: semester_id,
                    main_component_id: main_component_id,
                    sub_component_id: sub_component_id || null
                };

                // Make AJAX call to search assessments
                $.ajax({
                    url: "transection_exams/search_assessment.php",
                    method: "POST",
                    data: searchData,
                    dataType: "json",
                    success: function(response) {
                        // Clear existing table data
                        assessmentTable.clear();

                        if (response.success && response.assessments) {
                            // Add new data
                            response.assessments.forEach(function(assessment) {
                                // Create attachments links
                                let attachmentLinks = assessment.attachments.map(function(attachment) {
                                    return `<a href="${attachment}" target="_blank" class="btn btn-sm btn-info m-1">View</a>`;
                                }).join('');

                                assessmentTable.row.add([
                                    assessment.programme_name,
                                    assessment.batch_name,
                                    assessment.module_name,
                                    assessment.main_component_name,
                                    assessment.sub_component_name || 'N/A',
                                    assessment.year_id,
                                    assessment.semester_id,
                                    assessment.assessment_date,
                                    // assessment.description,
                                    `${assessment.programme_name === 'Bachelor of Business Management (Hons)' ? 
                                        `<a href="view_BBM_details_of_exams_add_result.php?id=${assessment.id}" class="btn btn-sm btn-success" style="font-size: 11px;">
                                            <i class="fas fa-plus"></i> &nbsp; BBMResult
                                        </a>` :
                                        `<a href="view_details_of_exams_add_result.php?id=${assessment.id}" class="btn btn-sm btn-success" style="font-size: 11px;">
                                            <i class="fas fa-plus"></i> &nbsp; Result
                                        </a>`}`,

                                ]);
                            });

                            // Draw the table
                            assessmentTable.draw();
                        } else {
                            // Draw empty table to show "No data" message
                            assessmentTable.draw();

                            Swal.fire({
                                icon: 'info',
                                title: 'No Results',
                                text: 'No matching assessment found!',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        // Clear and redraw table on error
                        assessmentTable.clear().draw();

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while searching. Please try again.',
                            confirmButtonColor: '#3085d6'
                        });
                        console.error('Error searching assessment:', error);
                    }
                });
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
                        let selected = <?php echo $isEdit ? 'programme.program_code == ' . $assessment['programme_id'] : 'false'; ?> ? 'selected' : '';
                        programmeDropdown.append(`<option value="${programme.program_code}" ${selected}>${programme.program_name}</option>`);
                    });
                    <?php if ($isEdit): ?>
                        programmeDropdown.trigger('change');
                    <?php endif; ?>
                }
            });

            // Programme change event
            $('#programme').change(function() {
                let programmeId = $(this).val();
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        let batchDropdown = $('#batch');
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        data.forEach(function(batch) {
                            let selected = <?php echo $isEdit ? 'batch.id == ' . $assessment['batch_id'] : 'false'; ?> ? 'selected' : '';
                            batchDropdown.append(`<option value="${batch.id}" ${selected}>${batch.batch_name}</option>`);
                        });
                        <?php if ($isEdit): ?>
                            batchDropdown.trigger('change');
                        <?php endif; ?>
                    }
                });
            });

            // Batch change event
            $('#batch').change(function() {
                let batchId = $(this).val();
                $.ajax({
                    url: "transection_exams/fetch_modules.php",
                    method: "POST",
                    data: {
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(data) {
                        let moduleDropdown = $('#module');
                        moduleDropdown.empty().append('<option value="">Select Module</option>');
                        data.forEach(function(module) {
                            let selected = <?php echo $isEdit ? 'module.id == ' . $assessment['module_id'] : 'false'; ?> ? 'selected' : '';
                            moduleDropdown.append(`<option value="${module.id}" ${selected}>${module.name}</option>`);
                        });
                    }
                });
            });

            $('#module').change(function() {
                let moduleId = $(this).val();
                console.log('Selected Module ID: ', moduleId); // Check if the module ID is being captured

                if (moduleId) {
                    $.ajax({
                        url: "transection_exams/fetch_year_semester.php",
                        method: "POST",
                        data: {
                            module_id: moduleId
                        },
                        dataType: "json",
                        success: function(data) {
                            console.log('Received Data: ', data); // Check the response from the server
                            if (data) {
                                $('#year').val(data.year_name).prop('readonly', true);
                                $('#semester').val(data.semester_name).prop('readonly', true);

                                console.log('Year:', $('#year').val()); // Log year field value
                                console.log('Semester:', $('#semester').val()); // Log semester field value

                            } else {
                                $('#year').val('').prop('readonly', false);
                                $('#semester').val('').prop('readonly', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', error); // In case of error
                        }
                    });
                } else {
                    $('#year').val('').prop('readonly', false);
                    $('#semester').val('').prop('readonly', false);
                }
            });

            $('#module').change(function() {
                let moduleId = $(this).val();
                if (moduleId) {
                    $.ajax({
                        url: "transection_exams/fetch_main_components.php",
                        method: "POST",
                        data: {
                            module_id: moduleId
                        },
                        dataType: "json",
                        success: function(data) {
                            let mainComponentDropdown = $('#mainComponent');
                            mainComponentDropdown.empty().append('<option value="">Select Main Component</option>');
                            data.forEach(function(component) {
                                mainComponentDropdown.append(`<option value="${component.main_component_id}">${component.as_main_component_name}</option>`);
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching main components:', error);
                        }
                    });
                } else {
                    $('#mainComponent').empty().append('<option value="">Select Main Component</option>');
                    $('#subComponent').empty().append('<option value="">Select Sub Component</option>');
                }
            });

            // Fetch sub components when a main component is selected
            $('#mainComponent').change(function() {
                let mainComponentId = $(this).val();
                let moduleId = $('#module').val(); // Get the selected module ID
                if (mainComponentId && moduleId) {
                    $.ajax({
                        url: "transection_exams/fetch_sub_components.php", // Ensure this path is correct
                        method: "POST",
                        data: {
                            main_component_id: mainComponentId,
                            module_id: moduleId
                        },
                        dataType: "json",
                        success: function(data) {
                            let subComponentDropdown = $('#subComponent');
                            subComponentDropdown.empty().append('<option value="">Select Sub Component</option>');
                            if (data.length > 0) {
                                data.forEach(function(subComponent) {
                                    subComponentDropdown.append(`<option value="${subComponent.id}">${subComponent.sub_component_name}</option>`);
                                });
                            } else {
                                subComponentDropdown.append('<option value="">No Sub Components Available</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching sub components:', error);
                        }
                    });
                } else {
                    $('#subComponent').empty().append('<option value="">Select Sub Component</option>');
                }
            });


        });
    </script>

    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">

</div>
</body>

</html>