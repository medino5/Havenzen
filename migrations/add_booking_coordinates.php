<?php
require_once __DIR__ . '/../config.php';

$sql = "
ALTER TABLE bookings
ADD COLUMN pickup_lat DECIMAL(10, 8) NULL AFTER pickup_location,
ADD COLUMN pickup_lng DECIMAL(11, 8) NULL AFTER pickup_lat,
ADD COLUMN dropoff_lat DECIMAL(10, 8) NULL AFTER dropoff_location,
ADD COLUMN dropoff_lng DECIMAL(11, 8) NULL AFTER dropoff_lat;
";

if ($conn->query($sql) === TRUE) {
    echo "Table bookings updated successfully";
} else {
    echo "Error updating table: " . $conn->error;
}

$conn->close();
?>
