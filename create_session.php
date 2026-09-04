<?php
session_start();
include("database/connection.php");

error_reporting(E_ALL);
ini_set('display_errors', 1); 

$Session_username = $_SESSION['username'] ?? ''; 
$current_user_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

require_once 'PermissionChecking.php';

/* ==========================================================================
   AJAX HANDLERS (EDIT / DELETE / FETCH SINGLE)
   ========================================================================== */
if (isset($_POST['action'])) {
    // 1. Fetch Single Session for Edit Modal
    if ($_POST['action'] == 'fetch_single') {
        $id = (int)$_POST['id'];
        $res = $conn->query("SELECT * FROM tutor_session WHERE session_id = $id");
        echo json_encode($res->fetch_assoc());
        exit;
    }

    // 2. Update Session
    if ($_POST['action'] == 'update_session') {
        $id = (int)$_POST['session_id'];
        $name = $_POST['edit_session_name'];
        $type = $_POST['edit_session_type'];
        
        $stmt = $conn->prepare("UPDATE tutor_session SET session_name = ?, session_type = ?, created_by = ? WHERE session_id = ?");
        $stmt->bind_param("ssii", $name, $type, $current_user_id, $id);
        
        if ($stmt->execute()) echo "success";
        else echo "error";
        exit;
    }

    // 3. Delete Session with Allocation Check
    if ($_POST['action'] == 'delete_session') {
        $id = (int)$_POST['id'];

        // Check Table 1: tutor_allocate
        $check1 = $conn->prepare("SELECT COUNT(*) as total FROM tutor_allocate WHERE session_id = ?");
        $check1->bind_param("i", $id);
        $check1->execute();
        $res1 = $check1->get_result()->fetch_assoc();

        // Check Table 2: tutor_session_allocation
        $check2 = $conn->prepare("SELECT COUNT(*) as total FROM tutor_session_allocation WHERE session_id = ?");
        $check2->bind_param("i", $id);
        $check2->execute();
        $res2 = $check2->get_result()->fetch_assoc();

        if ($res1['total'] > 0 || $res2['total'] > 0) {
            // If exists in either table, restrict deletion
            echo "restricted";
        } else {
            $stmt = $conn->prepare("DELETE FROM tutor_session WHERE session_id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) echo "success";
            else echo "error";
        }
        exit;
    }
}

/* =====================
   SAVE NEW SESSION
===================== */
if (isset($_POST['save_session'])) {
    $program_code = $_POST['program_code'] ?? '';
    $session_name = $_POST['session_name'] ?? '';
    $session_type = $_POST['session_type'] ?? '';

    if ($program_code && $session_name && $session_type) {
        $stmt = $conn->prepare("INSERT INTO tutor_session (session_name, session_type, program_code, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $session_name, $session_type, $program_code, $current_user_id);
        $stmt->execute();
        $_SESSION['success_message'] = "Session saved successfully";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

include("includes/header.php");

$programs = $conn->query("SELECT program_code, program_name FROM program_table");

/* =====================
   FETCH SESSIONS (User-Based Filtering)
===================== */
$user_role_query = $conn->query("SELECT role FROM admin WHERE id = $current_user_id");
$user_data = $user_role_query->fetch_assoc();
$is_super_admin = (($user_data['role'] ?? '') === 'super_admin');

$query = "SELECT ts.session_id, ts.session_name, ts.session_type, ts.program_code, pt.program_name
          FROM tutor_session ts
          JOIN program_table pt ON ts.program_code = pt.program_code";

if (!$is_super_admin) {
    $query .= " WHERE ts.program_code IN (SELECT pau.program_code FROM program_allocation_user pau WHERE pau.user_id = $current_user_id)";
}
$query .= " ORDER BY ts.session_id DESC";
$sessions = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tutor Allocate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
</head>

<body class="bg-light">
    <div id="wrapper">
        <?php include("nav.php"); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("includes/topnav.php"); ?>
                <div class="p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">Insert Program Session</h4>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Add Session</h6>
                                </div>
                                <div class="card-body">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <form method="POST">
                                                <label class="fw-semibold">Programme</label>
                                                <select name="program_code" id="programSelect" class="form-select mb-3" required>
                                                    <option value="">-- Select Program --</option>
                                                </select>
                                                <label class="fw-semibold">Session</label>
                                                <input type="text" name="session_name" id="sessionInput" class="form-control mb-3" placeholder="Enter session name" required disabled>
                                                <label class="fw-semibold">Session Type</label>
                                                <select name="session_type" id="sessionType" class="form-select mb-3" required disabled>
                                                    <option value="">-- Select Type --</option>
                                                    <option value="One to One">One to One</option>
                                                    <option value="Group">Group</option>
                                                </select>
                                                <button type="submit" name="save_session" class="btn btn-primary w-100"><i class="fa fa-save"></i> Save</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">View Sessions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive mb-4">
                                        <div class="mb-3">
                                            <label>Filter by Program:</label>
                                            <select id="tableFilter" class="form-control" style="width: 100%;">
                                                <option value="">-- Select Program --</option>
                                                <?php $programs->data_seek(0); while ($row = $programs->fetch_assoc()) { ?>
                                                    <option value="<?= htmlspecialchars($row['program_name']) ?>"><?= htmlspecialchars($row['program_name']) ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <table id="studentsTable" class="table table-striped" style="width: 100%; font-size: 11px;">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Program</th>
                                                    <th>Session Name</th>
                                                    <th>Session Type</th>
                                                    <th class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = $sessions->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['program_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['session_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['session_type']) ?></td>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-warning edit-session" data-id="<?= $row['session_id'] ?>"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm btn-danger delete-session" data-id="<?= $row['session_id'] ?>"><i class="fas fa-trash"></i></button>
                                                    </td>
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
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editForm">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Session</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="session_id" id="edit_id">
                        <input type="hidden" name="action" value="update_session">
                        <div class="mb-3">
                            <label class="fw-bold">Session Name</label>
                            <input type="text" name="edit_session_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Session Type</label>
                            <select name="edit_session_type" id="edit_type" class="form-select" required>
                                <option value="One to One">One to One</option>
                                <option value="Group">Group</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning">Update Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content text-center p-3">
                <div class="modal-body">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p class="text-muted">You won't be able to revert this!</p>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete it!</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="restrictionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-ban"></i> Action Denied</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <p class="fw-bold">This session cannot be deleted.</p>
                    <p class="text-muted small">It is currently allocated to tutors in the system. Please remove all tutor allocations for this session before trying again.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa fa-check-circle"></i> Success</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="successMsg"><?= $_SESSION['success_message'] ?? '' ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#studentsTable').DataTable({
        "dom": 'rtip',
        "pageLength": 50,
        "ordering": true
    });

    $('#tableFilter').select2({ placeholder: "Search program to filter...", allowClear: true, width: '50%' });

    $('#tableFilter').on('change', function() {
        table.column(0).search($(this).val()).draw();
    });

    var $progSelect = $('#programSelect').select2({ placeholder: "-- Select Program --", allowClear: true, width: '100%' });

    $progSelect.on('change', function() {
        $('#sessionInput').prop('disabled', !$(this).val()).val('');
        $('#sessionType').prop('disabled', true).val('');
    });

    $('#sessionInput').on('input', function() {
        $('#sessionType').prop('disabled', !this.value.trim());
    });

    $.ajax({
        url: 'reports/AllStudents/fetch_user_programs.php',
        type: 'POST',
        success: function (data) {
            $('#programSelect').html(data).trigger('change');
        }
    });

    /* EDIT LOGIC */
    $(document).on('click', '.edit-session', function() {
        var id = $(this).data('id');
        $.ajax({
            url: '', type: 'POST', data: { action: 'fetch_single', id: id }, dataType: 'json',
            success: function(data) {
                $('#edit_id').val(data.session_id);
                $('#edit_name').val(data.session_name);
                $('#edit_type').val(data.session_type);
                $('#editModal').modal('show');
            }
        });
    });

    $('#editForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '', type: 'POST', data: $(this).serialize(),
            success: function(response) {
                if(response == "success") {
                    $('#editModal').modal('hide');
                    $('#successMsg').text("Session updated successfully!");
                    $('#successModal').modal('show');
                    $('#successModal').on('hidden.bs.modal', function () { location.reload(); });
                }
            }
        });
    });

    /* DELETE LOGIC */
    var deleteId = null;
    $(document).on('click', '.delete-session', function() {
        deleteId = $(this).data('id');
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function() {
        $.ajax({
            url: '', type: 'POST', data: { action: 'delete_session', id: deleteId },
            success: function(response) {
                $('#deleteModal').modal('hide');
                if(response.trim() == "restricted") {
                    setTimeout(function(){ $('#restrictionModal').modal('show'); }, 400);
                } else if(response.trim() == "success") {
                    $('#successMsg').text("Session deleted successfully!");
                    $('#successModal').modal('show');
                    $('#successModal').on('hidden.bs.modal', function () { location.reload(); });
                }
            }
        });
    });
});
</script>

<?php 
if (isset($_SESSION['success_message'])) { 
    echo "<script>new bootstrap.Modal(document.getElementById('successModal')).show();</script>";
    unset($_SESSION['success_message']);
}
include("includes/footer.php"); 
?>
</body>
</html>