<?php
session_start();

include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    // header("location: login.php");
    echo '<script>window.location.href = "login";</script>';
    // exit();
}

$userId = $_SESSION['user_id'];
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
<!-- dashboard card desighns  -->
<link rel="stylesheet" href="dashboards_style.css">

<!-- Page Wrapper -->
<div id="wrapper">

    <?php include("nav.php"); ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <div class="container p-3">

                    <!-- Bottom Section (Tiles) -->
                    <div class="row mt-4 g-3" id="card_enable" <?php echo !in_array('card_enable - index', $subListValues) ? 'style="pointer-events: none; color: gray;"' : ''; ?>>
                        <!-- Sales & Marketing -->
                        <div class="col-md-4">
                            <a href="allStudentDetails">
                                <div class="section-card card_bg_blue">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-users"></i>
                                    <!-- </div> -->
                                    <span class="card-title">All Student Details</span>
                                </div>
                            </a>
                        </div>

                        <!-- Programme Management (PM) -->
                        <div class="col-md-4">
                            <a href="studentWiseDetails">
                                <div class="section-card card_bg_green">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-user-graduate"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Student Wise Details Report</span>
                                </div>
                            </a>
                        </div>

                        <!-- Registrar -->
                        <div class="col-md-4">
                            <a href="leadsReport">
                                <div class="section-card card_bg_db">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-address-book"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Leads Report</span>
                                </div>
                            </a>
                        </div>

                        <!-- Accounts -->
                        <div class="col-md-4">
                            <a href="outstandingPayment">
                                <div class="section-card card_bg_orange">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Outstanding Payment Report</span>
                                </div>
                            </a>
                        </div>

                        <!-- Reports -->
                        <div class="col-md-4">
                            <a href="specialReasonReport">
                                <div class="section-card card_bg_blue">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-file-signature"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Special Reason Report</span>
                                </div>
                            </a>
                        </div>

                        <!-- User Credentials (Options) -->
                        <div class="col-md-4">
                            <a href="exam_report">
                                <div class="section-card card_bg_green">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-file-alt"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Exam Report</span>
                                </div>
                            </a>
                        </div>

                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="#">
                                <div class="section-card card_bg_db">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-book"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Assignment Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="">
                                <div class="section-card card_bg_orange">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-receipt"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Registration Payment Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="paymentReport">
                                <div class="section-card card_bg_blue">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-money-check-alt"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Payment Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="penaltyPaymentReport">
                                <div class="section-card card_bg_green">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Panelty Payment Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="additionalPaymentReport">
                                <div class="section-card card_bg_db">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-coins"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Additional Payment Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="">
                                <div class="section-card card_bg_orange">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-calendar-alt"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Time Table Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- Student -->
                        <div class="col-md-4">
                            <a href="">
                                <div class="section-card card_bg_blue">
                                    <!-- <div class="section-icon"> -->
                                    <i class="fas fa-user-friends"></i>
                                    <!-- </div> -->
                                    <span class="card-title">Alumni Report </span>
                                </div>
                            </a>
                        </div>
                        <!-- You can add more tiles here if needed -->
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


</div>
</body>

</html>
<?php $conn->close(); ?>