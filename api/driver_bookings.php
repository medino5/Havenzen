<?php
require_once dirname(__DIR__) . '/drivers/auth.php';
header('Content-Type: application/json');

try {
    // Get driver's vehicle status and latest vehicle location (if assigned)
    $vehicle_query = "
        SELECT v.status as vehicle_status, v.vehicle_id,
               (SELECT l.latitude FROM locations l WHERE l.vehicle_id = v.vehicle_id ORDER BY l.timestamp DESC LIMIT 1) as vehicle_lat,
               (SELECT l.longitude FROM locations l WHERE l.vehicle_id = v.vehicle_id ORDER BY l.timestamp DESC LIMIT 1) as vehicle_lng
        FROM drivers d 
        LEFT JOIN vehicles v ON v.driver_id = d.user_id 
        WHERE d.user_id = ?
    ";
    $stmt_vehicle = $conn->prepare($vehicle_query);
    $stmt_vehicle->bind_param("i", $driver_id);
    $stmt_vehicle->execute();
    $vehicle_result = $stmt_vehicle->get_result();
    $vehicle_data = $vehicle_result->fetch_assoc();
    $vehicle_status = $vehicle_data['vehicle_status'] ?? 'inactive';
    $vehicle_lat = isset($vehicle_data['vehicle_lat']) ? $vehicle_data['vehicle_lat'] : null;
    $vehicle_lng = isset($vehicle_data['vehicle_lng']) ? $vehicle_data['vehicle_lng'] : null;

    // Get active bookings for current driver
    $query = "
        SELECT 
            b.booking_id,
            b.pickup_location,
            b.dropoff_location,
            b.pickup_lat,
            b.pickup_lng,
            b.dropoff_lat,
            b.dropoff_lng,
            b.status,
            c.full_name as passenger_name,
            c.phone_number
        FROM bookings b
        JOIN customers c ON b.passenger_id = c.user_id
        WHERE b.driver_id = ?
        AND b.status IN ('confirmed', 'in_progress')
        ORDER BY b.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'booking_id' => $row['booking_id'],
            'passenger_name' => $row['passenger_name'],
            'phone_number' => $row['phone_number'],
            'pickup_location' => $row['pickup_location'],
            'dropoff_location' => $row['dropoff_location'],
            'pickup_lat' => $row['pickup_lat'],
            'pickup_lng' => $row['pickup_lng'],
            'dropoff_lat' => $row['dropoff_lat'],
            'dropoff_lng' => $row['dropoff_lng'],
            'status' => $row['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'status' => 'success',
        'count' => count($bookings),
        'bookings' => $bookings,
        'vehicle_status' => $vehicle_status,
        'vehicle_lat' => $vehicle_lat,
        'vehicle_lng' => $vehicle_lng
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'count' => 0,
        'bookings' => [],
        'vehicle_status' => 'inactive'
    ]);
}
?>
