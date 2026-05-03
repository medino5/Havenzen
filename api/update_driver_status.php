<?php
require_once dirname(__DIR__) . '/drivers/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$status = $_POST['status'] ?? '';

if (!in_array($status, ['online', 'offline'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$driver_user_id = intval($_SESSION['user_id'] ?? 0);
$vehicle_id = intval($driver_data['vehicle_id'] ?? 0);
$is_online = $status === 'online' ? 1 : 0;
$vehicle_status = $is_online ? 'active' : 'inactive';

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE drivers SET is_online = ?, last_login = NOW() WHERE user_id = ?");
    $stmt->bind_param('ii', $is_online, $driver_user_id);
    $stmt->execute();
    $stmt->close();

    if ($vehicle_id > 0) {
        $stmt2 = $conn->prepare("UPDATE vehicles SET status = ? WHERE vehicle_id = ?");
        $stmt2->bind_param('si', $vehicle_status, $vehicle_id);
        $stmt2->execute();
        $stmt2->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'vehicle_updated' => $vehicle_id > 0,
        'vehicle_status' => $vehicle_status
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()]);
}
?>
