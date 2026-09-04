<?php
session_start();

// Set Sri Lanka timezone
date_default_timezone_set('Asia/Colombo');

if (!isset($conn)) {
    $paths = [
        __DIR__ . '/../database/connection.php',
        __DIR__ . '/database/connection.php',
        '../database/connection.php',
        'database/connection.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            include($path);
            break;
        }
    }
}

if (!$conn) {
    $_SESSION['error'] = "Database connection layer is unavailable.";
    header('location: ../return.php');
    exit();
}

if(isset($_POST['add'])){
    $student_input = trim($_POST['student']);
    $borrower_type = isset($_POST['borrower_type']) ? $_POST['borrower_type'] : 'student';
    $student_id = "";
    
    // BACKEND EXTRACTOR: Parse target identifying markers cleanly behind the scenes
    if($borrower_type == 'student') {
        if(strpos($student_input, ' | ') !== false) {
            $parts = explode(' | ', $student_input);
            $reg_id = $conn->real_escape_string(trim($parts[0]));
            $nic_id = $conn->real_escape_string(trim($parts[1]));
            
            $sql = "SELECT s.student_code FROM students s 
                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                    WHERE ap.student_registration_id = '$reg_id' AND s.nic = '$nic_id'";
            $query = $conn->query($sql);
        } else {
            $escaped = $conn->real_escape_string($student_input);
            $sql = "SELECT s.student_code FROM students s 
                    LEFT JOIN allocate_programme ap ON s.student_code = ap.student_code 
                    WHERE s.student_code = '$escaped' OR ap.student_registration_id = '$escaped' OR s.nic = '$escaped'";
            $query = $conn->query($sql);
        }
    } else {
        // Staff borrower
        if(strpos($student_input, ' | ') !== false) {
            $parts = explode(' | ', $student_input);
            $staff_name = $conn->real_escape_string(trim($parts[0]));
            $staff_nic = $conn->real_escape_string(trim($parts[1]));
            
            $sql = "SELECT id FROM admin WHERE full_name = '$staff_name' AND nic = '$staff_nic'";
            $query = $conn->query($sql);
        } else {
            $escaped = $conn->real_escape_string($student_input);
            $sql = "SELECT id FROM admin WHERE full_name LIKE '%$escaped%' OR nic LIKE '%$escaped%'";
            $query = $conn->query($sql);
        }
    }
    
    if(!$query || $query->num_rows < 1){
        $_SESSION['error'] = 'Borrower verification parameters mismatch. Verification failure.';
        header('location: ../return.php');
        exit();
    }
    
    $row = $query->fetch_assoc();
    if($borrower_type == 'student') {
        $student_id = $row['student_code'];
    } else {
        $student_id = $row['id'];
    }

    // Combine accession codes from both typed input inputs and checked table rows
    $raw_accession_array = isset($_POST['accession_number']) ? $_POST['accession_number'] : [];
    $processed_accessions = [];
    
    foreach($raw_accession_array as $code) {
        $clean = trim($code);
        if(!empty($clean)) {
            $processed_accessions[] = $conn->real_escape_string($clean);
        }
    }
    
    // Remove duplicates to prevent processing the same item twice
    $processed_accessions = array_unique($processed_accessions);
    $return_count = 0;

    if (empty($processed_accessions)) {
        $_SESSION['error'] = 'No valid book accession numbers specified for collection return transaction processing.';
        header('location: ../return.php');
        exit();
    }

    // Get current Sri Lanka time
    $current_time = date('H:i:s');
    $current_datetime = date('Y-m-d H:i:s');

    foreach($processed_accessions as $accession_number){
        $sql = "SELECT * FROM books WHERE accession_number = '$accession_number'";
        $b_query = $conn->query($sql);
        
        if($b_query && $b_query->num_rows > 0){
            $brow = $b_query->fetch_assoc();
            $bid = $brow['id'];

            // Confirm that the targeted student actually holds an open borrow entry for this book
            $sql = "SELECT * FROM borrow WHERE student_id = '$student_id' AND book_id = '$bid' AND borrower_type = '$borrower_type' AND status = 0";
            $bor_query = $conn->query($sql);

            if($bor_query && $bor_query->num_rows > 0){
                $borrow = $bor_query->fetch_assoc();
                $borrow_id = $borrow['id'];
                
                // Insert with date, time and borrower_type
                $sql = "INSERT INTO returns (student_id, book_id, date_return, return_time, borrower_type) 
                        VALUES ('$student_id', '$bid', '$current_datetime', '$current_time', '$borrower_type')";
                if($conn->query($sql)){
                    $return_count++;
                    
                    // Mark the book back to available status (status = 0)
                    $conn->query("UPDATE books SET status = 0 WHERE id = '$bid'");
                    // Mark borrow ledger transaction reference record to closed (status = 1)
                    $conn->query("UPDATE borrow SET status = 1 WHERE id = '$borrow_id'");
                }
            } else {
                if(!isset($_SESSION['error'])){ $_SESSION['error'] = array(); }
                if(is_string($_SESSION['error'])) { $_SESSION['error'] = [$_SESSION['error']]; }
                $_SESSION['error'][] = 'No active borrow transaction found for Accession Number: '.$accession_number;
            }
        } else {
            if(!isset($_SESSION['error'])){ $_SESSION['error'] = array(); }
            if(is_string($_SESSION['error'])) { $_SESSION['error'] = [$_SESSION['error']]; }
            $_SESSION['error'][] = 'Accession Number reference code ['.$accession_number.'] not found in records.';
        }
    }

    if($return_count > 0){
        $bookLabel = ($return_count == 1) ? 'Book' : 'Books';
        $_SESSION['success'] = $return_count.' '.$bookLabel.' successfully processed and returned to system inventory.';
    }
} else {
    $_SESSION['error'] = 'Invalid submission entry pathway encountered.';
}

header('location: ../return.php');
exit();
?>