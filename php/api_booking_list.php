<?php
// File: php/api_booking_list.php
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    service ENUM('photography','videography','editing') NOT NULL,
    theme VARCHAR(100), package VARCHAR(100),
    client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL,
    client_phone VARCHAR(100), booked_date DATE NOT NULL,
    booked_hour TINYINT, booked_end_hour TINYINT,
    delivery_date DATE, urgent TINYINT(1) DEFAULT 0,
    payment_method VARCHAR(50), total_price VARCHAR(100),
    notes TEXT, calendar_link TEXT,
    status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_service (booked_date, service)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->query("SELECT * FROM bookings ORDER BY booked_date DESC, booked_hour ASC, created_at DESC LIMIT 200");
    $rows = $stmt->fetchAll();
    jsonResponse(['success' => true, 'data' => $rows, 'count' => count($rows)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';
    $id     = (int)($data['id'] ?? 0);

    if ($action === 'update_status') {
        $status = $data['status'] ?? '';
        if (!in_array($status, ['pending','confirmed','completed','cancelled'])) jsonResponse(['error'=>'Invalid status'],400);
        $db->prepare("UPDATE bookings SET status=:s WHERE id=:id")->execute([':s'=>$status,':id'=>$id]);
        jsonResponse(['success'=>true]);
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM bookings WHERE id=:id")->execute([':id'=>$id]);
        jsonResponse(['success'=>true]);
    }
    jsonResponse(['error'=>'Unknown action'],400);
}
?>
