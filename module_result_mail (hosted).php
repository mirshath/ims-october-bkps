<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// ---------------------- allowed Redirections ------------------------------
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// ---------------------------------------------------------------------------

?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3" style="font-size: 12px;">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Module Results Mailing</h4>
                </div>

                <form action="" method="POST">
                    <div class="row mb-5">
                        <div class="col-md-7">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="programme">Programme</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="programme_id" id="programme" class="form-control select2" required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" class="form-control select2" required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="viewResults" class="btn btn-primary float-right"> View Results </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Send Email Button aligned to the right -->
                <div class="text-right">
                    <button id="sendEmailBtn" class="btn btn-primary mt-3 mr-5 mb-3">
                        Send Email
                    </button>
                    <button id="printBtn" class="btn btn-secondary mt-3 mr-5 mb-3">
                        Print Results
                    </button>
                    <button id="savePdfBtn" class="btn btn-success mt-3 mr-5 mb-3">
                        Save as PDF
                    </button>
                </div>

                <!-- Table to Display Results -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5>Results</h5>
                                <table class="table table-bordered table-hover" id="resultsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Student Full Name</th>
                                            <th>BMS Email</th>
                                            <th>Student Registration ID</th>
                                            <th>Module Name</th>
                                            <th>Final Result</th>
                                            <th>Email Status</th>
                                            <th>Sent By</th>
                                            <th>Sent Date</th>
                                        </tr>
                                    </thead>
                                    <tbody> </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <!-- Include jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.18/jspdf.plugin.autotable.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2();


            // Print functionality
            $('#printBtn').click(function() {
                var programme = $('#programme option:selected').text(); // Get the selected Programme
                var batch = $('#batch option:selected').text(); // Get the selected Batch

                // Get the table content
                var printContent = document.getElementById('resultsTable').outerHTML;

                // Create a new window for the print preview
                var printWindow = window.open('', '', 'height=800,width=1000');

                // Write the content for the print window
                printWindow.document.write('<html><head><title>Print Results</title>');
                printWindow.document.write('<style>body{font-family: Arial, sans-serif;}');
                printWindow.document.write('@page { size: A4 landscape; margin: 20mm; }');
                printWindow.document.write('table { width: 100%; border-collapse: collapse; }');
                printWindow.document.write('th, td { padding: 8px; border: 1px solid #ddd; text-align: center; }');
                printWindow.document.write('</style></head><body>');

                // Add Programme and Batch information above the table
                printWindow.document.write(`
        <div style="text-align: center; font-size: 18px; margin-bottom: 10px;">
            <p><strong>Programme:</strong> ${programme}</p>
            <p><strong>Batch:</strong> ${batch}</p>
        </div>
    `);

                // Insert the table content
                printWindow.document.write(printContent);
                printWindow.document.write('</body></html>');

                // Wait for the document to fully load before printing
                printWindow.document.close();
                printWindow.print();
            });

            // Save as PDF functionality
            $('#savePdfBtn').click(function() {
                var {
                    jsPDF
                } = window.jspdf; // Initialize jsPDF
                var doc = new jsPDF('landscape', 'mm', 'a4'); // Create a PDF in landscape orientation and A4 size

                var programme = $('#programme option:selected').text(); // Get the selected Programme
                var batch = $('#batch option:selected').text(); // Get the selected Batch

                // Add Programme and Batch information at the top
                doc.setFontSize(14);
                doc.text(`Programme: ${programme}`, 20, 20);
                doc.text(`Batch: ${batch}`, 20, 30);

                // Get the HTML content of the table and add it to the PDF using autoTable
                doc.autoTable({
                    html: '#resultsTable',
                    startY: 40, // Adjust where the table starts
                    theme: 'grid' // Set the table theme to 'grid'
                });

                // Save the PDF
                doc.save('module_results.pdf');
            });

            $('#resultsTable').DataTable({
                "pageLength": 10,
                "ordering": true,
            });

            // Fetch Programmes
            $.ajax({
                url: "transection_exams/fetch_programmes.php",
                method: "GET",
                dataType: "json",
                success: function(data) {
                    let programmeDropdown = $('#programme');
                    programmeDropdown.empty().append('<option value="">Select Programme</option>');
                    data.forEach(function(programme) {
                        programmeDropdown.append(`<option value="${programme.program_code}">${programme.program_name}</option>`);
                    });
                }
            });

            // Fetch Batches on Programme change
            $('#programme').change(function() {
                let programmeId = $(this).val();
                $.ajax({
                    url: "transection_exams/fetch_batches.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        let batchDropdown = $('#batch');
                        batchDropdown.empty().append('<option value="">Select Batch</option>');
                        data.forEach(function(batch) {
                            batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                        });
                    }
                });
            });

            // Fetch Student Results when clicking View Results
            $('#viewResults').click(function() {
                let programmeId = $('#programme').val();
                let batchId = $('#batch').val();

                if (programmeId && batchId) {
                    $.ajax({
                        url: "module_result_mailing/fetch_results.php",
                        method: "POST",
                        data: {
                            programme_id: programmeId,
                            batch_id: batchId
                        },
                        dataType: "json",
                        success: function(response) {
                            let resultsTable = $("#resultsTable tbody");
                            resultsTable.empty();

                            if (response.length > 0) {
                                let studentIndex = 1; // Initialize student counter

                                response.forEach(function(result, index) {
                                    // Fetch email status from module_result_email_log
                                    $.ajax({
                                        url: "module_result_mailing/fetch_email_status.php",
                                        method: "POST",
                                        data: {
                                            student_id: result.student_registration_id,
                                            email: result.bms_email,
                                            programme_id: programmeId,
                                            batch_id: batchId
                                        },
                                        dataType: "json",
                                        async: false,
                                        success: function(statusData) {
                                            let emailStatus = 'Not Sent';
                                            let sentBy = 'N/A';
                                            let sentDate = '';

                                            if (statusData.status) {
                                                emailStatus = statusData.status === 'sent' ? 
                                                    '<span class="badge bg-success text-white">Sent</span>' : 
                                                    '<span class="badge bg-danger text-white">Failed</span>';
                                                sentBy = statusData.sent_by || 'N/A';
                                                sentDate = statusData.sent_date || '';
                                            } else {
                                                emailStatus = '<span class="badge bg-warning text-dark">Not Sent</span>';
                                            }

                                            if (index % result.module_count === 0) {
                                                // Start a new row for each student
                                                resultsTable.append(`
                                                    <tr>
                                                        <td rowspan="${result.module_count}">${studentIndex}</td>
                                                        <td rowspan="${result.module_count}">${result.first_name} ${result.last_name}</td>
                                                        <td rowspan="${result.module_count}">${result.bms_email}</td>
                                                        <td rowspan="${result.module_count}">${result.student_registration_id}</td>
                                                        <td>${result.module_name}</td>
                                                        <td>${result.final_result}</td>
                                                        <td rowspan="${result.module_count}">${emailStatus}</td>
                                                        <td rowspan="${result.module_count}">${sentBy}</td>
                                                        <td rowspan="${result.module_count}">${sentDate}</td>
                                                    </tr>
                                                `);
                                                studentIndex++;
                                            } else {
                                                // For subsequent modules, no need for student ID, registration ID, or index
                                                resultsTable.append(`
                                                    <tr>
                                                        <td>${result.module_name}</td>
                                                        <td>${result.final_result}</td>
                                                    </tr>
                                                `);
                                            }
                                        }
                                    });
                                });
                            } else {
                                resultsTable.append(`<tr><td colspan="9" class="text-center">No results found.</td></tr>`);
                            }
                        }
                    });
                } else {
                    alert("Please select both Programme and Batch.");
                }
            });


            // $('#viewResults').click(function() {
            //     let programmeId = $('#programme').val();
            //     let batchId = $('#batch').val();

            //     if (programmeId && batchId) {
            //         // Show loading indicator
            //         $("#resultsTable tbody").html('<tr><td colspan="9" class="text-center">Loading results...</td></tr>');

            //         $.ajax({
            //             url: "module_result_mailing/fetch_results.php",
            //             method: "POST",
            //             data: {
            //                 programme_id: programmeId,
            //                 batch_id: batchId
            //             },
            //             dataType: "json",
            //             success: function(response) {
            //                 let resultsTable = $("#resultsTable tbody");
            //                 resultsTable.empty();

            //                 if (response.length > 0) {
            //                     // Group students by student_code to handle the data better
            //                     let students = {};
            //                     response.forEach(function(result) {
            //                         if (!students[result.student_code]) {
            //                             students[result.student_code] = {
            //                                 first_name: result.first_name,
            //                                 last_name: result.last_name,
            //                                 bms_email: result.bms_email,
            //                                 student_registration_id: result.student_registration_id,
            //                                 module_count: result.module_count,
            //                                 modules: []
            //                             };
            //                         }
            //                         students[result.student_code].modules.push({
            //                             module_name: result.module_name,
            //                             final_result: result.final_result
            //                         });
            //                     });

            //                     // Process each student
            //                     let studentIndex = 1;
            //                     Object.values(students).forEach(function(student) {
            //                         // Fetch email status for this student
            //                         $.ajax({
            //                             url: "module_result_mailing/fetch_email_status.php",
            //                             method: "POST",
            //                             data: {
            //                                 student_id: student.student_registration_id,
            //                                 email: student.bms_email,
            //                                 programme_id: programmeId,
            //                                 batch_id: batchId
            //                             },
            //                             dataType: "json",
            //                             success: function(statusData) {
            //                                 let emailStatus = 'Not Sent';
            //                                 let sentBy = 'N/A';
            //                                 let sentDate = '';

            //                                 if (statusData.status) {
            //                                     emailStatus = statusData.status === 'sent' ?
            //                                         '<span class="badge bg-success text-white">Sent</span>' :
            //                                         '<span class="badge bg-danger text-white">Failed</span>';
            //                                     sentBy = statusData.sent_by || 'N/A';
            //                                     sentDate = statusData.sent_date || '';
            //                                 } else {
            //                                     emailStatus = '<span class="badge bg-warning text-dark">Not Sent</span>';
            //                                 }

            //                                 // Add the first row with student info and first module
            //                                 let firstModule = student.modules[0];
            //                                 resultsTable.append(`
            //                         <tr class="student-row" data-student-id="${student.student_registration_id}">
            //                             <td rowspan="${student.module_count}">${studentIndex}</td>
            //                             <td rowspan="${student.module_count}">${student.first_name} ${student.last_name}</td>
            //                             <td rowspan="${student.module_count}">${student.bms_email}</td>
            //                             <td rowspan="${student.module_count}">${student.student_registration_id}</td>
            //                             <td>${firstModule.module_name}</td>
            //                             <td>${firstModule.final_result}</td>
            //                             <td rowspan="${student.module_count}">${emailStatus}</td>
            //                             <td rowspan="${student.module_count}">${sentBy}</td>
            //                             <td rowspan="${student.module_count}">${sentDate}</td>
            //                         </tr>
            //                     `);

            //                                 // Add remaining modules for this student
            //                                 for (let i = 1; i < student.modules.length; i++) {
            //                                     let module = student.modules[i];
            //                                     resultsTable.append(`
            //                             <tr class="module-row" data-student-id="${student.student_registration_id}">
            //                                 <td>${module.module_name}</td>
            //                                 <td>${module.final_result}</td>
            //                             </tr>
            //                         `);
            //                                 }

            //                                 studentIndex++;
            //                             },
            //                             error: function(xhr, status, error) {
            //                                 console.error("Error fetching email status:", error);
            //                                 // Still add the student row but with error status
            //                                 let firstModule = student.modules[0];
            //                                 resultsTable.append(`
            //                         <tr class="student-row" data-student-id="${student.student_registration_id}">
            //                             <td rowspan="${student.module_count}">${studentIndex}</td>
            //                             <td rowspan="${student.module_count}">${student.first_name} ${student.last_name}</td>
            //                             <td rowspan="${student.module_count}">${student.bms_email}</td>
            //                             <td rowspan="${student.module_count}">${student.student_registration_id}</td>
            //                             <td>${firstModule.module_name}</td>
            //                             <td>${firstModule.final_result}</td>
            //                             <td rowspan="${student.module_count}"><span class="badge bg-danger text-white">Error</span></td>
            //                             <td rowspan="${student.module_count}">N/A</td>
            //                             <td rowspan="${student.module_count}"></td>
            //                         </tr>
            //                     `);

            //                                 // Add remaining modules for this student
            //                                 for (let i = 1; i < student.modules.length; i++) {
            //                                     let module = student.modules[i];
            //                                     resultsTable.append(`
            //                             <tr class="module-row" data-student-id="${student.student_registration_id}">
            //                                 <td>${module.module_name}</td>
            //                                 <td>${module.final_result}</td>
            //                             </tr>
            //                         `);
            //                                 }

            //                                 studentIndex++;
            //                             }
            //                         });
            //                     });
            //                 } else {
            //                     resultsTable.append(`<tr><td colspan="9" class="text-center">No results found.</td></tr>`);
            //                 }
            //             },
            //             error: function(xhr, status, error) {
            //                 console.error("Error fetching results:", error);
            //                 $("#resultsTable tbody").html(`<tr><td colspan="9" class="text-center text-danger">Error: ${error}</td></tr>`);
            //             }
            //         });
            //     } else {
            //         alert("Please select both Programme and Batch.");
            //     }
            // });

            $('#sendEmailBtn').click(function() {
                let emailData = [];
                let programmeId = $('#programme').val();
                let batchId = $('#batch').val();

                // Check if programmeId and batchId are selected
                if (!programmeId || !batchId) {
                    alert('Please select a Programme and Batch.');
                    return;
                }

                // Collect student emails and IDs from the table
                $('#resultsTable tbody tr').each(function() {
                    let fullname = $(this).find('td').eq(1).text().trim(); // Assuming email is in the 3rd column (index 2)
                    let email = $(this).find('td').eq(2).text().trim(); // Assuming email is in the 3rd column (index 2)
                    let studentId = $(this).find('td').eq(3).text().trim(); // Assuming student ID is in the 4th column (index 3)

                    if (email && studentId) {
                        emailData.push({
                            full_name: fullname,
                            email: email,
                            student_id: studentId
                        });
                    }
                });

                // Ensure at least one student is available
                if (emailData.length === 0) {
                    alert('No students found in the table.');
                    return;
                }

                // Disable the button and show spinner
                $('#sendEmailBtn').prop('disabled', true);
                $('#sendEmailBtn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...');

                // Send data via AJAX
                $.ajax({
                    url: 'module_result_mailing/send_email.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        email_data: emailData,
                        programme_id: programmeId,
                        batch_id: batchId
                    }),
                    dataType: 'json',
                    success: function(response) {
                        console.log("Email Send Response:", response);
                        alert("Emails sent successfully!");

                        // Re-enable the button and reset text
                        $('#sendEmailBtn').prop('disabled', false);
                        $('#sendEmailBtn').html('Send Email');

                        // Refresh the results to show updated email status
                        $('#viewResults').click();
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", xhr.responseText);
                        alert("Failed to send emails. Check the console for details.");

                        // Re-enable the button and reset text
                        $('#sendEmailBtn').prop('disabled', false);
                        $('#sendEmailBtn').html('Send Email');
                    }
                });
            });

        });
    </script>
</div>
</body>

</html>