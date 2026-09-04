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


// Pagination settings
$results_per_page = 10; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$start = ($page - 1) * $results_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_year'])) {
        // Add new year
        $year_name = $_POST['year_name'];
        $sql = "INSERT INTO year_table (year_name,entered_by) VALUES ('$year_name','$Session_username')";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            // echo "New year added successfully!";
            $_SESSION['message'] = "New year added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['update_year'])) {
        // Update year
        $id = $_POST['id'];
        $year_name = $_POST['year_name'];
        $sql = "UPDATE year_table SET year_name='$year_name', entered_by='$Session_username' WHERE id=$id";
        // $conn->query($sql);
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Year updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['delete_year'])) {
        // Delete year
        $id = $_POST['id'];
        $sql = "DELETE FROM year_table WHERE id=$id";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Year deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch years for the current page
$sql = "SELECT * FROM year_table LIMIT $start, $results_per_page";
$result = $conn->query($sql);

// Fetch total number of years for pagination controls
$sql_total = "SELECT COUNT(*) as total FROM year_table";
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
                    <h4 class="h4 mb-0 text-gray-800">Year Management</h4>
                </div>

                <!-- Add form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Years</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-2 "> <label for="year_name">Year Name</label></div>
                                            <div class="col-md-10">
                                                <input type="text" class="form-control" placeholder="years" id="year_name" name="year_name" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="add_year" class="btn btn-primary">Add Year</button>
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
                        <h6 class="mb-0">Current Years</h6>
                    </div>

                    <div class="card-body">

                        <!-- Updated Year Table -->
                        <table class="table table-striped" id="yearTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Year Name</th>
                                    <th></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <!-- Form for updating or deleting year -->
                                        <form action="" method="post" class="form-inline">
                                            <td class="hidden"><?php echo $row['year_name']; ?></td>
                                            <td><?php echo $row['id']; ?></td>
                                            <td>
                                                <input type="text" class="form-control" name="year_name" value="<?php echo htmlspecialchars($row['year_name']); ?>" required>
                                            </td>
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="update_year" class="btn btn-info btn-sm">Update</button>
                                                <!-- <button type="submit" name="delete_year" class="btn btn-danger btn-sm">Delete</button> -->
                                                <button type="submit" name="delete_year" class="btn btn-danger btn-sm" onclick="return confirmDelete();">Delete</button>

                                                <script>
                                                    function confirmDelete() {
                                                        return confirm("Are you sure you want to delete this year?");
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

                        <!-- Pagination Controls -->

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<script>
    $(document).ready(function() {
        $('#yearTable').DataTable({
            "paging": true, // Disable built-in pagination since we are using our custom pagination
            "searching": true, // Enable searching
            "ordering": false, // Enable ordering
            "info": false // Disable info
        });
    });
</script>
</body>

</html>