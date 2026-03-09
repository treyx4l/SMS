CREATE TABLE IF NOT EXISTS `bus_routes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `school_id` INT UNSIGNED NOT NULL,
    `route_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure that id is strictly INT UNSIGNED in case the table was already created with INT
ALTER TABLE `bus_routes` MODIFY `id` INT UNSIGNED AUTO_INCREMENT;

-- Safely add route_id to drivers
SET @dbname = DATABASE();
SET @tablename = 'bus_drivers';
SET @columnname = 'route_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE bus_drivers ADD COLUMN route_id INT UNSIGNED NULL AFTER address"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND CONSTRAINT_NAME = 'fk_driver_route'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE bus_drivers ADD CONSTRAINT fk_driver_route FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE SET NULL"
));
PREPARE addFkIfNotExists FROM @preparedStatement2;
EXECUTE addFkIfNotExists;
DEALLOCATE PREPARE addFkIfNotExists;

-- Safely add route_id to students
SET @tablename = 'students';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 1",
  "ALTER TABLE students ADD COLUMN route_id INT UNSIGNED NULL AFTER address"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND CONSTRAINT_NAME = 'fk_student_route'
  ) > 0,
  "SELECT 1",
  "ALTER TABLE students ADD CONSTRAINT fk_student_route FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE SET NULL"
));
PREPARE addFkIfNotExists FROM @preparedStatement2;
EXECUTE addFkIfNotExists;
DEALLOCATE PREPARE addFkIfNotExists;
