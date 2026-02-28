-- Migration: Schools profile fields, Timetable, Integrations
-- Run once to add address/phone/email to schools, timetable_entries, school_integrations

USE axis_sms;

-- Schools: add address, phone, email for school profile (skip if columns already exist)
ALTER TABLE schools ADD COLUMN address VARCHAR(255) DEFAULT NULL;
ALTER TABLE schools ADD COLUMN phone VARCHAR(50) DEFAULT NULL;
ALTER TABLE schools ADD COLUMN email VARCHAR(191) DEFAULT NULL;

-- Timetable: period definitions + entries per class
CREATE TABLE IF NOT EXISTS `timetable_periods` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `period_order` TINYINT UNSIGNED NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `label` VARCHAR(50) DEFAULT NULL COMMENT 'e.g. Period 1',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_school_period` (`school_id`, `period_order`),
  CONSTRAINT `fk_periods_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `timetable_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '1=Mon .. 5=Fri',
  `period_order` TINYINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_class_day_period` (`school_id`, `class_id`, `day_of_week`, `period_order`),
  CONSTRAINT `fk_entries_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entries_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entries_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_entries_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Integrations: per-school third-party config
CREATE TABLE IF NOT EXISTS `school_integrations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `provider` VARCHAR(50) NOT NULL COMMENT 'e.g. sms, payment, reporting',
  `name` VARCHAR(100) NOT NULL COMMENT 'Display name',
  `config_json` TEXT DEFAULT NULL COMMENT 'API keys, endpoints etc',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_school_provider_name` (`school_id`, `provider`, `name`),
  CONSTRAINT `fk_integrations_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
