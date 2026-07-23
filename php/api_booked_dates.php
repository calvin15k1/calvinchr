<?php
// API: Return booked dates for public calendar (no personal info exposed)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/config.php';
$db = getDB();

// Ensure table exists silently
try {
  $db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service ENUM('photography','videography','editing') NOT NULL,
    theme VARCHAR(100), package VARCHAR(100),
    client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL,
    client_phone VARCHAR(100), booked_date DATE NOT NULL,
    start_hour TINYINT, duration TINYINT DEFAULT 1, urgent TINYINT DEFAULT 0,
    payment_method ENUM('bank','paypal') NOT NULL DEFAULT 'bank',
    total_price VARCHAR(255), notes TEXT,
    status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    delivery_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_service (booked_date, service)
  ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch(Exception $e) {}

$service = isset($_GET['service']) && in_array($_GET['service'],['photography','videography','editing'])
         ? $_GET['service'] : null;

$sql = "SELECT booked_date, service FROM bookings
        WHERE status != 'cancelled'
          AND booked_date >= CURDATE()";
$params = [];
if ($service) {
  $sql .= " AND service = :service";
  $params[':service'] = $service;
}
$sql .= " ORDER BY booked_date ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Return only dates and service type — no client info
$booked = [];
foreach ($rows as $r) {
  $booked[] = ['date' => $r['booked_date'], 'service' => $r['service']];
}

echo json_encode(['success' => true, 'booked_dates' => $booked]);
?>
