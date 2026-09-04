<?php
session_start();
include("database/connection.php");

if (!isset($_SESSION['username']) || !isset($_GET['id'])) {
    die("Unauthorized access.");
}

$id = mysqli_real_escape_string($conn, $_GET['id']);
$query = "SELECT sd.*, s.first_name, s.last_name 
          FROM student_documents sd 
          LEFT JOIN students s ON sd.student_code = s.student_code 
          WHERE sd.id = '$id'";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {
    $student_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $row['first_name'] . '_' . $row['last_name']);
    $zip_name = "Documents_" . $row['student_code'] . "_" . $student_name . ".zip";

    $zip = new ZipArchive();
    $tmp_file = tempnam(sys_get_temp_dir(), 'zip');

    if ($zip->open($tmp_file, ZipArchive::CREATE) !== TRUE) {
        die("Could not open archive");
    }

    $fields = [
        'Registration_Receipt' => $row['registration_receipt'],
        'CV_Biodata' => $row['cv'],
        'NIC_Passport' => $row['nic_passport'],
        'Edu_Cert_1' => $row['education_qualification_1'],
        'Edu_Cert_2' => $row['education_qualification_2'],
        'Edu_Cert_3' => $row['education_qualification_3'],
        'Edu_Cert_4' => $row['education_qualification_4'],
        'Experience_1' => $row['experience_1'],
        'Experience_2' => $row['experience_2'],
        'Experience_3' => $row['experience_3'],
        'Experience_4' => $row['experience_4'],
        'Photograph' => $row['photo'],
        'Other_Docs' => $row['other']
    ];

    $file_count = 0;
    foreach ($fields as $name => $path) {
        if (!empty($path) && file_exists($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $zip->addFile($path, $name . '.' . $ext);
            $file_count++;
        }
    }

    $zip->close();

    if ($file_count > 0) {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $zip_name);
        header('Content-Length: ' . filesize($tmp_file));
        readfile($tmp_file);
        unlink($tmp_file);
        exit;
    } else {
        unlink($tmp_file);
        echo "<script>alert('No files found to download.'); window.history.back();</script>";
    }
} else {
    die("Record not found.");
}
?>