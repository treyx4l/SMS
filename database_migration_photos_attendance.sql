-- Migration: Photo columns for staff, attendance table
USE axis_sms;

-- Teachers
ALTER TABLE teachers ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;

-- Parents
ALTER TABLE parents ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;

-- Bus drivers
ALTER TABLE bus_drivers ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;

-- Accountants
ALTER TABLE accountants ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;

-- Attendance: per student per date
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED DEFAULT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('present','late','absent') NOT NULL DEFAULT 'present',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `recorded_by` INT UNSIGNED DEFAULT NULL COMMENT 'teacher user id',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_attendance` (`school_id`, `student_id`, `date`),
  CONSTRAINT `fk_attendance_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
