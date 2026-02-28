-- Migration: Unique subject code per school
-- Run once. Ensure no duplicate codes exist before running.
USE axis_sms;

-- Add unique constraint on (school_id, code). Empty/NULL codes are allowed (multiple).
-- If you have duplicate codes, fix them first, then run this.
ALTER TABLE subjects ADD UNIQUE KEY `uniq_subject_code` (`school_id`, `code`);
