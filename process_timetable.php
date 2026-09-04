<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $programme_id = mysqli_real_escape_string($conn, $_POST['programme_id']);
    $module_id = mysqli_real_escape_string($conn, $_POST['module_id']);
    $lecturer_id = mysqli_real_escape_string($conn, $_POST['lecturer']);
    $day = mysqli_real_escape_string($conn, $_POST['day']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $comp1_deadline = mysqli_real_escape_string($conn, $_POST['comp1_deadline']);
    $comp2_deadline = mysqli_real_escape_string($conn, $_POST['comp2_deadline']);
    $created_by = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Handle multiple batch selection
    $batch_ids = isset($_POST['batch_id']) ? $_POST['batch_id'] : [];
    
    // Validate inputs
    if (empty($programme_id) || empty($module_id) || empty($lecturer_id) || empty($day) || 
        empty($start_time) || empty($end_time) || empty($start_date) || empty($end_date) || 
        empty($comp1_deadline) || empty($comp2_deadline) || empty($batch_ids)) {
        
        echo "<script>
            alert('All fields are required');
            window.history.back();
        </script>";
        exit();
    }
    
    // Validate time (end time should be after start time)
    if ($start_time >= $end_time) {
        echo "<script>
            alert('End time must be after start time');
            window.history.back();
        </script>";
        exit();
    }
    
    // Validate dates (end date should be after start date)
    if ($start_date > $end_date) {
        echo "<script>
            alert('End date must be after start date');
            window.history.back();
        </script>";
        exit();
    }
    
    // Get module name for reference
    $module_query = "SELECT module_name FROM modules WHERE id = '$module_id'";
    $module_result = mysqli_query($conn, $module_query);
    $module_row = mysqli_fetch_assoc($module_result);
    $module_name = $module_row['module_name'];
    
    // Get lecturer name for reference
    $lecturer_query = "SELECT CONCAT(title, ' ', lecturer_name) AS lecturer_name FROM lecturer_table WHERE id = '$lecturer_id'";
    $lecturer_result = mysqli_query($conn, $lecturer_query);
    $lecturer_row = mysqli_fetch_assoc($lecturer_result);
    $lecturer_name = $lecturer_row['lecturer_name'];
    
    // Get program name for reference
    $program_query = "SELECT program_name FROM program_table WHERE program_code = '$programme_id'";
    $program_result = mysqli_query($conn, $program_query);
    $program_row = mysqli_fetch_assoc($program_result);
    $program_name = $program_row['program_name'];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        $success = true;
        $error_message = "";
        
        // Insert timetable entry for each selected batch
        foreach ($batch_ids as $batch_id) {
            // Get batch name for reference
            $batch_query = "SELECT batch_name FROM batch_table WHERE id = '$batch_id'";
            $batch_result = mysqli_query($conn, $batch_query);
            $batch_row = mysqli_fetch_assoc($batch_result);
            $batch_name = $batch_row['batch_name'];
            
            // Check for existing timetable entries with the same batch, day, and overlapping time
            $overlap_query = "SELECT * FROM timetable 
                             WHERE batch_id = '$batch_id' 
                             AND day = '$day' 
                             AND ((start_time <= '$start_time' AND end_time > '$start_time') 
                                  OR (start_time < '$end_time' AND end_time >= '$end_time')
                                  OR (start_time >= '$start_time' AND end_time <= '$end_time'))
                             AND ((start_date <= '$start_date' AND end_date >= '$start_date')
                                  OR (start_date <= '$end_date' AND end_date >= '$end_date')
                                  OR (start_date >= '$start_date' AND end_date <= '$end_date'))";
            
            $overlap_result = mysqli_query($conn, $overlap_query);
            
            if (mysqli_num_rows($overlap_result) > 0) {
                $overlap_row = mysqli_fetch_assoc($overlap_result);
                $error_message .= "Time conflict for batch $batch_name on $day between $start_time and $end_time. ";
                $success = false;
                continue;
            }
            
            // Insert into timetable table
            $insert_query = "INSERT INTO timetable (
                                programme_id,
                                programme_name,
                                batch_id,
                                batch_name,
                                module_id,
                                module_name,
                                lecturer_id,
                                lecturer_name,
                                day,
                                start_time,
                                end_time,
                                start_date,
                                end_date,
                                comp1_deadline,
                                comp2_deadline,
                                created_by,
                                created_at,
                                status
                            ) VALUES (
                                '$programme_id',
                                '$program_name',
                                '$batch_id',
                                '$batch_name',
                                '$module_id',
                                '$module_name',
                                '$lecturer_id',
                                '$lecturer_name',
                                '$day',
                                '$start_time',
                                '$end_time',
                                '$start_date',
                                '$end_date',
                                '$comp1_deadline',
                                '$comp2_deadline',
                                '$created_by',
                                NOW(),
                                'active'
                            )";
            
            if (!mysqli_query($conn, $insert_query)) {
                $error_message .= "Error inserting timetable for batch $batch_name: " . mysqli_error($conn) . ". ";
                $success = false;
            }
        }
        
        if ($success) {
            // Commit transaction
            mysqli_commit($conn);
            echo "<script>
                alert('Timetable entries created successfully!');
                window.location.href = 'AddTimeTable.php';
            </script>";
        } else {
            // Rollback transaction
            mysqli_rollback($conn);
            echo "<script>
                alert('Error: $error_message');
                window.history.back();
            </script>";
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo "<script>
            alert('Error: " . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    // Not a POST request
    header("Location: AddTimeTable.php");
    exit();
}
?>
