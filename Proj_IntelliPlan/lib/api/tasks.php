<?php
// api/tasks.php
// JSON API for tasks (GET list, POST create, PUT update, DELETE).
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
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

try {
  if ($method === 'GET') {
    // Return tasks for user
    $stmt = $pdo->prepare('SELECT id, title, details, due_date, status FROM tasks WHERE user_id = ? ORDER BY due_date IS NULL, due_date ASC, created_at DESC');
    $stmt->execute([$user['id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($tasks);
    exit;
  }

  if ($method === 'POST') {
    $title = trim($input['title'] ?? '');
    $details = $input['details'] ?? null;
    $due = $input['due_date'] ?? null;
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Missing title']); exit; }

    $stmt = $pdo->prepare('INSERT INTO tasks (user_id, title, details, due_date) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user['id'], $title, $details, $due]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('SELECT id, title, details, due_date, status FROM tasks WHERE id = ? LIMIT 1');
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
    $due = $input['due_date'] ?? null;
    $status = $input['status'] ?? 'open';

    $stmt = $pdo->prepare('UPDATE tasks SET title = ?, details = ?, due_date = ?, status = ? WHERE id = ? AND user_id = ?');
    $stmt->execute([$title, $details, $due, $status, $id, $user['id']]);

    $stmt = $pdo->prepare('SELECT id, title, details, due_date, status FROM tasks WHERE id = ? LIMIT 1');
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