<?php
/**
 * Database Configuration
 * 
 * Environment variables:
 * - DB_HOST: Database hostname (default: localhost)
 * - DB_USER: Database username (default: root)
 * - DB_PASS: Database password (default: 1234)
 * - DB_NAME: Database name (default: schoolms)
 */

if (PHP_SAPI === 'cli') {
    ob_start();
}

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password_from_env = getenv('DB_PASS');
$password = ($password_from_env !== false) ? $password_from_env : '1234';
$dbname = getenv('DB_NAME') ?: 'schoolms';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log('SchoolMS DB connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Database connection failed.');
}

$conn->set_charset('utf8mb4');

// Define base URL if not already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: '/SchoolMS/');
}
