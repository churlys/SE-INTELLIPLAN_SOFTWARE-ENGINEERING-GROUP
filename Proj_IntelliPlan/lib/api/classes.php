<?php

// JSON API for classes (GET list, POST create, PUT update, DELETE).
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

function send_json(mixed $data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

function send_error(string $message, int $status = 400, array $extra = []): void {
  send_json(array_merge(['error' => $message], $extra), $status);
}

function normalize_class_row(array $row): array {
  // Normalize starts_at to an ISO 8601 UTC string if present.
  if (!empty($row['starts_at'])) {
    try {
      $dt = new DateTime((string)$row['starts_at'], new DateTimeZone('UTC'));
      $row['starts_at'] = $dt->format(DateTime::ATOM);
    } catch (Throwable $e) {
      // leave as-is
    }
  } else {
    $row['starts_at'] = null;
  }

  foreach (['subject', 'time', 'start_time', 'end_time', 'timezone', 'days', 'professor', 'status'] as $k) {
    if (!array_key_exists($k, $row)) $row[$k] = null;
  }

  return $row;
}

function parse_days(mixed $daysInput): ?string {
  if ($daysInput === null) return null;
  if (is_array($daysInput)) {
    $days = implode(',', array_map(static fn($d) => trim((string)$d), $daysInput));
  } else {
    $days = trim((string)$daysInput);
  }
  return $days === '' ? null : $days;
}

function parse_starts_at(?string $time, ?string $timezone): ?string {
  if ($time === null) return null;
  $time = trim($time);
  if ($time === '') return null;

  // Only parse if it looks like an ISO-ish datetime (avoid parsing ranges like "10:00 - 11:00").
  if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $time)) {
    return null;
  }

  if ($timezone) {
    try {
      $dt = DateTime::createFromFormat('Y-m-d\TH:i', $time, new DateTimeZone($timezone));
      if ($dt !== false) {
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
      }
    } catch (Throwable $e) {
      // fall through
    }
  }

  try {
    $dt = new DateTime($time);
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
  } catch (Throwable $e) {
    return null;
  }
}

function constraint_exists(PDO $pdo, string $table, string $constraintName): bool {
  $stmt = $pdo->prepare(
    'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1'
  );
  $stmt->execute([$table, $constraintName]);
  return (bool)$stmt->fetchColumn();
}

function add_fk_if_missing(PDO $pdo, string $table, string $constraintName, string $sql): void {
  try {
    if (constraint_exists($pdo, $table, $constraintName)) return;
    $pdo->exec($sql);
  } catch (Throwable $e) {
    // Best-effort; ignore if cannot be applied (e.g., existing orphan rows).
  }
}

// Create/upgrade table for local/dev setups (safe if already exists).
function ensure_classes_schema(PDO $pdo): void {
  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS classes (\n" .
    "  id INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
    "  user_id INT UNSIGNED NOT NULL,\n" .
    "  name VARCHAR(255) NOT NULL,\n" .
    "  subject VARCHAR(100) NULL,\n" .
    "  time VARCHAR(100) NULL,\n" .
    "  start_time TIME NULL,\n" .
    "  end_time TIME NULL,\n" .
    "  starts_at DATETIME NULL,\n" .
    "  timezone VARCHAR(100) NULL,\n" .
    "  days VARCHAR(100) NULL,\n" .
    "  professor VARCHAR(255) NULL,\n" .
    "  status VARCHAR(20) NOT NULL DEFAULT 'active',\n" .
    "  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
    "  PRIMARY KEY (id),\n" .
    "  KEY idx_classes_user (user_id)\n" .
    ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  );

  // Best-effort column adds for older schemas.
  try {
    $existing = [];
    foreach ($pdo->query('SHOW COLUMNS FROM classes') as $row) {
      $existing[strtolower((string)$row['Field'])] = true;
    }
    $alter = [];
    if (!isset($existing['name'])) $alter[] = "ADD COLUMN name VARCHAR(255) NOT NULL";
    if (!isset($existing['subject'])) $alter[] = "ADD COLUMN subject VARCHAR(100) NULL";
    if (!isset($existing['time'])) $alter[] = "ADD COLUMN time VARCHAR(100) NULL";
    if (!isset($existing['start_time'])) $alter[] = "ADD COLUMN start_time TIME NULL";
    if (!isset($existing['end_time'])) $alter[] = "ADD COLUMN end_time TIME NULL";
    if (!isset($existing['starts_at'])) $alter[] = "ADD COLUMN starts_at DATETIME NULL";
    if (!isset($existing['timezone'])) $alter[] = "ADD COLUMN timezone VARCHAR(100) NULL";
    if (!isset($existing['days'])) $alter[] = "ADD COLUMN days VARCHAR(100) NULL";
    if (!isset($existing['professor'])) $alter[] = "ADD COLUMN professor VARCHAR(255) NULL";
    if (!isset($existing['status'])) $alter[] = "ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'";
    if (!isset($existing['created_at'])) $alter[] = "ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    if ($alter) {
      $pdo->exec('ALTER TABLE classes ' . implode(', ', $alter));
    }
  } catch (Throwable $e) {
    // Ignore schema drift errors in production.
  }

  add_fk_if_missing(
    $pdo,
    'classes',
    'fk_classes_user_id',
    'ALTER TABLE classes ADD CONSTRAINT fk_classes_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE'
  );
}

ensure_classes_schema($pdo);

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$inputRaw = file_get_contents('php://input');
$jsonBody = json_decode($inputRaw ?: '', true);
$input = is_array($jsonBody) ? $jsonBody : ($_POST ?? []);

$effectiveMethod = $method;
if ($method === 'POST' && isset($input['_method'])) {
  $override = strtoupper(trim((string)$input['_method']));
  if (in_array($override, ['PUT', 'DELETE'], true)) $effectiveMethod = $override;
}

try {
  if ($effectiveMethod === 'GET') {
    // Optional filters: view=current|past|all and subject
    $view = strtolower(trim((string)($_GET['view'] ?? 'all')));
    $subject = trim((string)($_GET['subject'] ?? ''));

    $where = ['user_id = ?'];
    $params = [$user['id']];

    if ($subject !== '') {
      $where[] = 'subject = ?';
      $params[] = $subject;
    }

    if ($view === 'past') {
      $where[] = "status = 'archived'";
    } elseif ($view === 'current') {
      $where[] = "status <> 'archived'";
    }

    $sql = 'SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $classes = array_map('normalize_class_row', $rows);
    send_json($classes);
  }

  if ($effectiveMethod === 'POST') {
    $name = trim($input['name'] ?? '');
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $time = isset($input['time']) ? trim((string)$input['time']) : null;
    $start_time = isset($input['start_time']) ? trim((string)$input['start_time']) : null;
    $end_time = isset($input['end_time']) ? trim((string)$input['end_time']) : null;
    if ($start_time && $end_time) {
      $time = $start_time . ' - ' . $end_time;
    }
    $timezone = isset($input['timezone']) ? trim((string)$input['timezone']) : null;
    $days = array_key_exists('days', $input) ? parse_days($input['days']) : null;
    $professor = isset($input['professor']) ? trim((string)$input['professor']) : null;
    $starts_at = parse_starts_at($time, $timezone);
    if ($name === '') send_error('Missing name', 400);

    $stmt = $pdo->prepare('INSERT INTO classes (user_id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $name, $subject, $time, $start_time, $end_time, $starts_at, $timezone, $days, $professor]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    send_json(normalize_class_row($class ?: []));
  }

  if ($effectiveMethod === 'PUT') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) send_error('Missing id', 400);

    // verify ownership
    $stmt = $pdo->prepare('SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) send_error('Not found', 404);

    $name = array_key_exists('name', $input) ? trim((string)$input['name']) : (string)$existing['name'];
    if ($name === '') $name = (string)$existing['name'];

    $subject = array_key_exists('subject', $input) ? trim((string)$input['subject']) : (string)($existing['subject'] ?? '');
    if ($subject === '') $subject = null;

    $time = array_key_exists('time', $input) ? trim((string)$input['time']) : ($existing['time'] ?? null);
    $start_time = array_key_exists('start_time', $input) ? trim((string)$input['start_time']) : ($existing['start_time'] ?? null);
    $end_time = array_key_exists('end_time', $input) ? trim((string)$input['end_time']) : ($existing['end_time'] ?? null);
    if ($start_time && $end_time) $time = $start_time . ' - ' . $end_time;

    $timezone = array_key_exists('timezone', $input) ? trim((string)$input['timezone']) : ($existing['timezone'] ?? null);

    $status = array_key_exists('status', $input) ? (string)$input['status'] : (string)($existing['status'] ?? 'active');
    $status = strtolower(trim($status));
    if (!in_array($status, ['active', 'archived'], true)) $status = (string)($existing['status'] ?? 'active');

    $days = array_key_exists('days', $input) ? parse_days($input['days']) : ($existing['days'] ?? null);
    $professor = array_key_exists('professor', $input) ? trim((string)$input['professor']) : ($existing['professor'] ?? null);
    if ($professor === '') $professor = null;

    $starts_at = parse_starts_at(is_string($time) ? $time : null, is_string($timezone) ? $timezone : null);
    if ($starts_at === null) $starts_at = $existing['starts_at'] ?? null;

    $stmt = $pdo->prepare('UPDATE classes SET name = ?, subject = ?, time = ?, start_time = ?, end_time = ?, starts_at = ?, timezone = ?, days = ?, professor = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$name, $subject, $time, $start_time, $end_time, $starts_at, $timezone, $days, $professor, $status, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    send_json(normalize_class_row($class ?: []));
  }

  if ($effectiveMethod === 'DELETE') {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) send_error('Missing id', 400);

    $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    send_json(['deleted' => $id]);
  }

  send_error('Method not allowed', 405);
} catch (PDOException $e) {
  $debug = getenv('INTELLIPLAN_DEBUG');
  $payload = ['error' => 'Database error'];
  if ($debug && $debug !== '0') $payload['detail'] = $e->getMessage();
  send_json($payload, 500);
}
