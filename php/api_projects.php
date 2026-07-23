<?php
// API: Get projects
// File: php/api_projects.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php';

$db = getDB();
$category = isset($_GET['category']) ? $_GET['category'] : null;
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
$limit    = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

$where = [];
$params = [];

if ($category && in_array($category, ['photography', 'videography', 'editing'])) {
    $where[] = "category = :category";
    $params[':category'] = $category;
}
if ($featured !== null) {
    $where[] = "featured = :featured";
    $params[':featured'] = $featured;
}

$sql = "SELECT * FROM projects";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY sort_order ASC, created_at DESC LIMIT :limit";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

foreach ($projects as &$p) {
    $p['cover_url'] = $p['cover_image'] ? SITE_URL . '/' . str_replace(' ', '%20', ltrim($p['cover_image'], '/')) : null;
    // Build full video URL and encode spaces/special chars in the filename
    if ($p['video_url']) {
        $v = trim($p['video_url']);
        // If it's already a full http URL (YouTube, Vimeo, external), leave it
        if (preg_match('#^https?://#i', $v)) {
            $p['video_url'] = $v;
        } else {
            // Local file — prepend SITE_URL and encode only the filename part
            $parts    = explode('/', ltrim($v, '/'));
            $filename = array_pop($parts);
            $dir      = implode('/', $parts);
            $encoded  = ($dir ? $dir . '/' : '') . rawurlencode($filename);
            $p['video_url'] = SITE_URL . '/' . $encoded;
        }
    }
}

echo json_encode(['success' => true, 'data' => $projects, 'count' => count($projects)]);
?>
