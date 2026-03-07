USE `axis_sms`;

ALTER TABLE `school_messages` ADD COLUMN `is_read` TINYINT(1) NOT NULL DEFAULT 0 AFTER `body`;
ALTER TABLE `school_notifications` ADD COLUMN `link` VARCHAR(255) DEFAULT NULL AFTER `body`;
