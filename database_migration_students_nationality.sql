-- Migration: Add nationality field to students table
-- Run this once against your Axis SMS database (after the base schema).

USE axis_sms;

ALTER TABLE students
    ADD COLUMN nationality VARCHAR(100) DEFAULT NULL AFTER address;

