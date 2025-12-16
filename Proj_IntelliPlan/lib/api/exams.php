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
    "  exam_date DATE NOT NULL,\n" .
    "  exam_time TIME NULL,\n" .
    "  location VARCHAR(255) NULL,\n" .
    "  notes TEXT NULL,\n" .
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
    if (!isset($existing['exam_date'])) $alter[] = "ADD COLUMN exam_date DATE NOT NULL";
    if (!isset($existing['exam_time'])) $alter[] = "ADD COLUMN exam_time TIME NULL";
    if (!isset($existing['location'])) $alter[] = "ADD COLUMN location VARCHAR(255) NULL";
    if (!isset($existing['notes'])) $alter[] = "ADD COLUMN notes TEXT NULL";
    if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'scheduled'";
    if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($alter) {
      $pdo->exec('ALTER TABLE exams ' . implode(', ', $alter));
    }
  } catch (Throwable $e) {
    // Ignore schema drift errors in production.
  }
}

ensure_exams_schema($pdo);

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

    $sql = 'SELECT id, title, exam_date, exam_time, location, notes, status FROM exams WHERE ' . implode(' AND ', $where) . ' ORDER BY exam_date ASC, exam_time ASC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
  }

  if ($method === 'POST') {
    $title = trim((string)($input['title'] ?? ''));
    $exam_date = trim((string)($input['exam_date'] ?? ''));
    $exam_time = isset($input['exam_time']) ? trim((string)$input['exam_time']) : null;
    $location = isset($input['location']) ? trim((string)$input['location']) : null;
    $notes = isset($input['notes']) ? trim((string)$input['notes']) : null;

    if ($title === '' || $exam_date === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Missing title or exam_date']);
      exit;
    }

    $stmt = $pdo->prepare('INSERT INTO exams (user_id, title, exam_date, exam_time, location, notes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, $exam_date, ($exam_time !== '' ? $exam_time : null), ($location !== '' ? $location : null), ($notes !== '' ? $notes : null)]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, title, exam_date, exam_time, location, notes, status FROM exams WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper((string)$input['_method']) === 'PUT')) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    $stmt = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $title = trim((string)($input['title'] ?? ''));
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

    $stmt = $pdo->prepare('UPDATE exams SET title = ?, exam_date = ?, exam_time = ?, location = ?, notes = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $exam_date, ($exam_time !== '' ? $exam_time : null), ($location !== '' ? $location : null), ($notes !== '' ? $notes : null), ($status !== '' ? $status : 'scheduled'), $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, title, exam_date, exam_time, location, notes, status FROM exams WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
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
