<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}

// Capture query parameters
$studentId = $_GET['student_id'] ?? null;
$finalResultId = $_GET['id'] ?? null;
$programName = $_GET['program'] ?? null;
$batchId = $_GET['batch_id'] ?? null;
$moduleId = $_GET['module_id'] ?? null;
$programId = $_GET['program_id'] ?? null;
$mainComponentId = $_GET['main_component_id'] ?? null;
$subComponentId = $_GET['sub_component_id'] ?? null;

// Determine if it's a final result
$isFinalResult = !empty($finalResultId) && !empty($studentId) && !empty($programName) && !empty($programId) && !empty($batchId) && !empty($moduleId);
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
                    <?php if ($mainComponentId || $subComponentId): ?>
                        <h1 class="h1 mb-0 text-gray-800">Result Table</h1>
                    <?php else: ?>
                        <h3 class="h3 mb-0 text-gray-800">Final Result Single View</h3>
                    <?php endif; ?>
                </div>

                <!-- Display the captured values -->
                <div class="card mb-4">
                    <div class="card-body">

                        <!-- Conditional display for main and sub components -->
                        <?php if ($mainComponentId || $subComponentId): ?>
                            <?php
                            // Fetch the student result details from the database if available
                            if (!empty($studentId) && !empty($programId) && !empty($batchId) && !empty($moduleId)) {
                                $query = "
                                SELECT sr.*, ap.student_registration_id as stdREGno ,
                                    sr.comment,
                                    sr.description_for_std  as desc2,
                                    CONCAT(s.first_name, ' ', s.last_name) AS full_name, 
                                    s.*, 
                                    pt.*, 
                                    bt.*, 
                                    m.*, 
                                    ap.*,
                                    pw.*,
                                    ac.main_component_id AS allocated_main_component_id, 
                                    ac.sub_component_id AS allocated_sub_component_id,
                                    amc.as_main_component_name,
                                    sac.sub_component_name,
                                    (SELECT status FROM email_sending_log 
                                     WHERE student_id = s.student_code 
                                     AND module_id = sr.module_id 
                                     AND main_component_id = sr.main_component_id
                                     ORDER BY sent_date DESC LIMIT 1) as email_status,
                                    (SELECT sent_date FROM email_sending_log 
                                     WHERE student_id = s.student_code 
                                     AND module_id = sr.module_id 
                                     AND main_component_id = sr.main_component_id
                                     ORDER BY sent_date DESC LIMIT 1) as email_sent_date
                                    FROM student_results sr
                                    INNER JOIN students s ON sr.student_id = s.student_code
                                    INNER JOIN allocate_programme ap ON s.student_code = ap.student_code
                                    INNER JOIN program_table pt ON ap.programme_code = pt.program_code
                                    INNER JOIN batch_table bt ON ap.batch_id = bt.id
                                    INNER JOIN modules m ON sr.module_id = m.id
                                    INNER JOIN allocated_components ac ON sr.module_id = ac.module_id
                                    LEFT JOIN assignment_components amc ON ac.main_component_id = amc.id
                                    LEFT JOIN sub_assign_components sac ON ac.sub_component_id = sac.id
                                     LEFT JOIN payment_withheld_table pw ON s.student_code = pw.student_code
                                    WHERE sr.student_id = ?  AND ap.status = 'active'
                                  AND sr.program_id = ? 
                                  AND sr.batch_id = ? 
                                  AND sr.module_id = ? 
                                  AND (sr.main_component_id = ? OR sr.sub_component_id = ?)";

                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("iiiiii", $studentId, $programId, $batchId, $moduleId, $mainComponentId, $subComponentId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($row = $result->fetch_assoc()) {

                                    if (
                                        $programName == 'Executive Certificate in Management'
                                        // $programName == 'BSc (Hons) in Software Engineering'
                                    ) {
                                        $displayResult = $row['converted_marks'] ?? 'No Marks Available';
                                    } else if (
                                        $programName == 'Higher Diploma in Biomedical Science' ||
                                        $programName == 'Higher Diploma in Biotechnology' ||
                                        $programName == 'BSc (Hons) in Software Engineering' ||
                                        $programName == 'Higher Diploma in Food Science and Nutrition' ||
                                        $programName == 'Higher Diploma in Medical Biotechnology'
                                    ) {
                                        // Check for resit grades and assign the appropriate result
                                        $displayResult = !empty($row['hd_resit3_grade']) ? $row['hd_resit3_grade'] : (!empty($row['hd_resit2_grade']) ? $row['hd_resit2_grade'] : (!empty($row['hd_resit1_grade']) ? $row['hd_resit1_grade'] :
                                            $row['hd_grade']));  // Last fallback to hd_grade
                                    } else {
                                        $displayResult = !empty($row['resit_result_4']) ? $row['resit_result_4'] : (!empty($row['resit_result_3']) ? $row['resit_result_3'] : (!empty($row['resit_result_2']) ? $row['resit_result_2'] : (!empty($row['resit_result_1']) ? $row['resit_result_1'] :
                                            $row['result'])));
                                    }

                                    // Fetch the main component name using the mainComponentId
                                    $componentQuery = "SELECT as_main_component_name FROM assignment_components WHERE id = ?";
                                    $componentStmt = $conn->prepare($componentQuery);
                                    $componentStmt->bind_param("i", $mainComponentId);
                                    $componentStmt->execute();
                                    $componentResult = $componentStmt->get_result();
                                    $componentName = $componentResult->fetch_assoc()['as_main_component_name'] ?? 'Unknown Component';

                                    // Fetch the sub component name using the subComponentId if it exists
                                    $subComponentName = 'No Sub Component'; // Default value
                                    if (!empty($subComponentId)) {
                                        $subComponentQuery = "SELECT sub_component_name FROM sub_assign_components WHERE id = ?";
                                        $subComponentStmt = $conn->prepare($subComponentQuery);
                                        $subComponentStmt->bind_param("i", $subComponentId);
                                        $subComponentStmt->execute();
                                        $subComponentResult = $subComponentStmt->get_result();
                                        $subComponentName = $subComponentResult->fetch_assoc()['sub_component_name'] ?? 'Unknown Sub Component';
                                    }

                                    // Display the success message with the component names
                                    echo "<p class='text-success'>Main Component: $componentName (ID: $mainComponentId)</p>";
                                    echo "<p class='text-success'>Sub Components: $subComponentName (ID: $subComponentId)</p>";
                                    // --------------------------------------------------- 
                                    // send compoentn result success 
                                    // Display the result details
                                    echo "<table class='table table-striped'>";
                                    echo "<tr><th>Student IDs</th><td>" . (!empty($row['stdREGno']) ? htmlspecialchars($row['stdREGno']) : "<span class='text-danger'>ID not available</span>") . "</td></tr>";
                                    echo "<tr><th>Name</th><td>" . htmlspecialchars($row['full_name']) . "</td></tr>";
                                    echo "<tr><th>Email</th><td>" . htmlspecialchars($row['bms_email']) . "</td></tr>";
                                    echo "<tr><th>Program</th><td>" . htmlspecialchars($row['program_name']) . "</td></tr>";
                                    echo "<tr><th>Batch</th><td>" . htmlspecialchars($row['batch_name']) . "</td></tr>";
                                    echo "<tr><th>Module</th><td>" . htmlspecialchars($row['module_name']) . "</td></tr>";
                                    echo "<tr><th>Components</th><td>" . htmlspecialchars($componentName) . "</td></tr>";
                                    echo "<tr><th>Sub</th><td>" . htmlspecialchars($subComponentName) . "</td></tr>";
                                    echo "<tr><th>Result</th><td>" . htmlspecialchars($displayResult) . "</td></tr>";

                                    // m 
                                    // Display payment status
                                    echo "<tr><th>Payment Status</th><td>" . (empty($row['payment_status']) ? "<span class='text-danger'>Withheld</span>" : htmlspecialchars($row['payment_status'])) . "</td></tr>";
                            ?>
                                    <!-- ------------ 30.06 2025 ------------ -->

                                    <tr>
                                        <th>payment status manually change</th>
                                        <td>
                                            <!-- <input type="checkbox" name="payment_status" value="paid"> -->
                                            <!-- <input type="checkbox" name="payment_status" value="paid" class="payment-status-checkbox" data-student-id="<?= htmlspecialchars($row['student_code']) ?>" <?= ($row['payment_status'] == 'active') ? 'checked' : '' ?>> -->
                                            <input type="checkbox" name="payment_status" value="paid" class="payment-status-checkbox" data-student-id="<?= htmlspecialchars($row['student_code']) ?>" data-program-id="<?= htmlspecialchars($row['program_id']) ?>" <?= ($row['payment_status'] == 'active') ? 'checked' : '' ?>>



                                        </td>
                                    </tr>



                                    <script>
                                        $(document).ready(function() {
                                            $('.payment-status-checkbox').change(function() {
                                                var studentId = $(this).data('student-id');
                                                var programId = $(this).data('program-id');
                                                var status = $(this).is(':checked') ? 'active' : 'withheld';

                                                $.ajax({
                                                    url: 'update_payment_status.php', // Create this file to handle the update
                                                    type: 'POST',
                                                    data: {
                                                        student_id: studentId,
                                                        program_id: programId,
                                                        payment_status: status
                                                    },
                                                    success: function(response) {
                                                        window.location.reload();
                                                    },
                                                    error: function(xhr, status, error) {
                                                        console.error('Error updating payment status:', error);
                                                    }
                                                });
                                            });
                                        });
                                    </script>


                                    <!-- -----------------------------------------------  -->

                            <?php
                                    // Add the hidden input for payment_status
                                    echo "<input type='hidden' name='payment_status' value='" . htmlspecialchars($row['payment_status'] ?? 'Withheld') . "'>";

                                    // Display email status
                                    echo "<tr><th>Email Status</th><td>";
                                    if (!empty($row['email_status'])) {
                                        $statusClass = ($row['email_status'] == 'sent') ? 'text-success' : 'text-danger';
                                        $statusText = ($row['email_status'] == 'sent') ? 'Sent' : 'Failed';
                                        $sentDate = !empty($row['email_sent_date']) ? (new DateTime($row['email_sent_date']))->format('Y-m-d H:i') : 'N/A';
                                        echo "<span class='{$statusClass}'>{$statusText}</span> on {$sentDate}";
                                    } else {
                                        echo "<span class='text-warning'>Not Sent</span>";
                                    }
                                    echo "</td></tr>";

                                    if (!empty($row['comment'])) {
                                        echo "<tr style='background-color: #f8f9fa;'>
                                            <th style='text-align: left; padding: 10px;'>Comment</th>
                                            <td style='padding: 10px;'>" . nl2br(htmlspecialchars($row['comment'])) . "</td>
                                        </tr>";
                                    }


                                    if (!empty($row['description_for_std'])) {
                                        echo "<tr style='background-color: #f8f9fa;'>
                                                <th style='text-align: left; padding: 10px;'>Descriptions</th>
                                                <td style='padding: 10px;'>
                                                    <textarea id='description_editor' name='description_editor' readonly>
                                                        " . htmlspecialchars($row['description_for_std']) . "
                                                    </textarea>
                                                    <script>
                                                        CKEDITOR.replace('description_editor', {
                                                            readOnly: true,
                                                            toolbar: [], 
                                                            removePlugins: 'elementspath,resize',
                                                            height: '200px',
                                                            width: '100%',
                                                            allowedContent: true 
                                                        });
                                                    </script>
                                                </td>
                                              </tr>";
                                    }


                                    // Add more fields as necessary
                                    echo "</table>";

                                    // Add the "Send Component Result" button
                                    echo "<form method='post'>";
                                    echo "<input type='hidden' name='student_id' value='{$row['stdREGno']}'>";
                                    echo "<input type='hidden' name='student_code' value='{$row['student_code']}'>";
                                    echo "<input type='hidden' name='full_name' value='{$row['full_name']}'>";
                                    echo "<input type='hidden' name='email' value='{$row['bms_email']}'>";
                                    echo "<input type='hidden' name='program_name' value='{$row['program_name']}'>";
                                    echo "<input type='hidden' name='program_id' value='{$programId}'>";
                                    echo "<input type='hidden' name='batch_name' value='{$row['batch_name']}'>";
                                    echo "<input type='hidden' name='batch_id' value='{$batchId}'>";
                                    echo "<input type='hidden' name='module_name' value='{$row['module_name']}'>";
                                    echo "<input type='hidden' name='module_id' value='{$moduleId}'>";
                                    echo "<input type='hidden' name='component_name' value='{$componentName}'>";
                                    echo "<input type='hidden' name='main_component_id' value='{$mainComponentId}'>";
                                    echo "<input type='hidden' name='sub_component_name' value='{$subComponentName}'>";
                                    echo "<input type='hidden' name='sub_component_id' value='{$subComponentId}'>";
                                    echo "<input type='hidden' name='display_result' value='{$displayResult}'>";
                                    echo "<input type='hidden' name='payment_status' value='" . htmlspecialchars($row['payment_status'] ?? 'Withheld') . "'>";
                                    echo "<input type='hidden' name='description_for_std' value='" . htmlspecialchars($row['description_for_std']) . "'>";
                                    echo "<input type='hidden' name='comment' value='" . htmlspecialchars($row['comment']) . "'>";
                                    echo "<input type='hidden' name='details' value='" . htmlspecialchars(json_encode($row)) . "'>";
                                    echo "<button type='submit' name='send_result_email' class='btn btn-primary mt-3'>Send Component Result</button>";
                                    echo "</form>";
                                } else {
                                    echo "<p class='text-danger'>No data found for the given criteria.</p>";
                                }

                                // --------------------------------------------------- 
                            }
                            ?>
                        <?php else: ?>
                            <?php
                            // Fetch the final result details from the database if available
                            if (!empty($finalResultId)) {
                                $query = "
                                SELECT fsr.*, 
                                    CONCAT(s.first_name, ' ', s.last_name) AS full_name, 
                                    ap.student_registration_id as stdid,
                                    s.*, 
                                    pt.program_name, 
                                    bt.batch_name, 
                                    m.*,
                                    yt.year_name,
                                    ap.*,
                                    pw.*,
                                    st.semester_name,
                                    sr.comment,
                                    sr.description_for_std,
                                    (SELECT status FROM email_sending_log 
                                     WHERE student_id = s.student_code 
                                     AND module_id = fsr.module_id 
                                     ORDER BY sent_date DESC LIMIT 1) as email_status,
                                    (SELECT sent_date FROM email_sending_log 
                                     WHERE student_id = s.student_code 
                                     AND module_id = fsr.module_id 
                                     ORDER BY sent_date DESC LIMIT 1) as email_sent_date
                                FROM final_student_results fsr
                                INNER JOIN students s ON fsr.student_id = s.student_code
                                INNER JOIN allocate_programme ap ON s.student_code = ap.student_code
                                INNER JOIN program_table pt ON ap.programme_code = pt.program_code
                                INNER JOIN batch_table bt ON ap.batch_id = bt.id
                                INNER JOIN modules m ON fsr.module_id = m.id
                                LEFT JOIN year_table yt ON m.year_id = yt.id
                                LEFT JOIN semester_table st ON m.semester_id = st.id
                                LEFT JOIN payment_withheld_table pw ON s.student_code = pw.student_code
                                LEFT JOIN student_results sr ON sr.student_id = fsr.student_id 
                                    AND sr.module_id = fsr.module_id
                                WHERE fsr.id = ? AND ap.status = 'active'";

                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("i", $finalResultId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($row = $result->fetch_assoc()) {
                                    $email = $row['bms_email'];
                                    $studentRegId = !empty($row['stdid']) ? $row['stdid'] : 'Not Available';
                                    $studentCode = $row['student_code'];
                                    $fullName = $row['full_name'];
                                    $batchName = $row['batch_name'];
                                    $moduleName = $row['module_name'];
                                    $yearName = $row['year_name'];
                                    $semesterName = $row['semester_name'];
                                    $finalResult = $row['final_result'];
                                    $emailStatus = $row['email_status'];
                                    $emailSentDate = $row['email_sent_date'];
                                    $payment_status = $row['payment_status'];
                            ?>
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Student Details (module single row sending)</h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-striped table-hover">
                                                <tbody>
                                                    <tr>
                                                        <th>Name</th>
                                                        <td><?php echo $fullName; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Email</th>
                                                        <td><?php echo $email; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Student ID</th>
                                                        <td><?php echo $studentRegId; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Program</th>
                                                        <td><?php echo htmlspecialchars($programName); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Batch</th>
                                                        <td><?php echo $batchName; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Module</th>
                                                        <td><?php echo $moduleName; ?></td>
                                                    </tr>
                                                    <?php if (!empty($yearName)): ?>
                                                        <tr>
                                                            <th>Year</th>
                                                            <td><?php echo $yearName; ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php if (!empty($semesterName)): ?>
                                                        <tr>
                                                            <th>Semester</th>
                                                            <td><?php echo $semesterName; ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr>
                                                        <th>Final Result</th>
                                                        <!--<td><?php echo $finalResult; ?></td>-->
                                                        <td>
                                                            <?php
                                                            if ($finalResult < 0) {
                                                                echo "Not Submitted";
                                                            } elseif ($finalResult == 0 || $finalResult === "0") {
                                                                echo "Absent";
                                                            } else {
                                                                echo $finalResult;
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>payment status</th>
                                                        <td><?php echo empty($payment_status) ? 'Withheld' : $payment_status; ?></td>
                                                    </tr>

                                                    <!-- ------------ 30.06 2025 ------------ -->

                                                    <tr>
                                                        <th>payment status manually change</th>
                                                        <td>
                                                            <!-- <input type="checkbox" name="payment_status" value="paid"> -->
                                                            <!-- <input type="checkbox" name="payment_status" value="paid" class="payment-status-checkbox" data-student-id="<?= htmlspecialchars($row['student_code']) ?>" <?= ($row['payment_status'] == 'active') ? 'checked' : '' ?>> -->

                                                            <input type="checkbox" name="payment_status" value="paid" class="payment-status-checkbox" data-student-id="<?= htmlspecialchars($row['student_code']) ?>" data-program-id="<?= htmlspecialchars($row['program_id']) ?>" <?= ($row['payment_status'] == 'active') ? 'checked' : '' ?>>
                                                        </td>
                                                    </tr>




                                                    <script>
                                                        $(document).ready(function() {
                                                            $('.payment-status-checkbox').change(function() {
                                                                var studentId = $(this).data('student-id');
                                                                var programId = $(this).data('program-id');
                                                                var status = $(this).is(':checked') ? 'active' : 'withheld';

                                                                $.ajax({
                                                                    url: 'update_payment_status.php', // Create this file to handle the update
                                                                    type: 'POST',
                                                                    data: {
                                                                        student_id: studentId,
                                                                        program_id: programId,
                                                                        payment_status: status
                                                                    },
                                                                    success: function(response) {
                                                                        window.location.reload();
                                                                    },
                                                                    error: function(xhr, status, error) {
                                                                        console.error('Error updating payment status:', error);
                                                                    }
                                                                });
                                                            });
                                                        });
                                                    </script>

                                                    <!-- -----------------------------------------------  -->

                                                    <?php
                                                    // If the program is "Executive Certificate in Management"
                                                    if (
                                                        $programName == 'Executive Certificate in Management'
                                                        // $programName == 'BSc (Hons) in Software Engineering'
                                                    ) {
                                                        // $grade = is_string($finalResult) ? $finalResult : ($finalResult >= 70 ? 'Distinction' : ($finalResult >= 60 ? 'Merit' : ($finalResult >= 50 ? 'Pass' : 'Resit')));
                                                        // $grade = ($finalResult >= 70) ? 'Distinction' : (($finalResult >= 60) ? 'Merit' : (($finalResult >= 50) ? 'Pass' : 'Resit'));

                                                        // If $finalResult contains any text letter without a number, use it as the grade
                                                        if (preg_match('/[a-zA-Z]/', $finalResult) && !preg_match('/\d/', $finalResult)) {
                                                            $grade = $finalResult;
                                                        } else {
                                                            $grade = ($finalResult >= 70) ? 'Distinction' : (($finalResult >= 60) ? 'Merit' : (($finalResult >= 50) ? 'Pass' : 'Resit'));
                                                        }


                                                    ?>
                                                        <tr>
                                                            <th>Grades</th>
                                                            <td><?php echo $grade; ?></td>
                                                        </tr>
                                                    <?php
                                                    } else  if (

                                                        $programName == 'Graduate Diploma in Management (Level 6)'
                                                    ) {
                                                    } else if (
                                                        $programName == 'International Foundation Diploma (Business) - ATHE Level 3' ||
                                                        $programName == 'International Foundation Diploma (Applied Science) - ATHE Level 3' ||
                                                        $programName == 'BTEC Higher National Diploma in Business'
                                                    ) {
                                                    } else if (
                                                        $programName == 'Higher Diploma in Biomedical Science' ||
                                                        $programName == 'BSc (Hons) in Software Engineering' ||
                                                        $programName == 'Higher Diploma in Biotechnology' ||
                                                        $programName == 'Higher Diploma in Food Science and Nutrition' ||
                                                        $programName == 'Higher Diploma in Medical Biotechnology'
                                                    ) {
                                                        // bcz here wanna show only the final result

                                                        // ------------------- 25.06 -------------
                                                        $componentQuery = "
                                                            SELECT 
                                                                ac.id AS main_component_id,
                                                                ac.as_main_component_name,
                                                                ac.main_component_percent
                                                            FROM allocated_components alloc
                                                            LEFT JOIN assignment_components ac ON alloc.main_component_id = ac.id
                                                            WHERE alloc.module_id = '$moduleId'
                                                            ORDER BY ac.id
                                                        ";

                                                        $componentResult = $conn->query($componentQuery);
                                                        $componentCount = $componentResult->num_rows; // Correct count

                                                        if ($componentResult && $componentResult->num_rows > 0) {
                                                            echo "<tr>";
                                                            echo "<th>Component Breakdown</th>";
                                                            echo "<td>";
                                                            while ($row = $componentResult->fetch_assoc()) {
                                                                echo htmlspecialchars($row['as_main_component_name']) . " - " . htmlspecialchars($row['main_component_percent']) . "<br>";
                                                            }
                                                            echo "</td>";
                                                            echo "</tr>";
                                                        }

                                                    ?>


                                                        <?php
                                                        $finalresult = (int)$finalResult;

                                                        // function getHDGradeFromMarks($finalresult)
                                                        // {
                                                        //     if ($finalresult < 0 || $finalresult > 100) {
                                                        //         return 'Unknown'; // Handle out-of-range values
                                                        //     }

                                                        //     if ($finalresult <= 29) return 'Re-sit';   // 0-29 
                                                        //     elseif ($finalresult <= 49) return 'Pending'; // 30-49
                                                        //     elseif ($finalresult <= 59) return 'Pass'; // 50-59
                                                        //     elseif ($finalresult <= 69) return 'Merit'; // 60-69
                                                        //     elseif ($finalresult <= 100) return 'Distinction'; // 70-100
                                                        //     return 'Unknown';
                                                        // }


                                                        // Hasni Bro Added 2.6 Rules 
                                                        // --------------------------
                                                        // function getHDGradeFromMarks($marks)
                                                        //     {
                                                        //         if ($marks < 0 || $marks > 100) {
                                                        //             return 'Unknown';
                                                        //         }

                                                        //         // ===== APPLY 2.6 RULE =====
                                                        //         if ($marks == 49) {
                                                        //             $marks = 50;
                                                        //         } elseif ($marks == 59) {
                                                        //             $marks = 60;
                                                        //         } elseif ($marks == 69) {
                                                        //             $marks = 70;
                                                        //         }

                                                        //         // ===== GRADE LOGIC =====
                                                        //         if ($marks <= 29) return 'Re-sit';
                                                        //         elseif ($marks <= 49) return 'Pending';
                                                        //         elseif ($marks <= 59) return 'Pass';
                                                        //         elseif ($marks <= 69) return 'Merit';
                                                        //         else return 'Distinction';
                                                        //     }


                                                        // me add on HD Not Submitted inlcude and Absent also 27.02.2026 

                                                        function getHDGradeFromMarks($marks)
                                                        {
                                                            if ($marks > 100) {
                                                                return 'Unknown';
                                                            }

                                                            // ===== APPLY 2.6 RULE =====
                                                            if ($marks == 49) {
                                                                $marks = 50;
                                                            } elseif ($marks == 59) {
                                                                $marks = 60;
                                                            } elseif ($marks == 69) {
                                                                $marks = 70;
                                                            }

                                                            // ===== GRADE LOGIC =====
                                                            if ($marks < 0) {
                                                                return 'Not Submitted';
                                                            } elseif ($marks == 0) {
                                                                return 'Absent';
                                                            } elseif ($marks >= 1 && $marks <= 29) {
                                                                return 'Re-sit';
                                                            } elseif ($marks >= 30 && $marks <= 49) {
                                                                return 'Pending';
                                                            } elseif ($marks >= 50 && $marks <= 59) {
                                                                return 'Pass';
                                                            } elseif ($marks >= 60 && $marks <= 69) {
                                                                return 'Merit';
                                                            } elseif ($marks >= 70 && $marks <= 100) {
                                                                return 'Distinction';
                                                            } else {
                                                                return 'Unknown';
                                                            }
                                                        }



                                                        $studentId = $_GET['student_id'];
                                                        $componentsQuery = "
                                                                    SELECT ac.as_main_component_name, sc.sub_component_name, sr.result, 
                                                                        sr.hd_converted_marks, sr.hd_resit3_converted_marks, 
                                                                        sr.hd_resit2_converted_marks, sr.hd_resit1_converted_marks,
                                                                        sr.hd_resit3_grade, sr.hd_resit2_grade, sr.hd_resit1_grade, sr.hd_grade
                                                                    FROM student_results sr
                                                                    LEFT JOIN assignment_components ac ON sr.main_component_id = ac.id
                                                                    LEFT JOIN sub_assign_components sc ON sr.sub_component_id = sc.id
                                                                    WHERE sr.student_id = '$studentId' AND sr.module_id = '$moduleId'
                                                                ";

                                                        $componentsResult = $conn->query($componentsQuery);
                                                        $componentGrades = [];
                                                        $componentMarks = [];

                                                        if ($componentsResult && $componentsResult->num_rows > 0) {
                                                            while ($compRow = $componentsResult->fetch_assoc()) {
                                                                $convertedMarks = $compRow['hd_resit3_converted_marks'] ??
                                                                    $compRow['hd_resit2_converted_marks'] ??
                                                                    $compRow['hd_resit1_converted_marks'] ??
                                                                    $compRow['hd_converted_marks'];

                                                                $compGrade = $compRow['hd_resit3_grade'] ??
                                                                    $compRow['hd_resit2_grade'] ??
                                                                    $compRow['hd_resit1_grade'] ??
                                                                    $compRow['hd_grade'];

                                                                if ($compGrade) $componentGrades[] = $compGrade;
                                                                if ($convertedMarks !== null) $componentMarks[] = floatval($convertedMarks);
                                                            }
                                                        }

                                                        $finalHDGrade = 'N/A';

                                                        // OLD CODE WITHOUT NOT SUBMITTED FOR HD 
                                                        // -----------------------------------------------------
                                                        // if ($componentCount == 2) {
                                                        //     if (count($componentGrades) >= 2) {
                                                        //         $g1 = strtolower($componentGrades[0]);
                                                        //         $g2 = strtolower($componentGrades[1]);
                                                        //         if ($g1 === 're-sit' && $g2 === 're-sit') {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif ($g1 === 'pending' && $g2 === 'pending') {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif (
                                                        //             ($g1 === 're-sit' && $g2 === 'pending') ||
                                                        //             ($g2 === 're-sit' && $g1 === 'pending')
                                                        //         ) {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif (
                                                        //             ($g1 === 're-sit' && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                        //             ($g2 === 're-sit' && in_array($g1, ['pass', 'merit', 'distinction']))
                                                        //         ) {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         } elseif (
                                                        //             ($g1 === 'pending' && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                        //             ($g2 === 'pending' && in_array($g1, ['pass', 'merit', 'distinction']))
                                                        //         ) {
                                                        //             $finalHDGrade = getHDGradeFromMarks($finalResult);
                                                        //         } elseif (
                                                        //             in_array($g1, ['pass', 'merit', 'distinction']) &&
                                                        //             in_array($g2, ['pass', 'merit', 'distinction'])
                                                        //         ) {
                                                        //             $sumMarks = ($componentMarks[0] ?? 0) + ($componentMarks[1] ?? 0);
                                                        //             $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                        //         } elseif (
                                                        //             ($g1 === 'absent' && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                        //             ($g2 === 'absent' && in_array($g1, ['pass', 'merit', 'distinction']))
                                                        //         ) {
                                                        //             $finalHDGrade = 'pending';
                                                        //         } elseif (
                                                        //             $g1 === 'absent' && $g2 === 'absent'
                                                        //         ) {
                                                        //             $finalHDGrade = 'absent';
                                                        //         } else {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         }
                                                        //     } elseif (isset($componentGrades[0])) {
                                                        //         $g = strtolower($componentGrades[0]);
                                                        //         $finalHDGrade = $g === 'pending' ? getHDGradeFromMarks($finalResult) : $componentGrades[0];
                                                        //     }
                                                        // } elseif ($componentCount == 3) {
                                                        //     if (count($componentGrades) >= 3) {
                                                        //         $g1 = strtolower($componentGrades[0]);
                                                        //         $g2 = strtolower($componentGrades[1]);
                                                        //         $g3 = strtolower($componentGrades[2]);
                                                        //         $resitCount = 0;
                                                        //         $pendingCount = 0;
                                                        //         $absentCount = 0;
                                                        //         $validGrades = 0;
                                                        //         $sumMarks = 0;
                                                        //         for ($i = 0; $i < 3; $i++) {
                                                        //             $g = strtolower($componentGrades[$i]);
                                                        //             $m = floatval($componentMarks[$i] ?? 0);
                                                        //             if (in_array($g, ['pass', 'merit', 'distinction'])) {
                                                        //                 $validGrades++;
                                                        //                 $sumMarks += $m;
                                                        //             } elseif ($g === 're-sit') {
                                                        //                 $resitCount++;
                                                        //                 $sumMarks += $m;
                                                        //             } elseif ($g === 'pending') {
                                                        //                 $pendingCount++;
                                                        //                 $sumMarks += $m;
                                                        //             } elseif ($g === 'absent') {
                                                        //                 $absentCount++;
                                                        //             }
                                                        //         }
                                                        //         if ($resitCount === 3) {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif ($pendingCount === 3) {
                                                        //             $finalHDGrade = ($sumMarks < 50) ? 'Re-sit' : getHDGradeFromMarks($sumMarks);
                                                        //         } elseif ($absentCount === 3) {
                                                        //             $finalHDGrade = 'Absent';
                                                        //         } elseif ($resitCount === 2 && $pendingCount === 1) {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif ($resitCount === 2 && $validGrades === 1) {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         } elseif ($pendingCount === 2 && $validGrades === 1) {
                                                        //             $finalHDGrade = ($sumMarks < 50) ? 'Pending' : getHDGradeFromMarks($sumMarks);
                                                        //         } elseif ($resitCount === 1 && $pendingCount === 1 && $validGrades === 1) {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         } elseif ($resitCount === 1 && $validGrades === 2) {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         } elseif ($pendingCount === 1 && $validGrades === 2) {
                                                        //             $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                        //         } elseif ($validGrades === 3) {
                                                        //             $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                        //         } elseif ($absentCount === 2 && $pendingCount === 1) {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif ($absentCount === 2 && $resitCount === 1) {
                                                        //             $finalHDGrade = 'Re-sit';
                                                        //         } elseif ($pendingCount === 2 && $resitCount === 1) {
                                                        //             $finalHDGrade = ($sumMarks < 50) ? 'Re-sit' : getHDGradeFromMarks($sumMarks);
                                                        //         } else {
                                                        //             $finalHDGrade = 'Pending';
                                                        //         }
                                                        //     } elseif (isset($componentGrades[0])) {
                                                        //         $g = strtolower($componentGrades[0]);
                                                        //         $finalHDGrade = $g === 'pending' ? getHDGradeFromMarks($finalResult) : $componentGrades[0];
                                                        //     }
                                                        // } else {
                                                        //     echo "Error: No grades found for component";
                                                        // }

                                                        // -----------------------------------------
                                                        // ME ADD ON 27.02.2026  WITH NOTSUBMITTED CALCULATION ALSO 
                                                        // -----------------------------------------------------------


                                                        // Helper functions for clarity
                                                        function isAbsentHD($g)
                                                        {
                                                            return strtolower($g) === 'absent';
                                                        }
                                                        function isNotSubmittedHD($g)
                                                        {
                                                            return strtolower($g) === 'not submitted';
                                                        }

                                                        if ($componentCount == 2) {
                                                            if (count($componentGrades) >= 2) {
                                                                $g1 = strtolower($componentGrades[0]);
                                                                $g2 = strtolower($componentGrades[1]);
                                                                // --- Standard Rules ---
                                                                if ($g1 === 're-sit' && $g2 === 're-sit') {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif ($g1 === 'pending' && $g2 === 'pending') {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif (
                                                                    ($g1 === 're-sit' && $g2 === 'pending') ||
                                                                    ($g2 === 're-sit' && $g1 === 'pending')
                                                                ) {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif (
                                                                    ($g1 === 're-sit' && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                                    ($g2 === 're-sit' && in_array($g1, ['pass', 'merit', 'distinction']))
                                                                ) {
                                                                    $finalHDGrade = 'Pending';
                                                                } elseif (
                                                                    ($g1 === 'pending' && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                                    ($g2 === 'pending' && in_array($g1, ['pass', 'merit', 'distinction']))
                                                                ) {
                                                                    $finalHDGrade = getHDGradeFromMarks($finalResult);
                                                                } elseif (
                                                                    in_array($g1, ['pass', 'merit', 'distinction']) &&
                                                                    in_array($g2, ['pass', 'merit', 'distinction'])
                                                                ) {
                                                                    $sumMarks = ($componentMarks[0] ?? 0) + ($componentMarks[1] ?? 0);
                                                                    $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                                }
                                                                // --- Absent logic: both absent, or single absent + grade
                                                                elseif (
                                                                    (isAbsentHD($g1) && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                                    (isAbsentHD($g2) && in_array($g1, ['pass', 'merit', 'distinction']))
                                                                ) {
                                                                    $finalHDGrade = 'pending';
                                                                } elseif (
                                                                    isAbsentHD($g1) && isAbsentHD($g2)
                                                                ) {
                                                                    $finalHDGrade = 'Absent';
                                                                }
                                                                // --- Not Submitted logic: both not submitted, or single not submitted + grade
                                                                elseif (
                                                                    (isNotSubmittedHD($g1) && in_array($g2, ['pass', 'merit', 'distinction'])) ||
                                                                    (isNotSubmittedHD($g2) && in_array($g1, ['pass', 'merit', 'distinction']))
                                                                ) {
                                                                    $finalHDGrade = 'pending';
                                                                } elseif (
                                                                    isNotSubmittedHD($g1) && isNotSubmittedHD($g2)
                                                                ) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // --- Mixed Absent and Not Submitted
                                                                elseif (
                                                                    (isAbsentHD($g1) && isNotSubmittedHD($g2)) ||
                                                                    (isAbsentHD($g2) && isNotSubmittedHD($g1))
                                                                ) {
                                                                    // If one is absent, one is not submitted, treat as "Not Submitted"
                                                                    $finalHDGrade = 'Not Submitted';
                                                                } else {
                                                                    $finalHDGrade = 'Pending';
                                                                }
                                                            } elseif (isset($componentGrades[0])) {
                                                                $g = strtolower($componentGrades[0]);
                                                                if ($g === 'pending') {
                                                                    $finalHDGrade = getHDGradeFromMarks($finalResult);
                                                                } elseif (isAbsentHD($g)) {
                                                                    $finalHDGrade = 'Absent';
                                                                } elseif (isNotSubmittedHD($g)) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                } else {
                                                                    $finalHDGrade = $componentGrades[0];
                                                                }
                                                            }
                                                        } elseif ($componentCount == 3) {
                                                            if (count($componentGrades) >= 3) {
                                                                $resitCount = 0;
                                                                $pendingCount = 0;
                                                                $absentCount = 0;
                                                                $notSubmittedCount = 0;
                                                                $validGrades = 0;
                                                                $sumMarks = 0;
                                                                for ($i = 0; $i < 3; $i++) {
                                                                    $g = strtolower($componentGrades[$i]);
                                                                    $m = floatval($componentMarks[$i] ?? 0);
                                                                    if (in_array($g, ['pass', 'merit', 'distinction'])) {
                                                                        $validGrades++;
                                                                        $sumMarks += $m;
                                                                    } elseif ($g === 're-sit') {
                                                                        $resitCount++;
                                                                        $sumMarks += $m;
                                                                    } elseif ($g === 'pending') {
                                                                        $pendingCount++;
                                                                        $sumMarks += $m;
                                                                    } elseif (isAbsentHD($g)) {
                                                                        $absentCount++;
                                                                    } elseif (isNotSubmittedHD($g)) {
                                                                        $notSubmittedCount++;
                                                                    }
                                                                }
                                                                // Resit/Pending primary logic unchanged
                                                                if ($resitCount === 3) {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif ($pendingCount === 3) {
                                                                    $finalHDGrade = ($sumMarks < 50) ? 'Re-sit' : getHDGradeFromMarks($sumMarks);
                                                                }
                                                                // Absent logic (ALL absent)
                                                                elseif ($absentCount === 3) {
                                                                    $finalHDGrade = 'Absent';
                                                                }
                                                                // Not Submitted logic (ALL not submitted)
                                                                elseif ($notSubmittedCount === 3) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // 2 absent + 1 not submitted
                                                                elseif ($absentCount === 2 && $notSubmittedCount === 1) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // 2 not submitted + 1 absent
                                                                elseif ($notSubmittedCount === 2 && $absentCount === 1) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // 2 not submitted + 1 valid/resit/pending
                                                                elseif ($notSubmittedCount === 2 && ($validGrades === 1 || $pendingCount === 1 || $resitCount === 1)) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // 1 not submitted + 2 valid/etc
                                                                elseif ($notSubmittedCount === 1 && ($validGrades + $resitCount + $pendingCount + $absentCount) === 2) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                }
                                                                // Other (classic structure)
                                                                elseif ($resitCount === 2 && $pendingCount === 1) {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif ($resitCount === 2 && $validGrades === 1) {
                                                                    $finalHDGrade = 'Pending';
                                                                } elseif ($pendingCount === 2 && $validGrades === 1) {
                                                                    $finalHDGrade = ($sumMarks < 50) ? 'Pending' : getHDGradeFromMarks($sumMarks);
                                                                } elseif ($resitCount === 1 && $pendingCount === 1 && $validGrades === 1) {
                                                                    $finalHDGrade = 'Pending';
                                                                } elseif ($resitCount === 1 && $validGrades === 2) {
                                                                    $finalHDGrade = 'Pending';
                                                                } elseif ($pendingCount === 1 && $validGrades === 2) {
                                                                    $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                                } elseif ($validGrades === 3) {
                                                                    $finalHDGrade = getHDGradeFromMarks($sumMarks);
                                                                } elseif ($absentCount === 2 && $pendingCount === 1) {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif ($absentCount === 2 && $resitCount === 1) {
                                                                    $finalHDGrade = 'Re-sit';
                                                                } elseif ($pendingCount === 2 && $resitCount === 1) {
                                                                    $finalHDGrade = ($sumMarks < 50) ? 'Re-sit' : getHDGradeFromMarks($sumMarks);
                                                                } else {
                                                                    $finalHDGrade = 'Pending';
                                                                }
                                                            } elseif (isset($componentGrades[0])) {
                                                                $g = strtolower($componentGrades[0]);
                                                                if ($g === 'pending') {
                                                                    $finalHDGrade = getHDGradeFromMarks($finalResult);
                                                                } elseif (isAbsentHD($g)) {
                                                                    $finalHDGrade = 'Absent';
                                                                } elseif (isNotSubmittedHD($g)) {
                                                                    $finalHDGrade = 'Not Submitted';
                                                                } else {
                                                                    $finalHDGrade = $componentGrades[0];
                                                                }
                                                            }
                                                        } else {
                                                            echo "Error: No grades found for component";
                                                        }


                                                        ?>
                                                        <tr>
                                                            <th>
                                                                <p>Total Main Components: </p>
                                                            </th>
                                                            <td><?= $componentCount; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>HD Status</th>
                                                            <td>
                                                                <?= $finalHDGrade; ?>
                                                            </td>
                                                        </tr>
                                                        <!-- // --------------------------------  -->
                                                    <?php
                                                    } else {
                                                        // Other programs ( , , )
                                                        $status = (strpos(strtolower($finalResult), 'merit') !== false ||
                                                            strpos(strtolower($finalResult), 'pass') !== false ||
                                                            strpos(strtolower($finalResult), 'distinction') !== false) ? 'Completed' : 'Not Completed';
                                                    ?>
                                                        <tr>
                                                            <th>Status</th>
                                                            <td><?php echo $status; ?></td>
                                                        </tr>
                                                    <?php } ?>

                                                    <!-- -------------------------------------------------------  -->

                                                    <!-- Email Status Row -->
                                                    <tr>
                                                        <th>Email Status</th>
                                                        <td>
                                                            <?php
                                                            if (!empty($emailStatus)) {
                                                                $statusClass = ($emailStatus == 'sent') ? 'text-success' : 'text-danger';
                                                                $statusText = ($emailStatus == 'sent') ? 'Sent' : 'Failed';
                                                                $sentDate = !empty($emailSentDate) ? (new DateTime($emailSentDate))->format('Y-m-d H:i') : 'N/A';
                                                                echo "<span class='{$statusClass}'>{$statusText}</span> on {$sentDate}";
                                                            } else {
                                                                echo "<span class='text-warning'>Not Sent</span>";
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Email form -->
                                            <form method="post">
                                                <input type="hidden" name="email" value="<?php echo $email; ?>">
                                                <input type="hidden" name="student_code" value="<?php echo $studentCode; ?>">
                                                <input type="hidden" name="program_id" value="<?php echo $programId; ?>">
                                                <input type="hidden" name="batch_id" value="<?php echo $batchId; ?>">
                                                <input type="hidden" name="module_id" value="<?php echo $moduleId; ?>">
                                                <input type="hidden" name="details" value="<?php echo htmlspecialchars(json_encode($row)); ?>">
                                                <button type="submit" name="send_email" class="btn btn-primary mt-3">Send Email</button>
                                            </form>
                                        </div>
                                    </div>
                            <?php
                                } else {
                                    echo "<p class='text-danger'>No data found for the given criteria.</p>";
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php




// Module -> single Row ->
if (isset($_POST['send_email'])) {
    $email = $_POST['email'];
    $studentCode = $_POST['student_code'];
    $programId = $_POST['program_id'];
    $batchId = $_POST['batch_id'];
    $moduleId = $_POST['module_id'];
    $details = json_decode($_POST['details'], true);

    // Get the correct student ID - Fix the variable name and access
    // $studentRegId = !empty($details['stdid']) ? $details['stdid'] : 'Not Available';

    // Final Result logic for Module -> single Row
    $finalResult = $details['final_result'] ?? '';
    // $payment_status = $details['payment_status'] ?? '';
    $payment_status = $payment_status;
    // If payment status is "withheld", override the final result
    if (strtolower($payment_status) === 'withheld' || empty($payment_status)) {
        $finalResult = 'Withheld';
    }


    $emailedResult = $finalResult; // Will store in DB exactly what is shown


    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'noreply@bms.ac.lk';
        $mail->Password = 'gqfxxrphvjnlmwrn';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        //Recipients
        $mail->setFrom('noreply@bms.ac.lk', 'Business Management School'); // Replace with your email and name

        $mail->addAddress($email);
        $mail->isHTML(true);
        // $mail->Subject = $details['program_name'] . ' || ' . $details['module_name'] . ' results';
        $mail->Subject = $programName . ' || ' . $moduleName . ' results';


        // ------------------------------------- 12.06.----------------------------------------- 

        // ---------------- EMBED LOGO (IMPORTANT) ----------------
        // $mail->addEmbeddedImage(
        //     $_SERVER['DOCUMENT_ROOT'] . '/assets/BMS-Logo.png',
        //     'bmslogo'
        // );

        $mail->addEmbeddedImage(
            __DIR__ . '/assets/bmspnglogo.png',
            'bmslogo'
        );

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 700px; margin: auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1);'>
                <div style=' padding: 20px; text-align: center;'>
                    <img src='cid:bmslogo' alt='BMS Logo' width='200' style='display:block;margin:auto;'>
                    <h2  margin-top: 10px;'>Business Management School</h2>
                      <p style='font-size: 14px; color: #666;'>Result Notification</p>
                </div>

                <div style='padding: 20px;'>
        <h3 style='color: #004080;'>Student Result Notification</h3>

        <table style='width: 100%; border-collapse: collapse; font-size: 14px;' border='1' cellpadding='8' cellspacing='0'>
            
            <tr style='background-color: #f9f9f9;'><th align='left'>Full Name</th><td>" . $fullName . "</td></tr>
            <tr><th align='left'>Student ID</th><td>" . $studentRegId . "</td></tr>
            <tr style='background-color: #f9f9f9;'><th align='left'>BMS Email</th><td>" . $email . "</td></tr>
            <tr><th align='left'>Program</th><td>" . $programName . "</td></tr>
            <tr style='background-color: #f9f9f9;'><th align='left'>Batch</th><td>" .  $batchName . "</td></tr>
            <tr><th align='left'>Module</th><td>" . $moduleName . "</td></tr>";

        if (!empty($details['component_name'])) {
            $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component</th><td>" . $details['component_name'] . "</td></tr>";
        }

        if (!empty($details['sub_component_name'])) {
            $mail->Body .= "<tr><th align='left'>Sub Component</th><td>" . $details['sub_component_name'] . "</td></tr>";
        }

        $mail->Body .= "
            <tr style='background-color: #f9f9f9;'><th align='left'>Year</th><td>" . $yearName . "</td></tr>
            <tr><th align='left'>Semester</th><td>" . $semesterName . "</td></tr>
            ";

        // Final Result Display Logic (unchanged)
        if (
            $programName == 'Executive Certificate in Management'
            //$programName == 'BSc (Hons) in Software Engineering'
        ) {


            if (strtolower($payment_status) === 'withheld' || empty($payment_status)) {
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Withheld</td></tr>";
                $mail->Body .= "<tr><th>Note</th><td>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
            } else {
                if (is_numeric($finalResult)) {
                    $grade = ($finalResult >= 70) ? 'Distinction' : (($finalResult >= 60) ? 'Merit' : (($finalResult >= 50) ? 'Pass' : 'Resit'));
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result</th><td>" . $grade . "</td></tr>";
                    $emailedResult = $grade; // Will store in DB exactly what is shown
                } else {
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result</th><td>" . htmlspecialchars($finalResult) . "</td></tr>";
                    $emailedResult = $finalResult; // Will store in DB exactly what is shown
                }
            }
        } else if (
            $programName == 'Graduate Diploma in Management (Level 6)'
        ) {
            if (strtolower($payment_status) === 'withheld' || empty($payment_status)) {
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Withheld</td></tr>";
                $mail->Body .= "<tr><th>Note</th><td>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
            } else {

                // $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left' style='font-size: 16px; font-weight: bold;'>Final Result</th><td style='font-size: 16px; font-weight: bold;'>" . htmlspecialchars($finalResult) . "</td></tr>";
                //   $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left' style='font-size: 16px; font-weight: bold;'>Final Result</th><td style='font-size: 16px; font-weight: bold;'>" . ucfirst(htmlspecialchars($finalResult)) . "</td></tr>";
                //     $emailedResult = $finalResult; // Will store in DB exactly what is shown

                //     $breakdownHtml = '';
                //     $componentQuery = "
                //         SELECT 
                //             ac.as_main_component_name,
                //             ac.main_component_percent
                //         FROM allocated_components alloc
                //         LEFT JOIN assignment_components ac ON alloc.main_component_id = ac.id
                //         WHERE alloc.module_id = '$moduleId'
                //         ORDER BY ac.id
                //     ";
                //     $componentResult = $conn->query($componentQuery);
                //     if ($componentResult && $componentResult->num_rows > 0) {
                //         while ($row = $componentResult->fetch_assoc()) {
                //             $breakdownHtml .= htmlspecialchars($row['as_main_component_name']) . " - " . htmlspecialchars($row['main_component_percent']) . "<br>";
                //         }
                //         // $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component Breakdown</th><td>" . $breakdownHtml . "</td></tr>";
                //     }

                //     $resultsHtml = '';
                //     $componentsQuery = "
                //         SELECT ac.as_main_component_name, sc.sub_component_name, sr.result,
                //             sr.resit_result_4, sr.resit_result_3, sr.resit_result_2, sr.resit_result_1
                //         FROM student_results sr
                //         LEFT JOIN assignment_components ac ON sr.main_component_id = ac.id
                //         LEFT JOIN sub_assign_components sc ON sr.sub_component_id = sc.id
                //         WHERE sr.student_id = '$studentId' AND sr.module_id = '$moduleId'
                //     ";
                //     $componentsResult = $conn->query($componentsQuery);
                //     // if ($componentsResult && $componentsResult->num_rows > 0) {
                //     //     while ($r = $componentsResult->fetch_assoc()) {
                //     //         $compRes = $r['resit_result_4'] ?: ($r['resit_result_3'] ?: ($r['resit_result_2'] ?: ($r['resit_result_1'] ?: $r['result'])));
                //     //         $main = htmlspecialchars($r['as_main_component_name'] ?? '');
                //     //         $sub = htmlspecialchars($r['sub_component_name'] ?? '');
                //     //         $res = htmlspecialchars($compRes ?? '');
                //     //         $resultsHtml .= $main . " : " . $sub . " - " . $res . "<br>";
                //     //     }
                //     //     $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component Results</th><td>" . $resultsHtml . "</td></tr>";
                //     // }

                //     if ($componentsResult && $componentsResult->num_rows > 0) {
                //         while ($r = $componentsResult->fetch_assoc()) {
                //             $compRes = $r['resit_result_4'] ?: ($r['resit_result_3'] ?: ($r['resit_result_2'] ?: ($r['resit_result_1'] ?: $r['result'])));
                //             // Capitalize first letters
                //             $main = htmlspecialchars(ucfirst(strtolower($r['as_main_component_name'] ?? '')));
                //             $sub = htmlspecialchars(ucfirst(strtolower($r['sub_component_name'] ?? '')));
                //             $res = htmlspecialchars(ucfirst(strtolower($compRes ?? '')));
                //             $resultsHtml .= $main . " : " . $sub . " - " . $res . "<br>";
                //         }
                //         $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component Results</th><td>" . $resultsHtml . "</td></tr>";

                //         // Academic Appeals row (immediately after Component Results)
                //         $mail->Body .= "<tr>
                //             <td colspan='2' style='color: #c0392b; font-size: 14px; padding-top: 10px; background: #fff;'>
                //                 <strong>Academic Appeals:</strong> If any, shall be submitted within 7 days from the date hereof.<br>
                //                 Please email: <a href='mailto:shankar@bms.ac.lk' style='color: #2980b9;'>shankar@bms.ac.lk</a>
                //             </td>
                //         </tr>";
                //     }


                // new update ----------- 19-01-2026 ------
                // here included the breakdown component with comment 
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left' style='font-size: 16px; font-weight: bold;'>Final Result</th><td style='font-size: 16px; font-weight: bold;'>" . ucfirst(htmlspecialchars($finalResult)) . "</td></tr>";

                $emailedResult = $finalResult; // Will store in DB exactly what is shown

                $breakdownHtml = '';
                $componentQuery = "
                    SELECT 
                        ac.as_main_component_name,
                        ac.main_component_percent
                    FROM allocated_components alloc
                    LEFT JOIN assignment_components ac ON alloc.main_component_id = ac.id
                    WHERE alloc.module_id = '$moduleId'
                    ORDER BY ac.id
                ";
                $componentResult = $conn->query($componentQuery);
                if ($componentResult && $componentResult->num_rows > 0) {
                    while ($row = $componentResult->fetch_assoc()) {
                        $breakdownHtml .= htmlspecialchars($row['as_main_component_name']) . " - " . htmlspecialchars($row['main_component_percent']) . "<br>";
                    }
                    // $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component Breakdown</th><td>" . $breakdownHtml . "</td></tr>";
                }

                $resultsHtml = '';
                $componentsQuery = "
                    SELECT ac.as_main_component_name, sc.sub_component_name, sr.result,
                        sr.resit_result_4, sr.resit_result_3, sr.resit_result_2, sr.resit_result_1,
                        sr.comment
                    FROM student_results sr
                    LEFT JOIN assignment_components ac ON sr.main_component_id = ac.id
                    LEFT JOIN sub_assign_components sc ON sr.sub_component_id = sc.id
                    WHERE sr.student_id = '$studentId' AND sr.module_id = '$moduleId'
                ";

                $componentsResult = $conn->query($componentsQuery);
                if ($componentsResult && $componentsResult->num_rows > 0) {
                    while ($r = $componentsResult->fetch_assoc()) {
                        $compRes = $r['resit_result_4'] ?: ($r['resit_result_3'] ?: ($r['resit_result_2'] ?: ($r['resit_result_1'] ?: $r['result'])));
                        // Capitalize first letters
                        $main = htmlspecialchars(ucfirst(strtolower($r['as_main_component_name'] ?? '')));
                        $sub = htmlspecialchars(ucfirst(strtolower($r['sub_component_name'] ?? '')));
                        $res = htmlspecialchars(ucfirst(strtolower($compRes ?? '')));

                        // Add comment in brackets if it exists
                        $commentText = !empty($r['comment']) ? '<br>(' . htmlspecialchars($r['comment']) . ')' : '';

                        // $resultsHtml .= $main . " : " . $sub . " - " . $res . $commentText . "<br>";
                        $resultsHtml .= $main . " : " . $sub . " - " . $res . $commentText . "<br><br>";
                    }
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Component Results</th><td>" . $resultsHtml . "</td></tr>";

                    // Academic Appeals row (immediately after Component Results)
                    $mail->Body .= "<tr>
                        <td colspan='2' style='color: #c0392b; font-size: 14px; padding-top: 10px; background: #fff;'>
                            <strong>Academic Appeals:</strong> If any, shall be submitted within 7 days from the date hereof.<br>
                            Please email: <a href='mailto:shankar@bms.ac.lk' style='color: #2980b9;'>shankar@bms.ac.lk</a>
                        </td>
                    </tr>";
                }
            }
        } else if (
            $programName == 'BTEC Higher National Diploma in Business' ||
            $programName == 'International Foundation Diploma (Business) - ATHE Level 3' ||
            $programName == 'International Foundation Diploma (Applied Science) - ATHE Level 3'
        ) {
            if (strtolower($payment_status) === 'withheld' || empty($payment_status)) {
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Withheld</td></tr>";
                $mail->Body .= "<tr><th>Note</th><td>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
            } else {

                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left' style='font-size: 16px; font-weight: bold;'>Final Result</th><td style='font-size: 16px; font-weight: bold;'>" . htmlspecialchars($finalResult) . "</td></tr>";
                $emailedResult = $finalResult; // Will store in DB exactly what is shown
            }
        } else if (
            $programName == 'Higher Diploma in Biomedical Science' ||
            $programName == 'Higher Diploma in Biotechnology' ||
            $programName == 'Higher Diploma in Food Science and Nutrition' ||
            $programName == 'Higher Diploma in Medical Biotechnology' ||
            $programName == 'BSc (Hons) in Software Engineering'
        ) {
            if (strtolower($payment_status) === 'withheld' || empty($payment_status)) {
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Withheld</td></tr>";
                $mail->Body .= "<tr><th>Note</th><td>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
            } else {

                // $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>" . $finalHDGrade . "</td></tr>";

                // newly added 27.02.2026 not submitted and absnet 
                if ($finalHDGrade === 0 || $finalHDGrade === "0") {
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Absent</td></tr>";
                } elseif ($finalHDGrade < 0) {
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>Not Submitted</td></tr>";
                } else {
                    $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result </th><td>" . htmlspecialchars($finalHDGrade) . "</td></tr>";
                }
                $emailedResult = $finalHDGrade; // Will store in DB exactly what is shown
            }
        } else {
            if (strtolower($finalResult) === 'withheld') {
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result</th><td>Withheld</td></tr>";
                $mail->Body .= "<tr><th>Note</th><td>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
            } else {
                $status = (strpos(strtolower($finalResult), 'merit') !== false ||
                    strpos(strtolower($finalResult), 'pass') !== false ||
                    strpos(strtolower($finalResult), 'distinction') !== false)
                    ? 'Completed' : 'Not Completed';
                $mail->Body .= "<tr style='background-color: #f9f9f9;'><th align='left'>Final Result</th><td>" . $status . "</td></tr>";
                $emailedResult = $status; // Will store in DB exactly what is shown
            }
        }

        $mail->Body .= "
        </table>

        <p style='margin-top: 20px; font-size: 13px; color: #666;'>If you have any questions regarding your results, please contact the BMS administration office.</p>
            </div>

             <div style='font-size: 12px; color: #999; text-align: center; border-top: 1px solid #ccc; padding: 10px 0; margin-top: 20px;'>
            This is an automated email from the BMS Results System. Please do not reply.<br>
            © " . date('Y') . " Business Management School, All rights reserved.
        </div>
        </div>";

        // ------------------------------------------------------------------------------ 

        // Before sending the email
        error_log(print_r($details, true)); // Log the details to check what is being sent

        // Send the email
        $mail->send();

        // Record successful email sending
        // $logQuery = "INSERT INTO email_sending_log 
        //             (student_id, program_id, batch_id, module_id, email_sent, sent_date, sent_by, status) 
        //             VALUES (?, ?, ?, ?, 1, NOW(), ?, 'sent')";
        // $logStmt = $conn->prepare($logQuery);
        // $sentBy = $_SESSION['username'];
        // $logStmt->bind_param("iiiis", $studentCode, $programId, $batchId, $moduleId, $sentBy);
        // $logStmt->execute();

        // Log email in DB
        $logQuery = "INSERT INTO email_sending_log 
                    (student_id, program_id, batch_id, module_id, emailed_result, email_sent, sent_date, sent_by, status)
                    VALUES (?, ?, ?, ?, ?, 1, NOW(), ?, 'sent')";
        $logStmt = $conn->prepare($logQuery);
        $sentBy = $_SESSION['username'];
        $logStmt->bind_param("iiiiss", $studentCode, $programId, $batchId, $moduleId, $emailedResult, $sentBy);
        $logStmt->execute();

        // Show success message using JavaScript alert
        echo "<script type='text/javascript'>alert('Email has been sent successfully!');</script>";
        echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
    } catch (Exception $e) {
        // Record failed email sending
        $errorMsg = $mail->ErrorInfo;


        // $logQuery = "INSERT INTO email_sending_log 
        //             (student_id, program_id, batch_id, module_id, email_sent, sent_date, sent_by, status, error_message) 
        //             VALUES (?, ?, ?, ?, 0, NOW(), ?, 'failed', ?)";
        // $logStmt = $conn->prepare($logQuery);
        // $sentBy = $_SESSION['username'];
        // $logStmt->bind_param("iiiiss", $studentCode, $programId, $batchId, $moduleId, $sentBy, $errorMsg);
        // $logStmt->execute();

        $logQuery = "INSERT INTO email_sending_log 
                    (student_id, program_id, batch_id, module_id, emailed_result, email_sent, sent_date, sent_by, status, error_message)
                    VALUES (?, ?, ?, ?, ?, 0, NOW(), ?, 'failed', ?)";
        $logStmt = $conn->prepare($logQuery);
        $sentBy = $_SESSION['username'];
        $logStmt->bind_param("iiiisss", $studentCode, $programId, $batchId, $moduleId, $emailedResult, $sentBy, $errorMsg);
        $logStmt->execute();

        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}



// Module -> component  -> single  row sending -> 
if (isset($_POST['send_result_email'])) {
    $email = $_POST['email'];
    $studentId = $_POST['student_id'];
    $studentCode = $_POST['student_code'];
    $fullName = $_POST['full_name'];
    $programName = $_POST['program_name'];
    $programId = $_POST['program_id'];
    $batchName = $_POST['batch_name'];
    $batchId = $_POST['batch_id'];
    $moduleName = $_POST['module_name'];
    $moduleId = $_POST['module_id'];
    $displayResult = $_POST['display_result'];
    $paymentStatus = $_POST['payment_status'];
    $description = $_POST['description_for_std'] ?? '';
    $comment = $_POST['comment'] ?? '';

    // Corrected code for paymentStatus
    $status = '';
    // if (empty($paymentStatus) || strtolower($paymentStatus) === 'withheld') {
    //     $status = 'Withheld'; // Set status to 'Withheld' if payment status is withheld
    // }
    if (empty($paymentStatus) || strtolower($paymentStatus) === 'withheld') {
        $status = 'Withheld'; // Set status to 'Withheld' if payment status is withheld

    } else if (
        $programName === 'Higher Diploma in Biomedical Science' ||
        $programName === 'Graduate Diploma in Management (Level 6)' ||
        $programName === 'BSc (Hons) in Software Engineering' ||
        $programName === 'Higher Diploma in Biotechnology' ||
        $programName === 'Higher Diploma in Food Science and Nutrition' ||
        $programName === 'Higher Diploma in Medical Biotechnology'
    ) {
        $status = $displayResult;
    } else {
        // Determine status based on displayResult for other programs
        if (strtolower($displayResult) === 'absent') {
            $status = 'Absent';
        } else if (strtolower($displayResult) === 'not submitted') {
            $status = 'Not Submitted';
        } else if (
            strtolower($displayResult) === 'merit' ||
            strtolower($displayResult) === 'pass' ||
            strtolower($displayResult) === 'distinction'
        ) {
            $status = 'Completed';
        } else {
            $status = 'Not Completed';
        }
    }

    $componentName = $_POST['component_name'];
    $mainComponentId = $_POST['main_component_id'];
    $subComponentName = $_POST['sub_component_name'];
    $subComponentId = $_POST['sub_component_id'];

    $mail = new PHPMailer(true);
    try {

        $mail->isSMTP(); // Set mailer to use SMTP
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = 'noreply@bms.ac.lk'; // SMTP username
        $mail->Password = 'Lox51527'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port = 587; // TCP port to connect to

        //Recipients
        $mail->setFrom('noreply@bms.ac.lk', 'Business Management School'); // Replace with your email and name

        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $programName . ' || ' . $moduleName . ' Results';

        // --------------------------------------------- 12.06 ---------------- 


        // Embed BMS logo (same as module single row email ) module - component - single row image sending
        $mail->addEmbeddedImage(
            // __DIR__ . '/assets/BMS-Logo.png',
            __DIR__ . '/assets/bmspnglogo.png',
            'bmslogo'
        );

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 700px; margin: auto; border: 1px solid #ddd; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <img src='cid:bmslogo' alt='BMS Logo' width='200' style='display:block;margin:auto;'>
                <h2 style='margin-top: 10px; color: #004080;'>Business Management School</h2>
                <p style='font-size: 14px; color: #666;'>Result Notification</p>
            </div>

            <h3 style='border-bottom: 1px solid #ccc; padding-bottom: 10px;'>Student Details</h3>
            <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                <tr><th align='left' style='background-color: #f2f2f2; padding: 8px;'>Full Name</th><td style='padding: 8px;'>$fullName</td></tr>
                <tr><th align='left' style='padding: 8px;'>Student ID</th><td style='padding: 8px;'>$studentId</td></tr>
                <tr><th align='left' style='background-color: #f2f2f2; padding: 8px;'>BMS Email</th><td style='padding: 8px;'>$email</td></tr>
                <tr><th align='left' style='padding: 8px;'>Program</th><td style='padding: 8px;'>$programName</td></tr>
                <tr><th align='left' style='background-color: #f2f2f2; padding: 8px;'>Batch</th><td style='padding: 8px;'>$batchName</td></tr>
                <tr><th align='left' style='padding: 8px;'>Module</th><td style='padding: 8px;'>$moduleName</td></tr>
                <tr><th align='left' style='background-color: #f2f2f2; padding: 8px;'>Main Component</th><td style='padding: 8px;'>$componentName</td></tr>
                <tr><th align='left' style='padding: 8px;'>Sub Component</th><td style='padding: 8px;'>$subComponentName</td></tr>
                <tr><th align='left' style='background-color: #f2f2f2; padding: 8px;'>Result Status</th><td style='padding: 8px;'>$status</td></tr>";
        if (strtolower($status) == 'withheld') {
            $mail->Body .= "<tr><th align='left' style='padding: 8px;'>Note</th><td style='padding: 8px;'>You are kindly Requested to Contact BMS Campus <br> Finance Department <br> 0704001092</td></tr>";
        }
        $mail->Body .= "
                
            </table>";

        // Add comment if it exists
        if (!empty($comment)) {
            $mail->Body .= "<div style='background-color: #f9f9f9; border-left: 4px solid #004080; padding: 15px; margin-bottom: 20px;'>
                            <strong>Comments:</strong><br>" . nl2br(htmlspecialchars($comment)) . "
                        </div>";
        }

        // Add description if it exists
        if (!empty($description)) {
            $mail->Body .= "<div style='background-color: #f9f9f9; border-left: 4px solid #004080; padding: 15px; margin-bottom: 20px;'>
                            <strong>Additional Notes:</strong><br>" . $description . "
                        </div>";
        }

        $mail->Body .= "
        
                <div style='font-size: 12px; color: #999; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 20px;'>
                    This is an automated email from the BMS Results System. Please do not reply.<br>
                    © " . date('Y') . " Business Management School, All rights reserved.
                </div>
            </div>";

        // ----------------------------------------------------------- 

        // Before sending the email
        error_log(print_r($details, true)); // Log the details to check what is being sent

        // Send the email
        $mail->send();

        // Record successful email sending
        // $logQuery = "INSERT INTO email_sending_log 
        //             (student_id, program_id, batch_id, module_id, main_component_id, sub_component_id, 
        //              email_sent, sent_date, sent_by, status) 
        //             VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), ?, 'sent')";
        // $logStmt = $conn->prepare($logQuery);
        // $sentBy = $_SESSION['username'];
        // $logStmt->bind_param(
        //     "iiiiiss",
        //     $studentCode,
        //     $programId,
        //     $batchId,
        //     $moduleId,
        //     $mainComponentId,
        //     $subComponentId,
        //     $sentBy
        // );
        // $logStmt->execute();




        // Log email
        // $logQuery = "INSERT INTO email_sending_log 
        //             (student_id, program_id, batch_id, module_id, main_component_id, sub_component_id, 
        //              emailed_result, email_sent, sent_date, sent_by, status) 
        //             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, 'sent')";
        // $logStmt = $conn->prepare($logQuery);
        // $sentBy = $_SESSION['username'];
        // $logStmt->bind_param(
        //     "iiiiisss",
        //     $studentCode,
        //     $programId,
        //     $batchId,
        //     $moduleId,
        //     $mainComponentId,
        //     $subComponentId,
        //     $status,
        //     $sentBy
        // );
        // $logStmt->execute();

        // // echo "<p class='text-success'>Component Result has been sent successfully to {$email}!</p>";
        // echo "<script type='text/javascript'>alert('Component Result has been sent successfully to {$email}!');</script>";
        // echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
        $logQuery = "INSERT INTO email_sending_log 
                    (student_id, program_id, batch_id, module_id, main_component_id, sub_component_id,
                      email_sent, sent_date, sent_by, status, emailed_result) 
                    VALUES (?, ?, ?, ?, ?, ?, 1,NOW(), ?, 'sent', ? )";
        $logStmt = $conn->prepare($logQuery);
        $sentBy = $_SESSION['username'];
        $logStmt->bind_param(
            "iiiiiiss",
            $studentCode,
            $programId,
            $batchId,
            $moduleId,
            $mainComponentId,
            $subComponentId,
            $sentBy,
            $status

        );
        $logStmt->execute();

        // echo "<p class='text-success'>Component Result has been sent successfully to {$email}!</p>";
        echo "<script type='text/javascript'>alert('Component Result has been sent successfully to {$email}!');</script>";
        echo '<script>window.location.href = "' . $_SERVER['HTTP_REFERER'] . '";</script>';
    } catch (Exception $e) {
        // Record failed email sending

        // $errorMsg = $mail->ErrorInfo;
        // $logQuery = "INSERT INTO email_sending_log 
        //             (student_id, program_id, batch_id, module_id, main_component_id, sub_component_id, 
        //              email_sent, sent_date, sent_by, status, error_message) 
        //             VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), ?, 'failed', ?)";
        // $logStmt = $conn->prepare($logQuery);
        // $sentBy = $_SESSION['username'];
        // $logStmt->bind_param(
        //     "iiiiisss",
        //     $studentCode,
        //     $programId,
        //     $batchId,
        //     $moduleId,
        //     $mainComponentId,
        //     $subComponentId,
        //     $sentBy,
        //     $errorMsg
        // );
        // $logStmt->execute();

        $errorMsg = $mail->ErrorInfo;
        $logQuery = "INSERT INTO email_sending_log 
                    (student_id, program_id, batch_id, module_id, main_component_id, sub_component_id, email_sent, sent_date, sent_by, status, error_message, emailed_result) 
                    VALUES (?, ?, ?, ?, ?, ?, 0,NOW(), ?, 'failed', ?, ?)";
        $logStmt = $conn->prepare($logQuery);
        $sentBy = $_SESSION['username'];
        $logStmt->bind_param(
            "iiiiiisss",
            $studentCode,
            $programId,
            $batchId,
            $moduleId,
            $mainComponentId,
            $subComponentId,
            $sentBy,
            $status,
            $errorMsg
        );
        $logStmt->execute();

        echo "Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}

?>