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



// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_university'])) {
        // Add new university
        $university_code = $_POST['university_code'];
        $university_name = $_POST['university_name'];
        $address = $_POST['address'];
        $uni_code = $_POST['uni_code'];
        $sql = "INSERT INTO universities (university_code, university_name, address, uni_code) VALUES ('$university_code', '$university_name', '$address', '$uni_code')";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "University added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['update_university'])) {
        // Update university
        $id = $_POST['id'];
        $university_code = $_POST['university_code'];
        $university_name = $_POST['university_name'];
        $address = $_POST['address'];
        $uni_code = $_POST['uni_code'];
        $sql = "UPDATE universities SET university_code='$university_code', university_name='$university_name', address='$address', uni_code='$uni_code' WHERE id=$id";
        // $conn->query($sql);
          if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "University updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['delete_university'])) {
        // Delete university
        $id = $_POST['id'];
        $sql = "DELETE FROM universities WHERE id=$id";
        // $conn->query($sql);
        if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "University deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch universities for display in the DataTable
$sql = "SELECT * FROM universities";
$result = $conn->query($sql);

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
                    <h4 class="h4 mb-0 text-gray-800">University Management</h4>
                </div>

                <!-- Add University Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span>
                                &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add University</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"><label for="university_code">University Code</label></div>
                                            <div class="col"><input type="text" class="form-control" placeholder="University Code" id="university_code" name="university_code" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"><label for="university_name">University Name</label></div>
                                            <div class="col"><input type="text" class="form-control" placeholder="University Name" id="university_name" name="university_name" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"><label for="address">Address</label></div>
                                            <div class="col"><textarea class="form-control" id="address" placeholder="Address" name="address" rows="3"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"><label for="uni_code">Uni Code</label></div>
                                            <div class="col"><input type="text" class="form-control" placeholder="Uni Code" id="uni_code" name="uni_code"></div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" name="add_university" class="btn btn-primary">Add University</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Universities List -->
                <div class="card">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Universities</h6>
                    </div>

                    <div class="card-body">
                        <!-- DataTables Table -->
                        <table id="universitiesTable" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>University Code</th>
                                    <th>University Name</th>
                                    <th>Address</th>
                                    <th>Uni Code</th>
                                    <th></th>
                                    <th class="hidden"></th>
                                    <th class="hidden"></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="" method="post">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                            <td class="hidden"><?php echo htmlspecialchars($row['university_code']); ?></td>
                                            <td class="hidden"><?php echo htmlspecialchars($row['university_name']); ?></td>
                                            <td class="hidden"><?php echo htmlspecialchars($row['uni_code']); ?></td>
                                            <td>
                                                <input type="text" class="form-control" name="university_code" value="<?php echo htmlspecialchars($row['university_code']); ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="university_name" value="<?php echo htmlspecialchars($row['university_name']); ?>" required>
                                            </td>
                                            <td>
                                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($row['address']); ?></textarea>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="uni_code" value="<?php echo htmlspecialchars($row['uni_code']); ?>">
                                            </td>
                                            <td>
                                                <button type="submit" name="update_university" class="btn btn-info btn-sm">Update</button>
                                                <button type="submit" name="delete_university" class="btn btn-danger btn-sm" onclick="return confirmDelete();">Delete</button>
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

<!-- jQuery and DataTables scripts -->
<!-- Add DataTables CSS and JS in the header -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<script>
    $(document).ready(function() {
        $('#universitiesTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": false,
            "info": true,
            "lengthChange": true, // Allows the user to change the number of records per page
        });
    });
</script>

<style>
    .hidden {
        display: none;
        /* Hides the element */
    }
</style>

</body>

</html>

<?php $conn->close(); ?>