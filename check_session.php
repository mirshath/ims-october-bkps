<?php
session_start();

if (isset($_SESSION['username']) && $_SESSION['username'] == 'unknown_user') {
    echo json_encode(['logout' => true]);
} else {
    echo json_encode(['logout' => false]);
}
