<?php
// api/vehicle_history.php - Get vehicle location history
header('Content-Type: application/json');

// Fix the config path
$config_path = dirname(__DIR__) . '/config.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Config file not found']);
    exit;
}
require_once($config_path);

// ... rest of the code remains the same ...

$vehicle_id = $_GET['vehicle_id'] ?? '';
$hours = $_GET['hours'] ?? 24; // Default to last 24 hours
$limit = $_GET['limit'] ?? 100; // Default to 100 records

if (empty($vehicle_id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Vehicle ID required']);
    exit;
}

// Validate vehicle exists
$vehicle_check = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ?");
$vehicle_check->bind_param("i", $vehicle_id);
$vehicle_check->execute();
$vehicle_result = $vehicle_check->get_result();

if ($vehicle_result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid vehicle ID']);
    exit;
}

// Get location history
$sql = "SELECT 
            location_id,
            latitude,
            longitude,
            timestamp
        FROM locations 
        WHERE vehicle_id = ? 
        AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ORDER BY timestamp DESC
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $vehicle_id, $hours, $limit);
$stmt->execute();
$result = $stmt->get_result();

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = [
        'location_id' => $row['location_id'],
        'latitude' => (float)$row['latitude'],
        'longitude' => (float)$row['longitude'],
        'timestamp' => $row['timestamp']
    ];
}

// Get vehicle info
$vehicle_sql = "SELECT v.*, d.full_name as driver_name, d.phone_number as driver_phone
                FROM vehicles v 
                LEFT JOIN drivers d ON v.driver_id = d.driver_id 
                WHERE v.vehicle_id = ?";
$vehicle_stmt = $conn->prepare($vehicle_sql);
$vehicle_stmt->bind_param("i", $vehicle_id);
$vehicle_stmt->execute();
$vehicle_info = $vehicle_stmt->get_result()->fetch_assoc();

echo json_encode([
    'status' => 'success',
    'vehicle_info' => $vehicle_info,
    'locations' => $locations,
    'count' => count($locations),
    'time_range' => "$hours hours",
    'limit' => $limit
]);
?>