-- Migration: Add graduation flags to students table
-- Run this once against your Axis SMS database.

USE axis_sms;

-- Older MySQL/MariaDB do not support "ADD COLUMN IF NOT EXISTS",
-- so we add the columns plainly. Only run this file once, or
-- ignore "Duplicate column name" errors if you re-run it.

ALTER TABLE students
    ADD COLUMN is_graduated TINYINT(1) NOT NULL DEFAULT 0 AFTER phone;

ALTER TABLE students
    ADD COLUMN graduated_at TIMESTAMP NULL DEFAULT NULL AFTER is_graduated;

