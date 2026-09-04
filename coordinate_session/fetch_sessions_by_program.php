<?php
include("../database/connection.php");

$program_id = intval($_POST['program_id']);
$data = [];

if($program_id){
    $res = $conn->query("SELECT session_id, session_name FROM tutor_session WHERE program_code=$program_id");
    while($r=$res->fetch_assoc()){
        $data[] = ['session_id'=>$r['session_id'], 'session_name'=>$r['session_name']];
    }
}

echo json_encode($data);
