<?php
require_once dirname(__DIR__) . '/drivers/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$status = $_POST['status'] ?? '';
$driver_user_id = intval($_SESSION['user_id'] ?? 0);

if ($booking_id <= 0 || !in_array($status, ['in_progress', 'completed'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$check = $conn->prepare("SELECT status FROM bookings WHERE booking_id = ? AND driver_id = ? LIMIT 1");
if (!$check) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare booking lookup']);
    exit;
}

$check->bind_param('ii', $booking_id, $driver_user_id);
$check->execute();
$result = $check->get_result();
$booking = $result ? $result->fetch_assoc() : null;
$check->close();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found for this driver']);
    exit;
}

$current_status = $booking['status'];
$allowed = false;
if ($status === 'in_progress' && $current_status === 'confirmed') {
    $allowed = true;
}
if ($status === 'completed' && in_array($current_status, ['confirmed', 'in_progress'], true)) {
    $allowed = true;
}

if (!$allowed) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status transition from ' . $current_status . ' to ' . $status
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE booking_id = ? AND driver_id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to prepare booking update']);
    exit;
}

$stmt->bind_param('sii', $status, $booking_id, $driver_user_id);
$success = $stmt->execute();
$error = $stmt->error;
$stmt->close();

if ($success) {
    logCRUD(
        $conn,
        $driver_user_id,
        'UPDATE',
        'bookings',
        $booking_id,
        'Driver changed booking status to ' . ucwords(str_replace('_', ' ', $status))
    );

    echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
    exit;
}

http_response_code(500);
echo json_encode(['success' => false, 'message' => 'Failed to update booking: ' . $error]);
?>
