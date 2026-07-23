<?php
// API: Get all bookings / update booking status
// File: php/api_get_bookings.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin('../admin/login.php');

$db = getDB();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    service        ENUM('photography','videography','editing') NOT NULL,
    theme          VARCHAR(100), package VARCHAR(100),
    client_name    VARCHAR(255) NOT NULL,
    client_email   VARCHAR(255) NOT NULL,
    client_phone   VARCHAR(100),
    booked_date    DATE NOT NULL,
    start_hour     TINYINT, duration TINYINT DEFAULT 1,
    urgent         TINYINT DEFAULT 0,
    payment_method ENUM('bank','paypal') NOT NULL DEFAULT 'bank',
    total_price    VARCHAR(255), notes TEXT, status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    delivery_date  DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_service (booked_date, service)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// ── POST: Update booking status ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');
    if (!$id || !in_array($status, ['pending','confirmed','cancelled'])) {
        jsonResponse(['error' => 'Invalid params'], 400);
    }
    $db->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$status,':id'=>$id]);
    jsonResponse(['success' => true]);
}

// ── GET: Fetch all bookings ───────────────────────────────
$bookings = $db->query("SELECT * FROM bookings ORDER BY booked_date DESC, start_hour ASC")->fetchAll();
jsonResponse(['success' => true, 'data' => $bookings, 'count' => count($bookings)]);
?>
