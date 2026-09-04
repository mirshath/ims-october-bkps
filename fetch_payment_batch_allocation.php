<?php
include("database/connection.php");

// FIX: Explicit column list instead of pba.* to avoid column name case issues on hosting servers.
// Some hosting MySQL configs are case-sensitive with column names — this guarantees consistent keys.
$query = "
    SELECT 
        p.program_name,
        b.batch_name,
        pba.id,
        pba.programme_id,
        pba.batch_id,
        pba.course_fee_lkr,
        pba.uni_fee_gbp,
        pba.uni_fee_usd,
        pba.uni_fee_euro,
        pba.register_date,
        pba.installment_no,
        pba.registration_fee,
        pba.only_course_fee,
        pba.installment_interval
    FROM 
        payment_batch_allocation pba
    JOIN 
        program_table p ON pba.programme_id = p.program_code 
    JOIN 
        batch_table b ON pba.batch_id = b.id
";

$result = $conn->query($query);

if (!$result) {
    // Output error message if the query fails
    header('Content-Type: application/json');
    echo json_encode(['error' => $conn->error]);
    exit;
}

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // FIX: installment_interval is now explicitly selected — no runtime case workaround needed.
        // Ensure it defaults to null if somehow missing.
        if (!isset($row['installment_interval'])) {
            $row['installment_interval'] = null;
        }
        $data[] = $row;
    }
}

// Set the content type to application/json
header('Content-Type: application/json');
echo json_encode($data);
$conn->close();
?>