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


// Check which tables exist in the database
$tablesExist = [
    'program_table' => false,
    'batch_table' => false,
    'module_table' => false
];

$checkTablesQuery = "SHOW TABLES";
$tablesResult = mysqli_query($conn, $checkTablesQuery);

if ($tablesResult) {
    while ($table = mysqli_fetch_array($tablesResult)) {
        $tableName = $table[0];
        if ($tableName === 'program_table') $tablesExist['program_table'] = true;
        if ($tableName === 'batch_table') $tablesExist['batch_table'] = true;
        if ($tableName === 'module_table') $tablesExist['module_table'] = true;
    }
}

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
                    <h4 class="h4 mb-0 text-gray-800">Special Class Messages</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-8 offset-md-2">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex align-items-center">
                                <i class="bi bi-chat-left-text me-2"></i>
                                <span>Class</span>
                            </div>
                            <div class="card-body">
                                <form id="specialClassForm">
                                    <!-- Hidden field for edit mode -->
                                    <input type="hidden" name="edit_id" id="edit_id">
                                    
                                    <div class="row mb-3">
                                        <label for="programme" class="col-sm-3 col-md-2 form-label text-md-end">Programme:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <select class="form-select" id="programme" name="programme" required>
                                                <option value="" selected disabled>Select Programme</option>
                                                <?php
                                                // Fetch programmes from program_table if it exists
                                                if ($tablesExist['program_table']) {
                                                    $programQuery = "SELECT program_code, program_name FROM program_table ORDER BY program_name";
                                                    $programResult = mysqli_query($conn, $programQuery);

                                                    if ($programResult && mysqli_num_rows($programResult) > 0) {
                                                        while ($row = mysqli_fetch_assoc($programResult)) {
                                                            echo "<option value='" . $row['program_code'] . "'>" . $row['program_name'] . "</option>";
                                                        }
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="batch" class="col-sm-3 col-md-2 form-label text-md-end">Batch:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <select class="form-select" id="batch" name="batch" required>
                                                <option value="" selected disabled>Select Batch</option>
                                                <!-- Batches will be populated via AJAX when programme is selected -->
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="module" class="col-sm-3 col-md-2 form-label text-md-end">Module:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <select class="form-select" id="module" name="module" required>
                                                <option value="" selected disabled>Select Module</option>
                                                <!-- Modules will be populated via AJAX when batch is selected -->
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="dateTime" class="col-sm-3 col-md-2 form-label text-md-end">Date and Time:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <div class="input-group">
                                                <input type="text" class="form-control datepicker" id="dateTime" name="dateTime" placeholder="mm/dd/yyyy" required>
                                                <input type="time" class="form-control" id="timeInput" name="timeInput">
                                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="link" class="col-sm-3 col-md-2 form-label text-md-end">Link:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <input type="url" class="form-control" id="link" name="link" placeholder="https://example.com/meeting-link">
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="mailSubject" class="col-sm-3 col-md-2 form-label text-md-end">Mail Subject:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <input type="text" class="form-control" id="mailSubject" name="mailSubject" placeholder="Enter email subject" required>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <label for="description" class="col-sm-3 col-md-2 form-label text-md-end">Description:</label>
                                        <div class="col-sm-9 col-md-10">
                                            <div id="toolbar">
                                                <div class="btn-group mb-2">
                                                    <button type="button" class="btn btn-outline-secondary" data-command="bold">
                                                        <i class="bi bi-type-bold"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" data-command="italic">
                                                        <i class="bi bi-type-italic"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" data-command="underline">
                                                        <i class="bi bi-type-underline"></i>
                                                    </button>
                                                </div>
                                                <div class="btn-group mb-2">
                                                    <button type="button" class="btn btn-outline-secondary" data-command="insertUnorderedList">
                                                        <i class="bi bi-list-ul"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" data-command="insertOrderedList">
                                                        <i class="bi bi-list-ol"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="form-control" id="editor" contenteditable="true" style="min-height: 200px; overflow-y: auto;"></div>
                                            <textarea id="description" name="description" style="display: none;"></textarea>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-9 col-md-10 offset-sm-3 offset-md-2">
                                            <button type="submit" class="btn btn-success" id="saveBtn">
                                                <i class="bi bi-save"></i> Save
                                            </button>
                                            <button type="button" class="btn btn-secondary ms-2" id="cancelBtn">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Special Class Messages Table Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Special Class Messages List</h5>
                                <div class="input-group" style="max-width: 300px;">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="specialClassTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Programme</th>
                                                <th>Batch</th>
                                                <th>Module</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Subject</th>
                                                <th>Link</th>
                                                <th>Description</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Build the query based on which tables exist
                                            $query = "SELECT scm.* ";
                                            
                                            if ($tablesExist['program_table']) {
                                                $query .= ", p.program_name ";
                                            } else {
                                                $query .= ", scm.program_code as program_name ";
                                            }
                                            
                                            if ($tablesExist['batch_table']) {
                                                $query .= ", b.batch_name ";
                                            } else {
                                                $query .= ", CONCAT('Batch ID: ', scm.batch_id) as batch_name ";
                                            }
                                            
                                            if ($tablesExist['module_table']) {
                                                $query .= ", m.module_name ";
                                            } else {
                                                $query .= ", CONCAT('Module ID: ', scm.module_id) as module_name ";
                                            }
                                            
                                            $query .= " FROM special_class_messages scm ";
                                            
                                            if ($tablesExist['program_table']) {
                                                $query .= " LEFT JOIN program_table p ON scm.program_code = p.program_code ";
                                            }
                                            
                                            if ($tablesExist['batch_table']) {
                                                $query .= " LEFT JOIN batch_table b ON scm.batch_id = b.id ";
                                            }
                                            
                                            if ($tablesExist['module_table']) {
                                                $query .= " LEFT JOIN module_table m ON scm.module_id = m.id ";
                                            }
                                            
                                            $query .= " ORDER BY scm.created_at DESC";
                                            
                                            $result = mysqli_query($conn, $query);
                                            
                                            if ($result && mysqli_num_rows($result) > 0) {
                                                $count = 1;
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                    // Format date and time
                                                    $formattedDate = date('d-m-Y', strtotime($row['class_date']));
                                                    $formattedTime = date('h:i A', strtotime($row['class_time']));
                                                    $createdAt = date('d-m-Y h:i A', strtotime($row['created_at']));
                                                    
                                                    // Strip HTML tags from description for table view
                                                    $plainDescription = strip_tags($row['description']);
                                                    $truncatedDescription = (strlen($plainDescription) > 50) ? substr($plainDescription, 0, 50) . '...' : $plainDescription;
                                                    
                                                    echo "<tr>";
                                                    echo "<td>{$count}</td>";
                                                    echo "<td>{$row['program_name']}</td>";
                                                    echo "<td>{$row['batch_name']}</td>";
                                                    echo "<td>{$row['module_name']}</td>";
                                                    echo "<td>{$formattedDate}</td>";
                                                    echo "<td>{$formattedTime}</td>";
                                                    echo "<td>" . (strlen($row['mail_subject']) > 30 ? substr($row['mail_subject'], 0, 30) . '...' : $row['mail_subject']) . "</td>";
                                                    
                                                    // Link column
                                                    if (!empty($row['link'])) {
                                                        echo "<td><a href='{$row['link']}' target='_blank' class='text-primary'><i class='bi bi-link-45deg'></i> Link</a></td>";
                                                    } else {
                                                        echo "<td><span class='text-muted'>No link</span></td>";
                                                    }
                                                    
                                                    // Description column
                                                    echo "<td>{$truncatedDescription}</td>";
                                                    
                                                    echo "<td>{$createdAt}</td>";
                                                   
                                                    echo "</tr>";
                                                    $count++;
                                                }
                                            } else {
                                                echo "<tr><td colspan='11' class='text-center'>No special class messages found</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Content -->
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailsModalLabel">Special Class Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Programme:</strong> <span id="viewProgramme"></span></p>
                        <p><strong>Batch:</strong> <span id="viewBatch"></span></p>
                        <p><strong>Module:</strong> <span id="viewModule"></span></p>
                        <p><strong>Date:</strong> <span id="viewDate"></span></p>
                        <p><strong>Time:</strong> <span id="viewTime"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Mail Subject:</strong> <span id="viewSubject"></span></p>
                        <p><strong>Link:</strong> <a href="#" id="viewLink" target="_blank"></a></p>
                        <p><strong>Created At:</strong> <span id="viewCreatedAt"></span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">Description</h6>
                        <div id="viewDescription" class="p-3 bg-light rounded"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this special class message?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize datepicker
        $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Rich text editor functionality
        $('#toolbar button').click(function(e) {
            e.preventDefault();
            var command = $(this).data('command');
            document.execCommand(command, false, null);
            $('#editor').focus();
        });

        // Transfer content from editor to hidden textarea before form submission
        $('#specialClassForm').submit(function() {
            $('#description').val($('#editor').html());
        });

        // Populate batches when programme is selected
        $('#programme').change(function() {
            var programmeId = $(this).val();
            if (programmeId) {
                $.ajax({
                    url: 'specialClassFolder/get_batches.php',
                    type: 'POST',
                    data: {
                        programme_id: programmeId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#batch').empty();
                        $('#batch').append('<option value="" selected disabled>Select Batch</option>');
                        if (data && data.length > 0) {
                            $.each(data, function(key, value) {
                                $('#batch').append('<option value="' + value.id + '">' + value.batch_name + '</option>');
                            });
                        } else {
                            // If no batches found, allow manual entry
                            $('#batch').append('<option value="manual">Enter Manually</option>');
                        }
                        // Reset module dropdown
                        $('#module').empty();
                        $('#module').append('<option value="" selected disabled>Select Module</option>');
                    },
                    error: function() {
                        // If AJAX fails, allow manual entry
                        $('#batch').empty();
                        $('#batch').append('<option value="" selected disabled>Select Batch</option>');
                        $('#batch').append('<option value="manual">Enter Manually</option>');
                    }
                });
            } else {
                $('#batch').empty();
                $('#batch').append('<option value="" selected disabled>Select Batch</option>');
                $('#module').empty();
                $('#module').append('<option value="" selected disabled>Select Module</option>');
            }
        });

        // Populate modules when batch is selected
        $('#batch').change(function() {
            var programmeId = $('#programme').val();
            var batchId = $(this).val();
            if (programmeId && batchId && batchId !== 'manual') {
                $.ajax({
                    url: 'specialClassFolder/get_modules.php',
                    type: 'POST',
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#module').empty();
                        $('#module').append('<option value="" selected disabled>Select Module</option>');
                        if (data && data.length > 0) {
                            $.each(data, function(key, value) {
                                $('#module').append('<option value="' + value.id + '">' + value.module_name + '</option>');
                            });
                        } else {
                            // If no modules found, allow manual entry
                            $('#module').append('<option value="manual">Enter Manually</option>');
                        }
                    },
                    error: function() {
                        // If AJAX fails, allow manual entry
                        $('#module').empty();
                        $('#module').append('<option value="" selected disabled>Select Module</option>');
                        $('#module').append('<option value="manual">Enter Manually</option>');
                    }
                });
            } else {
                $('#module').empty();
                $('#module').append('<option value="" selected disabled>Select Module</option>');
                if (batchId === 'manual') {
                    $('#module').append('<option value="manual">Enter Manually</option>');
                }
            }
        });

        // Form submission
        $('#specialClassForm').submit(function(e) {
            e.preventDefault();
            $('#description').val($('#editor').html()); // Copy rich text to hidden field

            $.ajax({
                url: 'specialClassFolder/save_special_class.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        
                        // Reset form
                        resetForm();
                        
                        // Reload the page to refresh the table
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while saving the data.");
                }
            });
        });

        // View details button click
        $(document).on('click', '.view-btn', function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: 'specialClassFolder/get_special_class_details.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var data = response.data;
                        
                        // Populate modal with data
                        $('#viewProgramme').text(data.program_name || data.program_code);
                        $('#viewBatch').text(data.batch_name || 'Batch ID: ' + data.batch_id);
                        $('#viewModule').text(data.module_name || 'Module ID: ' + data.module_id);
                        $('#viewDate').text(data.formatted_date);
                        $('#viewTime').text(data.formatted_time);
                        $('#viewSubject').text(data.mail_subject);
                        
                        if (data.link) {
                            $('#viewLink').text(data.link).attr('href', data.link);
                        } else {
                            $('#viewLink').text('No link provided').removeAttr('href');
                        }
                        
                        $('#viewCreatedAt').text(data.created_at);
                        $('#viewDescription').html(data.description);
                        
                        // Show modal
                        $('#viewDetailsModal').modal('show');
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while fetching the details.");
                }
            });
        });

        // Edit button click
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: 'specialClassFolder/get_special_class_details.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var data = response.data;
                        
                        // Set edit_id
                        $('#edit_id').val(data.id);
                        
                        // Set programme and trigger change to load batches
                        $('#programme').val(data.program_code).trigger('change');
                        
                        // Set batch after a delay to ensure batches are loaded
                        setTimeout(function() {
                            $('#batch').val(data.batch_id).trigger('change');
                            
                            // Set module after another delay
                            setTimeout(function() {
                                $('#module').val(data.module_id);
                                
                                // Set other fields
                                $('#dateTime').val(data.class_date);
                                $('#timeInput').val(data.class_time);
                                $('#link').val(data.link);
                                $('#mailSubject').val(data.mail_subject);
                                $('#editor').html(data.description);
                                
                                // Change button text
                                $('#saveBtn').html('<i class="bi bi-save"></i> Update');
                                
                                // Scroll to form
                                $('html, body').animate({
                                    scrollTop: $("#specialClassForm").offset().top - 100
                                }, 500);
                            }, 500);
                        }, 500);
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while fetching the details.");
                }
            });
        });

        // Delete button click
        $(document).on('click', '.delete-btn', function() {
            var id = $(this).data('id');
            $('#confirmDelete').data('id', id);
            $('#deleteConfirmModal').modal('show');
        });

        // Confirm delete button click
        $('#confirmDelete').click(function() {
            var id = $(this).data('id');
            
            $.ajax({
                url: 'specialClassFolder/delete_special_class.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#deleteConfirmModal').modal('hide');
                        alert(response.message);
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred while deleting the record.");
                }
            });
        });

        // Cancel button click
        $('#cancelBtn').click(function() {
            resetForm();
        });

        // Search functionality
        $('#searchInput').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#specialClassTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Function to reset form
        function resetForm() {
            $('#specialClassForm')[0].reset();
            $('#editor').html('');
            $('#edit_id').val('');
            $('#saveBtn').html('<i class="bi bi-save"></i> Save');
        }
    });
</script>

</body>

</html>
