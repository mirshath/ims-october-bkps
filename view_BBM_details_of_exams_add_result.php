<?php
session_start();
include("database/connection.php");
include("includes/header.php");

$Session_username = $_SESSION['username'];
$Session_user_id = $_SESSION['user_id']; // Make sure this is available in your session

// echo "Welcome, $Session_username!"; // Display the username
// session id wanna show 
// echo "<br> ";
// echo "Your session id is: " . $Session_user_id;

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}



// Fetch grades from the database
$sql = "SELECT description_grad FROM description_grading";
$result = $conn->query($sql);
$grades = [];

if ($result->num_rows > 0) {
    // Fetch the grades and store them in an array
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row['description_grad'];
    }
} else {
    echo "No grades found.";
}


// <!-- ------------------------------------------------------------------------------------------------------  -->

// Module ID (Change this as needed)
$module_code = 189;

// Query to get examiner names by joining `modules` and `admin` tables
$query = "
    SELECT 
        a1.username AS examiner_1, 
        a2.username AS examiner_2 ,
        modules.examinor_1 As ex_1, modules.examinor_2 As ex_2
    FROM modules 
    LEFT JOIN admin a1 ON modules.examinor_1 = a1.id 
    LEFT JOIN admin a2 ON modules.examinor_2 = a2.id 
    WHERE modules.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $module_code);
$stmt->execute();
$result_examinor_find = $stmt->get_result();



if ($result_examinor_find->num_rows > 0) {
    // $row_have = true;
    $row = $result_examinor_find->fetch_assoc();

    // Initialize examiner IDs at the start
    global $ex_1_id, $ex_2_id;
    $ex_1_id = '';
    $ex_2_id = '';

    if ($Session_username == $row['examiner_1']) {
        $row_have = true;
        $examinor_1_txt = "You are allocated for the examior 1";
        $examiner_role =  $row['examiner_1'];
        $ex_1_id = isset($row['ex_1']) ? $row['ex_1'] : '';

        // Query to check the `examinor_1` and `examinor_2` columns
        $query = "
            SELECT examinor_1, examinor_2 
            FROM modules 
            WHERE id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $module_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Check if $ex_1_id matches any of the examiner columns
            if ($ex_1_id == $row['examinor_1']) {
                $matched_column = "examinor_1";
                // echo "The value $ex_1_id is stored in column: $matched_column";
            } else {
                // echo "No matching column found for value $ex_1_id";
            }
        } else {
            echo "No record found for module code: $module_code";
        }
    } elseif ($Session_username == $row['examiner_2']) {
        $row_have = true;
        $examinor_2_txt = "You are allocated for the examior 2 ";
        $examiner_role = $row['examiner_2'];
        $ex_2_id = isset($row['ex_2']) ? $row['ex_2'] : '';

        // Query to check the `examinor_1` and `examinor_2` columns
        $query = "
            SELECT examinor_1, examinor_2 
            FROM modules 
            WHERE id = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $module_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Check if $ex_1_id matches any of the examiner columns
            if ($ex_2_id == $row['examinor_2']) {
                $matched_column = "examinor_2";
                // echo "The value $ex_2_id is stored in column: $matched_column";
            } else {
                // echo "No matching column found for value $ex_2_id";
            }
        } else {
            echo "No record found for module code: $module_code";
        }
    } else {
        $row_have = false;
        $examiner_role = "You are not assigned as an examiner for this module.";
    }
} else {
    $examiner_role = "No examiners found for this module.";
    $row_have = false;
}


// <!-- ------------------------------------------------------------------------------------------------------  -->

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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">BBM Exam Result Add</h4>
                    <div>

                    </div>
                </div>

                <!-- Filter Form -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center" style="height: 60px;">
                                <span
                                    class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center"
                                    style="width: 30px; height: 30px;">
                                    <i class="fas fa-plus-circle"></i>
                                </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                <h6 class="mb-0 me-2">BBM Result Adding Details</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                if (isset($_GET['id'])) {
                                    $id = $_GET['id'];

                                    $query = "
                                        SELECT a.*, 
                                            p.program_name, 
                                            b.batch_name, 
                                            m.module_name,m.*, 
                                            ac.*, 
                                            sc.* 
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

                                        // Add console logging for the required IDs
                                        echo "<script>
                                            console.log('Batch IDs:', " . json_encode($assessment['batch_id']) . ");
                                            console.log('Main Component ID:', " . json_encode($assessment['main_component_id']) . ");
                                            console.log('Sub Component ID:', " . json_encode($assessment['sub_component_id']) . ");
                                            console.log('Module ID:', " . json_encode($assessment['module_id']) . ");
                                            console.log('Program IDs:', " . json_encode($assessment['programme_id']) . ");
                                            console.log('.............................................................');
                                            console.log('Module GPA VALUE:', " . json_encode($assessment['module_GPA']) . ");
                                            console.log('.............................................................');
                                        </script>";

                                ?>
                                        <div class="table-responsive">
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
                                                    <td><?php echo htmlspecialchars($assessment['module_name']); ?>
                                                        <?php echo htmlspecialchars($assessment['module_GPA']); ?></td>
                                                </tr>



                                                <!-- -----------------------------------------------------------------------------  -->
                                                <tr>
                                                    <td class="bg-secondary text-white">Main Component Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['as_main_component_name']); ?> <?php echo htmlspecialchars($assessment['main_component_percent']); ?></td>
                                                </tr>

                                                <tr>
                                                    <td class="bg-secondary text-white">Sub Component Name</td>
                                                    <td><?php echo htmlspecialchars($assessment['sub_component_name']); ?> <?php echo htmlspecialchars($assessment['sub_component_percent']); ?></td>
                                                </tr>
                                                <tr style="display: nones;">
                                                    <td class="bg-secondary text-white">Examiner Check</td>
                                                    <td>
                                                        <?php
                                                        if (
                                                            $assessment['as_main_component_name'] === 'End-Semester Examination' ||
                                                            $assessment['sub_component_name'] === 'Mid-Semester Test'
                                                        ) {
                                                            echo '<span class="text-danger fw-bold">This is the 2nd examiner checking part</span>';
                                                            echo '<script>$(document).ready(function() { $("#examinerCheckModal").modal("show"); });</script>';
                                                            $examinerCheck = true;
                                                        } else {
                                                            echo '<span class="text-success fw-bold">No examiner check - direct insert result</span>';
                                                            $examinerCheck = false;
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>

                                                <!-- -----------------------------------------------------------------------------  -->
                                                <tr>
                                                    <td class="bg-secondary text-white">Year</td>
                                                    <td><?php echo htmlspecialchars($assessment['year_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Semester</td>
                                                    <td><?php echo htmlspecialchars($assessment['semester_id']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-secondary text-white">Description</td>
                                                    <td><?php echo htmlspecialchars(strip_tags($assessment['description'])); ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>


                                        <?php

                                        // Now check the session user ID against the examiner IDs
                                        if ($Session_user_id == $ex_1_id || $Session_user_id == $ex_2_id) {
                                            // echo "<br> Only for showing for the session";

                                            if ($examinerCheck) {
                                                // echo "2 examinor chckingpart";
                                                if ($row_have) {

                                        ?>

                                                    <!-- ----------------------------------- radio button section ----------------------------------- -->
                                                    <div class="card">
                                                        <div class="card-body" id="choice">
                                                            <!-- <h6 class="card-title">Examinor name : <?= $examiner_role ?></h6> -->
                                                            <!-- <?= !empty($examinor_1_txt) ? $examinor_1_txt : $examinor_2_txt; ?> -->
                                                            <br>
                                                            <!-- examinor ID : <?= !empty($ex_1_id) ? $ex_1_id : $ex_2_id; ?> <br> -->
                                                            <!-- examinor stored column : <?= $matched_column ?> -->

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h5 class="card-title">With exam question or not?</h5>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="exam_check" value="yes" id="yesRadio">
                                                                        <label class="form-check-label" for="yesRadio">Yes</label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="exam_check" value="no" id="noRadio">
                                                                        <label class="form-check-label" for="noRadio">No</label>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- --------------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- radio button choose script  -->
                                                    <script>
                                                        $(document).ready(function() {
                                                            // When radio button is clicked
                                                            $('input[name="exam_check"]').change(function() {
                                                                window.selectedValue = $(this).val();
                                                                console.log("Selected value: " + selectedValue);

                                                                if (selectedValue === "yes") {
                                                                    $("#afterChoosedCount").show();
                                                                    $("#afterChoosedCountNo").hide();
                                                                    $("#singleInputContainer").hide(); // Hide single input
                                                                    $("#add_column_btn").show();
                                                                    $("#viewButtonResult").show();

                                                                } else {
                                                                    $("#afterChoosedCountNo").show(); // Hide dropdown
                                                                    $("#singleInputContainer").hide(); // Show single input
                                                                    $("#afterChoosedCount").hide(); // Show single input
                                                                    $("#add_column_btn").hide();
                                                                    $("#td_columns").hide();
                                                                    $("#viewButtonResult").hide();
                                                                    $("#viewResultSection").hide();

                                                                }
                                                            });


                                                            $("#viewButtonResult").click(function() {
                                                                $("#viewResultSection").toggle();
                                                                $("#afterChoosedCount").hide();
                                                            });
                                                        });
                                                    </script>

                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->

                                                    <!-- With exam question or not? -? YES -->
                                                    <div class="card mt-4" id="afterChoosedCount" style="display: none;">
                                                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                                                            <h6 class="mb-0 me-2">BBM Allocated Students & Add Results With Examinor (Yes)</h6>
                                                        </div>

                                                        <div class="card-body" style="font-size: 12px;">
                                                            <form action="" method="post" id="FormAfterChoosedCountYes">
                                                                <?php
                                                                $programme_id = $assessment['programme_id'];
                                                                $batch_id = $assessment['batch_id'];

                                                                $students_query = "
                                                                        SELECT s.*, a.student_registration_id
                                                                        FROM students s 
                                                                        INNER JOIN allocate_programme a ON s.student_code = a.student_code 
                                                                        WHERE a.programme_code = ? 
                                                                        AND a.batch_id = ? 
                                                                        AND a.status = 'active'";
                                                                $students_stmt = $conn->prepare($students_query);
                                                                $students_stmt->bind_param("ii", $programme_id, $batch_id);
                                                                $students_stmt->execute();
                                                                $students_result = $students_stmt->get_result();
                                                                $students = $students_result->fetch_all(MYSQLI_ASSOC);

                                                                $program_query = "SELECT p.program_name, p.result_method FROM program_table p WHERE p.program_code = ?";
                                                                $program_stmt = $conn->prepare($program_query);
                                                                $program_stmt->bind_param("i", $assessment['programme_id']);
                                                                $program_stmt->execute();
                                                                $program_result = $program_stmt->get_result();
                                                                $program_data = $program_result->fetch_assoc();

                                                                $mainComponentPercent = (int)$assessment['main_component_percent'];
                                                                $subComponentPercent = (int)$assessment['sub_component_percent'];

                                                                if ($students) {
                                                                    $results_query = "SELECT * FROM student_results 
                                                                            WHERE program_id = ? 
                                                                            AND batch_id = ? 
                                                                            AND module_id = ? 
                                                                            AND main_component_id = ?";
                                                                    $results_stmt = $conn->prepare($results_query);
                                                                    $results_stmt->bind_param(
                                                                        "iiii",
                                                                        $assessment['programme_id'],
                                                                        $assessment['batch_id'],
                                                                        $assessment['module_id'],
                                                                        $assessment['main_component_id']
                                                                    );
                                                                    $results_stmt->execute();
                                                                    $saved_results = $results_stmt->get_result();

                                                                    $student_results = [];
                                                                    while ($row = $saved_results->fetch_assoc()) {
                                                                        $student_results[$row['student_id']] = $row;
                                                                    }
                                                                ?>
                                                                    <div class="table-responsive">
                                                                        <div class="table-responsive">
                                                                            <table id="studentsTable" class="table table-striped table-hover">
                                                                                <thead>
                                                                                    <tr>
                                                                                        <th>#</th>
                                                                                        <th>Registration ID</th>
                                                                                        <th>Student Name</th>
                                                                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                                                                            <th>Q<?= $i ?></th>
                                                                                        <?php endfor; ?>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($students as $idx => $s): ?>
                                                                                        <tr
                                                                                            data-program-id="<?= htmlspecialchars($assessment['programme_id']) ?>"
                                                                                            data-batch-id="<?= htmlspecialchars($assessment['batch_id']) ?>"
                                                                                            data-module-id="<?= htmlspecialchars($assessment['module_id']) ?>"
                                                                                            data-main-component-id="<?= htmlspecialchars($assessment['main_component_id']) ?>"
                                                                                            data-student-id="<?= htmlspecialchars($s['student_registration_id']) ?>"
                                                                                            data-student-code="<?= htmlspecialchars($s['student_code']) ?>">
                                                                                            <td><?= $idx + 1 ?></td>
                                                                                            <td><?= htmlspecialchars($s['student_registration_id']) ?></td>
                                                                                            <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>

                                                                                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                                                                                <td>
                                                                                                    <input
                                                                                                        type="text"
                                                                                                        class="form-control"
                                                                                                        name="bbm_result_<?= $i ?>"
                                                                                                        style="width: 60px"
                                                                                                        value="">
                                                                                                </td>
                                                                                            <?php endfor; ?>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>

                                                                            <div class="mt-3 text-end">
                                                                                <button type="button" id="saveResults" class="btn btn-primary">
                                                                                    <i class="fas fa-save"></i> Save Results
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                <?php
                                                                } else {
                                                                    echo "<p>No students allocated for this program and batch.</p>";
                                                                }
                                                                ?>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- <<<<<<<<<<<<<   save_results_QUE_YES  >>>>>>>>>>>>> -->
                                                    <script>
                                                        $(document).ready(function() {
                                                            // === CONFIG ===
                                                            var columnAddCount = 8;
                                                            var selectedValue = 'yes';
                                                            var programmeId = <?= json_encode($assessment['programme_id']) ?>;
                                                            var batchId = <?= json_encode($assessment['batch_id']) ?>;
                                                            var moduleId = <?= json_encode($assessment['module_id']) ?>;
                                                            var mainComponentId = <?= json_encode($assessment['main_component_id']) ?>;
                                                            var examinerRole = <?= json_encode($examiner_role) ?>;
                                                            var examinerName = <?= json_encode(!empty($examinor_1_txt) ? $examinor_1_txt : $examinor_2_txt) ?>;
                                                            var examinerId = <?= json_encode(!empty($ex_1_id) ? $ex_1_id : $ex_2_id) ?>;
                                                            var matchedColumn = <?= json_encode($matched_column) ?>;
                                                            var mainComponentPercent = <?php echo json_encode(htmlspecialchars($assessment['main_component_percent'])); ?>;
                                                            var moduleGPA = <?= json_encode($assessment['module_GPA']) ?>;

                                                            var year_ID_Q_YES = <?php echo htmlspecialchars($assessment['year_id']); ?>;
                                                            var semester_ID_Q_YES = <?php echo htmlspecialchars($assessment['semester_id']); ?>;

                                                            $('#saveResults').on('click', function() {
                                                                var studentsData = [];

                                                                $('#studentsTable tbody tr').each(function() {
                                                                    var $tr = $(this);
                                                                    var rec = {
                                                                        studentRegistrationId: $tr.data('student-id'),
                                                                        studentCode: $tr.data('student-code')
                                                                    };
                                                                    for (var i = 1; i <= columnAddCount; i++) {
                                                                        var val = $tr.find('input[name="bbm_result_' + i + '"]').val();
                                                                        rec['bbm_result_' + i] = val !== '' ? val : null;
                                                                    }
                                                                    studentsData.push(rec);
                                                                });

                                                                var payload = {
                                                                    selectedValue: selectedValue,
                                                                    programmeId: programmeId,
                                                                    batchId: batchId,
                                                                    moduleId: moduleId,
                                                                    mainComponentId: mainComponentId,
                                                                    examinerRole: examinerRole,
                                                                    examinerName: examinerName,
                                                                    examinerId: examinerId,
                                                                    matchedColumn: matchedColumn,
                                                                    students: studentsData,
                                                                    mainComponentPercent: mainComponentPercent,
                                                                    moduleGPA: moduleGPA,
                                                                    year_id: year_ID_Q_YES,
                                                                    semester_id: semester_ID_Q_YES
                                                                };

                                                                $.ajax({
                                                                    url: 'BBM_result_store/save_results_QUE_YES.php',
                                                                    method: 'POST',
                                                                    contentType: 'application/json',
                                                                    dataType: 'json', // ← tell jQuery to parse JSON
                                                                    data: JSON.stringify(payload),
                                                                    success: function(response) {
                                                                        if (response.status === 'success') {
                                                                            alert('Results saved successfully!');
                                                                            console.log('Inserted:', response.insertedData);
                                                                            console.log('Updated:', response.updatedData);
                                                                        } else {
                                                                            // now response.error will be shown if provided
                                                                            alert('Save error: ' + (response.error || 'unknown'));
                                                                        }
                                                                    },
                                                                    error: function(xhr, status, err) {
                                                                        console.error('AJAX error', status, err, xhr.responseText);
                                                                        alert('An error occurred. Check console for details.');
                                                                    }
                                                                });
                                                            });
                                                        });
                                                    </script>

                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- fetch and show <<<<<  fetch_bbm_students.php >>>>>>>>   -->

                                                    <script>
                                                        $(document).ready(function() {
                                                            var columnAddCount = 8;
                                                            var matchedColumn = <?= json_encode($matched_column) ?>;
                                                            var programmeId = <?= json_encode($assessment['programme_id']) ?>;
                                                            var batchId = <?= json_encode($assessment['batch_id']) ?>;
                                                            var moduleId = <?= json_encode($assessment['module_id']) ?>;
                                                            var mainComponentId = <?= json_encode($assessment['main_component_id']) ?>;

                                                            $.getJSON('BBM_result_store/fetch_bbm_students.php', {
                                                                    matched_column: matchedColumn,
                                                                    programme_id: programmeId,
                                                                    batch_id: batchId,
                                                                    module_id: moduleId,
                                                                    main_component_id: mainComponentId
                                                                })
                                                                .done(function(students) {
                                                                    students.forEach(function(stu) {
                                                                        var $row = $('#studentsTable')
                                                                            .find("tr[data-student-code='" + stu.student_code + "']");
                                                                        if (!$row.length) return;

                                                                        for (var i = 1; i <= columnAddCount; i++) {
                                                                            $row
                                                                                .find('input[name="bbm_result_' + i + '"]')
                                                                                .val(stu['Q' + i] || '');
                                                                        }
                                                                    });
                                                                })
                                                                .fail(function(err) {
                                                                    console.error('Fetch error', err);
                                                                });
                                                        });
                                                    </script>

                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                    <!-- With exam question or not? -> NOT  -->
                                                    <div class="card mt-4" id="afterChoosedCountNo" style="display: none;">
                                                        <div class="card-header d-flex align-items-center" style="height: 60px;">
                                                            <h6 class="mb-0 me-2">BBM Allocated Students & Add Results With examinor (NO)</h6>
                                                        </div>

                                                        <div class="card-body" style="font-size: 12px;">
                                                            <?php
                                                            $programme_id = $assessment['programme_id'];
                                                            $batch_id = $assessment['batch_id'];

                                                            // Updated query to include student_registration_id
                                                            $students_query = "
                                                                SELECT s.*, a.student_registration_id
                                                                FROM students s 
                                                                INNER JOIN allocate_programme a ON s.student_code = a.student_code 
                                                                WHERE a.programme_code = ? 
                                                                AND a.batch_id = ? 
                                                                AND a.status = 'active'";

                                                            $students_stmt = $conn->prepare($students_query);
                                                            $students_stmt->bind_param("ii", $programme_id, $batch_id);
                                                            $students_stmt->execute();
                                                            $students_result = $students_stmt->get_result();
                                                            $students = $students_result->fetch_all(MYSQLI_ASSOC);

                                                            // Inside the students table section, first fetch the program's details
                                                            $program_query = "SELECT p.program_name, p.result_method 
                                                                    FROM program_table p 
                                                                    WHERE p.program_code = ?";
                                                            $program_stmt = $conn->prepare($program_query);
                                                            $program_stmt->bind_param("i", $assessment['programme_id']);
                                                            $program_stmt->execute();
                                                            $program_result = $program_stmt->get_result();
                                                            $program_data = $program_result->fetch_assoc();

                                                            $mainComponentPercent = (int)$assessment['main_component_percent'];
                                                            $subComponentPercent = (int)$assessment['sub_component_percent'];
                                                            echo  $mainComponentPercent;
                                                            // echo $subComponentPercent;  

                                                            if ($students) {
                                                                // First fetch existing results for all students
                                                                $results_query = "SELECT * FROM student_results 
                                                                            WHERE program_id = ? 
                                                                            AND batch_id = ? 
                                                                            AND module_id = ? 
                                                                            AND main_component_id = ?";

                                                                $results_stmt = $conn->prepare($results_query);
                                                                $results_stmt->bind_param(
                                                                    "iiii",
                                                                    $assessment['programme_id'],
                                                                    $assessment['batch_id'],
                                                                    $assessment['module_id'],
                                                                    $assessment['main_component_id']
                                                                );
                                                                $results_stmt->execute();
                                                                $saved_results = $results_stmt->get_result();

                                                                // Create an associative array of results
                                                                $student_results = [];
                                                                while ($row = $saved_results->fetch_assoc()) {
                                                                    $student_results[$row['student_id']] = $row;
                                                                }

                                                                echo "<div class='table-responsive'>";
                                                                echo "<table id='studentsTableNo' class='table table-striped table-hover'>
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>#</th>
                                                                                    <th>Registration ID</th>
                                                                                    <th>Student Name</th>";
                                                                echo "<th>100%</th> <th> $mainComponentPercent%  marks</th>"; // If 2nd examiner check is required
                                                                echo "</tr></thead><tbody>";

                                                            ?>
                                                                <?php

                                                                $index = 1;
                                                                foreach ($students as $student) {
                                                                    $savedResult = isset($student_results[$student['student_code']]) ?
                                                                        $student_results[$student['student_code']] : null;
                                                                    echo "<tr data-program-id='" . htmlspecialchars($assessment['programme_id']) . "' 
                                                                                data-batch-id='" . htmlspecialchars($assessment['batch_id']) . "' 
                                                                                data-module-id='" . htmlspecialchars($assessment['module_id']) . "' 
                                                                                data-main-component-id='" . htmlspecialchars($assessment['main_component_id']) . "' 
                                                                                data-sub-component-id='" . (isset($assessment['sub_component_id']) ? htmlspecialchars($assessment['sub_component_id']) : 'N/A') . "'
                                                                                data-student-id='" . htmlspecialchars($student['student_registration_id']) . "'
                                                                                data-student-code='" . htmlspecialchars($student['student_code']) . "'>
                                                                                <td style='width: 20px;'>" . $index++ . "</td>
                                                                                <td style='width: 140px;'>" . htmlspecialchars($student['student_registration_id']) . "</td>
                                                                                <td style='width: 100px;'>" . htmlspecialchars($student['first_name']) . " " . htmlspecialchars($student['last_name']) . "</td>";
                                                                ?>


                                                                    <div>
                                                                        <td style='width: 30px;'>
                                                                            <label for="result_<?php echo $index; ?>"></label>
                                                                            <input style="width: 50px;" type='text' id="result_<?php echo $index; ?>" name='result_<?php echo $index; ?>' value='<?php echo isset($savedResult['result']) ? htmlspecialchars($savedResult['result']) : ''; ?>'>
                                                                        </td>

                                                                        <td style='width: 30px;'>
                                                                            <label for="converted_<?php echo $index; ?>"></label>
                                                                            <input style="width: 50px;" type='text' id="converted_<?php echo $index; ?>" name='converted_<?php echo $index; ?>' readonly>
                                                                        </td>
                                                                        <script>
                                                                            $(document).ready(function() {
                                                                                var mainComponentPercent = <?php echo $mainComponentPercent; ?>; // PHP variable

                                                                                $("input[id^='result_']").on("input", function() {
                                                                                    var index = $(this).attr('id').split('_')[1]; // Extract index
                                                                                    var resultValue = parseFloat($(this).val());

                                                                                    if (!isNaN(resultValue) && resultValue >= 1 && resultValue <= 100) {
                                                                                        var convertedValue = (resultValue * mainComponentPercent) / 100;
                                                                                        $("#converted_" + index).val(convertedValue.toFixed(2)); // Update converted field
                                                                                    } else {
                                                                                        $("#converted_" + index).val(""); // Clear field if invalid
                                                                                    }
                                                                                });
                                                                            });
                                                                        </script>
                                                                    </div>

                                                            <?php
                                                                    echo "</tr>";
                                                                }

                                                                echo "</tbody></table>";
                                                                // Add Save Results button
                                                                echo "<div class='mt-3 text-end'>
                                                                        <button type='button' id='save_result_without_ques' class='btn btn-primary'>
                                                                            <i class='fas fa-save'></i> Save Results
                                                                        </button>
                                                                    </div>";

                                                                echo "</div>"; // Close table-responsive div
                                                            } else {
                                                                echo "<p>No students allocated for this program and batch.</p>";
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>

                                                    <!-- ------------------------------------------------------------------------------   -->
                                                    <!-- working code  <When Loading fetching for update without ques - NOO> -->
                                                    <script>
                                                        $(document).ready(function() {
                                                            $('#noRadio').click(function() {

                                                                // Prepare program and examiner details
                                                                var programmeId = <?php echo json_encode($assessment['programme_id']); ?>;
                                                                var batchId = <?php echo json_encode($assessment['batch_id']); ?>;
                                                                var moduleId = <?php echo json_encode($assessment['module_id']); ?>;
                                                                var mainComponentId = <?php echo json_encode($assessment['main_component_id']); ?>;
                                                                var examinerRole = <?php echo json_encode($examiner_role); ?>;
                                                                var examinerName = <?php echo json_encode(!empty($examinor_1_txt) ? $examinor_1_txt : $examinor_2_txt); ?>;
                                                                var examinerId = <?php echo json_encode(!empty($ex_1_id) ? $ex_1_id : $ex_2_id); ?>;
                                                                var matchedColumn = <?php echo json_encode($matched_column); ?>;

                                                                // Prepare an array to hold the student data to send
                                                                var student_Results = [];

                                                                // Loop through all rows in the table to collect data
                                                                $('#studentsTableNo tbody tr').each(function() {
                                                                    var studentId = $(this).data('student-id');
                                                                    var studentCode = $(this).data('student-code');
                                                                    var result = $(this).find('input[id^="result_"]').val();
                                                                    var converted = $(this).find('input[id^="converted_"]').val();

                                                                    // Add the data to the studentResults array
                                                                    student_Results.push({
                                                                        studentId: studentId,
                                                                        studentCode: studentCode,
                                                                        result: result,
                                                                        converted: converted
                                                                    });
                                                                });

                                                                // Prepare the data object to send in the AJAX request
                                                                var data = {
                                                                    programmeId: programmeId,
                                                                    batchId: batchId,
                                                                    moduleId: moduleId,
                                                                    mainComponentId: mainComponentId,
                                                                    examinerRole: examinerRole,
                                                                    examinerName: examinerName,
                                                                    examinerId: examinerId,
                                                                    matchedColumn: matchedColumn,
                                                                    student_Results: student_Results,
                                                                    selectedValue: 'no'
                                                                };

                                                                // AJAX request to fetch data from bbm_result_details
                                                                $.ajax({
                                                                    url: 'BBM_result_store/fetch_bbm_results_QUE_NO.php', // Path to PHP script
                                                                    type: 'POST',
                                                                    contentType: 'application/json',
                                                                    data: JSON.stringify(data), // Send the data object here
                                                                    success: function(response) {
                                                                        console.log('Fetched Data:', response);
                                                                        var data = JSON.parse(response); // Parse the JSON response
                                                                        if (data.error) {
                                                                            console.log(data.error); // If no data was found
                                                                        } else {

                                                                            data.forEach(function(studentData) {
                                                                                // Find the row with the matching student ID
                                                                                var row = $('#studentsTableNo tbody tr').filter(function() {
                                                                                    return $(this).data('student-code') == studentData.student_id;
                                                                                });

                                                                                if (row.length > 0) {
                                                                                    // Update the relevant inputs with the fetched data
                                                                                    row.find('input[id^="result_"]').val(studentData.marks100); // Updating the 'result_' field with marks100
                                                                                    row.find('input[id^="converted_"]').val(studentData.examiner_marks); // Updating the 'converted_' field with examiner_marks
                                                                                }
                                                                            });

                                                                        }
                                                                    },
                                                                    error: function(error) {
                                                                        console.error('Error:', error);
                                                                    }
                                                                });
                                                            });

                                                        });
                                                    </script>

                                                    <!-- ------------------------------------------------------------------------------   -->

                                                    <!-- save_result_without_ques - NOOOO  -->
                                                    <script>
                                                        document.getElementById('save_result_without_ques').addEventListener('click', function() {

                                                            // Prepare program and examiner details
                                                            var programId = <?php echo json_encode($assessment['programme_id']); ?>;
                                                            var b_Id = <?php echo json_encode($assessment['batch_id']); ?>;
                                                            var mod_Id = <?php echo json_encode($assessment['module_id']); ?>;
                                                            var mainComId = <?php echo json_encode($assessment['main_component_id']); ?>;
                                                            var examiner_Role = <?php echo json_encode($examiner_role); ?>;
                                                            var examiner_Name = <?php echo json_encode(!empty($examinor_1_txt) ? $examinor_1_txt : $examinor_2_txt); ?>;
                                                            var examiner_Id = <?php echo json_encode(!empty($ex_1_id) ? $ex_1_id : $ex_2_id); ?>;
                                                            var matched_Column = <?php echo json_encode($matched_column); ?>;
                                                            var moduleGPA_Q_NO = <?= json_encode($assessment['module_GPA']) ?>;


                                                            var year_ID_Q_NO = <?php echo htmlspecialchars($assessment['year_id']); ?>;
                                                            var semester_ID_Q_NO = <?php echo htmlspecialchars($assessment['semester_id']); ?>;


                                                            // Prepare an array to hold the student data to send
                                                            var student_Results = [];

                                                            // Loop through all rows in the table to collect data
                                                            $('#studentsTableNo tbody tr').each(function() {
                                                                var studentId = $(this).data('student-id');
                                                                var studentCode = $(this).data('student-code');
                                                                var result = $(this).find('input[id^="result_"]').val();
                                                                var converted = $(this).find('input[id^="converted_"]').val();

                                                                // Add the data to the studentResults array
                                                                student_Results.push({
                                                                    studentId: studentId,
                                                                    studentCode: studentCode,
                                                                    result: result,
                                                                    converted: converted
                                                                });
                                                            });

                                                            // Prepare the data to send to the server
                                                            var data = {
                                                                programmeId: programId,
                                                                batchId: b_Id,
                                                                moduleId: mod_Id,
                                                                mainComponentId: mainComId,
                                                                examinerRole: examiner_Role,
                                                                examinerName: examiner_Name,
                                                                examinerId: examiner_Id,
                                                                matchedColumn: matched_Column,
                                                                studentResults: student_Results, // Include the student results data
                                                                selectedValue: window.selectedValue,
                                                                moduleGPA_Q_NO: moduleGPA_Q_NO,
                                                                year_id: year_ID_Q_NO,
                                                                semester_id: semester_ID_Q_NO
                                                            };

                                                            // Console log for debugging
                                                            console.log(data);

                                                            $.ajax({
                                                                url: 'BBM_result_store/save_results_QUE_NO.php',
                                                                type: 'POST',
                                                                contentType: 'application/json',
                                                                data: JSON.stringify(data),
                                                                success: function(response) {
                                                                    console.log('Response:', response);

                                                                    if (response.status === 'success') {
                                                                        alert("Results saved successfully!");

                                                                        // Optionally display the inserted/updated data in the console
                                                                        console.log('Inserted/Updated Data:', response.insertedData);
                                                                    }
                                                                },
                                                                error: function(error) {
                                                                    console.error('Error:', error);
                                                                }
                                                            });

                                                        });
                                                    </script>

                                                    <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                <?php

                                                } else {
                                                    echo "<p>No examiners assigned for this program and batch.</p>";
                                                }
                                            }

                                            // <!-- --- ------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                            // this else part if not check examinor section  Common Entered section
                                            else {
                                                ?>
                                                <!-- Separate Card for Allocated Students -->

                                                <div class="card mt-4">
                                                    <div class="card-header d-flex align-items-center" style="height: 60px;">
                                                        <h6 class="mb-0 me-2">BBM Allocated Students & Add Results Without Examiners</h6>
                                                    </div>
                                                    <div class="card-body" style="font-size: 12px;">
                                                        <?php
                                                        $programme_id = $assessment['programme_id'];
                                                        $batch_id = $assessment['batch_id'];

                                                        // Updated query to include student_registration_id
                                                        $students_query = "
                                                                SELECT s.*, a.student_registration_id
                                                                FROM students s 
                                                                INNER JOIN allocate_programme a ON s.student_code = a.student_code 
                                                                WHERE a.programme_code = ? 
                                                                AND a.batch_id = ? 
                                                                AND a.status = 'active'";

                                                        $students_stmt = $conn->prepare($students_query);
                                                        $students_stmt->bind_param("ii", $programme_id, $batch_id);
                                                        $students_stmt->execute();
                                                        $students_result = $students_stmt->get_result();
                                                        $students = $students_result->fetch_all(MYSQLI_ASSOC);

                                                        // Display students in a DataTable with index
                                                        // Inside the students table section, first fetch the program's details
                                                        $program_query = "SELECT p.program_name, p.result_method 
                                                                FROM program_table p 
                                                                WHERE p.program_code = ?";
                                                        $program_stmt = $conn->prepare($program_query);
                                                        $program_stmt->bind_param("i", $assessment['programme_id']);
                                                        $program_stmt->execute();
                                                        $program_result = $program_stmt->get_result();
                                                        $program_data = $program_result->fetch_assoc();

                                                        // Add debugging information
                                                        echo "<script>
                                                                console.log('Program Code:', " . json_encode($assessment['programme_id']) . ");
                                                                console.log('Program Name:', " . json_encode($program_data['program_name']) . ");
                                                                </script>";

                                                        $mainComponentPercent = (int)$assessment['main_component_percent'];
                                                        $subComponentPercent = (int)$assessment['sub_component_percent'];

                                                        // Display students in a DataTable with index
                                                        if ($students) {
                                                            // First fetch existing results for all students
                                                            $results_query = "SELECT * FROM student_results 
                                                                                WHERE program_id = ? 
                                                                                AND batch_id = ? 
                                                                                AND module_id = ? 
                                                                                AND main_component_id = ?";

                                                            $results_stmt = $conn->prepare($results_query);
                                                            $results_stmt->bind_param(
                                                                "iiii",
                                                                $assessment['programme_id'],
                                                                $assessment['batch_id'],
                                                                $assessment['module_id'],
                                                                $assessment['main_component_id']
                                                            );
                                                            $results_stmt->execute();
                                                            $saved_results = $results_stmt->get_result();

                                                            // Create an associative array of results
                                                            $student_results = [];
                                                            while ($row = $saved_results->fetch_assoc()) {
                                                                $student_results[$row['student_id']] = $row;
                                                            }

                                                            echo "<div class='table-responsive'>";
                                                            echo "<table id='studentsTable' class='table table-striped table-hover'>
                                                                    <thead>
                                                                        <tr>
                                                                            <th>#</th>
                                                                            <th>Registration ID</th>
                                                                            <th>Student Name</th>";

                                                            if (strpos($assessment['program_name'], 'Bachelor of Business Management (Hons)') !== false) {
                                                                echo "<th>100% Marks</th><th> $subComponentPercent% Marks</th>"; // If no examiner check
                                                            }

                                                            echo "</tr></thead><tbody>";

                                                            $index = 1;
                                                            foreach ($students as $student) {
                                                                $savedResult = isset($student_results[$student['student_code']]) ?
                                                                    $student_results[$student['student_code']] : null;

                                                                echo "<tr data-program-id='" . htmlspecialchars($assessment['programme_id']) . "' 
                                                                        data-batch-id='" . htmlspecialchars($assessment['batch_id']) . "' 
                                                                        data-module-id='" . htmlspecialchars($assessment['module_id']) . "' 
                                                                        data-main-component-id='" . htmlspecialchars($assessment['main_component_id']) . "' 
                                                                        data-sub-component-id='" . (isset($assessment['sub_component_id']) ? htmlspecialchars($assessment['sub_component_id']) : 'N/A') . "'
                                                                        data-student-id='" . htmlspecialchars($student['student_registration_id']) . "'
                                                                        data-student-code='" . htmlspecialchars($student['student_code']) . "'>
                                                                    <td style='width: 20px;'>" . $index++ . "</td>
                                                                        <td style='width: 200px;'>" . htmlspecialchars($student['student_registration_id']) . "</td>
                                                                        <td style='width: 200px;'>" . htmlspecialchars($student['first_name']) . " " . htmlspecialchars($student['last_name']) . "</td>";


                                                                // For Higher Diploma
                                                                if (strpos($assessment['program_name'], 'Bachelor of Business Management (Hons)') !== false) {

                                                        ?>

                                                                    <td style="width: 100px;">
                                                                        <input type="number" class="result-input" min="0" max="100"
                                                                            value="" data-sub-percent="<?php echo $subComponentPercent; ?>"
                                                                            id="results_<?php echo $student['student_code']; ?>">
                                                                    </td>
                                                                    <td style="width: 100px;">
                                                                        <input type="number" class="final-result" min="0" max="100"
                                                                            value="" readonly id="final_results_<?php echo $student['student_code']; ?>">
                                                                    </td>


                                                                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                                                                    <script>
                                                                        $(document).ready(function() {
                                                                            $(".result-input").on("input", function() {
                                                                                let enteredValue = parseFloat($(this).val()) || 0; // Get entered value, default to 0 if empty
                                                                                let subPercent = parseFloat($(this).data("sub-percent")) || 0; // Get percentage value from data attribute
                                                                                let calculatedValue = (enteredValue * subPercent) / 100; // Calculate final result

                                                                                // Find the corresponding final result input in the same row and update it
                                                                                $(this).closest("tr").find(".final-result").val(calculatedValue.toFixed(2));
                                                                            });
                                                                        });
                                                                    </script>
                                                        <?php

                                                                }
                                                                echo "</tr>";
                                                            }

                                                            echo "</tbody></table>";
                                                            // Add Save Results button
                                                            echo "<div class='mt-3 text-end'>
                                                                    <button type='button' id='save_common_rst' class='btn btn-primary'>
                                                                        <i class='fas fa-save'></i> Save Results
                                                                    </button>
                                                                </div>";

                                                            echo "</div>"; // Close table-responsive div
                                                        } else {
                                                            echo "<p>No students allocated for this program and batch.</p>";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>

                                                <script>
                                                    $('#save_common_rst').click(function() {
                                                        let results = [];
                                                        // Get year_id and semester_id from the page
                                                        var year_ID_Q_DIRECT = <?php echo htmlspecialchars($assessment['year_id']); ?>;
                                                        var semester_ID_Q_DIRECT = <?php echo htmlspecialchars($assessment['semester_id']); ?>;

                                                        $('#studentsTable tbody tr').each(function() {
                                                            let row = $(this);
                                                            let studentData = {
                                                                program_id: row.data('program-id'),
                                                                batch_id: row.data('batch-id'),
                                                                module_id: row.data('module-id'),
                                                                main_component_id: row.data('main-component-id'),
                                                                sub_component_id: row.data('sub-component-id'),
                                                                student_registration_id: row.data('student-id'),
                                                                student_code: row.data('student-code'),
                                                                result: row.find('.result-input').val() || 0,
                                                                final_result: row.find('.final-result').val() || 0
                                                            };
                                                            results.push(studentData);
                                                        });
                                                        console.log(results);
                                                        var moduleGPA_Q_Direct = <?= json_encode($assessment['module_GPA']) ?>;

                                                        // Send AJAX request with proper headers
                                                        $.ajax({
                                                            url: 'BBM_result_store/save_direct_results.php',
                                                            type: 'POST',
                                                            data: JSON.stringify({
                                                                studentResults: results,
                                                                moduleGPA_Q_Direct: moduleGPA_Q_Direct,
                                                                year_id: year_ID_Q_DIRECT,
                                                                semester_id: semester_ID_Q_DIRECT
                                                            }),
                                                            contentType: 'application/json', // Tell PHP to expect JSON
                                                            dataType: 'json',
                                                            success: function(response) {
                                                                if (response.status === 'success') {
                                                                    alert('Results saved successfully!');
                                                                    console.log(response.insertedData);
                                                                } else {
                                                                    alert('Failed to save results: ' + response.message);
                                                                    console.error(response);
                                                                }
                                                            },
                                                            error: function(xhr, status, error) {
                                                                console.error('AJAX Error:', xhr.responseText);
                                                                alert('An error occurred while saving the results.');
                                                            }
                                                        });
                                                    });
                                                </script>

                                                <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                                <!-- fetch diret bbm result  -->

                                                <script>
                                                    $(document).ready(function() {

                                                        let batchId = <?= json_encode($assessment['batch_id']) ?>;
                                                        let mainComponentId = <?= json_encode($assessment['main_component_id']) ?>;
                                                        let subComponentId = <?= json_encode($assessment['sub_component_id']) ?>;
                                                        let moduleId = <?= json_encode($assessment['module_id']) ?>;
                                                        let programId = <?= json_encode($assessment['programme_id']) ?>;

                                                        console.log('---------------------------');
                                                        console.log('Program ID:', programId);
                                                        console.log('Batch ID:', batchId);
                                                        console.log('Module ID:', moduleId);
                                                        console.log('Main Component ID:', mainComponentId);
                                                        console.log('Sub Component ID:', subComponentId);

                                                        // Fetch student data
                                                        let studentData = [];
                                                        $('#studentsTable tbody tr').each(function() {
                                                            let row = $(this);
                                                            let student = {
                                                                student_registration_id: row.data('student-id'),
                                                                student_code: row.data('student-code')
                                                            };
                                                            studentData.push(student);
                                                        });

                                                        // console.log('Student Data:', studentData);

                                                        $.ajax({
                                                            url: 'BBM_result_store/fetch_bbm_direct_results.php',
                                                            type: 'POST',
                                                            dataType: 'json', // expecting JSON response
                                                            data: {
                                                                student_data: JSON.stringify(studentData), // send as JSON string
                                                                program_id: programId,
                                                                batch_id: batchId,
                                                                module_id: moduleId,
                                                                main_comp_id: mainComponentId,
                                                                sub_component_id: subComponentId
                                                            },

                                                            success: function(response) {
                                                                console.log('---------------------------');

                                                                let results;
                                                                try {
                                                                    results = typeof response === 'string' ? JSON.parse(response) : response;
                                                                } catch (e) {
                                                                    console.error('Invalid JSON response:', response);
                                                                    return;
                                                                }

                                                                console.log('Fetched BBM Results:', results);

                                                                if (Array.isArray(results)) {
                                                                    results.forEach(function(item) {
                                                                        const studentId = item.student_id;
                                                                        const resultVal = item['100marksEx1'] ? item['100marksEx1'] : 0;
                                                                        const finalResultVal = item.examiner1_marks ? item.examiner1_marks : 0;

                                                                        $(`#results_${studentId}`).val(resultVal);
                                                                        $(`#final_results_${studentId}`).val(finalResultVal);



                                                                    });
                                                                } else if (results.error) {
                                                                    console.error('Server Error:', results.error);
                                                                }
                                                            },

                                                            error: function(xhr, status, error) {
                                                                console.error('Error fetching BBM results:', error);
                                                            }
                                                        });


                                                        console.log('---------------------------');
                                                    });
                                                </script>
                                                <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->

                                        <?php
                                            }
                                        } else {
                                            echo "<br> Not assigned for these";
                                        }





                                        // <!-- ---------------------------------------------------------------------------------------------------------------------------------------------------------  -->
                                        ?>
                                <?php
                                    } else {
                                        echo "<p>
                                        .</p>";
                                    }
                                } else {
                                    echo "<p>No ID provided.</p>";
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

<!-- DataTables JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {

        window.matchedColumn = <?php echo json_encode($matched_column); ?>;
        console.log("Matched Column:", window.matchedColumn); // Log to console


        $('#studentsTable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable search
            "lengthChange": true, // Allow changing the number of items per page
            "pageLength": 10, // Set the default number of items per page
            "responsive": true, // Make the table responsive
            "autoWidth": false // Disable automatic column width adjustment
        });
    });
</script>

<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<!-- Include necessary CSS and JS for Select2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

</body>

</html>