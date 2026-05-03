<?php
require_once dirname(__DIR__) . '/drivers/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$lat_raw = $_POST['lat'] ?? '';
$lng_raw = $_POST['lng'] ?? '';
$lat = $lat_raw === '' ? null : floatval($lat_raw);
$lng = $lng_raw === '' ? null : floatval($lng_raw);
$driver_user_id = intval($_SESSION['user_id'] ?? 0);
$vehicle_id = intval($driver_data['vehicle_id'] ?? 0);

if ($lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("UPDATE drivers SET current_location = POINT(?, ?) WHERE user_id = ?");
    $stmt->bind_param('ddi', $lat, $lng, $driver_user_id);
    $stmt->execute();
    $stmt->close();

    if ($vehicle_id > 0) {
        $stmt2 = $conn->prepare("
            INSERT INTO locations (vehicle_id, latitude, longitude, timestamp)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt2->bind_param('idd', $vehicle_id, $lat, $lng);
        $stmt2->execute();
        $stmt2->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Location updated',
        'vehicle_updated' => $vehicle_id > 0
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update location: ' . $e->getMessage()]);
}
?>
