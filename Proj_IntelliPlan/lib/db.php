<?php
// lib/db.php - MySQL connection (PDO).
// Update constants or set environment variables for production.
//
// Recommended: set env vars INTELLIPLAN_DB_DSN, INTELLIPLAN_DB_USER, INTELLIPLAN_DB_PASS
// Example DSN: mysql:host=127.0.0.1;port=3306;dbname=student_prod;charset=utf8mb4

declare(strict_types=1);

$envDsn  = getenv('INTELLIPLAN_DB_DSN') ?: null;
$envUser = getenv('INTELLIPLAN_DB_USER') ?: null;
$envPass = getenv('INTELLIPLAN_DB_PASS') ?: null;

if ($envDsn && $envUser !== false) {
    define('DB_DSN', $envDsn);
    define('DB_USER', $envUser);
    define('DB_PASS', $envPass);
} else {
    // Local defaults - edit to match your local MySQL credentials
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'student_prod'); // make sure this matches the DB you create
    define('DB_USER', 'root');
    define('DB_PASS', 'root'); // set your MySQL password here
    define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4');
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // In development you can echo the error; in production log it instead.
        http_response_code(500);
        echo "Database connection failed: " . htmlspecialchars($e->getMessage());
        exit;
    }
}