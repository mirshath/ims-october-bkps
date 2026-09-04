<?php
include("database/connection.php");
$cols = ["conditional_offer_letter", "conditional_offer_letter_text"];
foreach ($cols as $c) {
    $res = $conn->query("SHOW COLUMNS FROM students_temporary_registration LIKE '$c'");
    echo "$c: " . ($res->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
}
?>