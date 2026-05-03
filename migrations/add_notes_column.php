<?php
require_once 'config.php';

// Add notes column to bookings table
$sql = "ALTER TABLE bookings ADD COLUMN notes TEXT NULL AFTER fare_estimate";

if ($conn->query($sql)) {
    echo "✓ Column 'notes' added to bookings table successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Verify the column was added
$result = $conn->query("DESCRIBE bookings");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

if (in_array('notes', $columns)) {
    echo "✓ Verification: 'notes' column exists in bookings table\n";
} else {
    echo "✗ Verification failed: 'notes' column not found\n";
}
?>
