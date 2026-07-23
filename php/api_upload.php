<?php
// API: Upload media files
// File: php/api_upload.php
// Called from admin/index.php via XHR POST to ../php/api_upload.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

// ── Determine upload subdirectory ──────────────────────────
$type   = isset($_POST['type']) && $_POST['type'] === 'video' ? 'video' : 'photo';
$subDir = $type === 'video' ? 'videos' : 'photos';

// Absolute path on disk: /Applications/XAMPP/.../calvin_portfolio/uploads/photos/
$targetDir = UPLOAD_PATH . $subDir . '/';

// Create directory if it doesn't exist yet
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        jsonResponse(['error' => 'Cannot create upload directory: ' . $targetDir], 500);
    }
}

// Check directory is writable
if (!is_writable($targetDir)) {
    jsonResponse([
        'error' => 'Upload directory is not writable. Run: chmod -R 755 ' . UPLOAD_PATH,
        'path'  => $targetDir
    ], 500);
}

// ── Validate uploaded file ─────────────────────────────────
if (!isset($_FILES['file'])) {
    jsonResponse(['error' => 'No file field in request'], 400);
}
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload',
    ];
    $code = $_FILES['file']['error'];
    jsonResponse(['error' => $uploadErrors[$code] ?? 'Upload error code ' . $code], 400);
}

$file = $_FILES['file'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$allowedPhoto = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$allowedVideo = ['mp4', 'mov', 'webm', 'avi', 'mkv'];
$allowed = $type === 'video' ? $allowedVideo : $allowedPhoto;

if (!in_array($ext, $allowed)) {
    jsonResponse(['error' => 'Invalid file type ".' . $ext . '". Allowed: ' . implode(', ', $allowed)], 400);
}

// Size limits: 500MB video, 20MB photo
$maxSize = $type === 'video' ? 500 * 1024 * 1024 : 20 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(['error' => 'File too large (' . round($file['size']/1024/1024, 1) . ' MB). Max: ' . ($type==='video' ? '500' : '20') . ' MB'], 400);
}

// ── Move file ──────────────────────────────────────────────
$filename     = uniqid('media_', true) . '.' . $ext;
$absolutePath = $targetDir . $filename;
// Relative path stored in DB — relative to project root, used as web path
$relativePath = 'uploads/' . $subDir . '/' . $filename;
// Full public URL
$publicUrl    = SITE_URL . '/' . $relativePath;

if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
    jsonResponse([
        'error'    => 'move_uploaded_file() failed.',
        'tmp_name' => $file['tmp_name'],
        'target'   => $absolutePath,
        'hint'     => 'Check folder permissions: chmod -R 755 ' . UPLOAD_PATH
    ], 500);
}

// ── Save record to database ────────────────────────────────
$title       = trim($_POST['title'] ?? '') ?: pathinfo($file['name'], PATHINFO_FILENAME);
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '') ?: 'general';
$featured    = (isset($_POST['featured']) && $_POST['featured'] == '1') ? 1 : 0;
// For photos the thumbnail IS the image itself; videos need a separate poster
$thumbnail   = ($type === 'photo') ? $relativePath : null;

try {
    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO media (title, description, type, category, file_path, thumbnail_path, featured, sort_order)
        VALUES (:title, :desc, :type, :cat, :path, :thumb, :featured, 0)
    ");
    $stmt->execute([
        ':title'    => $title,
        ':desc'     => $description,
        ':type'     => $type,
        ':cat'      => $category,
        ':path'     => $relativePath,
        ':thumb'    => $thumbnail,
        ':featured' => $featured,
    ]);
    $id = $db->lastInsertId();

    jsonResponse([
        'success'  => true,
        'id'       => $id,
        'filename' => $filename,
        'path'     => $relativePath,   // e.g. uploads/photos/media_abc123.jpg
        'url'      => $publicUrl,      // e.g. http://localhost/calvin_portfolio/uploads/photos/...
    ]);

} catch (Exception $e) {
    // File was moved but DB failed — clean it up
    @unlink($absolutePath);
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
}
?>
