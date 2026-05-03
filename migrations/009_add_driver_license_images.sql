ALTER TABLE drivers
  ADD COLUMN IF NOT EXISTS license_front_image varchar(255) DEFAULT NULL AFTER license_class,
  ADD COLUMN IF NOT EXISTS license_back_image varchar(255) DEFAULT NULL AFTER license_front_image;
