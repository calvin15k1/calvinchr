<?php
// API: Edit/update a media record
// File: php/api_edit_media.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error'=>'POST only'], 405); }

$data     = json_decode(file_get_contents('php://input'), true);
$id       = (int)($data['id'] ?? 0);
$title    = trim($data['title'] ?? '');
$category = trim($data['category'] ?? 'general');
$desc     = trim($data['description'] ?? '');
$featured = (int)($data['featured'] ?? 0);

if (!$id)    { jsonResponse(['error'=>'ID required'], 400); }
if (!$title) { jsonResponse(['error'=>'Title required'], 422); }

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE media SET title=:title, category=:cat, description=:desc, featured=:feat, updated_at=NOW() WHERE id=:id");
    $stmt->execute([':title'=>$title, ':cat'=>$category, ':desc'=>$desc, ':feat'=>$featured, ':id'=>$id]);
    if ($stmt->rowCount() === 0) { jsonResponse(['error'=>'Record not found'], 404); }
    jsonResponse(['success'=>true]);
} catch (Exception $e) {
    jsonResponse(['error'=>$e->getMessage()], 500);
}
?>
