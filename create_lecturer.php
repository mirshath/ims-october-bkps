<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 




// Initialize variables
$lecturer_name = $qualification = "";
$programs = []; // Initialize programs as an array
$id = 0;
$update = false;

// Fetch programs for the dropdown
$sql = "SELECT * FROM program_table";
$result = mysqli_query($conn, $sql);
$programsOptions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $programsOptions[] = $row;
}
$knownProgramNames = array_map(function ($p) {
    return $p['program_name'];
}, $programsOptions);

// Fetch lecturers from admin table where role = 'lecture'
$sql_admins = "SELECT username, title, full_name FROM admin WHERE role = 'lecture'";
$result_admins = mysqli_query($conn, $sql_admins);
$adminLecturers = [];
while ($row_admin = mysqli_fetch_assoc($result_admins)) {
    $adminLecturers[] = $row_admin;
}

// ---------- Helper: normalize a program name for reliable comparison ----------
function normalize_program_name($name)
{
    return mb_strtolower(trim($name));
}

/**
 * Parse whatever is stored in lecturer_table.programs back into a clean array.
 *
 * New rows are stored as a JSON array (see save/update below), which has no
 * ambiguity issues. Older rows were stored as a plain comma-joined string,
 * which breaks when a program name itself contains a comma (e.g.
 * "BSc (Hons) International Tourism, Hospitality & Events"). For those
 * legacy rows we greedily match against the *known* program names
 * (longest name first) so a name containing a comma is still recovered
 * as a single item instead of being split in two.
 */
function parse_stored_programs($stored, array $knownProgramNames)
{
    $stored = trim((string) $stored);
    if ($stored === '') {
        return [];
    }

    // 1) New format: JSON array
    $decoded = json_decode($stored, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded), fn($v) => $v !== ''));
    }

    // 2) Legacy format: comma-joined string, possibly with commas inside names.
    //    Try to match the longest known program names first.
    $names = $knownProgramNames;
    usort($names, fn($a, $b) => mb_strlen($b) - mb_strlen($a));

    $result = [];
    $remaining = $stored;
    while ($remaining !== '') {
        $matched = false;
        foreach ($names as $name) {
            $len = mb_strlen($name);
            if ($len > 0 && mb_substr($remaining, 0, $len) === $name) {
                $next = mb_substr($remaining, $len, 1);
                if ($next === ',' || $next === '') {
                    $result[] = $name;
                    $remaining = ltrim(mb_substr($remaining, $len + ($next === ',' ? 1 : 0)), " \t\n\r\0\x0B,");
                    $matched = true;
                    break;
                }
            }
        }
        if (!$matched) {
            // Couldn't confidently match against known names (e.g. a program
            // that has since been renamed/deleted). Fall back to a naive
            // split for whatever text is left, rather than losing it.
            foreach (explode(',', $remaining) as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $result[] = $piece;
                }
            }
            $remaining = '';
        }
    }
    return $result;
}

// Handle form submission (Add)
if (isset($_POST['save'])) {
    $lecturer_username = $_POST['lecturer_name'];
    $qualification = $_POST['qualification'];
    $selectedPrograms = isset($_POST['programs']) ? $_POST['programs'] : [];
    $programsJson = json_encode(array_values($selectedPrograms));

    // Fetch the title and full_name from the admin table for the selected username
    $admin_stmt = $conn->prepare("SELECT title, full_name FROM admin WHERE username = ?");
    $admin_stmt->bind_param("s", $lecturer_username);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin_row = $admin_result->fetch_assoc();
    $admin_stmt->close();

    $title = $admin_row['title'] ?? 'Mr';
    $full_name = $admin_row['full_name'] ?? $lecturer_username;
    $combined_name = $full_name;

    $stmt = $conn->prepare("INSERT INTO lecturer_table (title, lecturer_name, hourly_rate, qualification, programs) VALUES (?, ?, 0, ?, ?)");
    $stmt->bind_param("ssss", $title, $combined_name, $qualification, $programsJson);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Lecturer added successfully!";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    echo '<script>window.location.href = "create_lecturer";</script>';
    exit();
}

// Handle record update (Edit form load)
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $update = true;

    $stmt = $conn->prepare("SELECT * FROM lecturer_table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        $lecturer_name = $row['lecturer_name']; // This is now the full_name
        $qualification = $row['qualification'];
        $programs = parse_stored_programs($row['programs'], $knownProgramNames);
    }
}

// Handle form update
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $lecturer_username = $_POST['lecturer_name'];
    $qualification = $_POST['qualification'];
    $selectedPrograms = isset($_POST['programs']) ? $_POST['programs'] : [];
    $programsJson = json_encode(array_values($selectedPrograms));

    // Fetch the title and full_name from the admin table for the selected username
    $admin_stmt = $conn->prepare("SELECT title, full_name FROM admin WHERE username = ?");
    $admin_stmt->bind_param("s", $lecturer_username);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin_row = $admin_result->fetch_assoc();
    $admin_stmt->close();

    $title = $admin_row['title'] ?? 'Mr';
    $full_name = $admin_row['full_name'] ?? $lecturer_username;
    $combined_name = $full_name;

    $stmt = $conn->prepare("UPDATE lecturer_table SET title = ?, lecturer_name = ?, qualification = ?, programs = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $title, $combined_name, $qualification, $programsJson, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Lecturer updated successfully!";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    echo '<script>window.location.href = "create_lecturer";</script>';
    exit();
}

// Handle record deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM lecturer_table WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Lecturer deleted successfully!";
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
    $stmt->close();
    echo '<script>window.location.href = "create_lecturer";</script>';
    exit();
}

// Fetch all records
$result = $conn->query("SELECT * FROM lecturer_table");

// Pre-compute a normalized set of the currently selected programs (for the edit form)
$normalizedSelectedPrograms = array_map('normalize_program_name', $programs);

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
                    <h4 class="h4 mb-0 text-gray-800">Lecture Managment</h4>
                </div>


                <!-- add form // create forms -->

                <div class="row mb-5">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add Lecturer</h6>
                            </div>

                            <div class="card-body">
                                <form action="" method="post" class="mb-3">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="lecturer_name">Lecturer Name:</label>
                                            </div>
                                            <div class="col">
                                                <select class="form-control select2" id="lecturer_name"
                                                    name="lecturer_name" required style="width: 100%;">
                                                    <option value="">Select Lecturer</option>
                                                    <?php foreach ($adminLecturers as $admin): ?>
                                                        <?php
                                                        $combined_admin_name = ($admin['title'] ?? '') . ' ' . ($admin['full_name'] ?? '');
                                                        ?>
                                                        <option value="<?php echo htmlspecialchars($admin['username']); ?>"
                                                            <?php echo $lecturer_name == $admin['full_name'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($admin['username'] . ' (' . $combined_admin_name . ')'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        // Initialize select2 on the Lecturer Name select
                                        $(document).ready(function () {
                                            $('#lecturer_name').select2({
                                                placeholder: "Select Lecturer",
                                                allowClear: true,
                                                width: 'resolve'
                                            });
                                        });
                                    </script>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label for="qualification">Qualification:</label>
                                            </div>
                                            <div class="col"><textarea class="form-control" id="qualification"
                                                    name="qualification" placeholder="Qualifications" rows="3"
                                                    required><?php echo htmlspecialchars($qualification); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-3"> <label>Programs</label></div>
                                            <div class="col">
                                                <?php foreach ($programsOptions as $program): ?>
                                                    <?php
                                                    $normalizedOptionName = normalize_program_name($program['program_name']);
                                                    $isChecked = in_array($normalizedOptionName, $normalizedSelectedPrograms, true);
                                                    ?>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="programs[]"
                                                            value="<?php echo htmlspecialchars($program['program_name']); ?>"
                                                            <?php echo $isChecked ? 'checked' : ''; ?>>
                                                        <label
                                                            class="form-check-label"><?php echo htmlspecialchars($program['program_name']); ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <?php if ($update): ?>
                                            <button type="submit" class="btn btn-primary" name="update">Update</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-primary" name="save">Save</button>
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
                        <span
                            class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                            style="width: 30px; height: 30px;">
                            <i class="fas fa-list"></i>
                        </span> &nbsp;&nbsp;&nbsp;&nbsp;
                        <h6 class="mb-0">Current Lecturers</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <!-- <th>ID</th> -->
                                        <th>Lecturer Name</th>
                                        <th>Qualification</th>
                                        <th>Programs</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch all lecturers to display in the table
                                    $sql_table = "SELECT * FROM lecturer_table";
                                    $lecturers_result = mysqli_query($conn, $sql_table);
                                    while ($row = mysqli_fetch_assoc($lecturers_result)) {
                                        $display_name = htmlspecialchars($row['title'] . ' ' . $row['lecturer_name']);
                                        // Decode/parse for display so JSON (or legacy comma) storage
                                        // shows as a readable, comma-separated list to the user.
                                        $rowPrograms = parse_stored_programs($row['programs'], $knownProgramNames);
                                        $displayPrograms = htmlspecialchars(implode(', ', $rowPrograms));

                                        echo '<tr>';
                                        echo '<td>' . $display_name . '</td>';
                                        echo '<td>' . htmlspecialchars($row['qualification']) . '</td>';
                                        echo '<td>' . $displayPrograms . '</td>';
                                        echo '<td>';
                                        echo '<a href="?edit=' . htmlspecialchars($row['id']) . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a> &nbsp;';
                                        echo '<a href="?delete=' . htmlspecialchars($row['id']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')"><i class="fas fa-trash-alt"></i></a>';
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