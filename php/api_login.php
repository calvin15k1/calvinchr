<?php
// API: Handle login POST from login page
// File: php/api_login.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST only'], 405);
}

$data     = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'error' => 'Username and password are required.'], 422);
}

// Basic rate-limit: max 5 attempts per minute stored in session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts']      = 0;
    $_SESSION['login_attempt_start'] = time();
}
if ((time() - $_SESSION['login_attempt_start']) > 60) {
    $_SESSION['login_attempts']      = 0;
    $_SESSION['login_attempt_start'] = time();
}
if ($_SESSION['login_attempts'] >= 5) {
    jsonResponse(['success' => false, 'error' => 'Too many attempts. Please wait a minute and try again.'], 429);
}

if (attemptLogin($username, $password)) {
    $_SESSION['login_attempts'] = 0;
    jsonResponse(['success' => true, 'redirect' => '../admin/index.php']);
} else {
    $_SESSION['login_attempts']++;
    jsonResponse(['success' => false, 'error' => 'Invalid username or password.'], 401);
}
?>
