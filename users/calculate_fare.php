<?php
require_once '../config.php';
header('Content-Type: application/json');

$pickupLat = isset($_POST['pickup_lat']) && $_POST['pickup_lat'] !== '' ? floatval($_POST['pickup_lat']) : null;
$pickupLng = isset($_POST['pickup_lng']) && $_POST['pickup_lng'] !== '' ? floatval($_POST['pickup_lng']) : null;
$dropoffLat = isset($_POST['dropoff_lat']) && $_POST['dropoff_lat'] !== '' ? floatval($_POST['dropoff_lat']) : null;
$dropoffLng = isset($_POST['dropoff_lng']) && $_POST['dropoff_lng'] !== '' ? floatval($_POST['dropoff_lng']) : null;
$pickupLocation = isset($_POST['pickup_location']) ? trim($_POST['pickup_location']) : '';
$dropoffLocation = isset($_POST['dropoff_location']) ? trim($_POST['dropoff_location']) : '';

if ($pickupLat === null || $pickupLng === null || $dropoffLat === null || $dropoffLng === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid coordinates'
    ]);
    exit;
}

function haversine_km($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    $a = sin($dLat / 2) * sin($dLat / 2) + cos($lat1) * cos($lat2) * sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$distanceKm = haversine_km($pickupLat, $pickupLng, $dropoffLat, $dropoffLng);

// First, check if pickup and dropoff match an existing route exactly
$matched_route = null;
$routes_res = $conn->query("SELECT route_id, route_name, fare, distance_km, stops FROM routes WHERE fare IS NOT NULL");
if ($routes_res) {
    while ($route = $routes_res->fetch_assoc()) {
        $stops = json_decode($route['stops'], true);
        if ($stops && count($stops) >= 2) {
            $firstStop = trim($stops[0]['name'] ?? '');
            $lastStop = trim($stops[count($stops) - 1]['name'] ?? '');
            
            // Allow partial matches so short admin names match full address strings
            $pickupMatch = ($firstStop !== '' && stripos($pickupLocation, $firstStop) !== false);
            $dropoffMatch = ($lastStop !== '' && stripos($dropoffLocation, $lastStop) !== false);
            
            if ($pickupMatch && $dropoffMatch) {
                $matched_route = $route;
                break;
            }
        }
    }
}

// If exact route match found, use that fare
if ($matched_route) {
    $fare = floatval($matched_route['fare']);
    $distanceKm = floatval($matched_route['distance_km']) ?: $distanceKm;
    
    echo json_encode([
        'success' => true,
        'distance_km' => round($distanceKm, 2),
        'fare' => round($fare, 2),
        'route_matched' => true,
        'route_name' => $matched_route['route_name']
    ]);
    exit;
}

// No exact match - calculate fare dynamically
$rate_sum = 0;
$rate_count = 0;
$routes_res = $conn->query("SELECT fare, distance_km FROM routes WHERE distance_km > 0");
if ($routes_res) {
    while ($r = $routes_res->fetch_assoc()) {
        $dist = floatval($r['distance_km']);
        $fare_val = floatval($r['fare']);
        if ($dist > 0) {
            $rate_sum += ($fare_val / $dist);
            $rate_count++;
        }
    }
}

$baseFare = 20;
$perKm = 2.29;

if ($rate_count > 0) {
    $perKm = $rate_sum / $rate_count;
    $fare = $distanceKm * $perKm;
} else {
    $fare = $baseFare + ($distanceKm * $perKm);
}

$fare = round($fare);

echo json_encode([
    'success' => true,
    'distance_km' => round($distanceKm, 2),
    'fare' => $fare,
    'route_matched' => false
]);
