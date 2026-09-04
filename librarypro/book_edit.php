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

if(isset($_POST['edit'])){
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $accession_number = mysqli_real_escape_string($conn, $_POST['accession_number']);
    $calling_number = mysqli_real_escape_string($conn, $_POST['calling_number']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date']);

    $sql = "UPDATE books SET 
            accession_number = '$accession_number', 
            calling_number = '$calling_number', 
            category_id = '$category', 
            author = '$author', 
            title = '$title', 
            publish_date = '$publish_date' 
            WHERE id = '$id'";
    
    if($conn->query($sql)){
        $_SESSION['success'] = 'Book updated successfully';
    } else {
        $_SESSION['error'] = 'Database error: ' . $conn->error;
    }
} else {
    $_SESSION['error'] = 'Fill up edit form first';
}

header('location: ../book.php');
exit();