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
                    <h4 class="h4 mb-0 text-gray-800">Induction Master File</h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between"
                                style="height: 60px;">
                                <div class="d-flex align-items-center">
                                    <span
                                        class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                        style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Student Update</h6>
                                </div>
                                <div class="d-flex align-items-center" style="width: 300px;">
                                    <select id="programmeFilter" class="form-select form-select-sm select2">
                                        <option value="">All Programmes</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="inductionStudentsTable" class="table table-striped table-bordered"
                                        style="width: 100%; font-size: 12px;">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th style="display: none;">Full Name</th>
                                                <th>Date of Birth</th>
                                                <th style="display: none;">NIC</th>
                                                <th>NIC</th>
                                                <th style="display: none;">Programme</th>
                                                <th>Programme</th>
                                                <th style="display: none;">Contact No</th>
                                                <th>Contact No</th>
                                                <th>Fees</th>
                                                <th>Action</th>
                                                <th>Pack Collected</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentsTableBody">
                                            <!-- Data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <script>
        $(document).ready(function () {
            // Initialize Select2
            $('.select2').select2({
                placeholder: 'Select Programme',
                allowClear: true,
                width: '100%'
            });

            // Load programmes
            loadProgrammes();

            // Load students data
            loadStudents();

            // Handle filter change
            $('#programmeFilter').on('change', function () {
                loadStudents($(this).val());
            });

            function loadProgrammes() {
                $.ajax({
                    url: 'fetch_induction_programmes.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function (response) {
                        let select = $('#programmeFilter');
                        if (response && response.length > 0) {
                            response.forEach(function (prog) {
                                select.append(new Option(prog, prog));
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading programmes:', error);
                    }
                });
            }

            function loadStudents(programme) {
                // Handle null/undefined programme
                programme = programme || '';

                $.ajax({
                    url: 'fetch_induction_students_for_update.php',
                    type: 'GET',
                    data: { programme: programme },
                    dataType: 'json',
                    success: function (response) {
                        // Destroy DataTable BEFORE modifying the DOM
                        if ($.fn.DataTable.isDataTable('#inductionStudentsTable')) {
                            $('#inductionStudentsTable').DataTable().destroy();
                        }

                        let tbody = $('#studentsTableBody');
                        tbody.empty();

                        if (response.data && response.data.length > 0) {
                            response.data.forEach(function (student) {
                                // Get the fees status from the response (already normalized to 'Paid' or 'Unpaid')
                                let feesStatus = (student.fees || '').trim();

                                // Normalize to ensure exact match (case-insensitive check, but use exact value)
                                let isPaid = false;
                                if (feesStatus.toLowerCase() === 'paid') {
                                    isPaid = true;
                                }

                                // Set selected attribute based on the fees status
                                let paidSelected = isPaid ? 'selected="selected"' : '';
                                let unpaidSelected = !isPaid ? 'selected="selected"' : '';

                                // Pack collected status
                                let packCollected = student.pack_collected || 0;
                                let packCollectedHtml = '';
                                if (packCollected == 1) {
                                    packCollectedHtml = `<span class="badge bg-success"><i class="fas fa-check"></i> Collected</span>`;
                                } else {
                                    packCollectedHtml = `<button class="btn btn-sm btn-info pack-collected-btn" data-id="${student.id}">
                                        <i class="fas fa-box"></i> Mark Collected
                                    </button>`;
                                }

                                let row = `
                                    <tr data-id="${student.id}">
                                        <td style="display: none;">${student.full_name}</td>
                                        <td style="display: none;">${student.nic}</td>
                                        <td style="display: none;">${student.programme}</td>
                                        <td style="display: none;">${student.contact_no}</td>
                                        <td><input type="text" class="form-control form-control-sm full-name" value="${student.full_name}" style="min-width: 150px;"></td>
                                        <td><input type="text" class="form-control form-control-sm date-of-birth" value="${student.date_of_birth}" placeholder="YYYY-MM-DD" style="min-width: 130px;"></td>
                                        <td><input type="text" class="form-control form-control-sm nic" value="${student.nic}" style="min-width: 120px;"></td>
                                        <td><input type="text" class="form-control form-control-sm programme" value="${student.programme}" style="min-width: 120px;"></td>
                                        <td><input type="text" class="form-control form-control-sm contact-no" value="${student.contact_no}" style="min-width: 120px;"></td>
                                        <td>
                                            <select class="form-select form-select-sm fees-dropdown" data-id="${student.id}" data-original="${feesStatus}" style="min-width: 100px;">
                                                <option value="Paid" ${paidSelected}>Paid</option>
                                                <option value="Unpaid" ${unpaidSelected}>Unpaid</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary update-btn" data-id="${student.id}">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </td>
                                        <td>
                                            ${packCollectedHtml}
                                        </td>
                                    </tr>
                                `;
                                tbody.append(row);
                            });

                            // Re-initialize DataTable
                            $('#inductionStudentsTable').DataTable({
                                "pageLength": 250,
                                "order": [
                                    [4, "asc"] // Sorted by editable Full Name column (index 4)
                                ]
                            });
                        } else {
                            tbody.append('<tr><td colspan="12" class="text-center">No students found</td></tr>');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error loading students:', error);
                        $('#studentsTableBody').html('<tr><td colspan="12" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
            }

            // Handle update button click
            $(document).on('click', '.update-btn', function () {
                let id = $(this).data('id');
                let row = $(this).closest('tr');

                // Collect values from inputs
                let fullName = row.find('.full-name').val();
                let dateOfBirth = row.find('.date-of-birth').val();
                let nic = row.find('.nic').val();
                let programme = row.find('.programme').val();
                let contactNo = row.find('.contact-no').val();

                let dropdown = row.find('.fees-dropdown');
                let fees = dropdown.val();
                let originalFees = dropdown.data('original'); // Get original value from database

                // Disable button during update
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: 'update_induction_student_fees.php',
                    type: 'POST',
                    data: {
                        id: id,
                        full_name: fullName,
                        date_of_birth: dateOfBirth,
                        nic: nic,
                        programme: programme,
                        contact_no: contactNo,
                        fees: fees
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Update the original value to the new value
                            dropdown.data('original', fees);

                            // Show success message
                            btn.html('<i class="fas fa-check"></i> Updated').removeClass('btn-primary').addClass('btn-success');

                            // Reset button after 2 seconds
                            setTimeout(function () {
                                btn.html('<i class="fas fa-save"></i> Update').removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                            }, 2000);

                            // Show toast/alert
                            showAlert('Student details updated successfully!', 'success');
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function (xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                        showAlert('Error updating student details. Please try again.', 'danger');
                        console.error('Update error:', error);
                    }
                });
            });

            // Handle pack collected button click
            $(document).on('click', '.pack-collected-btn', function () {
                let id = $(this).data('id');
                let btn = $(this);

                // Disable button during update
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: 'update_pack_collected.php',
                    type: 'POST',
                    data: {
                        id: id,
                        pack_collected: 1
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Replace button with success badge
                            btn.closest('td').html('<span class="badge bg-success"><i class="fas fa-check"></i> Collected</span>');
                            showAlert('Pack collected status updated successfully!', 'success');
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-box"></i> Mark Collected');
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function (xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-box"></i> Mark Collected');
                        showAlert('Error updating pack collected status. Please try again.', 'danger');
                        console.error('Update error:', error);
                    }
                });
            });

            // Function to show alert messages
            function showAlert(message, type) {
                let alertClass = 'alert-info';
                if (type === 'success') {
                    alertClass = 'alert-success';
                } else if (type === 'danger') {
                    alertClass = 'alert-danger';
                } else if (type === 'info') {
                    alertClass = 'alert-info';
                }

                let alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $('body').append(alertHtml);

                // Auto remove after 3 seconds
                setTimeout(function () {
                    $('.alert').fadeOut(function () {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    </script>

</div>
</body>

</html>