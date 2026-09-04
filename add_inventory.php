<?php
//session_start();
    include("database/connection.php");
    include("includes/header.php");

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


/* NO CACHE */
// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Pragma: no-cache");

/* ================= UPDATE ================= */
if(isset($_POST['update_item'])){
    mysqli_query($conn,"
        UPDATE class_inventory SET
        type_id='{$_POST['type_id']}',
        quantity='{$_POST['quantity']}',
        working_qty='{$_POST['working']}',
        item_details='{$_POST['details']}'
        WHERE inv_id='{$_POST['inv_id']}'
    ");
    exit;
}

/* ================= ADD ================= */
if(isset($_POST['add_item'])){
    mysqli_query($conn,"
        INSERT INTO class_inventory
        (class_id,type_id,quantity,working_qty,item_details)
        VALUES
        ('{$_POST['class_id']}','{$_POST['type_id']}','{$_POST['quantity']}','{$_POST['working']}','{$_POST['details']}')
    ");
    exit;
}

/* ================= Delete ================= */
/* ================= DELETE ================= */
if(isset($_POST['delete_item'])){

    $inv_id = $_POST['inv_id'];

    mysqli_query($conn,"
        DELETE FROM class_inventory
        WHERE inv_id='$inv_id'
    ");

    echo "success";
    exit;
}

/* ================= LOAD INVENTORY ================= */
if(isset($_POST['load_inventory'])){

    $class_id = $_POST['class_id'];

    $res = mysqli_query($conn,"
        SELECT ci.*, it.type_name
        FROM class_inventory ci
        JOIN inventory_types it ON ci.type_id = it.type_id
        WHERE ci.class_id='$class_id'
    ");

    if(mysqli_num_rows($res)==0){
        echo "<div class='alert alert-warning'>No inventory found.</div>";
    } else {
?>

<table class="table table-bordered table-sm">
<thead class="table-dark">
<tr>
<th>Item</th>
<th>Qty</th>
<th>Working</th>
<th>Status</th>
<th>Action</th>
</tr>
</thead>
<tbody>

<?php while($r=mysqli_fetch_assoc($res)){ 
$issue = $r['working_qty'] < $r['quantity'];
?>

<tr class="<?= $issue ? 'table-danger' : '' ?>">
<td><?= $r['type_name'] ?></td>
<td><?= $r['quantity'] ?></td>
<td><?= $r['working_qty'] ?></td>
<td><?= $issue ? '⚠️' : '✔️' ?></td>

<td>

<button class="btn btn-warning btn-sm editItem"
data-id="<?= $r['inv_id'] ?>"
data-type="<?= $r['type_id'] ?>"
data-qty="<?= $r['quantity'] ?>"
data-working="<?= $r['working_qty'] ?>"
data-details="<?= htmlspecialchars($r['item_details']) ?>">
<i class="bi bi-pencil-square"></i>
</button>

<button class="btn btn-danger btn-sm deleteItem"
data-id="<?= $r['inv_id'] ?>">
<i class="bi bi-trash"></i>
</button>

</td>

<!-- <td>
<button class="btn btn-warning btn-sm editItem"
data-id="<?= $r['inv_id'] ?>"
data-type="<?= $r['type_id'] ?>"
data-qty="<?= $r['quantity'] ?>"
data-working="<?= $r['working_qty'] ?>"
data-details="<?= htmlspecialchars($r['item_details']) ?>">
Edit
</button>
</td> -->

</tr>

<?php } ?>

</tbody>
</table>

<?php
    }
    exit;
}

/* ================= DATA ================= */
$classrooms = mysqli_query($conn,"SELECT * FROM classroom");
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
.card{ transition:0.2s; }
.card:hover{ transform:scale(1.02); }

.manage-btn{
    color:#000;
    font-size:18px;
    cursor:pointer;
}

.manageBtn{
    background:transparent;
    border:none;
    color:#ffffff; /* BLACK ICON */
    font-size:18px;
}

.manageBtn:hover{
    color:#ffc107;
    transform:scale(1.1);
}
</style>

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
                        <h4 class="h4 mb-0 text-gray-800">Classroom Inventory</h4>
                    </div>

 <!-- ============================================================================================================ -->


<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
<!-- <span class="manage-btn" title="Manage Table">
⚙️
</span> -->
</div>


<!-- ================= TABLE ================= -->
<!-- <div class="card p-3 shadow mb-4">

<table class="table table-bordered">
<thead class="table-dark">
<tr>
<th>Classroom</th>
<th>Total Seats</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php 
mysqli_data_seek($classrooms,0);
while($row=mysqli_fetch_assoc($classrooms)){ 
?>

<tr>
<td><?= $row['lectuerhallname'] ?></td>
<td><?= $row['seatcount'] + $row['extra_seat'] ?></td>

<td>
<button class="btn btn-primary btn-sm viewInventory"
data-id="<?= $row['class_id'] ?>">
Manage
</button>
</td>
</tr>

<?php } ?>

</tbody>
</table>

</div> -->

<!-- SEARCH -->
<div class="mb-3">
<input type="text" id="cardSearch" class="form-control" placeholder="Search classroom...">
</div>


<!-- ================= CARDS ================= -->
 <div id="cardContainer">
<?php
function renderCards($conn,$branch,$title,$color){

echo "<h5 class='mt-4'>$title</h5>";
echo "<div class='row'>";

$q = mysqli_query($conn,"SELECT * FROM classroom WHERE branch='$branch' OR ('$branch'='OTHER' AND (branch IS NULL OR branch NOT IN('BMS','CGS')))");

while($c=mysqli_fetch_assoc($q)){

$items = mysqli_query($conn,"
SELECT it.type_name, ci.quantity, ci.working_qty
FROM class_inventory ci
JOIN inventory_types it ON ci.type_id=it.type_id
WHERE ci.class_id='{$c['class_id']}'
");

echo "<div class='col-md-4 mb-4 card-item'>";

echo "<div class='card shadow-sm'>";
$stateText = ($c['status'] == 1) ? 'Active' : 'Deactive';
$stateColor = ($c['status'] == 1) ? '#28a745' : '#ffffff';
$stateTextColor = ($c['status'] == 1) ? '#ffffff' : '#ee0909';

echo "
<div class='card-header d-flex justify-content-between align-items-center text-white' style='background:$color'>

<div>
    <span style='font-weight: bold;'>{$c['lectuerhallname']}</span>
    
    <span style='margin-left:10px; font-size:12px; padding:3px 8px; border-radius:10px; background:$stateColor; color:$stateTextColor;'>
        $stateText
    </span>
</div>

<button class='btn btn-sm manageBtn'
data-id='{$c['class_id']}'
title='Manage Inventory'>
<i class='bi bi-gear-fill'></i>
</button>

</div>
";
echo "<div class='card-body'>";

echo "<p><b>Available Seats:</b> {$c['seatcount']}</p>";
echo "<p><b>Extra Seats:</b> {$c['extra_seat']}</p>";

echo "<hr>";

if(mysqli_num_rows($items)>0){

echo "<table class='table table-sm'>";

while($i=mysqli_fetch_assoc($items)){
$issue = $i['working_qty'] < $i['quantity'];

echo "<tr class='".($issue?'table-danger':'')."'>
<td>{$i['type_name']}</td>
<td>{$i['quantity']}</td>
<td>{$i['working_qty']}</td>
<td>".($issue?'⚠️':'✔️')."</td>
</tr>";
}

echo "</table>";

}else{
echo "<span class='text-muted'>No inventory</span>";
}

echo "</div></div></div>";
}

echo "</div>";
}

/* GROUPS */
renderCards($conn,'BMS','BMS Classrooms','#042d5c');
renderCards($conn,'CGS','CGS Classrooms','#dc3545');
renderCards($conn,'OTHER','Other Place','#333333');
?>
</div>
<!-- ================= MODAL ================= -->
<div class="modal fade" id="inventoryModal">
<div class="modal-dialog modal-xl">
<div class="modal-content">

<div class="modal-header">
<h5>Inventory</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form id="addItemForm">

<input type="hidden" name="class_id" id="modal_class_id">
<input type="hidden" name="inv_id" id="inv_id">

<div class="row">

<div class="col-md-4">
<select name="type_id" class="form-select" required>
<option value="">Select Type</option>
<?php
$types = mysqli_query($conn,"SELECT * FROM inventory_types");
while($t=mysqli_fetch_assoc($types)){
echo "<option value='{$t['type_id']}'>{$t['type_name']}</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<input type="number" name="quantity" class="form-control" placeholder="Qty">
</div>

<div class="col-md-2">
<input type="number" name="working" class="form-control" placeholder="Working">
</div>

<div class="col-md-4">
<input type="text" name="details" class="form-control" placeholder="Details">
</div>

</div>

<br>

<button type="submit" class="btn btn-primary">+ Add Item</button>

</form>

<hr>

<div id="inventoryContent"></div>

</div>

</div>
</div>
</div>

<!-- =================================================================================================== -->

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

<!-- ============================================================================================================ -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

    if (window.performance && window.performance.navigation.type === 2) {
    location.reload(true);
}

/* DISABLE CACHE */
$.ajaxSetup({cache:false});

/* OPEN MODAL */
$(document).on('click','.viewInventory',function(){

let id=$(this).data('id');
$('#modal_class_id').val(id);

$.post("add_inventory.php",{load_inventory:1,class_id:id},function(data){
$('#inventoryContent').html(data);
$('#inventoryModal').modal('show');
});

});

/* EDIT */
$(document).on('click','.editItem',function(){

$('#inv_id').val($(this).data('id'));
$('select[name="type_id"]').val($(this).data('type'));
$('input[name="quantity"]').val($(this).data('qty'));
$('input[name="working"]').val($(this).data('working'));
$('input[name="details"]').val($(this).data('details'));

$('#addItemForm button').text('Update Item');

});

/* ADD / UPDATE */
$('#addItemForm').submit(function(e){
e.preventDefault();

let inv_id = $('#inv_id').val();
let action = inv_id ? 'update_item=1' : 'add_item=1';

$.post("add_inventory.php", $(this).serialize()+"&"+action, function(){

location.reload(); // FULL REFRESH (clean state)
refreshCards();

});

});



/* DELETE ITEM */
$(document).on('click','.deleteItem',function(){

let id = $(this).data('id');

if(confirm("Are you sure you want to delete this item?")){

$.post("add_inventory.php", {
    delete_item: 1,
    inv_id: id
}, function(res){

if(res.trim() === "success"){

let class_id = $('#modal_class_id').val();

/* refresh inventory */
$.post("add_inventory.php", {
    load_inventory: 1,
    class_id: class_id
}, function(data){
    $('#inventoryContent').html(data);
});

/* reset form */
$('#addItemForm')[0].reset();
$('#inv_id').val('');
$('#addItemForm button').text('+ Add Item');

location.reload();
refreshCards();

}

});

}

});

/* SEARCH CARDS */
$('#cardSearch').on('keyup',function(){

let val = $(this).val().toLowerCase();

$('.card-item').filter(function(){
$(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
});

});

$(document).on('click','.manageBtn',function(){

let id = $(this).data('id');

$('#modal_class_id').val(id);

$.post("add_inventory.php",{load_inventory:1,class_id:id},function(data){
    $('#inventoryContent').html(data);
    $('#inventoryModal').modal('show');
});

});

function refreshCards(){
    $.ajax({
        url: location.href,
        cache: false,
        success: function(data){
            let html = $(data).find('#cardContainer').html();
            $('#cardContainer').html(html);
        }
    });
}

</script>

</body>
</html>