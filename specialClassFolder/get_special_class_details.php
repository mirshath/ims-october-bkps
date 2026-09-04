<?php
session_start();
include("../database/connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Check which tables exist in the database
    $tablesExist = [
        'program_table' => false,
        'batch_table' => false,
        'module_table' => false
    ];

    $checkTablesQuery = "SHOW TABLES";
    $tablesResult = mysqli_query($conn, $checkTablesQuery);

    if ($tablesResult) {
        while ($table = mysqli_fetch_array($tablesResult)) {
            $tableName = $table[0];
            if ($tableName === 'program_table') $tablesExist['program_table'] = true;
            if ($tableName === 'batch_table') $tablesExist['batch_table'] = true;
            if ($tableName === 'module_table') $tablesExist['module_table'] = true;
        }
    }
    
    // Build the query based on which tables exist
    $query = "SELECT scm.* ";
    
    if ($tablesExist['program_table']) {
        $query .= ", p.program_name ";
    } else {
        $query .= ", scm.program_code as program_name ";
    }
    
    if ($tablesExist['batch_table']) {
        $query .= ", b.batch_name ";
    } else {
        $query .= ", CONCAT('Batch ID: ', scm.batch_id) as batch_name ";
    }
    
    if ($tablesExist['module_table']) {
        $query .= ", m.module_name ";
    } else {
        $query .= ", CONCAT('Module ID: ', scm.module_id) as module_name ";
    }
    
    $query .= " FROM special_class_messages scm ";
    
    if ($tablesExist['program_table']) {
        $query .= " LEFT JOIN program_table p ON scm.program_code = p.program_code ";
    }
    
    if ($tablesExist['batch_table']) {
        $query .= " LEFT JOIN batch_table b ON scm.batch_id = b.id ";
    }
    
    if ($tablesExist['module_table']) {
        $query .= " LEFT JOIN module_table m ON scm.module_id = m.id ";
    }
    
    $query .= " WHERE scm.id = $id";
    
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Format date and time for display
        $row['formatted_date'] = date('d-m-Y', strtotime($row['class_date']));
        $row['formatted_time'] = date('h:i A', strtotime($row['class_time']));
        $row['created_at'] = date('d-m-Y h:i A', strtotime($row['created_at']));
        
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Record not found'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}
?>
