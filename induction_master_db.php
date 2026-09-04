<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}
require_once 'PermissionChecking.php';

?>
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Induction Master File (From DB)</h4>
                </div>


                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>

                    <!-- Program-Batch Management Card -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between"
                                    style="height: 60px;">
                                    <div class="d-flex align-items-center">
                                        <span
                                            class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                            style="width: 30px; height: 30px;">
                                            <i class="fas fa-cog"></i>
                                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                        <h6 class="mb-0 me-2">Program-Batch Management</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3" id="programBatchStatusTabs">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#" data-status="active">
                                                Active <span class="badge bg-success ms-1" id="activeCountBadge">0</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#" data-status="inactive">
                                                Inactive <span class="badge bg-secondary ms-1" id="inactiveCountBadge">0</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="table-responsive">
                                        <table id="programBatchTable" class="table table-striped table-bordered"
                                            style="width: 100%; font-size: 12px;">
                                            <thead>
                                                <tr>
                                                    <th>Programme</th>
                                                    <th>Batch</th>
                                                    <th>Status</th>
                                                    <th>Created At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="programBatchTableBody">
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3 gap-2">
                                        <button id="initializeProgramBatchBtn" class="btn btn-sm btn-warning">
                                            <i class="fas fa-database"></i> Initialize
                                        </button>
                                        <button id="refreshProgramBatchBtn" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-sync"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

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
                                <div class="d-flex align-items-center" style="width: 500px;">
                                    <select id="programmeFilter" class="form-select form-select-sm select2">
                                        <option value="">All Programmes - Batches</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="inductionStudentsTable" class="table table-striped table-bordered"
                                        style="width: 100%; font-size: 12px;">
                                        <thead>
                                            <tr>
                                                <th style="display: none;">-</th>
                                                <th style="display: none;">-</th>
                                                <th style="display: none;">-</th>
                                                <th style="display: none;">-</th>
                                                <th>Full Name</th>
                                                <th>NIC</th>
                                                <th>Programme - Batch</th>
                                                <th>Contact No</th>
                                                <th>Email</th>
                                                <th>Fees</th>
                                                <th>Attended</th>
                                                <th>Action</th>
                                                <th>Pack Collected</th>
                                            </tr>
                                        </thead>
                                        <tbody id="studentsTableBody">
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
        $(document).ready(function() {
            let programBatchData = [];
            let currentProgramBatchStatus = 'active';

            $('.select2').select2({
                placeholder: 'Select Programme',
                allowClear: true,
                width: '100%'
            });

            loadProgrammes();
            loadStudents();
            loadProgramBatchManagement();

            $('#programmeFilter').on('change', function() {
                loadStudents($(this).val());
            });

            $('#refreshProgramBatchBtn').on('click', function() {
                loadProgramBatchManagement();
            });

            $(document).on('click', '#programBatchStatusTabs .nav-link', function(e) {
                e.preventDefault();
                $('#programBatchStatusTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                currentProgramBatchStatus = $(this).data('status');
                renderProgramBatchTable(currentProgramBatchStatus);
            });

            $('#initializeProgramBatchBtn').on('click', function() {
                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Initializing...');

                $.ajax({
                    url: 'initialize_induction_active_table.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        btn.prop('disabled', false).html('<i class="fas fa-database"></i> Initialize');

                        if (response.success) {
                            showAlert(response.message, 'success');
                            loadProgramBatchManagement();
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-database"></i> Initialize');
                        showAlert('Error initializing. Please try again.', 'danger');
                        console.error('Initialize error:', error);
                    }
                });
            });

            function loadProgramBatchManagement() {
                $.ajax({
                    url: 'induction_DB_folder/fetch_induction_active_programs.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            programBatchData = response.data;
                        } else {
                            programBatchData = [];
                        }
                        renderProgramBatchTable(currentProgramBatchStatus);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading program-batch data:', error);
                        $('#programBatchTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
            }

            function renderProgramBatchTable(status) {
                if ($.fn.DataTable.isDataTable('#programBatchTable')) {
                    $('#programBatchTable').DataTable().destroy();
                }

                let tbody = $('#programBatchTableBody');
                tbody.empty();

                let activeCount = programBatchData.filter(item => item.status === 'active').length;
                let inactiveCount = programBatchData.filter(item => item.status === 'inactive').length;
                $('#activeCountBadge').text(activeCount);
                $('#inactiveCountBadge').text(inactiveCount);

                let filteredData = programBatchData.filter(item => item.status === status);

                if (filteredData.length > 0) {
                    filteredData.forEach(function(item) {
                        let activeSelected = item.status === 'active' ? 'selected' : '';
                        let inactiveSelected = item.status === 'inactive' ? 'selected' : '';

                        let row = `
                            <tr data-id="${item.id}" data-program-id="${item.program_id}" data-batch-id="${item.batch_id}">
                                <td>${item.program_name}</td>
                                <td>${item.batch_name}</td>
                                <td>
                                    <select class="form-select form-select-sm status-dropdown" data-id="${item.id}" data-program-id="${item.program_id}" data-batch-id="${item.batch_id}" style="min-width: 120px;">
                                        <option value="active" ${activeSelected}>Active</option>
                                        <option value="inactive" ${inactiveSelected}>Inactive</option>
                                    </select>
                                </td>
                                <td>${item.created_at ? item.created_at : '-'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary save-status-btn" data-id="${item.id}" data-program-id="${item.program_id}" data-batch-id="${item.batch_id}">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });

                    $('#programBatchTable').DataTable({
                        "pageLength": 250,
                        "order": [] // Disable default ordering as we sort server-side
                    });
                } else {
                    tbody.append('<tr><td colspan="5" class="text-center">No ' + status + ' program-batch combinations found</td></tr>');
                }
            }

            $(document).on('click', '.save-status-btn', function() {
                let id = $(this).data('id');
                let programId = $(this).data('program-id');
                let batchId = $(this).data('batch-id');
                let row = $(this).closest('tr');
                let status = row.find('.status-dropdown').val();
                let btn = $(this);

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: 'induction_DB_folder/update_induction_active_status.php',
                    type: 'POST',
                    data: {
                        id: id,
                        program_id: programId,
                        batch_id: batchId,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // If a new id was returned, update the row and buttons
                            if (response.id) {
                                row.data('id', response.id);
                                row.find('.status-dropdown').data('id', response.id);
                                row.find('.save-status-btn').data('id', response.id);
                            }

                            // Keep the local dataset in sync so tab counts/filtering stay correct
                            let dataItem = programBatchData.find(function(item) {
                                return String(item.program_id) === String(programId) && String(item.batch_id) === String(batchId);
                            });
                            if (dataItem) {
                                dataItem.status = status;
                                if (response.id) {
                                    dataItem.id = response.id;
                                }
                            }

                            btn.html('<i class="fas fa-check"></i> Saved').removeClass('btn-primary').addClass('btn-success');
                            setTimeout(function() {
                                // Re-render so the row moves to the correct tab (Active/Inactive)
                                renderProgramBatchTable(currentProgramBatchStatus);
                            }, 600);

                            showAlert('Status updated successfully!', 'success');
                            // Reload the students to apply the new status
                            loadStudents($('#programmeFilter').val());
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save');
                        showAlert('Error updating status. Please try again.', 'danger');
                        console.error('Update error:', error);
                    }
                });
            });

            function loadProgrammes() {
                $.ajax({
                    url: 'induction_DB_folder/fetch_induction_db_programmes.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        let select = $('#programmeFilter');
                        if (response && response.length > 0) {
                            response.forEach(function(prog) {
                                select.append(new Option(prog, prog));
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading programmes:', error);
                    }
                });
            }

            function loadStudents(programme) {
                programme = programme || '';

                $.ajax({
                    url: 'induction_DB_folder/fetch_induction_db_students_for_update.php',
                    type: 'GET',
                    data: {
                        programme: programme
                    },
                    dataType: 'json',
                    success: function(response) {
                        if ($.fn.DataTable.isDataTable('#inductionStudentsTable')) {
                            $('#inductionStudentsTable').DataTable().destroy();
                        }

                        let tbody = $('#studentsTableBody');
                        tbody.empty();

                        if (response.data && response.data.length > 0) {
                            response.data.forEach(function(student) {
                                let feesStatus = (student.fees_paid || '').trim();
                                let isPaid = feesStatus.toLowerCase() === 'paid';
                                let paidSelected = isPaid ? 'selected="selected"' : '';
                                let unpaidSelected = !isPaid ? 'selected="selected"' : '';

                                let attendedStatus = (student.attended || '').trim();
                                let isAttended = attendedStatus.toLowerCase() === 'yes';
                                let yesAttendedSelected = isAttended ? 'selected="selected"' : '';
                                let noAttendedSelected = !isAttended ? 'selected="selected"' : '';

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
                                    <tr data-id="${student.id}" data-allocate-id="${student.allocate_programme_id}" data-student-code="${student.student_code}">
                                        <td style="display: none;">${student.email}</td>
                                        <td style="display: none;">${student.full_name}</td>
                                        <td style="display: none;">${student.nic}</td>
                                        <td style="display: none;">${student.contact_no}</td>
                                        <td><input type="text" class="form-control form-control-sm full-name" value="${student.full_name}" style="min-width: 150px;"></td>
                                        <td><input type="text" class="form-control form-control-sm nic" value="${student.nic}" style="min-width: 120px;"></td>
                                        <td><input type="text" class="form-control form-control-sm programme-batch" value="${student.programme} - ${student.batch}" style="min-width: 350px;" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm contact-no" value="${student.contact_no}" style="min-width: 120px;" readonly></td>   
                                        <td><input type="text" class="form-control form-control-sm email" value="${student.email}" style="min-width: 180px;" readonly></td>
                                        <td>
                                            <select class="form-select form-select-sm fees-dropdown" data-id="${student.id}" data-original="${feesStatus}" style="min-width: 100px;">
                                                <option value="Paid" ${paidSelected}>Paid</option>
                                                <option value="Unpaid" ${unpaidSelected}>Unpaid</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm attended-dropdown" data-id="${student.id}" data-original="${attendedStatus}" style="min-width: 100px;">
                                                <option value="Yes" ${yesAttendedSelected}>Yes</option>
                                                <option value="No" ${noAttendedSelected}>No</option>
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

                            $('#inductionStudentsTable').DataTable({
                                "pageLength": 250,
                                "order": [
                                    [0, "asc"]
                                ]
                            });
                        } else {
                            tbody.append('<tr><td colspan="9" class="text-center">No students found</td></tr>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading students:', error);
                        $('#studentsTableBody').html('<tr><td colspan="9" class="text-center text-danger">Error loading data</td></tr>');
                    }
                });
            }

            $(document).on('click', '.update-btn', function() {
                let id = $(this).data('id');
                let row = $(this).closest('tr');
                let studentCode = row.data('student-code');

                let fullName = row.find('.full-name').val();
                let nic = row.find('.nic').val();
                let contactNo = row.find('.contact-no').val();

                let feesDropdown = row.find('.fees-dropdown');
                let fees = feesDropdown.val();
                let originalFees = feesDropdown.data('original');

                let attendedDropdown = row.find('.attended-dropdown');
                let attended = attendedDropdown.val();
                let originalAttended = attendedDropdown.data('original');

                let btn = $(this);
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: 'induction_DB_folder/update_induction_db_student.php',
                    type: 'POST',
                    data: {
                        id: id,
                        student_code: studentCode,
                        full_name: fullName,
                        nic: nic,
                        contact_no: contactNo,
                        fees_paid: fees,
                        attended: attended
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            feesDropdown.data('original', fees);
                            attendedDropdown.data('original', attended);

                            btn.html('<i class="fas fa-check"></i> Updated').removeClass('btn-primary').addClass('btn-success');

                            setTimeout(function() {
                                btn.html('<i class="fas fa-save"></i> Update').removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                            }, 2000);

                            showAlert('Student details updated successfully!', 'success');
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                        showAlert('Error updating student details. Please try again.', 'danger');
                        console.error('Update error:', error);
                    }
                });
            });

            $(document).on('click', '.pack-collected-btn', function() {
                let id = $(this).data('id');
                let btn = $(this);

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: 'induction_DB_folder/update_induction_db_pack_collected.php',
                    type: 'POST',
                    data: {
                        id: id,
                        pack_collected: 1
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            btn.closest('td').html('<span class="badge bg-success"><i class="fas fa-check"></i> Collected</span>');
                            showAlert('Pack collected status updated successfully!', 'success');
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-box"></i> Mark Collected');
                            showAlert('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html('<i class="fas fa-box"></i> Mark Collected');
                        showAlert('Error updating pack collected status. Please try again.', 'danger');
                        console.error('Update error:', error);
                    }
                });
            });

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

                setTimeout(function() {
                    $('.alert').fadeOut(function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        });
    </script>
</div>
</body>

</html>