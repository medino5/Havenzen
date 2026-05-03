<?php
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json');

try {
    // Get active vehicles with their latest locations and assigned drivers
    $vehicle_id_filter = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : null;
    
    $query = "
        SELECT 
            v.vehicle_id,
            v.vehicle_name,
            v.license_plate,
            v.status,
            d.full_name as driver_name,
            r.route_name,
            l.latitude,
            l.longitude,
            l.timestamp,
            TIMESTAMPDIFF(SECOND, l.timestamp, NOW()) as last_update
        FROM vehicles v
        LEFT JOIN drivers d ON v.driver_id = d.user_id
        LEFT JOIN routes r ON v.route_id = r.route_id
        LEFT JOIN locations l ON v.vehicle_id = l.vehicle_id
        WHERE v.driver_id IS NOT NULL
    ";

    if ($vehicle_id_filter) {
        $query .= " AND v.vehicle_id = $vehicle_id_filter";
    }

    $query .= "
        AND l.timestamp = (
            SELECT MAX(timestamp) 
            FROM locations 
            WHERE vehicle_id = v.vehicle_id
        )
        ORDER BY v.vehicle_name
    ";
    
    $result = $conn->query($query);
    
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        // Only include vehicles with valid coordinates
        if (!empty($row['latitude']) && !empty($row['longitude'])) {
            $vehicles[] = [
                'vehicle_id' => $row['vehicle_id'],
                'vehicle_name' => $row['vehicle_name'],
                'license_plate' => $row['license_plate'],
                'driver_name' => $row['driver_name'],
                'route_name' => $row['route_name'],
                'latitude' => (float)$row['latitude'],
                'longitude' => (float)$row['longitude'],
                'status' => $row['status'],
                'last_update' => $row['last_update']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'count' => count($vehicles),
        'vehicles' => $vehicles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'count' => 0,
        'vehicles' => []
    ]);
}
?>
