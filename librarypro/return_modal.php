<?php
// DYNAMIC DATABASE CONNECTOR WATCHDOG
if (!isset($conn)) {
    $paths = [
        __DIR__ . '/../database/connection.php',
        __DIR__ . '/database/connection.php',
        '../database/connection.php',
        'database/connection.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            include($path);
            break;
        }
    }
}

// Set Sri Lanka timezone
date_default_timezone_set('Asia/Colombo');

// 1. AJAX BACKEND NODE CONNECTOR
if (isset($_GET['action']) && isset($_GET['student_input'])) {
    $student_input = trim($_GET['student_input']);
    $borrower_code = "";
    $borrower_type = isset($_GET['borrower_type']) ? $_GET['borrower_type'] : 'student';

    if (isset($conn) && !$conn->connect_error) {
        if ($borrower_type == 'student') {
            if (strpos($student_input, ' | ') !== false) {
                $parts = explode(' | ', $student_input);
                $reg_id = $conn->real_escape_string(trim($parts[0]));
                $nic_id = $conn->real_escape_string(trim($parts[1]));
                $sql = "SELECT s.student_code FROM students s 
                        LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                        WHERE ap.student_registration_id = '$reg_id' AND s.nic = '$nic_id'";
                $q = $conn->query($sql);
                if ($q && $q->num_rows > 0) {
                    $r = $q->fetch_assoc();
                    $borrower_code = $r['student_code'];
                }
            } else {
                $escaped = $conn->real_escape_string($student_input);
                $sql = "SELECT s.student_code FROM students s 
                        LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                        WHERE s.student_code = '$escaped' OR ap.student_registration_id = '$escaped' OR s.nic = '$escaped'";
                $q = $conn->query($sql);
                if ($q && $q->num_rows > 0) {
                    $r = $q->fetch_assoc();
                    $borrower_code = $r['student_code'];
                }
            }
        } else {
            // Staff borrower
            if (strpos($student_input, ' | ') !== false) {
                $parts = explode(' | ', $student_input);
                $staff_name = $conn->real_escape_string(trim($parts[0]));
                $staff_nic = $conn->real_escape_string(trim($parts[1]));
                $sql = "SELECT id FROM admin WHERE full_name = '$staff_name' AND nic = '$staff_nic'";
                $q = $conn->query($sql);
                if ($q && $q->num_rows > 0) {
                    $r = $q->fetch_assoc();
                    $borrower_code = $r['id'];
                }
            } else {
                $escaped = $conn->real_escape_string($student_input);
                $sql = "SELECT id FROM admin WHERE full_name LIKE '%$escaped%' OR nic LIKE '%$escaped%'";
                $q = $conn->query($sql);
                if ($q && $q->num_rows > 0) {
                    $r = $q->fetch_assoc();
                    $borrower_code = $r['id'];
                }
            }
        }
    }

    // ACTION A: Fetch dynamically rendered HTML data table elements
    if ($_GET['action'] == 'get_borrowed_books') {
        if (!isset($conn) || $conn->connect_error) {
            echo "<tr><td colspan='4' class='text-center text-danger py-3'><i class='fa fa-exclamation-triangle mr-1'></i> Database link broken. Can't sync active items.</td></tr>";
            exit();
        }

        if (!empty($borrower_code)) {
            if ($borrower_type == 'student') {
                $sql = "SELECT b.id AS borrow_id, bk.accession_number, bk.title, b.date_borrow 
                        FROM borrow b 
                        JOIN books bk ON b.book_id = bk.id 
                        WHERE b.student_id = '$borrower_code' AND b.borrower_type = 'student' AND b.status = 0 
                        ORDER BY b.date_borrow DESC";
            } else {
                $sql = "SELECT b.id AS borrow_id, bk.accession_number, bk.title, b.date_borrow 
                        FROM borrow b 
                        JOIN books bk ON b.book_id = bk.id 
                        WHERE b.student_id = '$borrower_code' AND b.borrower_type = 'staff' AND b.status = 0 
                        ORDER BY b.date_borrow DESC";
            }
            $query = $conn->query($sql);
            
            if ($query && $query->num_rows > 0) {
                while ($row = $query->fetch_assoc()) {
                    echo "
                    <tr id='row_".htmlspecialchars($row['accession_number'])."'>
                        <td class='text-center align-middle'>
                            <input type='checkbox' class='form-check-input return-checkbox' name='accession_number[]' value='".htmlspecialchars($row['accession_number'])."'>
                        </td>
                        <td>".htmlspecialchars($row['accession_number'])."</td>
                        <td>".htmlspecialchars($row['title'])."</td>
                        <td>".date('M d, Y', strtotime($row['date_borrow']))."</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='4' class='text-center text-muted py-3'>No active checked-out items found for this borrower.</td></tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center text-danger py-3'>Invalid borrower data reference point.</td></tr>";
        }
        exit();
    }

    // ACTION B: Fetch context-aware JSON datasets strictly for dropdown mapping routines
    if ($_GET['action'] == 'get_borrowed_dropdown_json') {
        $borrowed_items_array = [];
        if (isset($conn) && !$conn->connect_error && !empty($borrower_code)) {
            if ($borrower_type == 'student') {
                $sql = "SELECT bk.accession_number, bk.title 
                        FROM borrow b 
                        JOIN books bk ON b.book_id = bk.id 
                        WHERE b.student_id = '$borrower_code' AND b.borrower_type = 'student' AND b.status = 0";
            } else {
                $sql = "SELECT bk.accession_number, bk.title 
                        FROM borrow b 
                        JOIN books bk ON b.book_id = bk.id 
                        WHERE b.student_id = '$borrower_code' AND b.borrower_type = 'staff' AND b.status = 0";
            }
            $query = $conn->query($sql);
            if ($query) {
                while ($row = $query->fetch_assoc()) {
                    $borrowed_items_array[] = $row;
                }
            }
        }
        header('Content-Type: application/json');
        echo json_with_fallback($borrowed_items_array);
        exit();
    }
}

// Polyfill fallback layer block for alternative environment compilation targets
function json_with_fallback($data) {
    if (function_exists('json_encode')) { return json_encode($data); }
    $parts = [];
    foreach ($data as $item) {
        $parts[] = '{"accession_number":"'.addslashes($item['accession_number']).'","title":"'.addslashes($item['title']).'"}';
    }
    return '[' . implode(',', $parts) . ']';
}
?>

<?php if (!isset($conn) || $conn->connect_error): ?>
    <div class="alert alert-danger mx-3 my-2 shadow-sm" role="alert">
        <strong><i class="fa fa-exclamation-triangle mr-2"></i>Database Connection Error:</strong> 
        <?php echo isset($conn) ? htmlspecialchars($conn->connect_error) : 'The database connection handler instance object ($conn) remains missing. Check paths.'; ?>
    </div>
<?php endif; ?>

<style>
/* Fix for z-index issues */
.dropdown {
    position: relative !important;
    z-index: 1050 !important;
}

.dropdown-menu {
    position: absolute !important;
    z-index: 9999 !important;
    background: white !important;
    border: 1px solid rgba(0,0,0,.15) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

#students_dropdown_menu, #staff_dropdown_menu {
    z-index: 9999 !important;
    position: absolute !important;
    background: white !important;
    border: 1px solid rgba(0,0,0,.15) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    max-height: 200px !important;
    overflow-y: auto !important;
}

.target-contextual-menu {
    z-index: 9999 !important;
    position: absolute !important;
    background: white !important;
    border: 1px solid rgba(0,0,0,.15) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    max-height: 200px !important;
    overflow-y: auto !important;
}

/* Fix for table container to prevent overflow clipping */
.table-responsive {
    overflow: visible !important;
}

#borrowed_books_card .table-responsive {
    overflow-y: auto !important;
    overflow-x: visible !important;
    max-height: 220px !important;
}

/* Fix for modal body overflow */
.modal-body {
    overflow: visible !important;
}

.modal-content {
    overflow: visible !important;
}

.highlight-match-row {
    background-color: #d1e7dd !important;
    transition: background-color 0.3s ease-in-out;
}

.return-checkbox {
    pointer-events: none; 
}

#borrowed_books_table_body tr {
    cursor: pointer;
}

/* Ensure dropdown items are clickable */
.dropdown-item {
    cursor: pointer !important;
    z-index: 9999 !important;
}

/* Fix for the table header sticky positioning */
.sticky-top {
    position: sticky !important;
    top: 0 !important;
    z-index: 100 !important;
}

/* Ensure the card doesn't clip dropdowns */
.card {
    overflow: visible !important;
}

.card-body {
    overflow: visible !important;
}

/* Fix for position relative containers */
.position-relative {
    position: relative !important;
    z-index: 1050 !important;
}

/* Ensure the modal dialog doesn't clip content */
.modal-dialog {
    overflow: visible !important;
}

.modal-dialog-centered {
    overflow: visible !important;
}
</style>

<div class="modal fade" id="addnew" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="overflow: visible !important;">
        <div class="modal-content border-0 shadow-lg" style="overflow: visible !important;">
            <div class="modal-header text-white" style="background-color: #043927;">
                <h5 class="modal-title" id="returnModalLabel">
                    <i class="fa fa-reply mr-2"></i><b>Return Books</b>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" id="returnFormMaster" method="POST" action="librarypro/return_add.php">
                <div class="modal-body p-4" style="font-size: 0.875rem; overflow: visible !important;">
                    
                    <!-- Radio Button for Student/Staff Selection -->
                    <div class="form-group row align-items-center mb-3">
                        <label class="col-sm-3 font-weight-bold text-gray-700 m-0">Borrower Type</label>
                        <div class="col-sm-9">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-danger btn-sm active" id="student_radio_label">
                                    <input type="radio" name="borrower_type" id="student_type" value="student" checked> Student
                                </label>
                                <label class="btn btn-outline-dark btn-sm" id="staff_radio_label">
                                    <input type="radio" name="borrower_type" id="staff_type" value="staff"> Staff
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-4">
                        <label for="student" class="col-sm-3 font-weight-bold text-gray-700 m-0">Borrower Info</label>
                        <div class="col-sm-9 position-relative dropdown" style="position: relative !important; z-index: 1050 !important;">
                            <input type="text" class="form-control form-control-sm" id="student" name="student" placeholder="Type Registration ID, NIC or Name..." autocomplete="off" required>
                            
                            <!-- Student Dropdown Menu - Only show students with active borrowings -->
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto" style="max-height: 200px; font-size: 0.825rem; display: none; position: absolute; z-index: 9999; background: white; border: 1px solid rgba(0,0,0,.15); box-shadow: 0 4px 12px rgba(0,0,0,0.15);" id="students_dropdown_menu">
                                <?php if(isset($students_list) && is_array($students_list) && count($students_list) > 0): ?>
                                    <?php foreach($students_list as $st): ?>
                                        <?php 
                                            $disp_id = !empty($st['student_registration_id']) ? $st['student_registration_id'] : 'No Reg ID';
                                            $disp_nic = !empty($st['nic']) ? $st['nic'] : 'No NIC';
                                            $disp_name = trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? ''));
                                            $full_line = $disp_id . " | " . $disp_nic . " | " . $disp_name;
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark student-selection-item" href="#" data-value="<?php echo htmlspecialchars($full_line); ?>" data-type="student">
                                                <div class="mb-0 text-dark font-weight-bold"><?php echo htmlspecialchars($disp_id); ?></div>
                                                <small class="text-muted"> <?php echo htmlspecialchars($disp_nic); ?> &bull; | &bull; <?php echo htmlspecialchars($disp_name); ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="#">No students with active borrowings</a></li>
                                <?php endif; ?>
                            </ul>
                            
                            <!-- Staff Dropdown Menu - Only show staff with active borrowings -->
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto" style="max-height: 200px; font-size: 0.825rem; display: none; position: absolute; z-index: 9999; background: white; border: 1px solid rgba(0,0,0,.15); box-shadow: 0 4px 12px rgba(0,0,0,0.15);" id="staff_dropdown_menu">
                                <?php if(isset($staff_list) && is_array($staff_list) && count($staff_list) > 0): ?>
                                    <?php foreach($staff_list as $sf): ?>
                                        <?php 
                                            $staff_name = !empty($sf['full_name']) ? $sf['full_name'] : 'No Name';
                                            $staff_nic = !empty($sf['nic']) ? $sf['nic'] : 'No NIC';
                                            $full_line = $staff_name . " | " . $staff_nic;
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark student-selection-item" href="#" data-value="<?php echo htmlspecialchars($full_line); ?>" data-type="staff">
                                                <div class="mb-0 text-dark font-weight-bold"><?php echo htmlspecialchars($staff_name); ?></div>
                                                <small class="text-muted"> <?php echo htmlspecialchars($staff_nic); ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="#">No staff with active borrowings</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card border mb-4 shadow-sm d-none" id="borrowed_books_card" style="overflow: visible !important;">
                        <div class="card-header py-2 text-dark font-weight-bold bg-light" style="font-size: 0.825rem;">
                            <i class="fa fa-list mr-1"></i> Currently Borrowed Items Matrix (Click Row to Select)
                        </div>
                        <div class="table-responsive" style="max-height: 220px; overflow-y: auto !important; overflow-x: visible !important;">
                            <table class="table table-sm table-hover table-striped mb-0 text-dark" style="font-size: 0.825rem;">
                                <thead class="bg-white sticky-top shadow-sm" style="position: sticky; top: 0; z-index: 100;">
                                    <tr>
                                        <th class="text-center" style="width: 10%;">Return</th>
                                        <th style="width: 25%;">Accession No.</th>
                                        <th style="width: 45%;">Book Title</th>
                                        <th style="width: 20%;">Date Borrowed</th>
                                    </tr>
                                </thead>
                                <tbody id="borrowed_books_table_body">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="accession_number" class="col-sm-3 font-weight-bold text-gray-700 m-0">Accession Number</label>
                        <div class="col-sm-9 position-relative dropdown" style="position: relative !important; z-index: 1 !important;">
                            <input type="text" class="form-control form-control-sm bs5-book-input" id="accession_number" name="accession_number[]" placeholder="Type Accession Number or Book Title to search..." autocomplete="off">
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto target-contextual-menu" style="max-height: 200px; font-size: 0.825rem; display: none; position: absolute; z-index: 9999; background: white; border: 1px solid rgba(0,0,0,.15); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                            </ul>
                        </div>
                    </div>

                    <div id="append-div"></div>

                    <div class="form-group row mt-3">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill" id="dynamic-append-btn">
                                <i class="fa fa-plus fa-xs mr-1"></i> Add Another Book Field
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-sm px-3-clear-run" data-dismiss="modal">
                        <i class="fa fa-close mr-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-sm rounded-sm px-3 shadow text-white" name="add" style="background-color: #043927;">
                        <i class="fa fa-save mr-1"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    var inputRowMappings = {};
    var cachedStudentBorrowedBooks = []; // Holds the active borrower selection's books subset globally
    var currentBorrowerType = 'student'; // Track current borrower type

    // CACHE PURGE SYSTEM
    function purgeCacheAndRunFresh() {
        $('#returnFormMaster')[0].reset();
        $('#append-div').empty();
        $('.dropdown-menu').removeClass('show').hide();
        $('#borrowed_books_card').addClass('d-none');
        $('#borrowed_books_table_body').empty();
        inputRowMappings = {};
        cachedStudentBorrowedBooks = [];
        currentBorrowerType = 'student';
        
        // Reset to student view
        $('#students_dropdown_menu').hide();
        $('#staff_dropdown_menu').hide();
        $('#student_radio_label').addClass('active');
        $('#staff_radio_label').removeClass('active');
        $('#student').attr('placeholder', 'Type Registration ID, NIC or Name...');
        
        if (window.location.hash == '#addnew') {
            window.location.hash = '';
        }
    }

    // Reset ONLY when modal closes
    $('#addnew').on('hidden.bs.modal', function () {
        purgeCacheAndRunFresh();
    });

    $('.px-3-clear-run').on('click', function(e) {
        e.preventDefault();
        purgeCacheAndRunFresh();
        $('#addnew').modal('hide');
    });

    $('#returnFormMaster').on('submit', function() {
        setTimeout(function() {
            purgeCacheAndRunFresh();
        }, 400);
    });

    // Radio button toggle functionality
    $(document).on('change', 'input[name="borrower_type"]', function () {
        $('#student').val('');
        $('.dropdown-menu').removeClass('show').hide();
        $('#borrowed_books_card').addClass('d-none');
        $('#borrowed_books_table_body').empty();
        cachedStudentBorrowedBooks = [];
        currentBorrowerType = $(this).val();
        
        if ($(this).val() === 'student') {
            $('#students_dropdown_menu').show();
            $('#staff_dropdown_menu').hide();
            $('#student').attr('placeholder', 'Type Registration ID, NIC or Name...');
        } else {
            $('#staff_dropdown_menu').show();
            $('#students_dropdown_menu').hide();
            $('#student').attr('placeholder', 'Type Staff Name or NIC...');
        }
        
        $('#student').focus();
    });

    // Dynamic Row Field Generator Hook
    $('#dynamic-append-btn').on('click', function(e) {
        e.preventDefault();
        var dynamicFieldHtml = `
            <div class="form-group row align-items-center mb-3">
                <label class="col-sm-3 font-weight-bold text-gray-700 m-0">Accession Number</label>
                <div class="col-sm-9 position-relative dropdown" style="position: relative !important; z-index: 1050 !important;">
                    <input type="text" class="form-control form-control-sm bs5-book-input" name="accession_number[]" placeholder="Type Accession Number or Book Title to search..." autocomplete="off">
                    <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto target-contextual-menu" style="max-height: 200px; font-size: 0.825rem; display: none; position: absolute; z-index: 9999; background: white; border: 1px solid rgba(0,0,0,.15); box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></ul>
                </div>
            </div>`;
        $('#append-div').append(dynamicFieldHtml);
        rebuildBookDropdownsUI();
    });

    // Student Lookup Field Filtering Layout Engine
    $('#student').on('keyup focus input', function() {
        var value = $(this).val().toLowerCase();
        var borrowerType = $('input[name="borrower_type"]:checked').val();
        var dropdownMenu = borrowerType === 'staff' ? $('#staff_dropdown_menu') : $('#students_dropdown_menu');
        
        $('#students_dropdown_menu,#staff_dropdown_menu').removeClass('show').hide();
        
        var matchCount = 0;
        dropdownMenu.find('li').each(function() {
            if ($(this).text().toLowerCase().indexOf(value) > -1) {
                $(this).show();
                matchCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (matchCount > 0 && value.length > 0) {
            dropdownMenu.show().addClass('show');
        }
    });

    // Select Student/Staff Selection Event Hook
    $(document).on('click', '.student-selection-item', function(e) {
        e.preventDefault();
        var selectedVal = $(this).attr('data-value');
        var borrowerType = $(this).attr('data-type') || $('input[name="borrower_type"]:checked').val();
        
        $('#student').val(selectedVal);
        $('#students_dropdown_menu,#staff_dropdown_menu').removeClass('show').hide();

        // Fetch visual list data grid via standard AJAX endpoint template block
        $.ajax({
            url: 'librarypro/return_modal.php',
            type: 'GET',
            data: { 
                action: 'get_borrowed_books', 
                student_input: selectedVal,
                borrower_type: borrowerType
            },
            success: function(response) {
                $('#borrowed_books_table_body').html(response);
                $('#borrowed_books_card').removeClass('d-none');
                inputRowMappings = {}; 
            }
        });

        // Pull Contextual Json Arrays strictly linked directly back to this specific account number instance
        $.ajax({
            url: 'librarypro/return_modal.php',
            type: 'GET',
            data: { 
                action: 'get_borrowed_dropdown_json', 
                student_input: selectedVal,
                borrower_type: borrowerType
            },
            dataType: 'json',
            success: function(jsonResponse) {
                cachedStudentBorrowedBooks = jsonResponse;
                rebuildBookDropdownsUI();
            }
        });
    });

    // Rebuild the dropdown HTML elements using the localized array entries subset mapping arrays
    function rebuildBookDropdownsUI() {
        $('.target-contextual-menu').each(function() {
            var menu = $(this);
            menu.empty();
            if (cachedStudentBorrowedBooks.length > 0) {
                $.each(cachedStudentBorrowedBooks, function(i, item) {
                    var displayLine = item.accession_number + " | " + item.title;
                    menu.append(`
                        <li>
                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark book-selection-item" href="#" data-value="${item.accession_number}">
                                <span>${displayLine}</span>
                            </a>
                        </li>`);
                });
            } else {
                menu.append('<li><span class="dropdown-item-text text-muted py-2">No items to list</span></li>');
            }
        });
    }

    // Unified Accession Text Box Content Sync & State Validator Loop Engine
    function syncAllInputsToTableState() {
        $('#borrowed_books_table_body tr').removeClass('highlight-match-row');
        $('#borrowed_books_table_body tr .return-checkbox').prop('checked', false);

        $('.bs5-book-input').each(function() {
            var currentVal = $(this).val().trim().toLowerCase();
            var currentInput = $(this);
            var inputId = currentInput.data('input-id');

            if (!inputId) {
                inputId = 'input_' + Math.random().toString(36).substr(2, 9);
                currentInput.data('input-id', inputId);
            }

            if (currentVal !== "") {
                var foundMatch = false;
                $('#borrowed_books_table_body tr').each(function() {
                    var rowId = $(this).attr('id');
                    if (rowId && rowId.replace('row_', '').toLowerCase() === currentVal) {
                        var realCode = rowId.replace('row_', '');
                        $(this).addClass('highlight-match-row');
                        $(this).find('.return-checkbox').prop('checked', true);
                        inputRowMappings[inputId] = realCode;
                        foundMatch = true;
                    }
                });
                if(!foundMatch) {
                    delete inputRowMappings[inputId];
                }
            } else {
                delete inputRowMappings[inputId];
            }
        });
    }

    // Capture explicit real-time physical keystrokes on barcode fields
    $(document).on('keyup change blur focus input', '.bs5-book-input', function() {
        var value = $(this).val().trim().toLowerCase();
        var currentInput = $(this);
        var container = currentInput.closest('.dropdown');
        var targetDropdown = container.find('.target-contextual-menu');
        
        var matchCount = 0;
        targetDropdown.find('li').each(function() {
            if ($(this).text().toLowerCase().indexOf(value) > -1) {
                $(this).show();
                matchCount++;
            } else {
                $(this).hide();
            }
        });
        
        if (matchCount > 0 && value.length > 0) {
            targetDropdown.show().addClass('show');
        } else {
            targetDropdown.removeClass('show').hide();
        }

        syncAllInputsToTableState();
    });

    // Handle Dropdown Book Item Selection Clicking Routines
    $(document).on('click', '.book-selection-item', function(e) {
        e.preventDefault();
        var targetValue = $(this).attr('data-value');
        var container = $(this).closest('.dropdown');
        var linkedInput = container.find('input');
        
        linkedInput.val(targetValue);
        container.find('.dropdown-menu').removeClass('show').hide();

        syncAllInputsToTableState();
    });

    // Intercept Row Clicking Mechanics to route back to text inputs dynamically
    $(document).on('click', '#borrowed_books_table_body tr', function(e) {
        var targetRow = $(this);
        var accessionCode = targetRow.attr('id').replace('row_', '');
        
        var alreadyAdded = false;
        $('.bs5-book-input').each(function() {
            if ($(this).val().trim() === accessionCode) {
                alreadyAdded = true;
                $(this).val(""); // Acts as a clean toggle off switch structure
                return false;
            }
        });

        if (!alreadyAdded) {
            var emptyInputFound = false;
            $('.bs5-book-input').each(function() {
                if ($(this).val().trim() === "") {
                    $(this).val(accessionCode);
                    emptyInputFound = true;
                    return false;
                }
            });

            if (!emptyInputFound) {
                $('#dynamic-append-btn').trigger('click');
                $('.bs5-book-input').last().val(accessionCode);
            }
        }

        syncAllInputsToTableState();
    });

    // Close Dropdowns on Click Outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show').hide();
        }
    });
});
</script>