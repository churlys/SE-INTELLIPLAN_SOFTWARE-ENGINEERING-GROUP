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

// API endpoints should return JSON errors (not HTML redirects).
if (!function_exists('is_logged_in') || !function_exists('current_user')) {
  http_response_code(500);
  echo json_encode(['error' => 'Auth helpers missing.']);
  exit;
}
if (!is_logged_in()) {
  http_response_code(401);
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

$user = current_user();
if (!$user || empty($user['id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid session']);
  exit;
}
$pdo = db();

// Create/upgrade table for local/dev setups (safe if already exists).
function ensure_tasks_schema(PDO $pdo): void {
  // Prefer broadly compatible MySQL/MariaDB DDL.
  // NOTE: Avoid DATETIME DEFAULT CURRENT_TIMESTAMP (not supported on some older MySQL setups).
  try {
    $pdo->exec(
      "CREATE TABLE IF NOT EXISTS tasks (\n" .
      "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
      "  user_id INT UNSIGNED NOT NULL,\n" .
      "  title VARCHAR(255) NOT NULL,\n" .
      "  details TEXT NULL,\n" .
      "  subject VARCHAR(100) NULL,\n" .
      "  due_date DATETIME NULL,\n" .
      "  due_time TIME NULL,\n" .
      "  file_id INT UNSIGNED NULL,\n" .
      "  status VARCHAR(20) NOT NULL DEFAULT 'open',\n" .
      "  completed_at DATETIME NULL,\n" .
      "  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
      "  PRIMARY KEY (id),\n" .
      "  KEY idx_tasks_user (user_id),\n" .
      "  KEY idx_tasks_due (due_date),\n" .
      "  KEY idx_tasks_due_time (due_time)\n" .
      ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
  } catch (PDOException $e) {
    // If the table already exists but the CREATE statement is incompatible with this DB version,
    // continue; subsequent queries will still work against the existing table.
  }

  // Best-effort column adds for older schemas.
  try {
    $existing = [];
    foreach ($pdo->query('SHOW COLUMNS FROM tasks') as $row) {
      $existing[strtolower((string)$row['Field'])] = true;
    }
    $alter = [];
    if (!isset($existing['details'])) $alter[] = "ADD COLUMN details TEXT NULL";
    if (!isset($existing['subject'])) $alter[] = "ADD COLUMN subject VARCHAR(100) NULL";
    if (!isset($existing['due_date'])) $alter[] = "ADD COLUMN due_date DATETIME NULL";
    if (!isset($existing['due_time'])) $alter[] = "ADD COLUMN due_time TIME NULL";
    if (!isset($existing['file_id'])) $alter[] = "ADD COLUMN file_id INT UNSIGNED NULL";
    if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'";
    if (!isset($existing['completed_at'])) $alter[] = "ADD COLUMN completed_at DATETIME NULL";
    if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($alter) {
      $pdo->exec('ALTER TABLE tasks ' . implode(', ', $alter));
    }
  } catch (Throwable $e) {
    // Ignore schema drift errors in production.
  }
}

// Create/upgrade files table for attachments (safe if already exists).
function ensure_files_schema(PDO $pdo): void {
  try {
    $pdo->exec(
      "CREATE TABLE IF NOT EXISTS files (\n" .
      "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
      "  user_id INT UNSIGNED NOT NULL,\n" .
      "  original_name VARCHAR(255) NOT NULL,\n" .
      "  stored_path VARCHAR(500) NOT NULL,\n" .
      "  mime_type VARCHAR(120) NULL,\n" .
      "  size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,\n" .
      "  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
      "  PRIMARY KEY (id),\n" .
      "  KEY idx_files_user (user_id)\n" .
      ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
  } catch (PDOException $e) {
    // Best-effort; see note in ensure_tasks_schema.
  }
}

ensure_tasks_schema($pdo);
ensure_files_schema($pdo);

// Auto-delete completed tasks after 24 hours.
function cleanup_completed_tasks(PDO $pdo, int $userId): void {
  try {
    // Backfill missing completed_at for existing done tasks so they get a 24h grace window.
    $stmt = $pdo->prepare("UPDATE tasks SET completed_at = CURRENT_TIMESTAMP WHERE user_id = ? AND status = 'done' AND completed_at IS NULL");
    $stmt->execute([$userId]);

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE user_id = ? AND status = 'done' AND completed_at < (NOW() - INTERVAL 1 DAY)");
    $stmt->execute([$userId]);
  } catch (Throwable $e) {
    // Best-effort; don't break API calls if cleanup fails.
  }
}

// Auto-delete overdue (not done) tasks after 24 hours past due.
function cleanup_overdue_tasks(PDO $pdo, int $userId): void {
  try {
    // If due_date is date-only (or stored at midnight), comparing to NOW()-1 day can delete
    // "yesterday" tasks too early. Use date-based cutoff so tasks remain visible in Overdue
    // for at least a full day.
    $stmt = $pdo->prepare(
      "DELETE FROM tasks WHERE user_id = ? AND status <> 'done' AND due_date IS NOT NULL AND DATE(due_date) < (CURDATE() - INTERVAL 1 DAY)"
    );
    $stmt->execute([$userId]);
  } catch (Throwable $e) {
    // Best-effort; don't break API calls if cleanup fails.
  }
}

cleanup_completed_tasks($pdo, (int)$user['id']);
cleanup_overdue_tasks($pdo, (int)$user['id']);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

function null_if_empty($value): ?string {
  if ($value === null) return null;
  $s = trim((string)$value);
  return $s === '' ? null : $s;
}

try {
  if ($method === 'GET') {
    // Optional filters: view=current|overdue|past|all and subject
    $view = strtolower(trim((string)($_GET['view'] ?? 'all')));
    $subject = trim((string)($_GET['subject'] ?? ''));

    $where = ['t.user_id = ?'];
    $params = [$user['id']];

    if ($subject !== '') {
      $where[] = 't.subject = ?';
      $params[] = $subject;
    }

    if ($view === 'past') {
      $where[] = "t.status = 'done'";
    } elseif ($view === 'overdue') {
      $where[] = "t.status <> 'done'";
      $where[] = '(t.due_date IS NOT NULL AND (DATE(t.due_date) < CURDATE() OR (DATE(t.due_date) = CURDATE() AND t.due_time IS NOT NULL AND t.due_time < CURTIME())))';
    } elseif ($view === 'current') {
      $where[] = "t.status <> 'done'";
      $where[] = '(t.due_date IS NULL OR DATE(t.due_date) > CURDATE() OR (DATE(t.due_date) = CURDATE() AND (t.due_time IS NULL OR t.due_time >= CURTIME())))';
    }

    // Order without relying on created_at (older schemas may not have it).
    $sql = 'SELECT t.id, t.title, t.details, t.subject, DATE(t.due_date) AS due_date, t.due_time, t.status, t.file_id, f.original_name AS file_name ' .
      'FROM tasks t ' .
      'LEFT JOIN files f ON f.id = t.file_id AND f.user_id = t.user_id ' .
      'WHERE ' . implode(' AND ', $where) . ' ORDER BY t.due_date IS NULL, t.due_date ASC, t.due_time IS NULL, t.due_time ASC, t.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as &$t) {
      $fid = (int)($t['file_id'] ?? 0);
      $t['file_id'] = $fid ?: null;
      $t['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
    }
    echo json_encode($tasks);
    exit;
  }

  if ($method === 'POST') {
    $title = trim((string)($input['title'] ?? ''));
    $details = null_if_empty($input['details'] ?? null);
    $subject = null_if_empty($input['subject'] ?? null);
    $due = null_if_empty($input['due_date'] ?? null);
    $dueTime = null_if_empty($input['due_time'] ?? null);
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Missing title']); exit; }

    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, details, subject, due_date, due_time) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, $details, $subject, $due, $dueTime]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT t.id, t.title, t.details, t.subject, DATE(t.due_date) AS due_date, t.due_time, t.status, t.file_id, f.original_name AS file_name FROM tasks t LEFT JOIN files f ON f.id = t.file_id AND f.user_id = t.user_id WHERE t.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    $fid = (int)($task['file_id'] ?? 0);
    $task['file_id'] = $fid ?: null;
    $task['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
    echo json_encode($task);
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'PUT')) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    // verify ownership
    $stmt = $pdo->prepare('SELECT id, status FROM tasks WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $title = trim((string)($input['title'] ?? ''));
    $details = null_if_empty($input['details'] ?? null);
    $subject = null_if_empty($input['subject'] ?? null);
    $due = null_if_empty($input['due_date'] ?? null);
    $dueTime = null_if_empty($input['due_time'] ?? null);
    $status = (string)($input['status'] ?? 'open');

    $prevStatus = strtolower((string)($existing['status'] ?? 'open'));
    $nextStatus = strtolower((string)$status);
    $setCompletionSql = '';
    if ($nextStatus === 'done' && $prevStatus !== 'done') {
      $setCompletionSql = ', completed_at = CURRENT_TIMESTAMP';
    } elseif ($nextStatus !== 'done' && $prevStatus === 'done') {
      $setCompletionSql = ', completed_at = NULL';
    }

    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, details = ?, subject = ?, due_date = ?, due_time = ?, status = ?' . $setCompletionSql . ' WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $details, $subject, $due, $dueTime, $status, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT t.id, t.title, t.details, t.subject, DATE(t.due_date) AS due_date, t.due_time, t.status, t.file_id, f.original_name AS file_name FROM tasks t LEFT JOIN files f ON f.id = t.file_id AND f.user_id = t.user_id WHERE t.id = ? LIMIT 1');
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    $fid = (int)($task['file_id'] ?? 0);
    $task['file_id'] = $fid ?: null;
    $task['file_url'] = $fid ? ('lib/file.php?id=' . $fid) : null;
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