<?php
// logout.php
// Logs the current user out and redirects to the index (landing) page.
// Uses logout_user() and verify_csrf_token() from lib/auth.php.
//
// Place this file in your project root (same folder as index.php, login.php, dashboard.php).

session_start();

require_once __DIR__ . '/lib/auth.php';

// POST (recommended) with CSRF token
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

// GET fallback for convenience (development). It also redirects to index.php.
// Remove this block if you want logout to be POST-only.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    logout_user();
    header('Location: index.php');
    exit;
}