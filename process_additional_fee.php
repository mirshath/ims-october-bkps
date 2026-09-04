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
    $student_registration_id = mysqli_real_escape_string($conn, $_POST['student_registration_id']);
    $program_name = mysqli_real_escape_string($conn, $_POST['program_name']);
    $batch_name = mysqli_real_escape_string($conn, $_POST['batch_name']);
    $paid_date = mysqli_real_escape_string($conn, $_POST['paid_date']);
    $net_total = mysqli_real_escape_string($conn, $_POST['net_total']);
    $fee_items = json_decode($_POST['fee_items'], true);
    $entered_by = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Get student program and batch
    $query = "SELECT ap.programme_code, ap.batch_id 
              FROM allocate_programme ap 
              WHERE ap.student_code = '$student_id' AND ap.status = 'active'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $programme_code = $row['programme_code'];
        $batch_id = $row['batch_id'];
        
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert into additional_fee_payments table
            $insert_query = "INSERT INTO additional_fee_payments (
                                student_id, 
                                student_registration_id,
                                programme_code, 
                                batch_id, 
                                program_name,
                                batch_name,
                                total_amount, 
                                paid_date, 
                                entered_by, 
                                created_at
                            ) VALUES (
                                '$student_id',
                                '$student_registration_id',
                                '$programme_code',
                                '$batch_id',
                                '$program_name',
                                '$batch_name',
                                '$net_total',
                                '$paid_date',
                                '$entered_by',
                                NOW()
                            )";
            
            if (mysqli_query($conn, $insert_query)) {
                $payment_id = mysqli_insert_id($conn);
                
                // Insert fee items
                foreach ($fee_items as $item) {
                    $description = mysqli_real_escape_string($conn, $item['description']);
                    $amount = mysqli_real_escape_string($conn, $item['amount']);
                    $sequence = mysqli_real_escape_string($conn, $item['sequence']);
                    
                    $item_query = "INSERT INTO additional_fee_items (
                                    payment_id,
                                    sequence,
                                    description,
                                    amount,
                                    created_at
                                ) VALUES (
                                    '$payment_id',
                                    '$sequence',
                                    '$description',
                                    '$amount',
                                    NOW()
                                )";
                    
                    if (!mysqli_query($conn, $item_query)) {
                        throw new Exception("Error inserting fee item: " . mysqli_error($conn));
                    }
                }
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Success
                echo "<script>
                    alert('Additional fee payment recorded successfully!');
                    window.location.href = 'AdditionalFee.php';
                </script>";
            } else {
                throw new Exception("Error inserting payment: " . mysqli_error($conn));
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            
            // Error
            echo "<script>
                alert('Error: " . $e->getMessage() . "');
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
    header("Location: AdditionalFee.php");
    exit();
}
?>
