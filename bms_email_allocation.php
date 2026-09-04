<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
?>

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
                <div class="container p-3">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">BMS EMAIL ALLOCATIONS</h4>
                    </div>

                    <!-- Filter Form -->
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header d-flex align-items-center" style="height: 60px;">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus-circle"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">BMS Email Add for Each Students</h6>
                                </div>
                                <div class="card-body">
                                    <div id="allocateSection">
                                        <!-- University -->
                                        <div class="form-group mb-3 row">
                                            <label for="university" class="col-md-4 col-form-label">University</label>
                                            <div class="col-md-8">
                                                <select id="university" name="university_id" class="form-control select2" required>
                                                    <option value="">-- Select University --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Programme -->
                                        <div class="form-group mb-3 row">
                                            <label for="programme" class="col-md-4 col-form-label">Programme</label>
                                            <div class="col-md-8">
                                                <select id="programme" name="programme_code" class="form-control select2" required>
                                                    <option value="">-- Select Programme --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Batch -->
                                        <div class="form-group mb-4 row">
                                            <label for="batch" class="col-md-4 col-form-label">Batch</label>
                                            <div class="col-md-8">
                                                <select id="batch" name="batch_id" class="form-control select2" required>
                                                    <option value="">-- Select Batch --</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Fetch Students Button -->
                                        <div class="form-group text-right">
                                            <button id="fetchStudentsBtn" class="btn btn-primary">Fetch Students</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Excel Upload Form -->
                        <div class="col-md-6">
                            <div class="form">
                                <!-- <span class="form-title">Upload BMS Email Excel File</span> -->
                                <!-- <p class="form-paragraph">
                                    File should be .xls or .xlsx
                                </p> -->

                                <form id="uploadBmsExcelForm" enctype="multipart/form-data">
                                    <label for="bmsExcelFile" class="drop-container">
                                        <span class="drop-title">Drop Excel files here</span>
                                        or
                                        <input type="file" accept=".xls,.xlsx" required id="bmsExcelFile" name="bmsExcelFile">
                                    </label>

                                    <div class="form-group text-center" style="margin-top: 20px;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload"></i> Upload
                                        </button>
                                    </div>

                                    <div id="uploadStatus" class="mt-2"></div>
                                </form>
                            </div>
                        </div>

                        <!-- CSS Styling -->
                        <style>
                            .form {
                                background-color: #fff;
                                box-shadow: 0 10px 60px rgb(218, 229, 255);
                                border: 1px solid rgb(159, 159, 160);
                                border-radius: 20px;
                                padding: 2rem .7rem .7rem .7rem;
                                text-align: center;
                                font-size: 1.125rem;
                                max-width: 400px;
                                margin: auto;
                            }

                            .form-title {
                                color: #000000;
                                font-size: 1.8rem;
                                font-weight: 500;
                            }

                            .form-paragraph {
                                margin-top: 10px;
                                font-size: 0.9375rem;
                                color: rgb(105, 105, 105);
                            }

                            .drop-container {
                                background-color: #fff;
                                position: relative;
                                display: flex;
                                gap: 10px;
                                flex-direction: column;
                                justify-content: center;
                                align-items: center;
                                padding: 20px;
                                margin-top: 2rem;
                                border-radius: 10px;
                                border: 2px dashed rgb(171, 202, 255);
                                color: #444;
                                cursor: pointer;
                                transition: background .2s ease-in-out, border .2s ease-in-out;
                            }

                            .drop-container:hover {
                                background: rgba(0, 140, 255, 0.164);
                                border-color: rgba(17, 17, 17, 0.616);
                            }

                            .drop-container:hover .drop-title {
                                color: #222;
                            }

                            .drop-title {
                                color: #444;
                                font-size: 18px;
                                font-weight: bold;
                                text-align: center;
                                transition: color .2s ease-in-out;
                            }

                            #bmsExcelFile {
                                width: 100%;
                                max-width: 100%;
                                color: #444;
                                padding: 2px;
                                background: #fff;
                                border-radius: 10px;
                                border: 1px solid rgba(8, 8, 8, 0.288);
                            }

                            #bmsExcelFile::file-selector-button {
                                margin-right: 20px;
                                border: none;
                                background: #084cdf;
                                padding: 10px 20px;
                                border-radius: 10px;
                                color: #fff;
                                cursor: pointer;
                                transition: background .2s ease-in-out;
                            }

                            #bmsExcelFile::file-selector-button:hover {
                                background: #0d45a5;
                            }
                        </style>


                        <!-- Title + Programme/Batch -->
                        <div class="col-12 mt-3">
                            <div id="tableTitle" class="mb-3">
                                <h5>
                                    <small id="programBatchText" class="text-muted"></small>
                                </h5>
                            </div>
                        </div>

                        <div id="studentDataContainer" class="mt-4 col-12"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- SheetJS for Excel parsing -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>


    <script>
        $('#uploadBmsExcelForm').submit(function(e) {
            e.preventDefault();

            var fileInput = document.getElementById('bmsExcelFile');
            if (fileInput.files.length === 0) {
                alert("Please select an Excel file");
                return;
            }

            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {
                    type: 'array'
                });

                // Assuming first sheet
                var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                var excelData = XLSX.utils.sheet_to_json(firstSheet, {
                    header: 1
                });

                // Send the data to PHP
                $.ajax({
                    url: 'Batch_transer/upload_bms_excel.php',
                    type: 'POST',
                    data: {
                        excelData: JSON.stringify(excelData)
                    },
                    success: function(response) {
                        $('#uploadStatus').html('<div class="alert alert-success">' + response + '</div>');
                    },
                    error: function() {
                        $('#uploadStatus').html('<div class="alert alert-danger">Upload failed. Please try again.</div>');
                    }
                });
            };

            reader.readAsArrayBuffer(file);
        });
    </script>


    <!-- JQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables CSS & JS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <!-- Select2 CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <!-- DataTables Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>

    <!-- JSZip & pdfmake for Excel/PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Buttons for Excel & PDF -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2();

            // Load universities
            $.ajax({
                url: 'Batch_transer/fetch_universities.php',
                type: 'GET',
                success: function(data) {
                    $('#university').html(data);
                },
                error: function() {
                    alert('Failed to load universities.');
                }
            });

            // On university change -> load programmes
            $('#university').on('change', function() {
                const universityId = $(this).val();
                $('#programme').html('<option value="">-- Select Programme --</option>');
                $('#batch').html('<option value="">-- Select Batch --</option>');

                if (universityId) {
                    $.ajax({
                        url: 'Batch_transer/fetch_programs_all_programs.php',
                        type: 'POST',
                        data: {
                            university_id: universityId
                        },
                        success: function(data) {
                            $('#programme').html(data);
                        },
                        error: function() {
                            alert('Failed to load programmes.');
                        }
                    });
                }
            });

            // On programme change -> load batches
            $('#programme').on('change', function() {
                const programId = $(this).val();
                const universityId = $('#university').val();
                $('#batch').html('<option value="">-- Select Batch --</option>');

                if (programId && universityId) {
                    $.ajax({
                        url: 'Batch_transer/fetch_batches.php',
                        type: 'POST',
                        data: {
                            program_id: programId,
                            university_id: universityId
                        },
                        success: function(data) {
                            $('#batch').html(data);
                        },
                        error: function() {
                            alert('Failed to load batches.');
                        }
                    });
                }
            });

            // Fetch students
            $('#fetchStudentsBtn').click(function(e) {
                e.preventDefault();
                let universityId = $('#university').val();
                let programId = $('#programme').val();
                let batchId = $('#batch').val();

                if (!universityId || !programId || !batchId) {
                    alert("Please select university, program and batch!");
                    return;
                }

                $.ajax({
                    url: 'Batch_transer/fetch_students.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        university_id: universityId,
                        program_id: programId,
                        batch_id: batchId
                    },
                    success: function(response) {
                        if (response.students.length === 0) {
                            $('#studentDataContainer').html('<p class="text-danger">No students found.</p>');
                            $('#programBatchText').text('');
                            return;
                        }

                        // Update Programme + Batch title
                        var programName = $('#programme option:selected').text();
                        var batchName = $('#batch option:selected').text();
                        $('#programBatchText').html(`Programme: ${programName}<br>Batch: ${batchName}`);

                        let table = `<form id="updateBmsForm">
                            <table id="studentsTable" class="table table-bordered table-striped table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Code</th>
                                        <th>Student Name</th>
                                        <th>Registration ID</th>
                                        <th>BMS Email</th>
                                    </tr>
                                </thead>
                                <tbody>`;

                        response.students.forEach(function(student) {
                            table += `<tr>
                                <td></td>
                                <td>${student.student_code}</td>
                                <td>${student.name}</td>
                                <td>${student.student_registration_id || ''}</td>
                                <td><input type="text" name="bms_email[${student.id}]" value="${student.bms_email}" class="form-control"></td>
                            </tr>`;
                        });

                        table += `</tbody></table>
                            <div class="text-end mt-2">
                                <button type="submit" class="btn btn-success">Update BMS Email</button>
                            </div>
                        </form>`;

                        if ($.fn.DataTable.isDataTable('#studentsTable')) {
                            $('#studentsTable').DataTable().destroy();
                        }

                        $('#studentDataContainer').html(table);

                        // Initialize DataTable with Excel/PDF
                        $('#studentsTable').DataTable({
                            paging: true,
                            searching: true,
                            ordering: true,
                            lengthChange: true,
                            pageLength: 50,
                            dom: 'Bfrtip',
                            buttons: [{
                                    extend: 'excelHtml5',
                                    title: `BMS Student Emails - ${programName} - ${batchName}`,
                                    exportOptions: {
                                        columns: ':visible',
                                        orthogonal: 'export'
                                    }
                                },
                                {
                                    extend: 'pdfHtml5',
                                    title: `BMS Student Emails - ${programName} - ${batchName}`,
                                    orientation: 'portrait',
                                    pageSize: 'A4',
                                    exportOptions: {
                                        columns: ':visible',
                                        orthogonal: 'export'
                                    },
                                    customize: function(doc) {
                                        doc.styles.tableHeader.alignment = 'center';
                                        doc.styles.tableBodyOdd.alignment = 'center';
                                        doc.styles.tableBodyEven.alignment = 'center';
                                        doc.styles.title = {
                                            fontSize: 14,
                                            bold: true,
                                            alignment: 'center'
                                        };
                                        var objLayout = {};
                                        objLayout['hLineWidth'] = function() {
                                            return 0.5;
                                        };
                                        objLayout['vLineWidth'] = function() {
                                            return 0.5;
                                        };
                                        objLayout['hLineColor'] = function() {
                                            return '#aaa';
                                        };
                                        objLayout['vLineColor'] = function() {
                                            return '#aaa';
                                        };
                                        objLayout['paddingLeft'] = function() {
                                            return 4;
                                        };
                                        objLayout['paddingRight'] = function() {
                                            return 4;
                                        };
                                        doc.content[doc.content.length - 1].layout = objLayout;
                                    }
                                }
                            ],
                            columnDefs: [{
                                    targets: 0,
                                    orderable: false,
                                    searchable: false,
                                    className: 'dt-center',
                                    render: function(data, type, row, meta) {
                                        return meta.row + 1;
                                    }
                                },
                                {
                                    targets: -1,
                                    render: function(data, type, row) {
                                        return type === 'export' ? $(data).val() : data;
                                    }
                                }
                            ],
                            order: [
                                [1, 'asc']
                            ]
                        });
                    },
                    error: function() {
                        alert('Failed to load student data.');
                    }
                });
            });

            // Handle update form submit
            $(document).on('submit', '#updateBmsForm', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'Batch_transer/update_bms_email.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response);
                    },
                    error: function() {
                        alert('Failed to update BMS Emails');
                    }
                });
            });
        });
    </script>
</body>

</html>