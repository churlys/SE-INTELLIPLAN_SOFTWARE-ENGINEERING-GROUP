<?php
if (session_status() === PHP_SESSION_NONE) {
    $sessionDir = sys_get_temp_dir();
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }
    session_save_path($sessionDir);
    session_start();
}

require_once __DIR__ . '/lib/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(400);
        echo 'Invalid request.';
        exit;
    }

    logout_user();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logout_user();
    header('Location: index.php');
    exit;
}