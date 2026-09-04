<?php
session_start();
include 'database/connection.php';

// Get program filter from GET parameter
$program = isset($_GET['program']) ? trim($_GET['program']) : '';

// Get all counts from induction_students table
$stats = [];

if ($program !== '' && $program !== 'all') {
    // Filter by program using prepared statement
    // Total students in table
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM induction_students WHERE programme = ?");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_students'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Students attended
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM induction_students WHERE programme = ? AND attended = 'Yes'");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['attended'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Students issued pack (pack_collected = 1)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM induction_students WHERE programme = ? AND pack_collected = 1");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['pack_issued'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Students not issued pack (pack_collected = 0 or NULL)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM induction_students WHERE programme = ? AND (pack_collected = 0 OR pack_collected IS NULL)");
    $stmt->bind_param("s", $program);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['pack_not_issued'] = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    // All programs - no filter
    // Total students in table
    $result = $conn->query("SELECT COUNT(*) as total FROM induction_students");
    $stats['total_students'] = $result->fetch_assoc()['total'];

    // Students attended
    $result = $conn->query("SELECT COUNT(*) as total FROM induction_students WHERE attended = 'Yes'");
    $stats['attended'] = $result->fetch_assoc()['total'];

    // Students issued pack (pack_collected = 1)
    $result = $conn->query("SELECT COUNT(*) as total FROM induction_students WHERE pack_collected = 1");
    $stats['pack_issued'] = $result->fetch_assoc()['total'];

    // Students not issued pack (pack_collected = 0 or NULL)
    $result = $conn->query("SELECT COUNT(*) as total FROM induction_students WHERE pack_collected = 0 OR pack_collected IS NULL");
    $stats['pack_not_issued'] = $result->fetch_assoc()['total'];
}

echo json_encode($stats);
?>
