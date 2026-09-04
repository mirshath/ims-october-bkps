
<?php

$config = parse_ini_file(__DIR__ . "/../config.ini");

$conn = new mysqli(
    $config['DB_HOST'],
    $config['DB_USER'],
    $config['DB_PASS'],
    $config['DB_NAME']
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure UTF8
$conn->set_charset("utf8");


// mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// $mysqli = new mysqli("mail.hazz.lk", "hazz_bms", "Campus@2026", "hazz_bms");

// if ($mysqli->connect_errno) {
//     echo "Failed to connect: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
// } else {
//     echo "Connected to remote DB!";
// }



?>