<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

require_once 'PermissionChecking.php';

?>

<div id="wrapper">
    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>

            <div class="p-3" style="font-size: 14px;">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">
                        Final Year Report Check
                    </h4>
                </div>

                <form action="" method="POST">
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-md-3">
                                            <label for="programme">Programme</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="programme_id" id="programme" class="form-control select2" style="font-size: 12px;" required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" class="form-control select2" style="font-size: 12px;" required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="module">Module</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="module_id" id="module" class="form-control select2" style="font-size: 12px;" required>
                                                    <option value="">Select Module</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="lecturer">Lecturer</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="lecturer_id" id="lecturer" class="form-control select2" style="font-size: 12px;" required>
                                                    <option value="">Select Lecturer</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 text-right">
                                            <button type="submit" class="btn btn-primary">View Report</button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <canvas id="feedbackPieChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

                <div id="reportDetails" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h3 id="displayLecturerName" class="font-weight-bold"></h3>
                                <p class="text-muted">
                                    <span id="displayProgramme"></span> |
                                    <span id="displayBatch"></span> |
                                    <span id="displayModule"></span>
                                </p>
                            </div>

                            <div class="text-right mb-3">
                                <span class="mr-3 font-weight-bold text-dark" id="studentCountWrapper" style="display: none;">
                                    Filled Student Count: <span id="displayFilledCount" class="text-primary">0</span>
                                </span>
                                <button id="exportPdfBtn" class="btn btn-danger btn-sm">Export to PDF</button>
                                <button id="exportCsvBtn" class="btn btn-success btn-sm">Export to CSV</button>
                            </div>

                            <div class="row" id="breakdownSection">
                            </div>

                            <div class="mt-4">
                                <h5><strong>Comments:</strong></h5>
                                <ul id="commentsList" class="list-group list-group-flush">
                                </ul>
                            </div>

                            <div class="mt-5 pt-4 border-top">
                                <h5 class="font-weight-bold text-center mb-4 text-primary">
                                    <i class="fas fa-clipboard-list"></i> Individual Submissions / Detailed Answers
                                </h5>
                                <div id="submissionsList">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
    $(document).ready(function() {
        $('.select2').select2();

        let feedbackPieChartInstance = null;
        let lastReportData = null;

        function ratingBadgeClass(value) {
            switch(value) {
                case 'Excellent': return 'badge-primary';
                case 'Good':      return 'badge-success';
                case 'Average':   return 'badge-warning';
                case 'Poor':      return 'badge-danger';
                default:          return 'badge-secondary';
            }
        }

        function copyToClipboard(elementId) {
            let copyText = document.getElementById(elementId);
            if (copyText) {
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                document.execCommand("copy");
                alert("Copied the link: " + copyText.value);
            } else {
                alert("Could not find the link to copy.");
            }
        }

        $('#exportCsvBtn').click(function() {
            let programmeId = $('#programme').val();
            let batchId = $('#batch').val();
            let moduleId = $('#module').val();
            let lecturerId = $('#lecturer').val();

            if (programmeId && batchId && moduleId && lecturerId) {
                let url = `export_final_year_csv.php?programme_id=${programmeId}&batch_id=${batchId}&module_id=${moduleId}&lecturer_id=${lecturerId}`;
                window.location.href = url;
            } else {
                alert('Please select all filters before exporting to CSV.');
            }
        });

        $('#exportPdfBtn').click(function() {
            if (!lastReportData) {
                alert('No report data to export. Please view a report first.');
                return;
            }

            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');

            let lecturerName = $('#displayLecturerName').text();
            let programme = $('#displayProgramme').text();
            let batch = $('#displayBatch').text();
            let module = $('#displayModule').text();
            let filledCount = $('#displayFilledCount').text();

            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text('Final Year Report', 105, 22, {
                align: 'center'
            });

            doc.setFontSize(14);
            doc.text(lecturerName, 105, 32, {
                align: 'center'
            });
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`${programme} | ${batch} | ${module}`, 105, 40, {
                align: 'center'
            });
            doc.text(`Total Feedbacks (Filled Student Count): ${filledCount}`, 105, 46, {
                align: 'center'
            });

            let breakdownBody = [];
            let categories = lastReportData.categories || {};
            for (let catLabel in categories) {
                if (categories.hasOwnProperty(catLabel)) {
                    let counts = categories[catLabel];
                    let valuesText = `Excellent: ${counts.Excellent || 0}\nGood: ${counts.Good || 0}\nAverage: ${counts.Average || 0}\nPoor: ${counts.Poor || 0}`;
                    breakdownBody.push([catLabel, valuesText]);
                }
            }

            doc.autoTable({
                head: [
                    ['Category', 'Feedback Breakdown']
                ],
                body: breakdownBody,
                startY: 50,
                theme: 'grid',
                headStyles: {
                    fillColor: [78, 115, 223],
                    textColor: 255
                },
                columnStyles: {
                    0: {
                        cellWidth: 45,
                        fontStyle: 'bold'
                    },
                    1: {
                        cellWidth: 'auto'
                    }
                },
                margin: {
                    left: 10,
                    right: 10
                }
            });

            let commentsBody = [];
            let comments = lastReportData.comments_list || [];
            comments.forEach(function(c) {
                commentsBody.push([c]);
            });

            if (commentsBody.length > 0) {
                doc.autoTable({
                    head: [
                        ['Comments']
                    ],
                    body: commentsBody,
                    startY: doc.autoTable.previous.finalY + 10,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [78, 115, 223],
                        textColor: 255
                    },
                    margin: {
                        left: 10,
                        right: 10
                    }
                });
            }

            let submissionsBody = [];
            let submissions = lastReportData.submissions || [];
            submissions.forEach(function(sub, idx) {
                let header = `Submission #${idx + 1}  (${sub.submitted_at || ''})`;
                sub.answers.forEach(function(a) {
                    submissionsBody.push([header, a.field_label, a.answer_value]);
                    header = '';
                });
            });

            if (submissionsBody.length > 0) {
                doc.autoTable({
                    head: [
                        ['Submission', 'Question / Field', 'Answer']
                    ],
                    body: submissionsBody,
                    startY: doc.autoTable.previous.finalY + 10,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [28, 200, 138],
                        textColor: 255
                    },
                    columnStyles: {
                        0: {
                            cellWidth: 35,
                            fontStyle: 'bold'
                        },
                        1: {
                            cellWidth: 55
                        },
                        2: {
                            cellWidth: 'auto'
                        }
                    },
                    margin: {
                        left: 10,
                        right: 10
                    }
                });
            }

            let filename = `Final-Year-Report-${lecturerName.replace(/ /g, '_')}-${new Date().toISOString().slice(0, 10)}.pdf`;
            doc.save(filename);
        });

        $(document).on('click', '.copy-link-btn', function() {
            let linkId = $(this).data('link-id');
            copyToClipboard('link-' + linkId);
        });

        $.ajax({
            url: "transection_exams/fetch_programmes_final_year.php",
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

        $('#programme').change(function() {
            let programmeId = $(this).val();
            $('#batch').empty().append('<option value="">Select Batch</option>');
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (programmeId) {
                $.ajax({
                    url: "transection_exams/fetch_batches_final_year.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId
                    },
                    dataType: "json",
                    success: function(data) {
                        let batchDropdown = $('#batch');
                        data.forEach(function(batch) {
                            batchDropdown.append(`<option value="${batch.id}">${batch.batch_name}</option>`);
                        });
                    }
                });
            }
        });

        $('#batch').change(function() {
            let batchId = $(this).val();
            $('#module').empty().append('<option value="">Select Module</option>');
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (batchId) {
                $.ajax({
                    url: "transection_exams/fetch_modules_final_year.php",
                    method: "POST",
                    data: {
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(data) {
                        let moduleDropdown = $('#module');
                        data.forEach(function(module) {
                            moduleDropdown.append(`<option value="${module.id}">${module.name}</option>`);
                        });
                    }
                });
            }
        });

        $('#module').change(function() {
            let moduleId = $(this).val();
            let programmeId = $('#programme').val();
            let batchId = $('#batch').val();
            $('#lecturer').empty().append('<option value="">Select Lecturer</option>');

            if (moduleId && programmeId && batchId) {
                $.ajax({
                    url: "transection_exams/fetch_lecturers_final_year.php",
                    method: "POST",
                    data: {
                        programme_id: programmeId,
                        batch_id: batchId,
                        module_id: moduleId
                    },
                    dataType: "json",
                    success: function(data) {
                        let lecturerDropdown = $('#lecturer');
                        data.forEach(function(lecturer) {
                            lecturerDropdown.append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
                        });
                    }
                });
            }
        });

        $('form').submit(function(event) {
            event.preventDefault();

            $.ajax({
                url: "fetch_final_year_report.php",
                method: "POST",
                data: $(this).serialize(),
                dataType: "json",
                success: function(data) {
                    if (!data || !data.lecturer_name || data.filled_count === 0) {
                        alert("No feedback data found for the selected criteria.");
                        $('#reportDetails').hide();
                        if (feedbackPieChartInstance) {
                            feedbackPieChartInstance.destroy();
                        }
                        lastReportData = null;
                        return;
                    }

                    lastReportData = data;

                    $('#displayLecturerName').text(data.lecturer_name);
                    $('#displayProgramme').text(data.program_name);
                    $('#displayBatch').text(data.batch_name);
                    $('#displayModule').text(data.module_name);

                    $('#displayFilledCount').text(data.filled_count || 0);
                    $('#studentCountWrapper').show();

                    let categories = data.categories || {};
                    let breakdownHtml = '';
                    for (let catLabel in categories) {
                        if (categories.hasOwnProperty(catLabel)) {
                            let counts = categories[catLabel];
                            breakdownHtml += `
                                <div class="col-md-3 mb-3">
                                    <h6 class="font-weight-bold text-primary">${catLabel}</h6>
                                    <ul class="list-unstyled" style="font-size: 13px;">
                                        <li>Excellent: <span class="font-weight-bold text-primary">${counts.Excellent || 0}</span></li>
                                        <li>Good: <span class="font-weight-bold text-success">${counts.Good || 0}</span></li>
                                        <li>Average: <span class="font-weight-bold text-warning">${counts.Average || 0}</span></li>
                                        <li>Poor: <span class="font-weight-bold text-danger">${counts.Poor || 0}</span></li>
                                    </ul>
                                </div>
                            `;
                        }
                    }
                    $('#breakdownSection').html(breakdownHtml);

                    let commentsHtml = '';
                    if (data.comments_list && data.comments_list.length > 0) {
                        data.comments_list.forEach(function(comment) {
                            commentsHtml += `<li class="list-group-item" style="font-size: 13px;">• ${comment}</li>`;
                        });
                    } else {
                        commentsHtml = '<li class="list-group-item text-muted">No comments available.</li>';
                    }
                    $('#commentsList').html(commentsHtml);

                    let submissions = data.submissions || [];
                    let submissionsHtml = '';
                    if (submissions.length === 0) {
                        submissionsHtml = '<div class="text-muted text-center py-3">No individual submission records found.</div>';
                    } else {
                        submissions.forEach(function(sub, idx) {
                            submissionsHtml += `
                                <div class="card mb-3 border-left-${(idx % 2 === 0) ? 'primary' : 'success'}">
                                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 font-weight-bold">
                                            <i class="fas fa-user-check"></i> Submission #${idx + 1}
                                            <span class="ml-2 text-muted small">ID: ${sub.submission_id}</span>
                                        </h6>
                                        <span class="badge badge-secondary">
                                            <i class="far fa-clock"></i> ${sub.submitted_at || '-'}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm table-bordered mb-0" style="font-size: 13px;">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width: 35%;">Question / Field</th>
                                                    <th style="width: 15%;">Type</th>
                                                    <th>Answer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                            `;
                            (sub.answers || []).forEach(function(a) {
                                let typeLabel = a.field_type === 'rating'
                                    ? `<span class="badge badge-info">Rating</span>`
                                    : `<span class="badge badge-dark">Comment</span>`;
                                let ansDisplay;
                                if (a.field_type === 'rating') {
                                    ansDisplay = `<span class="badge ${ratingBadgeClass(a.answer_value)}" style="font-size: 13px; padding: 6px 10px;">${a.answer_value}</span>`;
                                } else {
                                    ansDisplay = `<div class="p-2 bg-light rounded">${a.answer_value || '<span class="text-muted">(empty)</span>'}</div>`;
                                }
                                submissionsHtml += `
                                    <tr>
                                        <td class="font-weight-semibold">${a.field_label}</td>
                                        <td>${typeLabel}</td>
                                        <td>${ansDisplay}</td>
                                    </tr>
                                `;
                            });
                            submissionsHtml += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#submissionsList').html(submissionsHtml);

                    $('#reportDetails').fadeIn();
                    setTimeout(function() {
                        $('#reportDetails')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 200);

                    let pieData = data.overall_totals || { Excellent: 0, Good: 0, Average: 0, Poor: 0 };
                    let ctx = document.getElementById('feedbackPieChart').getContext('2d');
                    if (feedbackPieChartInstance) {
                        feedbackPieChartInstance.destroy();
                    }
                    feedbackPieChartInstance = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Excellent', 'Good', 'Average', 'Poor'],
                            datasets: [{
                                label: 'Overall Feedback',
                                data: [
                                    pieData.Excellent,
                                    pieData.Good,
                                    pieData.Average,
                                    pieData.Poor
                                ],
                                backgroundColor: [
                                    '#4e73df',
                                    '#1cc88a',
                                    '#f6c23e',
                                    '#e74a3b'
                                ],
                                hoverBackgroundColor: [
                                    '#2e59d9',
                                    '#17a673',
                                    '#dda20a',
                                    '#be2617'
                                ],
                                hoverBorderColor: "rgba(234, 236, 244, 1)",
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            tooltips: {
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                            },
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            cutoutPercentage: 0,
                        },
                    });
                }
            });
        });

    });
</script>

<?php include("includes/footer.php"); ?>
