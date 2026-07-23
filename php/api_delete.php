<?php
// API: Delete a media item or project
// File: php/api_delete.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';

$type = trim($_GET['type'] ?? '');
$id   = (int)($_GET['id']   ?? 0);

if (!$id || !in_array($type, ['media', 'project'])) {
    jsonResponse(['error' => 'Invalid parameters'], 400);
}

try {
    $db = getDB();

    if ($type === 'media') {
        // Fetch paths before deletion so we can remove the actual file
        $stmt = $db->prepare("SELECT file_path, thumbnail_path FROM media WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            // Delete the physical file using PROJECT_ROOT (absolute disk path)
            $filePath = PROJECT_ROOT . '/' . ltrim($row['file_path'], '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            // Delete thumbnail if it's different from the main file
            if ($row['thumbnail_path'] && $row['thumbnail_path'] !== $row['file_path']) {
                $thumbPath = PROJECT_ROOT . '/' . ltrim($row['thumbnail_path'], '/');
                if (file_exists($thumbPath)) {
                    @unlink($thumbPath);
                }
            }
        }
        $db->prepare("DELETE FROM media WHERE id = :id")->execute([':id' => $id]);

    } else {
        $db->prepare("DELETE FROM projects WHERE id = :id")->execute([':id' => $id]);
    }

    jsonResponse(['success' => true]);

} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
?>
