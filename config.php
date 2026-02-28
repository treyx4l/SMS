<?php

require_once __DIR__ . '/config_env.php';

// Database configuration using env values with sensible defaults for XAMPP
$DB_HOST     = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT     = getenv('DB_PORT') ?: '3306';
$DB_DATABASE = getenv('DB_DATABASE') ?: 'axis_sms';
$DB_USERNAME = getenv('DB_USERNAME') ?: 'root';
$DB_PASSWORD = getenv('DB_PASSWORD') ?: '';

function get_db_connection(): mysqli
{
    global $DB_HOST, $DB_PORT, $DB_DATABASE, $DB_USERNAME, $DB_PASSWORD;

    $conn = new mysqli($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_DATABASE, (int) $DB_PORT);

    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

// Firebase config (used on PHP side if needed)
$FIREBASE_PROJECT_ID = getenv('FIREBASE_PROJECT_ID') ?: '';
$FIREBASE_API_KEY    = getenv('FIREBASE_API_KEY') ?: '';

// Basic multi-tenant helper: current school id from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_school_id(): ?int
{
    return isset($_SESSION['school_id']) ? (int) $_SESSION['school_id'] : null;
}

function require_school(): int
{
    $schoolId = current_school_id();
    if (!$schoolId) {
        die('No school selected. (Multi-tenant enforcement placeholder)');
    }
    return $schoolId;
}

// Per-school creation limits (staff, students, classes, subjects)
const SCHOOL_LIMIT_TEACHERS   = 50;
const SCHOOL_LIMIT_STUDENTS    = 999;
const SCHOOL_LIMIT_ACCOUNTANTS = 2;
const SCHOOL_LIMIT_BUS_DRIVERS = 5;
const SCHOOL_LIMIT_CLASSES     = 30;
const SCHOOL_LIMIT_SUBJECTS   = 30;

