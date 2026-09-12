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


$userId = $_SESSION['user_id'];
// Fetching sub_list_value from the user_permission table for the logged-in user
$query = "SELECT sub_list_value FROM user_permission WHERE user_id = '$userId'";
$result_for_up = mysqli_query($conn, $query);
// Initialize an array to store sub_list_values
$subListValues = [];

if ($result_for_up) {
    while ($row = mysqli_fetch_assoc($result_for_up)) {
        $subListValues[] = $row['sub_list_value'];
    }
} else {
    echo "Error fetching permissions: " . mysqli_error($conn); // Corrected connection variable
}

// Initialize variables for edit mode
$isEdit = false;
$assessment = null;

// Check if we're in edit mode
if (isset($_GET['edit_id'])) {
    $isEdit = true;
    $id = $_GET['edit_id'];

    // Fetch assessment data
    $query = "SELECT * FROM save_assessment_document_send WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();
}

// Fetch main components if in edit mode
if ($isEdit) {
    // Fetch main components based on the module_id
    $query = "SELECT ac.main_component_id, a.as_main_component_name 
              FROM allocated_components ac
              INNER JOIN assignment_components a ON ac.main_component_id = a.id
              WHERE ac.module_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assessment['module_id']);
    $stmt->execute();
    $mainComponents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch sub components based on the main_component_id
    $query = "SELECT DISTINCT sc.id, sc.sub_component_name 
              FROM sub_assign_components sc
              INNER JOIN allocated_components ac ON sc.id = ac.sub_component_id
              WHERE ac.main_component_id = ? AND ac.module_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $assessment['main_component_id'], $assessment['module_id']);
    $stmt->execute();
    $subComponents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("nav.php"); ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column ">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <?php include("includes/topnav.php"); ?>

                <!-- Begin Page Content -->
                <div class="p-3" style="font-size: 14px;">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">
                            <?php echo $isEdit ? 'Edit Assesment Document Send' : 'Assesment Document Send'; ?>
                        </h4>
                    </div>

                    <!-- Form Section -->
                    <form action="transection_exams/save_assessment_document_send.php" method="POST" enctype="multipart/form-data">
                        <?php if ($isEdit && is_array($assessment) && isset($assessment['id'])): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($assessment['id']); ?>">
                        <?php endif; ?>

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

                                            <div class="col-md-3">
                                                <label for="year">Year</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <input type="text" name="year_id" style="font-size: 12px;" id="year" class="form-control"
                                                        required readonly
                                                        value="<?php echo $isEdit && isset($assessment['year_id']) ? htmlspecialchars($assessment['year_id']) : ''; ?>">
                                                </div>
                                            </div>

                                            <!-- ------------------------------------------------- -->
                                            <!-- ------------------------------------------------- -->

                                            <div class="col-md-3">
                                                <label for="semester">Semester</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <input type="text" name="semester_id" style="font-size: 12px;" id="semester" class="form-control"
                                                        required readonly
                                                        value="<?php echo $isEdit && isset($assessment['semester_id']) ? htmlspecialchars($assessment['semester_id']) : ''; ?>">
                                                </div>
                                            </div>
                                            <!-- ------------------------------------------------- -->
                                            <div class="col-md-3">
                                                <label for="mainComponent">Main Components</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <?php if ($isEdit): ?>
                                                        <select name="main_component_id" id="mainComponent" class="form-control select2" required>
                                                            <option value="">Select Main Component</option>
                                                            <?php foreach ($mainComponents as $component): ?>
                                                                <option value="<?php echo htmlspecialchars($component['main_component_id']); ?>" <?php echo (isset($assessment['main_component_id']) && $component['main_component_id'] == $assessment['main_component_id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($component['as_main_component_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php else: ?>
                                                        <select name="main_component_id" id="mainComponent" class="form-control select2" required>
                                                            <option value="">Select Main Component</option>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <!-- ------------------------------------------------- -->
                                            <!-- ------------------------------------------------- -->
                                            <div class="col-md-3">
                                                <label for="subComponent">Sub Components</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <?php if ($isEdit): ?>
                                                        <select name="sub_component_id" id="subComponent" class="form-control select2">
                                                            <option value="">Select Sub Component</option>
                                                            <?php foreach ($subComponents as $subComponent): ?>
                                                                <option value="<?php echo htmlspecialchars($subComponent['id']); ?>" <?php echo (isset($assessment['sub_component_id']) && $subComponent['id'] == $assessment['sub_component_id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($subComponent['sub_component_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    <?php else: ?>
                                                        <select name="sub_component_id" id="subComponent" class="form-control select2">
                                                            <option value="">Select Sub Component</option>
                                                        </select>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- ------------------------------------------------- -->
                                            <!-- ------------------------------------------------- -->
                                            <div class="col-md-3">
                                                <label for="subject_body">Subject of Body</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <input type="text" name="subject_body" style="font-size: 12px;" id="subject_body" class="form-control"
                                                        value="<?php echo $isEdit ? htmlspecialchars($assessment['subject_body'] ?? '') : ''; ?>"
                                                        placeholder="Enter email subject">
                                                </div>
                                            </div>
                                            <!-- ------------------------------------------------- -->

                                            <div class="col-md-3">
                                                <label for="description">Description</label>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="form-group mb-3">
                                                    <textarea name="description" id="description" style="font-size: 12px;" rows="4"
                                                        class="form-control ckeditor"><?php echo $isEdit && isset($assessment['description']) ? htmlspecialchars($assessment['description']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                            <!-- ------------------------------------------------- -->
                                            <!-- ------------------------------------------------- -->

                                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <div class="col-md-3">
                                                    <label for="attachment<?php echo $i; ?>">Attachment <?php echo $i; ?></label>
                                                </div>
                                                <div class="col-md-9">
                                                    <div class="form-group mb-3">
                                                        <input type="file" style="font-size: 12px;" name="attachment_<?php echo $i; ?>"
                                                            id="attachment<?php echo $i; ?>" class="form-control">

                                                        <?php if ($isEdit && !empty($assessment["attachment_$i"])): ?>
                                                            <!-- Hidden field to track if this attachment should be removed -->
                                                            <input type="hidden" name="remove_attachment_<?php echo $i; ?>"
                                                                id="remove_attachment_<?php echo $i; ?>" value="0">

                                                            <div class="d-flex align-items-center mt-1">
                                                                <small class="text-muted mr-2">Current file:
                                                                    <a href="<?php echo 'uploads_exam_assessments/' . $assessment["attachment_$i"]; ?>" target="_blank">
                                                                        <?php echo basename($assessment["attachment_$i"]); ?>
                                                                    </a>
                                                                </small>
                                                                <button type="button" class="btn btn-sm btn-danger remove-attachment"
                                                                    data-attachment-id="<?php echo $i; ?>">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endfor; ?>

                                            <!-- ------------------------------------------------- -->
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-sm float-right">
                                            <?php echo $isEdit ? 'Update Assessment Document' : 'Save Assessment Document'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- -------------------- newly fetching 21.03.2025 -------------------------  -->
                <?php

                if ($role === 'super_admin') {
                    // Super admin: see all assessments
                    $query = "
                    SELECT a.*, 
                           p.program_name, 
                           b.batch_name, 
                           m.module_name, 
                           mc.as_main_component_name, 
                           sc.sub_component_name
                    FROM save_assessment_document_send a
                    LEFT JOIN program_table p ON a.programme_id = p.program_code 
                    LEFT JOIN batch_table b ON a.batch_id = b.id
                    LEFT JOIN modules m ON a.module_id = m.id
                    LEFT JOIN assignment_components mc ON a.main_component_id = mc.id
                    LEFT JOIN sub_assign_components sc ON a.sub_component_id = sc.id
                    ORDER BY a.created_at DESC
                ";
                } else {
                    // Other users: only assessments for allocated programs
                    $query = "
                    SELECT a.*, 
                           p.program_name, 
                           b.batch_name, 
                           m.module_name, 
                           mc.as_main_component_name, 
                           sc.sub_component_name
                    FROM save_assessment_document_send a
                    LEFT JOIN program_table p ON a.programme_id = p.program_code 
                    LEFT JOIN batch_table b ON a.batch_id = b.id
                    LEFT JOIN modules m ON a.module_id = m.id
                    LEFT JOIN assignment_components mc ON a.main_component_id = mc.id
                    LEFT JOIN sub_assign_components sc ON a.sub_component_id = sc.id
                    INNER JOIN program_allocation_user pau 
                           ON a.programme_id = pau.program_code
                    WHERE pau.user_id = $user_id
                    ORDER BY a.created_at DESC
                ";
                }

                $result = $conn->query($query);
                ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Academic Assessments</h6>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="assessmentTable" width="100%" cellspacing="0" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th style="width: 5px;">#</th>
                                        <th>Programme</th>
                                        <th>Batch</th>
                                        <th>Module</th>
                                        <th>Main Component</th>
                                        <th>Subject</th>
                                        <th>Attach 1</th>
                                        <th>Attach 2</th>
                                        <th>Attach 3</th>
                                        <th>Attach 4</th>
                                        <th>Mail Sent</th>
                                        <?php if (in_array('exams_asses_document_send_button', $subListValues) || in_array('exams_asses_document_edit_button', $subListValues)): ?>
                                            <th width="100px">Actions</th>
                                        <?php endif; ?>
                                        <?php if (in_array('exams_asses_document_send_button', $subListValues)): ?>
                                            <th width="100px">View</th>
                                        <?php endif; ?>
                                        <th>By</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $count = 1;
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo $count++; ?></td>
                                            <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['batch_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['module_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['as_main_component_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject_body'] ?? 'N/A'); ?></td>

                                            <!-- Attachments -->
                                            <?php
                                            for ($i = 1; $i <= 4; $i++) {
                                                $attachmentKey = "attachment_$i";
                                                if (!empty($row[$attachmentKey])) {
                                                    echo "<td><a href='./uploads_exam_assessments/" . htmlspecialchars($row[$attachmentKey]) . "' target='_blank'>View</a></td>";
                                                } else {
                                                    echo "<td>N/A</td>";
                                                }
                                            }
                                            ?>

                                            <td><?php echo htmlspecialchars($row['mail_sent']); ?></td>

                                            <!-- Actions -->
                                            <?php if (in_array('exams_asses_document_send_button', $subListValues) || in_array('exams_asses_document_edit_button', $subListValues)): ?>
                                                <td style="display: flex; justify-content: space-between;">
                                                    <?php if (in_array('exams_asses_document_send_button', $subListValues)): ?>
                                                        <a href="document_view_details_of_exams.php?id=<?= urlencode($row['id']) ?>" class="btn btn-success btn-sm" id="exams_asses_document_send_button" style="font-size: 11px;">Send</a>
                                                    <?php endif; ?>
                                                    <?php if (in_array('exams_asses_document_edit_button', $subListValues)): ?>
                                                        <a href="assesmentDocumentSend.php?edit_id=<?= urlencode($row['id']) ?>" class="btn btn-primary btn-sm" id="exams_asses_document_edit_button"><i class="fas fa-edit"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (in_array('exams_asses_document_send_button', $subListValues)): ?>
                                                        <a href="document_view_details_of_exams.php?id=<?= urlencode($row['id']) ?>" class="btn btn-success btn-sm" id="exams_asses_document_send_button" style="font-size: 11px;"><i class="fas fa-eye"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>

                                            <td><?php echo htmlspecialchars($row['entered_by']); ?></td>
                                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ---------------------------------------------  -->

                <!-- Bootstrap CDN -->
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


                <script src="vendor/datatables/jquery.dataTables.min.js"></script>
                <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
                <link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">

                <script>
                    $(document).ready(function() {
                        $('#assessmentTable').DataTable({
                            "paging": true, // Enable pagination
                            "searching": true, // Enable searching
                            "ordering": true, // Enable sorting
                            "order": [
                                [0, "asc"]
                            ], // Default order by first column (Type)
                            "pageLength": 100, // Show 10 entries per page
                            "language": {
                                "lengthMenu": "Show _MENU_ entries per page",
                                "zeroRecords": "No assessments Document found",
                                "info": "Showing page _PAGE_ of _PAGES_",
                                "infoEmpty": "No assessments available",
                                "infoFiltered": "(filtered from _MAX_ total records)"
                            }
                        });

                        // Attachment removal functionality
                        $('.remove-attachment').on('click', function() {
                            const attachmentId = $(this).data('attachment-id');
                            const confirmRemove = confirm('Are you sure you want to remove this attachment?');

                            if (confirmRemove) {
                                // Set the hidden input value to 1 to indicate removal
                                $(`#remove_attachment_${attachmentId}`).val('1');

                                // Hide the current file info and show a message
                                $(this).closest('div').html('<span class="text-danger">File will be removed upon update</span>');

                                // Clear the file input
                                $(`#attachment${attachmentId}`).val('');
                            }
                        });
                    });
                </script>


                <script>
                    function sendRowId(button, rowId) {
                        // Change the button text to "Sending..." and disable the button
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>&nbsp;Sending...';
                        button.classList.add('disabled'); // Add a disabled class for styling (optional)
                        button.style.pointerEvents = "none"; // Disable clicking

                        // AJAX call to send the row ID
                        $.ajax({
                            url: 'transection_exams/check_allocate.php', // The PHP file to handle the request
                            type: 'POST',
                            dataType: 'json', // Expect a JSON response
                            data: {
                                id: rowId
                            },
                            success: function(response) {
                                console.log('Response from server: ', response);

                                if (response.success) {
                                    alert(response.message);
                                    // Change the button text and class after success
                                    button.innerHTML = '<i class="fas fa-check"></i>&nbsp;' + response.buttonText; // Update button text
                                    button.classList.remove('btn-success'); // Remove old class
                                    button.classList.add(response.buttonClass); // Add new class
                                    button.style.pointerEvents = "auto"; // Re-enable clicking
                                    // Optionally refresh or navigate
                                    window.location.reload();
                                } else {
                                    alert(response.message);
                                    // Revert the button on failure
                                    button.innerHTML = '<i class="fas fa-envelope"></i>&nbsp;Send';
                                    button.classList.remove('disabled');
                                    button.style.pointerEvents = "auto"; // Re-enable clicking
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error: ' + error);
                                alert('An error occurred while sending the email.');
                                // Revert the button back to its original state on failure
                                button.innerHTML = '<i class="fas fa-envelope"></i>&nbsp;Send';
                                button.classList.remove('disabled');
                                button.style.pointerEvents = "auto"; // Re-enable clicking
                            }
                        });
                    }

                    function handleMailAction(selectElement, rowId) {
                        const action = selectElement.value;
                        if (action === 'resend') {
                            // Call the function to resend the mail
                            sendRowId(selectElement, rowId);
                        }
                    }

                    $(document).ready(function() {
                        CKEDITOR.replace('description');

                        // Initialize Select2 for all dropdowns
                        $('.select2').select2({
                            // minimumResultsForSearch: Infinity // Disable search box if not needed
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
                                    let selected = (<?php echo ($isEdit && isset($assessment['programme_id'])) ? json_encode($assessment['programme_id']) : 'null'; ?> == programme.program_code) ? 'selected' : '';
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
                                        let selected = (<?php echo ($isEdit && isset($assessment['batch_id'])) ? json_encode($assessment['batch_id']) : 'null'; ?> == batch.id) ? 'selected' : '';
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
                                        let selected = (<?php echo ($isEdit && isset($assessment['module_id'])) ? json_encode($assessment['module_id']) : 'null'; ?> == module.id) ? 'selected' : '';
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
                                        let uniqueComponents = [...new Set(data.map(component => component.as_main_component_name))];
                                        uniqueComponents.forEach(function(uniqueComponent) {
                                            mainComponentDropdown.append(`<option value="${data.find(component => component.as_main_component_name === uniqueComponent).main_component_id}">${uniqueComponent}</option>`);
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

                    })
                </script>

</body>

</html>

<!-- Include necessary CSS and JS for Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>