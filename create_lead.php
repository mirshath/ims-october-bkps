<?php
session_start();
// here wanna show the username getting from the session username 

$Session_username = $_SESSION['username'];
// print  // Get the username from the session

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



$results_per_page = 10; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$start = ($page - 1) * $results_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_lead'])) {
        // Add new lead
        $lead_type = $_POST['lead_type'];
        $sql = "INSERT INTO leads_table (lead_type,entered_by) VALUES ('$lead_type','$Session_username')";
        // $conn->query($sql);
         if ($conn->query($sql)) {
            $_SESSION['message'] = "Lead added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        }
        
    } elseif (isset($_POST['update_lead'])) {
        // Update lead
        $id = $_POST['id'];
        $lead_type = $_POST['lead_type'];
        $sql = "UPDATE leads_table SET lead_type='$lead_type', entered_by='$Session_username' WHERE id=$id";
        // $conn->query($sql);
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Lead updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>'; 
        }
    } elseif (isset($_POST['delete_lead'])) {
        // Delete lead
        $id = $_POST['id'];
        $sql = "DELETE FROM leads_table WHERE id=$id";
        // $conn->query($sql);
        if ($conn->query($sql)) {
            $_SESSION['message'] = "Lead deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>'; 
        }
    }
}

// Fetch leads for the current page
$sql = "SELECT * FROM leads_table LIMIT $start, $results_per_page";
$result = $conn->query($sql);

// Fetch total number of leads for pagination controls
$sql_total = "SELECT COUNT(*) as total FROM leads_table";
$result_total = $conn->query($sql_total);
$total_rows = $result_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);
?>

<!-- Page Wrapper -->
<div id="wrapper" style="background-color: red;">

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
                    <h4 class="h4 mb-0 text-gray-800">Manage Inquiry Types</h4>
                </div>

                <!-- add form // create forms -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add inquiry</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-2 "><label for="lead_type"> Type Name</label></div>
                                            <div class="col-md-10"> <input type="text" class="form-control" id="lead_type" name="lead_type" placeholder="Type Inquiry Name" required></div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="add_lead" class="btn btn-primary">Add Lead</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current inquiry Types</h6>
                    </div>

                    <div class="card-body">

                        <!-- Leads Table -->
                        <table id="leadsTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Type Code</th>
                                    <th>Type Name</th>
                                    <th></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="" method="post">
                                            <td class="hidden"><?php echo $row['lead_type']; ?></td>
                                            <td><?php echo $row['id']; ?></td>


                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="text" class="form-control" name="lead_type" value="<?php echo htmlspecialchars($row['lead_type']); ?>" required>
                                            </td>
                                            <td>
                                                <button type="submit" name="update_lead" class="btn btn-info btn-sm">Update</button>
                                                <button type="submit" name="delete_lead" class="btn btn-danger btn-sm" onclick="return confirmDelete();">Delete</button>
                                                <script>
                                                    function confirmDelete() {
                                                        return confirm("Are you sure you want to delete this Lead?");
                                                    }
                                                </script>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>

                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hidden {
        display: none;
        /* Hides the element */
    }
</style>

<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<!-- Initialize DataTables -->
<script>
    $(document).ready(function() {
        $('#leadsTable').DataTable({
            "paging": true, // Enables pagination
            "searching": true, // Enables global search for all columns
            "ordering": false, // Enables sorting
            "columnDefs": [{
                "targets": [1], // Disable searching in the "Actions" column (optional)
                "searchable": false
            }]
        });
    });
</script>

<?php $conn->close(); ?>

</body>

</html>