<!-- includes/config.php -->

<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'personal_library');

// Application settings
define('APP_NAME', 'Personal Library System');
define('BASE_URL', 'http://localhost/personal-library-system');
define('SITE_URL', '/personal-library-system');

// Error reporting - set to false in production
define('DISPLAY_ERRORS', true);
if (DISPLAY_ERRORS) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

// Session settings
session_start();

// Date and time settings
date_default_timezone_set('Asia/Shanghai');
?>