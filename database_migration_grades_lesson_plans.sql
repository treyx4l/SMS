-- Migration: Grades and Lesson Plans tables
USE axis_sms;

-- Exam types (e.g. Mid-term, Final)
CREATE TABLE IF NOT EXISTS `exam_types` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `weight` DECIMAL(5,2) DEFAULT 100 COMMENT 'Percentage weight for GPA',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_exam_types_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grades: student, subject, class, exam_type, score
CREATE TABLE IF NOT EXISTS `grades` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `exam_type_id` INT UNSIGNED NOT NULL,
  `score` DECIMAL(6,2) NOT NULL,
  `max_score` DECIMAL(6,2) DEFAULT 100,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_grade` (`school_id`, `student_id`, `subject_id`, `exam_type_id`),
  CONSTRAINT `fk_grades_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_exam_type` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lesson plans: teacher, class, subject, week, content
CREATE TABLE IF NOT EXISTS `lesson_plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT UNSIGNED NOT NULL,
  `teacher_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `week_start` DATE NOT NULL COMMENT 'Monday of the week',
  `topic` VARCHAR(255) NOT NULL,
  `objectives` TEXT DEFAULT NULL,
  `content` TEXT DEFAULT NULL,
  `resources` TEXT DEFAULT NULL,
  `status` ENUM('draft','submitted','approved') DEFAULT 'draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_plan` (`school_id`, `teacher_id`, `class_id`, `subject_id`, `week_start`),
  CONSTRAINT `fk_plans_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plans_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plans_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plans_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
