<?php
session_start();
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



// Initialize variables
$currency_code = $currency_name = $short_name = $symbol = "";
$update = false;
$id = 0;

// Create or Update a currency
if (isset($_POST['save'])) {
    // Check if it's an update (where currency_code comes from hidden field)
    // or a new entry (where it comes from currency_code_display)
    $currency_code = isset($_POST['currency_code']) ? $_POST['currency_code'] : $_POST['currency_code_display'];
    $currency_name = $_POST['currency_name'];
    $short_name = $_POST['short_name'];
    $symbol = $_POST['symbol'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id == 0) {
        // Insert new currency
        $sql = "INSERT INTO currency_table (currency_code, currency_name, short_name, symbol) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $currency_code, $currency_name, $short_name, $symbol);
    } else {
        // Update existing currency
        $sql = "UPDATE currency_table SET currency_code=?, currency_name=?, short_name=?, symbol=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $currency_code, $currency_name, $short_name, $symbol, $id);
    }

    if ($stmt->execute()) {
        // header('Location: create_currency');
         $_SESSION['message'] = "Currency saved successfully!";
        echo '<script>window.location.href = "create_currency";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Edit a currency
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM currency_table WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currency_code = htmlspecialchars($row['currency_code']);
        $currency_name = htmlspecialchars($row['currency_name']);
        $short_name = htmlspecialchars($row['short_name']);
        $symbol = htmlspecialchars($row['symbol']);
    }
}

// Delete a currency
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM currency_table WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // header('Location: create_currency');
         $_SESSION['message'] = "Currency successfully removed!";
        echo '<script>window.location.href = "create_currency";</script>';
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
                    <h4 class="h4 mb-0 text-gray-800">Currency Managment</h4>
                </div>

                <!-- add form // create forms -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Currency</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">

                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="currency_code">Currency Code:</label>
                                            </div>
                                            <div class="col">
                                                <?php if ($update): ?>
                                                    <input type="text" name="currency_code_display" class="form-control" placeholder="Currency Code"
                                                        value="<?php echo htmlspecialchars($currency_code); ?>"
                                                        disabled required>
                                                    <input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($currency_code); ?>">
                                                <?php else: ?>
                                                    <input type="text" name="currency_code" class="form-control" placeholder="Currency Code"
                                                        value="<?php echo htmlspecialchars($currency_code); ?>"
                                                        required>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="currency_name">Currency Name:</label></div>
                                            <div class="col"> <input type="text" name="currency_name" class="form-control" placeholder="Ex : Sri Lankan Rupees" value="<?php echo htmlspecialchars($currency_name); ?>" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="short_name">Short Name:</label></div>
                                            <div class="col"> <input type="text" name="short_name" class="form-control" placeholder="Short Name Ex : LKR" value="<?php echo htmlspecialchars($short_name); ?>" required></div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="symbol">Symbol:</label></div>
                                            <div class="col"> <input type="text" name="symbol" class="form-control" placeholder="Symbol Ex : Rs " value="<?php echo htmlspecialchars($symbol); ?>" required></div>
                                        </div>
                                    </div>


                                    <div class="text-right">
                                        <!-- buttons  -->
                                        <?php if ($update == true): ?>
                                            <button type="submit" name="save" class="btn btn-info">Update Currency</button>
                                        <?php else: ?>
                                            <button type="submit" name="save" class="btn btn-primary">Add Currency</button>
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
                        <h6 class="mb-0">Current Currencies</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Currency Code</th>
                                        <th>Currency Name</th>
                                        <th>Short Name</th>
                                        <th>Symbol</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch all currencies from the currency_table
                                    $stmt = $conn->prepare("SELECT * FROM currency_table");
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    // Loop through the results and display in the table
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['currency_code']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['currency_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['short_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['symbol']) . '</td>';
                                        echo '<td>';
                                        echo '<a href="?edit=' . htmlspecialchars($row['id']) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> &nbsp;';
                                        echo '<a href="?delete=' . htmlspecialchars($row['id']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this currency?\')"><i class="fas fa-trash-alt"></i></a>';
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
</body>

</html>