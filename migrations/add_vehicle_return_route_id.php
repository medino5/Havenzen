<?php
require_once __DIR__ . '/../config.php';

// Add return_route_id column to vehicles table
$sql = "ALTER TABLE vehicles ADD COLUMN return_route_id INT NULL DEFAULT NULL AFTER route_id";

if ($conn->query($sql)) {
    echo "Successfully added return_route_id column to vehicles table.\n";
    
    // Add foreign key constraint
    $fk_sql = "ALTER TABLE vehicles ADD CONSTRAINT fk_vehicle_return_route FOREIGN KEY (return_route_id) REFERENCES routes(route_id) ON DELETE SET NULL";
    if ($conn->query($fk_sql)) {
        echo "Successfully added foreign key constraint.\n";
    } else {
        echo "Error adding foreign key: " . $conn->error . "\n";
    }
} else {
    echo "Error adding column (might already exist): " . $conn->error . "\n";
}
?>
