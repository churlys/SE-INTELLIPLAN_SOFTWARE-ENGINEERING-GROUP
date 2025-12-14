<?php
// lib/auth.php - authentication helpers used by the app (register/login/session/CSRF)
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

/* CSRF helpers */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}
function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

/* User helpers */
function get_user_by_email(string $email): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function register_user(string $name, string $email, string $password): ?int {
    $pdo = db();
    $pwHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    try {
        $stmt->execute([$name, $email, $pwHash]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        // duplicate email or other error -> return null
        return null;
    }
}

function login_user(int $user_id): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
}

function logout_user(): void {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function require_auth(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
