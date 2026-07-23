<?php
// Auth helper — included at the top of every protected page/API
// File: php/auth.php

require_once __DIR__ . '/config.php';

// Use a secure session config
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_name('cc_admin');
    session_start();
}

// Hardcoded admin credentials (no DB dependency)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_BCRYPT));
// We store the hash as a constant at boot time.
// For verification we use the function below.

function isLoggedIn(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Verify submitted credentials against hardcoded values.
 * Returns true on success, false on failure.
 */
function attemptLogin(string $username, string $password): bool {
    // Constant-time username comparison to prevent timing attacks
    $usernameOk = hash_equals(ADMIN_USERNAME, $username);
    // password_verify is already timing-safe
    $passwordOk = password_verify($password, ADMIN_PASSWORD_HASH);

    if ($usernameOk && $passwordOk) {
        // Regenerate session ID on login to prevent session fixation
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $username;
        $_SESSION['login_time']      = time();
        return true;
    }
    return false;
}

function requireLogin(string $redirectTo = '../admin/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
    // Auto-logout after 4 hours of inactivity
    if (!empty($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
        logout($redirectTo);
    }
    // Refresh activity timer
    $_SESSION['login_time'] = time();
}

function logout(string $redirectTo = '../admin/login.php'): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: ' . $redirectTo);
    exit;
}
?>
