<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid message ID";
    $_SESSION['message_type'] = "danger";
    header("Location: SpecialClassMessages.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch the special class message
$query = "SELECT * FROM special_class_messages WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Message not found";
    $_SESSION['message_type'] = "danger";
    header("Location: SpecialClassMessages.php");
    exit();
}

$message = mysqli_fetch_assoc($result);
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
            <div class="container-fluid p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">View Special Class Message</h4>
                    <a href="SpecialClassMessages.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left fa-sm"></i> Back to List
                    </a>
                </div>

                <div class="row mb-5">
                    <div class="col-md-8 offset-md-2">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex align-items-center">
                                <i class="fas fa-comment-alt me-2"></i>
                                <span>Special Class Details</span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Programme:</div>
                                    <div class="col-md-9"><?= htmlspecialchars($message['programme_name']) ?></div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Batch:</div>
                                    <div class="col-md-9"><?= htmlspecialchars($message['batch_name']) ?></div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Module:</div>
                                    <div class="col-md-9"><?= htmlspecialchars($message['module_name']) ?></div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Date & Time:</div>
                                    <div class="col-md-9">
                                        <?= date('d M Y', strtotime($message['class_date'])) ?> at 
                                        <?= date('h:i A', strtotime($message['class_time'])) ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($message['link'])): ?>
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Link:</div>
                                    <div class="col-md-9">
                                        <a href="<?= htmlspecialchars($message['link']) ?>" target="_blank">
                                            <?= htmlspecialchars($message['link']) ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Mail Subject:</div>
                                    <div class="col-md-9"><?= htmlspecialchars($message['mail_subject']) ?></div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-3 fw-bold">Description:</div>
                                    <div class="col-md-9">
                                        <div class="p-3 border rounded bg-light">
                                            <?= $message['description'] ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Created By:</div>
                                    <div class="col-md-9"><?= htmlspecialchars($message['created_by']) ?></div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-3 fw-bold">Created At:</div>
                                    <div class="col-md-9"><?= date('d M Y h:i A', strtotime($message['created_at'])) ?></div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <a href="edit_special_class.php?id=<?= $message['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="javascript:void(0)" onclick="confirmDelete(<?= $message['id'] ?>)" class="btn btn-danger ms-2">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Page Content -->
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Confirm delete function
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this special class message?')) {
        window.location.href = 'delete_special_class.php?id=' + id;
    }
}
</script>
</body>
</html>
