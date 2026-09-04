<?php
include("database/connection.php");
$current_url = basename($_SERVER['REQUEST_URI'], ".php");
// session user_id 
// $userId = $_SESSION['user_id'] ?? null;
// $userRole =  $_SESSION['role'] ?? null;


$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : null;
// $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;


// Fetching sub_list_value from the user_permission table for the logged-in user
$query = "SELECT sub_list_value FROM user_permission WHERE user_id = '$userId'";
$result_for_up = mysqli_query($conn, $query);
// Initialize an array to store sub_list_values
$subListValues = [];

if ($result_for_up) {
    while ($row = mysqli_fetch_assoc($result_for_up)) {
        $subListValues[] = $row['sub_list_value'];
    }
} else {
    echo "Error fetching permissions: " . mysqli_error($conn); // Corrected connection variable
}


?>

<style>
    .nav-item.active .nav-link {
        color: white !important;
        background: rgb(203, 0, 0);
        background: radial-gradient(circle, rgba(203, 0, 0, 1) 0%, rgba(110, 4, 4, 1) 92%);
        /* border-radius: 5px; */
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
    }

    #navHover:hover .nav-link {
        color: white !important;
        background: rgb(203, 0, 0);
        background: radial-gradient(circle, rgba(203, 0, 0, 1) 0%, rgba(110, 4, 4, 1) 92%);
        /* border-radius: 5px; */
        transition: background-color 0.3s ease;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
    }

    a {
        font-size: 14px;
    }

    .top_bottom_line {
        /* border-top: 2px solid whitesmoke; */
        border-bottom: 2px solid whitesmoke;
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar" style="font-size: 10px;">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center mt-4 mb-4" href="index">
        <div class="sidebar-brand-icon rotate-n-15">
            <!-- <i class="fas fa-laugh-wink"></i> -->
        </div>
        <!-- <div class="sidebar-brand-text mx-3">IMS <sup>2</sup></div> -->
        <div class="sidebar-brand-text mx-3"><img src="https://202.124.164.112:8140/img/logo4.png" class="img-fluid"></div>
    </a>
    <!-- Divider -->
    <hr class="sidebar-divider my-0">
    <!-- Nav Item - Dashboard -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'index') ? 'active' : '' ?>" id="navHover">
        <a class="nav-link" href="index">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>



<!--  ----------------------------------------- MASTER FILE SECTION --------------------------------------------------- -->
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'create_lead' ||
                                            $current_url == 'db_tables_count' ||
                                            $current_url == 'create_year' ||
                                            $current_url == 'create_semester' ||
                                            $current_url == 'create_criteria' ||
                                            $current_url == 'create_universities' ||
                                            $current_url == 'create_coordinators' ||
                                            $current_url == 'create_program' ||
                                            $current_url == 'induction_master' ||
                                            $current_url == 'assign_components_allocations' ||
                                            $current_url == 'program_payment_allocation' ||
                                            $current_url == 'create_lecturer' ||
                                            $current_url == 'master_nav_collection' ||
                                            $current_url == 'create_module' ||
                                            $current_url == 'create_batch' ||
                                            $current_url == 'create_grade' ||
                                            $current_url == 'create_currency' ||
                                            $current_url == 'create_status' ||
                                            $current_url == 'create_assignment_components' ||
                                            $current_url == 'create_decision'
                                        ) ? 'active' : '' ?>" id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOne"
            aria-expanded="true" aria-controls="collapseOne">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Master File</span>
        </a>

        <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">IMS Components</h6>

                <a
                    class="collapse-item <?= ($current_url == 'create_lead' && in_array('Lead Types - create_lead', $subListValues)) ? 'active' : '' ?>"
                    href="create_lead"
                    <?= !in_array('Lead Types - create_lead', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Lead Types</a>

                <a class="collapse-item <?= ($current_url == 'db_tables_count' && in_array('DBTablesCount - db_tables_count', $subListValues)) ? 'active' : '' ?>" href="./db_tables_count" <?= !in_array('DBTablesCount - db_tables_count', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>DB tables count</a>
                <a class="collapse-item <?= ($current_url == 'create_year' && in_array('Year - create_year', $subListValues)) ? 'active' : '' ?>" href="./create_year" <?= !in_array('Year - create_year', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Year</a>
                <a class="collapse-item <?= ($current_url == 'create_semester' && in_array('Semester - create_semester', $subListValues)) ? 'active' : '' ?>" href="./create_semester" <?= !in_array('Semester - create_semester', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Semester</a>
                <a class="collapse-item <?= ($current_url == 'create_criteria' && in_array('Criteria - create_criteria', $subListValues)) ? 'active' : '' ?>" href="./create_criteria" <?= !in_array('Criteria - create_criteria', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Criteria</a>
                <a class="collapse-item <?= ($current_url == 'create_universities' && in_array('University - create_universities', $subListValues)) ? 'active' : '' ?>" href="./create_universities" <?= !in_array('University - create_universities', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>University</a>
                <a class="collapse-item <?= ($current_url == 'create_coordinators' && in_array('Coordinator - create_coordinators', $subListValues)) ? 'active' : '' ?>" href="./create_coordinators" <?= !in_array('Coordinator - create_coordinators', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Coordinator</a>
                <a class="collapse-item <?= ($current_url == 'create_program' && in_array('Program - create_program', $subListValues)) ? 'active' : '' ?>" href="./create_program" <?= !in_array('Program - create_program', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Program</a>
                <a class="collapse-item <?= ($current_url == 'program_payment_allocation' && in_array('Batch Payment Allocation - program_payment_allocation', $subListValues)) ? 'active' : '' ?>" href="./program_payment_allocation" <?= !in_array('Batch Payment Allocation - program_payment_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Batch Payment Allocation</a>
                <a class="collapse-item <?= ($current_url == 'create_assignment_components' && in_array('Assignment Components - create_assignment_components', $subListValues)) ? 'active' : '' ?>" href="./create_assignment_components" <?= !in_array('Assignment Components - create_assignment_components', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>> Add Components</a>
                <a class="collapse-item <?= ($current_url == 'create_lecturer' && in_array('Lecture - create_lecturer', $subListValues)) ? 'active' : '' ?>" href="./create_lecturer" <?= !in_array('Lecture - create_lecturer', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Lecture</a>
                <a class="collapse-item <?= ($current_url == 'create_module' && in_array('Module - create_module', $subListValues)) ? 'active' : '' ?>" href="./create_module" <?= !in_array('Module - create_module', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Module</a>
                <a class="collapse-item <?= ($current_url == 'create_batch' && in_array('Batch - create_batch', $subListValues)) ? 'active' : '' ?>" href="./create_batch" <?= !in_array('Batch - create_batch', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Batch</a>
                <a class="collapse-item <?= ($current_url == 'create_grade' && in_array('Grade - create_grade', $subListValues)) ? 'active' : '' ?>" href="./create_grade" <?= !in_array('Grade - create_grade', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Grade</a>
                <a class="collapse-item <?= ($current_url == 'create_currency' && in_array('Currency - create_currency', $subListValues)) ? 'active' : '' ?>" href="./create_currency" <?= !in_array('Currency - create_currency', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Currency</a>
                <a class="collapse-item <?= ($current_url == 'create_status' && in_array('Status - create_status', $subListValues)) ? 'active' : '' ?>" href="./create_status" <?= !in_array('Status - create_status', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Status</a>
                 <a class="collapse-item <?= ($current_url == 'assign_components_allocations' && in_array('Components Allocations - assign_components_allocations', $subListValues)) ? 'active' : '' ?>" href="assign_components_allocations" <?= !in_array('Components Allocations - assign_components_allocations', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Components Allocations</a>
                
                
                <!-- Nav colelction CRUD  -->
                <a class="collapse-item <?= ($current_url == 'master_nav_collection' && in_array('Nav Collection CRUD - master_nav_collection', $subListValues)) ? 'active' : '' ?>" href="master_nav_collection" <?= !in_array('Nav Collection CRUD - master_nav_collection', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Nav Collection CRUD</a>


             
                <a
                    class="collapse-item <?= ($current_url == 'create_decision' && in_array('Decision - create_decision', $subListValues)) ? 'active' : '' ?>"
                    href="./create_decision"
                    <?= !in_array('Decision - create_decision', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Decision</a>
                    
                       <!-- induction  section  -->
                 <h6 class="collapse-header">INDUCTION Components</h6>
                 
                <a class="collapse-item <?= ($current_url == 'induction_master' && in_array('Induction Edit - induction_master', $subListValues)) ? 'active' : '' ?>"
                    href="./induction_master" <?= !in_array('Induction Edit - induction_master', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Edit(New)</a>
                
                <a class="collapse-item <?= ($current_url == 'induction_master_db' && in_array('Induction Edit From DB - induction_master_db', $subListValues)) ? 'active' : '' ?>"
                    href="induction_master_db" <?= !in_array('Induction Edit From DB - induction_master_db', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Edit(From DB)</a>


            </div>
        </div>
    </li>



<!--  ----------------------------------------- TRANSACTION FILE SECTION --------------------------------------------------- -->


 <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- recruitment section (14 COUNT) 02.04.2026   -->
    
    <li class="nav-item top_bottom_line <?= ($current_url == 'addLeads'
                                            || $current_url == 'online_registration_data'
                                            || $current_url == 'uploadStudents'
                                            || $current_url == 'allocateProgram'
                                            || $current_url == 'bms_email_allocation'
                                            || $current_url == 'studentID_allocation'
                                            || $current_url == 'UpdateStudentStatus'
                                            || $current_url == 'batch_transfer'
                                            || $current_url == 'batchSwap'
                                            || $current_url == 'programProgression'
                                            || $current_url == 'offer_letter'
                                            || $current_url == 'get_data_from_std'
                                            || $current_url == 'leadsReport'
                                            || $current_url == 'uploadScanCopies'
                                            || $current_url == 'UpdateElectiveModule'
                                            || $current_url == 'studentRegister') ? 'active' : '' ?> "
        id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRecruitmentSectionsection" aria-expanded="true"
            aria-controls="collapseRecruitmentSectionsection">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>Recruitment</span>
        </a>
        <div id="collapseRecruitmentSectionsection" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>



                <a class="collapse-item <?= ($current_url == 'addLeads' && in_array('Add Leads - addLeads', $subListValues)) ? 'active' : '' ?>"
                    href="addLeads" <?= !in_array('Add Leads - addLeads', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Lead </a>

                <a class="collapse-item <?= ($current_url == 'studentRegister' && in_array('Student Registration - studentRegister', $subListValues)) ? 'active' : '' ?>"
                    href="studentRegister" <?= !in_array('Student Registration - studentRegister', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Registration</a>

                <!-- ---------------- Online Registration Data -------------------------  -->

                <a class="collapse-item <?= ($current_url == 'online_registration_data' && in_array('Online Registration Data - online_registration_data', $subListValues)) ? 'active' : '' ?>"
                    href="online_registration_data" <?= !in_array('Online Registration Data - online_registration_data', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Online Registration Data</a>

                <!-- -----------------------------------------  -->

                <a class="collapse-item <?= ($current_url == 'uploadStudents' && in_array('Upload Students - uploadStudents', $subListValues)) ? 'active' : '' ?>"
                    href="uploadStudents" <?= !in_array('Upload Students - uploadStudents', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Upload Students</a>

                <a class="collapse-item <?= ($current_url == 'allocateProgram' && in_array('Allocate Program - allocateProgram', $subListValues)) ? 'active' : '' ?>"
                    href="allocateProgram" <?= !in_array('Allocate Program - allocateProgram', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Allocate Program</a>

                <a class="collapse-item <?= ($current_url == 'bms_email_allocation' && in_array('BMS Email Allocation - bms_email_allocation', $subListValues)) ? 'active' : '' ?>"
                    href="bms_email_allocation" <?= !in_array('BMS Email Allocation - bms_email_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>BMS Email Allocation</a>

                <a class="collapse-item <?= ($current_url == 'studentID_allocation' && in_array('StudentID Allocation - studentID_allocation', $subListValues)) ? 'active' : '' ?>"
                    href="studentID_allocation" <?= !in_array('StudentID Allocation - studentID_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>StudentID Allocation</a>

                <a class="collapse-item <?= ($current_url == 'UpdateStudentStatus' && in_array('Update Student Status - UpdateStudentStatus', $subListValues)) ? 'active' : '' ?>"
                    href="UpdateStudentStatus.php" <?= !in_array('Update Student Status - UpdateStudentStatus', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Update Student Status</a>

                <a class="collapse-item <?= ($current_url == 'batch_transfer' && in_array('Student Batch Transfer - batch_transfer', $subListValues)) ? 'active' : '' ?>"
                    href="batch_transfer" <?= !in_array('Student Batch Transfer - batch_transfer', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Batch Transfer </a>

                <a class="collapse-item <?= ($current_url == 'batchSwap' && in_array('Student Batch Swap - batchSwap', $subListValues)) ? 'active' : '' ?>"
                    href="batchSwap" <?= !in_array('Student Batch Swap - batchSwap', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Batch Swap </a>

                <a class="collapse-item <?= ($current_url == 'programProgression' && in_array('Student Program Progression - programProgression', $subListValues)) ? 'active' : '' ?>"
                    href="programProgression" <?= !in_array('Student Program Progression - programProgression', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Program Progression
                </a>

                <!--<a class="collapse-item <?= ($current_url == 'UpdateElectiveModule' && in_array('Update Students E Module - UpdateElectiveModule', $subListValues)) ? 'active' : '' ?>"-->
                <!--    href="UpdateElectiveModule" <?= !in_array('Update Students E Module - UpdateElectiveModule', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>><strike>Update Students E-->
                <!--        Module</strike></a>-->

                <a class="collapse-item <?= ($current_url == 'uploadScanCopies' && in_array('Upload Student Documents - uploadScanCopies', $subListValues)) ? 'active' : '' ?>"
                    href="uploadScanCopies" <?= !in_array('Upload Student Documents - uploadScanCopies', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Upload Student Documents </a>

                <a class="collapse-item <?= ($current_url == 'offer_letter' && in_array('Send Offer Letter - offer_letter', $subListValues)) ? 'active' : '' ?>"
                    href="offer_letter" <?= !in_array('Send Offer Letter - offer_letter', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Send Offer Letter</a>


                <h6 class="collapse-header">Report</h6>

                <a class="collapse-item <?= ($current_url == 'leadsReport') ? 'active' : '' ?>" href="leadsReport"
                    <?= !in_array('Leads Report - leadsReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Leads Report</a>

                <a class="collapse-item <?= ($current_url == 'get_data_from_std') ? 'active' : '' ?>"
                    href="get_data_from_std" <?= !in_array('Online Registration Report - get_data_from_std', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Online Registration
                    Report</a>


            </div>
        </div>
    </li>

<!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- EXAM / ASSIGNMENT SECTIONs NEW ON 02.04.2026 -->
    
    <li class="nav-item top_bottom_line <?= ($current_url == 'exams'
                                            || $current_url == 'assesmentDocumentSend'
                                            || $current_url == 'exam_result'
                                            || $current_url == 'SpecialClassMessages'
                                            || $current_url == 'AddTimeTable'
                                            || $current_url == 'AddDecision'
                                            || $current_url == 'Alumni'
                                            || $current_url == 'ListBoard'
                                            || $current_url == 'specialReason'
                                            || $current_url == 'module_result_mail'
                                            || $current_url == 'BBM_result_report'
                                            || $current_url == 'specialReasonReport'
                                            || $current_url == 'exam_report'
                                            || $current_url == 'result_mailing_report'
                                            || $current_url == 'timeTableReport'
                                            || $current_url == 'exam_result_send') ? 'active' : '' ?> "
        id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseExamAssignmentsection" aria-expanded="true"
            aria-controls="collapseExamAssignmentsection">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Exam </span>
        </a>
        <div id="collapseExamAssignmentsection" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>

                <!-- EXAM SECTION MOVING  -->

                <a class="collapse-item <?= ($current_url == 'exams' && in_array('Assesment Management - exams', $subListValues)) ? 'active' : '' ?>"
                    href="exams" <?= !in_array('Assesment Management - exams', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Assesment Management </a>

                <a class="collapse-item <?= ($current_url == 'assesmentDocumentSend' && in_array('Assesment Document Send - assesmentDocumentSend', $subListValues)) ? 'active' : '' ?>"
                    href="assesmentDocumentSend" <?= !in_array('Assesment Document Send - assesmentDocumentSend', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Assesment Document Send </a>

                <a class="collapse-item <?= ($current_url == 'exam_result' && in_array('Exam / Assignment Result - exam_result', $subListValues)) ? 'active' : '' ?>"
                    href="exam_result" <?= !in_array('Exam / Assignment Result - exam_result', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Exam / Assignment Result</a>

                <a class="collapse-item <?= ($current_url == 'exam_result_send' && in_array('E/A Result Send - exam_result_send', $subListValues)) ? 'active' : '' ?>"
                    href="exam_result_send" <?= !in_array('E/A Result Send - exam_result_send', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Result Send</a>

                <a class="collapse-item <?= ($current_url == 'module_result_mail' && in_array('Module Results Mail - module_result_mail', $subListValues)) ? 'active' : '' ?>"
                    href="module_result_mail" <?= !in_array('Module Results Mail - module_result_mail', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Module Results Mail</a>

                <a class="collapse-item <?= ($current_url == 'AddTimeTable' && in_array('Time Table - AddTimeTable', $subListValues)) ? 'active' : '' ?>"
                    href="AddTimeTable" <?= !in_array('Time Table - AddTimeTable', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Time Table</a>

                <a class="collapse-item <?= ($current_url == '' && in_array('Daily Time Table Message', $subListValues)) ? 'active' : '' ?>"
                    href="" <?= !in_array('Daily Time Table Message', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Daily Time Table Message</a>

                <a class="collapse-item <?= ($current_url == 'SpecialClassMessages' && in_array('Special Class Messages - SpecialClassMessages', $subListValues)) ? 'active' : '' ?>"
                    href="SpecialClassMessages" <?= !in_array('Special Class Messages - SpecialClassMessages', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Special Class Messages</a>


                <a class="collapse-item <?= ($current_url == 'specialReason' && in_array('Special Reason - specialReason', $subListValues)) ? 'active' : '' ?>"
                    href="specialReason" <?= !in_array('Special Reason - specialReason', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Special Reason</a>

                <a class="collapse-item <?= ($current_url == 'AddDecision' && in_array('Add Decision', $subListValues)) ? 'active' : '' ?>"
                    href="AddDecision" <?= !in_array('Add Decision', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Decision</a>

                <a class="collapse-item <?= ($current_url == 'Alumni' && in_array('Alumni', $subListValues)) ? 'active' : '' ?>"
                    href="Alumni" <?= !in_array('Alumni', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Alumni</a>

                <a class="collapse-item <?= ($current_url == 'ListBoard' && in_array('List Board', $subListValues)) ? 'active' : '' ?>"
                    href="ListBoard" <?= !in_array('List Board', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>List Board</a>

                <h6 class="collapse-header">Report</h6>


                <a class="collapse-item <?= ($current_url == 'BBM_result_report') ? 'active' : '' ?>"
                    href="BBM_result_report" <?= !in_array('BBM result report - BBM_result_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>BBM result report</a>

                <a class="collapse-item <?= ($current_url == 'specialReasonReport') ? 'active' : '' ?>"
                    href="specialReasonReport" <?= !in_array('Special Reason Report - specialReasonReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Special Reason Report</a>

                <a class="collapse-item <?= ($current_url == 'exam_report') ? 'active' : '' ?>" href="exam_report"
                    <?= !in_array('Exam / Assignment Report - exam_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Exam / Assignment Report</a>

                <a class="collapse-item <?= ($current_url == 'result_mailing_report') ? 'active' : '' ?>"
                    href="result_mailing_report" <?= !in_array('Result Mailing Report - result_mailing_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Result Mailing Report (w)</a>

                <a class="collapse-item <?= ($current_url == 'timeTableReport') ? 'active' : '' ?>"
                    href="timeTableReport" <?= !in_array('Time Table Report - timeTableReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Time Table Report</a>


            </div>
        </div>
    </li>



    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- HRRRRRRRRRRRR  -->
    
   
       <li class="nav-item top_bottom_line <?= ($current_url == 'feedback_link_generate' || $current_url == 'feedback_report') ? 'active' : '' ?> "
        id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHRsection" aria-expanded="true"
            aria-controls="collapseHRsection">
            <i class="fas fa-fw fa-user-tie"></i>
            <span>HR</span>
        </a>
        <div id="collapseHRsection" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'feedback_link_generate' && in_array('Feedback Link Generate - feedback_link_generate', $subListValues)) ? 'active' : '' ?>"
                    href="feedback_link_generate" <?= !in_array('Feedback Link Generate - feedback_link_generate', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Feedback Link Generate </a>


                <h6 class="collapse-header">Report</h6>
               
                <a class="collapse-item <?= ($current_url == 'feedback_report' && in_array('Feedback Report - feedback_report', $subListValues)) ? 'active' : '' ?>"
                    href="feedback_report" <?= !in_array('Feedback Report - feedback_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Feedback Report </a>

                <a class="collapse-item <?= ($current_url == 'final_year_report' && in_array('Final Year Report - final_year_report', $subListValues)) ? 'active' : '' ?>"
                    href="final_year_report" <?= !in_array('Final Year Report - final_year_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Final Year Report </a>

            </div>
        </div>
    </li>
    
    
    <!-- here wanna create a finance separate section -->
    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- Finance  -->
    <li class="nav-item top_bottom_line <?= ($current_url == 'add_payment_plan' ||
                                            $current_url == 'batch_wise_payment_plan' ||
                                            $current_url == 'payment' ||
                                            $current_url == 'AdditionalFee' ||
                                            $current_url == 'check_all_data_for' ||
                                            $current_url == 'outstandingPayment' ||
                                            $current_url == 'paymentReport' ||
                                            $current_url == 'penaltyPaymentReport' ||
                                            $current_url == 'additionalPaymentReport' ||
                                            $current_url == 'penalty_pay') ? 'active' : '' ?> "
        id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFinancesection" aria-expanded="true"
            aria-controls="collapseFinancesection">
            <i class="fas fa-fw fa-coins"></i>
            <span>Finance</span>
        </a>
        <div id="collapseFinancesection" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>

                <a class="collapse-item <?= ($current_url == 'add_payment_plan' && in_array('Add Payment Plan - add_payment_plan', $subListValues)) ? 'active' : '' ?>"
                    href="add_payment_plan.php" <?= !in_array('Add Payment Plan - add_payment_plan', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Payment Plan</a>

                <a class="collapse-item <?= ($current_url == 'batch_wise_payment_plan' && in_array('Batch Wise Payment Plan - batch_wise_payment_plan', $subListValues)) ? 'active' : '' ?>"
                    href="batch_wise_payment_plan" <?= !in_array('Batch Wise Payment Plan - batch_wise_payment_plan', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Batch Wise Payment Plan</a>

                <a class="collapse-item <?= ($current_url == 'payment' && in_array('Payment - payment', $subListValues)) ? 'active' : '' ?>"
                    href="payment" <?= !in_array('Payment - payment', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Payment</a>

                <a class="collapse-item <?= ($current_url == 'penalty_pay' && in_array('Penalty Payment - penalty_pay', $subListValues)) ? 'active' : '' ?>"
                    href="penalty_pay" <?= !in_array('Penalty Payment - penalty_pay', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Penalty Payment</a>

                <a class="collapse-item <?= ($current_url == 'AdditionalFee' && in_array('Additional Payment - AdditionalFee', $subListValues)) ? 'active' : '' ?>"
                    href="AdditionalFee" <?= !in_array('Additional Payment - AdditionalFee', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Additional Payment</a>

                <h6 class="collapse-header">Report</h6>


                <a class="collapse-item <?= ($current_url == 'check_all_data_for') ? 'active' : '' ?>"
                    href="check_all_data_for" <?= !in_array('Payment Check Report - check_all_data_for', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Payment Check Report</a>

                <a class="collapse-item <?= ($current_url == 'outstandingPayment') ? 'active' : '' ?>"
                    href="outstandingPayment" <?= !in_array('Outstanding Payment - outstandingPayment', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Outstanding Payment</a>

                <a class="collapse-item <?= ($current_url == 'paymentReport') ? 'active' : '' ?>" href="paymentReport"
                    <?= !in_array('Payment Report - paymentReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Payment Report</a>

                <a class="collapse-item <?= ($current_url == 'penaltyPaymentReport') ? 'active' : '' ?>"
                    href="penaltyPaymentReport" <?= !in_array('Penalty Payment Report - penaltyPaymentReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Penalty Payment Report</a>

                <a class="collapse-item <?= ($current_url == 'additionalPaymentReport') ? 'active' : '' ?>"
                    href="additionalPaymentReport" <?= !in_array('Additional Payment Report - additionalPaymentReport', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Additional Payment Report</a>

            </div>
        </div>
    </li>



<!--  ----------------------------------------- ONE TO ONE FILE SECTION BY THILSHATH --------------------------------------------------- -->

 <!-- One to One  -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'tutor_time_allocation' 
                                            || $current_url == 'create_session'
                                            || $current_url == 'tutor_allocation'
                                            || $current_url == 'tutor_session'
                                            || $current_url == 'tutor_view') ? 'active' : '' ?>" id="navHover">
        
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsOneToOne"
            aria-expanded="true" aria-controls="collapsOneToOne">
            <i class="fas fa-solid fa-people-arrows"></i>
            <span>One To One</span>
        </a>
        <div id="collapsOneToOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                
                <a class="collapse-item <?= ($current_url == 'create_session' && in_array('Create Session - create_session', $subListValues)) ? 'active' : '' ?>" href="create_session" <?= !in_array('Create Session - create_session', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Create Session</a>

                <a class="collapse-item <?= ($current_url == 'tutor_allocation' && in_array('Tutor Allocation - tutor_allocation', $subListValues)) ? 'active' : '' ?>" href="tutor_allocation" <?= !in_array('Tutor Allocation - tutor_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Tutor Allocation</a>

                <a class="collapse-item <?= ($current_url == 'tutor_session' && in_array('Tutor Session - tutor_session', $subListValues)) ? 'active' : '' ?>" href="tutor_session" <?= !in_array('Tutor Session - tutor_session', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Tutor Session Allocation</a>
           
                
                <a class="collapse-item <?= ($current_url == 'tutor_time_allocation') ? 'active' : '' ?>" href="tutor_time_allocation" <?= !in_array('Tutor Time Allocation - tutor_time_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Time Allocation</a>
                <a class="collapse-item <?= ($current_url == 'tutor_view') ? 'active' : '' ?>" href="tutor_view" <?= !in_array('Tutor View - tutor_view', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Time Slot View</a>
                
            </div>
        </div>
    </li>
    
    
     <!--  ----------------------------------------- OPERATIONS FILE SECTION BY THILSHATH --------------------------------------------------- -->

 <!-- OPERATIONS  -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'classroom_reservation' 
                                            || $current_url == 'add_classroom') ? 'active' : '' ?>" id="navHover">
        
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsOperation"
            aria-expanded="true" aria-controls="collapsOperation">
            <i class="fas fa-users-cog"></i>
            <span>Operations</span>
        </a>
        <div id="collapsOperation" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'classroom_reservation') ? 'active' : '' ?>" href="classroom_reservation" <?= !in_array('Classroom Reservation - classroom_reservation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Classroom Reservation</a>
                <a class="collapse-item <?= ($current_url == 'coordinator_calendar') ? 'active' : '' ?>" href="coordinator_calendar" <?= !in_array('Coordinator Calendar - coordinator_calendar', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Booking Timetable</a>
                <a class="collapse-item <?= ($current_url == 'timetable_calendar') ? 'active' : '' ?>" href="timetable_calendar" <?= !in_array('Timetable - timetable_calendar', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Timetable</a>
                <a class="collapse-item <?= ($current_url == 'class_allocation') ? 'active' : '' ?>" href="class_allocation" <?= !in_array('Class Allocation - class_allocation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Room Allocation</a>
                <a class="collapse-item <?= ($current_url == 'add_classroom') ? 'active' : '' ?>" href="add_classroom" <?= !in_array('Add Classroom - add_classroom', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Rooms</a>
                <a class="collapse-item <?= ($current_url == 'add_inventory') ? 'active' : '' ?>" href="add_inventory" <?= !in_array('Add Inventory - add_inventory', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Room Inventory</a>
                
                
            </div>
        </div>
    </li>

    <!--  ----------------------------------------- DIGITAL FILE SECTION BY THILSHATH --------------------------------------------------- -->

 <!-- DIGITAL -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'tv_image') ? 'active' : '' ?>" id="navHover">
        
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsDigital"
            aria-expanded="true" aria-controls="collapsDigital">
            <i class="fas fa-icons"></i>
            <span>Digital</span>
        </a>
        <div id="collapsDigital" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'tv_image') ? 'active' : '' ?>" href="tv_image" <?= !in_array('TV Folder Drive - tv_image', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>TV Folder Drive</a>
                
                
            </div>
        </div>
    </li>


 <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- INDUCTION SECTION  -->
    
    <li class="nav-item top_bottom_line <?= ($current_url == 'induction_scan'
        || $current_url == 'total_induction_students'
        || $current_url == 'upload_induction_students'
        || $current_url == 'induction_from_db_email_send'
        || $current_url == 'induction_email_report'
        || $current_url == 'induction_email_body_page'
        || $current_url == 'induction_scan_db'
        || $current_url == 'induction_fl_report_db') ? 'active' : '' ?> " id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseInductionSectionsection"
            aria-expanded="true" aria-controls="collapseInductionSectionsection">
            <i class="fas fa-fw fa-graduation-cap"></i>
            <span>Induction</span>
        </a>
        <div id="collapseInductionSectionsection" class="collapse" aria-labelledby="headingOne"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                
                <h6 class="collapse-header">FROM OUT SOURCE</h6>

                <a class="collapse-item <?= ($current_url == 'induction_scan' && in_array('Induction Scan - induction_scan', $subListValues)) ? 'active' : '' ?>"
                    href="induction_scan" <?= !in_array('Induction Scan - induction_scan', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Scan</a>

                <a class="collapse-item <?= ($current_url == 'upload_induction_students' && in_array('Induction Upload Student - upload_induction_students', $subListValues)) ? 'active' : '' ?>"
                    href="upload_induction_students" <?= !in_array('Induction Upload Student - upload_induction_students', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Upload Student</a>


                <a class="collapse-item <?= ($current_url == 'total_induction_students' && in_array('Total Induction Student List - total_induction_students', $subListValues)) ? 'active' : '' ?>"
                    href="total_induction_students" <?= !in_array('Total Induction Student List - total_induction_students', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Total Induction Student List</a>



                <h6 class="collapse-header">FROM DB</h6>

                <a class="collapse-item <?= ($current_url == 'induction_from_db_email_send' && in_array('Induction Email Send - induction_from_db_email_send', $subListValues)) ? 'active' : '' ?>"
                    href="induction_from_db_email_send" <?= !in_array('Induction Email Send - induction_from_db_email_send', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Email Send</a>

                <a class="collapse-item <?= ($current_url == 'induction_scan_db' && in_array('Induction Scan From DB - induction_scan_db', $subListValues)) ? 'active' : '' ?>"
                    href="induction_scan_db" <?= !in_array('Induction Scan From DB - induction_scan_db', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Scan From DB</a>

                <a class="collapse-item <?= ($current_url == 'induction_email_body_page' && in_array('Induction Email Template - induction_email_body_page', $subListValues)) ? 'active' : '' ?>"
                    href="induction_email_body_page" <?= !in_array('Induction Email Template - induction_email_body_page', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Email Template</a>


                <h6 class="collapse-header">Report</h6>
    
                
                <a class="collapse-item <?= ($current_url == 'induction_fl_report_db' && in_array('Induction DB Student List - induction_fl_report_db', $subListValues)) ? 'active' : '' ?>"
                    href="induction_fl_report_db" <?= !in_array('Induction DB Student List - induction_fl_report_db', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction DB Student List</a>

                <a class="collapse-item <?= ($current_url == 'induction_email_report' && in_array('Induction Email Report - induction_email_report', $subListValues)) ? 'active' : '' ?>"
                    href="induction_email_report" <?= !in_array('Induction Email Report - induction_email_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Induction Email Report</a>

    
            </div>
        </div>
    </li>
    
    
     <!--  ----------------------------------------- Library FILE SECTION BY THILSHATH --------------------------------------------------- -->

 <!-- Library  -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'library_dashboard' 
                                            || $current_url == 'borrow'
                                            || $current_url == 'return'
                                            || $current_url == 'book'
                                            || $current_url == 'category'
                                            || $current_url == 'lib_dashboard'
                                            || $current_url == 'lib_report_view'
                                            || $current_url == 'lib_catagory_items') ? 'active' : '' ?>" id="navHover">
        
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsLibrary"
            aria-expanded="true" aria-controls="collapsLibrary">
            <i class="fas fa-solid fa-swatchbook"></i>
            <span>Library</span>
        </a>
        <div id="collapsLibrary" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'library_dashboard') ? 'active' : '' ?>" href="library_dashboard" <?= !in_array('Library Dashboard - library_dashboard', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Library Dashboard</a>
                <a class="collapse-item <?= ($current_url == 'borrow') ? 'active' : '' ?>" href="borrow" <?= !in_array('Borrow Book - borrow', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Borrow Book</a>
                <a class="collapse-item <?= ($current_url == 'return') ? 'active' : '' ?>" href="return" <?= !in_array('Return Book - return', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Return Book</a>
                
                <h6 class="collapse-header">Book Inventory</h6>
                <a class="collapse-item <?= ($current_url == 'book') ? 'active' : '' ?>" href="book" <?= !in_array('Book - book', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Book Info</a>
                <a class="collapse-item <?= ($current_url == 'category') ? 'active' : '' ?>" href="category" <?= !in_array('Category - category', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Book Category</a>

                <h6 class="collapse-header">Library POS</h6>
                <a class="collapse-item <?= ($current_url == 'lib_dashboard') ? 'active' : '' ?>" href="lib_dashboard" target="_blank" rel="noopener noreferrer" <?= !in_array('Library POS - lib_dashboard', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Library POS</a>
                <a class="collapse-item <?= ($current_url == 'lib_report_view') ? 'active' : '' ?>" href="lib_report_view" <?= !in_array('Library Sales Report - lib_report_view', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Library Sales Report</a>
                <a class="collapse-item <?= ($current_url == 'lib_catagory_items') ? 'active' : '' ?>" href="lib_catagory_items" <?= !in_array('Library POS Itmes - lib_catagory_items', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Library POS Itmes</a>
                
                
            </div>
        </div>
    </li>


<!--  ----------------------------------------- EDIT FILE SECTION --------------------------------------------------- -->

    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- edit  -->
    <li class="nav-item top_bottom_line <?= ($current_url == 'editAllocateProgram' || $current_url == 'editPayment') ? 'active' : '' ?>" id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThree"
            aria-expanded="true" aria-controls="collapseThree">
            <i class="fas fa-fw fa-edit"></i>
            <span>Edit</span>
        </a>
        <div id="collapseThree" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'editAllocateProgram') ? 'active' : '' ?>" href="editAllocateProgram" <?= !in_array('Edit Programme Allocation - editAllocateProgram', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Edit Programme Allocation</a>
                <a class="collapse-item <?= ($current_url == 'editPayment') ? 'active' : '' ?>" href="editPayment" <?= !in_array('Edit Payment - editPayment', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Edit Payment</a>

            </div>
        </div>
    </li>


<!--  ----------------------------------------- CANCELLATION FILE SECTION --------------------------------------------------- -->

    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- cancellation  -->
    <li class="nav-item top_bottom_line <?= ($current_url == 'PaymentCancellation' || $current_url == 'PenaltyPaymentCancellation' || $current_url == 'AdditionalPaymentCancellation') ? 'active' : '' ?> " id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseFive"
            aria-expanded="true" aria-controls="collapseFive">
            <i class="fas fa-fw fa-times"></i>
            <span>Cancellations</span>
        </a>
        <div id="collapseFive" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'PaymentCancellation' && in_array('payment cancellation - PaymentCancellation', $subListValues)) ? 'active' : '' ?>" href="PaymentCancellation" <?= !in_array('payment cancellation - PaymentCancellation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>payment cancellation </a>
                <a class="collapse-item <?= ($current_url == 'PenaltyPaymentCancellation' && in_array('penalty payment cancellation - PenaltyPaymentCancellation', $subListValues)) ? 'active' : '' ?>" href="PenaltyPaymentCancellation" <?= !in_array('penalty payment cancellation - PenaltyPaymentCancellation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>penalty payment cancellation </a>
                <a class="collapse-item <?= ($current_url == 'AdditionalPaymentCancellation' && in_array('Additional payment cancellation - AdditionalPaymentCancellation', $subListValues)) ? 'active' : '' ?>" href="AdditionalPaymentCancellation" <?= !in_array('Additional payment cancellation - AdditionalPaymentCancellation', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Additional payment cancellation </a>
            </div>
        </div>
    </li>
    
    
    <!-- -------------  BMS POS RELATED DATA-------------------------------------------------------------------------------------------------------  -->
    <!-- ---------------------------------------------------------------------------------------------------------------------  -->



    <li class="nav-item top_bottom_line <?= (in_array($current_url, ['pos_store', 'pos_dashboard', 'pos_products', 'pos_reports', 'pos_detailed_report', 'pos_detailed_sales'])) ? 'active' : '' ?>"
        id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBMSposData"
            aria-expanded="true" aria-controls="collapseBMSposData">
            <i class="fas fa-fw fa-cash-register"></i>
            <span>BMS POS</span>
        </a>
        <div id="collapseBMSposData" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">POS</h6>

                <a class="collapse-item <?= ($current_url == 'pos_store' && in_array('POS Store / Checkout - pos_store', $subListValues)) ? 'active' : '' ?>"
                    href="pos_store" <?= !in_array('POS Store / Checkout - pos_store', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-shopping-cart fa-fw mr-1"></i> Store / Checkout
                </a>


                <h6 class="collapse-header">POS ADMIN PANEL</h6>

                <a class="collapse-item <?= ($current_url == 'pos_dashboard' && in_array('POS Dashboard - pos_dashboard', $subListValues)) ? 'active' : '' ?>"
                    href="pos_dashboard" <?= !in_array('POS Dashboard - pos_dashboard', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-tachometer-alt fa-fw mr-1"></i> Dashboard
                </a>
                <a class="collapse-item <?= ($current_url == 'pos_products' && in_array('POS Manage Products - pos_products', $subListValues)) ? 'active' : '' ?>"
                    href="pos_products" <?= !in_array('POS Manage Products - pos_products', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-box fa-fw mr-1"></i> Manage Products
                </a>
                <a class="collapse-item <?= ($current_url == 'pos_reports' && in_array('POS Reports - pos_reports', $subListValues)) ? 'active' : '' ?>"
                    href="pos_reports" <?= !in_array('POS Reports - pos_reports', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-chart-bar fa-fw mr-1"></i> Reports
                </a>
                <a class="collapse-item <?= ($current_url == 'pos_detailed_report' && in_array('POS Detailed Reports - pos_detailed_report', $subListValues)) ? 'active' : '' ?>"
                    href="pos_detailed_report" <?= !in_array('POS Detailed Reports - pos_detailed_report', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-file-invoice fa-fw mr-1"></i> Detailed Reports
                </a>
                <a class="collapse-item <?= ($current_url == 'pos_detailed_sales' && in_array('POS Detailed Sales - pos_detailed_sales', $subListValues)) ? 'active' : '' ?>"
                    href="pos_detailed_sales" <?= !in_array('POS Detailed Sales - pos_detailed_sales', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>
                    <i class="fas fa-receipt fa-fw mr-1"></i> Detailed Sales
                </a>

            </div>
        </div>
    </li>



<!--  ----------------------------------------- REPORT FILE SECTION --------------------------------------------------- -->

    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- reports -->
    <li class="nav-item top_bottom_line <?= ($current_url == 'allStudentDetails'
                                            || $current_url == 'studentWiseDetails'
                                           
                                            || $current_url == 'aluminiReport') ? 'active' : '' ?>" id="navHover">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseSix"
            aria-expanded="true" aria-controls="collapseSix">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Reports</span>
        </a>
        <div id="collapseSix" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                
                <!-- --------------------------------------------------------  -->
                <a class="collapse-item <?= ($current_url == 'check_all_data_for') ? 'active' : '' ?>"
                    href="check_all_data_for" <?= !in_array('Payment Check Report - check_all_data_for', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Payment Check Report</a>
                <!-- --------------------------------------------------------  -->
                <a class="collapse-item <?= ($current_url == 'allStudentDetails') ? 'active' : '' ?>" href="allStudentDetails" <?= !in_array('All Student Details - allStudentDetails', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>All Student Details</a>
                <a class="collapse-item <?= ($current_url == 'studentWiseDetails') ? 'active' : '' ?>" href="studentWiseDetails" <?= !in_array('Student Wise Details - studentWiseDetails', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Wise Details</a>
                         

            </div>
        </div>
    </li>
    
    
<!--  ----------------------------------------- OPTION FILE SECTION --------------------------------------------------- -->

    <!-- ---------------------------------------------------------------------------------------------------------------------  -->
    <!-- options  -->
    <li class="top_bottom_line nav-item <?= ($current_url == 'addUser' || $current_url == 'program_to_user' || $current_url == 'studentCheckPayment' || $current_url == 'all_notifications' || $current_url == 'userPermission') ? 'active' : '' ?>" id="navHover" <?= ($userRole != 'super_admin') ? 'style="display: none;"' : '' ?>>
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOption"
            aria-expanded="true" aria-controls="collapseOption">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Options</span>
        </a>
        <div id="collapseOption" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Components</h6>
                <a class="collapse-item <?= ($current_url == 'addUser') ? 'active' : '' ?>" href="addUser" <?= !in_array('Add Users - addUser', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Add Users</a>
                <a class="collapse-item <?= ($current_url == 'userPermission') ? 'active' : '' ?>" href="userPermission" <?= !in_array('User Permission - userPermission', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>User Permission</a>
                <a class="collapse-item <?= ($current_url == 'all_notifications') ? 'active' : '' ?>" href="all_notifications" <?= !in_array('All Notifications - all_notifications', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>All Notifications</a>
                <a class="collapse-item <?= ($current_url == 'program_to_user' && in_array('UserProgram - program_to_user', $subListValues)) ? 'active' : '' ?>" href="./program_to_user" <?= !in_array('UserProgram - program_to_user', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>User Program Allocation</a>
             <!--<a class="collapse-item <?= ($current_url == 'studentCheckPayment' && in_array('Student Check Payments - studentCheckPayment', $subListValues)) ? 'active' : '' ?>" href="./studentCheckPayment" <?= !in_array('Student Check Payments - studentCheckPayment', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student check payment</a>-->
          
           <!--THILSHATH BRO ADDED THE ONE TO ONE SESSTION PART -->
           
             <a class="collapse-item <?= ($current_url == 'send_student_login') ? 'active' : '' ?>" href="send_student_login" <?= !in_array('Send Student Login - send_student_login', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Student Log</a>
             <a class="collapse-item <?= ($current_url == 'generate_student_logins') ? 'active' : '' ?>" href="generate_student_logins" <?= !in_array('Generate Student Logins - generate_student_logins', $subListValues) ? 'style="pointer-events: none; color: gray;"' : '' ?>>Loard Student Logins</a>
            </div>
        </div>
    </li>


    <!-- ---------------------------------------------------------------------------------------------------------------------  -->


</ul>
<!-- End of Sidebar -->