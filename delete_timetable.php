<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $username = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Update status to 'cancelled' instead of deleting
    $query = "UPDATE timetable SET 
              status = 'cancelled', 
              updated_by = '$username', 
              updated_at = NOW() 
              WHERE id = '$id'";
    
    if (mysqli_query($conn, $query)) {
        echo "<script>
            alert('Timetable entry deleted successfully');
            window.location.href = 'view_timetable.php';
        </script>";
    } else {
        echo "<script>
            alert('Error deleting timetable entry: " . mysqli_error($conn) . "');
            window.location.href = 'view_timetable.php';
        </script>";
    }
} else {
    echo "<script>
        alert('Invalid timetable ID');
        window.location.href = 'view_timetable.php';
    </script>";
}
?>
