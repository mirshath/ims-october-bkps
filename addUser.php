<?php
//session_start();
$Session_username = $_SESSION['username'] ?? null;
include("database/connection.php");
include("includes/header.php");

if (!$Session_username) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

// Permission Checking
require_once 'PermissionChecking.php';

// ------------------- Registration Logic -------------------
if (isset($_POST['register'])) {
    $title = trim($_POST['title'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $nic = trim($_POST['nic'] ?? '');

    if ($username && $email && $password && $role) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO admin (title, full_name, username, admin_email, password, role, department, nic, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("ssssssss", $title, $full_name, $username, $email, $hashed_password, $role, $department, $nic);
            if ($stmt->execute()) {
                $_SESSION['message'] = "User Added Successfully!";
                $redirect = $_SERVER['HTTP_REFERER'] ?? 'addUser.php';
                echo '<script>window.location.href = "' . htmlspecialchars($redirect) . '";</script>';
            } else {
                echo "<script>alert('Database Error!');</script>";
            }
            $stmt->close();
        }
    }
}

// ------------------- Update Logic -------------------
if (isset($_POST['update'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_title = trim($_POST['title'] ?? '');
    $new_full_name = trim($_POST['full_name'] ?? '');
    $new_email = trim($_POST['admin_email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_role = $_POST['role'] ?? '';
    $new_department = trim($_POST['department'] ?? '');
    $new_nic = trim($_POST['nic'] ?? '');

    if ($user_id && $new_email && $new_role) {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE admin SET title = ?, full_name = ?, admin_email = ?, password = ?, role = ?, department = ?, nic = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssi", $new_title, $new_full_name, $new_email, $hashed_password, $new_role, $new_department, $new_nic, $user_id);
        } else {
            $query = "UPDATE admin SET title = ?, full_name = ?, admin_email = ?, role = ?, department = ?, nic = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $new_title, $new_full_name, $new_email, $new_role, $new_department, $new_nic, $user_id);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $_SESSION['message'] = "User updated successfully!";
                $redirect = $_SERVER['HTTP_REFERER'] ?? 'addUser.php';
                echo '<script>window.location.href = "' . htmlspecialchars($redirect) . '";</script>';
            } else {
                echo "<script>alert('Database Error!');</script>";
            }
            $stmt->close();
        }
    }
}

// ------------------- Status Toggle Logic -------------------
if (isset($_GET['toggle_status'])) {
    $user_id = (int)($_GET['toggle_status'] ?? 0);
    $current_status = $_GET['current_status'] ?? 'active';
    $new_status = ($current_status == 'active' || $current_status == 'Active') ? 'inactive' : 'active';
    
    if ($user_id > 0) {
        $query = "UPDATE admin SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $user_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "User status updated successfully!";
            }
            $stmt->close();
        }
    }
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'addUser.php';
    echo '<script>window.location.href = "' . htmlspecialchars($redirect) . '";</script>';
    exit();
}

// ------------------- Delete Logic -------------------
if (isset($_GET['delete_id'])) {
    $user_id = (int)($_GET['delete_id'] ?? 0);
    if ($user_id > 0) {
        $query = "DELETE FROM admin WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $_SESSION['message'] = "User deleted successfully.";
            $redirect = $_SERVER['HTTP_REFERER'] ?? 'addUser.php';
            echo '<script>window.location.href = "' . htmlspecialchars($redirect) . '";</script>';
            $stmt->close();
            exit();
        }
    }
}

// ------------------- Fetch Users (Optimized) -------------------
// Only select needed columns for faster loading
$query = "SELECT id, title, full_name, username, admin_email, department, nic, status, role FROM admin ORDER BY id DESC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// 1. Initialize your counters
$total_records = $result->num_rows;
$active_records = 0;

// 2. Loop through to count active staff members
while ($row = $result->fetch_assoc()) {
    if (isset($row['status']) && strtolower($row['status']) === 'active') {
        $active_records++;
    }
}

// 3. CRITICAL: Reset the pointer back to the first row 
// This allows your HTML table below to loop through the records again safely!
if ($total_records > 0) {
    $result->data_seek(0);
}

// Predefined departments
$departments = [
    "Academic Administration",
    "Digital Studio",
    "Driver",
    "Examinations",
    "Faculty of Management & Technologies",
    "Feculty of Life & Medical Science",
    "Finance",
    "Front Office",
    "Global Centre",
    "Human Resources",
    "IT",
    "Library",
    "NU Support Centre",
    "Operations",
    "Post Graduate Programmes",
    "Student Recruitment",
    "Web & System Administration"
];
sort($departments);
?>

<!-- Load CSS and JS from CDN with async for faster loading -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<!-- FRONTEND -->
<div id="wrapper">
    <?php include('nav.php'); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Add | Update User</h4>
                </div>

                <!-- Registration Form -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">Add User</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST" autocomplete="off">
                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="title" class="form-label">Title</label></div>
                                        <div class="col-md-8">
                                            <select class="form-control select2" id="title" name="title" required style="width: 100%;">
                                                <option value="">Select Title</option>
                                                <option value="Mr.">Mr.</option>
                                                <option value="Ms.">Ms.</option>
                                                <option value="Mrs.">Mrs.</option>
                                                <option value="Dr.">Dr.</option>
                                                <option value="Prof.">Prof.</option>
                                                <option value="Rev.">Rev.</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="full_name" class="form-label">Full Name</label></div>
                                        <div class="col-md-8"><input type="text" class="form-control" name="full_name" placeholder="full name" required></div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="username" class="form-label">Username</label></div>
                                        <div class="col-md-8"><input type="text" class="form-control" name="username" placeholder="username" required></div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="admin_email" class="form-label">Email</label></div>
                                        <div class="col-md-8"><input type="email" class="form-control" placeholder="email" name="admin_email" required></div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label>Password</label></div>
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" placeholder="password" required onkeyup="checkPasswords()">
                                                <span class="input-group-text" onclick="togglePasswordVisibility('password')">
                                                    <i class="fas fa-eye" id="eye-icon-password"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label>Confirm Password</label></div>
                                        <div class="col-md-8">
                                            <div class="input-group">
                                                <input type="password" class="form-control" placeholder="confirm password" id="confirm_password" required onkeyup="checkPasswords()">
                                                <span class="input-group-text" onclick="togglePasswordVisibility('confirm_password')">
                                                    <i class="fas fa-eye" id="eye-icon-confirm"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="role" class="form-label">Role</label></div>
                                        <div class="col-md-8">
                                            <select class="form-control select2" id="role" name="role" required style="width: 100%;">
                                                <option value="">Select Role</option>
                                                <option value="super_admin">Super Admin</option>
                                                <option value="manager">Manager</option>
                                                <option value="data_enter">Data Enter</option>
                                                <option value="lecture">Lecture</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-md-4"><label for="department" class="form-label">Department</label></div>
                                        <div class="col-md-8">
                                            <select class="form-control select2" id="department" name="department" style="width: 100%;">
                                                <option value="">Select Department</option>
                                                <?php foreach ($departments as $dept): ?>
                                                    <option value="<?= htmlspecialchars($dept); ?>"><?= htmlspecialchars($dept); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-4"><label for="nic" class="form-label">ID/NIC</label></div>
                                        <div class="col-md-8"><input type="text" class="form-control" name="nic" placeholder="ID/NIC"></div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" name="register" id="registerButton" class="btn btn-primary w-50" disabled>Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Table -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                <!-- Title stays on the left -->
                                <span style="font-size: 18px; font-weight: 500;">Users Status & Updates</span> 
                                
                                <!-- Stats group automatically gets pushed to the right side -->
                                <div>
                                    <span style="color: #737d85; font-weight: 500;">Records: <?php echo $total_records; ?></span>
                                    <span style="color: #ccc; margin: 0 5px;">|</span> 
                                    <span style="color: #135746; font-weight: bold;">Active Staff: <?php echo $active_records; ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered" id="userTable" style="font-size: 11px;">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Full Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>ID/NIC</th>
                                            <th>New Password</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1;
                                        while ($row = $result->fetch_assoc()) { 
                                            $status = $row['status'] ?? 'active';
                                            $is_active = ($status == 'active' || $status == 'Active' || $status == null);
                                        ?>
                                            <tr class="<?= $is_active ? '' : 'inactive-row'; ?>">
                                                <form action="" method="POST" autocomplete="off">
                                                    <td><?= $i++; ?></td>
                                                    <td>
                                                        <select name="title" class="form-control title" style="width: 100%; font-size: 11px;" required>
                                                            <option value="Mr." <?= ($row['title'] ?? '') == 'Mr.' ? 'selected' : ''; ?>>Mr.</option>
                                                            <option value="Ms." <?= ($row['title'] ?? '') == 'Ms.' ? 'selected' : ''; ?>>Ms.</option>
                                                            <option value="Mrs." <?= ($row['title'] ?? '') == 'Mrs.' ? 'selected' : ''; ?>>Mrs.</option>
                                                            <option value="Dr." <?= ($row['title'] ?? '') == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                                                            <option value="Prof." <?= ($row['title'] ?? '') == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                                                            <option value="Rev." <?= ($row['title'] ?? '') == 'Rev.' ? 'selected' : ''; ?>>Rev.</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name'] ?? '', ENT_QUOTES); ?>" class="form-control" style="font-size: 11px;">
                                                    </td>
                                                    <td style="font-size: 11px;"><?= htmlspecialchars($row['username'] ?? '', ENT_QUOTES); ?></td>
                                                    <td>
                                                        <input type="email" name="admin_email" value="<?= htmlspecialchars($row['admin_email'] ?? '', ENT_QUOTES); ?>" class="form-control" style="font-size: 11px;" required>
                                                    </td>
                                                    <td>
                                                        <select name="department" class="form-control" style="width: 100%; font-size: 11px;">
                                                            <option value=""></option>
                                                            <?php foreach ($departments as $dept): ?>
                                                                <option value="<?= htmlspecialchars($dept); ?>" <?= ($dept == ($row['department'] ?? '')) ? 'selected' : ''; ?>>
                                                                    <?= htmlspecialchars($dept); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="nic" value="<?= htmlspecialchars($row['nic'] ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="ID/NIC" style="font-size: 11px;">
                                                    </td>
                                                    <td>
                                                        <div style="position: relative;">
                                                            <input type="password" name="new_password" class="form-control new-password-input" placeholder="New Password" autocomplete="off" style="padding-right: 2.2rem; font-size: 11px;">
                                                            <span class="toggle-password-visibility" style="
                                                                position: absolute;
                                                                top: 50%;
                                                                right: 0.65rem;
                                                                transform: translateY(-50%);
                                                                cursor: pointer;
                                                                color: #6c757d;
                                                                font-size: 0.9rem;
                                                                z-index: 2;
                                                            ">
                                                                <i class="fa fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <select name="role" class="form-control" style="width: 100%; font-size: 11px;" required>
                                                            <option value=""></option>
                                                            <option value="super_admin" <?= ($row['role'] ?? '') == 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                                            <option value="manager" <?= ($row['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                                            <option value="data_enter" <?= ($row['role'] ?? '') == 'data_enter' ? 'selected' : ''; ?>>Data Enter</option>
                                                            <option value="lecture" <?= ($row['role'] ?? '') == 'lecture' ? 'selected' : ''; ?>>Lecture</option>
                                                        </select>
                                                    </td>
                                                    <td style="text-align: center; vertical-align: middle;">
                                                        <span class="status-icon <?= $is_active ? 'status-active' : 'status-inactive'; ?>" 
                                                              style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; <?= $is_active ? 'background-color: #28a745;' : 'background-color: #dc3545;' ?> color: white; font-size: 12px;">
                                                            <i class="fas <?= $is_active ? 'fa-check' : 'fa-times'; ?>"></i>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="user_id" value="<?= (int)$row['id']; ?>">
                                                        <button type="submit" name="update" class="btn btn-sm" title="Update User" style="font-size: 10px; padding: 2px 6px; background-color:#042d5c; color:antiquewhite;">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?toggle_status=<?= (int)$row['id']; ?>&current_status=<?= $status; ?>" class="btn btn-sm <?= $is_active ? 'btn-warning' : 'btn-success'; ?>" title="<?= $is_active ? 'Deactivate' : 'Activate'; ?>" style="font-size: 10px; padding: 2px 6px;" onclick="return confirm('Are you sure you want to <?= $is_active ? 'deactivate' : 'activate'; ?> this user?')">
                                                            <i class="fas <?= $is_active ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                        </a>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table><span style="color: #135746; font-weight: bold;">Active Staff: <?php echo $active_records; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function checkPasswords() {
        const pass = document.getElementById("password").value;
        const confirm = document.getElementById("confirm_password").value;
        document.getElementById("registerButton").disabled = !(pass && confirm && pass === confirm);
    }

    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const eyeIcon = document.getElementById(`eye-icon-${inputId}`);
        if (input.type === "password") {
            input.type = "text";
            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye");
        }
    }

    // Initialize Select2 and DataTable after page load
    $(document).ready(function() {
        // Initialize Select2 for all dropdowns
        $('.select2, .select2-title, .select2-department').select2({
            placeholder: "Select an option",
            allowClear: true,
            width: 'resolve',
            dropdownCssClass: 'small-dropdown'
        });

        // Initialize DataTable with optimizations
       $(document).ready(function () {

            $.fn.dataTable.ext.ofnSearch['html-input'] = function (data) {
                return $('<div>').html(data).find('input').val() || data;
            };

            $.fn.dataTable.ext.ofnSearch['html-select'] = function (data) {
                return $('<div>').html(data).find('option:selected').text() || data;
            };

            $('#userTable').DataTable({
                pageLength: 50,
                stateSave: true,
                searching: true,

                columnDefs: [
                    { targets: [1,2,4,5,6,8], type: "html-input" },
                    { targets: [7,10], searchable: false }
                ]
            });

        });

        // Password toggle functionality
        $('.toggle-password-visibility').off('click').on('click', function() {
            var $input = $(this).siblings('.new-password-input');
            var $icon = $(this).find('i');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    });
</script>

<style>
    /* Table font size */
    .table {
        font-size: 11px !important;
    }
    .table th,
    .table td {
        font-size: 11px !important;
        vertical-align: middle !important;
    }
    
    /* Form controls in table */
    .table .form-control {
        font-size: 11px !important;
        padding: 2px 6px !important;
        height: auto !important;
    }
    .table .form-select {
        font-size: 11px !important;
        padding: 2px 6px !important;
        height: auto !important;
    }
    
    /* Buttons in table */
    .table .btn-sm {
        font-size: 10px !important;
        padding: 2px 6px !important;
    }
    
    /* Select2 dropdown */
    .small-dropdown .select2-results__option {
        font-size: 11px !important;
    }
    .select2-container--default .select2-selection--single {
        height: auto !important;
        padding: 2px 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 11px !important;
        line-height: 1.5 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100% !important;
    }
    
    /* Status icons - Centered */
    .status-icon {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        color: white !important;
        font-size: 10px !important;
        margin: 0 auto !important;
    }
    .status-active {
        background-color: #135746 !important;
    }
    .status-inactive {
        background-color: #b41424 !important;
    }
    
    /* Inactive row styling */
    .inactive-row td {
        color: #dc3545 !important;
        text-decoration: line-through;
        opacity: 0.7;
    }
    .inactive-row .status-icon {
        opacity: 0.8;
    }
    .inactive-row input,
    .inactive-row select {
        opacity: 0.6;
    }
    
    /* Email display */
    .table td input[type="email"] {
        min-width: 120px;
    }
    
    /* Title dropdown full text */
    .select2-title .select2-selection__rendered {
        font-size: 11px !important;
    }
</style>

<?php include("includes/footer.php"); ?>