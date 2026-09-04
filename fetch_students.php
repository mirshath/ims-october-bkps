<?php
session_start();
include("database/connection.php");

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 3;
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_POST['search']) ? $_POST['search'] : '';

// Build search query
$searchQuery = "";
if ($search) {
    $searchQuery = "WHERE first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR nic LIKE '%$search%' OR certificate_name LIKE '%$search%' "; // Adjust fields as needed
}


// Count total records after applying search filter
$countQuery = "SELECT COUNT(*) AS total FROM students $searchQuery";
$result = $conn->query($countQuery);
$total = $result->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

// Fetch data based on search filter and pagination
$query = "SELECT * FROM students $searchQuery LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    'data' => $students,
    'totalPages' => $totalPages
]);

$conn->close();
