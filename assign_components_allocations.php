<?php
session_start();
include("database/connection.php");
include("includes/header.php");

$Session_username = $_SESSION['username'];

if (!isset( $_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------



if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $result = $conn->query("SELECT * FROM allocated_components WHERE id = $id");
    $data = $result->fetch_assoc();
    echo json_encode($data); // Return the data as JSON
}

?>

<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2();

        // Edit button functionality
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');

            // Fetch the data for the selected ID
            $.ajax({
                url: 'Assignment_components/fetch_allocated_component.php', // Fetch data from this file
                method: 'POST',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(data) {
                    // Populate the form with the fetched data
                    $('#module_id').val(data.module_id).trigger('change'); // Set Module ID
                    $('#main_component').val(data.main_component_id).trigger('change'); // Set Main Component
                    $('#sub_component').val(data.sub_component_id).trigger('change'); // Set Sub Component

                    // Show the form for editing
                    $('#allocateForm').show();

                    // Optionally, you can show/hide elements based on the data
                    if (data.sub_component_id) {
                        $('input[name="has_sub_component"][value="yes"]').prop('checked', true); // Show subcomponent section
                        $('#sub').show();
                        $('#subComponentSection').show();
                    } else {
                        $('input[name="has_sub_component"][value="no"]').prop('checked', true); // Hide subcomponent section
                        $('#sub').hide();
                        $('#subComponentSection').hide();
                    }
                }
            });
        });
    });
</script>

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
                    <h4 class="h4 mb-0 text-gray-800">Allocate Components</h4>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Allocate Components</h6>
                            </div>
                            <div class="card-body">
                                <form action="allocate_components.php" method="POST" id="allocateForm">
                                    <!-- Main Component Dropdown -->
                                    <div class="row">
                                        <!-- Module Dropdown -->
                                        <div class="col-md-4">
                                            <label for="module_id">Module</label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <select name="module_id" id="module_id" class="form-control select2" required>
                                                    <option value="">Select Module</option>
                                                    <?php
                                                    $result = $conn->query("SELECT id, module_code, module_name FROM modules");
                                                    while ($row = $result->fetch_assoc()) {
                                                        echo "<option value='{$row['id']}'>{$row['module_code']} - {$row['module_name']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="main_component">Main Component</label>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <select name="main_component" id="main_component" class="form-control select2" required>
                                                    <option value="">Select Main Component</option>
                                                    <?php
                                                    $result = $conn->query("SELECT * FROM assignment_components");
                                                    while ($row = $result->fetch_assoc()) {
                                                        // echo "<option value='{$row['id']}'>{$row['as_main_component_name']}</option>";
                                                        echo "<option value='{$row['id']}'>
                                                            {$row['as_main_component_name']} - {$row['main_component_percent']}
                                                        </option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>Does it have subcomponents?</label><br>
                                            </div>

                                            <div class="col-md-8">
                                                <!-- Radio Buttons for Subcomponents -->
                                                <div class="form-group mt-3">
                                                    <input type="radio" name="has_sub_component" value="no" checked> No
                                                    <input type="radio" name="has_sub_component" value="yes" style="margin-left: 50px;"> Yes
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="sub" style="display: none;">
                                            <div class="col-md-4 mt-4">
                                                <label for="sub_component">Sub Component</label>
                                            </div>
                                            <div class="col-md-8">
                                                <!-- Subcomponent Dropdown -->
                                                <div class="form-group mt-3" id="subComponentSection" style="display: none;">
                                                    <select name="sub_component[]" id="sub_component" class="form-control select2">
                                                        <option value="">Select Sub Component</option>
                                                        <?php
                                                        $result = $conn->query("SELECT * FROM sub_assign_components");
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo "<option value='{$row['id']}'>{$row['sub_component_name']} - {$row['sub_component_percent']}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <!-- Button to Add More Subcomponents (Initially Hidden) -->
                                                <button type="button" id="addMoreSubComponent" class="btn btn-secondary mt-3" style="display: none;">Add More Sub Components</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!--Submit Button -->
                                    <button type="submit" class="btn btn-primary mt-4">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DataTable for Allocated Components -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Allocated Components</h6>
                            </div>
                            <div class="card-body">
                                <table id="allocatedComponentsTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 5px;">#</th>
                                            <th>ID</th>
                                            <th>Module ID</th>
                                            <th>Main Component ID</th>
                                            <th>Sub Component ID</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $index = 1;
                                        // Initialize index counter

                                        // Join allocated_components with modules, assignment_components, and sub_assign_components to get more meaningful data
                                        $query = "
                                            SELECT 
                                                ac.id, 
                                                ac.module_id, 
                                                ac.main_component_id, 
                                                ac.sub_component_id, 
                                                m.module_code, 
                                                m.module_name, 
                                                amc.*, 
                                                sc.sub_component_name,
                                                sc.sub_component_percent
                                            FROM allocated_components ac
                                            INNER JOIN modules m ON ac.module_id = m.id
                                            INNER JOIN assignment_components amc ON ac.main_component_id = amc.id
                                            LEFT JOIN sub_assign_components sc ON ac.sub_component_id = sc.id
                                        ";

                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                             <td>" . $index++ . "</td>
                                                <td>{$row['id']}</td>
                                                <td>{$row['module_code']} - {$row['module_name']}</td>
                                                <td>{$row['as_main_component_name']} - {$row['main_component_percent']} </td>
                                                <td>{$row['sub_component_name']} - {$row['sub_component_percent']}</td>
                                                <td>
                                                    <button class='btn btn-sm btn-warning edit-btn' data-id='{$row['id']}'><i class='fa fa-edit'></i></button>
                                                    <button class='btn btn-sm btn-danger delete-btn' data-id='{$row['id']}'><i class='fa fa-trash'></i></button>
                                                </td>
                                            </tr>";
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
    </div>


    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">



    <!-- JavaScript to Show/Hide Subcomponent Section and Button -->
    <script>
        $(document).ready(function() {
            // Initialize select2
            $('.select2').select2();


            // delete 
            $(document).on('click', '.delete-btn', function() {
                var id = $(this).data('id');

                if (confirm("Are you sure you want to delete this component?")) {
                    // Send an AJAX request to delete the record
                    $.ajax({
                        url: 'Assignment_components/delete_allocated_component.php',
                        method: 'POST',
                        data: {
                            id: id
                        },
                        success: function(response) {
                            alert(response); // Show success message
                            location.reload(); // Reload the page to update the table
                        }
                    });
                }
            });
            // Initialize DataTable
            $('#allocatedComponentsTable').DataTable();

            // Show/hide subcomponent section and "Add More" button based on radio button
            $('input[name="has_sub_component"]').change(function() {
                if ($(this).val() === "yes") {
                    // Show the subcomponent section and button
                    $('#sub').show();
                    $('#subComponentSection').show();
                    $('#addMoreSubComponent').show();
                } else {
                    // Hide the subcomponent section and button
                    $('#sub').hide();
                    $('#subComponentSection').hide();
                    $('#addMoreSubComponent').hide();
                }
            });

            // Add More Subcomponents functionality
            // $('#addMoreSubComponent').click(function() {
            //     var newDropdown = `
            //         <div class="form-group mt-3">
            //             <select name="sub_component[]" class="form-control select2">
            //                 <option value="">Select Sub Component</option>
            //                 <?php
            //                 $result = $conn->query("SELECT id, sub_component_name FROM sub_assign_components");
            //                 while ($row = $result->fetch_assoc()) {
            //                     echo "<option value='{$row['id']}'>{$row['sub_component_name']}</option>";
            //                 }
            //                 ?>
            //             </select>
            //         </div>
            //     `;
            //     $('#subComponentSection').append(newDropdown);
            //     $('.select2').select2(); // Reinitialize select2
            // });
            
            
            
             $('#addMoreSubComponent').click(function() {
                var newDropdown = `
                    <div class="form-group mt-3">
                        <select name="sub_component[]" class="form-control select2">
                            <option value="">Select Sub Component</option>
                            <?php
                            $result = $conn->query("SELECT * FROM sub_assign_components");
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}' data-percent='{$row['sub_component_percent']}'>{$row['sub_component_name']} - {$row['sub_component_percent']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                `;
                $('#subComponentSection').append(newDropdown);
                $('.select2').select2(); // Reinitialize select2
            });

            // Edit button functionality
            $(document).on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                // Fetch the data for the selected ID
                $.ajax({
                    url: 'fetch_allocated_component.php', // Create this file to fetch data
                    method: 'POST',
                    data: {
                        id: id
                    },
                    dataType: 'json',
                    success: function(data) {
                        // Populate the form with the fetched data
                        $('#main_component').val(data.main_component_id).trigger('change');
                        $('#sub_component').val(data.sub_component_id).trigger('change');
                        // Show the form for editing
                        $('#allocateForm').show();
                    }
                });
            });
        });
    </script>

</div>
</body>

</html>