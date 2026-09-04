<?php
include '../database/connection.php'; // Include your DB connection file

if (isset($_POST['student_id']) && isset($_POST['prog_batch'])) {
    $student_id = $_POST['student_id'];
    $Prog_batch = $_POST['prog_batch'];


    $query = "
    SELECT 
        a.*, 
        i.*
    FROM 
        add_payment_plan_table a
    INNER JOIN 
        installment_payment_table i
    ON 
        a.id = i.payment_plans_tb_id
    WHERE 
        a.student_id = '$student_id' AND 
        a.programme_batch = '$Prog_batch'
";
    $result = mysqli_query($conn, $query);


    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        echo json_encode([
            'success' => true,
            // here wanna show the id also 
            'pay_id' => $row['id'],
            'student_id' => $row['student_id'],
            'programme_batch' => $row['programme_batch'],

            // 'registration_fee_LKR' => $row['registration_fee_LKR'],
            'registration_fee_LKR' => $row['registrationfee'],    // installment_payment_table getting values
            'Total_registration_fee_LKR' => $row['registration_fee_LKR'],    // installment_payment_table getting values
            
            'registration_fee_GBP' => $row['registration_fee_GBP'],
            'Total_registration_fee_GBP' => $row['registration_fee_GBP'],
            
            'registration_fee_USD' => $row['registration_fee_USD'],
            'Total_registration_fee_USD' => $row['registration_fee_USD'],

            // 'university_fee_GBP' => $row['university_fee_GBP'],  
            'university_fee_GBP' => $row['unifee_gbp'],                   // fetch from installment_payment_table
            'Total_university_fee_GBP' => $row['unifee_gbp_total'],                   // fetch from installment_payment_table
            // 'university_fee_USD' => $row['university_fee_USD'],
            'university_fee_USD' => $row['unifee_usd'],                   // fetch from installment_payment_table
            'Total_university_fee_USD' => $row['unifee_usd_total'],                   // fetch from installment_payment_table

            'university_fee_LKR' => $row['university_fee_LKR'],

            'courseFeeLKR_total' => $row['courseFeeLKR_total'],
            'courseFeeGBP_total' => $row['courseFeeGBP_total'],
            'courseFeeUSD_total' => $row['courseFeeUSD_total'],

            'installment_month_LKR' => $row['installment_month_LKR'],
            'installment_month_GBP' => $row['installment_month_GBP'],
            'installment_month_USD' => $row['installment_month_USD'],

            'course_fee_LKR' => $row['course_fee_LKR'],
            'course_fee_GBP' => $row['course_fee_GBP'],
            'course_fee_USD' => $row['course_fee_USD'],

            'course_fee_type_LKR' => $row['course_fee_type_LKR'],
            'course_fee_type_USD' => $row['course_fee_type_USD'],
            'course_fee_type_GBP' => $row['course_fee_type_GBP'],

            'entered_by' => $row['entered_by'],
            'created_at' => $row['created_at']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
