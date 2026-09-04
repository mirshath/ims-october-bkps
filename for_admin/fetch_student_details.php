<?php
include("../database/connection.php");

if (!isset($_GET['student_code'])) {
    echo json_encode(["status" => false, "message" => "No student code"]);
    exit();
}

$student_code = $_GET['student_code'];

$sql = "
    SELECT 
        student_code,
        title,
        first_name,
        last_name,
        certificate_name,
        preferred_name,
        date_of_birth,
        nationality,
        permanent_address,
        current_address,
        mobile,
        telephone,
        emergency_contact_name,
        emergency_contact_number,
        english_ability,
        minimum_entry_qualification,
        nic,
        passport,
        personal_email,
        bms_email,
        occupation,
        organization,
        previous_organization,
        qualifications,
        active,
        student_status,
        transfer_status,
        remark,
        entered_by
    FROM students
    WHERE student_code = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(["status" => false, "message" => "No data found"]);
}
