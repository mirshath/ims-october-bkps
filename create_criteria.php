<?php
session_start();
include("database/connection.php");
include("includes/header.php");

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


// Pagination settings
$results_per_page = 10; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$start = ($page - 1) * $results_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_criteria'])) {
        // Add new criteria
        $criteria_code = $_POST['criteria_code'];
        $criteria_name = $_POST['criteria_name'];
        $sql = "INSERT INTO criterias (criteria_code, criteria_name) VALUES ('$criteria_code', '$criteria_name')";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "New criteria added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['update_criteria'])) {
        // Update criteria
        $id = $_POST['id'];
        $criteria_code = $_POST['criteria_code'];
        $criteria_name = $_POST['criteria_name'];
        $sql = "UPDATE criterias SET criteria_code='$criteria_code', criteria_name='$criteria_name' WHERE id=$id";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Criteria updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['delete_criteria'])) {
        // Delete criteria
        $id = $_POST['id'];
        $sql = "DELETE FROM criterias WHERE id=$id";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Criteria deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch criteria for the current page
$sql = "SELECT * FROM criterias LIMIT $start, $results_per_page";
$result = $conn->query($sql);

// Fetch total number of criteria for pagination controls
$sql_total = "SELECT COUNT(*) as total FROM criterias";
$result_total = $conn->query($sql_total);
$total_rows = $result_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);
?>

<!-- Add DataTables CSS and JS in the header -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

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
                    <h4 class="h4 mb-0 text-gray-800">Criteria Management</h4>
                </div>

                <!-- Add Criteria Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Criteria</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="criteria_code">Criteria Code</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" class="form-control" id="criteria_code" name="criteria_code" placeholder="Criteria Code" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label for="criteria_name">Criteria Name</label>
                                                    </div>
                                                    <div class="col">
                                                        <input type="text" class="form-control" id="criteria_name" name="criteria_name" placeholder="Criteria Name" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" name="add_criteria" class="btn btn-primary">Add Criterias</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Criterias Table -->
                <div class="card">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Criterias</h6>
                    </div>

                    <div class="card-body">
                        <table id="criteriaTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Criteria Code</th>
                                    <th>Criteria Name</th>
                                    <th></th>
                                    <th class="hidden"></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="" method="post">
                                            <td class="hidden">
                                                <?php echo htmlspecialchars($row['criteria_code']); ?>
                                            </td>
                                            <td class="hidden">
                                                <?php echo htmlspecialchars($row['criteria_name']); ?>
                                            </td>
                                            <td>
                                                <input type="text" name="criteria_code" value="<?php echo htmlspecialchars($row['criteria_code']); ?>" class="form-control">
                                            </td>
                                            <td>
                                                <input type="text" name="criteria_name" value="<?php echo htmlspecialchars($row['criteria_name']); ?>" class="form-control">
                                            </td>
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="update_criteria" class="btn btn-info btn-sm">Update</button>
                                                <!--<button type="submit" name="delete_criteria" class="btn btn-danger btn-sm"  onclick="return confirmDelete();">Delete</button>-->
                                                 <button type="submit" name="delete_criteria" class="btn btn-danger btn-sm" onclick="return confirmDelete();">Delete</button>
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
                        <style>
                            .hidden {
                                display: none;
                                /* Hides the element */
                            }
                        </style>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Initialize DataTables -->
<script>
    $(document).ready(function() {
        $('#criteriaTable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable searching
            "ordering": false // Enable sorting
        });
    });
</script>

<?php $conn->close(); ?>