<?php
// API: Edit/update a project record
// File: php/api_edit_project.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error'=>'POST only'], 405); }

$data     = json_decode(file_get_contents('php://input'), true);
$id       = (int)($data['id'] ?? 0);
$title    = trim($data['title'] ?? '');
$subtitle = trim($data['subtitle'] ?? '');
$desc     = trim($data['description'] ?? '');
$category = trim($data['category'] ?? 'photography');
$client   = trim($data['client'] ?? '');
$year     = (int)($data['year'] ?? date('Y'));
$cover    = trim($data['cover_image'] ?? '');
$video    = trim($data['video_url'] ?? '');
$featured = (int)($data['featured'] ?? 0);

if (!$id)    { jsonResponse(['error'=>'ID required'], 400); }
if (!$title) { jsonResponse(['error'=>'Title required'], 422); }
if (!in_array($category, ['photography','videography','editing'])) { $category = 'photography'; }

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE projects SET title=:title, subtitle=:sub, description=:desc, category=:cat, client=:cli, year=:yr, cover_image=:cov, video_url=:vid, featured=:feat WHERE id=:id");
    $stmt->execute([
        ':title'=>$title, ':sub'=>$subtitle, ':desc'=>$desc,
        ':cat'=>$category, ':cli'=>$client, ':yr'=>$year,
        ':cov'=>$cover, ':vid'=>$video, ':feat'=>$featured, ':id'=>$id,
    ]);
    if ($stmt->rowCount() === 0) { jsonResponse(['error'=>'Record not found'], 404); }
    jsonResponse(['success'=>true]);
} catch (Exception $e) {
    jsonResponse(['error'=>$e->getMessage()], 500);
}
?>
