<?php
require_once __DIR__ . '/../config.php';

echo "Checking vehicle plate uniqueness...\n";

$duplicateSql = "
    SELECT UPPER(license_plate) AS normalized_plate, COUNT(*) AS duplicate_count
    FROM vehicles
    GROUP BY UPPER(license_plate)
    HAVING COUNT(*) > 1
";

$duplicates = $conn->query($duplicateSql);
if ($duplicates && $duplicates->num_rows > 0) {
    echo "Cannot add unique index yet. Duplicate plate numbers exist:\n";
    while ($row = $duplicates->fetch_assoc()) {
        echo "- {$row['normalized_plate']} ({$row['duplicate_count']} records)\n";
    }
    $conn->close();
    exit(1);
}

$indexResult = $conn->query("SHOW INDEX FROM vehicles WHERE Key_name = 'license_plate'");
if ($indexResult && $indexResult->num_rows > 0) {
    echo "Unique index on vehicles.license_plate already exists.\n";
    $conn->close();
    exit(0);
}

$alterSql = "ALTER TABLE vehicles ADD UNIQUE KEY license_plate (license_plate)";
if ($conn->query($alterSql)) {
    echo "Successfully added unique index on vehicles.license_plate.\n";
} else {
    echo "Failed to add unique index: " . $conn->error . "\n";
    $conn->close();
    exit(1);
}

$conn->close();
