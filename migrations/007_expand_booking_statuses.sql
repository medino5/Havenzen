-- Migration: normalize booking statuses to the canonical set used by the PHP app
-- Run this in your database after backing it up.

ALTER TABLE bookings
  MODIFY status ENUM(
    'pending',
    'accepted',
    'confirmed',
    'in-progress',
    'in_progress',
    'in-transit',
    'completed',
    'denied',
    'cancelled'
  ) DEFAULT 'pending';

UPDATE bookings
SET status = 'confirmed'
WHERE status = 'accepted';

UPDATE bookings
SET status = 'in_progress'
WHERE status IN ('in-progress', 'in-transit');

ALTER TABLE bookings
  MODIFY status ENUM(
    'pending',
    'confirmed',
    'in_progress',
    'completed',
    'denied',
    'cancelled'
  ) DEFAULT 'pending';

UPDATE driver_earnings de
JOIN bookings b ON b.booking_id = de.booking_id
SET de.driver_id = b.driver_id
WHERE b.driver_id IS NOT NULL
  AND de.driver_id <> b.driver_id;
