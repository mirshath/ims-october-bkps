<?php
//session_start();
    include("database/connection.php");
    

    if (!isset($_SESSION['username'])) {
        // header("location: login.php");
        echo '<script>window.location.href = "login";</script>';
        // exit();
    }


// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------



/* =========================
SAVE / UPDATE
========================= */
if(isset($_POST['save'])){

    $id = $_POST['class_id'] ?? '';
    $branch = $_POST['programme'] ?? '';
    $name = $_POST['hall_name'] ?? '';

    /* UPDATED */
    $classtype = $_POST['classtype'] ?? '';

    $seatcount = $_POST['seatcount'] ?? 0;
    $extra_seat = $_POST['extra_seat'] ?? 0;
    $totalseat = $seatcount + $extra_seat;

    if($id == ''){

        mysqli_query($conn,"
            INSERT INTO classroom 
            (
                branch,
                lectuerhallname,
                class_type,
                seatcount,
                extra_seat,
                totalseat
            )
            VALUES 
            (
                '$branch',
                '$name',
                '$classtype',
                '$seatcount',
                '$extra_seat',
                '$totalseat'
            )
        ");

    } else {

        mysqli_query($conn,"
            UPDATE classroom SET

            branch='$branch',
            lectuerhallname='$name',
            class_type='$classtype',
            seatcount='$seatcount',
            extra_seat='$extra_seat',
            totalseat='$totalseat'

            WHERE class_id='$id'
        ");
    }

    header("Location: add_classroom.php");
    exit;
}

/* =========================
STATUS UPDATE (AJAX)
========================= */
if(isset($_POST['toggle_status'])){

    $id = $_POST['id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'] ?? '';

    if($status == 0){

        mysqli_query($conn,"
            UPDATE classroom 
            SET 
            status='$status',
            deactivate_reason='$reason'
            WHERE class_id='$id'
        ");

    } else {

        mysqli_query($conn,"
            UPDATE classroom 
            SET 
            status='$status',
            deactivate_reason=NULL
            WHERE class_id='$id'
        ");
    }

    echo "success";
    exit;
}

/* =========================
EDIT LOAD
========================= */
$edit_data = null;

if(isset($_GET['edit_id'])){

    $id = $_GET['edit_id'];

    $res = mysqli_query($conn,"
    SELECT *
    FROM classroom
    WHERE class_id='$id'
    ");

    $edit_data = mysqli_fetch_assoc($res);
}

/* =========================
SEARCH AJAX
========================= */
if(isset($_POST['ajax_search'])){

    // 1. Get the search term or default to empty string
    $search = $_POST['search'] ?? '';

    // 2. Secure the input to prevent SQL Injection
    $safe_search = mysqli_real_escape_string($conn, $search);

    // 3. Add wildcards for the LIKE operator
    $like_search = "%" . $safe_search . "%";

    // 4. Use OR instead of AND, and execute the query
    $result = mysqli_query($conn, "
        SELECT *
        FROM classroom
        WHERE lectuerhallname LIKE '$like_search'
        OR branch LIKE '$like_search'
        OR class_type LIKE '$like_search'
        OR status LIKE '$like_search'
        ORDER BY branch DESC
    ");
?>

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>
<th>ID</th>
<th>Region</th>
<th>Lecture Hall</th>
<th>Lecture Hall Type</th>
<th>Seat</th>
<th>Extra</th>
<th>Total</th>
<th>Status</th>
<th>Reason</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['class_id'] ?></td>

<td><?= $row['branch'] ?></td>

<td><?= $row['lectuerhallname'] ?></td>

<!-- UPDATED -->
<td><?= $row['class_type'] ?></td>

<td><?= $row['seatcount'] ?></td>

<td><?= $row['extra_seat'] ?></td>

<td>
<b><?= $row['totalseat'] ?></b>
</td>

<td>

<div class="form-check form-switch">

<input
class="form-check-input statusToggle"
type="checkbox"
data-id="<?= $row['class_id'] ?>"
<?= ($row['status']==1)?'checked':'' ?>
>

<label class="form-check-label">

<?= ($row['status']==1)?'Active':'Deactive' ?>

</label>

</div>

</td>

<td>

<?= ($row['status']==0 && !empty($row['deactivate_reason']))
? $row['deactivate_reason']
: '-' ?>

</td>

<td>

<a href="?edit_id=<?= $row['class_id'] ?>"
class="btn btn-warning btn-sm">

Edit

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<?php
exit;
}


include("includes/header.php");
?>

<!DOCTYPE html>
<html>

<head>

<title>Classroom Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body >

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
                        <h4 class="h4 mb-0 text-gray-800">Classroom Management</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Add & Update Room Info</h6>
                                </div>
                                <div class="card-body">
<!-- ===================================================================================== -->


<!-- ================= FORM ================= -->

<form method="POST" class="card p-4 shadow mb-3">

<input
type="hidden"
name="class_id"
value="<?= $edit_data['class_id'] ?? '' ?>"
>

<div class="row">

<!-- REGION -->

<div class="col-md-6 mb-3">

<label>Region</label>

<select name="programme" class="form-select">

<option
value="BMS"
<?= ($edit_data['branch']??'')=='BMS'?'selected':'' ?>
>

BMS

</option>

<option
value="CGS"
<?= ($edit_data['branch']??'')=='CGS'?'selected':'' ?>
>

CGS

</option>

</select>

</div>

<!-- HALL NAME -->

<div class="col-md-6 mb-3">

<label>Lecture Hall Name *</label>

<input
type="text"
name="hall_name"
class="form-control"
value="<?= $edit_data['lectuerhallname'] ?? '' ?>"
required
>

</div>

<!-- UPDATED CLASS TYPE -->

<div class="col-md-6 mb-3">

<label>Lecture Hall Type</label>

<select name="classtype" class="form-select">

<option value="">Select Type</option>

<option
value="In-person class"
<?= ($edit_data['class_type']??'')=='In-person class'?'selected':'' ?>
>

In-person class

</option>

<option
value="Hybrid class"
<?= ($edit_data['class_type']??'')=='Hybrid class'?'selected':'' ?>
>

Hybrid class

</option>

<option
value="Executive Suite Hub"
<?= ($edit_data['class_type']??'')=='Executive Suite Hub'?'selected':'' ?>
>

Executive Suite Hub

</option>

<option
value="Computer Lab"
<?= ($edit_data['class_type']??'')=='Computer Lab'?'selected':'' ?>
>

Computer Lab

</option>

<option
value="Science Laboratory"
<?= ($edit_data['class_type']??'')=='Science Laboratory'?'selected':'' ?>
>

Science Laboratory

</option>

</select>

</div>

<!-- SEAT COUNT -->

<div class="col-md-3 mb-3">

<label>Seat Count *</label>

<input
type="number"
name="seatcount"
class="form-control"
value="<?= $edit_data['seatcount'] ?? 0 ?>"
required
>

</div>

<!-- EXTRA SEAT -->

<div class="col-md-3 mb-3">

<label>Extra Seat</label>

<input
type="number"
name="extra_seat"
class="form-control"
value="<?= $edit_data['extra_seat'] ?? 0 ?>"
>

</div>

</div>

<button type="submit" name="save" class="btn btn-primary" >

<?= isset($edit_data) ? 'Update' : 'Save' ?>

</button>

</form>


<!-- ============================================================================================================== -->

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
                                    <h6 class="mb-0 me-2">Space Info & Status</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Students Table -->
                                    <div class="table-responsive mb-4">

<!-- ================================================================================================================== -->

<!-- ================= SEARCH ================= -->

<div class="d-flex justify-content-end mb-2">

<input
type="text"
id="searchBox"
class="form-control w-25"
placeholder="Search classroom..."
>

</div>

<!-- ================= TABLE ================= -->

<div class="card p-3 shadow" id="tableArea">

<?php
$result = mysqli_query($conn,"
SELECT *
FROM classroom
ORDER BY class_id DESC
");
?>

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Region</th>
<th>Lecture Hall</th>
<th>Lecture Hall Type</th>
<th>Seat</th>
<th>Extra</th>
<th>Total</th>
<th>Status</th>
<th>Reason</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['class_id'] ?></td>

<td><?= $row['branch'] ?></td>

<td><?= $row['lectuerhallname'] ?></td>

<!-- UPDATED -->
<td><?= $row['class_type'] ?></td>

<td><?= $row['seatcount'] ?></td>

<td><?= $row['extra_seat'] ?></td>

<td>
<b><?= $row['totalseat'] ?></b>
</td>

<td>

<div class="form-check form-switch">

<input
class="form-check-input statusToggle"
type="checkbox"
data-id="<?= $row['class_id'] ?>"
<?= ($row['status']==1)?'checked':'' ?>
>

<label class="form-check-label">

<?= ($row['status']==1)?'Active':'Deactive' ?>

</label>

</div>

</td>

<td>

<?= ($row['status']==0 && !empty($row['deactivate_reason']))
? $row['deactivate_reason']
: '-' ?>

</td>

<td>

<a
href="?edit_id=<?= $row['class_id'] ?>"
class="btn btn-warning btn-sm"
>

Edit

</a>

</td>

</tr>

<?php } ?>

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

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />


    </div>

<!-- ================= SCRIPTS ================= -->

<script>

/* SEARCH */
$('#searchBox').on('keyup', function(){

$.post(
"add_classroom.php",
{
    ajax_search: 1,
    search: $(this).val()
},
function(data){

    $('#tableArea').html(data);

});

});

/* STATUS TOGGLE */
$(document).on('change','.statusToggle',function(){

let checkbox = $(this);

let id = checkbox.data('id');

let status = checkbox.is(':checked') ? 1 : 0;

if(status == 0){

    let reason = prompt("Enter reason for deactivation:");

    if(reason === null || reason.trim() === ''){

        alert("Deactivation reason required!");

        checkbox.prop('checked', true);

        return;
    }

    $.post(
    "add_classroom.php",
    {
        toggle_status:1,
        id:id,
        status:0,
        reason:reason
    },
    function(res){

        if(res.trim() === "success"){

            alert("Classroom deactivated successfully!");

            location.reload();
        }

    });

} else {

    $.post(
    "add_classroom.php",
    {
        toggle_status:1,
        id:id,
        status:1
    },
    function(res){

        if(res.trim() === "success"){

            alert("Classroom activated successfully!");

            location.reload();
        }

    });

}

/* LABEL UPDATE */
let label = $(this)
.closest('.form-check')
.find('.form-check-label');

label.text(status ? 'Active' : 'Deactive');

});

</script>

</body>
</html>