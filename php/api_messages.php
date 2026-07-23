<?php
// API: Fetch contact messages
// File: php/api_messages.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 100");
$msgs = $stmt->fetchAll();

// Mark all as read
$db->exec("UPDATE messages SET is_read = 1");

echo json_encode(['success' => true, 'data' => $msgs, 'count' => count($msgs)]);
?>
