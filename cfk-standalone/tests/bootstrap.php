<?php

/**
 * PHPUnit Bootstrap File
 * Sets up testing environment
 */

// Define test environment
define('CFK_APP', true);
define('CFK_TESTING', true);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration (test environment)
require_once __DIR__ . '/../config/config.php';

// Load core functions
require_once __DIR__ . '/../includes/functions.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Session handling for tests
if (!isset($_SESSION)) {
    $_SESSION = [];
}
