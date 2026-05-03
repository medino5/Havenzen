<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

// Include config
require_once '../config.php';

// Update admin last login timestamp
$admin_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE user_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->close();
?>