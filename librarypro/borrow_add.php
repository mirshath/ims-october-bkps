<?php
session_start();
ob_start();

// Set Sri Lanka timezone
date_default_timezone_set('Asia/Colombo');

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
        if (isset($conn) && $conn) {
            break;
        }
    }
}

if (!$conn) {
    $_SESSION['error'] = ['Database system link component connection dropped.'];
    header('location: ../borrow.php');
    exit();
}

if(isset($_POST['add'])){
    $student_input = trim($_POST['student']);
    $borrower_type = isset($_POST['borrower_type']) ? $_POST['borrower_type'] : 'student';
    
    // BACKEND EXTRACTION CORE: Deconstructs the pipeline string components if selected from dropdown list
    if($borrower_type == 'student') {
        if(strpos($student_input, ' | ') !== false) {
            $parts = explode(' | ', $student_input);
            $reg_id = $conn->real_escape_string(trim($parts[0]));
            $nic_id = $conn->real_escape_string(trim($parts[1]));
            
            $sql = "SELECT s.student_code FROM students s 
                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                    WHERE ap.student_registration_id = '$reg_id' AND s.nic = '$nic_id'";
        } else {
            // Fallback for manual user typing entries
            $escaped = $conn->real_escape_string($student_input);
            $sql = "SELECT s.student_code FROM students s 
                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                    WHERE s.student_code = '$escaped' 
                       OR ap.student_registration_id = '$escaped' 
                       OR s.nic = '$escaped'
                       OR CONCAT(s.first_name, ' ', s.last_name) LIKE '%$escaped%'";
        }
    } else {
        // Staff borrowing - NO ROLE FILTER
        if(strpos($student_input, ' | ') !== false) {
            $parts = explode(' | ', $student_input);
            $staff_name = $conn->real_escape_string(trim($parts[0]));
            $staff_nic = $conn->real_escape_string(trim($parts[1]));
            
            $sql = "SELECT id, nic FROM admin WHERE full_name = '$staff_name' AND nic = '$staff_nic'";
        } else {
            $escaped = $conn->real_escape_string($student_input);
            $sql = "SELECT id, nic FROM admin WHERE full_name LIKE '%$escaped%' OR nic LIKE '%$escaped%'";
        }
    }
               
    $query = $conn->query($sql);
    
    if(!$query || $query->num_rows < 1){
        if(!isset($_SESSION['error'])){ $_SESSION['error'] = array(); }
        $_SESSION['error'][] = "Borrower lookup validation failed. Match criteria not found.";
    }
    else{
        $row = $query->fetch_assoc();
        // Use the appropriate ID based on borrower type
        if($borrower_type == 'student') {
            $db_borrower_id = $row['student_code'];
        } else {
            $db_borrower_id = $row['id'];
        }

        $added = 0;
        // Get current Sri Lanka time
        $current_time = date('H:i:s');
        $current_datetime = date('Y-m-d H:i:s');
        
        foreach($_POST['accession_number'] as $accession_number){
            $accession_number = trim($accession_number);
            if(!empty($accession_number)){
                $accession_number = $conn->real_escape_string($accession_number);
                
                $sql = "SELECT * FROM books WHERE accession_number = '$accession_number' AND status != 1";
                $query_book = $conn->query($sql);
                
                if($query_book && $query_book->num_rows > 0){
                    $brow = $query_book->fetch_assoc();
                    $bid = $brow['id'];

                    // Store borrower type, date, and time separately with Sri Lanka time
                    $sql = "INSERT INTO borrow (student_id, book_id, date_borrow, borrow_time, status, borrower_type) 
                            VALUES ('$db_borrower_id', '$bid', '$current_datetime', '$current_time', 0, '$borrower_type')";
                    if($conn->query($sql)){
                        $added++;
                        $sql = "UPDATE books SET status = 1 WHERE id = '$bid'";
                        $conn->query($sql);
                    }
                    else{
                        if(!isset($_SESSION['error'])){ $_SESSION['error'] = array(); }
                        $_SESSION['error'][] = 'Database Processing Error: ' . $conn->error;
                    }
                }
                else{
                    if(!isset($_SESSION['error'])){ $_SESSION['error'] = array(); }
                    $_SESSION['error'][] = 'Book with accession code number ['.$accession_number.'] is either unavailable or checked out';
                }
            }
        }

        if($added > 0){
            $bookLabel = ($added == 1) ? 'Book' : 'Books';
            $borrowerLabel = ($borrower_type == 'student') ? 'Student' : 'Staff';
            $_SESSION['success'] = $added.' '.$bookLabel.' successfully borrowed by '.$borrowerLabel.'.';
        }
    }
}   
else{
    $_SESSION['error'] = ['Empty submission block initialization caught. Fill out all input metrics first.'];
}

header('location: ../borrow.php');
exit();
?>