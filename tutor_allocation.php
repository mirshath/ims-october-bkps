<?php
session_start();
include("database/connection.php");
include("includes/header.php");
$Session_username = $_SESSION['username'] ?? ''; // Get the username from session
$current_user_id  = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

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

 $tutors = $conn->query("SELECT id, username FROM admin WHERE role='lecture'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tutor Allocate</title>

<!-- Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
.student-card {
  box-sizing: border-box;
  width: auto;
  height: 100px;
  background: rgba(255, 255, 255, 0.79);
  border: 2px slid #dc3545;
  box-shadow: 12px 17px 51px rgba(0, 0, 0, 0.22);
  backdrop-filter: blur(6px);
  border-radius: 17px;
  text-align: center;
  cursor: pointer;
  transition: all 0.5s;
  align-items: center;
  justify-content: center;
  user-select: none;
  color: black;
  padding: 20px 5px 10px 5px;

}
.student-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
.student-card p { margin: 0; font-size: 13px; }
.student-card small { color: #8a0337; font-weight: 600; }

.icone {
    color: #148547;
    font-weight: 700; 
    text-align: right;
    margin-right: 10px;
    font-size: 18px;
}

</style>
</head>
<body class="bg-light">
<div class="">

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
                        <h4 class="h4 mb-0 text-gray-800">Tutor Allocation</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Add Tutor</h6>
                                </div>
                                <div class="card-body">

<!-- ------------------------------------------------------------------
-------------------------------------------------------------------- -->

<!-- MESSAGE BOX -->
<div id="msgBox"></div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <label>Programme</label>
    <select id="programme" class="form-select"></select>
  </div>
  <div class="col-md-4">
    <label>Batch</label>
    <select id="batch" class="form-select"></select>
  </div>
  <div class="col-md-4">
    <label>Session</label>
    <select id="session" class="form-select">
      <option value="">-- All / Unallocated --</option>
    </select>
  </div>
  <div class="col-md-6 mt-3">
    <label>Tutor</label>
    <select id="tutor" class="form-select">
      <option value="">-- Select Tutor --</option>
      <?php while($t=$tutors->fetch_assoc()){ ?>
        <option value="<?= $t['id'] ?>"><?= $t['username'] ?></option>
      <?php } ?>
    </select>
  </div>
</div>

<div class="col-12 mt-4" id="studentData"></div>


                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-search"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">Find Student | Edit Tutor Allocation</h6>
                                </div>
                                <div class="card-body">

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" style="z-index: 1100;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5>Edit Allocation</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_student">
         <label>Tutor</label>
        <select id="edit_tutor" class="form-select">
          <option value="" disabled hidden>Please Select the Tutor</option>
          <?php
          $tutors->data_seek(0);
          while($t=$tutors->fetch_assoc()){
              echo "<option value='{$t['id']}'>{$t['username']}</option>";
          }
          ?>
        </select>
        <label class="mt-2">Session</label>
        <select id="edit_session" class="form-select" style="display: none;"></select>
        <label style="color: #6c757d; font-size: 12px; font-style: italic;"> The 'Session' is locked for editing</label>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger" id="unallocate">Unallocate</button>
        <button class="btn btn-success" id="saveEdit">Save</button>
      </div>
    </div>
  </div>
</div>



<!-- Second PAGE -->

<!-- ================= ADVANCED FILTER (CARDS) ================= -->
<div class="row mt-5">
  <div class="col-12 mb-3">
    <input type="text" id="cardSearch" class="form-control mb-2" placeholder="Search student name or ID">
    <select id="cardSessionFilter" class="form-select mb-2">
      <option value="">-- All Sessions --</option>
    </select>
    <select id="cardTutorFilter" class="form-select">
      <option value="">-- All Tutors --</option>
    </select>
  </div>
</div>

<div class="row" id="studentCards"></div>

<!-- ================= CARD MODAL ================= -->
<div class="modal fade" id="studentCardModal">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 id="studentCardModalTitle"></h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="studentCardModalBody"></div>
    </div>
  </div>
</div>

</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>


$(document).ready(function(){



    $('#programme').select2({
        placeholder: "Search Programme",
        width: '100%'
    });

    $('#batch').select2({
        placeholder: "Search Batch",
        width: '100%'
    });

    $('#session').select2({
        placeholder: "Search Session",
        width: '100%'
    });

    $('#tutor').select2({
        placeholder: "Search Tutor",
        width: '100%'
    });

    $('#edit_tutor').select2({
        dropdownParent: $('#editModal'),
        width: '100%'
    });

    $('#edit_session').select2({
        dropdownParent: $('#editModal'),
        width: '100%'
    });
    $('#edit_session').next('.select2-container').css('pointer-events', 'none');

});


let table;
let editModal = new bootstrap.Modal(document.getElementById('editModal'));

// Load programmes
// $.get('coordinate_session/fetch_programs_all_programs_without_uni.php', d=>{ $('#programme').html(d); });

// 🔥 Load programs based on user access
$.ajax({
    url: 'reports/AllStudents/fetch_user_programs.php',
    type: 'POST',
    success: function (data) {

        // Destroy select2 before updating
        $('#programme').select2('destroy');

        // Replace options
        $('#programme').html(data);

        // Reinitialize select2
        $('#programme').select2({
            placeholder: "Search Programme",
            width: '100%'
        });

        // Reset dependent dropdowns
        $('#batch').html('<option value="">Select Batch</option>');
        $('#session').html('<option value="">-- All / Unallocated --</option>');
    }
});

// Programme change
$('#programme').change(function(){
    let program_id=$(this).val();
    $.post('coordinate_session/fetch_batches_without_uni.php',{program_id},d=>{ $('#batch').html(d); });
    $.post('coordinate_session/fetch_sessions_by_program.php',{program_id},res=>{
        let opt='<option value="">-- All / Unallocated --</option>';
        res.forEach(r=>{ opt+=`<option value="${r.session_id}">${r.session_name}</option>`; });
        $('#session,#edit_session').html(opt);
    },'json');
});

// Load table on change
$('#programme,#batch,#session').change(loadStudents);

function loadStudents(){
    let program_id=$('#programme').val();
    let batch_id=$('#batch').val();
    let session_id=$('#session').val();
    if(!program_id || !batch_id) return;

    $.post('coordinate_session/fetch_students.php',{program_id,batch_id,session_id},function(res){
        let html=`<form id="saveForm">
        <table class="table table-striped" id="tbl">
        <thead class="table-dark">
            <tr>
                <th class="text-center" >#</th>
                <th class="text-center" >STU. ID</th>
                <th class="text-center" >Name</th>
                <th class="text-center" >Allocate</th>
                <th class="text-center" >Session</th>
                <th class="text-center" >Tutor</th>
                <th class="text-center" >Edit</th>
            </tr>
        </thead>
        <tbody>`;

        res.students.forEach((s,i)=>{
            let editBtn = '';
            if(s.allocated && s.allocate_id){
                editBtn = `<button type="button" class="btn btn-warning btn-sm editBtn" 
                    data-allocate_id="${s.allocate_id}" 
                    data-tutor="${s.tutor_id}" 
                    data-session="${s.session_id}">
                    <i class="fa fa-edit"></i>
                </button>`;
            }

            html+=`<tr>
                <td>${i+1}</td>
                <td>${s.stu}</td>
                <td>${s.name}</td>
                <td class="text-center" style="min-width: 100px; margin-left: auto; margin-right: auto;">
                    <input type="checkbox" class="assign" ${s.allocated ? 'checked disabled' : ''}>
                    <input type="hidden" name="student_id[]" value="${s.student_code}">
                    <input type="hidden" name="program_id[]" value="${program_id}">
                    <input type="hidden" name="batch_id[]" value="${batch_id}">
                    <input type="hidden" name="created_by" value="<?= $current_user_id; ?>">
                    <input type="hidden" name="tutor_id[]" class="tid">
                    <input type="hidden" name="session_id[]" class="sid">
                </td>
                <td class="sessionText" style="min-width: 100px;">${s.session_name || ''}</td>
                <td class="tutorText" style="min-width: 100px;">${s.tutor_name || ''}</td>
                <td style="min-width: 100px;">${editBtn}</td>
            </tr>`;
        });

        html+=`</tbody></table><button class="btn btn-success mt-3"><i class="fa fa-save"></i> Save Allocation</button></form>`;
        $('#studentData').html(html);

        // if(table) table.destroy();
        // table=$('#tbl').DataTable();
    },'json');
}

// Checkbox logic
$(document).on('change','.assign',function(){
    let row=$(this).closest('tr');
    let tutor=$('#tutor').val();
    let session=$('#session').val();
    if(this.checked){
        if(!tutor || !session){ alert('Select tutor and session'); this.checked=false; return; }
        row.find('.tid').val(tutor); row.find('.sid').val(session);
        row.find('.tutorText').text($('#tutor option:selected').text());
        row.find('.sessionText').text($('#session option:selected').text());
    }else{
        row.find('.tid,.sid').val(''); row.find('.tutorText,.sessionText').text('');
    }
});

// SAVE allocation
$(document).on('submit','#saveForm',function(e){
    e.preventDefault();
    let valid=false;
    $('.assign:checked').each(function(){
        let row=$(this).closest('tr');
        if(row.find('.tid').val() && row.find('.sid').val()){ valid=true; }
    });
    if(!valid){ $('#msgBox').html(`<div class="alert alert-warning alert-dismissible fade show"><i class="fa fa-exclamation-triangle"></i> Please select at least one student to allocate <button class="btn-close" data-bs-dismiss="alert"></button></div>`); return; }
    $.post('coordinate_session/save_tutor_allocate.php',$(this).serialize(),function(){
        $('#msgBox').html(`<div class="alert alert-success alert-dismissible fade show"><i class="fa fa-check"></i> Tutor allocation saved successfully <button class="btn-close" data-bs-dismiss="alert"></button></div>`);
        loadStudents();
        loadStudentCards();
    });
});

// ---------------- EDIT / UNALLOCATE ----------------
$(document).on('click', '.editBtn, .editCard', function() {
    // 1. Get data from the button attributes
    let allocateId = $(this).data('allocate_id');
    let tutorId    = $(this).data('tutor');
    let sessionId  = $(this).data('session');

    // 2. Set the Hidden ID field
    $('#edit_student').val(allocateId);

    // 3. Set Tutor Select2 and trigger change to refresh UI
    $('#edit_tutor').val(tutorId).trigger('change');

    // 4. Set Session Select2 and trigger change to refresh UI
    $('#edit_session').val(sessionId).trigger('change');

    // 5. Show the modal
    editModal.show();
});

// SAVE EDIT
$('#saveEdit').click(function(){
    $.post('coordinate_session/edit_tutor_allocate.php',{
        allocate_id : $('#edit_student').val(),
        tutor_id    : $('#edit_tutor').val(),
        session_id  : $('#edit_session').val()
    }, function(res){
        editModal.hide();
        loadStudents();
        loadStudentCards();
    }, 'json');
});

// UNALLOCATE
$('#unallocate').click(function(){
    $.post('coordinate_session/edit_tutor_allocate.php',{
        allocate_id : $('#edit_student').val(),
        tutor_id    : '',
        session_id  : ''
    }, function(res){
        editModal.hide();
        loadStudents();
        loadStudentCards();
    }, 'json');
});

/* ================= ADVANCED FILTER FIX ================= */

function loadStudentCards(){
  let program_id=$('#programme').val();
  let batch_id=$('#batch').val();
  let search=$('#cardSearch').val();
  let sessionFilter=$('#cardSessionFilter').val();
  let tutorFilter=$('#cardTutorFilter').val();

  if(!program_id||!batch_id) return;

  $.post('coordinate_session/fetch_all_students_cards.php',{
    program_id,batch_id,search,sessionFilter,tutorFilter
  },res=>{
    let html='<div class="row">';
    res.students.forEach(s=>{
      html+=`
      
      <div class="col-md-2 pb-2">
        <div class="student-card" data-student="${s.student_code}">
        <small>${s.stu}</small>            <br>
        <p>${s.name}</p>

          <!-- <div class="icone"><i class="bi bi-award"></i></div> -->
                    
          <!-- <p>${s.student_code}</p>
          <small>${s.programme}<br>${s.batch_name} </small> -->
        </div>
      
      </div>`;
    });
    html += '</div>'; // close row
    $('#studentCards').html(html);

    let sessOpt=`<option value="">-- All Sessions --</option>`;
    res.sessions.forEach(s=>{
      let sel=s.session_id==sessionFilter?'selected':'';
      sessOpt+=`<option value="${s.session_id}" ${sel}>${s.session_name}</option>`;
    });
    $('#cardSessionFilter').html(sessOpt);

    let tutOpt=`<option value="">-- All Tutors --</option>`;
    res.tutors.forEach(t=>{
      let sel=t.id==tutorFilter?'selected':'';
      tutOpt+=`<option value="${t.id}" ${sel}>${t.username}</option>`;
    });
    $('#cardTutorFilter').html(tutOpt);
  },'json');
}

$('#programme,#batch').change(loadStudentCards);
$('#cardSearch').keyup(loadStudentCards);
$('#cardSessionFilter,#cardTutorFilter').change(loadStudentCards);

// Card click modal
$(document).on('click','.student-card', function() {
    let student_code = $(this).data('student');
    $.post('coordinate_session/fetch_student_allocations.php',{ student_code }, function(res){
        let html=`<table class="table table-bordered"><thead class="table-dark"><tr><th>#</th><th>Session</th><th>Tutor</th><th>Edit</th></tr></thead><tbody>`;
        res.allocations.forEach((a,i)=>{ 
            html+=`<tr>
                <td>${i+1}</td>
                <td>${a.session_name||''}</td>
                <td>${a.tutor_name||''}</td>
                <td><button class="btn btn-warning btn-sm editCard" data-allocate_id="${a.id}" data-tutor="${a.tutor_id}" data-session="${a.session_id}"><i class="fa fa-edit"></i></button></td>
            </tr>`; 
        });
        html+=`</tbody></table>`;
        $('#studentCardModalBody').html(html);
        $('#studentCardModalTitle').text(res.student_name + ' (' + student_code + ')');
        $('#studentCardModal').modal('show');
    }, 'json');
});

// Edit from card modal
$(document).on('click','.editCard', function(){
    $('#edit_student').val($(this).data('allocate_id'));
    $('#edit_tutor').val($(this).data('tutor'));
    $('#edit_session').val($(this).data('session'));
    editModal.show();
});
</script>
</body>
</html>
