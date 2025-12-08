<?php
// api/calendar.php
// JSON API for calendar events (GET, POST, PUT, DELETE).
// Requires session auth and lib/db.php, lib/auth.php with require_auth() and current_user().

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/db.php';
if (file_exists(__DIR__ . '/../lib/auth.php')) {
  require_once __DIR__ . '/../lib/auth.php';
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Auth library not found.']);
  exit;
}

require_auth();
$user = current_user();
$pdo = db();

$method = $_SERVER['REQUEST_METHOD'];

// Read input body for PUT/POST with JSON
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

try {
  if ($method === 'GET') {
    // List events for current user (optionally filter by start/end query params)
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    $sql = 'SELECT id, title, description, start, end, all_day FROM calendar_events WHERE user_id = :uid';
    $params = [':uid' => $user['id']];

    if ($start && $end) {
      $sql .= ' AND (start BETWEEN :start AND :end OR end BETWEEN :start AND :end OR (start <= :start AND end >= :end))';
      $params[':start'] = $start;
      $params[':end'] = $end;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format for FullCalendar: allDay boolean and iso strings
    foreach ($events as &$e) {
      $e['allDay'] = $e['all_day'] ? true : false;
      $e['start'] = date('c', strtotime($e['start']));
      $e['end'] = $e['end'] ? date('c', strtotime($e['end'])) : null;
    }

    echo json_encode($events);
    exit;
  }

  if ($method === 'POST') {
    // Create event
    $title = trim($input['title'] ?? '');
    $start = $input['start'] ?? null;
    $end = $input['end'] ?? null;
    $all_day = !empty($input['allDay']) ? 1 : 0;
    $desc = $input['description'] ?? null;

    if ($title === '' || !$start) {
      http_response_code(400);
      echo json_encode(['error' => 'Missing title or start']);
      exit;
    }

    $stmt = $pdo->prepare('INSERT INTO calendar_events (user_id, title, description, start, end, all_day) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, $desc, $start, $end, $all_day]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, title, description, start, end, all_day FROM calendar_events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $event['allDay'] = $event['all_day'] ? true : false;
    $event['start'] = date('c', strtotime($event['start']));
    $event['end'] = $event['end'] ? date('c', strtotime($event['end'])) : null;

    echo json_encode($event);
    exit;
  }

  if ($method === 'PUT' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'PUT')) {
    // Update event
    $id = (int)($input['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    // Ensure event belongs to user
    $stmt = $pdo->prepare('SELECT id FROM calendar_events WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$id, $user['id']]);
    if (!$stmt->fetch()) { http_response_code(403); echo json_encode(['error' => 'Not found']); exit; }

    $title = trim($input['title'] ?? '');
    $start = $input['start'] ?? null;
    $end = $input['end'] ?? null;
    $all_day = !empty($input['allDay']) ? 1 : 0;
    $desc = $input['description'] ?? null;

    $stmt = $pdo->prepare('UPDATE calendar_events SET title = ?, description = ?, start = ?, end = ?, all_day = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $desc, $start, $end, $all_day, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, title, description, start, end, all_day FROM calendar_events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    $event['allDay'] = $event['all_day'] ? true : false;
    $event['start'] = date('c', strtotime($event['start']));
    $event['end'] = $event['end'] ? date('c', strtotime($event['end'])) : null;

    echo json_encode($event);
    exit;
  }

  if ($method === 'DELETE' || ($method === 'POST' && isset($input['_method']) && strtoupper($input['_method']) === 'DELETE')) {
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }

    // Ensure event belongs to user
    $stmt = $pdo->prepare('DELETE FROM calendar_events WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    echo json_encode(['deleted' => $id]);
    exit;
  }

  // method not allowed
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
  exit;
}