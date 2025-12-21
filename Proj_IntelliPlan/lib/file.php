<?php
// lib/file.php
// Secure file download/inline viewer for uploaded attachments.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = sys_get_temp_dir();
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    session_save_path($sessionDir);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_auth();
$user = current_user();
if (!$user || !isset($user['id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Missing id';
    exit;
}

$pdo = db();

// Ensure files table exists for dev setups.
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS files (\n" .
    "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
    "  user_id INT UNSIGNED NOT NULL,\n" .
    "  original_name VARCHAR(255) NOT NULL,\n" .
    "  stored_path VARCHAR(500) NOT NULL,\n" .
    "  mime_type VARCHAR(120) NULL,\n" .
    "  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,\n" .
    "  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
    "  PRIMARY KEY (id),\n" .
    "  KEY idx_files_user (user_id)\n" .
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$stmt = $pdo->prepare('SELECT id, original_name, stored_path, mime_type, size_bytes FROM files WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$id, $user['id']]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$storedPath = (string)($row['stored_path'] ?? '');
$originalName = (string)($row['original_name'] ?? 'download');
$mime = $row['mime_type'] ? (string)$row['mime_type'] : 'application/octet-stream';

$base = realpath(__DIR__ . '/../');
if ($base === false) $base = __DIR__ . '/../';

$full = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath);
$real = realpath($full);

$uploadsRoot = realpath(__DIR__ . '/../uploads');
if ($uploadsRoot === false) $uploadsRoot = __DIR__ . '/../uploads';

if ($real === false || strpos($real, $uploadsRoot) !== 0 || !is_file($real)) {
    http_response_code(404);
    echo 'File missing';
    exit;
}

// Decide inline vs attachment. Inline helps with PDFs ("start answering")
$inline = in_array($mime, ['application/pdf', 'image/png', 'image/jpeg', 'text/plain'], true);

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($real));
header('Cache-Control: private, max-age=0, must-revalidate');

$disposition = $inline ? 'inline' : 'attachment';
$safeName = str_replace(['"', "\r", "\n"], '_', $originalName);
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');

$fp = fopen($real, 'rb');
if ($fp === false) {
    http_response_code(500);
    echo 'Failed to open file';
    exit;
}

fpassthru($fp);
fclose($fp);
