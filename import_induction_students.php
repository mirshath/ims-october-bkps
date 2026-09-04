<?php
include 'database/connection.php';

if (!isset($_FILES['csv_file'])) {
    die("No file uploaded");
}

$file = fopen($_FILES['csv_file']['tmp_name'], "r");
$conn->begin_transaction();

try {
    $row = 0;

    while (($data = fgetcsv($file, 2000, ",")) !== false) {
        if ($row++ == 0) continue; // skip header
        if (empty($data[14])) continue; // skip if no email

        $dob = !empty($data[11]) ? date('Y-m-d', strtotime($data[11])) : null;

        $stmt = $conn->prepare("
            INSERT INTO induction_students (
                ref_no, programme, full_name, nic,
                contact_no, landline_no, fees,
                qualification, institute, gender,
                date_of_birth, country_of_residence, nationality,
                email, bms_email, address1, address2,
                address_country, postal_code, paid
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssssssssssssssssssss",
            $data[1],
            $data[2],
            $data[3],
            $data[4],
            $data[5],
            $data[6],
            $data[7],
            $data[8],
            $data[9],
            $data[10],
            $dob,
            $data[12],
            $data[13],
            $data[14],
            $data[15],
            $data[16],
            $data[17],
            $data[18],
            $data[19],
            $data[20]
        );

        $stmt->execute();
    }

    fclose($file);
    $conn->commit();
    header("Location: upload_induction_students.php");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo "Import failed: " . $e->getMessage();
}
