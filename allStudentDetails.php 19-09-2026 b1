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

            <!-- Begin Page Content -->
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">All Student Details</h4>
                </div>

                <div class="div w-50">

                    <!-- Program Selection -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="programDropdown">
                                Program
                            </label>
                        </div>
                        <div class="col-md-8">
                            <select id="programDropdown" class="form-select select2">
                                <option value="">Select Program</option>
                            </select>
                        </div>
                    </div>

                    <!-- Batch Selection -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="batchDropdown">
                                Batch
                            </label>
                        </div>
                        <div class="col-md-8">
                            <select id="batchDropdown" class="form-select select2">
                                <option value="">Select Batch</option>
                            </select>
                        </div>
                    </div>

                    <!-- Student Status Selection -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="statusDropdown">
                                Student Status
                            </label>
                        </div>
                        <div class="col-md-8">
                            <select id="statusDropdown" class="form-select select2">
                                <option value="active" selected>Active</option>
                                <option value="transferred">Transferred</option>
                                <option value="completed">Completed</option>
                                <option value="progressionTo">Progression</option>
                                <option value="drop">Dropped</option>
                                <option value="ReRegister">Re-Register</option>
                                <option value="batchSwap">Batch Swap</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <button id="submitBtn" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="table-responsive mt-4 w-100" id="resultsSection" style="display: none; font-size: 13px;">
                    <table class="table table-bordered table-striped" id="studentsDataTable">
                        <thead>
                            <tr>
                                <th>BMS Registration ID (AG)</th>
                                <th>Program Registration ID</th>
                                <th>Student Code (System)</th>
                                <th>NIC</th>
                                <th>Title</th>
                                <th>Full Name</th>
                                <th>Name to be appeared</th>
                                <th>DOB</th>
                                <th>Nationality</th>
                                <th>Permanent Address</th>
                                <th>Current Address</th>
                                <th>Mobile</th>
                                <th>Telephone</th>
                                <th>Personal Email</th>
                                <th>BMS Email</th>
                                <th>Occupation</th>
                                <th>Status</th>
                                <th>Entered By</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            let dataTable = null;

            // Initialize Select2
            $('.select2').select2();

            // Directly load all programs
            $.ajax({
                url: 'Batch_transer/fetch_programs_without_uni.php',
                type: 'POST',
                success: function(data) {
                    $('#programDropdown').html(data).trigger('change');
                }
            });


            // Fetch batches based on selected program
            $('#programDropdown').on('change', function() {
                const programId = $(this).val();
                if (programId) {
                    $.ajax({
                        url: 'reports/AllStudents/fetch_batches.php',
                        type: 'POST',
                        data: {
                            program_id: programId
                        },
                        success: function(data) {
                            $('#batchDropdown').html(data).trigger('change');
                        }
                    });
                } else {
                    $('#batchDropdown').html('<option value="">Select Batch</option>').trigger('change');
                }
            });

            // Submit Button Click Handler
            $('#submitBtn').on('click', function() {
                const $btn = $(this);
                const filters = {
                    // university_id: 1, 
                    program_id: $('#programDropdown').val(),
                    batch_id: $('#batchDropdown').val(),
                    status: $('#statusDropdown').val()
                };

                // Check if program is selected
                if (!filters.program_id) {
                    alert('Please select a program');
                    return;
                }

                // Disable button to prevent double-click (no text change)
                $btn.prop('disabled', true);

                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#studentsDataTable')) {
                    $('#studentsDataTable').DataTable().destroy();
                }

                // Show loading indicator in table
                $('#resultsBody').html('<tr><td colspan="17" class="text-center">Loading...</td></tr>');
                $('#resultsSection').show();

                // Fetch filtered results
                $.ajax({
                    url: 'reports/AllStudents/fetch_student_details.php',
                    type: 'POST',
                    data: filters,
                    success: function(response) {
                        $('#resultsBody').html(response);

                        // Only initialize DataTable if data is found
                        if (response.indexOf('No results found') === -1) {
                            dataTable = $('#studentsDataTable').DataTable({
                                pageLength: 100,
                                dom: 'Bfrtip',
                                buttons: [{
                                        extend: 'copy',
                                        className: 'btn btn-primary btn-sm'
                                    },
                                    {
                                        extend: 'csv',
                                        className: 'btn btn-primary btn-sm'
                                    },
                                    {
                                        extend: 'excel',
                                        className: 'btn btn-primary btn-sm'
                                    },
                                    {
                                        extend: 'pdf',
                                        className: 'btn btn-primary btn-sm',
                                        orientation: 'landscape',
                                        pageSize: 'A4',
                                        title: 'Student Details',
                                        customize: function(doc) {
                                            doc.pageMargins = [20, 20, 20, 20];
                                        }
                                    },
                                    {
                                        extend: 'print',
                                        className: 'btn btn-primary btn-sm',
                                        orientation: 'landscape',
                                        pageSize: 'A4',
                                        title: 'Student Details',
                                        customize: function(win) {
                                            $(win.document.body)
                                                .css('font-size', '12px')
                                                .find('h1')
                                                .css('text-align', 'center');
                                        }
                                    }
                                ],
                                responsive: true,
                                ordering: true,
                                searching: true,
                                info: true,
                                lengthChange: true,
                                language: {
                                    search: "_INPUT_",
                                    searchPlaceholder: "Search records..."
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        $('#resultsBody').html('<tr><td colspan="17" class="text-center text-danger">Error loading data: ' + error + '</td></tr>');
                    },
                    complete: function() {
                        // Re-enable button
                        $btn.prop('disabled', false).html('Submit');
                    }
                });
            });

            // Add error handling for empty or invalid responses
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                console.error('Ajax error:', thrownError);
            });

            // Add custom styling for DataTables buttons and search
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .dt-buttons .btn {
                        margin-right: 5px;
                        margin-bottom: 5px;
                    }
                    .dataTables_filter {
                        margin-bottom: 10px;
                    }
                    .dataTables_filter input {
                        width: 300px;
                        padding: 5px 10px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                    }
                `)
                .appendTo('head');
        });
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
</div>
</body>

</html>