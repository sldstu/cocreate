<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Application settings
define('APP_NAME', 'CoCreate');
define('APP_VERSION', '1.0.0');

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'cocreate_db_v2');
define('DB_USER', 'root');
define('DB_PASS', '');

// User roles
define('ROLE_ADMIN', 1);
define('ROLE_DEPARTMENT_LEAD', 2);
define('ROLE_OFFICER', 3);
define('ROLE_MEMBER', 4);

// File upload settings
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Email settings
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@example.com');
define('MAIL_PASSWORD', 'your-password');
define('MAIL_FROM_ADDRESS', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');
