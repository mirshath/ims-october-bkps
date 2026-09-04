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
    if (isset($_POST['add_coordinator'])) {
        // Add new coordinator
        $coordinator_code = $_POST['coordinator_code'];
        $title = $_POST['title'];
        $coordinator_name = $_POST['coordinator_name'];
        $bms_email = $_POST['bms_email'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO coordinator_table (coordinator_code, title, coordinator_name, bms_email, password_hash) VALUES ('$coordinator_code', '$title', '$coordinator_name', '$bms_email', '$password')";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Coordinator added successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['update_coordinator'])) {
        // Update coordinator
        $id = $_POST['id'];
        $coordinator_code = $_POST['coordinator_code'];
        $title = $_POST['title'];
        $coordinator_name = $_POST['coordinator_name'];
        $bms_email = $_POST['bms_email'];

        $sql = "UPDATE coordinator_table SET coordinator_code='$coordinator_code', title='$title', coordinator_name='$coordinator_name', bms_email='$bms_email' WHERE id=$id";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Coordinator updated successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } elseif (isset($_POST['delete_coordinator'])) {
        // Delete coordinator
        $id = $_POST['id'];
        $sql = "DELETE FROM coordinator_table WHERE id=$id";
        // $conn->query($sql);
         if ($conn->query($sql) === TRUE) {
            $_SESSION['message'] = "Coordinator deleted successfully!";
            echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Fetch coordinators
$sql = "SELECT * FROM coordinator_table";
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
                    <h4 class="h4 mb-0 text-gray-800">Coordinator Management</h4>
                </div>
                <!-- Add Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Coordinators</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="coordinator_code">Coordinator Code</label></div>
                                            <div class="col"> <input type="text" placeholder="Coordinator Code" class="form-control" id="coordinator_code" name="coordinator_code" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="title">Title</label></div>
                                            <div class="col">
                                                <select class="form-control" id="title" name="title" required>
                                                    <option value="Mr">Mr</option>
                                                    <option value="Mrs">Mrs</option>
                                                    <option value="Ms">Ms</option>
                                                    <option value="Dr">Dr</option>
                                                    <option value="Prof">Prof</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="coordinator_name">Coordinator Name</label></div>
                                            <div class="col"> <input type="text" placeholder="Coordinator Name" class="form-control" id="coordinator_name" name="coordinator_name" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="bms_email">BMS Email</label></div>
                                            <div class="col"> <input type="email" placeholder="BMS Email" class="form-control" id="bms_email" name="bms_email" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="password">Password</label></div>
                                            <div class="col"> <input type="password" placeholder="Password" class="form-control" id="password" name="password" required></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <button type="submit" name="add_coordinator"  class="btn btn-primary">Add Coordinator</button>
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
                        <h6 class="mb-0">Current Coordinators</h6>
                    </div>

                    <div class="card-body">
                        <!-- Coordinators Table -->
                        <table class="table table-striped" id="coordinatorTable">
                            <thead>
                                <tr>
                                    <th>Coordinator Code</th>
                                    <th>Title</th>
                                    <th>Coordinator Name</th>
                                    <th>BMS Email</th>
                                    <th></th>

                                    <th class="hidden"></th>
                                    <th class="hidden"></th>
                                    <th class="hidden"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="" method="post" class="form-inline">
                                            <td class="hidden"><?php echo htmlspecialchars($row['coordinator_code']); ?></td>
                                            <td class="hidden"><?php echo htmlspecialchars($row['coordinator_name']); ?></td>
                                            <td class="hidden"><?php echo htmlspecialchars($row['bms_email']); ?></td>

                                            <td><input type="text" class="form-control mb-2 mr-sm-2" name="coordinator_code" value="<?php echo htmlspecialchars($row['coordinator_code']); ?>" required></td>
                                            <td>
                                                <select class="form-control mb-2 mr-sm-2" name="title" required>
                                                    <option value="Mr" <?php echo $row['title'] == 'Mr' ? 'selected' : ''; ?>>Mr</option>
                                                    <option value="Mrs" <?php echo $row['title'] == 'Mrs' ? 'selected' : ''; ?>>Mrs</option>
                                                    <option value="Ms" <?php echo $row['title'] == 'Ms' ? 'selected' : ''; ?>>Ms</option>
                                                    <option value="Dr" <?php echo $row['title'] == 'Dr' ? 'selected' : ''; ?>>Dr</option>
                                                    <option value="Prof" <?php echo $row['title'] == 'Prof' ? 'selected' : ''; ?>>Prof</option>
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control mb-2 mr-sm-2" name="coordinator_name" value="<?php echo htmlspecialchars($row['coordinator_name']); ?>" required></td>
                                            <td><input type="email" class="form-control mb-2 mr-sm-2" name="bms_email" value="<?php echo htmlspecialchars($row['bms_email']); ?>" required></td>
                                            <td>
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="update_coordinator" class="btn btn-info btn-sm mb-2">Update</button>
                                                <button type="submit" name="delete_coordinator" class="btn btn-danger btn-sm mb-2" onclick="return confirmDelete();">Delete</button>
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


<!-- Add DataTables CSS and JS in the header -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>

<script>
    $(document).ready(function() {
        $('#coordinatorTable').DataTable();
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