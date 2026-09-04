<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}
$Session_username = $_SESSION['username'];

$student_id = $_GET['student_id'] ?? '';
$program_id = $_GET['program_id'] ?? '';
$batch_id = $_GET['batch_id'] ?? '';

// =========================
// Fetch Student Information
// =========================
$query_student = "SELECT s.first_name, s.last_name, pr.program_name, b.batch_name
                  FROM students s
                  INNER JOIN program_table pr ON pr.program_code = '$program_id'
                  INNER JOIN batch_table b ON b.id = '$batch_id'
                  WHERE s.student_code = '$student_id'
                  LIMIT 1";
$result_student = mysqli_query($conn, $query_student);
$student = mysqli_fetch_assoc($result_student);

// =========================
// Fetch Installment Discount History
// =========================
$query = "SELECT p.*, s.*, pr.program_name, b.batch_name
          FROM payment_plan_history p
          INNER JOIN students s ON p.student_id = s.student_code 
          INNER JOIN program_table pr ON p.program_id = pr.program_code 
          INNER JOIN batch_table b ON p.batch_id = b.id
          WHERE p.student_id = '$student_id'
          AND p.program_id = '$program_id'
          AND p.batch_id = '$batch_id'
          ORDER BY p.id DESC";
$result = mysqli_query($conn, $query);

// =========================
// Fetch Registration Fee Discount History
// =========================
$query_regfee = "SELECT prf.*, s.*, pr.program_name, b.batch_name
          FROM payment_plan_regfee_discount prf
          INNER JOIN students s ON prf.student_id = s.student_code 
          INNER JOIN program_table pr ON prf.program_id = pr.program_code 
          INNER JOIN batch_table b ON prf.batch_id = b.id
          WHERE prf.student_id = '$student_id'
          AND prf.program_id = '$program_id'
          AND prf.batch_id = '$batch_id'
          ORDER BY prf.id DESC";
$result_regfee = mysqli_query($conn, $query_regfee);
?>

<!-- Page Wrapper -->
<div id="wrapper" class="d-flex">

    <!-- Sidebar -->
    <?php include("nav.php"); ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="flex-grow-1 d-flex flex-column">

        <!-- Topbar -->
        <?php include("includes/topnav.php"); ?>

        <!-- Main Content -->
        <div id="content" class="bg-light p-4">

            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h5 class="mb-0 text-gray-800">Payment Plan & Discount History</h5>
                <hr>
            </div>

            <div class="row g-4">

                <!-- Student Information Card -->
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-gradient-primary rounded-top-4 text-white">
                            <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Student Information</h5>
                        </div>
                        <div class="card-body p-3">

                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body d-flex align-items-center">
                                    <!-- Left: Student Image -->
                                    <div class="me-4 text-center">
                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRISuukVSb_iHDfPAaDKboFWXZVloJW9XXiwGYFab-QwlAYQ3zFsx4fToY9ijcVNU5ieKk&usqp=CAU"
                                            alt="Student Photo"
                                            class="rounded-circle"
                                            style="width: 120px; height: 120px; object-fit: cover; border: 2px solid #ddd; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    </div>

                                    <!-- Right: Student Details -->
                                    <div class="flex-grow-1">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <?php if ($student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['program_name']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['batch_name']); ?></td>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <td>No student info found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Initial Fee Discount History -->
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-gradient-warning text-white rounded-top-4">
                            <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Initial Fee Discount History</h5>
                        </div>
                        <div class="card-body p-3">
                            <table id="initialFeeHistory" class="table table-hover table-striped table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Amount</th>
                                        <th>Remarks</th>
                                        <th>Updated Date</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 1;
                                    while ($row = mysqli_fetch_assoc($result_regfee)): ?>
                                        <tr>
                                            <td><?php echo $index++; ?></td>
                                            <td><?php echo htmlspecialchars($row['discount_value']); ?></td>
                                            <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($row['updated_by']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Installment Discount History -->
                <div class="col-lg-12">
                    <div class="card shadow-sm border-0 rounded-4">
                        <div class="card-header bg-gradient-success text-white rounded-top-4">
                            <h5 class="mb-0"><i class="fas fa-coins"></i> Installment Discount History</h5>
                        </div>
                        <div class="card-body p-3">
                            <table id="installmentHistory" class="table table-hover table-striped table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Amount</th>
                                        <th>Remarks</th>
                                        <th>Updated Date</th>
                                        <th>Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 1;
                                    while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo $index++; ?></td>
                                            <td><?php echo htmlspecialchars($row['discount_value']); ?></td>
                                            <td><?php echo htmlspecialchars($row['re_marks']); ?></td>
                                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($row['updated_by']); ?></td>
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

<!-- DataTables Initialization -->
<script>
    $(document).ready(function() {
        $('#installmentHistory, #initialFeeHistory').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
    });
</script>

<!-- Scripts: jQuery, Bootstrap, DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

</body>

</html>