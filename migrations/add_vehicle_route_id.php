<?php
require_once __DIR__ . '/../config.php';

// Add route_id column to vehicles table
$sql = "ALTER TABLE vehicles ADD COLUMN route_id INT NULL DEFAULT NULL AFTER driver_id";

if ($conn->query($sql) === TRUE) {
    echo "Column route_id added successfully to vehicles table.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

// Add foreign key constraint (optional but good practice)
// We'll check if it exists first or just try to add it. 
// For simplicity in this script, we'll just try to add it.
$sql_fk = "ALTER TABLE vehicles ADD CONSTRAINT fk_vehicle_route FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE SET NULL";

if ($conn->query($sql_fk) === TRUE) {
    echo "Foreign key constraint added successfully.\n";
} else {
    echo "Error adding foreign key: " . $conn->error . "\n";
}

$conn->close();
?>
