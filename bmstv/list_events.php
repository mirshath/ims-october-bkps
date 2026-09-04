<?php
/**
 * List Events from Folder - Simple version with absolute paths
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define the events folder path
$events_folder = 'images/events/';

$images = [];

// Check if folder exists
if (is_dir($events_folder)) {
    $files = scandir($events_folder);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        
        if (in_array($ext, $allowed) && is_file($events_folder . $file)) {
            // Use absolute path from web root
            $images[] = '/bmstv/' . $events_folder . $file;
        }
    }
}

echo json_encode([
    'success' => true,
    'count' => count($images),
    'images' => $images
], JSON_PRETTY_PRINT);
?>