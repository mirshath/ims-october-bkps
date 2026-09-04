<?php
session_start();
ob_start();

// Database connection with path array
$paths = [
    __DIR__ . '/../database/connection.php',
    __DIR__ . '/database/connection.php',
    '../database/connection.php',
    'database/connection.php'
];

$conn = null;
foreach ($paths as $path) {
    if (file_exists($path)) {
        include($path);
        break;
    }
}

if (!isset($conn) || !$conn) {
    $_SESSION['error'] = 'Database connection failed';
    header('location: ../book.php');
    exit();
}

if(isset($_POST['inactivate'])){
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $reason = mysqli_real_escape_string($conn, $_POST['inactivation_reason']);
    $date = date('Y-m-d H:i:s');
    
    $sql = "UPDATE books SET 
            is_inactive = 1, 
            inactivation_reason = '$reason',
            inactivation_date = '$date'
            WHERE id = '$id'";
    
    if($conn->query($sql)){
        $_SESSION['success'] = 'Book inactivated successfully';
    } else {
        $_SESSION['error'] = 'Database error: ' . $conn->error;
    }
} else {
    $_SESSION['error'] = 'Invalid request';
}

header('location: ../book.php');
exit();