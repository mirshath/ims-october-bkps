<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
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
            <div class="container-fluid py-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Manage Time Table</h4>
                    <a href="view_timetable.php" class="btn btn-primary btn-sm">View Time Table</a>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                           
                            <div class="card-body p-4">
                                <form action="process_timetable.php" method="POST" id="timetableForm">
                                    <div class="row g-4">
                                        <!-- Left Column -->
                                        <div class="col-md-6">
                                            <div class="mb-4 row">
                                                <label for="programme" class="form-label fw-bold col-md-4">Programme:</label>
                                                <div class="col-md-8">
                                                    <select name="programme_id" id="programme" class="form-select select2-dropdown" required>
                                                        <option value="">Select Programme</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="batch" class="form-label fw-bold col-md-4">Batch:</label>
                                                <div class="col-md-8">
                                                    <select name="batch_id[]" id="batch" class="form-select select2-dropdown" multiple required>
                                                        <option value="">Select Batch(es)</option>
                                                    </select>
                                                    <small class="form-text text-muted">You can select multiple batches by holding Ctrl/Cmd key</small>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="module" class="form-label fw-bold col-md-4">Module:</label>
                                                <div class="col-md-8">
                                                    <select name="module_id" id="module" class="form-select select2-dropdown" required>
                                                        <option value="">Select Module</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="lecturer" class="form-label fw-bold col-md-4">Lecturer:</label>
                                                <div class="col-md-8">
                                                    <select name="lecturer" id="lecturer" class="form-select select2-dropdown" required>
                                                        <option value="">Select Lecturer</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="day" class="form-label fw-bold col-md-4">Day:</label>
                                                <div class="col-md-8">
                                                    <select name="day" id="day" class="form-select select2-dropdown" required>
                                                        <option value="">Select Day</option>
                                                        <option value="Monday">Monday</option>
                                                        <option value="Tuesday">Tuesday</option>
                                                        <option value="Wednesday">Wednesday</option>
                                                        <option value="Thursday">Thursday</option>
                                                        <option value="Friday">Friday</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column -->
                                        <div class="col-md-6">
                                            <div class="mb-4 row">
                                                <label for="start_time" class="form-label fw-bold col-md-4">Start Time:</label>
                                                <div class="col-md-8">
                                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="end_time" class="form-label fw-bold col-md-4">End Time:</label>
                                                <div class="col-md-8">
                                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="start_date" class="form-label fw-bold col-md-4">Start Date:</label>
                                                <div class="col-md-8">
                                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="end_date" class="form-label fw-bold col-md-4">End Date:</label>
                                                <div class="col-md-8">
                                                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="comp1_deadline" class="form-label fw-bold col-md-4">Component 1 Deadline:</label>
                                                <div class="col-md-8">
                                                    <input type="date" class="form-control" id="comp1_deadline" name="comp1_deadline" required>
                                                </div>
                                            </div>

                                            <div class="mb-4 row">
                                                <label for="comp2_deadline" class="form-label fw-bold col-md-4">Component 2 Deadline:</label>
                                                <div class="col-md-8">
                                                    <input type="date" class="form-control" id="comp2_deadline" name="comp2_deadline" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">Reset</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Timetable
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Content -->
        </div>
        <!-- End Main Content -->
    </div>
    <!-- End Content Wrapper -->
</div>
<!-- End Page Wrapper -->

<!-- JS and CSS includes -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

<style>
    /* Scoped styles for the timetable form only */
    #timetableForm .select2-container--bootstrap-5 .select2-selection {
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        font-weight: 400;
        line-height: 1.5;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-color: #fff;
    }

    #timetableForm .select2-container--bootstrap-5.select2-container--focus .select2-selection,
    #timetableForm .select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    #timetableForm .form-label {
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    #timetableForm input[type="date"],
    #timetableForm input[type="time"] {
        height: 38px;
    }

    /* These styles only apply to the card within our form section */
    #content .card {
        border: 1px solid rgba(0, 0, 0, 0.125);
    }

    #content .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    }

    /* Style for multiple select */
    #batch+.select2-container .select2-selection--multiple {
        min-height: 38px;
    }

    #batch+.select2-container .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd;
        color: white;
        border: none;
        padding: 2px 8px;
        margin-right: 5px;
        margin-top: 3px;
    }

    #batch+.select2-container .select2-selection--multiple .select2-selection__choice__remove {
        color: white;
        margin-right: 5px;
    }

    #batch+.select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #f8f9fa;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize all Select2 dropdowns with Bootstrap 5 theme
        $('.select2-dropdown').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || $(this).find('option:first').text();
            },
            allowClear: true
        });

        // Special configuration for batch dropdown to support multiple selection
        $('#batch').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Batch(es)',
            allowClear: true,
            closeOnSelect: false
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
                    programmeDropdown.append(`<option value="${programme.program_code}">${programme.program_name}</option>`);
                });
                programmeDropdown.trigger('change');
            },
            error: function(xhr, status, error) {
                console.error("Error fetching programmes:", error);
                showAlert("Error loading programmes. Please try again.", "danger");
            }
        });

        // Programme change event
        $('#programme').on('change', function() {
            let programmeId = $(this).val();
            let batchDropdown = $('#batch');
            let moduleDropdown = $('#module');
            let lecturerDropdown = $('#lecturer');

            // Reset dependent dropdowns
            batchDropdown.empty().append('<option value="">Select Batch</option>');
            moduleDropdown.empty().append('<option value="">Select Module</option>');
            lecturerDropdown.empty().append('<option value="">Select Lecturer</option>');

            batchDropdown.trigger('change');
            moduleDropdown.trigger('change');
            lecturerDropdown.trigger('change');

            if (programmeId) {
                // Show loading indicator
                batchDropdown.append('<option value="" disabled>Loading batches...</option>');
                lecturerDropdown.append('<option value="" disabled>Loading lecturers...</option>');

                // Fetch batches
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        if (data.length > 0) {
                            data.forEach(function(batch) {
                                batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                            });
                        } else {
                            batchDropdown.append('<option value="" disabled>No batches found</option>');
                        }
                        batchDropdown.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching batches:", error);
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        batchDropdown.append('<option value="" disabled>Error loading batches</option>');
                        batchDropdown.trigger('change');
                    }
                });

                // Fetch lecturers for this program
                $.ajax({
                    url: "transection_exams/fetch_lecturers.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        lecturerDropdown.empty().append('<option value="">Select Lecturer</option>');
                        if (data.length > 0) {
                            data.forEach(function(lecturer) {
                                lecturerDropdown.append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
                            });
                        } else {
                            lecturerDropdown.append('<option value="" disabled>No lecturers found for this program</option>');
                        }
                        lecturerDropdown.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching lecturers:", error);
                        lecturerDropdown.empty().append('<option value="">Select Lecturer</option>');
                        lecturerDropdown.append('<option value="" disabled>Error loading lecturers</option>');
                        lecturerDropdown.trigger('change');
                    }
                });
            }
        });

        // Batch change event
        $('#batch').on('change', function() {
            let batchIds = $(this).val();
            let moduleDropdown = $('#module');

            moduleDropdown.empty().append('<option value="">Select Module</option>');
            moduleDropdown.trigger('change');

            if (batchIds && batchIds.length > 0) {
                // Show loading indicator
                moduleDropdown.append('<option value="" disabled>Loading modules...</option>');

                // Get the first selected batch to fetch modules
                // In a real implementation, you might want to fetch modules for all selected batches
                // and show modules that are common to all selected batches
                let firstBatchId = batchIds[0];

                $.ajax({
                    url: "transection_exams/fetch_modules.php",
                    method: "POST",
                    data: {
                        batch_id: firstBatchId
                    },
                    dataType: "json",
                    success: function(data) {
                        moduleDropdown.empty().append('<option value="">Select Module</option>');
                        if (data.length > 0) {
                            data.forEach(function(module) {
                                moduleDropdown.append(`<option value="${module.id}">${module.name}</option>`);
                            });
                        } else {
                            moduleDropdown.append('<option value="" disabled>No modules found for the selected batch(es)</option>');
                        }
                        moduleDropdown.trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching modules:", error);
                        moduleDropdown.empty().append('<option value="">Select Module</option>');
                        moduleDropdown.append('<option value="" disabled>Error loading modules</option>');
                        moduleDropdown.trigger('change');
                    }
                });
            }
        });

        // Form validation
        $('#timetableForm').on('submit', function(e) {
            let isValid = true;

            // Check all required fields
            $(this).find('[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            // Check if end time is after start time
            const startTime = $('#start_time').val();
            const endTime = $('#end_time').val();
            if (startTime && endTime && startTime >= endTime) {
                isValid = false;
                $('#end_time').addClass('is-invalid');
                showAlert("End time must be after start time", "danger");
            }

            // Check if end date is after start date
            const startDate = new Date($('#start_date').val());
            const endDate = new Date($('#end_date').val());
            if (startDate && endDate && startDate > endDate) {
                isValid = false;
                $('#end_date').addClass('is-invalid');
                showAlert("End date must be after start date", "danger");
            }

            if (!isValid) {
                e.preventDefault();
                showAlert("Please fill in all required fields correctly", "danger");
            }
        });
    });

    // Function to reset the form
    function resetForm() {
        $('#timetableForm')[0].reset();
        $('.select2-dropdown').val('').trigger('change');
        $('.is-invalid').removeClass('is-invalid');
    }

    // Function to show alerts
    function showAlert(message, type) {
        const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`);

        // Insert alert before the form
        $('#timetableForm').before(alertDiv);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alertDiv.alert('close');
        }, 5000);
    }
</script>
</body>

</html>