<?php
// Database Configuration for Calvin Portfolio
// File: php/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');           // XAMPP default (empty)
define('DB_NAME', 'calvin_portfolio');
define('DB_PORT', 3306);

// ── ROOT PATH: absolute path to /calvin_portfolio/ folder ──
// __DIR__ is /xampp/xamppfiles/htdocs/calvin_portfolio/php
// so going one level up lands exactly at the project root.
define('PROJECT_ROOT', realpath(__DIR__ . '/..'));  
// e.g. /Applications/XAMPP/xamppfiles/htdocs/calvin_portfolio

define('SITE_URL', 'http://localhost/calvin_portfolio');

// Absolute path to uploads folder (used for file operations)
define('UPLOAD_PATH', PROJECT_ROOT . '/uploads/');

// Web-accessible URL prefix for uploaded files
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Create connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// JSON response helper
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data);
    exit;
}
?>
