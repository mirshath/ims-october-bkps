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

if(isset($_POST['add'])){
    $accession_number = mysqli_real_escape_string($conn, $_POST['accession_number']);
    $calling_number = mysqli_real_escape_string($conn, $_POST['calling_number']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $publish_date = mysqli_real_escape_string($conn, $_POST['publish_date']);

    $sql = "INSERT INTO books (accession_number, calling_number, category_id, author, title, publish_date, is_inactive) 
            VALUES ('$accession_number', '$calling_number', '$category','$author', '$title', '$publish_date', 0)";
    if($conn->query($sql)){
        $_SESSION['success'] = 'Book added successfully';
    }
    else{
        $_SESSION['error'] = $conn->error;
    }
}   
else{
    $_SESSION['error'] = 'Fill up add form first';
}

header('location: ../book.php');
exit();