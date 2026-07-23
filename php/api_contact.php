<?php
// API: Handle contact form submissions
// File: php/api_contact.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

$name    = trim($data['name'] ?? '');
$email   = trim($data['email'] ?? '');
$subject = trim($data['subject'] ?? '');
$message = trim($data['message'] ?? '');

// Validate
$errors = [];
if (empty($name))    $errors[] = 'Name is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($message)) $errors[] = 'Message is required';

if ($errors) {
    jsonResponse(['success' => false, 'errors' => $errors], 422);
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
    $stmt->execute([
        ':name'    => htmlspecialchars($name),
        ':email'   => htmlspecialchars($email),
        ':subject' => htmlspecialchars($subject),
        ':message' => htmlspecialchars($message),
    ]);
    
    jsonResponse(['success' => true, 'message' => 'Your message has been sent! I\'ll get back to you soon.']);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Failed to send message. Please try again.'], 500);
}
?>
