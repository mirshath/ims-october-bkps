<?php
// lib_functions.php

if (!function_exists('getSummaryDisplay')) {
    function getSummaryDisplay($conn, $period = 'today', $type = 'revenue') {
        $date = date('Y-m-d');
        $where = '';
        if ($period === 'today') $where = "job_date = '$date'";
        elseif ($period === 'week') $where = "job_date BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND CURDATE()";
        elseif ($period === 'month') $where = "job_date BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND CURDATE()";
        elseif ($period === 'year') $where = "job_date BETWEEN DATE_FORMAT(CURDATE(), '%Y-01-01') AND CURDATE()";
        else return 0;

        if ($type === 'revenue') {
            $sql = "SELECT SUM(net_amount) as total FROM lib_printing_jobs WHERE $where AND is_free = 0";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } elseif ($type === 'jobs') {
            $sql = "SELECT COUNT(*) as total FROM lib_printing_jobs WHERE $where";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } elseif ($type === 'binding_revenue') {
            $sql = "SELECT SUM(net_amount) as total FROM lib_printing_jobs WHERE $where AND job_type = 'binding' AND is_free = 0";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        } elseif ($type === 'binding_jobs') {
            $sql = "SELECT COUNT(*) as total FROM lib_printing_jobs WHERE $where AND job_type = 'binding'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        }
        return 0;
    }
}

if (!function_exists('getPrintCounts')) {
    function getPrintCounts($conn, $date) {
    $singlePages = 0;
    $doublePages = 0;
    $errorPages = 0;
    
    // Get single pages (one_side)
    $sqlSingle = "SELECT 
                    SUM(one_side) as single_total, 
                    SUM(both_side) as double_total, 
                    SUM(error_count) as error_total 
                  FROM lib_printing_jobs 
                  WHERE job_date = ? AND job_type = 'printing'";
    $stmt = $conn->prepare($sqlSingle);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $singlePages = (int)($row['single_total'] ?? 0);
    $doublePages = (int)($row['double_total'] ?? 0);
    $errorPages = (int)($row['error_total'] ?? 0);
    $stmt->close();
    
    $totalPages = $singlePages + ($doublePages * 2) + $errorPages;
    
    return [
        'single_pages' => $singlePages,
        'double_pages' => $doublePages,
        'error_pages' => $errorPages,
        'total_pages' => $totalPages
    ];
}
}

if (!function_exists('getItemDetails')) {
    function getItemDetails($job) {
        if ($job['job_type'] === 'printing') {
            $details = "OS:{$job['one_side']} BS:{$job['both_side']} R1:{$job['rough_one']} R2:{$job['rough_two']} Err:{$job['error_count']}";
            $qty = $job['total_sheets'] ?? 0;
        } else {
            $details = "Binding: {$job['binding_name']} ({$job['binding_size']})";
            $qty = $job['quantity'] ?? 0;
        }
        return ['details' => $details, 'qty' => $qty];
    }
}

if (!function_exists('calculateTotals')) {
    function calculateTotals($jobs) {
        $summary = [
            'total_jobs' => count($jobs),
            'total_revenue' => 0,
            'print_revenue' => 0,
            'binding_revenue' => 0,
            'total_discount' => 0,
            'paid_jobs' => 0,
            'free_jobs' => 0
        ];
        foreach ($jobs as $job) {
            $summary['total_revenue'] += $job['net_amount'];
            $summary['total_discount'] += $job['discount_amount'] ?? 0;
            if ($job['job_type'] === 'printing') $summary['print_revenue'] += $job['net_amount'];
            else $summary['binding_revenue'] += $job['net_amount'];
            if ($job['is_free']) $summary['free_jobs']++;
            else $summary['paid_jobs']++;
        }
        return $summary;
    }
}

if (!function_exists('getErrorAndRoughCounts')) {
    function getErrorAndRoughCounts($conn, $date) {
        $errorCount = 0;
        $roughSheets = 0;
        
        $sql = "SELECT SUM(error_count) as total_error, SUM(rough_one + rough_two) as total_rough 
                FROM lib_printing_jobs 
                WHERE job_date = ? AND job_type = 'printing'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $errorCount = (int)($row['total_error'] ?? 0);
        $roughSheets = (int)($row['total_rough'] ?? 0);
        
        return [
            'error_count' => $errorCount,
            'rough_sheets' => $roughSheets
        ];
    }
}
if (!function_exists('getPrintJobCount')) {
    function getPrintJobCount($conn, $date) {
        // This function ONLY counts print jobs = one_side + both_side + error
        $sql = "SELECT 
                    SUM(one_side) as single_total, 
                    SUM(both_side) as double_total, 
                    SUM(error_count) as error_total 
                FROM lib_printing_jobs 
                WHERE job_date = ? AND job_type = 'printing'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $singlePages = (int)($row['single_total'] ?? 0);
        $doublePages = (int)($row['double_total'] ?? 0);
        $errorPages = (int)($row['error_total'] ?? 0);
        
        // Print Jobs = Single + Double + Error (NO ROUGH)
        return $singlePages + $doublePages + $errorPages;
    }
}
?>