<div class="modal fade" id="addnew" tabindex="-3" role="dialog" aria-labelledby="borrowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="borrowModalLabel">
                    <i class="fa fa-book mr-2"></i><b>Borrow Books</b>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="POST" action="librarypro/borrow_add.php">
                <div class="modal-body p-4" style="font-size: 0.875rem;">
                    
                    <!-- Radio Button for Student/Staff Selection -->
                    <div class="form-group row align-items-center mb-3">
                        <label class="col-sm-4 font-weight-bold text-gray-700 m-0">Borrower Type</label>
                        <div class="col-sm-8">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-danger btn-sm active" id="student_radio_label" style="width: 70px;">
                                    <input type="radio" name="borrower_type" id="student_type" value="student" checked> Student
                                </label>
                                <label class="btn btn-outline-dark btn-sm" id="staff_radio_label"  style="width: 70px;">
                                    <input type="radio" name="borrower_type" id="staff_type" value="staff"> Staff
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="student" class="col-sm-4 font-weight-bold text-gray-700 m-0">Borrower Info</label>
                        <div class="col-sm-8 position-relative dropdown">
                            <input type="text" class="form-control form-control-sm bs5-search-input" id="student" name="student" placeholder="Type Registration ID, NIC or Name..." autocomplete="off" required>
                            
                            <!-- Student Dropdown Menu -->
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto bs5-custom-dropdown" style="max-height: 200px; font-size: 0.825rem; display: none;" id="students_dropdown_menu">
                                <?php if(isset($students_list) && is_array($students_list) && count($students_list) > 0): ?>
                                    <?php foreach($students_list as $st): ?>
                                        <?php 
                                            $disp_id = !empty($st['student_registration_id']) ? $st['student_registration_id'] : 'No Reg ID';
                                            $disp_nic = !empty($st['nic']) ? $st['nic'] : 'No NIC';
                                            $disp_name = trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? ''));
                                            $full_line = $disp_id . " | " . $disp_nic . " | " . $disp_name;
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark selection-item" href="#" data-value="<?php echo htmlspecialchars($full_line); ?>" data-type="student">
                                                <div class="mb-0 text-dark font-weight-bold"><?php echo htmlspecialchars($disp_id); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($disp_nic); ?> &bull; | &bull; <?php echo htmlspecialchars($disp_name); ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="#">No students found</a></li>
                                <?php endif; ?>
                            </ul>
                            
                            <!-- Staff Dropdown Menu -->
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto bs5-custom-dropdown" style="max-height: 200px; font-size: 0.825rem; display: none;" id="staff_dropdown_menu">
                                <?php if(isset($staff_list) && is_array($staff_list) && count($staff_list) > 0): ?>
                                    <?php foreach($staff_list as $sf): ?>
                                        <?php 
                                            $staff_name = !empty($sf['full_name']) ? $sf['full_name'] : 'No Name';
                                            $staff_nic = !empty($sf['nic']) ? $sf['nic'] : 'NIC Not Available';
                                            $staff_id = !empty($sf['id']) ? $sf['id'] : '';
                                            $full_line = $staff_name . " | " . $staff_nic;
                                        ?>
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark selection-item" href="#" data-value="<?php echo htmlspecialchars($full_line); ?>" data-type="staff" data-staff-id="<?php echo htmlspecialchars($staff_id); ?>">
                                                <div class="mb-0 text-dark font-weight-bold"><?php echo htmlspecialchars($staff_name); ?></div>
                                                <small class="text-muted"> <?php echo htmlspecialchars($staff_nic); ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="#">No staff members found</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="accession_number" class="col-sm-4 font-weight-bold text-gray-700 m-0">Accession Number</label>
                        <div class="col-sm-8 position-relative dropdown">
                            <input type="text" class="form-control form-control-sm bs5-book-input" id="accession_number" name="accession_number[]" placeholder="Enter Accession Number" autocomplete="off" required>
                            
                            <ul class="dropdown-menu w-100 shadow-sm mt-1 overflow-auto bs5-custom-dropdown" style="max-height: 200px; font-size: 0.825rem; display: none;" id="books_dropdown_menu">
                                <?php if(isset($books_list) && is_array($books_list) && count($books_list) > 0): ?>
                                    <?php foreach($books_list as $bk): ?>
                                        <?php $book_line = $bk['accession_number'] . " | " . $bk['title']; ?>
                                        <li>
                                            <a class="dropdown-item py-2 border-bottom text-wrap text-dark selection-item" href="#" data-value="<?php echo htmlspecialchars($bk['accession_number']); ?>">
                                                <span><?php echo htmlspecialchars($book_line); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a class="dropdown-item text-muted" href="#">No books available</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div id="append-div"></div>

                    <div class="form-group row mt-3">
                        <div class="col-sm-8 offset-sm-4">
                            <button type="button" class="btn btn-outline-secondary btn-xs rounded-pill" id="dynamic-append-btn">
                                <i class="fa fa-plus fa-xs mr-1"></i> Add Another Book Field
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-sm px-3" data-dismiss="modal">
                        <i class="fa fa-close mr-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-sm px-3 shadow" name="add">
                        <i class="fa fa-save mr-1"></i> Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    
    // Reset ONLY when modal closes
    $('#addnew').on('hidden.bs.modal', function () {
        const form = $(this).find('form')[0];
        if(form){
            form.reset();
        }
        $('#append-div').empty();
        $('.dropdown-menu').removeClass('show').hide();
        
        // Restore student mode
        $('#student_type').prop('checked', true);
        $('#staff_type').prop('checked', false);
        
        $('#student_radio_label').addClass('active');
        $('#staff_radio_label').removeClass('active');
        
        $('#students_dropdown_menu').hide();
        $('#staff_dropdown_menu').hide();
        
        $('#student')
            .val('')
            .attr('placeholder','Type Registration ID, NIC or Name...');
    });

    // Radio button toggle functionality
    $(document).on('change', 'input[name="borrower_type"]', function () {
        $('#student').val('');
        $('.dropdown-menu').removeClass('show').hide();
        
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

    // Dynamic Row Generation Node Manager Override
    $('#dynamic-append-btn').on('click', function(e) {
        e.preventDefault();
        
        // Injects full layout containing independent .dropdown blocks to isolate rows cleanly
        var dynamicFieldHtml = `
            <div class="form-group row align-items-center mb-3">
                <label class="col-sm-4 font-weight-bold text-gray-700 m-0">Accession Number</label>
                <div class="col-sm-8 position-relative dropdown">
                    <input type="text" class="form-control form-control-sm bs5-book-input" name="accession_number[]" placeholder="Enter Accession Number" autocomplete="off" required>
                </div>
            </div>`;
            
        $('#append-div').append(dynamicFieldHtml);
    });

    // Borrower search event with proper filtering
    $('#student').on('keyup focus input', function () {
        const value = $(this).val().toLowerCase();
        const borrowerType = $('input[name="borrower_type"]:checked').val();
        
        const dropdown = borrowerType === 'staff'
            ? $('#staff_dropdown_menu')
            : $('#students_dropdown_menu');
        
        $('#students_dropdown_menu,#staff_dropdown_menu')
            .removeClass('show')
            .hide();
        
        let found = false;
        
        dropdown.find('li').each(function () {
            const txt = $(this).text().toLowerCase();
            if (txt.includes(value)) {
                $(this).show();
                found = true;
            } else {
                $(this).hide();
            }
        });
        
        if (found && value.length > 0) {
            dropdown.show().addClass('show');
        }
    });

    // Live Filter and dynamic dropdown tracking for primary AND newly appended inputs
    $(document).on('keyup focus input', 'input[name="accession_number[]"]', function() {
        var value = $(this).val().toLowerCase();
        var currentInput = $(this);
        var container = currentInput.closest('.dropdown');
        
        // Dynamically inject cloned dropdown template if not explicitly generated yet for this cell
        if (!container.find('.dropdown-menu').length) {
            var dropdownClone = $('#books_dropdown_menu').clone().attr('id', '').removeAttr('style');
            container.append(dropdownClone);
        }
        
        var targetDropdown = container.find('.dropdown-menu');
        targetDropdown.css({ 'max-height': '200px', 'overflow-y': 'auto', 'font-size': '0.825rem', 'width': '100%' });
        
        var matchCount = 0;
        targetDropdown.find('li').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(value) > -1) {
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
    });

    // Selection Event Engine Handler: Intercepts selection item triggers across all dynamically appended rows
    $(document).on('click', '.dropdown-menu .selection-item', function(e) {
        e.preventDefault();
        var targetValue = $(this).attr('data-value');
        var container = $(this).closest('.dropdown');
        
        container.find('input').val(targetValue);
        container.find('.dropdown-menu').removeClass('show').hide();
    });

    // Global Interface Cleanup: Clear floating dropdowns if view focus drops completely out of target bounds
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show').hide();
        }
    });
});
</script>