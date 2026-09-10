<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}
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
            <div class="p-3 pw-page">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Payment Withheld Data</h4>
                </div>

                <!-- Student Selector -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 pw-card">
                            <div class="card-header d-flex align-items-center bg-white border-0 pt-3">
                                <span class="pw-icon-badge bg-primary">
                                    <i class="fas fa-user-graduate"></i>
                                </span>
                                <h6 class="mb-0 ms-3 fw-bold text-gray-800">Select Student</h6>
                            </div>
                            <div class="card-body pt-0">
                                <label for="studentSelect" class="form-label small text-muted text-uppercase fw-bold">Active Student</label>
                                <select id="studentSelect" class="form-control" style="width: 100%;">
                                    <option value="">-- Select a student --</option>
                                </select>
                                <div id="studentLoading" class="text-muted mt-2" style="display:none;">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Personal Data -->
                <div class="row mb-4" id="personalDataRow" style="display:none;">
                    <div class="col-md-12">
                        <div class="card shadow-sm border-0 pw-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="pw-avatar" id="pdAvatar">--</div>
                                    <div class="ms-3 flex-grow-1">
                                        <div class="d-flex align-items-center flex-wrap">
                                            <h5 class="mb-0 fw-bold text-gray-800" id="pdFullName">-</h5>
                                            <span class="badge rounded-pill pw-status-badge ms-2" id="pdStatus">-</span>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            Preferred name: <span class="fw-semibold text-gray-800" id="pdPreferredName">-</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pw-reg-grid mt-4 px-0">
                                    <div class="pw-reg-chip">
                                        <div class="pw-label"><i class="fas fa-user me-1"></i>First Name</div>
                                        <div class="pw-value" id="pdFirstName">-</div>
                                    </div>
                                    <div class="pw-reg-chip">
                                        <div class="pw-label"><i class="fas fa-user me-1"></i>Last Name</div>
                                        <div class="pw-value" id="pdLastName">-</div>
                                    </div>
                                    <div class="pw-reg-chip">
                                        <div class="pw-label"><i class="fas fa-id-card me-1"></i>NIC</div>
                                        <div class="pw-value" id="pdNic">-</div>
                                    </div>
                                    <div class="pw-reg-chip">
                                        <div class="pw-label"><i class="fas fa-envelope me-1"></i>BMS Email</div>
                                        <div class="pw-value" id="pdBmsEmail">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Program(s) + Payment Withheld Data -->
                <div class="row mb-5" id="programsRow" style="display:none;">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center mb-3">
                            <span class="pw-icon-badge bg-dark">
                                <i class="fas fa-money-check-alt"></i>
                            </span>
                            <h6 class="mb-0 ms-3 fw-bold text-gray-800">Active Program / Batch &amp; Payment Withheld Data</h6>
                        </div>
                        <div id="programsContainer">
                            <!-- Program cards injected here by JS -->
                        </div>
                    </div>
                </div>

                <div class="row mb-5" id="noActiveProgramRow" style="display:none;">
                    <div class="col-md-12">
                        <div class="alert alert-warning border-0 shadow-sm">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This student has no <strong>active</strong> record in <code>allocate_programme</code>.
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

    <style>
        .pw-page .pw-card {
            border-radius: 0.75rem;
        }

        .pw-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .pw-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .pw-personal-grid {
            display: flex;
            gap: 2.5rem;
            margin-left: auto;
            padding-left: 1rem;
        }

        .pw-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #858796;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .pw-value {
            font-size: 0.95rem;
            color: #3a3b45;
            font-weight: 600;
        }

        .pw-status-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.35em 0.75em;
            background-color: #e2e6ea;
            color: #5a5c69;
        }

        .pw-status-badge.status-active {
            background-color: #1cc88a1a;
            color: #17a673;
        }

        .pw-status-badge.status-inactive,
        .pw-status-badge.status-transferred {
            background-color: #e74a3b1a;
            color: #c0392b;
        }

        .pw-program-card {
            border-radius: 0.75rem;
            border: 1px solid #e3e6f0;
            border-left: 4px solid #4e73df;
            overflow: hidden;
        }

        .pw-program-card.pw-withheld {
            border-left-color: #e74a3b;
        }

        .pw-program-card.pw-active {
            border-left-color: #1cc88a;
        }

        .pw-program-card .pw-program-header {
            background: #f8f9fc;
            padding: 0.9rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .pw-payment-badge {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.4em 0.85em;
            border-radius: 50rem;
            font-weight: 700;
        }

        .pw-payment-badge.badge-active {
            background-color: #1cc88a;
            color: #fff;
        }

        .pw-payment-badge.badge-withheld {
            background-color: #e74a3b;
            color: #fff;
        }

        .pw-reg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.9rem;
            padding: 1.1rem 1.25rem 0.25rem 1.25rem;
        }

        .pw-reg-chip {
            background: #eef1fb;
            border: 1px solid #dde3f7;
            border-radius: 0.5rem;
            padding: 0.65rem 0.9rem;
        }

        .pw-reg-chip .pw-value {
            font-size: 1rem;
            letter-spacing: 0.02em;
        }

        .pw-form-section {
            background: #f8f9fc;
            margin: 1rem 1.25rem 1.25rem 1.25rem;
            border-radius: 0.5rem;
            padding: 1rem 1.1rem;
        }

        .pw-form-section .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #858796;
        }

        @media (max-width: 767px) {
            .pw-personal-grid {
                margin-left: 0;
                padding-left: 0;
                margin-top: 1rem;
                gap: 1.5rem;
            }
        }
    </style>

    <script>
        $(function() {

            // ---- 1. Load active students into the select2 dropdown ----
            // Search matches display text, NIC, and both registration id fields.
            function studentMatcher(params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }
                if (typeof data.searchBlob === 'undefined') {
                    return null;
                }
                var term = params.term.toLowerCase();
                if (data.searchBlob.toLowerCase().indexOf(term) > -1) {
                    return data;
                }
                return null;
            }

            function loadStudents() {
                $.getJSON('ajax/get_active_students.php', function(res) {
                    if (res.success) {
                        var $select = $('#studentSelect');
                        $.each(res.data, function(i, s) {
                            var opt = new Option(s.text, s.id,s.nic, false, false);
                            // Select2 reads data-* attributes into each result's data object,
                            // which is what the custom matcher below searches against.
                            $(opt).attr('data-search-blob', s.search || s.text || s.nic);
                            $select.append(opt);
                        });
                        $select.select2({
                            placeholder: '-- Search by name, NIC, or registration ID --',
                            allowClear: true,
                            width: '100%',
                            matcher: studentMatcher
                        });
                    } else {
                        alert('Failed to load student list: ' + res.message);
                    }
                }).fail(function(xhr) {
                    console.error('get_active_students.php raw response:', xhr.responseText);
                    alert('Failed to load student list. Server said:\n\n' + xhr.responseText);
                });
            }
            loadStudents();

            // ---- 2. On student select, fetch full details ----
            $('#studentSelect').on('change', function() {
                var studentCode = $(this).val();

                $('#personalDataRow').hide();
                $('#programsRow').hide();
                $('#noActiveProgramRow').hide();
                $('#programsContainer').empty();

                if (!studentCode) {
                    return;
                }

                $('#studentLoading').show();

                $.getJSON('ajax/get_student_info.php', {
                    student_code: studentCode
                }, function(res) {
                    $('#studentLoading').hide();

                    if (!res.success) {
                        alert(res.message || 'Failed to load student details.');
                        return;
                    }

                    // Personal data
                    var s = res.student;
                    var firstName = s.first_name || '';
                    var lastName = s.last_name || '';
                    var fullName = (firstName + ' ' + lastName).trim() || '-';
                    var initials = ((firstName.trim().charAt(0) || '') + (lastName.trim().charAt(0) || '')).toUpperCase() || '?';
                    var status = s.student_status || '-';

                    $('#pdAvatar').text(initials);
                    $('#pdFullName').text(fullName);
                    $('#pdFirstName').text(firstName || '-');
                    $('#pdLastName').text(lastName || '-');
                    $('#pdPreferredName').text(s.preferred_name || '-');
                    $('#pdNic').text(s.nic || '-');
                    $('#pdBmsEmail').text(s.bms_email || '-');

                    var $statusBadge = $('#pdStatus').text(status);
                    $statusBadge.removeClass('status-active status-inactive status-transferred');
                    var statusLower = status.toLowerCase();
                    if (statusLower === 'active') {
                        $statusBadge.addClass('status-active');
                    } else if (statusLower.indexOf('inactive') !== -1 || statusLower.indexOf('transfer') !== -1) {
                        $statusBadge.addClass('status-inactive');
                    }

                    $('#personalDataRow').show();

                    // Active program(s)
                    if (res.programs.length === 0) {
                        $('#noActiveProgramRow').show();
                        return;
                    }

                    var $container = $('#programsContainer');
                    $.each(res.programs, function(i, prog) {
                        $container.append(buildProgramCard(studentCode, prog));
                    });
                    $('#programsRow').show();

                }).fail(function(xhr) {
                    $('#studentLoading').hide();
                    console.error('get_student_info.php raw response:', xhr.responseText);
                    alert('Failed to load student details. Server said:\n\n' + xhr.responseText);
                });
            });

            // ---- 3. Build one program/payment card ----
            function buildProgramCard(studentCode, prog) {
                var pw = prog.payment_withheld || {};
                var recordId = pw.id ? pw.id : '';
                var status = pw.payment_status ? pw.payment_status : 'active';
                var bmsDue = (typeof pw.due_count_bms_fees !== 'undefined') ? pw.due_count_bms_fees : 0;
                var uniDue = (typeof pw.due_count_uni_fees !== 'undefined') ? pw.due_count_uni_fees : 0;
                var lastPay = pw.last_payment_date ? pw.last_payment_date.substring(0, 16) : '';

                // Prefer the newer registration id if present, else fall back to the original one
                var oldRegId = prog.student_registration_id || '-';
                var newRegId = prog.new_student_registration_id || '-';

                var cardStatusClass = status === 'withheld' ? 'pw-withheld' : 'pw-active';
                var badgeClass = status === 'withheld' ? 'badge-withheld' : 'badge-active';
                var badgeIcon = status === 'withheld' ? 'fa-lock' : 'fa-check-circle';

                var html = '';
                html += '<div class="card mb-4 pw-program-card ' + cardStatusClass + '">';

                html += '  <div class="pw-program-header">';
                html += '    <div>';
                html += '      <div class="fw-bold text-gray-800" style="font-size:1.05rem;">' + escapeHtml(prog.program_name) + '</div>';
                html += '      <div class="text-muted small">' + escapeHtml(prog.batch_name) + '</div>';
                html += '    </div>';
                html += '    <span class="pw-payment-badge ' + badgeClass + '"><i class="fas ' + badgeIcon + ' me-1"></i>' + escapeHtml(status) + '</span>';
                html += '  </div>';

                // Registration IDs
                html += '  <div class="pw-reg-grid">';
                html += '    <div class="pw-reg-chip">';
                html += '      <div class="pw-label"><i class="fas fa-hashtag me-1"></i>Student Registration ID</div>';
                html += '      <div class="pw-value">' + escapeHtml(oldRegId) + '</div>';
                html += '    </div>';
                html += '    <div class="pw-reg-chip">';
                html += '      <div class="pw-label"><i class="fas fa-hashtag me-1"></i>New Student Registration ID</div>';
                html += '      <div class="pw-value">' + escapeHtml(newRegId) + '</div>';
                html += '    </div>';
                html += '  </div>';

                html += '  <div class="pw-form-section">';
                html += '    <form class="payment-withheld-form row g-3 align-items-end" data-student="' + studentCode + '" data-program="' + prog.programme_code + '" data-batch="' + prog.batch_id + '" data-record-id="' + recordId + '">';
                html += '      <div class="col-md-3">';
                html += '        <label class="form-label">Payment Status</label>';
                html += '        <select name="payment_status" class="form-control">';
                html += '          <option value="active"' + (status === 'active' ? ' selected' : '') + '>Active</option>';
                html += '          <option value="withheld"' + (status === 'withheld' ? ' selected' : '') + '>Withheld</option>';
                html += '        </select>';
                html += '      </div>';
                html += '      <div class="col-md-3">';
                html += '        <label class="form-label">Due Count (BMS Fees)</label>';
                html += '        <input type="number" min="0" name="due_count_bms_fees" class="form-control" value="' + bmsDue + '">';
                html += '      </div>';
                html += '      <div class="col-md-3">';
                html += '        <label class="form-label">Due Count (Uni Fees)</label>';
                html += '        <input type="number" min="0" name="due_count_uni_fees" class="form-control" value="' + uniDue + '">';
                html += '      </div>';
                html += '      <div class="col-md-3">';
                html += '        <label class="form-label">Last Payment Date</label>';
                html += '        <input type="datetime-local" name="last_payment_date" class="form-control" value="' + lastPay + '">';
                html += '      </div>';
                html += '      <div class="col-md-12 d-flex align-items-center">';
                html += '        <button type="submit" class="btn btn-primary btn-sm px-3"><i class="fas fa-save me-1"></i> Save</button>';
                html += '        <span class="save-status ms-3 small fw-semibold"></span>';
                html += '      </div>';
                html += '    </form>';
                html += '  </div>';

                html += '</div>';
                return html;
            }

            // ---- 4. Save (insert/update) payment withheld data ----
            $(document).on('submit', '.payment-withheld-form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $status = $form.find('.save-status');

                var payload = {
                    record_id: $form.data('record-id'),
                    student_code: $form.data('student'),
                    program_id: $form.data('program'),
                    batch_id: $form.data('batch'),
                    payment_status: $form.find('[name="payment_status"]').val(),
                    due_count_bms_fees: $form.find('[name="due_count_bms_fees"]').val(),
                    due_count_uni_fees: $form.find('[name="due_count_uni_fees"]').val(),
                    last_payment_date: $form.find('[name="last_payment_date"]').val()
                };

                $status.text('Saving...').removeClass('text-success text-danger');

                $.post('ajax/save_payment_withheld.php', payload, function(res) {
                    if (res.success) {
                        $status.html('<i class="fas fa-check-circle"></i> Saved').addClass('text-success');
                        if (res.record_id) {
                            $form.data('record-id', res.record_id);
                            $form.attr('data-record-id', res.record_id);
                        }
                    } else {
                        $status.text(res.message || 'Save failed.').addClass('text-danger');
                    }
                }, 'json').fail(function(xhr) {
                    console.error('save_payment_withheld.php raw response:', xhr.responseText);
                    $status.text('Save failed: ' + xhr.responseText).addClass('text-danger');
                });
            });

            function escapeHtml(str) {
                if (str === null || typeof str === 'undefined' || str === '') return '-';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

        });
    </script>

</div>
</body>

</html>