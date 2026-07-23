<?php
// API: Save/create a project
// File: php/api_save_project.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST only'], 405); }

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { jsonResponse(['error' => 'No data'], 400); }

$title       = trim($data['title'] ?? '');
$subtitle    = trim($data['subtitle'] ?? '');
$description = trim($data['description'] ?? '');
$category    = trim($data['category'] ?? 'photography');
$client      = trim($data['client'] ?? '');
$year        = (int)($data['year'] ?? date('Y'));
$cover       = trim($data['cover_image'] ?? '');
$video       = trim($data['video_url'] ?? '');
$featured    = (int)($data['featured'] ?? 0);

if (!$title) { jsonResponse(['error' => 'Title required'], 422); }
if (!in_array($category, ['photography','videography','editing'])) {
    $category = 'photography';
}

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO projects (title, subtitle, description, category, client, year, cover_image, video_url, featured) VALUES (:title,:sub,:desc,:cat,:cli,:yr,:cov,:vid,:feat)");
    $stmt->execute([
        ':title' => $title, ':sub' => $subtitle, ':desc' => $description,
        ':cat'   => $category, ':cli' => $client, ':yr' => $year,
        ':cov'   => $cover, ':vid' => $video, ':feat' => $featured,
    ]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
?>
