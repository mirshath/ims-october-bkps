<?php
include("../database/connection.php");

$res = $conn->query("SELECT program_code, program_name FROM program_table ORDER BY program_name");

$html = '<option value="">-- Select Programme --</option>';
while ($r = $res->fetch_assoc()) {
    $html .= "<option value='{$r['program_code']}'>{$r['program_name']}</option>";
}
echo $html;
