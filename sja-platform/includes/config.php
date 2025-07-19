<?php
/**
 * SJA Foundation Investment Management Platform
 * Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sja_platform');
define('DB_USER', 'sja_user');
define('DB_PASS', 'secure_password');

// Site Configuration
define('SITE_URL', 'http://localhost/sja-platform');
define('SITE_NAME', 'SJA Foundation');
define('SITE_DESCRIPTION', 'Investment Management Platform');
define('ADMIN_EMAIL', 'admin@sja-foundation.com');

// Security Configuration
define('ENCRYPTION_KEY', 'sja_foundation_2025_secure_key_12345');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);
define('UPLOAD_PATH', '../assets/uploads/');

// Commission Structure
define('COMMISSION_LEVELS', [
    1 => ['name' => 'Professional Ambassador', 'min_amount' => 100000, 'max_amount' => 2000000, 'rate' => 0.25],
    2 => ['name' => 'Rubies Ambassador', 'min_amount' => 3000000, 'max_amount' => 3000000, 'rate' => 0.37],
    3 => ['name' => 'Topaz Ambassador', 'min_amount' => 4000000, 'max_amount' => 4000000, 'rate' => 0.50],
    4 => ['name' => 'Silver Ambassador', 'min_amount' => 5000000, 'max_amount' => 5000000, 'rate' => 0.70],
    5 => ['name' => 'Golden Ambassador', 'min_amount' => 6000000, 'max_amount' => 6000000, 'rate' => 0.85],
    6 => ['name' => 'Platinum Ambassador', 'min_amount' => 7000000, 'max_amount' => 7000000, 'rate' => 1.00],
    7 => ['name' => 'Diamond Ambassador', 'min_amount' => 8000000, 'max_amount' => 8000000, 'rate' => 1.25],
    8 => ['name' => 'MTA', 'min_amount' => 9000000, 'max_amount' => 9000000, 'rate' => 1.50],
    9 => ['name' => 'Channel Partner', 'min_amount' => 10000000, 'max_amount' => 10000000, 'rate' => 2.00],
    10 => ['name' => 'Co-Director', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0],
    11 => ['name' => 'Director', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0],
    12 => ['name' => 'MD/CEO/CMD', 'min_amount' => 0, 'max_amount' => 0, 'rate' => 0]
]);

// Investment Configuration
define('MIN_INVESTMENT', 0);
define('MAX_INVESTMENT', 0);
define('LOCK_IN_PERIOD', 11); // months
define('PARTIAL_WITHDRAWAL_PENALTY', 3); // percentage

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>