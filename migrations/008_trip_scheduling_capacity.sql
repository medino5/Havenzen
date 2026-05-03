-- Migration: add scheduled trips, seat capacity, and trip operations
-- Run this after 007_expand_booking_statuses.sql

ALTER TABLE vehicles
    ADD COLUMN seat_capacity INT NOT NULL DEFAULT 0 AFTER vehicle_color;

ALTER TABLE routes
    ADD COLUMN travel_minutes INT NOT NULL DEFAULT 0 AFTER distance_km;

CREATE TABLE IF NOT EXISTS vehicle_schedules (
    schedule_id INT NOT NULL AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    route_id INT NOT NULL,
    return_route_id INT DEFAULT NULL,
    departure_time TIME NOT NULL,
    active_days LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(active_days)),
    layover_minutes INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (schedule_id),
    KEY idx_vehicle_schedules_vehicle_id (vehicle_id),
    KEY idx_vehicle_schedules_route_id (route_id),
    KEY idx_vehicle_schedules_active (is_active),
    CONSTRAINT fk_vehicle_schedules_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (vehicle_id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_schedules_route FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_schedules_return_route FOREIGN KEY (return_route_id) REFERENCES routes (route_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS vehicle_trips (
    trip_id INT NOT NULL AUTO_INCREMENT,
    schedule_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    route_id INT NOT NULL,
    direction ENUM('outbound','return') NOT NULL DEFAULT 'outbound',
    scheduled_departure_at DATETIME NOT NULL,
    actual_departure_at DATETIME DEFAULT NULL,
    arrival_reported_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    trip_status ENUM('scheduled','boarding','in_transit','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    seat_capacity_snapshot INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (trip_id),
    UNIQUE KEY uq_vehicle_trip_schedule_departure (schedule_id, direction, scheduled_departure_at),
    KEY idx_vehicle_trips_vehicle_date (vehicle_id, scheduled_departure_at),
    KEY idx_vehicle_trips_route_date (route_id, scheduled_departure_at),
    KEY idx_vehicle_trips_status (trip_status),
    CONSTRAINT fk_vehicle_trips_schedule FOREIGN KEY (schedule_id) REFERENCES vehicle_schedules (schedule_id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_trips_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles (vehicle_id) ON DELETE CASCADE,
    CONSTRAINT fk_vehicle_trips_route FOREIGN KEY (route_id) REFERENCES routes (route_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE bookings
    ADD COLUMN trip_id INT DEFAULT NULL AFTER route_id,
    ADD COLUMN scheduled_departure_at DATETIME DEFAULT NULL AFTER trip_id,
    ADD COLUMN passenger_count INT NOT NULL DEFAULT 1 AFTER scheduled_departure_at,
    ADD COLUMN baggage_count INT NOT NULL DEFAULT 0 AFTER passenger_count,
    ADD COLUMN boarding_status ENUM('scheduled','vehicle_arrived','boarded','no_show','dropped_off') NOT NULL DEFAULT 'scheduled' AFTER baggage_count,
    ADD COLUMN arrival_reported_at DATETIME DEFAULT NULL AFTER boarding_status,
    ADD COLUMN boarding_deadline_at DATETIME DEFAULT NULL AFTER arrival_reported_at,
    ADD COLUMN boarded_at DATETIME DEFAULT NULL AFTER boarding_deadline_at,
    ADD COLUMN dropped_off_at DATETIME DEFAULT NULL AFTER boarded_at,
    ADD COLUMN no_show_at DATETIME DEFAULT NULL AFTER dropped_off_at;

ALTER TABLE bookings
    ADD KEY idx_bookings_trip_id (trip_id),
    ADD KEY idx_bookings_scheduled_departure_at (scheduled_departure_at),
    ADD KEY idx_bookings_boarding_status (boarding_status);

ALTER TABLE bookings
    ADD CONSTRAINT fk_bookings_trip
        FOREIGN KEY (trip_id) REFERENCES vehicle_trips (trip_id) ON DELETE SET NULL;

UPDATE vehicles
SET seat_capacity = CASE
    WHEN LOWER(COALESCE(vehicle_type, '')) = 'bus' THEN 40
    WHEN LOWER(COALESCE(vehicle_type, '')) = 'van' THEN 14
    ELSE 12
END
WHERE seat_capacity = 0;

UPDATE routes
SET travel_minutes = CASE
    WHEN distance_km IS NULL OR distance_km <= 0 THEN 60
    ELSE GREATEST(15, ROUND(distance_km * 1.5))
END
WHERE travel_minutes = 0;

UPDATE bookings
SET passenger_count = 1
WHERE passenger_count IS NULL OR passenger_count <= 0;

UPDATE bookings
SET baggage_count = 0
WHERE baggage_count IS NULL OR baggage_count < 0;

UPDATE bookings
SET scheduled_departure_at = requested_time
WHERE scheduled_departure_at IS NULL
  AND requested_time IS NOT NULL;

UPDATE bookings
SET boarding_status = CASE
    WHEN status = 'completed' THEN 'dropped_off'
    WHEN status = 'cancelled' THEN 'no_show'
    WHEN status = 'in_progress' THEN 'boarded'
    ELSE 'scheduled'
END
WHERE boarding_status = 'scheduled';
