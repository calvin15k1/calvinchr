<?php
// API: Log a page visit
// File: php/api_track.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

// Auto-create analytics table if it doesn't exist
$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS analytics (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    page        VARCHAR(255) NOT NULL DEFAULT '/',
    referrer    VARCHAR(500),
    user_agent  VARCHAR(500),
    ip_hash     VARCHAR(64),
    country     VARCHAR(100),
    visited_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page (page),
    INDEX idx_visited (visited_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'POST only'], 405);
}

$data      = json_decode(file_get_contents('php://input'), true) ?: [];
$page      = substr(trim($data['page'] ?? '/'), 0, 255);
$referrer  = substr(trim($data['referrer'] ?? ''), 0, 500);
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

// Hash IP for privacy (no raw IPs stored)
$ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ipHash = hash('sha256', $ip . date('Y-m-d')); // changes daily for privacy

try {
    $stmt = $db->prepare("INSERT INTO analytics (page, referrer, user_agent, ip_hash) VALUES (:page, :ref, :ua, :ip)");
    $stmt->execute([':page' => $page, ':ref' => $referrer, ':ua' => $userAgent, ':ip' => $ipHash]);
    jsonResponse(['success' => true]);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
?>
