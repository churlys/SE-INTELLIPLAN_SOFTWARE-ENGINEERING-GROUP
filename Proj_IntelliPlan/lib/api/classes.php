<?php
// api/classes.php
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
}

ensure_classes_schema($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

try {
  if ($method === 'GET') {
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
    $classes = [];
    foreach ($rows as $r) {
      // Normalize starts_at to an ISO 8601 UTC string if present
      if (!empty($r['starts_at'])) {
        try {
          $dt = new DateTime($r['starts_at'], new DateTimeZone('UTC'));
          $r['starts_at'] = $dt->format(DateTime::ATOM);
        } catch (Throwable $e) {
          // leave as-is
        }
      } else {
        $r['starts_at'] = null;
      }
      $r['subject'] = isset($r['subject']) ? $r['subject'] : null;
      $r['days'] = isset($r['days']) ? $r['days'] : null;
      $r['professor'] = isset($r['professor']) ? $r['professor'] : null;
      $classes[] = $r;
    }
    echo json_encode($classes);
    exit;
  }

  if ($method === 'POST') {
    $name = trim($input['name'] ?? '');
    $subject = isset($input['subject']) ? trim((string)$input['subject']) : null;
    $time = isset($input['time']) ? trim((string)$input['time']) : null;
    $start_time = isset($input['start_time']) ? trim((string)$input['start_time']) : null;
    $end_time = isset($input['end_time']) ? trim((string)$input['end_time']) : null;
    if ($start_time && $end_time) {
      $time = $start_time . ' - ' . $end_time;
    }
    $timezone = isset($input['timezone']) ? trim((string)$input['timezone']) : null;
    $days = null;
    if (isset($input['days'])) {
      if (is_array($input['days'])) $days = implode(',', array_map('trim', $input['days']));
      else $days = trim((string)$input['days']);
      if ($days === '') $days = null;
    }
    $professor = isset($input['professor']) ? trim((string)$input['professor']) : null;
    $starts_at = null;
    if ($time) {
      if ($timezone) {
        try {
          $dt = DateTime::createFromFormat('Y-m-d\TH:i', $time, new DateTimeZone($timezone));
          if ($dt !== false) {
            $dt->setTimezone(new DateTimeZone('UTC'));
            $starts_at = $dt->format('Y-m-d H:i:s');
          }
        } catch (Throwable $e) {
          // ignore and fallback
        }
      }
      if ($starts_at === null) {
        try {
          $dt = new DateTime($time);
          $dt->setTimezone(new DateTimeZone('UTC'));
          $starts_at = $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
          $starts_at = null;
        }
      }
    }
    if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Missing name']); exit; }

    $stmt = $pdo->prepare('INSERT INTO classes (user_id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $name, $subject, $time, $start_time, $end_time, $starts_at, $timezone, $days, $professor]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($class['starts_at'])) {
      try { $dt = new DateTime($class['starts_at'], new DateTimeZone('UTC')); $class['starts_at'] = $dt->format(DateTime::ATOM); } catch (Throwable $e) {}
    } else {
      $class['starts_at'] = null;
    }
    $class['subject'] = isset($class['subject']) ? $class['subject'] : null;
    $class['days'] = isset($class['days']) ? $class['days'] : null;
    $class['professor'] = isset($class['professor']) ? $class['professor'] : null;
    echo json_encode($class);
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'PUT')) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    // verify ownership
    $stmt = $pdo->prepare('SELECT id FROM classes WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $name = trim($input['name'] ?? '');
    $time = isset($input['time']) ? trim((string)$input['time']) : null;
    $start_time = isset($input['start_time']) ? trim((string)$input['start_time']) : null;
    $end_time = isset($input['end_time']) ? trim((string)$input['end_time']) : null;
    if ($start_time && $end_time) { $time = $start_time . ' - ' . $end_time; }
    $timezone = isset($input['timezone']) ? trim((string)$input['timezone']) : null;
    $status = $input['status'] ?? 'active';
    $days = null;
    if (isset($input['days'])) { if (is_array($input['days'])) $days = implode(',', array_map('trim', $input['days'])); else $days = trim((string)$input['days']); if ($days === '') $days = null; }
    $professor = isset($input['professor']) ? trim((string)$input['professor']) : null;

    $starts_at = null;
    if ($time) {
      if ($timezone) {
        try {
          $dt = DateTime::createFromFormat('Y-m-d\TH:i', $time, new DateTimeZone($timezone));
          if ($dt !== false) { $dt->setTimezone(new DateTimeZone('UTC')); $starts_at = $dt->format('Y-m-d H:i:s'); }
        } catch (Throwable $e) {}
      }
      if ($starts_at === null) {
        try { $dt = new DateTime($time); $dt->setTimezone(new DateTimeZone('UTC')); $starts_at = $dt->format('Y-m-d H:i:s'); } catch (Throwable $e) { $starts_at = null; }
      }
    }

    $stmt = $pdo->prepare('UPDATE classes SET name = ?, subject = ?, time = ?, start_time = ?, end_time = ?, starts_at = ?, timezone = ?, days = ?, professor = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$name, $subject, $time, $start_time, $end_time, $starts_at, $timezone, $days, $professor, $status, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, name, subject, time, start_time, end_time, starts_at, timezone, days, professor, status FROM classes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($class['starts_at'])) {
      try { $dt = new DateTime($class['starts_at'], new DateTimeZone('UTC')); $class['starts_at'] = $dt->format(DateTime::ATOM); } catch (Throwable $e) {}
    } else {
      $class['starts_at'] = null;
    }
    $class['subject'] = isset($class['subject']) ? $class['subject'] : null;
    $class['days'] = isset($class['days']) ? $class['days'] : null;
    if (!empty($class['starts_at'])) {
      try { $dt = new DateTime($class['starts_at'], new DateTimeZone('UTC')); $class['starts_at'] = $dt->format(DateTime::ATOM); } catch (Throwable $e) {}
    } else {
      $class['starts_at'] = null;
    }
    $class['days'] = isset($class['days']) ? $class['days'] : null;
    $class['professor'] = isset($class['professor']) ? $class['professor'] : null;
    echo json_encode($class);
    exit;
  }

  if ($method === 'DELETE' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'DELETE')) {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ? AND user_id = ?');
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
