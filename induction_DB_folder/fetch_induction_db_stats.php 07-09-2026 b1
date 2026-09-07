<?php
session_start();
include '../database/connection.php';

try {
    $programBatch = isset($_GET['program']) ? trim($_GET['program']) : 'all';
    $user_id = $_SESSION['user_id'] ?? 0;
    $role    = $_SESSION['role'] ?? '';
    $totalStudents = 0;
    $attendedStudents = 0;
    $packCollected = 0;
    
    if ($programBatch === 'all') {
        if ($role === 'super_admin') {
            // Get total students (all active - including those without iat records)
            $sqlTotal = "SELECT COUNT(*) AS total 
                        FROM induction_emails_sent ies
                        LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                        WHERE (iat.status = 'active' OR iat.status IS NULL)";
            $stmtTotal = $conn->prepare($sqlTotal);
            
            // Get attended and pack collected (active only)
            $sqlAttended = "SELECT 
                                COUNT(*) AS attended,
                                SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                            FROM induction_emails_sent ies
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND ies.attended = 'yes'";
            $stmtAttended = $conn->prepare($sqlAttended);
        } else {
            // Get total students (all active - including those without iat records AND program_allocation_user)
            $sqlTotal = "SELECT COUNT(*) AS total 
                        FROM induction_emails_sent ies
                        INNER JOIN program_table pt ON ies.program_id = pt.program_code
                        INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                        LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                        WHERE (iat.status = 'active' OR iat.status IS NULL) AND pau.user_id = ?";
            $stmtTotal = $conn->prepare($sqlTotal);
            $stmtTotal->bind_param("i", $user_id);
            
            // Get attended and pack collected (active only AND program_allocation_user)
            $sqlAttended = "SELECT 
                                COUNT(*) AS attended,
                                SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                            FROM induction_emails_sent ies
                            INNER JOIN program_table pt ON ies.program_id = pt.program_code
                            INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND ies.attended = 'yes' AND pau.user_id = ?";
            $stmtAttended = $conn->prepare($sqlAttended);
            $stmtAttended->bind_param("i", $user_id);
        }
        
        $stmtTotal->execute();
        $totalStudents = $stmtTotal->get_result()->fetch_assoc()['total'];
        $stmtTotal->close();
        
        $stmtAttended->execute();
        $statsAttended = $stmtAttended->get_result()->fetch_assoc();
        $attendedStudents = $statsAttended['attended'];
        $packCollected = $statsAttended['pack_collected'];
        $stmtAttended->close();
    } else {
        // Split "Programme - Batch" into separate parts
        $parts = explode(' - ', $programBatch, 2);
        if (count($parts) === 2) {
            $programName = $parts[0];
            $batchName = $parts[1];
            
            if ($role === 'super_admin') {
                // Get total students (filtered by programme-batch, active only)
                $sqlTotal = "SELECT COUNT(*) AS total 
                            FROM induction_emails_sent ies
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            INNER JOIN program_table pt ON ies.program_id = pt.program_code
                            INNER JOIN batch_table bt ON ies.batch_id = bt.id
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND bt.batch_name = ?";
                $stmtTotal = $conn->prepare($sqlTotal);
                $stmtTotal->bind_param("ss", $programName, $batchName);
                
                // Get attended and pack collected (filtered)
                $sqlAttended = "SELECT 
                                    COUNT(*) AS attended,
                                    SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                                FROM induction_emails_sent ies
                                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                                INNER JOIN batch_table bt ON ies.batch_id = bt.id
                                WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND bt.batch_name = ? AND ies.attended = 'yes'";
                $stmtAttended = $conn->prepare($sqlAttended);
                $stmtAttended->bind_param("ss", $programName, $batchName);
            } else {
                // Get total students (filtered by programme-batch, active only AND program_allocation_user)
                $sqlTotal = "SELECT COUNT(*) AS total 
                            FROM induction_emails_sent ies
                            INNER JOIN program_table pt ON ies.program_id = pt.program_code
                            INNER JOIN batch_table bt ON ies.batch_id = bt.id
                            INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND bt.batch_name = ? AND pau.user_id = ?";
                $stmtTotal = $conn->prepare($sqlTotal);
                $stmtTotal->bind_param("ssi", $programName, $batchName, $user_id);
                
                // Get attended and pack collected (filtered AND program_allocation_user)
                $sqlAttended = "SELECT 
                                    COUNT(*) AS attended,
                                    SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                                FROM induction_emails_sent ies
                                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                                INNER JOIN batch_table bt ON ies.batch_id = bt.id
                                INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                                WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND bt.batch_name = ? AND ies.attended = 'yes' AND pau.user_id = ?";
                $stmtAttended = $conn->prepare($sqlAttended);
                $stmtAttended->bind_param("ssi", $programName, $batchName, $user_id);
            }
            
            $stmtTotal->execute();
            $totalStudents = $stmtTotal->get_result()->fetch_assoc()['total'];
            $stmtTotal->close();
            
            $stmtAttended->execute();
            $statsAttended = $stmtAttended->get_result()->fetch_assoc();
            $attendedStudents = $statsAttended['attended'];
            $packCollected = $statsAttended['pack_collected'];
            $stmtAttended->close();
        } else {
            // Fallback: just match programme name
            if ($role === 'super_admin') {
                $sqlTotal = "SELECT COUNT(*) AS total 
                            FROM induction_emails_sent ies
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            INNER JOIN program_table pt ON ies.program_id = pt.program_code
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ?";
                $stmtTotal = $conn->prepare($sqlTotal);
                $stmtTotal->bind_param("s", $programBatch);
                
                $sqlAttended = "SELECT 
                                    COUNT(*) AS attended,
                                    SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                                FROM induction_emails_sent ies
                                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                                WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND ies.attended = 'yes'";
                $stmtAttended = $conn->prepare($sqlAttended);
                $stmtAttended->bind_param("s", $programBatch);
            } else {
                $sqlTotal = "SELECT COUNT(*) AS total 
                            FROM induction_emails_sent ies
                            INNER JOIN program_table pt ON ies.program_id = pt.program_code
                            INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                            LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                            WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND pau.user_id = ?";
                $stmtTotal = $conn->prepare($sqlTotal);
                $stmtTotal->bind_param("si", $programBatch, $user_id);
                
                $sqlAttended = "SELECT 
                                    COUNT(*) AS attended,
                                    SUM(CASE WHEN ies.pack_collected = 1 THEN 1 ELSE 0 END) AS pack_collected
                                FROM induction_emails_sent ies
                                INNER JOIN program_table pt ON ies.program_id = pt.program_code
                                INNER JOIN program_allocation_user pau ON pt.program_code = pau.program_code
                                LEFT JOIN induction_active_table iat ON ies.program_id = iat.program_id AND ies.batch_id = iat.batch_id
                                WHERE (iat.status = 'active' OR iat.status IS NULL) AND pt.program_name = ? AND ies.attended = 'yes' AND pau.user_id = ?";
                $stmtAttended = $conn->prepare($sqlAttended);
                $stmtAttended->bind_param("si", $programBatch, $user_id);
            }
            
            $stmtTotal->execute();
            $totalStudents = $stmtTotal->get_result()->fetch_assoc()['total'];
            $stmtTotal->close();
            
            $stmtAttended->execute();
            $statsAttended = $stmtAttended->get_result()->fetch_assoc();
            $attendedStudents = $statsAttended['attended'];
            $packCollected = $statsAttended['pack_collected'];
            $stmtAttended->close();
        }
    }
    
    echo json_encode([
        'totalStudents' => $totalStudents,
        'attendedStudents' => $attendedStudents,
        'packCollected' => $packCollected
    ]);
} catch (Exception $e) {
    echo json_encode([
        'totalStudents' => 0,
        'attendedStudents' => 0,
        'packCollected' => 0
    ]);
}
?>
