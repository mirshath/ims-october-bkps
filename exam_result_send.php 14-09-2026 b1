<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 


// Initialize variables for edit mode
$isEdit = false;
$assessment = null;
?>

<style>
    .hidden-field {
        display: none;
    }

    .badge {
        display: inline-block;
        border-radius: 4px;
    }

    .badge-success {
        background-color: #28a745;
        color: white;
    }

    .badge-danger {
        background-color: #dc3545;
        color: white;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }
</style>

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
            <div class="p-3" style="font-size: 14px;">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Final Result Check Mo</h4>
                </div>

                <!-- Form Section -->
                <form action="" method="POST" enctype="multipart/form-data">
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
                                                <select name="programme_id" id="programme" style="font-size: 14px;"
                                                    class="form-control select2" required>
                                                    <option value="">Select Programme</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="batch">Batch</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="batch_id" id="batch" style="font-size: 14px;"
                                                    class="form-control select2" required>
                                                    <option value="">Select Batch</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="studentStatus">Student Status</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="student_status" id="studentStatus" style="font-size: 14px;"
                                                    class="form-control select2">
                                               
                                                    <option value="active" selected>Active Students</option>
                                                    <option value="completed">Completed Students</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="module">Module</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="module_id" id="module" style="font-size: 14px;"
                                                    class="form-control select2" required>
                                                    <option value="">Select Module</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->

                                        <div class="col-md-3">
                                            <label for="mainComponent">Main Component</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="main_component_id" id="mainComponent"
                                                    style="font-size: 14px;" class="form-control select2" required>
                                                    <option value="">Select Main Component</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- ------------------------------------------------- -->
                                        <div class="col-md-3">
                                            <label for="subComponent">Sub Component</label>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="form-group mb-3">
                                                <select name="sub_component_id" id="subComponent"
                                                    style="font-size: 14px;" class="form-control select2" required>
                                                    <option value="">Select Sub Component</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- button section here  -->
                                    <!-- Add View Button -->
                                    <div class="col-12 mt-3" style="display: none;">
                                        <button type="button" id="viewButton"
                                            class="btn btn-primary btn-sm">View</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Add results table container -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="resultsTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Main Component ID</th>
                                                <th>Result</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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

    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <script>
        $(document).ready(function() {
            $('.select2').select2();

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

            // Modify the change events to trigger loadResults
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
                        loadResults(); // Trigger results update
                    }
                });
            });

            $('#batch').change(function() {
                let batchId = $(this).val();
                $.ajax({
                    url: "transection_exams/fetch_modules.php",
                    method: "POST",
                    data: {
                        batch_id: batchId
                    },
                    dataType: "json",
                    success: function(data) {
                        let moduleDropdown = $('#module');
                        moduleDropdown.empty().append('<option value="">Select Module</option>');
                        data.forEach(function(module) {
                            moduleDropdown.append(`<option value="${module.id}">${module.name}</option>`);
                        });
                        loadResults(); // Trigger results update
                    }
                });
            });

            $('#module').change(function() {
                let moduleId = $(this).val();
                if (moduleId) {
                    $.ajax({
                        url: "transection_exams/fetch_main_components.php",
                        method: "POST",
                        data: {
                            module_id: moduleId
                        },
                        dataType: "json",
                        success: function(data) {
                            let mainComponentDropdown = $('#mainComponent');
                            mainComponentDropdown.empty().append('<option value="">Select Main Component</option>');
                            data.forEach(function(mainComponent) {
                                mainComponentDropdown.append(`<option value="${mainComponent.main_component_id}">${mainComponent.as_main_component_name}</option>`);
                            });
                            loadResults(); // Trigger results update
                        }
                    });
                } else {
                    $('#mainComponent').empty().append('<option value="">Select Main Component</option>');
                    loadResults(); // Trigger results update
                }
            });

            // Fetch sub components when a main component is selected
            $('#mainComponent').change(function() {
                let mainComponentId = $(this).val();
                let moduleId = $('#module').val();
                if (mainComponentId && moduleId) {
                    $.ajax({
                        url: "transection_exams/fetch_sub_components.php",
                        method: "POST",
                        data: {
                            main_component_id: mainComponentId,
                            module_id: moduleId
                        },
                        dataType: "json",
                        success: function(data) {
                            let subComponentDropdown = $('#subComponent');
                            subComponentDropdown.empty().append('<option value="">Select Sub Component</option>');
                            if (data.length > 0) {
                                data.forEach(function(subComponent) {
                                    subComponentDropdown.append(`<option value="${subComponent.id}">${subComponent.sub_component_name}</option>`);
                                });
                            } else {
                                subComponentDropdown.append('<option value="">No Sub Components Available</option>');
                            }
                            loadResults(); // Trigger results update
                        }
                    });
                } else {
                    $('#subComponent').empty().append('<option value="">Select Sub Component</option>');
                    loadResults(); // Trigger results update
                }
            });

            // Student status change -> refresh results
            $('#studentStatus').change(function() {
                loadResults();
            });
            // Add this to your existing JavaScript in exam_result_send.php, replacing the existing loadResults function

            function loadResults() {
                const programmeId = $('#programme').val();
                const programmeName = $('#programme option:selected').text();
                const batchId = $('#batch').val();
                const moduleId = $('#module').val();
                const mainComponentId = $('#mainComponent').val();
                const subComponentId = $('#subComponent').val();
                const studentStatus = $('#studentStatus').val();

                // Only proceed if all required fields are selected
                if (programmeId && batchId && moduleId) {
                    $.ajax({
                        url: "exam_result_folder/fetch_results.php",
                        method: "POST",
                        data: {
                            programme_id: programmeId,
                            batch_id: batchId,
                            module_id: moduleId,
                            main_component_id: mainComponentId,
                            sub_component_id: subComponentId,
                            student_status: studentStatus
                        },
                        dataType: "json",
                        success: function(data) {
                            console.log('Received data:', data);

                            // Clear previous results
                            $('#resultsTable').DataTable().clear().destroy();

                            // Remove any existing "Send to All" button before adding a new one
                            $('#sendToAll').remove();

                            // Add "Send to All" button above the results table
                            $('#resultsTable').parent().before('<button id="sendToAll" class="btn btn-success mb-3">Send to All</button>');

                            // Handle BBM program type
                            if (data.programme_type === 'BBM') {
                                // Build BBM-specific table headers with proper component hierarchy
                                let headerHtml = '<tr><th rowspan="3">Student Name</th>';

                                // Calculate total columns for each main component
                                let mainComponentColspans = {};
                                let totalSubcomponentColumns = 0;

                                // First, determine the structure and calculate column spans
                                Object.keys(data.componentStructure).forEach(mainCompId => {
                                    const component = data.componentStructure[mainCompId];
                                    const subcomponents = component.subcomponents;
                                    const subcomponentCount = Object.keys(subcomponents).length;

                                    if (subcomponentCount > 0) {
                                        // If there are subcomponents, we need 2 columns (Ex1, Ex2) for each subcomponent
                                        mainComponentColspans[mainCompId] = subcomponentCount * 2;
                                        totalSubcomponentColumns += subcomponentCount * 2;
                                    } else {
                                        // If no subcomponents, just 2 columns for the main component (Ex1, Ex2)
                                        mainComponentColspans[mainCompId] = 2;
                                        totalSubcomponentColumns += 2;
                                    }
                                });

                                // Now build the header rows

                                // First row: Main component names with appropriate colspans
                                Object.keys(data.componentStructure).forEach(mainCompId => {
                                    const component = data.componentStructure[mainCompId];
                                    headerHtml += `<th colspan="${mainComponentColspans[mainCompId]}">${component.name}</th>`;
                                });

                                // Add the summary columns
                                headerHtml += `<th rowspan="3">Total</th>`;
                                headerHtml += `<th rowspan="3">Grade</th>`;
                                headerHtml += `<th rowspan="3">GPA</th>`;
                                headerHtml += `<th rowspan="3">Email</th>`;
                                headerHtml += `</tr><tr>`;

                                // Second row: Subcomponent names
                                Object.keys(data.componentStructure).forEach(mainCompId => {
                                    const component = data.componentStructure[mainCompId];
                                    const subcomponents = component.subcomponents;
                                    const subcomponentCount = Object.keys(subcomponents).length;

                                    if (subcomponentCount > 0) {
                                        // Add each subcomponent with colspan=2 for Ex1 and Ex2
                                        Object.entries(subcomponents).forEach(([subCompId, subCompName]) => {
                                            headerHtml += `<th colspan="2">${subCompName}</th>`;
                                        });
                                    } else {
                                        // If no subcomponents, just add a placeholder that spans both examiner columns
                                        headerHtml += `<th colspan="2"></th>`;
                                    }
                                });

                                headerHtml += `</tr><tr>`;

                                // Third row: Examiner 1 and Examiner 2 headers
                                Object.keys(data.componentStructure).forEach(mainCompId => {
                                    const component = data.componentStructure[mainCompId];
                                    const subcomponents = component.subcomponents;
                                    const subcomponentCount = Object.keys(subcomponents).length;

                                    if (subcomponentCount > 0) {
                                        // Add Ex1 and Ex2 for each subcomponent
                                        for (let i = 0; i < subcomponentCount; i++) {
                                            headerHtml += `<th>Examiner 1</th><th>Examiner 2</th>`;
                                        }
                                    } else {
                                        // Add Ex1 and Ex2 for the main component
                                        headerHtml += `<th>Examiner 1</th><th>Examiner 2</th>`;
                                    }
                                });

                                headerHtml += `</tr>`;

                                $('#resultsTable thead').html(headerHtml);

                                // Build columns configuration for DataTable
                                const columns = [{
                                    data: 'student_name',
                                    title: 'Student Name'
                                }];


                                // Inside the loadResults function, in the BBM section where columns are defined
                                // Replace the existing code for main component columns with this improved version

                                // Add columns for marks based on component structure
                                Object.keys(data.componentStructure).forEach(mainCompId => {
                                    const component = data.componentStructure[mainCompId];
                                    const subcomponents = component.subcomponents;
                                    const subcomponentCount = Object.keys(subcomponents).length;

                                    if (subcomponentCount > 0) {
                                        // Add columns for each subcomponent (this part remains unchanged)
                                        Object.keys(subcomponents).forEach(subCompId => {
                                            // Examiner 1 column
                                            columns.push({
                                                data: null,
                                                title: 'Examiner 1',
                                                render: function(data, type, row) {
                                                    if (row.components[mainCompId] &&
                                                        row.components[mainCompId][subCompId]) {
                                                        return row.components[mainCompId][subCompId].examiner1_marks || '-';
                                                    }
                                                    return '-';
                                                }
                                            });

                                            // Examiner 2 column
                                            columns.push({
                                                data: null,
                                                title: 'Examiner 2',
                                                render: function(data, type, row) {
                                                    if (row.components[mainCompId] &&
                                                        row.components[mainCompId][subCompId]) {
                                                        return row.components[mainCompId][subCompId].examiner2_marks || '-';
                                                    }
                                                    return '-';
                                                }
                                            });
                                        });
                                    } else {
                                        // Add columns for the main component only - THIS IS THE MODIFIED PART
                                        // Examiner 1 column
                                        columns.push({
                                            data: null,
                                            title: 'Examiner 1',
                                            render: function(data, type, row) {
                                                // First check if we have data in bbm_result_details
                                                if (row.questions && row.questions[mainCompId]) {
                                                    // Check if there's a main entry with questions
                                                    if (row.questions[mainCompId].main) {
                                                        // Get the first question's examiner1_marks
                                                        const questionKeys = Object.keys(row.questions[mainCompId].main);
                                                        if (questionKeys.length > 0) {
                                                            const firstQuestion = questionKeys[0];
                                                            return row.questions[mainCompId].main[firstQuestion].examiner1_marks || '-';
                                                        }
                                                    }
                                                }

                                                // If no data in bbm_result_details, try bbm_final_results_tbl
                                                if (row.final_results && row.final_results[mainCompId]) {
                                                    return row.final_results[mainCompId].final_result || '-';
                                                }

                                                // Fallback to direct component data if available
                                                if (row.components[mainCompId]) {
                                                    // Check if there's a main entry
                                                    if (row.components[mainCompId].main) {
                                                        return row.components[mainCompId].main.examiner1_marks || '-';
                                                    }

                                                    // If no main entry, try to find the first sub-component
                                                    const subCompKeys = Object.keys(row.components[mainCompId]);
                                                    if (subCompKeys.length > 0) {
                                                        const firstSubComp = subCompKeys[0];
                                                        return row.components[mainCompId][firstSubComp].examiner1_marks || '-';
                                                    }
                                                }

                                                return '-';
                                            }
                                        });

                                        // Examiner 2 column
                                        columns.push({
                                            data: null,
                                            title: 'Examiner 2',
                                            render: function(data, type, row) {
                                                // First check if we have data in bbm_result_details
                                                if (row.questions && row.questions[mainCompId]) {
                                                    // Check if there's a main entry with questions
                                                    if (row.questions[mainCompId].main) {
                                                        // Get the first question's examiner2_marks
                                                        const questionKeys = Object.keys(row.questions[mainCompId].main);
                                                        if (questionKeys.length > 0) {
                                                            const firstQuestion = questionKeys[0];
                                                            return row.questions[mainCompId].main[firstQuestion].examiner2_marks || '-';
                                                        }
                                                    }
                                                }

                                                // If no data in bbm_result_details, try bbm_final_results_tbl
                                                if (row.final_results && row.final_results[mainCompId]) {
                                                    return row.final_results[mainCompId].final_result_ex2 || '-';
                                                }

                                                // Fallback to direct component data if available
                                                if (row.components[mainCompId]) {
                                                    // Check if there's a main entry
                                                    if (row.components[mainCompId].main) {
                                                        return row.components[mainCompId].main.examiner2_marks || '-';
                                                    }

                                                    // If no main entry, try to find the first sub-component
                                                    const subCompKeys = Object.keys(row.components[mainCompId]);
                                                    if (subCompKeys.length > 0) {
                                                        const firstSubComp = subCompKeys[0];
                                                        return row.components[mainCompId][firstSubComp].examiner2_marks || '-';
                                                    }
                                                }

                                                return '-';
                                            }
                                        });
                                    }
                                });

                                // Add columns for total, grade, and GPA
                                columns.push({
                                    data: 'total_mod_result.total',
                                    defaultContent: '-',
                                    title: 'Total'
                                });

                                columns.push({
                                    data: 'total_mod_result.grades',
                                    defaultContent: '-',
                                    title: 'Grade'
                                });

                                columns.push({
                                    data: 'gpa_data.final_GPA_value',
                                    defaultContent: '-',
                                    title: 'GPA'
                                });

                                // Add email button column
                                columns.push({
                                    data: null,
                                    title: 'Email',
                                    render: function(data, type, row) {
                                        // single row send email button 
                                        return `<button class="btn btn-primary btn-sm send-email" data-student="${row.student_id}" data-id="${row.student_id}" data-program="${programmeName}">Send Email</button>`;
                                    }
                                });

                                // Initialize DataTable
                                $('#resultsTable').DataTable({
                                    destroy: true,
                                    data: data.results,
                                    columns: columns,
                                    pageLength: 500,
                                    ordering: true,
                                    responsive: true,
                                    scrollX: true
                                });
                            } else {
                                // Original code for other program types
                                // Build table headers
                                // let headerHtml = `<tr><th rowspan="2">Student Name</th>`;

                                let headerHtml = `<tr><th rowspan="2">Student Name</th> 
                                <th rowspan="2">Student Name Only</th> `;

                                // Add columns for each component
                                data.components.forEach(component => {
                                    let colCount = 1;
                                    const componentResits = data.componentResits[component.main_component_id];
                                    for (let i = 1; i <= 7; i++) {
                                        if (componentResits[`resit_${i}`]) {
                                            colCount++;
                                        }
                                    }
                                    headerHtml += `<th colspan="${colCount}">${component.as_main_component_name}</th>`;
                                });

                                // Add Final Result column for both IFD and ECM
                                headerHtml += `<th rowspan="2">Final Result (Modules)</th>`;

                                // Add Grades of Modules column only for ECM
                                if (data.programme_type === 'ECM') {
                                    headerHtml += `<th rowspan="2">Modules Grades</th>`;
                                }

                                // Add Status and Email columns only for IFD
                                // if (data.programme_type === 'IFD') {
                                //     headerHtml += `<th rowspan="2">Status</th><th rowspan="2">Email</th>`;
                                // }

                                // Add only Email column for HD
                                if (data.programme_type === 'HDs' || data.programme_type === 'GDM' || data.programme_type === 'IFD' || data.programme_type === 'BTEC') {
                                    headerHtml += `<th rowspan="2">Email</th>`;
                                }

                                // Add Email column for ECM
                                if (data.programme_type === 'ECM') {
                                    headerHtml += `<th rowspan="2">Email</th>`;
                                }

                                headerHtml += `</tr><tr>`;

                                // Add sub-headers for each component
                                data.components.forEach(component => {
                                    headerHtml += `<th>Main</th>`;
                                    const componentResits = data.componentResits[component.main_component_id];
                                    for (let i = 1; i <= 7; i++) {
                                        if (componentResits[`resit_${i}`]) {
                                            headerHtml += `<th>Resit ${i}</th>`;
                                        }
                                    }
                                });

                                headerHtml += `</tr>`;

                                $('#resultsTable thead').html(headerHtml);

                                function normalizeHDGrade(grade) {
                                    let value = String(grade ?? '').trim().toLowerCase();
                                    value = value.replace(/[-_]/g, ' ').replace(/\s+/g, ' ');
                                    if (value === 'resit' || value === 're sit') value = 're-sit';
                                    return value;
                                }

                                function getHDMarkInfo(rawMark) {
                                    if (rawMark === null || rawMark === undefined || rawMark === '') {
                                        return {
                                            mark: null,
                                            grade: '',
                                            ruleApplied: false
                                        };
                                    }

                                    if (rawMark === 'Not Submitted' || rawMark === -1 || rawMark === '-1') {
                                        return {
                                            mark: -1,
                                            grade: 'Not Submitted',
                                            ruleApplied: false
                                        };
                                    }

                                    if (rawMark === 'Absent' || rawMark === -2 || rawMark === '-2') {
                                        return {
                                            mark: -2,
                                            grade: 'Absent',
                                            ruleApplied: false
                                        };
                                    }

                                    let mark = parseFloat(rawMark);
                                    if (isNaN(mark)) {
                                        return {
                                            mark: rawMark,
                                            grade: String(rawMark),
                                            ruleApplied: false
                                        };
                                    }

                                    let ruleApplied = false;
                                    if (mark === 49) {
                                        mark = 50;
                                        ruleApplied = true;
                                    } else if (mark === 59) {
                                        mark = 60;
                                        ruleApplied = true;
                                    } else if (mark === 69) {
                                        mark = 70;
                                        ruleApplied = true;
                                    }

                                    let grade = 'N/A';
                                    if (mark === 0) {
                                        grade = 'Absent';
                                    } else if (mark <= 29) {
                                        grade = 'Re-sit';
                                    } else if (mark <= 49) {
                                        grade = 'Pending';
                                    } else if (mark <= 59) {
                                        grade = 'Pass';
                                    } else if (mark <= 69) {
                                        grade = 'Merit';
                                    } else if (mark <= 100) {
                                        grade = 'Distinction';
                                    }

                                    return {
                                        mark,
                                        grade,
                                        ruleApplied
                                    };
                                }

                                function getLatestHDComponentAttempt(componentData) {
                                    if (!componentData) return null;

                                    for (let i = 7; i >= 1; i--) {
                                        const markKey = `resit_${i}`;
                                        const gradeKey = `resit${i}_Grade`;
                                        const hasMark = componentData[markKey] !== null && componentData[markKey] !== undefined && componentData[markKey] !== '';
                                        const hasGrade = componentData[gradeKey] !== null && componentData[gradeKey] !== undefined && componentData[gradeKey] !== '';

                                        if (hasMark || hasGrade) {
                                            const markInfo = getHDMarkInfo(componentData[markKey]);
                                            return {
                                                grade: componentData[gradeKey] || markInfo.grade,
                                                mark: typeof markInfo.mark === 'number' ? markInfo.mark : 0
                                            };
                                        }
                                    }

                                    const mainMarkInfo = getHDMarkInfo(componentData.main_result);
                                    if (
                                        (componentData.main_result_Grade !== null && componentData.main_result_Grade !== undefined && componentData.main_result_Grade !== '') ||
                                        (componentData.main_result !== null && componentData.main_result !== undefined && componentData.main_result !== '')
                                    ) {
                                        return {
                                            grade: componentData.main_result_Grade || mainMarkInfo.grade,
                                            mark: typeof mainMarkInfo.mark === 'number' ? mainMarkInfo.mark : 0
                                        };
                                    }

                                    return null;
                                }

                                function calculateHDGradeFromComponents(row, allComponents, finalResult) {
                                    const componentGrades = [];
                                    const componentMarks = [];
                                    const componentCount = Array.isArray(allComponents) ? allComponents.length : 0;

                                    (allComponents || []).forEach(component => {
                                        const componentData = row.components ? row.components[component.main_component_id] : null;
                                        const latestAttempt = getLatestHDComponentAttempt(componentData);

                                        if (!latestAttempt) return;

                                        if (latestAttempt.grade !== null && latestAttempt.grade !== undefined && latestAttempt.grade !== '') {
                                            componentGrades.push(latestAttempt.grade);
                                            componentMarks.push(parseFloat(latestAttempt.mark || 0));
                                        }
                                    });

                                    const norm = normalizeHDGrade;
                                    const getGradeFromMark = function(mark) {
                                        return getHDMarkInfo(mark).grade;
                                    };

                                    let finalHDGrade = '';

                                    if (componentCount === 2) {
                                        if (componentGrades.length >= 2) {
                                            const g1 = norm(componentGrades[0]);
                                            const g2 = norm(componentGrades[1]);

                                            if (g1 === 're-sit' && g2 === 're-sit') {
                                                finalHDGrade = 'Re-sit';
                                            } else if (g1 === 'pending' && g2 === 'pending') {
                                                finalHDGrade = 'Re-sit';
                                            } else if (
                                                (g1 === 're-sit' && g2 === 'pending') ||
                                                (g2 === 're-sit' && g1 === 'pending')
                                            ) {
                                                finalHDGrade = 'Re-sit';
                                            } else if (
                                                (g1 === 're-sit' && ['pass', 'merit', 'distinction'].includes(g2)) ||
                                                (g2 === 're-sit' && ['pass', 'merit', 'distinction'].includes(g1))
                                            ) {
                                                finalHDGrade = 'Pending';
                                            } else if (
                                                (g1 === 'pending' && ['pass', 'merit', 'distinction'].includes(g2)) ||
                                                (g2 === 'pending' && ['pass', 'merit', 'distinction'].includes(g1))
                                            ) {
                                                finalHDGrade = getGradeFromMark(finalResult);
                                            } else if (
                                                ['pass', 'merit', 'distinction'].includes(g1) &&
                                                ['pass', 'merit', 'distinction'].includes(g2)
                                            ) {
                                                finalHDGrade = getGradeFromMark((componentMarks[0] || 0) + (componentMarks[1] || 0));
                                            } else if (
                                                (g1 === 're-sit' && ['absent', 'not submitted'].includes(g2)) ||
                                                (g2 === 're-sit' && ['absent', 'not submitted'].includes(g1))
                                            ) {
                                                finalHDGrade = 'Re-sit';
                                            } else if (
                                                (g1 === 'pending' && ['absent', 'not submitted'].includes(g2)) ||
                                                (g2 === 'pending' && ['absent', 'not submitted'].includes(g1))
                                            ) {
                                                finalHDGrade = 'Pending';
                                            } else if (g1 === 'not submitted' && g2 === 'not submitted') {
                                                finalHDGrade = 'Not Submitted';
                                            } else if (g1 === 'absent' && g2 === 'absent') {
                                                finalHDGrade = 'Absent';
                                            } else if (
                                                (g1 === 'not submitted' && ['pass', 'merit', 'distinction'].includes(g2)) ||
                                                (g2 === 'not submitted' && ['pass', 'merit', 'distinction'].includes(g1))
                                            ) {
                                                finalHDGrade = 'Pending';
                                            } else if (
                                                (g1 === 'absent' && ['pass', 'merit', 'distinction'].includes(g2)) ||
                                                (g2 === 'absent' && ['pass', 'merit', 'distinction'].includes(g1))
                                            ) {
                                                finalHDGrade = 'Pending';
                                            } else if (
                                                (g1 === 'absent' && g2 === 'not submitted') ||
                                                (g1 === 'not submitted' && g2 === 'absent')
                                            ) {
                                                finalHDGrade = 'Not Submitted';
                                            } else {
                                                finalHDGrade = 'Pending';
                                            }
                                        } else if (componentGrades.length === 1) {
                                            const g = norm(componentGrades[0]);
                                            finalHDGrade = g === 'pending' ? getGradeFromMark(finalResult) : componentGrades[0];
                                        }
                                    } else if (componentCount === 3) {
                                        if (componentGrades.length >= 3) {
                                            let resitCount = 0;
                                            let pendingCount = 0;
                                            let absentCount = 0;
                                            let notSubmittedCount = 0;
                                            let validGrades = 0;
                                            let sumMarks = 0;

                                            for (let i = 0; i < 3; i++) {
                                                const g = norm(componentGrades[i]);
                                                const m = parseFloat(componentMarks[i] || 0);

                                                if (['pass', 'merit', 'distinction'].includes(g)) {
                                                    validGrades++;
                                                    sumMarks += m;
                                                } else if (g === 're-sit') {
                                                    resitCount++;
                                                    sumMarks += m;
                                                } else if (g === 'pending') {
                                                    pendingCount++;
                                                    sumMarks += m;
                                                } else if (g === 'absent') {
                                                    absentCount++;
                                                } else if (g === 'not submitted') {
                                                    notSubmittedCount++;
                                                }
                                            }

                                            if (resitCount === 3) {
                                                finalHDGrade = 'Re-sit';
                                            } else if (pendingCount === 3) {
                                                finalHDGrade = sumMarks < 50 ? 'Re-sit' : getGradeFromMark(sumMarks);
                                            } else if ((absentCount + notSubmittedCount) === 3) {
                                                finalHDGrade = notSubmittedCount === 3 ? 'Not Submitted' : (absentCount === 3 ? 'Absent' : 'Not Submitted');
                                            } else if (resitCount === 2 && pendingCount === 1) {
                                                finalHDGrade = 'Re-sit';
                                            } else if (resitCount === 2 && validGrades === 1) {
                                                finalHDGrade = 'Pending';
                                            } else if (pendingCount === 2 && validGrades === 1) {
                                                finalHDGrade = sumMarks < 50 ? 'Pending' : getGradeFromMark(sumMarks);
                                            } else if (resitCount === 1 && pendingCount === 1 && validGrades === 1) {
                                                finalHDGrade = 'Pending';
                                            } else if (resitCount === 1 && validGrades === 2) {
                                                finalHDGrade = 'Pending';
                                            } else if (pendingCount === 1 && validGrades === 2) {
                                                finalHDGrade = getGradeFromMark(sumMarks);
                                            } else if (validGrades === 3) {
                                                finalHDGrade = getGradeFromMark(sumMarks);
                                            } else if ((absentCount + notSubmittedCount) === 2 && pendingCount === 1) {
                                                finalHDGrade = 'Re-sit';
                                            } else if ((absentCount + notSubmittedCount) === 2 && resitCount === 1) {
                                                finalHDGrade = 'Re-sit';
                                            } else if (pendingCount === 2 && resitCount === 1) {
                                                finalHDGrade = sumMarks < 50 ? 'Re-sit' : getGradeFromMark(sumMarks);
                                            } else {
                                                finalHDGrade = 'Pending';
                                            }
                                        } else if (componentGrades.length === 1) {
                                            const g = norm(componentGrades[0]);
                                            finalHDGrade = g === 'pending' ? getGradeFromMark(finalResult) : componentGrades[0];
                                        }
                                    }

                                    return finalHDGrade;
                                }



                                const columns = [{
                                        // data: 'student_name (student_registration_id)',
                                        data: 'student_registration_id',
                                        title: 'Student Reg ID'
                                    },
                                    {
                                        data: 'student_name',
                                        title: 'Student Name'
                                    }

                                ];

                                data.components.forEach(component => {
                                    columns.push({
                                        data: `components.${component.main_component_id}.main_result`,
                                        defaultContent: '-'
                                    });
                                    const componentResits = data.componentResits[component.main_component_id];
                                    for (let i = 1; i <= 7; i++) {
                                        if (componentResits[`resit_${i}`]) {
                                            columns.push({
                                                data: `components.${component.main_component_id}.resit_${i}`,
                                                defaultContent: '-'
                                            });
                                        }
                                    }
                                });

                                // Add Final Result column for both IFD and ECM
                                // columns.push({
                                //     data: 'final_result',
                                //     defaultContent: '-',
                                //     title: 'Final Result (Module)',
                                //     render: function(data, type, row) {
                                //         return `${data}`; // Show final result with ID
                                //     }
                                // });


                                columns.push({
                                    data: 'final_result',
                                    defaultContent: '-',
                                    title: 'Final Result (Module)',
                                    render: function(value, type, row) {

                                        if (value === null || value === undefined || value === '') return '-';

                                        // me done on 27.02.2026 (here inlcuded the Not Submitted and absent students)
                                        if (data.programme_type === 'HD') {
                                            const markInfo = getHDMarkInfo(value);
                                            const finalHDGrade = calculateHDGradeFromComponents(row, data.components, value) || markInfo.grade;

                                            if (finalHDGrade === 'Not Submitted' || finalHDGrade === 'Absent') {
                                                return finalHDGrade;
                                            }

                                            if (markInfo.mark === null || typeof markInfo.mark !== 'number') {
                                                return finalHDGrade || value;
                                            }

                                            let output = `${markInfo.mark} - ${finalHDGrade}`;

                                            if (markInfo.ruleApplied) {
                                                return `
                                                    ${output}
                                                    <br>
                                                    <small style="color:green;font-weight:600;">
                                                        (2.6 Rule Applied)
                                                    </small>`;
                                            }

                                            return output;
                                        }

                                        let mark = parseFloat(value);
                                        if (isNaN(mark)) return value;

                                        // Other programmes unchanged
                                        return value;
                                    }
                                });


                                // Add Grades of Modules column only for ECM ✅✅✅✅
                                if (data.programme_type === 'ECM') {


                                    columns.push({
                                        data: 'final_result',
                                        title: 'Modules Grades',
                                        render: function(data) {
                                            let grade = 'Not Available';

                                            if (data !== null) {
                                                // Try parsing as float
                                                const finalResult = parseFloat(data);

                                                // Check if the parsed value is actually numeric
                                                if (!isNaN(finalResult)) {
                                                    if (finalResult >= 70) {
                                                        grade = 'Distinction';
                                                    } else if (finalResult >= 60) {
                                                        grade = 'Merit';
                                                    } else if (finalResult >= 50) {
                                                        grade = 'Pass';
                                                    } else {
                                                        grade = 'Resit';
                                                    }
                                                } else {
                                                    // If not numeric, just print the original value (string)
                                                    grade = data;
                                                }
                                            }

                                            return grade;
                                        }
                                    });

                                    columns.push({
                                        data: null,
                                        title: 'Email',
                                        render: function(data, type, row) {
                                            return `<button class="btn btn-primary btn-sm send-email" data-student="${row.student_id}" data-id="${row.id}" data-program="${programmeName}">Send Email</button>`;
                                        }
                                    });
                                }


                                if (data.programme_type === 'HDs' || data.programme_type === 'GDM' || data.programme_type === 'IFD' || data.programme_type === 'BTEC') {
                                    columns.push({
                                        data: null,
                                        title: 'Email',
                                        render: function(data, type, row) {
                                            return `<button class="btn btn-primary btn-sm send-email" data-student="${row.student_id}" data-id="${row.id}" data-program="${programmeName}">Send Email</button>`;
                                        }
                                    });
                                }

                                // Initialize DataTable
                                const table = $('#resultsTable').DataTable({
                                    destroy: true, // Ensure no duplication
                                    data: data.results,
                                    columns: columns,
                                    pageLength: 500,
                                    ordering: true,
                                    responsive: true,
                                    scrollX: true
                                });
                            }


                            $('#resultsTable').off('click', '.send-email'); // Remove previous event handlers to avoid duplication

                            // single row send email button
                            $('#resultsTable').on('click', '.send-email', function() {
                                const studentId = $(this).data('student'); // Get the student ID
                                const finalResultId = $(this).data('id'); // Get the final result ID
                                const programName = $(this).data('program'); // Get the program name
                                const programId = $('#programme').val(); // Capture the program ID
                                const batchId = $('#batch').val(); // Capture the batch ID
                                const moduleId = $('#module').val(); // Capture the module ID
                                const mainComponentId = $('#mainComponent').val(); // Capture the main component ID
                                const subComponentId = $('#subComponent').val(); // Capture the sub component ID
                                const studentStatus = $('#studentStatus').val();

                                // Construct query parameters
                                let queryParams = `id=${finalResultId}&student_id=${studentId}&program=${encodeURIComponent(programName)}&program_id=${programId}`;

                                // Ensure required fields are included
                                if (batchId) {
                                    queryParams += `&batch_id=${batchId}`;
                                }
                                if (moduleId) {
                                    queryParams += `&module_id=${moduleId}`;
                                }
                                if (mainComponentId) {
                                    queryParams += `&main_component_id=${mainComponentId}`;
                                }
                                if (subComponentId) {
                                    queryParams += `&sub_component_id=${subComponentId}`;
                                }
                                if (studentStatus) {
                                    queryParams += `&student_status=${encodeURIComponent(studentStatus)}`;
                                }

                                // Open single_views.php with the constructed query parameters
                                window.open(`single_views.php?${queryParams}`, '_blank');
                            });

                            // send to all button
                            $('#sendToAll').on('click', function() {
                                const programmeId = $('#programme').val();
                                const programmeName = $('#programme option:selected').text();
                                const batchId = $('#batch').val();
                                const moduleId = $('#module').val();
                                const mainComponentId = $('#mainComponent').val(); // Capture the main component ID
                                const subComponentId = $('#subComponent').val(); // Capture the sub component ID
                                const studentStatus = $('#studentStatus').val();

                                // Construct query parameters
                                let queryParams = `programmeId=${programmeId}&programmeName=${encodeURIComponent(programmeName)}&batchId=${batchId}&moduleId=${moduleId}`;


                                // Ensure required fields are included
                                if (mainComponentId) {
                                    queryParams += `&mainComponentId=${mainComponentId}`;
                                }
                                if (subComponentId) {
                                    queryParams += `&subComponentId=${subComponentId}`;
                                }
                                if (studentStatus) {
                                    queryParams += `&studentStatus=${encodeURIComponent(studentStatus)}`;
                                }

                                // Check URL before redirecting
                                console.log("Redirecting to:", 'all_results.php?' + queryParams);

                                // Redirect to all_results.php with the constructed query parameters
                                if (programmeName === "Bachelor of Business Management (Hons)") {
                                    window.location.href = 'testing.php?' + queryParams;
                                } else {
                                    window.location.href = 'all_results.php?' + queryParams;
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error details:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            alert('Error fetching results. Check console for details.');
                        }
                    });
                }
            }


        });
    </script>

</div>
</body>

</html>
