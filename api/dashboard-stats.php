<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/trip_helpers.php';

header('Content-Type: application/json');

try {
    hz_generate_trips_for_date($conn, date('Y-m-d'));
    hz_expire_overdue_no_shows($conn);

    $stats = [];

    $result = $conn->query("SELECT COUNT(*) AS count FROM vehicles WHERE status = 'active'");
    $stats['active_vehicles'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) AS count FROM drivers");
    $stats['total_drivers'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) AS count FROM users");
    $stats['total_users'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("
        SELECT COUNT(*) AS count
        FROM vehicle_trips
        WHERE DATE(scheduled_departure_at) = CURDATE()
    ");
    $stats['today_trips'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("
        SELECT COUNT(*) AS count
        FROM vehicle_trips
        WHERE DATE(scheduled_departure_at) = CURDATE()
          AND trip_status = 'completed'
    ");
    $stats['completed_today_trips'] = intval($result->fetch_assoc()['count'] ?? 0);

    $result = $conn->query("
        SELECT COALESCE(SUM(COALESCE(NULLIF(fare, 0), fare_estimate, 0)), 0) AS amount
        FROM bookings
        WHERE status = 'completed'
          AND DATE(COALESCE(dropped_off_at, updated_at, created_at)) = CURDATE()
    ");
    $stats['daily_income'] = number_format((float) ($result->fetch_assoc()['amount'] ?? 0), 2, '.', '');

    $result = $conn->query("
        SELECT COUNT(DISTINCT vehicle_id) AS active_gps_vehicles
        FROM locations
        WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stats['active_gps_vehicles'] = intval($result->fetch_assoc()['active_gps_vehicles'] ?? 0);

    $tripIds = $conn->query("
        SELECT trip_id
        FROM vehicle_trips
        WHERE DATE(scheduled_departure_at) = CURDATE()
    ");

    $totalSeats = 0;
    $reservedSeats = 0;
    $boardedSeats = 0;
    if ($tripIds) {
        while ($tripRow = $tripIds->fetch_assoc()) {
            $metrics = hz_get_trip_metrics($conn, intval($tripRow['trip_id']));
            $totalSeats += $metrics['capacity'];
            $reservedSeats += $metrics['reserved'];
            $boardedSeats += $metrics['boarded'];
        }
    }

    $stats['available_seats'] = max(0, $totalSeats - $reservedSeats);
    $stats['boarded_passengers'] = $boardedSeats;
    $stats['reserved_seats'] = $reservedSeats;
    $stats['total_seats'] = $totalSeats;

    echo json_encode([
        'status' => 'success',
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'stats' => [],
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}
?>
