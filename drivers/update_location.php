<?php
require_once 'auth.php';
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

if ($lat === null || $lng === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

try {
    $vehicle_id = intval($driver_data['vehicle_id'] ?? 0);

    $conn->begin_transaction();

    $sql = "UPDATE drivers SET current_location = POINT(?, ?) WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ddi', $lat, $lng, $driver_id);
    $stmt->execute();
    $stmt->close();

    if ($vehicle_id > 0) {
        $logStmt = $conn->prepare("
            INSERT INTO locations (vehicle_id, latitude, longitude, timestamp)
            VALUES (?, ?, ?, NOW())
        ");
        $logStmt->bind_param('idd', $vehicle_id, $lat, $lng);
        $logStmt->execute();
        $logStmt->close();
    }

    $conn->commit();

    // Log location update (optional - this updates frequently so may clutter logs)
    // Uncomment if needed: logSystemEvent($conn, $driver_id, 'LOCATION_UPDATE', "Driver updated location to ($lat, $lng)");

    echo json_encode(['success' => true, 'vehicle_updated' => $vehicle_id > 0]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
