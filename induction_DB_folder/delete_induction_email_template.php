<?php
session_start();
include '../database/connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit;
}

try {
    // First get the banner path to delete the file
    $getBanner = $conn->prepare("SELECT banner_image_path FROM induction_email_body_db_table WHERE id = ?");
    $getBanner->bind_param("i", $id);
    $getBanner->execute();
    $result = $getBanner->get_result()->fetch_assoc();
    $getBanner->close();
    
    if ($result && !empty($result['banner_image_path'])) {
        $filePath = '../' . $result['banner_image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Now delete from DB
    $deleteQuery = "DELETE FROM induction_email_body_db_table WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Template deleted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
$conn->close();
?>
