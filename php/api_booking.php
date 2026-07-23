<?php
// api_booking.php — Date-based booking (no hourly slots)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';
$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS bookings (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    service        ENUM('photography','videography','editing') NOT NULL,
    theme          VARCHAR(100), package VARCHAR(100),
    client_name    VARCHAR(255) NOT NULL,
    client_email   VARCHAR(255) NOT NULL,
    client_phone   VARCHAR(100),
    booked_date    DATE NOT NULL,
    urgent         TINYINT DEFAULT 0,
    payment_method ENUM('bank','paypal') NOT NULL DEFAULT 'bank',
    total_price    VARCHAR(255), notes TEXT,
    status         ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
    delivery_date  DATE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_service (booked_date, service)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// ════════════════════════════════════════════════════
// GET: Return blocked dates for a service (whole month or all future)
// Public endpoint — no auth needed for client-facing calendar
// ════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $service = trim($_GET['service'] ?? '');
    if (!in_array($service, ['photography','videography','editing'])) {
        jsonResponse(['success'=>true,'blocked_dates'=>[]]);
    }
    if ($service === 'editing') {
        jsonResponse(['success'=>true,'blocked_dates'=>[]]);
    }
    // Return all booked dates for this service from today onwards
    $stmt = $db->prepare("
        SELECT DISTINCT booked_date
        FROM bookings
        WHERE service = :service
          AND status != 'cancelled'
          AND booked_date >= CURDATE()
        ORDER BY booked_date ASC
    ");
    $stmt->execute([':service' => $service]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    jsonResponse(['success'=>true,'blocked_dates'=>$rows]);
}

// ════════════════════════════════════════════════════
// POST: Save a booking (date-based, no hours)
// ════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $service   = trim($data['service']        ?? '');
    $theme     = trim($data['theme']          ?? '');
    $package   = trim($data['package']        ?? '');
    $name      = trim($data['client_name']    ?? '');
    $email     = trim($data['client_email']   ?? '');
    $phone     = trim($data['client_phone']   ?? '');
    $date      = trim($data['booked_date']    ?? '');
    $urgent    = !empty($data['urgent']) ? 1 : 0;
    $payment   = in_array($data['payment_method']??'', ['bank','paypal']) ? $data['payment_method'] : 'bank';
    $price     = trim($data['total_price']    ?? '');
    $notes     = trim($data['notes']          ?? '');
    $content   = trim($data['content_type']   ?? '');

    // Validate
    if (!$service || !$name || !$email || !$date) {
        jsonResponse(['success'=>false,'error'=>'Missing required fields.'],422);
    }
    if (!in_array($service, ['photography','videography','editing'])) {
        jsonResponse(['success'=>false,'error'=>'Invalid service.'],422);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success'=>false,'error'=>'Invalid email address.'],422);
    }
    if ($date < date('Y-m-d')) {
        jsonResponse(['success'=>false,'error'=>'Cannot book a past date.'],422);
    }

    // Date conflict check (photography & videography only — one booking per date per service)
    if (in_array($service, ['photography','videography'])) {
        $chk = $db->prepare("SELECT id FROM bookings WHERE booked_date=:d AND service=:s AND status!='cancelled' LIMIT 1");
        $chk->execute([':d'=>$date,':s'=>$service]);
        if ($chk->fetch()) {
            jsonResponse(['success'=>false,'error'=>'That date is already booked for '.ucfirst($service).'. Please choose a different date.'],409);
        }
    }

    // Delivery date for editing
    $deliveryDate = null; $deliveryFormatted = null;
    if ($service === 'editing') {
        $dt = new DateTime($date);
        $dt->modify($urgent ? '+1 week' : '+2 weeks');
        $deliveryDate      = $dt->format('Y-m-d');
        $deliveryFormatted = $dt->format('l, d F Y');
    }

    // Append content type to notes for editing
    if ($service === 'editing' && $content) {
        $notes = ($notes ? $notes . "\n" : '') . "Content type: $content";
    }

    // Insert
    $ins = $db->prepare("INSERT INTO bookings
        (service,theme,package,client_name,client_email,client_phone,
         booked_date,urgent,payment_method,total_price,notes,delivery_date)
        VALUES(:svc,:theme,:pkg,:name,:email,:phone,:date,:urgent,:pay,:price,:notes,:delivery)");
    $ins->execute([
        ':svc'=>$service,':theme'=>$theme,':pkg'=>$package,
        ':name'=>$name,':email'=>$email,':phone'=>$phone,
        ':date'=>$date,':urgent'=>$urgent,':pay'=>$payment,
        ':price'=>$price,':notes'=>$notes,':delivery'=>$deliveryDate,
    ]);
    $bookingId = $db->lastInsertId();

    // Mirror to messages
    $msgLines = array_filter([
        "=== BOOKING #{$bookingId} ===",
        "Service: ".ucfirst($service).($theme?" · $theme":'').($package?" · $package":''),
        "Date: ".date('d F Y',strtotime($date)),
        $service==='editing' ? "Rush: ".($urgent?"Yes (1-week +50%)":'No (2-week standard)') : '',
        $deliveryFormatted ? "Delivery: $deliveryFormatted" : '',
        "Payment: ".($payment==='bank'?'Blu by BCA 007002991116':'PayPal calvinchristian15k1@gmail.com'),
        "Price: $price",
        $phone?"Phone: $phone":'',
        $notes?"Notes: $notes":'',
    ]);
    $db->prepare("INSERT INTO messages(name,email,subject,message) VALUES(:n,:e,:s,:m)")->execute([
        ':n'=>$name,':e'=>$email,
        ':s'=>"📅 Booking #{$bookingId}: ".ucfirst($service).($theme?" · $theme":''),
        ':m'=>implode("\n",array_values($msgLines)),
    ]);

    // Google Calendar link
    $calLink = null;
    if (in_array($service,['photography','videography'])) {
        $dtS = date('Ymd',strtotime($date)).'T080000';
        $dtE = date('Ymd',strtotime($date)).'T180000';
        $title = urlencode(ucfirst($service).' · '.$name.($theme?' · '.$theme:''));
        $detail = urlencode("Booking #{$bookingId}\n{$email}\nPrice: {$price}");
        $calLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$dtS}/{$dtE}&details={$detail}&location=Bali%2C+Indonesia";
    } elseif ($service==='editing' && $deliveryDate) {
        $dtD = date('Ymd',strtotime($deliveryDate));
        $dtN = date('Ymd',strtotime($deliveryDate.'+1 day'));
        $title = urlencode('📦 Delivery: '.$name);
        $detail = urlencode("Booking #{$bookingId} · ".($urgent?'Rush 1-week':'Standard 2-week')."\n{$email}\nPrice: {$price}");
        $calLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$dtD}/{$dtN}&details={$detail}";
    }

    jsonResponse(['success'=>true,'booking_id'=>(int)$bookingId,'calendar_link'=>$calLink,'delivery_date'=>$deliveryFormatted]);
}

jsonResponse(['error'=>'Invalid request'],405);
?>
