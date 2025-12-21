<?php
// api/exam_attachment.php
// Upload an attachment for an exam.

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = sys_get_temp_dir();
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    session_save_path($sessionDir);
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

require_auth();
$user = current_user();
if (!$user || !isset($user['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$examId = (int)($_POST['exam_id'] ?? 0);
if ($examId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing exam_id']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing file']);
    exit;
}

$pdo = db();

function ensure_files_schema(PDO $pdo): void {
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
}

function ensure_exams_file_column(PDO $pdo): void {
    try {
        $existing = [];
        foreach ($pdo->query('SHOW COLUMNS FROM exams') as $row) {
            $existing[strtolower((string)$row['Field'])] = true;
        }
        if (!isset($existing['file_id'])) {
            $pdo->exec('ALTER TABLE exams ADD COLUMN file_id INT UNSIGNED NULL');
        }
    } catch (Throwable $e) {
        // Best-effort.
    }
}

ensure_files_schema($pdo);
ensure_exams_file_column($pdo);

// Verify exam ownership
$stmt = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$examId, $user['id']]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Exam not found']);
    exit;
}

$file = $_FILES['file'];
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $code = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed', 'code' => $code]);
    exit;
}

$maxBytes = 25 * 1024 * 1024; // 25MB
$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > $maxBytes) {
    http_response_code(400);
    echo json_encode(['error' => 'File must be between 1 byte and 25MB']);
    exit;
}

$originalName = (string)($file['name'] ?? 'upload');
$originalName = trim($originalName);
if ($originalName === '') $originalName = 'upload';
$originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName) ?? 'upload';
$originalName = mb_substr($originalName, 0, 255);

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid upload']);
    exit;
}

$allowed = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
    'image/png',
    'image/jpeg',
];

$mime = null;
try {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath) ?: null;
} catch (Throwable $e) {
    $mime = null;
}

if ($mime !== null && !in_array($mime, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file type']);
    exit;
}

$uploadsBase = realpath(__DIR__ . '/../../uploads');
if ($uploadsBase === false) {
    $uploadsBase = __DIR__ . '/../../uploads';
}

$userDir = rtrim($uploadsBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . (string)(int)$user['id'];
if (!is_dir($userDir)) {
    if (!@mkdir($userDir, 0700, true) && !is_dir($userDir)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create uploads directory']);
        exit;
    }
}

$ext = '';
$dot = strrpos($originalName, '.');
if ($dot !== false) {
    $candidate = strtolower(substr($originalName, $dot + 1));
    if (preg_match('/^[a-z0-9]{1,8}$/', $candidate)) {
        $ext = $candidate;
    }
}

$storedName = bin2hex(random_bytes(16)) . ($ext ? ('.' . $ext) : '');
$destPath = $userDir . DIRECTORY_SEPARATOR . $storedName;

if (!move_uploaded_file($tmpPath, $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to store uploaded file']);
    exit;
}

$storedRel = 'uploads/' . (string)(int)$user['id'] . '/' . $storedName;

$stmt = $pdo->prepare('INSERT INTO files (user_id, original_name, stored_path, mime_type, size_bytes) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$user['id'], $originalName, $storedRel, $mime, $size]);
$fileId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare('UPDATE exams SET file_id = ? WHERE id = ? AND user_id = ?');
$stmt->execute([$fileId, $examId, $user['id']]);

echo json_encode([
    'ok' => true,
    'file_id' => $fileId,
    'file_name' => $originalName,
    'file_url' => '../file.php?id=' . $fileId,
]);
