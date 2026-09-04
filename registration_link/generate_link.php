<?php
header('Content-Type: application/json');
include("../database/connection.php"); // This sets $conn

// Check connection
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit();
}

// Check POST data
if (!isset($_POST['programme_id']) || !isset($_POST['batch_id'])) {
    echo json_encode(["status" => "error", "message" => "Missing inputs"]);
    exit();
}

$programme_id = $conn->real_escape_string($_POST['programme_id']);
$batch_id = $conn->real_escape_string($_POST['batch_id']);

// Generate secure token
$token = bin2hex(random_bytes(16)); // 32 chars

// Build link for your local testing
// $link = "http://localhost/ims/testing4/register.php?token=" . $token;
$link = "http://localhost/ims/registration_link/register.php?token=" . $token;


// Insert into DB
$sql = "INSERT INTO registration_links (programme_id, batch_id, token, generated_link)
        VALUES ('$programme_id', '$batch_id', '$token', '$link')";

if ($conn->query($sql)) {
    echo json_encode([
        "status" => "success",
        "link" => $link
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
}
