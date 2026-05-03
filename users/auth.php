<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header("Location: ../login/login.php");
    exit();
}

// Update last login
require_once '../config.php';
$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("UPDATE customers SET last_login = NOW() WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Get user data joined with passenger profile
$stmt = $conn->prepare("SELECT 
    u.user_id,
    u.username,
    u.role,
    c.full_name,
    c.email,
    c.phone_number,
    c.profile_picture,
    c.created_at,
    c.last_login
FROM users u
JOIN customers c ON c.user_id = u.user_id
WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
?>