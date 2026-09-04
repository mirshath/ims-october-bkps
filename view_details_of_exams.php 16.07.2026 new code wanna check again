<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="./vendor/datatables/dataTables.bootstrap4.min.css">
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" rel="stylesheet" />

    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">Assessment Details</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Assessment Details</h6>
                                </div>
                                <div class="card-body">
                                    <?php
                                    if (isset($_GET['id'])) {
                                        $id = $_GET['id'];

                                        // Updated query with LEFT JOINs to handle potential NULL values
                                        $query = "
                                        SELECT a.*, 
                                            p.program_name, 
                                            b.batch_name, 
                                            m.module_name, 
                                            ac.as_main_component_name, 
                                            sc.sub_component_name 
                                        FROM assessments a 
                                        LEFT JOIN program_table p ON a.programme_id = p.program_code 
                                        LEFT JOIN batch_table b ON a.batch_id = b.id 
                                        LEFT JOIN modules m ON a.module_id = m.id 
                                        LEFT JOIN assignment_components ac ON a.main_component_id = ac.id 
                                        LEFT JOIN sub_assign_components sc ON a.sub_component_id = sc.id 
                                        WHERE a.id = ?";

                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("i", $id);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $assessment = $result->fetch_assoc();

                                        if ($assessment) {
                                    ?>

                                        <div class="table-responsive">
                                            <button id="sendAllEmails" class="btn btn-primary mb-3" onclick="sendAllEmails(<?php echo $assessment['id']; ?>)">
                                                <i class="fas fa-envelope"></i> Send All Emails
                                                <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                            </button>

                                            <table class="table table-striped table-hover" style="width: 80%; font-size: 11px;">
                                                <tr>
                                                    <td class="bg-secondary text-white" style="width: 40%;">ID</td>
                                                    <td><?php echo htmlspecialchars($assessment['id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Programme Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['program_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Batch Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['batch_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Module Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['module_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Main Component Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['as_main_component_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Sub Component Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['sub_component_name'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Year</td>
                                                    <td><?php echo htmlspecialchars($assessment['year_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Semester</td>
                                                    <td><?php echo htmlspecialchars($assessment['semester_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Assessment Date</td>
                                                    <td><?php echo htmlspecialchars($assessment['assessment_date']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Description</td>
                                                    <td>
                                                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                                            <?php echo $assessment['description']; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                                    <tr>
                                                        <td class="bg-secondary text-white">Attachment <?php echo $i; ?></td>
                                                        <td>
                                                            <?php if (!empty($assessment["attachment_$i"])): ?>
                                                                <a href="uploads_exam_assessments/<?php echo htmlspecialchars($assessment["attachment_$i"]); ?>" target="_blank" class="text-primary">
                                                                    <?php echo htmlspecialchars(basename($assessment["attachment_$i"])); ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">No attachment</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endfor; ?>
                                            </table>
                                        </div>

                                        <!-- Separate Card for Allocated Students -->
                                        <div class="card mt-4">
                                            <div class="card-header d-flex align-items-center justify-content-between" style="height: 60px;">
                                                <h6 class="mb-0 me-2">Allocated Students</h6>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="selectAllStudents()">
                                                        <i class="fas fa-check-square"></i> Select All
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="deselectAllStudents()">
                                                        <i class="fas fa-square"></i> Deselect All
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $programme_id = $assessment['programme_id'];
                                                $batch_id = $assessment['batch_id'];
                                                $assessment_id = $assessment['id'];
                                                $module_name = $assessment['module_name']; // Get the module name from the assessment


                                                $students_query = "
                                                    SELECT s.*, 
                                                        ap.student_registration_id,
                                                        ap.compulsory_sub,
                                                        ap.elective_subs,
                                                        (SELECT status FROM assessment_email_log 
                                                        WHERE assessment_id = ? AND email COLLATE utf8mb4_general_ci = s.bms_email COLLATE utf8mb4_general_ci 
                                                        ORDER BY sent_date DESC LIMIT 1) as email_status,
                                                        (SELECT sent_date FROM assessment_email_log 
                                                        WHERE assessment_id = ? AND email COLLATE utf8mb4_general_ci = s.bms_email COLLATE utf8mb4_general_ci 
                                                        ORDER BY sent_date DESC LIMIT 1) as email_sent_date,
                                                        (SELECT sent_by FROM assessment_email_log 
                                                        WHERE assessment_id = ? AND email COLLATE utf8mb4_general_ci = s.bms_email COLLATE utf8mb4_general_ci 
                                                        ORDER BY sent_date DESC LIMIT 1) as sent_by
                                                    FROM students s 
                                                    INNER JOIN allocate_programme ap ON s.student_code = ap.student_code 
                                                    WHERE ap.programme_code = ? AND ap.batch_id = ? AND ap.status = 'active'
                                                    ORDER BY s.first_name, s.last_name";


                                                $students_stmt = $conn->prepare($students_query);
                                                $students_stmt->bind_param("iiiii", $assessment_id, $assessment_id, $assessment_id, $programme_id, $batch_id);
                                                $students_stmt->execute();
                                                $students_result = $students_stmt->get_result();
                                                $all_students = $students_result->fetch_all(MYSQLI_ASSOC);
                                                
                                                // Now filter students who have the module in either compulsory_sub or elective_subs
                                                $students = [];
                                                foreach ($all_students as $student) {
                                                    $compulsory_subs = explode(',', $student['compulsory_sub']);
                                                    $elective_subs_array = explode(',', $student['elective_subs']);
                                                    
                                                    // Trim whitespace from each module name
                                                    $compulsory_subs = array_map('trim', $compulsory_subs);
                                                    $elective_subs_array = array_map('trim', $elective_subs_array);
                                                    
                                                    if (in_array($module_name, $compulsory_subs) || in_array($module_name, $elective_subs_array)) {
                                                        $students[] = $student;
                                                    }
                                                }

                                                // Display students in a DataTable with index
                                                if ($students) {
                                                    echo "<div class='table-responsive'>";
                                                    echo "<table id='studentsTable' class='table table-striped table-hover' style='font-size: 12px;'>
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Student ID</th>
                                                            <th>Name</th>
                                                            <th>BMS Email</th>
                                                            <th>Email Status</th>
                                                            <th>Sent By</th>
                                                            <th>Action</th>
                                                            <th>Remove from List</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>";

                                                    // Initialize index counter
                                                    $index = 1;

                                                    foreach ($students as $student) {
                                                        // Format email status for display
                                                        $emailStatusHtml = '';
                                                        if (empty($student['email_status'])) {
                                                            $emailStatusHtml = '<span class="badge bg-warning text-dark">Not Sent</span>';
                                                        } else {
                                                            $statusClass = ($student['email_status'] == 'sent') ? 'bg-success' : 'bg-danger';
                                                            $statusText = ($student['email_status'] == 'sent') ? 'Sent' : 'Failed';
                                                            $sentDate = !empty($student['email_sent_date']) ? date('M d, H:i', strtotime($student['email_sent_date'])) : '';
                                                            $emailStatusHtml = "<span class='badge {$statusClass}'>{$statusText}</span>";
                                                            if (!empty($sentDate)) {
                                                                $emailStatusHtml .= "<br><small class='text-muted'>{$sentDate}</small>";
                                                            }
                                                        }

                                                        // Button text based on status
                                                        $buttonText = 'Send Email';
                                                        $buttonClass = 'btn-primary';
                                                        if ($student['email_status'] == 'sent') {
                                                            $buttonText = 'Resend Email';
                                                            $buttonClass = 'btn-info';
                                                        } elseif ($student['email_status'] == 'failed') {
                                                            $buttonText = 'Retry Send';
                                                            $buttonClass = 'btn-warning';
                                                        }

                                                        $buttonHtml = "
                                                        <form class='email-form' data-student-email='" . htmlspecialchars($student['bms_email']) . "'>
                                                            <input type='hidden' name='email' value='" . htmlspecialchars($student['bms_email']) . "'>
                                                            <input type='hidden' name='student_name' value='" . htmlspecialchars($student['first_name']) . " " . htmlspecialchars($student['last_name']) . "'>
                                                            <input type='hidden' name='assessment_id' value='" . htmlspecialchars($assessment['id']) . "'>
                                                            <input type='hidden' name='student_id' value='" . htmlspecialchars($student['student_code']) . "'>
                                                            <input type='hidden' name='as_main_component_name' value='" . htmlspecialchars($assessment['as_main_component_name']) . "'>
                                                            <input type='hidden' name='sub_component_name' value='" . htmlspecialchars($assessment['sub_component_name'] ?? '') . "'>
                                                            <input type='hidden' name='program_name' value='" . htmlspecialchars($assessment['program_name']) . "'>
                                                            <button type='submit' class='btn {$buttonClass} btn-sm sendEmailButton'>
                                                                <i class='fas fa-envelope'></i> {$buttonText}
                                                                <span class='spinner-border spinner-border-sm' role='status' aria-hidden='true' style='display: none;'></span>
                                                            </button>
                                                        </form>";

                                                        echo "<tr>
                                                        <td>" . $index++ . "</td>
                                                        <td>" . htmlspecialchars($student['student_registration_id'] ?? 'N/A') . "</td>
                                                        <td>" . htmlspecialchars($student['first_name']) . " " . htmlspecialchars($student['last_name']) . "</td>
                                                        <td>" . htmlspecialchars($student['bms_email']) . "</td>
                                                        <td>" . $emailStatusHtml . "</td>
                                                        <td>" . htmlspecialchars($student['sent_by'] ?? 'N/A') . "</td>
                                                        <td>" . $buttonHtml . "</td>
                                                        <td class='text-center'>
                                                            <input type='checkbox' class='remove-student' value='" . htmlspecialchars($student['bms_email']) . "'>
                                                        </td>
                                                    </tr>";
                                                    }

                                                    echo "</tbody></table>";
                                                    echo "</div>";
                                                } else {
                                                    echo "<div class='alert alert-info'>";
                                                    echo "<i class='fas fa-info-circle'></i> No students allocated for this program, batch, and module.";
                                                    echo "</div>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php
                                        } else {
                                            echo "<div class='alert alert-warning'>";
                                            echo "<i class='fas fa-exclamation-triangle'></i> ";
                                            echo "<strong>Assessment Not Found!</strong><br>";
                                            echo "No assessment found with ID: " . htmlspecialchars($id) . "<br>";
                                            echo "Please verify the assessment ID and try again.";
                                            echo "</div>";
                                        }
                                    } else {
                                        echo "<div class='alert alert-warning'>";
                                        echo "<i class='fas fa-exclamation-triangle'></i> ";
                                        echo "<strong>No ID Provided!</strong><br>";
                                        echo "Please access this page through the proper assessment link.";
                                        echo "</div>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Sending Modal -->
    <div class="custom-modal" id="emailSendingModal" data-backdrop="static" data-keyboard="false">
        <div class="custom-modal-dialog custom-modal-dialog-centered">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-primary text-white">
                    <h5 class="custom-modal-title">
                        <i class="fas fa-envelope"></i> Sending Emails
                    </h5>
                </div>
                <div class="custom-modal-body text-center">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <h5 class="mb-3">Please Wait</h5>
                    <p class="mb-2"><strong>Mail Sending in Progress...</strong></p>
                    <p class="text-muted">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        Please don't refresh or close this page
                    </p>
                    <div class="progress mt-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                            role="progressbar" style="width: 100%"></div>
                    </div>
                    <div id="emailProgress" class="mt-3">
                        <small class="text-muted">Preparing to send emails...</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="custom-modal" id="emailSuccessModal">
        <div class="custom-modal-dialog custom-modal-dialog-centered">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-success text-white">
                    <h5 class="custom-modal-title">
                        <i class="fas fa-check-circle"></i> Email Sending Complete
                    </h5>
                </div>
                <div class="custom-modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-success">Success!</h5>
                    <p id="successMessage">Emails have been sent successfully.</p>
                    <p class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        Page will refresh automatically in <span id="countdown">3</span> seconds...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="custom-modal" id="emailErrorModal">
        <div class="custom-modal-dialog custom-modal-dialog-centered">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-danger text-white">
                    <h5 class="custom-modal-title">
                        <i class="fas fa-exclamation-circle"></i> Email Sending Failed
                    </h5>
                    <button type="button" class="custom-close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="custom-modal-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-circle text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-danger">Error!</h5>
                    <p id="errorMessage">An error occurred while sending emails.</p>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom Modal Styles - Prevent backdrop click closing */
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .custom-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }

        .custom-modal-dialog {
            position: relative;
            width: auto;
            margin: 0.5rem;
            pointer-events: none;
            max-width: 500px;
        }

        .custom-modal-dialog-centered {
            min-height: calc(100% - 1rem);
            display: flex;
            align-items: center;
        }

        .custom-modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            pointer-events: auto;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .custom-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 1rem 1rem;
            border-bottom: 1px solid #dee2e6;
            border-top-left-radius: 9px;
            border-top-right-radius: 9px;
        }

        .custom-modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }

        .custom-modal-title {
            margin-bottom: 0;
            line-height: 1.5;
            font-size: 1.25rem;
            font-weight: 500;
        }

        .custom-close {
            padding: 1rem 1rem;
            margin: -1rem -1rem -1rem auto;
            background-color: transparent;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-shadow: 0 1px 0 #fff;
            opacity: 0.8;
            cursor: pointer;
        }

        .custom-close:hover {
            opacity: 1;
        }

        /* Animation for modal */
        .custom-modal.fade {
            transition: opacity 0.15s linear;
        }

        .custom-modal.fade .custom-modal-dialog {
            transition: transform 0.3s ease-out;
            transform: translate(0, -50px);
        }

        .custom-modal.show .custom-modal-dialog {
            transform: none;
        }

        /* Progress bar animation */
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            0% {
                background-position: 1rem 0;
            }

            100% {
                background-position: 0 0;
            }
        }

        /* Spinner animation */
        .spinner-border {
            animation: spinner-border 0.75s linear infinite;
        }

        @keyframes spinner-border {
            to {
                transform: rotate(360deg);
            }
        }

        .btn-disabled-sending {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Responsive modal */
        @media (min-width: 576px) {
            .custom-modal-dialog {
                max-width: 500px;
                margin: 1.75rem auto;
            }
        }

        @media (min-width: 992px) {
            .custom-modal-dialog-centered {
                min-height: calc(100% - 3.5rem);
            }
        }

        /* Prevent text selection on modal backdrop */
        .custom-modal {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .custom-modal-content {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
    </style>

    <!-- DataTables JS -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        // Custom Modal JavaScript - Replace Bootstrap modal functionality
        class CustomModal {
            constructor(element) {
                this.element = element;
                this.isShown = false;
                this.backdrop = null;

                // Bind events
                this.bindEvents();
            }

            bindEvents() {
                // Close button functionality
                const closeButtons = this.element.querySelectorAll('[data-dismiss="modal"], .custom-close');
                closeButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.hide();
                    });
                });

                // Prevent modal from closing when clicking on modal content
                const modalContent = this.element.querySelector('.custom-modal-content');
                if (modalContent) {
                    modalContent.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Handle backdrop click - only close if not static
                this.element.addEventListener('click', (e) => {
                    if (e.target === this.element) {
                        const isStatic = this.element.getAttribute('data-backdrop') === 'static';
                        if (!isStatic) {
                            this.hide();
                        }
                    }
                });

                // Handle ESC key - only close if keyboard is not disabled
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isShown) {
                        const keyboardDisabled = this.element.getAttribute('data-keyboard') === 'false';
                        if (!keyboardDisabled) {
                            this.hide();
                        }
                    }
                });
            }

            show() {
                if (this.isShown) return;

                this.isShown = true;
                document.body.style.overflow = 'hidden';

                // Show modal
                this.element.style.display = 'block';
                this.element.classList.add('show');

                // Focus management
                this.element.setAttribute('aria-hidden', 'false');
                this.element.focus();

                // Trigger shown event
                const event = new CustomEvent('shown.bs.modal');
                this.element.dispatchEvent(event);
            }

            hide() {
                if (!this.isShown) return;

                this.isShown = false;
                document.body.style.overflow = '';

                // Hide modal
                this.element.classList.remove('show');

                // Hide after transition
                setTimeout(() => {
                    this.element.style.display = 'none';
                    this.element.setAttribute('aria-hidden', 'true');

                    // Trigger hidden event
                    const event = new CustomEvent('hidden.bs.modal');
                    this.element.dispatchEvent(event);
                }, 150);
            }
        }

        // Initialize modals when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals
            const modals = {};
            document.querySelectorAll('.custom-modal').forEach(modalElement => {
                const modalId = modalElement.id;
                modals[modalId] = new CustomModal(modalElement);
            });

            // Global modal functions for backward compatibility
            window.showModal = function(modalId) {
                if (modals[modalId]) {
                    modals[modalId].show();
                }
            };

            window.hideModal = function(modalId) {
                if (modals[modalId]) {
                    modals[modalId].hide();
                }
            };
        });

        // Wait for document to be ready
        $(document).ready(function() {
            // Initialize DataTable
            $('#studentsTable').DataTable({
                "paging": true,
                "searching": true,
                "lengthChange": true,
                "pageLength": 10,
                "responsive": true,
                "autoWidth": false,
                "order": [
                    [0, "asc"]
                ],
                "columnDefs": [{
                    "orderable": false,
                    "targets": [6, 7]
                }]
            });

            // Individual email sending functionality
            $(document).on('submit', '.email-form', function(event) {
                event.preventDefault();

                const form = $(this);
                const button = form.find('.sendEmailButton');
                const spinner = button.find('.spinner-border');
                const originalText = button.html();

                button.prop('disabled', true);
                spinner.show();
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

                const formData = form.serialize();

                $.ajax({
                    url: 'transection_exams/send_email.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            button.removeClass('btn-primary btn-warning').addClass('btn-success');
                            button.html('<i class="fas fa-check"></i> Sent Successfully');

                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            button.removeClass('btn-primary btn-info btn-warning').addClass('btn-danger');
                            button.html('<i class="fas fa-times"></i> Failed');
                            alert('Failed to send email: ' + response.message);

                            setTimeout(function() {
                                button.prop('disabled', false);
                                button.removeClass('btn-danger').addClass('btn-warning');
                                button.html('<i class="fas fa-redo"></i> Retry Send');
                            }, 2000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error: ' + error);
                        button.removeClass('btn-primary btn-info btn-warning').addClass('btn-danger');
                        button.html('<i class="fas fa-times"></i> Error');
                        alert('An error occurred while sending the email.');

                        setTimeout(function() {
                            button.prop('disabled', false);
                            button.removeClass('btn-danger').addClass('btn-warning');
                            button.html('<i class="fas fa-redo"></i> Try Again');
                        }, 2000);
                    }
                });
            });
        });

        function selectAllStudents() {
            document.querySelectorAll('.remove-student').forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function deselectAllStudents() {
            document.querySelectorAll('.remove-student').forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Enhanced sendAllEmails function with custom modal
        function sendAllEmails(assessmentId) {
            const removedEmails = Array.from(document.querySelectorAll('.remove-student:checked')).map(checkbox => checkbox.value);

            if (removedEmails.length > 0) {
                const confirmMessage = `You have selected ${removedEmails.length} student(s) to exclude from the email list. Continue sending emails to the remaining students?`;
                if (!confirm(confirmMessage)) {
                    return;
                }
            }

            // Show the loading modal using custom modal
            showModal('emailSendingModal');

            // Disable the send button
            const button = document.getElementById('sendAllEmails');
            button.disabled = true;
            button.classList.add('btn-disabled-sending');

            // Update progress message
            document.getElementById('emailProgress').innerHTML = '<small class="text-info">Sending emails to students...</small>';

            // Set flag to prevent page refresh
            window.emailSendingInProgress = true;

            $.ajax({
                url: 'transection_exams/check_allocate.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: assessmentId,
                    removed_emails: removedEmails
                },
                success: function(response) {
                    console.log('Response from server: ', response);

                    // Hide the loading modal
                    hideModal('emailSendingModal');

                    // Reset flag
                    window.emailSendingInProgress = false;

                    let message = response.message;
                    if (response.failedEmails && response.failedEmails.length > 0) {
                        message += "\n\nFailed emails:\n";
                        response.failedEmails.forEach(emailObj => {
                            message += `• ${emailObj.email}: ${emailObj.error}\n`;
                        });
                    }

                    if (response.success) {
                        // Show success modal
                        document.getElementById('successMessage').textContent = message;
                        showModal('emailSuccessModal');

                        // Update button appearance
                        button.innerHTML = '<i class="fas fa-check"></i> Emails Sent Successfully';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-success');

                        // Start countdown and auto-refresh
                        let countdown = 3;
                        const countdownElement = document.getElementById('countdown');

                        const countdownInterval = setInterval(function() {
                            countdown--;
                            countdownElement.textContent = countdown;

                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                hideModal('emailSuccessModal');
                                location.reload();
                            }
                        }, 1000);

                    } else {
                        // Show error modal
                        document.getElementById('errorMessage').textContent = message;
                        showModal('emailErrorModal');

                        // Re-enable button
                        button.disabled = false;
                        button.classList.remove('btn-disabled-sending');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error: ' + error);

                    // Hide the loading modal
                    hideModal('emailSendingModal');

                    // Reset flag
                    window.emailSendingInProgress = false;

                    // Show error modal
                    document.getElementById('errorMessage').textContent = 'An error occurred while sending the emails. Please try again.';
                    showModal('emailErrorModal');

                    // Re-enable button
                    button.disabled = false;
                    button.classList.remove('btn-disabled-sending');
                }
            });
        }

        // Prevent page refresh/close during email sending
        window.addEventListener('beforeunload', function(e) {
            if (window.emailSendingInProgress) {
                const confirmationMessage = 'Email sending is in progress. Are you sure you want to leave?';
                e.returnValue = confirmationMessage;
                return confirmationMessage;
            }
        });

        // Initialize flag
        window.emailSendingInProgress = false;
    </script>

</body>

</html>

