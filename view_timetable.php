<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// Fetch all active timetable entries
$query = "SELECT t.*, p.program_name, b.batch_name, m.module_name, 
          CONCAT(l.title, ' ', l.lecturer_name) as lecturer_name
          FROM timetable t
          JOIN program_table p ON t.programme_id = p.program_code
          JOIN batch_table b ON t.batch_id = b.id
          JOIN modules m ON t.module_id = m.id
          JOIN lecturer_table l ON t.lecturer_id = l.id
          WHERE t.status = 'active'
          ORDER BY t.day, t.start_time";
$result = mysqli_query($conn, $query);
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
            <div class="container-fluid py-4">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">View Timetable</h4>
                    <a href="AddTimeTable.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus fa-sm"></i> Add New Timetable Entry
                    </a>
                </div>

                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Options</h6>
                    </div>
                    <div class="card-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-3">
                                <label for="filterProgram" class="form-label">Program</label>
                                <select id="filterProgram" class="form-select">
                                    <option value="">All Programs</option>
                                    <?php
                                    $program_query = "SELECT DISTINCT programme_id, programme_name FROM timetable WHERE status = 'active' ORDER BY programme_name";
                                    $program_result = mysqli_query($conn, $program_query);
                                    while ($row = mysqli_fetch_assoc($program_result)) {
                                        echo "<option value='" . $row['programme_id'] . "'>" . $row['programme_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterBatch" class="form-label">Batch</label>
                                <select id="filterBatch" class="form-select">
                                    <option value="">All Batches</option>
                                    <?php
                                    $batch_query = "SELECT DISTINCT batch_id, batch_name FROM timetable WHERE status = 'active' ORDER BY batch_name";
                                    $batch_result = mysqli_query($conn, $batch_query);
                                    while ($row = mysqli_fetch_assoc($batch_result)) {
                                        echo "<option value='" . $row['batch_id'] . "'>" . $row['batch_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterDay" class="form-label">Day</label>
                                <select id="filterDay" class="form-select">
                                    <option value="">All Days</option>
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" id="applyFilter" class="btn btn-primary me-2">Apply Filter</button>
                                <button type="button" id="resetFilter" class="btn btn-secondary">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Timetable Table -->
                <div class="card shadow-sm">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Timetable Entries</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="timetableTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Program</th>
                                        <th>Batch</th>
                                        <th>Module</th>
                                        <th>Lecturer</th>
                                        <th>Duration</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $start_time = date("h:i A", strtotime($row['start_time']));
                                            $end_time = date("h:i A", strtotime($row['end_time']));
                                            $start_date = date("d M Y", strtotime($row['start_date']));
                                            $end_date = date("d M Y", strtotime($row['end_date']));

                                            echo "<tr>";
                                            echo "<td>" . $row['day'] . "</td>";
                                            echo "<td>" . $start_time . " - " . $end_time . "</td>";
                                            echo "<td>" . $row['programme_name'] . "</td>";
                                            echo "<td>" . $row['batch_name'] . "</td>";
                                            echo "<td>" . $row['module_name'] . "</td>";
                                            echo "<td>" . $row['lecturer_name'] . "</td>";
                                            echo "<td>" . $start_date . " to " . $end_date . "</td>";
                                            echo "<td>
                                                   
                                                    <a href='javascript:void(0)' onclick='confirmDelete(" . $row['id'] . ")' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></a>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No timetable entries found</td></tr>";
                                    }
                                    ?>

                                    <!-- // <a href='edit_timetable.php?id=" . $row['id'] . "' class='btn btn-sm btn-primary'><i class='fas fa-edit'></i></a> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Content -->
        </div>
        <!-- End Main Content -->
    </div>
    <!-- End Content Wrapper -->
</div>
<!-- End Page Wrapper -->

<!-- JS and CSS includes -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#timetableTable').DataTable({
            "ordering": true,
            "paging": true,
            "searching": true,
            "info": true,
            "responsive": true,
            "lengthMenu": [
                [10, 25, 50, -1],
                [10, 25, 50, "All"]
            ]
        });

        // Apply filters
        $('#applyFilter').on('click', function() {
            applyFilters();
        });

        // Reset filters
        $('#resetFilter').on('click', function() {
            $('#filterProgram').val('');
            $('#filterBatch').val('');
            $('#filterDay').val('');
            table.search('').columns().search('').draw();
        });

        function applyFilters() {
            var program = $('#filterProgram').val();
            var batch = $('#filterBatch').val();
            var day = $('#filterDay').val();

            // Clear previous filters
            table.search('').columns().search('').draw();

            // Apply new filters
            if (program) {
                table.column(2).search(program).draw();
            }
            if (batch) {
                table.column(3).search(batch).draw();
            }
            if (day) {
                table.column(0).search(day).draw();
            }
        }
    });

    // Confirm delete function
    function confirmDelete(id) {
        if (confirm('Are you sure you want to delete this timetable entry?')) {
            window.location.href = 'delete_timetable.php?id=' + id;
        }
    }
</script>
</body>

</html>