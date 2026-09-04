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



// Initialize variables
$grade_code = $grade_name = "";
$update = false;
$id = 0;

// Create or Update a grade
if (isset($_POST['save'])) {
    $grade_code = $_POST['grade_code'];
    $grade_name = $_POST['grade_name'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id == 0) {
        // Insert new grade
        $sql = "INSERT INTO grade_table (grade_code, grade_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $grade_code, $grade_name);
    } else {
        // Update existing grade grade_code=?, $grade_code,
        $sql = "UPDATE grade_table SET  grade_name=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si",  $grade_name, $id);
    }

    if ($stmt->execute()) {
        // header('Location: create_grade');
         $_SESSION['message'] = "Grade saved successfully!";
        echo '<script>window.location.href = "create_grade";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Edit a grade
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM grade_table WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $grade_code = htmlspecialchars($row['grade_code']);
        $grade_name = htmlspecialchars($row['grade_name']);
    }
}

// Delete a grade
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM grade_table WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // header('Location: create_grade');
          $_SESSION['message'] = "Grade successfully removed!";
        echo '<script>window.location.href = "create_grade";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
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

            <!-- ----------------------------------------------------------  -->
            <!-- ----------------------------------------------------------  -->


            <div class="p-3">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Grade Managment</h4>
                </div>


                <!-- add form // create forms -->

                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Grade</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">

                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="grade_code">Grade Code:</label></div>
                                            <div class="col">
                                                <!-- <input type="text" name="grade_code" class="form-control" value="<?php echo htmlspecialchars($grade_code); ?>"  required> -->
                                                <input type="text" name="grade_code" class="form-control" value="<?php echo htmlspecialchars($grade_code); ?>" <?php echo $update ? 'disabled' : ''; ?> required>
                                            </div>
                                        </div>


                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="grade_name">Grade Name:</label></div>
                                            <div class="col">
                                                <input type="text" name="grade_name" class="form-control" value="<?php echo htmlspecialchars($grade_name); ?>" required>
                                            </div>
                                        </div>

                                    </div>
 
                                    <div class="text-right">
                                        <!-- buttons  -->
                                        <?php if ($update == true): ?>
                                            <button type="submit" name="save" class="btn btn-info">Update Grade</button>
                                        <?php else: ?>
                                            <button type="submit" name="save" class="btn btn-primary">Add Grade</button>
                                        <?php endif; ?>

                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ----------------------------------------------------------  -->
            <!-- ----------------------------------------------------------  -->
            <div class="container-fluid">
                <div class="card shadow mb-4" style="font-size: 13px;">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Grades</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Grade Code</th>
                                        <th>Grade Name</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch all grades from the grade_table
                                    $stmt = $conn->prepare("SELECT * FROM grade_table");
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    // Loop through the results and display in the table
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['grade_code']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['grade_name']) . '</td>';
                                        echo '<td>';
                                        echo '<a href="?edit=' . htmlspecialchars($row['id']) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> &nbsp;';
                                        echo '<a href="?delete=' . htmlspecialchars($row['id']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this grade?\')"><i class="fas fa-trash-alt"></i></a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->




<!-- Page level plugins -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<!-- Page level custom scripts -->
<script src="js/demo/datatables-demo.js"></script>


<!-- ------------------ new concoet css js  -->
<!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css"> -->


</body>

</html>