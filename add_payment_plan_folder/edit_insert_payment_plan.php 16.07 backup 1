<!-- still not do this section  -->


<?php
include '../database/connection.php'; // Include DB connection

if (isset($_POST['saveButton'])) { // Check if the submit button is pressed
    // Get values from the form
    $student_code = $_POST['student_code']; // Ensure this matches the name attribute in the HTML
    $programme_batch = $_POST['programmeBatch'];
    $entered_by = $_POST['username'];

    // University Fee
    $university_fee_LKR = $_POST['uniFeeLKR'] ?? NULL;
    $university_fee_GBP = $_POST['uniFeeGBP'] ?? NULL;
    $university_fee_USD = $_POST['uniFeeUSD'] ?? NULL;

    // Course Fee
    $courseFeeLKR_total = $_POST['courseFeeInputLKR_initial_value'] ?? NULL;
    $courseFeeGBP_total = $_POST['courseFeeInputGBP_initial_value'] ?? NULL;
    $courseFeeUSD_total = $_POST['courseFeeInputUSD_initial_value'] ?? NULL;

    $course_fee_LKR = $_POST['courseFeeLKR'] ?? NULL;
    $course_fee_GBP = $_POST['courseFeeGBP'] ?? NULL;
    $course_fee_USD = $_POST['courseFeeUSD'] ?? NULL;

    // Registration Fee
    $registration_fee_LKR = $_POST['registrationFeeLKR'] ?? NULL;
    $registration_fee_GBP = $_POST['registrationFeeGBP'] ?? NULL;
    $registration_fee_USD = $_POST['registrationFeeUSD'] ?? NULL;

    // Payment Types
    $course_fee_type_LKR = $_POST['courseFeeLKR_type'] ?? NULL;
    $course_fee_type_GBP = $_POST['courseFeeGBP_type'] ?? NULL;
    $course_fee_type_USD = $_POST['courseFeeUSD_type'] ?? NULL;

    // Installments
    $installment_month_LKR = $_POST['installmentsLKR'] ?? NULL;
    $installment_month_GBP = $_POST['installmentsGBP'] ?? NULL;
    $installment_month_USD = $_POST['installmentsUSD'] ?? NULL;

    // Retrieve installment amounts and dates
    $installmentGBP_amounts = $_POST['installmentGBP_amount'] ?? [];
    $installmentGBP_dates = $_POST['installmentGBP_date'] ?? [];
    $installmentLKR_amounts = $_POST['installmentLKR_amount'] ?? [];
    $installmentLKR_dates = $_POST['installmentLKR_date'] ?? [];
    $installmentUSD_amounts = $_POST['installmentUSD_amount'] ?? [];
    $installmentUSD_dates = $_POST['installmentUSD_date'] ?? [];

    // Fetch hidden input values for installments
    $discount_types = $_POST['installmentLKR_discount_type'] ?? [];
    $discount_values = $_POST['installmentLKR_discount_value'] ?? [];
    $remarks = $_POST['installmentLKR_remark'] ?? [];
    // Start transaction to ensure both payment and installment insertions are successful
    mysqli_begin_transaction($conn);

    try {
        // Insert into add_payment_plan_table
        $sql = "INSERT INTO add_payment_plan_table 
            (student_id, programme_batch, 
            university_fee_LKR, courseFeeLKR_total, course_fee_LKR, course_fee_type_LKR, installment_month_LKR, registration_fee_LKR,
            university_fee_GBP, courseFeeGBP_total, course_fee_GBP, course_fee_type_GBP, installment_month_GBP, registration_fee_GBP,
            university_fee_USD, courseFeeUSD_total, course_fee_USD, course_fee_type_USD, installment_month_USD, registration_fee_USD,entered_by) 
            VALUES ('$student_code', '$programme_batch', 
            '$university_fee_LKR',  '$courseFeeLKR_total', '$course_fee_LKR', '$course_fee_type_LKR', '$installment_month_LKR', '$registration_fee_LKR',
            '$university_fee_GBP',  '$courseFeeGBP_total', '$course_fee_GBP', '$course_fee_type_GBP', '$installment_month_GBP', '$registration_fee_GBP',
            '$university_fee_USD',  '$courseFeeUSD_total', '$course_fee_USD', '$course_fee_type_USD', '$installment_month_USD',  '$registration_fee_USD',
            '$entered_by')";

        // Execute the query
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error inserting payment plan: " . mysqli_error($conn));
        }

        $last_id = mysqli_insert_id($conn); // Get the last inserted ID from add_payment_plan_table

        // Check if the payment plan already exists
        $check_sql = "SELECT COUNT(*) FROM installment_payment_table 
        WHERE student_id = '$student_code' 
        AND programme_batch = '$programme_batch' 
        AND fee_type = '$course_fee_type_LKR'";

        $result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_array($result);

        if ($row[0] > 0) {
            // If there's an existing record, show an error or skip the insertion
            echo "<script>alert('Duplicate entry: A payment plan for this student and programme batch already exists.');</script>";
            exit; // Stop further execution
        }

        // Insert payment plan into the installment payment table
        $installment_sql = "INSERT INTO installment_payment_table 
            (
            payment_plans_tb_id,
            student_id,
            programme_batch, 
            unifee_lkr_total,
            unifee_lkr,
            unifee_gbp_total,
            unifee_gbp,
            unifee_usd_total,
            unifee_usd, 
            fee_type,
            coursefee_total,
            coursefee,
            registrationfee) 
            VALUES (
            '$last_id', 
            '$student_code', 
            '$programme_batch',
            '$university_fee_LKR',
            '$university_fee_LKR',
            '$university_fee_GBP', 
            '$university_fee_GBP',
            '$university_fee_USD', 
            '$university_fee_USD', 
            '$course_fee_type_LKR', 
            '$courseFeeLKR_total',
            '$course_fee_LKR',
            '$registration_fee_LKR'
            )";

        // Execute the query
        if (!mysqli_query($conn, $installment_sql)) {
            echo "Error inserting into installment payment table: " . mysqli_error($conn);
            exit;
        }

        // Step 3: Get the last inserted ID from installment_payment_table
        $installment_payment_id = mysqli_insert_id($conn);

        // Step 4: Insert the installment details if installments are provided
        // Handle LKR installments
        if (isset($_POST['installmentLKR_amount']) && isset($_POST['installmentLKR_date'])) {
            $amounts = $_POST['installmentLKR_amount'];
            $dates = $_POST['installmentLKR_date'];
            $installmentNumbers = $_POST['installment_number'];

            // Create installment_details_table if not exists
            $create_table_sql = "CREATE TABLE IF NOT EXISTS installment_details_table (
                id INT AUTO_INCREMENT PRIMARY KEY,
                installment_payment_table_id INT,
                student_id VARCHAR(255),
                programme_batch VARCHAR(255),
                installment_numbers VARCHAR(255),
                installment_amount DECIMAL(10, 2),
                due_date DATE,
                discount_type VARCHAR(255),
                discount_value DECIMAL(10, 2),
                remark VARCHAR(255),
                entered_by VARCHAR(50),
                UNIQUE KEY unique_installment (installment_payment_table_id, student_id)
            )";

            if (!mysqli_query($conn, $create_table_sql)) {
                throw new Exception("Error creating installment details table: " . mysqli_error($conn));
            }

            // Insert installment details
            for ($i = 0; $i < count($amounts); $i++) {
                $installment_count = trim($installmentNumbers[$i]);
                $amount = str_replace(',', '', $amounts[$i]);
                $due_date = $dates[$i];

                // Assign values to variables
                $discount_type = $discount_types[$i] ?? NULL;
                $discount_value = $discount_values[$i] ?? NULL;
                $remark = $remarks[$i] ?? NULL;

                // Prepare the SQL statement
                $installment_details_sql = "INSERT INTO installment_details_table 
                    (installment_payment_table_id, student_id, programme_batch, installment_numbers, installment_amount, due_date, discount_type, discount_value, remark, entered_by)
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($conn, $installment_details_sql);
                mysqli_stmt_bind_param($stmt, "isssdsssss", $installment_payment_id, $student_code, $programme_batch, $installment_count, $amount, $due_date, $discount_type, $discount_value, $remark, $entered_by);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting installment details: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // if ($course_fee_type_LKR == 'full') {
        //     // Insert into installment_details_table for full payment
        //     $installment_details_sql = "INSERT INTO installment_details_table 
        //         (installment_payment_table_id, student_id, programme_batch, installment_numbers, installment_amount, due_date, discount_type, discount_value, remark)
        //         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        //     $stmt = mysqli_prepare($conn, $installment_details_sql);
        //     $installment_count = 'Full-payment'; // Assuming a single installment for full payment
        //     $amount = $course_fee_LKR; // Use course_fee_LKR instead of university_fee_LKR
        //     $due_date = date('Y-m-d'); // Assuming today's date as the due date

        //     mysqli_stmt_bind_param($stmt, "isssdssss", $installment_payment_id, $student_code, $programme_batch, $installment_count, $amount, $due_date, $discount_types[0] ?? NULL, $discount_values[0] ?? NULL, $remarks[0] ?? NULL);

        //     if (!mysqli_stmt_execute($stmt)) {
        //         throw new Exception("Error inserting installment details for full payment: " . mysqli_error($conn));
        //     }

        //     mysqli_stmt_close($stmt);
        // }

        // ---------------------------------------------------------------- 12.03.2025  when add these insert duplicate 

        // if ($course_fee_type_LKR == 'full') {
        //     // Insert into installment_details_table for full payment
        //     $installment_details_sql = "INSERT INTO installment_details_table 
        //         (installment_payment_table_id, student_id, programme_batch, installment_numbers, installment_amount, due_date)
        //         VALUES (?, ?, ?, ?, ?, ?)";

        //     $stmt = mysqli_prepare($conn, $installment_details_sql);
        //     $installment_count = 'Full-payment'; // Assuming a single installment for full payment
        //     $amount = $course_fee_LKR; // Use course_fee_LKR instead of university_fee_LKR
        //     $due_date = date('Y-m-d'); // Assuming today's date as the due date

        //     mysqli_stmt_bind_param($stmt, "isssds", $installment_payment_id, $student_code, $programme_batch, $installment_count, $amount, $due_date);

        //     if (!mysqli_stmt_execute($stmt)) {
        //         throw new Exception("Error inserting installment details for full payment: " . mysqli_error($conn));
        //     }

        //     mysqli_stmt_close($stmt);
        // }

        // ---------------------------------------------------------------- 

        // Successfully inserted
        echo "<script>alert('Installment payment plan has been successfully added!');</script>";
        echo '<script>window.location.href = "../add_payment_plan.php";</script>';

        // Commit the transaction if both insertions are successful
        mysqli_commit($conn);
        exit();
    } catch (Exception $e) {
        // Rollback transaction in case of an error
        mysqli_rollback($conn);
        echo "Error: " . $e->getMessage();
    }
}
