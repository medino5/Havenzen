-- Migration: shrink users table to authentication-only (username, password, role)
-- Run this ONLY AFTER:
--   1) 003_create_role_profile_tables.sql has run successfully, AND
--   2) Your PHP code has been updated to read profile data from admins/customers/drivers.
-- IMPORTANT: Back up your database before running this migration.

ALTER TABLE users
  DROP COLUMN email,
  DROP COLUMN full_name,
  DROP COLUMN phone_number,
  DROP COLUMN profile_picture,
  DROP COLUMN created_at,
  DROP COLUMN last_login,
  DROP COLUMN license_number,
  DROP COLUMN vehicle_assigned,
  DROP COLUMN is_online,
  DROP COLUMN current_location,
  DROP COLUMN license_expiry,
  DROP COLUMN license_class,
  DROP COLUMN years_experience,
  DROP COLUMN emergency_contact,
  DROP COLUMN emergency_phone,
  DROP COLUMN address;
