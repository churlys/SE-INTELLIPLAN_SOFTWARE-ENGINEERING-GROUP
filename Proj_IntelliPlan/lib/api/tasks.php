<?php
// api/tasks.php
// JSON API for tasks (GET list, POST create, PUT update, DELETE).
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

// Create/upgrade table for local/dev setups (safe if already exists).
function ensure_tasks_schema(PDO $pdo): void {
  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS tasks (\n" .
    "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
    "  user_id INT UNSIGNED NOT NULL,\n" .
    "  title VARCHAR(255) NOT NULL,\n" .
    "  details TEXT NULL,\n" .
    "  subject VARCHAR(100) NULL,\n" .
    "  due_date DATE NULL,\n" .
    "  due_time TIME NULL,\n" .
    "  status VARCHAR(20) NOT NULL DEFAULT 'open',\n" .
    "  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
    "  PRIMARY KEY (id),\n" .
    "  KEY idx_tasks_user (user_id),\n" .
    "  KEY idx_tasks_due (due_date),\n" .
    "  KEY idx_tasks_due_time (due_time)\n" .
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  );

  // Best-effort column adds for older schemas.
  try {
    $existing = [];
    foreach ($pdo->query('SHOW COLUMNS FROM tasks') as $row) {
      $existing[strtolower((string)$row['Field'])] = true;
    }
    $alter = [];
    if (!isset($existing['details'])) $alter[] = "ADD COLUMN details TEXT NULL";
    if (!isset($existing['subject'])) $alter[] = "ADD COLUMN subject VARCHAR(100) NULL";
    if (!isset($existing['due_date'])) $alter[] = "ADD COLUMN due_date DATE NULL";
    if (!isset($existing['due_time'])) $alter[] = "ADD COLUMN due_time TIME NULL";
    if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'";
    if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($alter) {
      $pdo->exec('ALTER TABLE tasks ' . implode(', ', $alter));
    }
  } catch (Throwable $e) {
    // Ignore schema drift errors in production.
  }
}

ensure_tasks_schema($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

try {
  if ($method === 'GET') {
    // Optional filters: view=current|overdue|past|all and subject
    $view = strtolower(trim((string)($_GET['view'] ?? 'all')));
    $subject = trim((string)($_GET['subject'] ?? ''));

    $where = ['user_id = ?'];
    $params = [$user['id']];

    if ($subject !== '') {
      $where[] = 'subject = ?';
      $params[] = $subject;
    }

    if ($view === 'past') {
      $where[] = "status = 'done'";
    } elseif ($view === 'overdue') {
      $where[] = "status <> 'done'";
      $where[] = 'due_date IS NOT NULL AND DATE(due_date) < CURDATE()';
    } elseif ($view === 'current') {
      $where[] = "status <> 'done'";
      $where[] = '(due_date IS NULL OR DATE(due_date) >= CURDATE())';
    }

    // Order without relying on created_at (older schemas may not have it).
    $sql = 'SELECT id, title, details, subject, DATE(due_date) AS due_date, due_time, status FROM tasks WHERE ' . implode(' AND ', $where) . ' ORDER BY due_date IS NULL, due_date ASC, due_time IS NULL, due_time ASC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
    exit;
  }

  if ($method === 'POST') {
    $title = trim($input['title'] ?? '');
    $details = $input['details'] ?? null;
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $due = $input['due_date'] ?? null;
    $dueTime = $input['due_time'] ?? null;
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Missing title']); exit; }

    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, details, subject, due_date, due_time) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, $details, $subject, $due, $dueTime]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, title, details, subject, DATE(due_date) AS due_date, due_time, status FROM tasks WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($task);
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'PUT')) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    // verify ownership
    $stmt = $pdo->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $title = trim($input['title'] ?? '');
    $details = $input['details'] ?? null;
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $due = $input['due_date'] ?? null;
    $dueTime = $input['due_time'] ?? null;
    $status = $input['status'] ?? 'open';

    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, details = ?, subject = ?, due_date = ?, due_time = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $details, $subject, $due, $dueTime, $status, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, title, details, subject, DATE(due_date) AS due_date, due_time, status FROM tasks WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($task);
    exit;
  }

  if ($method === 'DELETE' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'DELETE')) {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
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