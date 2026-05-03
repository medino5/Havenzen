-- Migration: add vehicle_type and vehicle_color to vehicles table
-- Run this in your database (phpMyAdmin or CLI) for the drivers UI to support type/color
ALTER TABLE vehicles
  ADD COLUMN vehicle_type VARCHAR(50) NULL AFTER license_plate,
  ADD COLUMN vehicle_color VARCHAR(50) NULL AFTER vehicle_type;

-- Optional: populate defaults for existing rows (uncomment if desired)
-- UPDATE vehicles SET vehicle_type = 'sedan' WHERE vehicle_type IS NULL;