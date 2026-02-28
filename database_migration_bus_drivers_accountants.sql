-- Migration: Add bus_drivers and accountants tables for login-enabled roles

USE axis_sms;

-- Bus drivers
CREATE TABLE IF NOT EXISTS `bus_drivers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_drivers_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accountants
CREATE TABLE IF NOT EXISTS `accountants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(191) NOT NULL,
  `email` VARCHAR(191) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_accountants_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
