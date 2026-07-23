<?php
// API: Fetch media (photos/videos) for gallery and carousel
// File: php/api_media.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$db       = getDB();
$type     = isset($_GET['type'])     && in_array($_GET['type'], ['photo','video']) ? $_GET['type'] : null;
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
$limit    = isset($_GET['limit'])    ? max(1, min(500, (int)$_GET['limit'])) : 50;
$category = isset($_GET['category']) ? trim($_GET['category']) : null;

$where  = [];
$params = [];

if ($type !== null) {
    $where[]          = "type = :type";
    $params[':type']  = $type;
}
if ($featured !== null) {
    $where[]              = "featured = :featured";
    $params[':featured']  = $featured;
}
if ($category !== null && $category !== '') {
    $where[]              = "category = :category";
    $params[':category']  = $category;
}

$sql = "SELECT * FROM media";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY featured DESC, sort_order ASC, created_at DESC LIMIT :limit";

$stmt = $db->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$media = $stmt->fetchAll();

// Build full public URLs — encode spaces/special chars in filenames
function buildFileUrl($path) {
    if (!$path) return null;
    $parts    = explode('/', ltrim($path, '/'));
    $filename = array_pop($parts);
    $dir      = implode('/', $parts);
    $encoded  = ($dir ? $dir . '/' : '') . rawurlencode($filename);
    return SITE_URL . '/' . $encoded;
}

foreach ($media as &$item) {
    $item['file_url']      = buildFileUrl($item['file_path']);
    $item['thumbnail_url'] = $item['thumbnail_path']
        ? buildFileUrl($item['thumbnail_path'])
        : $item['file_url'];
}

echo json_encode([
    'success' => true,
    'count'   => count($media),
    'data'    => $media,
]);
?>
