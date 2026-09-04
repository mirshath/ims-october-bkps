<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

// Initialize variables
$batch_name = $university = $programme = $intake_date = $intake_end_date = $end_date = "";
$batch_no = $intake_no = $year_no = "";
$batch_intake = $batch_limit = $attendance = $awarded_by = $qualification_level = $recognized_by = $accredited_by = "";
$batch_hide_active = "";
$created_at = $updated_at = "";
$update = false;
$id = 0;

// Fetch universities for the dropdown
$universities_result = mysqli_query($conn, "SELECT * FROM universities");
$universities = [];
while ($row = mysqli_fetch_assoc($universities_result)) {
    $universities[] = $row;
}

// Fetch programs for dropdown
$sql = "SELECT * FROM program_table";
$result = mysqli_query($conn, $sql);
$programsOptions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $programsOptions[] = $row;
}

// Create or Update a batch
if (isset($_POST['save'])) {
    $batch_name = $_POST['batch_name'];
    $university = $_POST['university'];
    $programme = $_POST['programme'];
    $intake_date = $_POST['intake_date'];
    $intake_end_date = $_POST['intake_end_date'];
    $end_date = $_POST['end_date'];
    $batch_no = $_POST['batch_no'];
    $intake_no = $_POST['intake_no'];
    $year_no = $_POST['year_no'];
    $batch_intake = $_POST['batch_intake'];
    $batch_limit = $_POST['batch_limit'];
    $attendance = $_POST['attendance'];
    $awarded_by = $_POST['awarded_by'];
    $qualification_level = $_POST['qualification_level'];
    $recognized_by = $_POST['recognized_by'];
    $accredited_by = $_POST['accredited_by'];
    $batch_hide_active = isset($_POST['batch_hide_active']) ? $_POST['batch_hide_active'] : null;
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $now = date('Y-m-d H:i:s');

    if ($id == 0) {
        // Insert new batch
        $sql = "INSERT INTO batch_table 
            (batch_name, university, programme, intake_date, intake_end_date, batch_no, intake_no, year_no, end_date, batch_intake, batch_limit, attendance, awarded_by, qualification_level, recognized_by, accredited_by, batch_hide_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siissssississssssss",
            $batch_name,
            $university,
            $programme,
            $intake_date,
            $intake_end_date,
            $batch_no,
            $intake_no,
            $year_no,
            $end_date,
            $batch_intake,
            $batch_limit,
            $attendance,
            $awarded_by,
            $qualification_level,
            $recognized_by,
            $accredited_by,
            $batch_hide_active,
            $now,
            $now
        );
    } else {
        // Update existing batch
        $sql = "UPDATE batch_table SET 
            batch_name=?, university=?, programme=?, intake_date=?, intake_end_date=?, batch_no=?, intake_no=?, year_no=?, end_date=?, 
            batch_intake=?, batch_limit=?, attendance=?, awarded_by=?, qualification_level=?, recognized_by=?, accredited_by=?, batch_hide_active=?, updated_at=?
            WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siissssississsssssi",
            $batch_name,
            $university,
            $programme,
            $intake_date,
            $intake_end_date,
            $batch_no,
            $intake_no,
            $year_no,
            $end_date,
            $batch_intake,
            $batch_limit,
            $attendance,
            $awarded_by,
            $qualification_level,
            $recognized_by,
            $accredited_by,
            $batch_hide_active,
            $now,
            $id
        );
    }

    if ($stmt->execute()) {
        $_SESSION['message'] = "Batch updated successfully!";
        echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Edit a batch
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $update = true;
    $stmt = $conn->prepare("SELECT * FROM batch_table WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $batch_name = $row['batch_name'] ?? '';
        $university = $row['university'] ?? '';
        $programme = $row['programme'] ?? '';
        $intake_date = $row['intake_date'] ?? '';
        $intake_end_date = $row['intake_end_date'] ?? '';
        $end_date = $row['end_date'] ?? '';
        $batch_no = $row['batch_no'] ?? '';
        $intake_no = $row['intake_no'] ?? '';
        $year_no = $row['year_no'] ?? '';
        $batch_intake = $row['batch_intake'] ?? '';
        $batch_limit = $row['batch_limit'] ?? '';
        $attendance = $row['attendance'] ?? '';
        $awarded_by = $row['awarded_by'] ?? '';
        $qualification_level = $row['qualification_level'] ?? '';
        $recognized_by = $row['recognized_by'] ?? '';
        $accredited_by = $row['accredited_by'] ?? '';
        $batch_hide_active = $row['batch_hide_active'] ?? '';
        $created_at = $row['created_at'];
        $updated_at = $row['updated_at'];
    }
}

// Delete a batch
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM batch_table WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Batch deleted successfully!";
        echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
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
            <div class="p-3">
                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Batches Management</h4>
                </div>

                <div class="row mb-5">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Batches</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="batch_name">
                                                    Batch Name: <span style="color: red;">*</span>
                                                </label>
                                            </div>
                                            <div class="col">
                                                <input type="text" name="batch_name" class="form-control"
                                                    value="<?php echo htmlspecialchars($batch_name); ?>"
                                                    placeholder="Batch Name" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="university">University:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <select class="form-control select2" id="university" name="university"
                                                    required>
                                                    <option value="">Select University</option>
                                                    <?php foreach ($universities as $uni): ?>
                                                        <option value="<?php echo htmlspecialchars($uni['id']); ?>" <?php echo $uni['id'] == $university ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($uni['university_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="programme">Programme:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <select class="form-control select2" id="programme" name="programme"
                                                    required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <script
                                        src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
                                    <script>
                                        $(document).ready(function() {

                                            var selectedProgramme = '<?php echo $programme; ?>';

                                            function loadProgrammes(universityID, callback) {
                                                if (universityID) {
                                                    $.ajax({
                                                        type: 'POST',
                                                        url: 'module_creating/get_programs.php',
                                                        data: {
                                                            university_id: universityID
                                                        },
                                                        dataType: 'json',
                                                        success: function(data) {
                                                            $('#programme').empty();
                                                            $('#programme').append('<option value="">Select Programme</option>');
                                                            $.each(data, function(key, value) {
                                                                $('#programme').append(
                                                                    '<option value="' + value.program_code + '">' +
                                                                    value.program_name + '</option>'
                                                                );
                                                            });

                                                            // Now that the real options exist, set the value
                                                            // and tell Select2 to refresh its displayed label.
                                                            $('#programme').val(selectedProgramme).trigger('change');

                                                            if (typeof callback === 'function') {
                                                                callback();
                                                            }
                                                        },
                                                        error: function() {
                                                            console.error('Failed to load programmes for university ' + universityID);
                                                        }
                                                    });
                                                } else {
                                                    $('#programme').empty();
                                                    $('#programme').append('<option value="">Select Programme</option>');
                                                    $('#programme').trigger('change');
                                                }
                                            }

                                            $('#university').on('change', function() {
                                                // Once the user manually changes university, any previously
                                                // "remembered" programme selection no longer applies.
                                                selectedProgramme = '';
                                                loadProgrammes($(this).val());
                                            });

                                            // Load programmes for the already-selected university (edit mode)
                                            <?php if ($update && !empty($university)): ?>
                                                loadProgrammes('<?php echo htmlspecialchars($university); ?>');
                                            <?php endif; ?>
                                        });
                                    </script>

                                    <!-- Removed Year Batch Code input -->

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="intake_date">Program Start Date:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="date" name="intake_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($intake_date); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="end_date">Program End Date:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="date" name="end_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($end_date); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="intake_end_date">Intake End Date:<span style="color: red;">*</span></label>
                                            </div>
                                            <div class="col">
                                                <input type="date" name="intake_end_date" class="form-control"
                                                    value="<?php echo htmlspecialchars($intake_end_date); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="batch_no">Batch No:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="text" name="batch_no" class="form-control"
                                                    value="<?php echo htmlspecialchars($batch_no); ?>"
                                                    placeholder="Batch No" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="intake_no">Intake No:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="text" name="intake_no" class="form-control"
                                                    value="<?php echo htmlspecialchars($intake_no); ?>"
                                                    placeholder="Intake No" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="year_no">Year No:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="text" name="year_no" class="form-control"
                                                    value="<?php echo htmlspecialchars($year_no); ?>"
                                                    placeholder="Year No" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Batch Intake -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="batch_intake">Batch Intake:(as a
                                                    text)<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="text" name="batch_intake" class="form-control"
                                                    value="<?php echo htmlspecialchars($batch_intake); ?>"
                                                    placeholder="Batch Intake" min="0" required>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Batch Limit -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="batch_limit">Batch Limit:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="number" name="batch_limit" class="form-control"
                                                    value="<?php echo htmlspecialchars($batch_limit); ?>"
                                                    placeholder="Batch Limit" min="0" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Attendance -->
                                    <!-- <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="attendance">Attendance:<span style="color: red;">*</span></label></div>
                                            <div class="col">
                                                <input type="text" name="attendance" class="form-control"
                                                    value="<?php echo htmlspecialchars($attendance); ?>"
                                                    placeholder="Attendance" required>
                                            </div>
                                        </div>
                                    </div> -->


                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label for="attendance">Attendance:<span style="color: red;">*</span></label>
                                            </div>
                                            <div class="col">
                                                <select name="attendance" class="form-control" required>
                                                    <option value="" disabled <?php echo empty($attendance) ? 'selected' : ''; ?>>Select Attendance</option>
                                                    <option value="Full Time" <?php echo ($attendance == "Full Time") ? 'selected' : ''; ?>>Full Time</option>
                                                    <option value="Part Time" <?php echo ($attendance == "Part Time") ? 'selected' : ''; ?>>Part Time</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Awarded By -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="awarded_by">Awarded By:</label></div>
                                            <div class="col">
                                                <input type="text" name="awarded_by" class="form-control"
                                                    value="<?php echo htmlspecialchars($awarded_by); ?>"
                                                    placeholder="Awarded By">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Qualification Level -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="qualification_level">Qualification
                                                    Level:</label></div>
                                            <div class="col">
                                                <input type="text" name="qualification_level" class="form-control"
                                                    value="<?php echo htmlspecialchars($qualification_level); ?>"
                                                    placeholder="Qualification Level">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Recognized By -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="recognized_by">Recognized By:</label>
                                            </div>
                                            <div class="col">
                                                <input type="text" name="recognized_by" class="form-control"
                                                    value="<?php echo htmlspecialchars($recognized_by); ?>"
                                                    placeholder="Recognized By">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Accredited By -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="accredited_by">Accredited By:</label>
                                            </div>
                                            <div class="col">
                                                <input type="text" name="accredited_by" class="form-control"
                                                    value="<?php echo htmlspecialchars($accredited_by); ?>"
                                                    placeholder="Accredited By">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Batch Hide/Active -->
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="batch_hide_active">Status:</label>
                                            </div>
                                            <div class="col">
                                                <select name="batch_hide_active" id="batch_hide_active" class="form-control">
                                                    <option value="" <?php echo empty($batch_hide_active) ? 'selected' : ''; ?>>Select Status</option>
                                                    <option value="active" <?php echo ($batch_hide_active == "active") ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($batch_hide_active == "inactive") ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <?php if ($update == true): ?>
                                            <button type="submit" name="save" class="btn btn-sm btn-info">Update
                                                Batch</button>
                                        <?php else: ?>
                                            <button type="submit" name="save" class="btn btn-sm btn-primary">Add
                                                Batch</button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <ul style="font-size: 12px;">
                            <li>MBA IHM - MBA IHM ()</li>
                            <li>Qualifi Health And Social Care - QLHS BG()</li>
                            <li>Foundation and Diploma in Project Management - IIPM BG()</li>
                            <li>Business Psychology - BP BG()</li>
                            <li>MBA - General - MBAG BG()</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="card shadow mb-4" style="font-size: 13px;">
                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                        <span
                            class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Batches</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Programme</th>
                                        <!-- <th>Batch Name</th> -->
                                        <!-- <th>Year & Batch</th> -->
                                        <th>Program Start Date</th>
                                        <th>Program End Date</th>
                                        <th>Intake End Date</th>
                                        <th>Batch No</th>
                                        <th>Intake No</th>
                                        <th>Year No</th>
                                        <th>Batch Intake</th>
                                        <th>Batch Limit</th>
                                        <th>Attendance</th>
                                        <th>Awarded By</th>
                                        <th>Qualification Level</th>
                                        <th>Recognized By</th>
                                        <th>Accredited By</th>
                                        <th>Status</th>
                                        <!-- <th>Created At</th> -->
                                        <!-- <th>Updated At</th> -->
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->prepare("
                                    SELECT batch_table.*, program_table.program_name 
                                    FROM batch_table
                                    LEFT JOIN program_table ON batch_table.programme = program_table.program_code 
                                    ");
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row['program_name']) . ' - ' . htmlspecialchars($row['batch_name']) . '</td>';
                                        // echo '<td>' . htmlspecialchars($row['year_batch_code']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['intake_date']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['end_date']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['intake_end_date']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['batch_no']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['intake_no']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['year_no']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['batch_intake']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['batch_limit']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['attendance']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['awarded_by']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['qualification_level']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['recognized_by']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['accredited_by']) . '</td>';
                                        if ($row['batch_hide_active'] === 'active') {
                                            echo '<td><span class="badge badge-success">Active</span></td>';
                                        } elseif ($row['batch_hide_active'] === 'inactive') {
                                            echo '<td><span class="badge badge-secondary">Inactive</span></td>';
                                        } else {
                                            echo '<td><span class="badge badge-light">-</span></td>';
                                        }
                                        // echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                        // echo '<td>' . htmlspecialchars($row['updated_at']) . '</td>';
                                        echo '<td>';
                                        echo '<a href="?edit=' . htmlspecialchars($row['id']) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> &nbsp;';
                                        echo '<a href="?delete=' . htmlspecialchars($row['id']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure you want to delete this batch?\')"> <i class="fas fa-trash-alt"></i></a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <script>
                        $(document).ready(function() {
                            // Set default number of entries for DataTables here
                            $('#dataTable').DataTable({
                                "pageLength": 100
                            });
                        });
                    </script>
                </div>
            </div>

            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content -->
    </div>
    <!-- End of Content Wrapper -->
</div>
<!-- End of Page Wrapper -->
<script src="vendor/datatables/jquery.dataTables.min.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
<link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
<script src="js/demo/datatables-demo.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Initialize Select2 for all dropdowns
    });
</script>
</body>

</html>