-- Migration: Add graduation flags to students table
-- Run this once against your Axis SMS database.

USE axis_sms;

-- Add columns only if they do not already exist
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS is_graduated TINYINT(1) NOT NULL DEFAULT 0 AFTER phone,
    ADD COLUMN IF NOT EXISTS graduated_at TIMESTAMP NULL DEFAULT NULL AFTER is_graduated;

