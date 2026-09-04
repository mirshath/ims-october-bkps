<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 

$generated_links_data = [];

// Get current user info from session
$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

// Role-based query: super_admin sees all, others see only allocated program links
if ($role === 'super_admin') {
    $sql = "SELECT link_id, programme_id, batch_id, module_id, lecturer_id, active, created_by, created_at FROM feedback_links ORDER BY created_at DESC";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT fl.link_id, fl.programme_id, fl.batch_id, fl.module_id, fl.lecturer_id, fl.active, fl.created_by, fl.created_at 
            FROM feedback_links AS fl
            INNER JOIN program_allocation_user AS pau ON fl.programme_id = pau.program_code
            WHERE pau.user_id = ?
            ORDER BY fl.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Fetch final year criteria options (stored as e.g. "final_Presentation")
$final_year_criteria_options = [];
$criteria_sql = "SELECT id, criteria_names FROM final_year_criteria ORDER BY id ASC";
$criteria_result = $conn->query($criteria_sql);
if ($criteria_result && $criteria_result->num_rows > 0) {
    while ($crow = $criteria_result->fetch_assoc()) {
        $raw_name = $crow['criteria_names'];
        // Strip the "final_" prefix for display, keep raw value as the option value
        $display_name = preg_replace('/^final_/', '', $raw_name);
        $display_name = str_replace('_', ' ', $display_name);
        $final_year_criteria_options[] = [
            'value' => $raw_name,
            'label' => $display_name
        ];
    }
}

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $programme_id = $row['programme_id'];
        $batch_id = $row['batch_id'];
        $module_id = $row['module_id'];
        $lecturer_id = $row['lecturer_id'];
        $active = $row['active']; // Fetch active status
        $created_by = $row['created_by'] ?? 'N/A'; // Fetch created_by

        // Fetch programme name
        $stmt_program = $conn->prepare("SELECT program_name FROM program_table WHERE program_code = ?");
        $stmt_program->bind_param("s", $programme_id);
        $stmt_program->execute();
        $result_program = $stmt_program->get_result();
        $program_name = $result_program->fetch_assoc()['program_name'] ?? 'N/A';
        $stmt_program->close();

        // Fetch batch name
        $stmt_batch = $conn->prepare("SELECT batch_name FROM batch_table WHERE id = ?");
        $stmt_batch->bind_param("s", $batch_id);
        $stmt_batch->execute();
        $result_batch = $stmt_batch->get_result();
        $batch_name = $result_batch->fetch_assoc()['batch_name'] ?? 'N/A';
        $stmt_batch->close();

        // Fetch module name
        $stmt_module = $conn->prepare("SELECT module_name FROM modules WHERE id = ?");
        $stmt_module->bind_param("s", $module_id);
        $stmt_module->execute();
        $result_module = $stmt_module->get_result();
        $module_name = $result_module->fetch_assoc()['module_name'] ?? 'N/A';
        $stmt_module->close();

        // Fetch lecturer name and full_name from admin table
        $stmt_lecturer = $conn->prepare("SELECT l.lecturer_name as username, a.full_name 
                                         FROM lecturer_table l 
                                         LEFT JOIN admin a ON l.lecturer_name = a.username 
                                         WHERE l.id = ?");
        $stmt_lecturer->bind_param("s", $lecturer_id);
        $stmt_lecturer->execute();
        $result_lecturer = $stmt_lecturer->get_result();
        $lecturer_row = $result_lecturer->fetch_assoc();
        $lecturer_name = $lecturer_row['username'] ?? 'N/A';
        if (!empty($lecturer_row['full_name'])) {
            $lecturer_name .= ' (' . $lecturer_row['full_name'] . ')';
        }
        $stmt_lecturer->close();

        $generated_links_data[] = [
            'link_id' => $row['link_id'],
            'programme_name' => $program_name,
            'batch_name' => $batch_name,
            'module_name' => $module_name,
            'lecturer_name' => $lecturer_name,
            'active' => $active,
            'created_by' => $created_by,
            'created_at' => $row['created_at']
        ];
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
            <div class="p-3" style="font-size: 14px;">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">
                        Student Feedback Form Link Generation
                    </h4>
                </div>

                <!-- Form Section -->
                <form action="" method="POST">
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">

                                        <!-- Programme -->
                                        <div class="col-md-3">
                                            <label for="programme">Programme</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="programme_id" id="programme" class="form-control select2"
                                                    style="font-size: 12px;" required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Batch -->
                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" class="form-control select2"
                                                    style="font-size: 12px;" required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Module -->
                                        <div class="col-md-3">
                                            <label for="module">Module</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="module_id" id="module" class="form-control select2"
                                                    style="font-size: 12px;" required>
                                                    <option value="">Select Module</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Lecturer -->
                                        <div class="col-md-3">
                                            <label for="lecturer">Lecturer</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="lecturer_id" id="lecturer" class="form-control select2"
                                                    style="font-size: 12px;" required>
                                                    <option value="">Select Lecturer</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Selected Details Preview</h5>
                                </div>
                                <div class="card-body" id="preview-section">
                                    <p><strong>Programme:</strong> <span id="preview-programme">N/A</span></p>
                                    <p><strong>Batch:</strong> <span id="preview-batch">N/A</span></p>
                                    <p><strong>Module:</strong> <span id="preview-module">N/A</span></p>
                                    <p><strong>Lecturer:</strong> <span id="preview-lecturer">N/A</span></p>
                                </div>
                                <div class="card-footer text-right" id="generate-link-footer" style="display: none;">
                                    <button type="button" class="btn btn-primary" id="generate-link-btn">Generate
                                        Link</button>
                                    <button type="button" class="btn btn-danger" id="create-form-btn"
                                        style="display: none;">
                                        <i class="fas fa-pencil-alt mr-1"></i> Create a Form
                                    </button>
                                </div>
                                <script>
                                    // categoryMap: programme_id -> cetegory value (e.g. 'final_year', 'normal', null)
                                    // Populated once when the Programme dropdown loads (see fetch_programmes AJAX below).
                                    var programmeCategoryMap = {};

                                    function toggleGenerateButton() {
                                        var lecturerText = document.getElementById('preview-lecturer').innerText || document.getElementById('preview-lecturer').textContent;
                                        var allSelected = !(lecturerText.trim() === 'N/A' || lecturerText.trim() === 'Select Lecturer');

                                        var generateBtn = document.getElementById('generate-link-btn');
                                        var createFormBtn = document.getElementById('create-form-btn');
                                        var footer = document.getElementById('generate-link-footer');

                                        if (!allSelected) {
                                            footer.style.display = 'none';
                                            return;
                                        }

                                        footer.style.display = 'block';

                                        var selectedProgrammeId = document.getElementById('programme').value;
                                        var category = programmeCategoryMap[selectedProgrammeId] || 'normal';

                                        if (category === 'final_year') {
                                            // Final year programmes get a custom-built form instead of a plain link
                                            generateBtn.style.display = 'none';
                                            createFormBtn.style.display = 'inline-block';
                                        } else {
                                            // Everything else keeps the existing simple link generation
                                            generateBtn.style.display = 'inline-block';
                                            createFormBtn.style.display = 'none';
                                        }
                                    }
                                    // Listen for DOM changes in preview-lecturer span
                                    const observer = new MutationObserver(toggleGenerateButton);
                                    observer.observe(document.getElementById('preview-lecturer'), {
                                        childList: true,
                                        subtree: true,
                                        characterData: true
                                    });

                                    // Also re-check whenever the programme changes (category can only be known once loaded)
                                    document.getElementById('programme').addEventListener('change', toggleGenerateButton);

                                    // Also call on page load in case value is set initially
                                    document.addEventListener('DOMContentLoaded', toggleGenerateButton);
                                </script>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- ============================================================ -->
                <!-- "Create a Form" Section — final_year custom feedback builder  -->
                <!-- Shown INLINE above the Generated Feedback Links table (not a  -->
                <!-- popup/modal), styled after the code.html mockup.              -->
                <!-- ============================================================ -->
                <div class="row mb-5 d-none ff-scope" id="ff-create-form-section">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center"
                                style="background: var(--ff-primary); color: #fff;">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-signature mr-2"></i> Create Final Year Feedback Form
                                </h5>
                                <button type="button" class="btn btn-sm btn-light" id="ff-cancel-form-btn">
                                    <i class="fas fa-times mr-1"></i> Close
                                </button>
                            </div>
                            <div class="card-body ff-section-body">

                                <!-- Course info banner, filled from the current Programme/Batch/Module/Lecturer selection -->
                                <div class="ff-banner">
                                    <div class="ff-banner-strip"></div>
                                    <div class="ff-banner-body">
                                        <h4 id="ff-programme-name">N/A</h4>
                                        <p class="ff-batch" id="ff-batch-name">N/A</p>
                                        <p class="ff-module" id="ff-module-lecturer">N/A</p>
                                    </div>
                                </div>

                                <!-- Dynamic criteria rows -->
                                <div id="ff-rows-container">
                                    <!-- Hidden template row, cloned by JS -->
                                    <div class="ff-row d-none" id="ff-row-template">
                                        <button type="button" class="ff-remove-row" title="Remove"><span
                                                class="material-symbols-outlined">close</span></button>
                                        <div class="d-flex align-items-center" style="gap:8px;">
                                            <span class="material-symbols-outlined" style="color:var(--ff-primary-container);">school</span>
                                            <select class="ff-criteria-select" required>
                                                <option value="" disabled selected>Choose a criteria...</option>
                                                <?php foreach ($final_year_criteria_options as $opt): ?>
                                                    <option value="<?php echo htmlspecialchars($opt['value']); ?>">
                                                        <?php echo htmlspecialchars($opt['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                                <option value="other">Other (type your own)</option>
                                            </select>
                                        </div>
                                        <input type="text" class="ff-custom-input" placeholder="Enter custom criteria...">
                                        <div class="ff-rating-group">
                                            <div class="ff-rating-btn"><span
                                                    class="material-symbols-outlined">sentiment_very_satisfied</span>Excellent</div>
                                            <div class="ff-rating-btn"><span class="material-symbols-outlined">sentiment_satisfied</span>Good
                                            </div>
                                            <div class="ff-rating-btn"><span
                                                    class="material-symbols-outlined">sentiment_neutral</span>Average</div>
                                            <div class="ff-rating-btn"><span
                                                    class="material-symbols-outlined">sentiment_dissatisfied</span>Poor</div>
                                        </div>
                                    </div>
                                    <!-- Real rows start here; JS seeds one default row when the section opens -->
                                </div>

                                <button type="button" class="ff-add-row-btn" id="ff-add-row-btn">
                                    <span class="material-symbols-outlined">add</span> Add More Criteria
                                </button>

                                <div class="mb-4">
                                    <label class="font-weight-bold d-block mb-2" style="color: var(--ff-primary); font-size:14px;">
                                        Additional Comments Field(s)
                                    </label>

                                    <div id="ff-comments-container">
                                        <!-- Hidden template row, cloned by JS -->
                                        <div class="ff-comment-row d-none" id="ff-comment-row-template">
                                            <button type="button" class="ff-comment-remove-btn" title="Remove"><span
                                                    class="material-symbols-outlined">close</span></button>
                                            <input type="text" class="ff-comment-label-input" value="Additional Comments"
                                                placeholder="Label shown to the student, e.g. Additional Comments">
                                            <textarea class="ff-comments" rows="2" disabled
                                                placeholder="Students will type their free-text answer here - no setup needed."></textarea>
                                        </div>
                                        <!-- Real rows start here; JS seeds one default row when the section opens -->
                                    </div>

                                    <button type="button" class="ff-add-row-btn" id="ff-add-comment-btn">
                                        <span class="material-symbols-outlined">add</span> Add More
                                    </button>
                                </div>

                                <div class="text-right">
                                    <button type="button" class="btn btn-secondary" id="ff-cancel-form-btn-2">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="ff-save-form-btn">
                                        <i class="fas fa-save mr-1"></i> Save Form
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- here wanna show the data and links also  -->
                <!-- Display Section for Generated Link -->
                <div class="row mb-5" id="generated-link-display">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <h5 class="mb-0 mr-3">Generated Feedback Links</h5>
                                    <ul class="nav nav-pills" id="link-status-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" href="#" data-filter="all">
                                                All <span class="badge badge-light ml-1" id="count-all">0</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#" data-filter="active">
                                                Active <span class="badge badge-light ml-1" id="count-active">0</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#" data-filter="inactive">
                                                Inactive <span class="badge badge-light ml-1" id="count-inactive">0</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="links-table">
                                        <thead>
                                            <tr>
                                                <th>Programme</th>
                                                <th>Batch</th>
                                                <th>Module</th>
                                                <th>Lecturer</th>
                                                <th>Feedback Link</th>
                                                <th>Active</th>
                                                <th>Created By</th>
                                                <th>Generated At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($generated_links_data)): ?>
                                                <?php foreach ($generated_links_data as $link_data): ?>
                                                    <tr data-status="<?php echo ($link_data['active'] == 1) ? 'active' : 'inactive'; ?>">
                                                        <td><?php echo htmlspecialchars($link_data['programme_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($link_data['batch_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($link_data['module_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($link_data['lecturer_name']); ?></td>
                                                        <td>
                                                            <div class="input-group">
                                                                <?php
                                                                // Protocol-relative URL to handle both HTTP and HTTPS
                                                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                                                                ?>
                                                                <input type="text" class="form-control form-control-sm"
                                                                    value="<?php echo htmlspecialchars($protocol . "://" . $_SERVER['HTTP_HOST'] . "/g/feedback.php?id=" . $link_data['link_id']); ?>"
                                                                    id="link-<?php echo htmlspecialchars($link_data['link_id']); ?>"
                                                                    readonly>
                                                                <div class="input-group-append">
                                                                    <button
                                                                        class="btn btn-sm btn-outline-secondary copy-link-btn"
                                                                        type="button"
                                                                        data-link-id="<?php echo htmlspecialchars($link_data['link_id']); ?>">Copy</button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <select class="form-control form-control-sm status-dropdown"
                                                                data-link-id="<?php echo htmlspecialchars($link_data['link_id']); ?>">
                                                                <option value="1" <?php echo ($link_data['active'] == 1) ? 'selected' : ''; ?>>Active</option>
                                                                <option value="0" <?php echo ($link_data['active'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                                                            </select>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($link_data['created_by']); ?></td>
                                                        <td><?php echo htmlspecialchars($link_data['created_at']); ?></td>

                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
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


<!-- ============================================================ -->
<!-- "Create a Form" Modal — final_year custom feedback builder    -->
<!-- Visual design follows the code.html mockup (Manrope font,    -->
<!-- Material Symbols icons, navy/red palette), scoped under      -->
<!-- .ff-scope so it never leaks into the rest of the admin theme -->
<!-- ============================================================ -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<style>
    .ff-scope {
        font-family: 'Manrope', sans-serif;
        --ff-primary: #001e40;
        --ff-primary-container: #003366;
        --ff-secondary: #bb0014;
        --ff-bg: #F1F5F9;
        --ff-card: #ffffff;
        --ff-outline: #E2E8F0;
        --ff-outline-2: #c3c6d1;
        color: #191c1e;
    }

    .ff-scope .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        vertical-align: middle;
    }

    .ff-scope .ff-section-body {
        background: var(--ff-bg);
        padding: 24px;
    }

    .ff-scope .ff-banner {
        background: var(--ff-card);
        border-radius: 12px;
        box-shadow: 0px 4px 12px rgba(0, 51, 102, 0.05);
        overflow: hidden;
        margin-bottom: 20px;
    }

    .ff-scope .ff-banner-strip {
        height: 56px;
        background: linear-gradient(120deg, var(--ff-primary) 0%, var(--ff-primary-container) 55%, var(--ff-secondary) 130%);
    }

    .ff-scope .ff-banner-body {
        padding: 16px 20px;
        text-align: center;
        border-top: 1px solid var(--ff-outline);
    }

    .ff-scope .ff-banner-body h4 {
        color: var(--ff-primary);
        font-weight: 800;
        margin-bottom: 2px;
    }

    .ff-scope .ff-banner-body .ff-batch {
        color: #43474f;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .ff-scope .ff-banner-body .ff-module {
        color: #737780;
        font-size: 13px;
        margin-bottom: 0;
    }

    .ff-scope .ff-row {
        background: var(--ff-card);
        border: 1px solid var(--ff-outline);
        border-left: 4px solid var(--ff-primary-container);
        border-radius: 12px;
        box-shadow: 0px 4px 12px rgba(0, 51, 102, 0.05);
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
    }

    .ff-scope .ff-row select.ff-criteria-select {
        border: none;
        background: transparent;
        color: var(--ff-primary);
        font-weight: 600;
        font-size: 14px;
        padding: 0;
    }

    /* Compact Select2 styling for the criteria dropdown - fixed width, no stretching */
    .ff-scope .select2-container {
        min-width: 220px;
        max-width: 260px;
    }

    .ff-scope .select2-container--default .select2-selection--single {
        border: none;
        background: transparent;
        height: auto;
        padding: 2px 20px 2px 0;
    }

    .ff-scope .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--ff-primary);
        font-weight: 600;
        font-size: 14px;
        padding-left: 0;
        line-height: 22px;
    }

    .ff-scope .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 24px;
    }

    .ff-scope .ff-row select.ff-criteria-select:focus {
        outline: none;
        box-shadow: none;
    }

    .ff-scope .ff-row.ff-row-invalid {
        border-left-color: #dc3545;
    }

    .ff-scope .ff-row.ff-row-invalid .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #dc3545;
    }

    .ff-scope .ff-custom-input {
        display: none;
        margin-top: 8px;
        border: 1px solid var(--ff-outline-2);
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 13px;
        width: 100%;
    }

    .ff-scope .ff-rating-group {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        margin-top: 12px;
    }

    .ff-scope .ff-rating-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px 4px;
        background: #F8FAFC;
        border: 1px solid var(--ff-outline);
        border-radius: 8px;
        color: var(--ff-primary);
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all .15s ease;
    }

    .ff-scope .ff-rating-btn .material-symbols-outlined {
        display: block;
        margin-bottom: 2px;
    }

    .ff-scope .ff-rating-btn.active {
        background: #EBF5FF;
        border-color: var(--ff-primary-container);
    }

    .ff-scope .ff-remove-row {
        position: absolute;
        top: 8px;
        right: 10px;
        color: #737780;
        cursor: pointer;
        border: none;
        background: none;
    }

    .ff-scope .ff-remove-row:hover {
        color: var(--ff-secondary);
    }

    .ff-scope .ff-add-row-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
        padding: 10px;
        margin-top: 8px;
        margin-bottom: 20px;
        border: 1px dashed var(--ff-outline-2);
        border-radius: 999px;
        background: transparent;
        color: var(--ff-primary);
        font-weight: 600;
        font-size: 14px;
    }

    .ff-scope textarea.ff-comments {
        width: 100%;
        border: 1px solid var(--ff-outline);
        border-radius: 8px;
        padding: 10px;
        font-size: 13px;
    }

    .ff-scope .ff-comment-row {
        background: var(--ff-card);
        border: 1px solid var(--ff-outline);
        border-left: 4px solid var(--ff-secondary);
        border-radius: 12px;
        box-shadow: 0px 4px 12px rgba(0, 51, 102, 0.05);
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
    }

    .ff-scope .ff-comment-label-input {
        width: 100%;
        border: none;
        border-bottom: 1px solid var(--ff-outline-2);
        background: transparent;
        color: var(--ff-primary);
        font-weight: 600;
        font-size: 14px;
        padding: 4px 24px 6px 0;
        margin-bottom: 8px;
    }

    .ff-scope .ff-comment-label-input:focus {
        outline: none;
        border-bottom-color: var(--ff-primary-container);
    }

    .ff-scope .ff-comment-remove-btn {
        position: absolute;
        top: 8px;
        right: 10px;
        color: #737780;
        cursor: pointer;
        border: none;
        background: none;
    }

    .ff-scope .ff-comment-remove-btn:hover {
        color: var(--ff-secondary);
    }
</style>


<!-- JS Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
<script type="text/javascript" charset="utf8"
    src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8"
    src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
    $(document).ready(function() {
        // Track which tab is currently selected: 'all', 'active', or 'inactive'
        let currentStatusFilter = 'all';

        // Custom DataTables search plugin - filters rows by data-status attribute
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'links-table') {
                return true; // don't affect any other DataTable on the page
            }
            if (currentStatusFilter === 'all') {
                return true;
            }
            let rowStatus = $(linksTable.row(dataIndex).node()).attr('data-status');
            return rowStatus === currentStatusFilter;
        });

        // Initialize DataTable
        let linksTable = $('#links-table').DataTable({
            "order": [
                [7, "desc"]
            ], // Sort by "Generated At" column by default
            "pageLength": 100,
            "responsive": true
        });

        // Update the count badges on each tab
        function updateStatusCounts() {
            let $rows = $('#links-table tbody tr');
            let total = $rows.length;
            let activeCount = $rows.filter('[data-status="active"]').length;
            let inactiveCount = $rows.filter('[data-status="inactive"]').length;

            $('#count-all').text(total);
            $('#count-active').text(activeCount);
            $('#count-inactive').text(inactiveCount);
        }
        updateStatusCounts();

        // Tab click handler - switches the active filter and redraws the table
        $('#link-status-tabs .nav-link').on('click', function(e) {
            e.preventDefault();
            $('#link-status-tabs .nav-link').removeClass('active');
            $(this).addClass('active');
            currentStatusFilter = $(this).data('filter');
            linksTable.draw();
        });

        $('.select2').select2();

        // Function to copy text to clipboard
        function copyToClipboard(elementId) {
            let copyText = document.getElementById(elementId);
            if (copyText) {
                copyText.select();
                copyText.setSelectionRange(0, 99999); // For mobile devices
                document.execCommand("copy");
                alert("Copied the link: " + copyText.value);
            } else {
                alert("Could not find the link to copy.");
            }
        }

        // Event listener for copy buttons
        $(document).on('click', '.copy-link-btn', function() {
            let linkId = $(this).data('link-id');
            copyToClipboard('link-' + linkId);
        });

        // Event listener for status dropdown change
        $(document).on('change', '.status-dropdown', function() {
            let linkId = $(this).data('link-id');
            let status = $(this).val();
            let dropdown = $(this);

            $.ajax({
                url: "g/update_link_status.php",
                method: "POST",
                data: {
                    link_id: linkId,
                    active: status
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        alertify.success(response.message);

                        // Keep the row's data-status in sync so the tab filter stays correct
                        let newStatus = (status == 1) ? 'active' : 'inactive';
                        dropdown.closest('tr').attr('data-status', newStatus);

                        updateStatusCounts();
                        linksTable.draw(false); // false = keep current paging position

                    } else {
                        alertify.error('Error: ' + response.message);
                        // Revert dropdown if error
                        dropdown.val(status == 1 ? 0 : 1);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.error("Response Text:", xhr.responseText);
                    alertify.error('An error occurred while updating the status. Status: ' + status + ', Error: ' + error);
                    dropdown.val(status == 1 ? 0 : 1);
                }
            });
        });

        // Function to update the preview section
        function updatePreview() {
            $('#preview-programme').text($('#programme option:selected').text() || 'N/A');
            $('#preview-batch').text($('#batch option:selected').text() || 'N/A');
            $('#preview-module').text($('#module option:selected').text() || 'N/A');
            $('#preview-lecturer').text($('#lecturer option:selected').text() || 'N/A');
        }

        // Fetch Programmes
        $.ajax({
            url: "transection_exams/fetch_programmes.php",
            method: "GET",
            dataType: "json",
            success: function(data) {
                let programmeDropdown = $('#programme');
                programmeDropdown.empty().append('<option value="">Select Programme</option>');
                data.forEach(function(programme) {
                    // Store the category so we know instantly (no extra AJAX) whether
                    // this programme is 'final_year' or 'normal' once it's selected.
                    programmeCategoryMap[programme.program_code] = programme.cetegory || 'normal';
                    programmeDropdown.append(`<option value="${programme.program_code}" data-cetegory="${programme.cetegory || 'normal'}">${programme.program_name}</option>`);
                });
                updatePreview(); // Initial update
            }
        });

        // Fetch Batches when Programme changes
        $('#programme').change(function() {
            let programmeId = $(this).val();
            // Clear dependent dropdowns
            $('#batch').empty().append('<option value="">Select Batch</option>');
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (programmeId) {
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        let batchDropdown = $('#batch');
                        data.forEach(function(batch) {
                            batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                        });
                        updatePreview(); // Update preview after batches are loaded
                    }
                });
            } else {
                updatePreview(); // Update preview if no programme is selected
            }
        });

        // Fetch Modules when Batch changes
        $('#batch').change(function() {
            let batchId = $(this).val();
            // Clear dependent dropdowns
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (batchId) {
                $.ajax({
                    url: "transection_exams/fetch_modules.php",
                    method: "POST",
                    data: {
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(data) {
                        let moduleDropdown = $('#module');
                        data.forEach(function(module) {
                            moduleDropdown.append(`<option value="${module.id}">${module.name}</option>`);
                        });
                        updatePreview(); // Update preview after modules are loaded
                    }
                });
            } else {
                updatePreview(); // Update preview if no batch is selected
            }
        });

        // Fetch Lecturers when Module changes
        $('#module').change(function() {
            let moduleId = $(this).val();
            // Clear lecturer dropdown
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (moduleId) {
                $.ajax({
                    url: "transection_exams/fetch_lecturers_by_module.php",
                    method: "POST",
                    data: {
                        module_id: moduleId
                    },
                    dataType: "json",
                    success: function(data) {
                        let lecturerDropdown = $('#lecturer');
                        let seenIds = {};
                        data.forEach(function(lecturer) {
                            if (seenIds[lecturer.id]) return;
                            seenIds[lecturer.id] = true;
                            lecturerDropdown.append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
                        });
                        updatePreview(); // Update preview after lecturers are loaded
                    }
                });
            } else {
                updatePreview(); // Update preview if no module is selected
            }
        });

        // Update preview when module or lecturer changes (without fetching new data)
        $('#module, #lecturer').change(function() {
            updatePreview();
        });

        // ============================================================
        // "Create a Form" inline section logic (final_year custom feedback builder)
        // Shown/hidden inline above the Generated Feedback Links table — no modal/popup.
        // ============================================================

        // Toggle rating button selection (visual preview only for now)
        $(document).on('click', '#ff-create-form-section .ff-rating-btn', function() {
            $(this).siblings('.ff-rating-btn').removeClass('active');
            $(this).addClass('active');
        });

        // Show/hide the custom text input when "Other" is chosen
        $(document).on('change', '#ff-create-form-section .ff-criteria-select', function() {
            let $row = $(this).closest('.ff-row');
            let $input = $row.find('.ff-custom-input');
            if ($(this).val() === 'other') {
                $input.show().prop('required', true);
            } else {
                $input.hide().prop('required', false).val('');
            }
            $row.removeClass('ff-row-invalid');
        });

        $(document).on('input', '#ff-create-form-section .ff-custom-input', function() {
            $(this).closest('.ff-row').removeClass('ff-row-invalid');
        });

        function ffValidateCriteriaRow($row) {
            let $select = $row.find('.ff-criteria-select');
            let val = $select.val();
            if (!val) {
                return false;
            }
            if (val === 'other' && !$row.find('.ff-custom-input').val().trim()) {
                return false;
            }
            return true;
        }

        function ffValidateAllCriteriaRows() {
            let allValid = true;
            $('#ff-rows-container .ff-row:not(#ff-row-template)').each(function() {
                let $row = $(this);
                if (!ffValidateCriteriaRow($row)) {
                    $row.addClass('ff-row-invalid');
                    allValid = false;
                } else {
                    $row.removeClass('ff-row-invalid');
                }
            });
            return allValid;
        }

        // Add a new criteria row (cloned from the hidden template)
        function ffAddRow() {
            let $newRow = $('#ff-row-template').clone();
            $newRow.removeAttr('id').removeClass('d-none');
            $newRow.find('.ff-custom-input').val('').hide();
            $newRow.find('.ff-rating-btn').removeClass('active');
            $('#ff-rows-container').append($newRow);

            // Init Select2 on this row's criteria dropdown - compact, fixed width, no search box
            $newRow.find('.ff-criteria-select')
                .prop('selectedIndex', 0)
                .select2({
                    width: '220px',
                    minimumResultsForSearch: -1
                });
        }
        $('#ff-add-row-btn').click(function() {
            if (!ffValidateAllCriteriaRows()) {
                alertify.error('Please choose a criteria for every row before adding another.');
                return;
            }
            ffAddRow();
        });

        // Remove a criteria row (keep at least one row on the form)
        $(document).on('click', '#ff-create-form-section .ff-remove-row', function() {
            if ($('#ff-rows-container .ff-row:not(.d-none)').length > 1) {
                $(this).closest('.ff-row').remove();
            } else {
                alertify.error('A form needs at least one criteria row.');
            }
        });

        // Add a new "Additional Comments" style field (cloned from the hidden template)
        function ffAddCommentRow() {
            let $newRow = $('#ff-comment-row-template').clone();
            $newRow.removeAttr('id').removeClass('d-none');
            $newRow.find('.ff-comment-label-input').val('Additional Comments');
            $('#ff-comments-container').append($newRow);
        }
        $('#ff-add-comment-btn').click(function() {
            ffAddCommentRow();
        });

        // Remove a comment field (keep at least one field on the form)
        $(document).on('click', '#ff-create-form-section .ff-comment-remove-btn', function() {
            if ($('#ff-comments-container .ff-comment-row:not(.d-none)').length > 1) {
                $(this).closest('.ff-comment-row').remove();
            } else {
                alertify.error('A form needs at least one comments field.');
            }
        });

        // "Create a Form" button -> reveal the inline section, pre-filled from the current selection
        $('#create-form-btn').click(function() {
            $('#ff-programme-name').text($('#preview-programme').text() || 'N/A');
            $('#ff-batch-name').text($('#preview-batch').text() || 'N/A');
            $('#ff-module-lecturer').text(
                ($('#preview-module').text() || 'N/A') + ' • ' + ($('#preview-lecturer').text() || 'N/A')
            );

            // Show the section first so Select2's width calculations behave correctly
            $('#ff-create-form-section').removeClass('d-none');

            // Hide the Generated Feedback Links table while building the form, to keep focus
            $('#generated-link-display').hide();

            // Reset to a single default row every time the section is (re)opened
            $('#ff-rows-container .ff-row:not(#ff-row-template)').remove();
            ffAddRow();

            $('#ff-comments-container .ff-comment-row:not(#ff-comment-row-template)').remove();
            ffAddCommentRow();

            $('html, body').animate({
                scrollTop: $('#ff-create-form-section').offset().top - 20
            }, 400);
        });

        // Close / Cancel -> hide the inline section again and bring the links table back
        $('#ff-cancel-form-btn, #ff-cancel-form-btn-2').click(function() {
            $('#ff-create-form-section').addClass('d-none');
            $('#generated-link-display').show();
        });

        // Save Form (draft) - collects the criteria list built by the admin.
        // NOTE: this currently just assembles the data client-side; the endpoint
        // that persists it (new table + feedback.php rendering logic for
        // final_year links) is the "balance concept" to be added next.
        $('#ff-save-form-btn').click(function() {
            if (!ffValidateAllCriteriaRows()) {
                alertify.error('Please choose a criteria for every row before saving.');
                return;
            }

            let criteria = [];
            $('#ff-rows-container .ff-row:not(#ff-row-template)').each(function() {
                let $select = $(this).find('.ff-criteria-select');
                let label = $select.val() === 'other' ?
                    $(this).find('.ff-custom-input').val().trim() :
                    $select.find('option:selected').text().trim();
                criteria.push(label);
            });

            let commentFields = [];
            $('#ff-comments-container .ff-comment-row:not(#ff-comment-row-template)').each(function() {
                let label = $(this).find('.ff-comment-label-input').val().trim();
                if (label) {
                    commentFields.push(label);
                }
            });

            if (commentFields.length === 0) {
                alertify.error('Add at least one comments field before saving.');
                return;
            }

            let formPayload = {
                programme_id: $('#programme').val(),
                batch_id: $('#batch').val(),
                module_id: $('#module').val(),
                lecturer_id: $('#lecturer').val(),
                criteria: criteria,
                comment_fields: commentFields
            };

            let $saveBtn = $('#ff-save-form-btn');
            $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');

            $.ajax({
                url: "g/create_final_year_form.php",
                method: "POST",
                data: formPayload,
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        alertify.success(response.message + ' (' + response.field_count + ' fields saved)');
                        $('#ff-create-form-section').addClass('d-none');
                        $('#generated-link-display').show();
                        // Refresh so the new/updated link shows up in the table below
                        location.reload();
                    } else if (response.status === 'duplicate') {
                        $('#display-programme-name').text(response.programme_name);
                        $('#display-batch-name').text(response.batch_name || $('#batch option:selected').text());
                        $('#display-module-name').text(response.module_name || $('#module option:selected').text());
                        $('#display-lecturer-name').text(response.lecturer_name);
                        $('#display-feedback-link').attr('href', response.link).text(response.link);
                        $('#generated-link-display').show();

                        alert(`⚠️ This feedback link already exists!\n\nProgram: ${response.programme_name}\nBatch: ${response.batch_name || $('#batch option:selected').text()}\nModule: ${response.module_name || $('#module option:selected').text()}\nLecturer: ${response.lecturer_name}\n\nExisting Link: ${response.link}`);
                        $saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Form');
                    } else {
                        alertify.error('Error: ' + response.message);
                        $saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Form');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    console.error("Response Text:", xhr.responseText);
                    alertify.error('An error occurred while saving the form.');
                    $saveBtn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Form');
                }
            });
        });

        // Event listener for the Generate Link button
        $('#generate-link-btn').click(function() {
            // Here you would collect the selected values and generate the link
            let selectedProgramme = $('#programme').val();
            let selectedBatch = $('#batch').val();
            let selectedModule = $('#module').val();
            let selectedLecturer = $('#lecturer').val();

            if (selectedProgramme && selectedBatch && selectedModule && selectedLecturer) {
                $.ajax({
                    url: "g/link.php", // Target URL
                    method: "POST", // Or "GET", depending on how you want to handle it
                    data: {
                        programme_id: selectedProgramme,
                        batch_id: selectedBatch,
                        module_id: selectedModule,
                        lecturer_id: selectedLecturer
                    },
                    dataType: "json", // Expecting JSON response from the server
                    success: function(response) {
                        if (response.status === 'success') {
                            // Fetch batch name
                            let batch_name = $('#batch option:selected').text();
                            // Fetch module name
                            let module_name = $('#module option:selected').text();

                            $('#display-programme-name').text(response.programme_name);
                            $('#display-batch-name').text(batch_name);
                            $('#display-module-name').text(module_name);
                            $('#display-lecturer-name').text(response.lecturer_name);
                            $('#display-feedback-link').attr('href', response.link).text(response.link);
                            $('#generated-link-display').show();

                            alert(`Link generated for ${response.programme_name} - ${response.lecturer_name}: ${response.link}`);
                            // You can also display the link on the page, e.g., in a new div
                            // $('#link-display-area').text(response.link).show();

                            // Refresh the links table after a new link is generated
                            location.reload(); // Simple reload to refresh the table with new data

                        } else if (response.status === 'duplicate') {
                            // Handle duplicate - link already exists
                            let batch_name = $('#batch option:selected').text();
                            let module_name = $('#module option:selected').text();

                            $('#display-programme-name').text(response.programme_name);
                            $('#display-batch-name').text(batch_name);
                            $('#display-module-name').text(module_name);
                            $('#display-lecturer-name').text(response.lecturer_name);
                            $('#display-feedback-link').attr('href', response.link).text(response.link);
                            $('#generated-link-display').show();

                            alert(`⚠️ This feedback link already exists!\n\nProgram: ${response.programme_name}\nLecturer: ${response.lecturer_name}\n\nExisting Link: ${response.link}`);

                        } else {
                            alert('Error generating link: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        alert('An error occurred while trying to generate the link.');
                    }
                });
            } else {
                alert('Please select all fields before generating the link.');
            }
        });
    });
</script>