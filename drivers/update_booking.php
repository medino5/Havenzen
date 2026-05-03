<?php
require_once 'auth.php';

$driver_user_id = intval($_SESSION['user_id'] ?? 0);
$vehicle_id = intval($driver_data['vehicle_id'] ?? 0);

// Get POST data
$booking_id = isset($_POST['booking_id']) ? intval(trim($_POST['booking_id'])) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// Validate inputs
if (!$booking_id || $booking_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID: ' . htmlspecialchars($_POST['booking_id'] ?? 'empty')]);
    exit();
}

if (!in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First, check if the booking exists and is pending
    $check_query = $conn->prepare("
        SELECT booking_id, driver_id, vehicle_id, status 
        FROM bookings 
        WHERE booking_id = ? AND status = 'pending'
        FOR UPDATE
    ");
    $check_query->bind_param("i", $booking_id);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Booking not found or already processed');
    }
    
    $booking = $check_result->fetch_assoc();
    
    if ($action === 'accept') {
        // Check if booking already has a driver
        if ($booking['driver_id'] && intval($booking['driver_id']) !== $driver_user_id) {
            throw new Exception('This booking has already been accepted by another driver');
        }
        
        // Only set vehicle_id if it's valid (> 0)
        if ($vehicle_id > 0) {
            // Accept the booking with vehicle_id
            $update_query = $conn->prepare("
                UPDATE bookings 
                SET driver_id = ?, vehicle_id = ?, status = 'confirmed', accepted_at = NOW(), rejected_at = NULL, updated_at = NOW() 
                WHERE booking_id = ? AND status = 'pending'
            ");
            $update_query->bind_param("iii", $driver_user_id, $vehicle_id, $booking_id);
        } else {
            // Accept the booking without setting vehicle_id (keep existing value or NULL)
            $update_query = $conn->prepare("
                UPDATE bookings 
                SET driver_id = ?, status = 'confirmed', accepted_at = NOW(), rejected_at = NULL, updated_at = NOW() 
                WHERE booking_id = ? AND status = 'pending'
            ");
            $update_query->bind_param("ii", $driver_user_id, $booking_id);
        }
        
        if (!$update_query->execute()) {
            throw new Exception('Failed to accept booking: ' . $update_query->error);
        }
        
        // Record earnings for this booking (if not already recorded)
        $fare_query = $conn->query("SELECT fare_estimate FROM bookings WHERE booking_id = $booking_id");
        $fare_row = $fare_query->fetch_assoc();
        $fare_amount = $fare_row['fare_estimate'] ?? 0;
        
        // Check if earnings already exist for this booking
        $earnings_check = $conn->query("SELECT COUNT(*) as cnt FROM driver_earnings WHERE booking_id = $booking_id");
        $earnings_check_row = $earnings_check->fetch_assoc();
        if (($earnings_check_row['cnt'] ?? 0) == 0 && $fare_amount > 0) {
            // Insert new earnings record
            $earnings_insert = $conn->prepare("
                INSERT INTO driver_earnings (driver_id, booking_id, amount, earning_date, status)
                VALUES (?, ?, ?, CURDATE(), 'pending')
            ");
            $earnings_insert->bind_param("iid", $driver_user_id, $booking_id, $fare_amount);
            if (!$earnings_insert->execute()) {
                throw new Exception('Failed to record earnings: ' . $earnings_insert->error);
            }
            $earnings_insert->close();
        }
        
        // Update driver availability
        $driver_update = $conn->prepare("
            UPDATE drivers 
            SET is_online = 0,  
                last_login = NOW()
            WHERE user_id = ?
        ");
        $driver_update->bind_param("i", $driver_user_id);
        $driver_update->execute();
        
    } elseif ($action === 'reject') {
        // Reject the booking
        $update_query = $conn->prepare("
            UPDATE bookings 
            SET status = 'denied', rejected_at = NOW(), updated_at = NOW() 
            WHERE booking_id = ? AND status = 'pending'
        ");
        $update_query->bind_param("i", $booking_id);
        
        if (!$update_query->execute()) {
            throw new Exception('Failed to reject booking');
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking ' . ($action === 'accept' ? 'accepted' : 'rejected') . ' successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
