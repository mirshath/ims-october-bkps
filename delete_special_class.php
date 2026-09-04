<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid message ID";
    $_SESSION['message_type'] = "danger";
    header("Location: SpecialClassMessages.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$username = mysqli_real_escape_string($conn, $_SESSION['username']);

// Update status to 'deleted' instead of deleting
$query = "UPDATE special_class_messages SET 
          status = 'deleted', 
          updated_by = '$username', 
          updated_at = NOW() 
          WHERE id = '$id'";

if (mysqli_query($conn, $query)) {
    $_SESSION['message'] = "Special class message deleted successfully";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Error deleting message: " . mysqli_error($conn);
    $_SESSION['message_type'] = "danger";
}

header("Location: SpecialClassMessages.php");
exit();
?>
