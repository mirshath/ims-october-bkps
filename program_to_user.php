<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

$Session_username = $_SESSION['username'];
require_once 'PermissionChecking.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_POST['user_id'] ?? '';
    $program_codes = $_POST['program_code'] ?? [];
    $id = $_POST['id'] ?? '';

    if (!empty($user_id)) {
        // 🔹 Fetch currently allocated programs for this user
        $existing_programs = [];
        $stmt = $conn->prepare("SELECT TRIM(CAST(program_code AS CHAR)) AS program_code 
                                FROM program_allocation_user WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existing_programs[] = trim($row['program_code']);
        }
        $stmt->close();

        // 🔹 Programs to add
        $to_add = array_diff($program_codes, $existing_programs);

        // 🔹 Programs to remove
        $to_remove = array_diff($existing_programs, $program_codes);

        // 🔹 Insert new programs
        if (!empty($to_add)) {
            $insert_sql = "INSERT INTO program_allocation_user (user_id, program_code, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($insert_sql);
            foreach ($to_add as $program_code) {
                $stmt->bind_param("is", $user_id, $program_code);
                $stmt->execute();
            }
            $stmt->close();
        }

        // 🔹 Delete unchecked programs
        if (!empty($to_remove)) {
            $delete_sql = "DELETE FROM program_allocation_user 
                           WHERE user_id = ? AND TRIM(CAST(program_code AS CHAR)) = ?";
            $stmt = $conn->prepare($delete_sql);
            foreach ($to_remove as $program_code) {
                $stmt->bind_param("is", $user_id, $program_code);
                $stmt->execute();
            }
            $stmt->close();
        }

        // 🔹 Show final alert
        echo "<script>
                alert('✅ Program allocations updated successfully!');
                window.location.href='program_to_user';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('⚠️ Please select a user first.');
                window.location.href='program_to_user';
              </script>";
        exit();
    }
}

// 🔹 Fetch all users and programs
$users_result = $conn->query("SELECT id, username FROM admin ORDER BY username ASC");
$program_result = $conn->query("SELECT program_code, program_name FROM program_table ORDER BY program_name ASC");
?>

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column bg-light">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="container py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-primary">
                        <i class="fas fa-tasks me-2"></i>Allocate Program to Coordinator
                    </h4>
                </div>

                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body p-4">

                        <form method="POST" action="">
                            <input type="hidden" name="id" id="edit_id">

                            <!-- Select User -->
                            <div class="mb-4 w-50">
                                <label for="username" class="form-label fw-semibold">Select User</label>
                                <select id="username" name="user_id" class="form-select select2" required>
                                    <option value="" disabled selected>Choose a User</option>
                                    <?php while ($row = $users_result->fetch_assoc()) { ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <!-- Select Programs -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="fw-semibold mb-0">Select Programs</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="checkbox" id="select_all_programs">
                                        <span class="fw-bold">Select All</span>
                                    </div>
                                </div>

                                <div id="program_list" class="program-grid">
                                    <?php
                                    $program_result->data_seek(0);
                                    while ($row = $program_result->fetch_assoc()) {
                                        echo '
                                        <div class="program-item">
                                            <label>
                                                <input type="checkbox" class="program_checkbox" name="program_code[]" value="' . htmlspecialchars($row['program_code']) . '">
                                                <span class="fw-semibold ms-2">' . htmlspecialchars($row['program_name']) . '</span>
                                            </label>
                                        </div>';
                                    }
                                    ?>
                                </div>

                                <style>
                                    .program-grid {
                                        display: grid;
                                        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
                                        gap: 12px 20px;
                                        padding: 15px 0;
                                    }

                                    .program-item {
                                        display: flex;
                                        align-items: center;
                                        background: #f8f9fa;
                                        border: 1px solid #dee2e6;
                                        border-radius: 10px;
                                        padding: 8px 12px;
                                        transition: all 0.2s ease-in-out;
                                    }

                                    .program-item:hover {
                                        background: #eef1f3;
                                        transform: scale(1.02);
                                    }
                                </style>
                            </div>

                            <div class="d-flex justify-content-end mt-4 border-top pt-3">
                                <button type="submit" class="btn btn-primary me-2 px-4">
                                    <i class="fas fa-save me-1"></i> Save
                                </button>
                                <button type="reset" class="btn btn-secondary px-4" onclick="clearForm()">
                                    <i class="fas fa-undo me-1"></i> Clear
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>

<!-- JS Libraries -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // ✅ Select All toggle
        $('#select_all_programs').on('change', function() {
            $('.program_checkbox').prop('checked', $(this).is(':checked'));
        });

        // ✅ Update Select All based on individual selections
        $(document).on('change', '.program_checkbox', function() {
            $('#select_all_programs').prop('checked',
                $('.program_checkbox').length === $('.program_checkbox:checked').length
            );
        });

        // ✅ Load user programs
        $('#username').on('change', function() {
            var userId = $(this).val();
            $('.program_checkbox').prop('checked', false);
            $('#select_all_programs').prop('checked', false);

            if (userId) {
                $.ajax({
                    url: 'fetch_user_programs.php',
                    type: 'POST',
                    cache: false,
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(data) {
                        data.forEach(function(code) {
                            $('input[value="' + code + '"]').prop('checked', true);
                        });
                        $('#select_all_programs').prop('checked',
                            $('.program_checkbox').length === $('.program_checkbox:checked').length
                        );
                    }
                });
            }
        });
    });

    function clearForm() {
        $('#edit_id').val('');
        $('#username').val('').trigger('change');
        $('.program_checkbox').prop('checked', false);
        $('#select_all_programs').prop('checked', false);
    }
</script>
