<?php
/**
 * Application Configuration
 */

// Base URL
define('BASE_URL', getenv('BASE_URL') ?: '/SchoolMS/');

// Project root
define('PROJECT_ROOT', dirname(__DIR__));

// Path constants
define('CONFIG_PATH', PROJECT_ROOT . '/config');
define('SRC_PATH', PROJECT_ROOT . '/src');
define('INCLUDES_PATH', PROJECT_ROOT . '/includes');
define('UPLOADS_PATH', PROJECT_ROOT . '/uploads');
define('LOGS_PATH', PROJECT_ROOT . '/logs');
define('DATABASE_PATH', PROJECT_ROOT . '/database');
define('PUBLIC_PATH', PROJECT_ROOT . '/public');

// Session configuration
if (session_status() !== PHP_SESSION_ACTIVE) {
	ini_set('session.cookie_httponly', 1);
	ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
	ini_set('session.use_only_cookies', 1);
}

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Character encoding
ini_set('default_charset', 'utf-8');
