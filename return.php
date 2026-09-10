<?php
session_start();
ob_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Set Sri Lanka timezone
date_default_timezone_set('Asia/Colombo');

// Fetch ONLY students who have active borrowings (status = 0)
$students_list = [];
$students_query = $conn->query("SELECT DISTINCT s.student_code, s.first_name, s.last_name, s.nic, ap.student_registration_id 
                                FROM students s 
                                LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code
                                INNER JOIN borrow b ON s.student_code = b.student_id
                                WHERE b.status = 0 AND b.borrower_type = 'student'
                                ORDER BY s.first_name ASC");
if ($students_query) {
    while ($s_row = $students_query->fetch_assoc()) {
        $students_list[] = $s_row;
    }
}

// Fetch ONLY staff who have active borrowings (status = 0)
$staff_list = [];
$staff_query = $conn->query("SELECT DISTINCT a.id, a.full_name, a.nic 
                            FROM admin a
                            INNER JOIN borrow b ON a.id = b.student_id
                            WHERE b.status = 0 AND b.borrower_type = 'staff'
                            ORDER BY a.full_name ASC");
if ($staff_query && $staff_query->num_rows > 0) {
    while ($st_row = $staff_query->fetch_assoc()) {
        $staff_list[] = $st_row;
    }
}

// Fetch books that are currently borrowed (status = 1) to populate the autocomplete arrays
$books_list = [];
$books_query = $conn->query("SELECT accession_number, title FROM books WHERE status = 1");
if ($books_query) {
    while ($b_row = $books_query->fetch_assoc()) {
        $books_list[] = $b_row;
    }
}
?>
<style>
/* Date and Time display */
.datetime-display {
    text-align: center;
}

.return-date {
    color: #333;
    font-weight: 500;
    display: block;
}

.return-time {
    color: #dc3545;
    font-weight: 500;
    font-size: 0.8rem;
    display: block;
    margin-top: 2px;
}

/* Badge styles */
.badge-student {
    background-color: #042d5c;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.badge-staff {
    background-color: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}
</style>
<body class="hold-transition skin-blue sidebar-mini">

<div id="wrapper">
    <?php include("nav.php"); ?>
    
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Return Books</h4>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-end">
                                <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm btn-flat shadow-sm">
                                    <i class="fa fa-plus fa-sm text-white-50"></i> Returns
                                </a>
                            </div>
                            <div class="card-body">

                                <?php
                                if(isset($_SESSION['error'])){
                                    ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <h4><i class="icon fa fa-warning"></i> Error!</h4>
                                        <ul class="mb-0">
                                        <?php
                                        if(is_array($_SESSION['error'])){
                                            foreach($_SESSION['error'] as $error){
                                                echo "<li>".$error."</li>";
                                            }
                                        } else {
                                            echo "<li>".$_SESSION['error']."</li>";
                                        }
                                        ?>
                                        </ul>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?php
                                    unset($_SESSION['error']);
                                }

                                if(isset($_SESSION['success'])){
                                    echo "
                                    <div class='alert alert-success alert-dismissible fade show' role='alert'>
                                        <h4><i class='icon fa fa-check'></i> Success!</h4>
                                        ".$_SESSION['success']."
                                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                            <span aria-hidden='true'>&times;</span>
                                        </button>
                                    </div>
                                    ";
                                    unset($_SESSION['success']);
                                }
                                ?>

                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-striped" style="font-size: 0.875rem;">
                                        <thead>
                                            <tr class="text-gray-800">
                                                <th class="hidden"></th>
                                                <th>Date & Time Returned</th>
                                                <th>Borrower ID</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Accession Number</th>
                                                <th>Title</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT r.*, 
                                                    s.student_code, s.first_name AS firstname, s.last_name AS lastname, 
                                                    s.nic AS student_nic,
                                                    a.full_name AS staff_name, a.nic AS staff_nic,
                                                    bk.accession_number, bk.title, 
                                                    ap.student_registration_id AS stud,
                                                    r.return_time,
                                                    r.borrower_type
                                                    FROM returns r 
                                                    LEFT JOIN students s ON s.student_code = r.student_id 
                                                    LEFT JOIN admin a ON a.id = r.student_id
                                                    LEFT JOIN books bk ON bk.id = r.book_id
                                                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code
                                                    ORDER BY r.date_return DESC";
                                                    
                                            $query = $conn->query($sql);
                                            if($query){
                                                while($row = $query->fetch_assoc()){
                                                    // Display date and time in Sri Lanka timezone
                                                    $return_date = (!empty($row['date_return'])) ? date('M d, Y', strtotime($row['date_return'])) : 'N/A';
                                                    $return_time = (!empty($row['return_time'])) ? date('h:i A', strtotime($row['return_time'])) : 'N/A';
                                                    
                                                    $datetime_display = '<span class="datetime-display">
                                                                        <span class="return-date">' . $return_date . '</span>
                                                                        <span class="return-time">' . $return_time . '</span>
                                                                        </span>';
                                                    
                                                    // Determine borrower type
                                                    $borrower_type = isset($row['borrower_type']) ? $row['borrower_type'] : 'student';
                                                    
                                                    if($borrower_type == 'student') {
                                                        $borrower_id = htmlspecialchars($row['stud'] ?? '');
                                                        $borrower_name = htmlspecialchars(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                                                        $type_badge = '<span class="badge-student">Student</span>';
                                                    } else {
                                                        $borrower_id = htmlspecialchars($row['staff_nic'] ?? 'N/A');
                                                        $borrower_name = htmlspecialchars($row['staff_name'] ?? 'Unknown Staff');
                                                        $type_badge = '<span class="badge-staff">Staff</span>';
                                                    }
                                                    
                                                    $acc_num = htmlspecialchars($row['accession_number'] ?? '');
                                                    $title = htmlspecialchars($row['title'] ?? '');

                                                    echo "
                                                    <tr>
                                                        <td class='hidden'></td>
                                                        <td>".$datetime_display."</td>
                                                        <td>".$borrower_id."</td>
                                                        <td>".$borrower_name."</td>
                                                        <td>".$type_badge."</td>
                                                        <td>".$acc_num."</td>
                                                        <td>".$title."</td>
                                                    </tr>
                                                    ";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div> </div> </div> </div> </div> </div> </div> </div> 
    <?php include 'librarypro/return_modal.php'; ?>
</div> 
<?php include 'librarypro/scripts.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

</body>
</html>