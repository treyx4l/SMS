-- Migration: Add password_hash for local MySQL auth (teacher, parent, driver, accountant)
-- Admin continues to use Firebase. Other roles use email+password stored in MySQL.
-- Run once. Re-running may fail if columns already exist.
USE axis_sms;

ALTER TABLE teachers ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL;
ALTER TABLE parents ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL;
ALTER TABLE bus_drivers ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL;
ALTER TABLE accountants ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL;
