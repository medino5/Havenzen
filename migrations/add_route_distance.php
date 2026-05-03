<?php
require_once __DIR__ . '/../config.php';

// Add distance_km column to routes table
$sql = "SHOW COLUMNS FROM routes LIKE 'distance_km'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alter_sql = "ALTER TABLE routes ADD COLUMN distance_km DECIMAL(10,2) DEFAULT NULL AFTER fare";
    if ($conn->query($alter_sql)) {
        echo "Successfully added distance_km column to routes table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column distance_km already exists.\n";
}
?>
