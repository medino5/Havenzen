<?php
session_start();
require_once '../config.php';

// Simple test to check if accept booking works
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in', 'session' => $_SESSION]);
    exit;
}

echo json_encode([
    'status' => 'ok',
    'user_id' => $_SESSION['user_id'],
    'role' => $_SESSION['role'],
    'post_data' => $_POST,
    'get_data' => $_GET
]);
?>
