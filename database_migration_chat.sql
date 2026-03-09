-- ============================================================
-- Chat & Notifications Migration
-- Run once against your axis_sms database
-- ============================================================

USE `axis_sms`;

-- ── Messages table ──────────────────────────────────────────
-- Stores both individual DMs and group chat messages
CREATE TABLE IF NOT EXISTS `school_messages` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id`        INT UNSIGNED NOT NULL,
  `sender_id`        INT UNSIGNED NOT NULL,           -- users.id
  `sender_role`      VARCHAR(30)  NOT NULL,
  `sender_name`      VARCHAR(191) NOT NULL,
  -- recipient: either a specific user OR a named group
  `recipient_type`   ENUM('user','group') NOT NULL DEFAULT 'user',
  `recipient_user_id` INT UNSIGNED DEFAULT NULL,      -- set when recipient_type='user'
  `recipient_group`  ENUM('staff','parents_staff') DEFAULT NULL, -- set when recipient_type='group'
  `body`             TEXT NOT NULL,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_school_sender`   (`school_id`, `sender_id`),
  KEY `idx_school_recipient` (`school_id`, `recipient_user_id`),
  KEY `idx_school_group`    (`school_id`, `recipient_group`),
  CONSTRAINT `fk_sm_school`  FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sm_sender`  FOREIGN KEY (`sender_id`) REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Notifications table ─────────────────────────────────────
-- Real activity events (message received, student added, etc.)
CREATE TABLE IF NOT EXISTS `school_notifications` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `school_id`    INT UNSIGNED NOT NULL,
  -- which audience sees this notification
  `target_group` ENUM('staff','parents_staff','all') NOT NULL DEFAULT 'all',
  `type`         VARCHAR(50) NOT NULL DEFAULT 'info',
  -- 'message','student_added','student_updated','teacher_added',
  -- 'parent_added','driver_added','accountant_added','general'
  `title`        VARCHAR(255) NOT NULL,
  `body`         TEXT NOT NULL,
  `actor_name`   VARCHAR(191) DEFAULT NULL,  -- who triggered this event
  `color`        VARCHAR(20)  DEFAULT 'indigo',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sn_school_group` (`school_id`, `target_group`),
  CONSTRAINT `fk_sn_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
