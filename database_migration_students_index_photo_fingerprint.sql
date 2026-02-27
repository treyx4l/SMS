-- Migration: Add index_no, photo_path, fingerprint_data (keep admission_no)
-- Run this once if your students table already exists. admission_no is kept for backward compatibility.

USE axis_sms;

-- Add new columns (skip if already exist)
ALTER TABLE students ADD COLUMN index_no VARCHAR(100) NULL AFTER last_name;
ALTER TABLE students ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL;
ALTER TABLE students ADD COLUMN fingerprint_data TEXT DEFAULT NULL;

-- Copy admission_no to index_no where empty
UPDATE students SET index_no = admission_no WHERE index_no IS NULL;

-- Make index_no NOT NULL
ALTER TABLE students MODIFY index_no VARCHAR(100) NOT NULL;

-- Add unique key on index_no (omit if already exists)
ALTER TABLE students ADD UNIQUE KEY uniq_student_index (school_id, index_no);
