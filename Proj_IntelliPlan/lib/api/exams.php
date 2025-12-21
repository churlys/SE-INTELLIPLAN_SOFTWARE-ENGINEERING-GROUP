<?php
// api/exams.php
// JSON API for exams (GET list, POST create, PUT update, DELETE).

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
if (file_exists(__DIR__ . '/../auth.php')) {
  require_once __DIR__ . '/../auth.php';
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Auth library not found.']);
  exit;
}

require_auth();
$user = current_user();
$pdo = db();

function ensure_exams_schema(PDO $pdo): void {
  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS exams (\n" .
    "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
    "  user_id INT UNSIGNED NOT NULL,\n" .
    "  title VARCHAR(255) NOT NULL,\n" .
    "  subject VARCHAR(100) NULL,\n" .
    "  exam_date DATE NOT NULL,\n" .
    "  exam_time TIME NULL,\n" .
    "  location VARCHAR(255) NULL,\n" .
    "  notes TEXT NULL,\n" .
    "  file_id INT UNSIGNED NULL,\n" .
    "  status VARCHAR(20) NOT NULL DEFAULT 'scheduled',\n" .
    "  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
    "  PRIMARY KEY (id),\n" .
    "  KEY idx_exams_user_date (user_id, exam_date)\n" .
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  );

  // Best-effort upgrades for older schemas.
  try {
    $existing = [];
    foreach ($pdo->query('SHOW COLUMNS FROM exams') as $row) {
      $existing[strtolower((string)$row['Field'])] = true;
    }
    $alter = [];
    if (!isset($existing['title'])) $alter[] = "ADD COLUMN title VARCHAR(255) NOT NULL";
    if (!isset($existing['subject'])) $alter[] = "ADD COLUMN subject VARCHAR(100) NULL";
    if (!isset($existing['exam_date'])) $alter[] = "ADD COLUMN exam_date DATE NOT NULL";
    if (!isset($existing['exam_time'])) $alter[] = "ADD COLUMN exam_time TIME NULL";
    if (!isset($existing['location'])) $alter[] = "ADD COLUMN location VARCHAR(255) NULL";
    if (!isset($existing['notes'])) $alter[] = "ADD COLUMN notes TEXT NULL";
    if (!isset($existing['file_id'])) $alter[] = "ADD COLUMN file_id INT UNSIGNED NULL";
    if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'scheduled'";
    if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($alter) {
      $pdo->exec('ALTER TABLE exams ' . implode(', ', $alter));
    }
  } catch (Throwable $e) {
    // Ignore schema drift errors in production.
  }
}

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

ensure_exams_schema($pdo);
ensure_files_schema($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

try {
  if ($method === 'GET') {
    // Optional filters: date=YYYY-MM-DD or start/end
    $date = trim((string)($_GET['date'] ?? ''));
    $start = trim((string)($_GET['start'] ?? ''));
    $end = trim((string)($_GET['end'] ?? ''));

    $where = ['user_id = ?'];
    $params = [$user['id']];

    if ($date !== '') {
      $where[] = 'exam_date = ?';
      $params[] = $date;
    } elseif ($start !== '' && $end !== '') {
      $where[] = 'exam_date BETWEEN ? AND ?';
      $params[] = $start;
      $params[] = $end;
    }

    $sql = 'SELECT e.id, e.title, e.subject, e.exam_date, e.exam_time, e.location, e.notes, e.status, e.file_id, f.original_name AS file_name ' .
      'FROM exams e ' .
      'LEFT JOIN files f ON f.id = e.file_id AND f.user_id = e.user_id ' .
      'WHERE ' . implode(' AND ', $where) . ' ORDER BY e.exam_date ASC, e.exam_time ASC, e.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$x) {
      $fid = (int)($x['file_id'] ?? 0);
      $x['file_id'] = $fid ?: null;
      $x['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
    }
    echo json_encode($rows);
    exit;
  }

  if ($method === 'POST') {
    $title = trim((string)($input['title'] ?? ''));
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $exam_date = trim((string)($input['exam_date'] ?? ''));
    $exam_time = isset($input['exam_time']) ? trim((string)$input['exam_time']) : null;
    $location = isset($input['location']) ? trim((string)$input['location']) : null;
    $notes = isset($input['notes']) ? trim((string)$input['notes']) : null;

    if ($title === '' || $exam_date === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Missing title or exam_date']);
      exit;
    }

    $stmt = $pdo->prepare('INSERT INTO exams (user_id, title, subject, exam_date, exam_time, location, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      $user['id'],
      $title,
      ($subject !== '' ? $subject : null),
      $exam_date,
      ($exam_time !== '' ? $exam_time : null),
      ($location !== '' ? $location : null),
      ($notes !== '' ? $notes : null)
    ]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT e.id, e.title, e.subject, e.exam_date, e.exam_time, e.location, e.notes, e.status, e.file_id, f.original_name AS file_name FROM exams e LEFT JOIN files f ON f.id = e.file_id AND f.user_id = e.user_id WHERE e.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $fid = (int)($row['file_id'] ?? 0);
    $row['file_id'] = $fid ?: null;
    $row['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
    echo json_encode($row);
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper((string)$input['_method']) === 'PUT')) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    $stmt = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $title = trim((string)($input['title'] ?? ''));
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $exam_date = trim((string)($input['exam_date'] ?? ''));
    $exam_time = isset($input['exam_time']) ? trim((string)$input['exam_time']) : null;
    $location = isset($input['location']) ? trim((string)$input['location']) : null;
    $notes = isset($input['notes']) ? trim((string)$input['notes']) : null;
    $status = trim((string)($input['status'] ?? 'scheduled'));

    if ($title === '' || $exam_date === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Missing title or exam_date']);
      exit;
    }

    $stmt = $pdo->prepare('UPDATE exams SET title = ?, subject = ?, exam_date = ?, exam_time = ?, location = ?, notes = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([
      $title,
      ($subject !== '' ? $subject : null),
      $exam_date,
      ($exam_time !== '' ? $exam_time : null),
      ($location !== '' ? $location : null),
      ($notes !== '' ? $notes : null),
      ($status !== '' ? $status : 'scheduled'),
      $id,
      $user['id']
    ]);

    $stmt = $pdo->prepare('SELECT e.id, e.title, e.subject, e.exam_date, e.exam_time, e.location, e.notes, e.status, e.file_id, f.original_name AS file_name FROM exams e LEFT JOIN files f ON f.id = e.file_id AND f.user_id = e.user_id WHERE e.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $fid = (int)($row['file_id'] ?? 0);
    $row['file_id'] = $fid ?: null;
    $row['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
    echo json_encode($row);
    exit;
  }

  if ($method === 'DELETE' || ($method === 'POST' && isset($input['_method']) && strtoupper((string)$input['_method']) === 'DELETE')) {
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    $stmt = $pdo->prepare('DELETE FROM exams WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    echo json_encode(['deleted' => $id]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}
