-- Migration: add rating column to bookings table
-- Run this in your database (phpMyAdmin or CLI) to allow storing driver ratings per booking
ALTER TABLE bookings
  ADD COLUMN rating TINYINT(2) NULL AFTER fare_estimate;

-- Optional: enforce rating range (1-5) depending on MySQL version; uncomment if supported
-- ALTER TABLE bookings ADD CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5);

-- Optional: set default ratings for historical completed bookings (uncomment to set)
-- UPDATE bookings SET rating = NULL WHERE rating IS NULL;
