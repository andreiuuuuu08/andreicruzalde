<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'system');

// Error reporting (set to 0 in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Debug mode - set to true during development, false in production
define('DEBUG_MODE', true);

// Session configuration
session_start();

// Site configuration
define('SITE_URL', 'http://localhost/system');
define('SITE_NAME', 'Employee Performance Evaluation System');
?>
