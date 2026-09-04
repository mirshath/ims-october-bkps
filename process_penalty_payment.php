<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $penalty_type = mysqli_real_escape_string($conn, $_POST['penalty_type']);
    $penalty_amount = mysqli_real_escape_string($conn, $_POST['penalty_amount']);
    $discount_type = mysqli_real_escape_string($conn, $_POST['discount_type']);
    $discount_amount = mysqli_real_escape_string($conn, $_POST['discount_amount']);
    $final_amount = mysqli_real_escape_string($conn, $_POST['final_amount']);
    $paid_date = mysqli_real_escape_string($conn, $_POST['paid_date']);
    $entered_by = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Get student program and batch
    $query = "SELECT ap.programme_code, ap.batch_id, ap.student_registration_id 
              FROM allocate_programme ap 
              WHERE ap.student_code = '$student_id' AND ap.status = 'active'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $programme_code = $row['programme_code'];
        $batch_id = $row['batch_id'];
        $student_reg_id = $row['student_registration_id'];
        
        // Get program and batch names for record
        $program_query = "SELECT program_name FROM program_table WHERE program_code = '$programme_code'";
        $program_result = mysqli_query($conn, $program_query);
        $program_row = mysqli_fetch_assoc($program_result);
        $program_name = $program_row['program_name'];
        
        $batch_query = "SELECT batch_name FROM batch_table WHERE id = '$batch_id'";
        $batch_result = mysqli_query($conn, $batch_query);
        $batch_row = mysqli_fetch_assoc($batch_result);
        $batch_name = $batch_row['batch_name'];
        
        $program_batch = $program_name . ' - ' . $batch_name;
        
        // Insert into penalty_payments table
        // Note: You need to create this table in your database
        $insert_query = "INSERT INTO penalty_payments (
                            student_id, 
                            student_registration_id,
                            programme_code, 
                            batch_id, 
                            program_batch,
                            penalty_type, 
                            penalty_amount, 
                            discount_type, 
                            discount_amount, 
                            final_amount, 
                            paid_date, 
                            entered_by, 
                            created_at
                        ) VALUES (
                            '$student_id',
                            '$student_reg_id',
                            '$programme_code',
                            '$batch_id',
                            '$program_batch',
                            '$penalty_type',
                            '$penalty_amount',
                            '$discount_type',
                            '$discount_amount',
                            '$final_amount',
                            '$paid_date',
                            '$entered_by',
                            NOW()
                        )";
        
        if (mysqli_query($conn, $insert_query)) {
            // Success
            echo "<script>
                alert('Penalty payment recorded successfully!');
                window.location.href = 'penalty_pay.php';
            </script>";
        } else {
            // Error
            echo "<script>
                alert('Error: " . mysqli_error($conn) . "');
                window.history.back();
            </script>";
        }
    } else {
        // No active program found for student
        echo "<script>
            alert('No active program found for this student.');
            window.history.back();
        </script>";
    }
} else {
    // Not a POST request
    header("Location: penalty_pay.php");
    exit();
}
?>
