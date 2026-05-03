<?php
// api/gps_tracking.php - Minimal GPS data receiver
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log all incoming requests for debugging
error_log("GPS Request received - Method: " . $_SERVER['REQUEST_METHOD'] . ", Data: " . json_encode($_POST));

// Fix config path - try multiple approaches
$config_path = dirname(__DIR__) . '/config.php';
if (!file_exists($config_path)) {
    // Try absolute path
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/havenzen/config.php';
}

if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Config file not found']);
    exit;
}

require_once($config_path);

// Simple API key check
$api_key = $_POST['api_key'] ?? '';
$expected_api_key = defined('GPS_TRACKING_API_KEY') ? GPS_TRACKING_API_KEY : '';
if ($api_key !== $expected_api_key) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
    exit;
}

// Get data
$vehicle_id = $_POST['vehicle_id'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';

if (empty($vehicle_id) || empty($latitude) || empty($longitude)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

try {
    // Insert into database
    $sql = "INSERT INTO locations (vehicle_id, latitude, longitude, timestamp) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idd", $vehicle_id, $latitude, $longitude);
    
    if ($stmt->execute()) {
        $locationId = $stmt->insert_id;
        $stmt->close();

        $updateSql = "UPDATE drivers d
                      JOIN vehicles v ON v.driver_id = d.user_id
                      SET d.current_location = POINT(?, ?)
                      WHERE v.vehicle_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ddi", $latitude, $longitude, $vehicle_id);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Location saved',
            'location_id' => $locationId
        ]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception($error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
