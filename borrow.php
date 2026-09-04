<?php
//session_start();
ob_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Set Sri Lanka timezone
date_default_timezone_set('Asia/Colombo');

// Fetch lists for the live search drop-downs - student_code removed from visible presentation format
$students_list = [];
$students_query = $conn->query("SELECT s.student_code, s.first_name, s.last_name, s.nic, ap.student_registration_id 
                                FROM students s 
                                LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code
                                WHERE ap.status = 'active' ");
if ($students_query) {
    while ($s_row = $students_query->fetch_assoc()) {
        $students_list[] = $s_row;
    }
}

// Fetch staff list from admin table - NO ROLE FILTER
$staff_list = [];
$staff_query = $conn->query("SELECT id, full_name, nic FROM admin");
if ($staff_query && $staff_query->num_rows > 0) {
    while ($st_row = $staff_query->fetch_assoc()) {
        $staff_list[] = $st_row;
    }
}

$books_list = [];
$books_query = $conn->query("SELECT accession_number, title FROM books WHERE status != 1");
if ($books_query) {
    while ($b_row = $books_query->fetch_assoc()) {
        $books_list[] = $b_row;
    }
}
?>

<style>
/* Status badges */
.status-available {
    background-color: #353434;
    color: greenyellow;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
    width: 100%;
}

.status-borrowed {
    background-color: #dc3545;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
    text-align: center;
    width: 100%;
}

.badge-borrower-type {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    color: white;
}

.badge-student {
    background-color: #042d5c; /* Dark blue as requested */
    width: 100%;
    text-align: center;
}

.badge-staff {
    background-color: #990014; /* Red as requested */
    width: 100%;
    text-align: center;
}

/* Date and Time display */
.datetime-display {
    text-align: center;
}

.borrow-date {
    color: #333;
    font-weight: 500;
    display: block;
}

.borrow-time {
    color: #dc3545;
    font-weight: 500;
    font-size: 0.8rem;
    display: block;
    margin-top: 2px;
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
                    <h4 class="h4 mb-0 text-gray-800">Borrow Books</h4>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-end align-items-center">
                                <a href="#addnew" data-toggle="modal" class="btn btn-primary btn-sm">
                                    <i class="fa fa-plus fa-sm text-white-50"></i> Borrow
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
                                                <th>Date & Time</th>
                                                <th>Borrower ID</th>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Accession Number</th>
                                                <th>Title</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fixed query to prevent duplicates - using DISTINCT and proper grouping
                                            $sql = "SELECT DISTINCT 
                                                    b.id,
                                                    b.date_borrow,
                                                    b.borrow_time,
                                                    b.status AS barstat,
                                                    b.borrower_type,
                                                    s.student_code, 
                                                    s.first_name AS firstname, 
                                                    s.last_name AS lastname, 
                                                    s.nic AS student_nic,
                                                    a.full_name AS staff_name, 
                                                    a.nic AS staff_nic,
                                                    bk.accession_number, 
                                                    bk.title, 
                                                    ap.student_registration_id AS stud
                                                    FROM borrow b
                                                    LEFT JOIN students s ON s.student_code = b.student_id AND b.borrower_type = 'student'
                                                    LEFT JOIN admin a ON a.id = b.student_id AND b.borrower_type = 'staff'
                                                    LEFT JOIN books bk ON bk.id = b.book_id 
                                                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code
                                                    WHERE ap.status = 'active' 
                                                    GROUP BY b.id
                                                    ORDER BY b.date_borrow DESC";

                                            $query = $conn->query($sql);
                                            if($query){
                                                while($row = $query->fetch_assoc()){
                                                    if($row['barstat'] == 1){
                                                        $status = '<span class="badge status-available px-2 py-1">Returned</span>';
                                                    }
                                                    else{
                                                        $status = '<span class="badge status-borrowed px-2 py-1">Not Returned</span>';
                                                    }
                                                    
                                                    // Display date and time in Sri Lanka timezone
                                                    $borrow_date = (!empty($row['date_borrow'])) ? date('M d, Y', strtotime($row['date_borrow'])) : 'N/A';
                                                    $borrow_time = (!empty($row['borrow_time'])) ? date('h:i A', strtotime($row['borrow_time'])) : 'N/A';
                                                    
                                                    $datetime_display = '<span class="datetime-display">
                                                                        <span class="borrow-date">' . $borrow_date . '</span>
                                                                        <span class="borrow-time">' . $borrow_time . '</span>
                                                                        </span>';
                                                    
                                                    // Determine borrower type and display accordingly
                                                    $borrower_type = isset($row['borrower_type']) ? $row['borrower_type'] : 'student';
                                                    $borrower_id = '';
                                                    $borrower_name = '';
                                                    
                                                    if($borrower_type == 'student') {
                                                        $borrower_id = htmlspecialchars($row['stud'] ?? '');
                                                        $borrower_name = htmlspecialchars(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
                                                        $type_badge = '<span class="badge-borrower-type badge-student">Student</span>';
                                                    } else {
                                                        // Staff borrower - show NIC as ID
                                                        $borrower_id = htmlspecialchars($row['staff_nic'] ?? 'N/A');
                                                        $borrower_name = htmlspecialchars($row['staff_name'] ?? 'Unknown Staff');
                                                        $type_badge = '<span class="badge-borrower-type badge-staff">Staff</span>';
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
                                                        <td>".$status."</td>
                                                    </tr>
                                                    ";
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div> </div> </div> </div> </div> </div> </div> </div> 
    
    <?php include 'librarypro/borrow_modal.php'; ?>
</div> <?php include 'librarypro/scripts.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

<script>
$(function(){
    $(document).on('click', '#append', function(e){
        e.preventDefault();
        $('#append-div').append(
            '<div class="form-group row mt-2"><label class="col-sm-4 col-form-label font-weight-bold text-gray-700 m-0" style="font-size: 0.875rem;">Accession Number</label><div class="col-sm-8"><input type="text" class="form-control form-control-sm border-left-info" name="accession_number[]" list="books_data" placeholder="Enter Accession Number" required></div></div>'
        );
    });
});
</script>
</body>
</html>