<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

$is_admin = ($_SESSION['role'] === 'super_admin'); 
$logged_in_tutor_id = $_SESSION['user_id'];
$Session_username = $_SESSION['username'];

require_once 'PermissionChecking.php';

$all_tutors = [];
if ($is_admin) {
    $tutor_query = mysqli_query($conn, "SELECT id, username, admin_email FROM admin WHERE role = 'lecture' OR role = 'lecturer'"); 
    while($row = mysqli_fetch_assoc($tutor_query)) {
        $all_tutors[] = $row;
    }
}

include("includes/header.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tutor Slots Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <style>
        .slot-date { font-weight: bold; margin-top: 20px; font-size: 1.1rem; }
        .slot-table { margin-bottom: 20px; }
        /* Fix for modal layering */
       /* Base Bootstrap fix */
            /* .modal-backdrop {
                z-index: 1050 !important;
            } */

            .modal {
                z-index: 1055 !important;
            }

/* Ensure stacking order */
#reasonModal { z-index: 1060 !important; }
#messageModal { z-index: 1070 !important; }
#addStudentModal { z-index: 1080 !important; }
    </style>
</head>

<body class="bg-light">
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Allocated Time Slots</h4>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0">Filter Assignments</h6>
                    </div>
                    <div class="card-body">
                        <?php if($is_admin): ?>
                        <div class="alert alert-info mb-4">
                            <label class="fw-bold">Select Lecturer to Manage:</label>
                            <select id="adminTutorSelect" class="form-select select2">
                                <option value="<?= $logged_in_tutor_id ?>">-- My Own Slots (Default) --</option>
                                <?php foreach($all_tutors as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['username']) ?> (<?= htmlspecialchars($t['admin_email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Program</label>
                                <select id="program" class="form-select">
                                    <option value="">Select Program</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Batch</label>
                                <select id="batch" class="form-select" disabled>
                                    <option value="">Select Batch</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Session</label>
                                <select id="session" class="form-select" disabled>
                                    <option value="">Select Session</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="slots"></div>
            </div>
        </div>
    </div>
</div>

<!-- Input Modal -->
<div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Reason (optional):</p>
                <textarea id="reasonInput" class="form-control" rows="3" placeholder="Enter reason if any"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmActionBtn" class="btn btn-primary">Yes, Proceed</button>
            </div>
        </div>
    </div>
</div>

<!-- Global Message Modal (Top Level) -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="messageTitle">Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p id="messageContent"></p></div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white" style="background-color: #042d5c;">
                <h5 class="modal-title">Allocate Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="searchStudent" class="form-control mb-3" placeholder="Search student...">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="studentTable">
                        <thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
let activeTutorId = "<?= $logged_in_tutor_id ?>";
const program = document.getElementById('program');
const batch = document.getElementById('batch');
const session = document.getElementById('session');
const slots = document.getElementById('slots');

let currentSlotId = null;
let currentAction = null;
let currentStudentId = null;
let addStudentSlotId = null;

function resetVariables() {
    currentSlotId = null;
    currentAction = null;
    currentStudentId = null;
    addStudentSlotId = null;
    document.getElementById('reasonInput').value = '';
}

$(document).ready(function () {
    $('.select2').select2();
    loadPrograms();

    $('#adminTutorSelect').on('change', function () {
        activeTutorId = $(this).val();
        resetFilters();
        loadPrograms();
    });

    program.addEventListener('change', () => { 
        if (program.value) loadBatch(program.value); 
        slots.innerHTML = '';
    });

    batch.addEventListener('change', () => { 
        if (batch.value) loadSession(program.value, batch.value); 
        slots.innerHTML = '';
    });

    session.addEventListener('change', () => { 
        if (session.value) loadSlots(); 
    });

    $('#searchStudent').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $("#studentTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Unified Click Handler
    document.getElementById('confirmActionBtn').addEventListener('click', function() { 
        if (!currentSlotId) return; 
        
        const btn = this;
        btn.disabled = true; 
        btn.innerText = 'Processing...'; 

        const reason = document.getElementById('reasonInput').value; 
        const useOptionEndpoint = ['allocate', 'add_student', 'cancel_pending'].includes(currentAction);
        const endpoint = useOptionEndpoint ? 'tutortime/tutor_option.php' : 'tutortime/tutor_actions.php';
        
        const formData = new URLSearchParams(); 
        formData.append('slot_id', currentSlotId); 
        formData.append('action', currentAction); 
        formData.append('reason', reason); 
        formData.append('tutor_id', activeTutorId);
        if (currentStudentId) formData.append('student_id', currentStudentId); 

        fetch(endpoint, { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, 
            body: formData.toString() 
        }) 
        .then(res => res.text())
        .then(text => {
            const cleanJson = text.trim(); 
            try {
                const data = JSON.parse(cleanJson);

                // --- FIX START: Fallback for undefined messages ---
                const successMsg = data.message || "Action completed successfully.";
                const errorMsg = data.message || "An unknown error occurred on the server.";
                // --- FIX END ---

                if (data.status === 'success') { 
                    finalizeAction();
                    showMessage('Success', successMsg); // Uses fallback
                } else { 
                    handleError(errorMsg); // Uses fallback
                }
            } catch (e) {
                console.error("Server Sent This Data:", text);
                handleError("Server Response Error. Check console for details.");
            }
        }) 
        .catch(err => {
            console.error(err);
            handleError('Connection failed');
        });
    }); 
}); // End of $(document).ready

function loadSlots() {
    if (!program.value || !batch.value || !session.value) return;
    const ts = new Date().getTime();
    fetch(`tutortime/load_slots.php?program_id=${program.value}&batch_id=${batch.value}&session_id=${session.value}&tutor_id=${activeTutorId}&_=${ts}`)
        .then(res => res.text())
        .then(html => { slots.innerHTML = html; });
}

function finalizeAction() {
    document.getElementById('confirmActionBtn').disabled = false;
    document.getElementById('confirmActionBtn').innerText = 'Yes, Proceed'; 
    const reasonModalEl = document.getElementById('reasonModal');
    const reasonInstance = bootstrap.Modal.getInstance(reasonModalEl);
    if (reasonInstance) reasonInstance.hide();
    loadSlots(); 
    resetVariables();
}

function handleError(msg) {
    document.getElementById('confirmActionBtn').disabled = false;
    document.getElementById('confirmActionBtn').innerText = 'Yes, Proceed';
    showMessage('Error', msg);
}

function showMessage(title, msg) {
    const reasonModalEl = document.getElementById('reasonModal');
    const reasonInstance = bootstrap.Modal.getInstance(reasonModalEl);
    if (reasonInstance) reasonInstance.hide();

    document.getElementById('messageTitle').innerText = title;
    document.getElementById('messageContent').innerText = msg;
    
    setTimeout(() => {
        const msgModal = new bootstrap.Modal(document.getElementById('messageModal'));
        msgModal.show();
    }, 300);
}

function loadPrograms() {
    fetch(`tutortime/tutor_actions.php?type=get_programs_by_tutor&tutor_id=${activeTutorId}`)
        .then(res => res.json())
        .then(data => {
            let opt = '<option value="">Select Program</option>';
            data.forEach(p => opt += `<option value="${p.program_code}">${p.program_name}</option>`);
            program.innerHTML = opt;
        });
}

function loadBatch(prog) {
    batch.innerHTML = '<option>Loading...</option>';
    fetch(`tutortime/tutor_actions.php?type=batch&program_id=${prog}&tutor_id=${activeTutorId}`)
        .then(res => res.json())
        .then(data => {
            let opt = '<option value="">Select Batch</option>';
            data.forEach(b => opt += `<option value="${b.batch_id}">${b.batch_name}</option>`);
            batch.innerHTML = opt;
            batch.disabled = false;
        });
}

function loadSession(prog, batchId) {
    session.innerHTML = '<option>Loading...</option>';
    fetch(`tutortime/tutor_actions.php?type=session&program_id=${prog}&batch_id=${batchId}&tutor_id=${activeTutorId}`)
        .then(res => res.json())
        .then(data => {
            let opt = '<option value="">Select Session</option>';
            data.forEach(s => opt += `<option value="${s.session_id}">${s.session_name}</option>`);
            session.innerHTML = opt;
            session.disabled = false;
        });
}

function openAddStudent(slotId) {
    addStudentSlotId = slotId;
    new bootstrap.Modal(document.getElementById('addStudentModal')).show();
    loadStudents();
}

function loadStudents() {
    fetch(`tutortime/tutor_actions.php?type=students&program_id=${program.value}&batch_id=${batch.value}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(s => {
                html += `<tr>
                    <td>${s.first_name} ${s.last_name}</td>
                    <td>${s.bms_email}</td>
                    <td><button class="btn btn-success btn-sm" onclick="assignStudent('${s.student_code}', '${s.first_name} ${s.last_name}', '${s.bms_email}')">Select</button></td>
                </tr>`;
            });
            document.querySelector('#studentTable tbody').innerHTML = html;
        });
}

function assignStudent(studentId, name, email) {
    currentStudentId = studentId;
    currentAction = 'add_student';
    currentSlotId = addStudentSlotId;
    document.getElementById('modalTitle').innerText = "Confirm Student Allocation";
    document.getElementById('modalMessage').innerHTML = `<strong>Name:</strong> ${name}<br><strong>Email:</strong> ${email}<br><br>Are you sure?`;
    
    const addStudentModalEl = document.getElementById('addStudentModal');
    const addStudentInstance = bootstrap.Modal.getInstance(addStudentModalEl);
    if (addStudentInstance) addStudentInstance.hide();
    
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('reasonModal')).show();
    }, 300);
}

function confirmAction(slotId, action) {
    currentSlotId = slotId;
    const card = event.target.closest('.product-plan');
    const hasStudent = card.querySelector('.name').innerText.trim() !== 'Pending';
    if (action === 'cancelled') {
        currentAction = hasStudent ? 'cancelled' : 'cancel_pending';
        document.getElementById('modalTitle').innerText = hasStudent ? "Cancel Booking" : "Cancel Slot";
        document.getElementById('modalMessage').innerHTML = hasStudent ? "Cancel and notify student?" : "Cancel this slot?";
    } else if (action === 'denied') {
        currentAction = 'denied';
        document.getElementById('modalTitle').innerText = "Deny Student";
        document.getElementById('modalMessage').innerText = "Are you sure?";
    }
    new bootstrap.Modal(document.getElementById('reasonModal')).show();
}

function resetFilters() {
    program.innerHTML = '<option value="">Select Program</option>';
    batch.innerHTML = '<option value="">Select Batch</option>';
    session.innerHTML = '<option value="">Select Session</option>';
    batch.disabled = true;
    session.disabled = true;
    slots.innerHTML = '';
}
</script>

</body>
</html>