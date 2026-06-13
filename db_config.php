<?php
/**
 * SecureBank - Central Database Configuration
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bank');

// Enable detailed error reporting for local development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
mysqli_set_charset($con, 'utf8mb4');