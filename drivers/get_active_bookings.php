<?php
require_once 'auth.php';
header('Content-Type: application/json');

try {
    $bookings = [];

    $result = $conn->query("SELECT b.*, c.full_name, c.phone_number
                             FROM bookings b
                             JOIN customers c ON b.passenger_id = c.user_id
                             WHERE b.driver_id = $driver_id
                               AND b.status IN ('confirmed', 'in_progress')
                             ORDER BY b.created_at DESC");

    while ($row = $result->fetch_assoc()) {
        $bookings[] = [
            'booking_id' => (int)$row['booking_id'],
            'pickup_location' => $row['pickup_location'],
            'dropoff_location' => $row['dropoff_location'],
            'pickup_lat' => isset($row['pickup_lat']) ? (float)$row['pickup_lat'] : null,
            'pickup_lng' => isset($row['pickup_lng']) ? (float)$row['pickup_lng'] : null,
            'dropoff_lat' => isset($row['dropoff_lat']) ? (float)$row['dropoff_lat'] : null,
            'dropoff_lng' => isset($row['dropoff_lng']) ? (float)$row['dropoff_lng'] : null,
            'status' => $row['status'],
            'fare_estimate' => isset($row['fare_estimate']) ? (float)$row['fare_estimate'] : null,
            'full_name' => $row['full_name'],
            'phone_number' => $row['phone_number'],
            'requested_time' => $row['requested_time'],
        ];
    }

    echo json_encode([
        'status' => 'success',
        'bookings' => $bookings,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
