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



$results_per_page = 10; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page
$start = ($page - 1) * $results_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_semester'])) {
        // Add new semester
        $semester_name = $_POST['semester_name'];
        $sql = "INSERT INTO semester_table (semester_name) VALUES ('$semester_name')";
        // $conn->query($sql);
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "New semester added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['update_semester'])) {
        // Update semester
        $id = $_POST['id'];
        $semester_name = $_POST['semester_name'];
        $sql = "UPDATE semester_table SET semester_name='$semester_name' WHERE id=$id";
        // $conn->query($sql);
        
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Semester updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        
    } elseif (isset($_POST['delete_semester'])) {
        // Delete semester
        $id = $_POST['id'];
        $sql = "DELETE FROM semester_table WHERE id=$id";
        // $conn->query($sql);
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Semester deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch semesters for the current page
$sql = "SELECT * FROM semester_table LIMIT $start, $results_per_page";
$result = $conn->query($sql);

// Fetch total number of semesters for pagination controls
$sql_total = "SELECT COUNT(*) as total FROM semester_table";
$result_total = $conn->query($sql_total);
$total_rows = $result_total->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $results_per_page);
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
                    <h4 class="h4 mb-0 text-gray-800">Semester Management</h4>
                </div>

                <!-- ----------------------------------------------------------  -->
                <!-- ----------------------------------------------------------  -->

                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Semester</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3 "><label for="lead_type"> Semester Name</label></div>
                                            <div class="col-md-9"> <input type="text" class="form-control" id="semester_name" name="semester_name" placeholder="Semester Name" required></div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="add_semester" class="btn btn-primary">Add Semester</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


                <script src="vendor/datatables/jquery.dataTables.min.js"></script>
                <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

                <link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">

                <div class="card">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Semesters</h6>
                    </div>

                    <div class="card-body">

                        <style>
                            .hidden {
                                display: none;
                                /* Hides the element */
                            }
                        </style>

                        <!-- Semesters Table -->
                        <table id="semestersTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Semester Name</th>
                                    <th></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="" method="post" class="d-inline">
                                            <td><?php echo $row['id']; ?></td>
                                            <td class="hidden"><?php echo $row['semester_name']; ?></td>
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <input type="text" class="form-control form-control-sm" name="semester_name" value="<?php echo htmlspecialchars($row['semester_name']); ?>" required>
                                            </td>
                                            <td>
                                                <button type="submit" name="update_semester" class="btn btn-info btn-sm">Update</button>
                                                <button type="submit" name="delete_semester" class="btn btn-danger btn-sm" onclick="return confirmDelete();">Delete</button>

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

                <!-- Initialize DataTables -->
                <script>
                    $(document).ready(function() {
                        $('#semestersTable').DataTable({
                            "paging": true, // Enable pagination
                            "searching": true, // Enable searching
                            "ordering": true // Enable sorting
                        });
                    });
                </script>

            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

</body>

</html>