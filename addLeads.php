<?php
session_start();
include("database/connection.php");
include("includes/header.php");
$Session_username = $_SESSION['username'];


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

// Initialize variables
$date = $type = $university = $programme = $intake = $first_name = $last_name = $contact = $email = $details = $status = "";
$update = false;
$id = 0;

// Fetch distinct 'type' values from leads_table
$sql_lead = "SELECT DISTINCT lead_type FROM leads_table";
$result_lead = $conn->query($sql_lead);

// Fetch universities and programs for dropdowns
$sql_universities = "SELECT * FROM universities";
$result_universities = $conn->query($sql_universities);

// Fetch status options from status_table
$query_status = "SELECT id, status_name FROM status_table";
$result_status = $conn->query($query_status);
$status_options = [];

while ($row_status = $result_status->fetch_assoc()) {
    $status_options[] = $row_status;
}

// Create or Update a lead
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $university = $_POST['university'];
    $programme = $_POST['programme'];
    $intake = $_POST['intake'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $details = $_POST['details'];
    $status = $_POST['status'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id == 0) {
        // Insert new lead
        $sql = "INSERT INTO leads (date, type, university, programme, intake, first_name, last_name, contact, email, details, status,entered_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssss", $date, $type, $university, $programme, $intake, $first_name, $last_name, $contact, $email, $details, $status, $Session_username);
    } else {
        // Update existing lead
        $sql = "UPDATE leads SET date=?, type=?, university=?, programme=?, intake=?, first_name=?, last_name=?, contact=?, email=?, details=?, status=?, entered_by=?
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssi", $date, $type, $university, $programme, $intake, $first_name, $last_name, $contact, $email, $details, $status,$Session_username, $id);
    }

    if ($stmt->execute()) {
         $_SESSION['message'] = "Lead added/updated successfully!";
        echo '<script>window.location.href = "addLeads";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Edit a lead
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM leads WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $date = htmlspecialchars($row['date']);
        $type = htmlspecialchars($row['type']);
        $university = htmlspecialchars($row['university']);
        $programme = htmlspecialchars($row['programme']);
        $intake = htmlspecialchars($row['intake']);
        $first_name = htmlspecialchars($row['first_name']);
        $last_name = htmlspecialchars($row['last_name']);
        $contact = htmlspecialchars($row['contact']);
        $email = htmlspecialchars($row['email']);
        $details = htmlspecialchars($row['details']);
        // $status = htmlspecialchars($row['status']);
        // Set the status variable to the status from the database
        $status = htmlspecialchars($row['status']);
    }
}

// Delete a lead
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM leads WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
         $_SESSION['message'] = "Lead deleted successfully!";
        echo '<script>window.location.href = "addLeads";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>
            <!-- End of Topbar -->
            <!-- Begin Page Content -->
            <div class="container">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Lead Management</h4>
                </div>
                <!-- Add Form -->
                <div class="row mb-5">
                    <div class="col-md-10">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Leads</h6>
                            </div>

                            <div class="card-body">
                                <form action="addLeads" method="post" class="mb-3">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3 "> <label for="date">Date: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" required>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="type">Type: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <select class="form-control" id="type" name="type" required>
                                                            <option value="" disabled>Select Type</option>
                                                            <?php
                                                            // Populate the dropdown with lead types
                                                            if ($result_lead->num_rows > 0) {
                                                                while ($row = $result_lead->fetch_assoc()) {
                                                                    $selected = ($row['lead_type'] === $type) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($row['lead_type']) . '" ' . $selected . '>' . htmlspecialchars($row['lead_type']) . '</option>';
                                                                }
                                                            } else {
                                                                echo '<option value="" disabled>No types available</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- University Dropdown -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="university">University: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <select name="university" id="university" class="form-control select2" required>
                                                            <option value="" disabled selected>Select University</option>
                                                            <?php
                                                            if ($result_universities->num_rows > 0) {
                                                                while ($row = $result_universities->fetch_assoc()) {
                                                                    $selected = ($row['id'] == $university) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($row['id']) . '" ' . $selected . '>' . htmlspecialchars($row['university_name']) . '</option>';
                                                                }
                                                            } else {
                                                                echo '<option value="" disabled>No universities available</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <!-- Programme Dropdown -->
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="programme">Programme: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <select name="programme" id="programme" class="form-control select2" required>
                                                            <option value="" disabled selected>Select Programme</option>
                                                            <!-- Options will be populated dynamically via AJAX -->
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                            <script>
                                                $(document).ready(function() {
                                                    var selectedProgramme = '<?php echo $programme; ?>';

                                                    $('#university').on('change', function() {
                                                        var universityID = $(this).val();
                                                        if (universityID) {
                                                            $.ajax({
                                                                type: 'POST',
                                                                url: 'module_creating/get_programs.php',
                                                                data: {
                                                                    university_id: universityID
                                                                },
                                                                dataType: 'json',
                                                                success: function(data) {
                                                                    $('#programme').html('<option value="">Select Programme</option>');
                                                                    $.each(data, function(key, value) {
                                                                        var isSelected = value.program_name == selectedProgramme ? 'selected' : '';
                                                                        $('#programme').append('<option value="' + value.program_name + '" ' + isSelected + '>' + value.program_name + '</option>');
                                                                    });
                                                                }
                                                            });
                                                        } else {
                                                            $('#programme').html('<option value="">Select Programme</option>');
                                                        }
                                                    });
                                                    // Trigger change event to load programs if university is already selected (for edit mode)
                                                    <?php if ($update && !empty($university)): ?>
                                                        $('#university').trigger('change');
                                                    <?php endif; ?>
                                                });
                                            </script>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="intake">Intake Date: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="date" placeholder="Intake Date" name="intake" class="form-control" value="<?php echo htmlspecialchars($intake); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="first_name">First Name: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="text" placeholder="First Name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="last_name">Last Name: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="text" placeholder="Last Name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="contact">Contact: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="text" placeholder="Contact" name="contact" class="form-control" value="<?php echo htmlspecialchars($contact); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="email">Email: <span style="color: red; font-weight: bold;">*</span></label></div>
                                                    <div class="col">
                                                        <input type="email" placeholder="Email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3"> <label for="details">Details:</label></div>
                                                    <div class="col">
                                                        <textarea name="details" placeholder="Details" class="form-control"><?php echo htmlspecialchars($details); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="status">Status <span style="color: red; font-weight: bold;">*</span></label>
                                                    </div>
                                                    <div class="col">
                                                        <select class="form-control select2" id="status" name="status" required>
                                                            <?php
                                                            // Generate options for the status dropdown
                                                            foreach ($status_options as $status_option) {
                                                                // Check if this option is the one currently stored in the database
                                                                $selected = ($status_option['status_name'] == $status) ? 'selected' : '';
                                                                echo "<option value=\"" . htmlspecialchars($status_option['status_name']) . "\" $selected>" . htmlspecialchars($status_option['status_name']) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button type="submit" name="save" class="btn btn-primary"><?php echo $update ? 'Update' : 'Save'; ?> Lead</button>

                                        </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
>
                <!-- ---------------------------------------  -->

                <div class="card mt-5">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Leads</h6>
                    </div>

                    <div class="card-body">
                        <!-- Coordinators Table -->
                        <table class="table table-bordered" id="leadsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Programme</th>
                                    <th>Intake</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Details</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 12px;">
                                <?php
                                // Fetch and display leads from the database
                                $sql_leads = "SELECT * FROM leads";
                                $result_leads = $conn->query($sql_leads);
                                while ($row = $result_leads->fetch_assoc()) {
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['date']) ?></td>
                                        <td><?= htmlspecialchars($row['type']) ?></td>
                                        <td><?= htmlspecialchars($row['programme']) ?></td>
                                        <td><?= htmlspecialchars($row['intake']) ?></td>
                                        <td><?= htmlspecialchars($row['first_name']) ?> <?= htmlspecialchars($row['last_name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['details']) ?></td>
                                        <td>
                                            <form id="updateStatusForm" data-lead-id="<?= htmlspecialchars($row['id']) ?>" style="display:inline;">
                                                <select name="status" class="form-control form-control-sm" id="statusSelect">
                                                    <?php
                                                    foreach ($status_options as $status) {
                                                        $selected = ($status['status_name'] == $row['status']) ? 'selected' : '';
                                                        echo "<option value=\"" . htmlspecialchars($status['status_name']) . "\" $selected>" . htmlspecialchars($status['status_name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                                <button type="button" class="btn btn-sm btn-primary w-100 updateStatusButton mt-2">Update</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="addLeads?edit=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="addLeads?delete=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this lead?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- jQuery (necessary for DataTables) -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <!-- DataTables JS -->
                <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
                <script>
                    $(document).ready(function() {
                        // Initialize DataTables
                        $('#leadsTable').DataTable();

                        $('.updateStatusButton').on('click', function() {
                            // Get the form associated with the clicked button
                            var form = $(this).closest('form');
                            var leadId = form.data('lead-id');
                            var status = form.find('#statusSelect').val();

                            $.ajax({
                                url: 'updateLeadStatus', // Make sure this points to your update endpoint
                                type: 'POST',
                                data: {
                                    lead_id: leadId,
                                    status: status
                                },
                                success: function(response) {
                                    alert('Status updated successfully!'); // You can customize this
                                },
                                error: function(xhr, status, error) {
                                    alert('An error occurred: ' + error);
                                }
                            });
                        });
                    });
                </script>


            </div>
        </div>
    </div>
</div>

<!-- Add DataTables CSS and JS in the header -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>
<!-- Include necessary CSS and JS for Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Initialize Select2 for all dropdowns
    });
</script>

</body>

</html>