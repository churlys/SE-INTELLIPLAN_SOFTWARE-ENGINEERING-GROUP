<?php


declare(strict_types=1);

$envDsn  = getenv('INTELLIPLAN_DB_DSN') ?: null;
$envUser = getenv('INTELLIPLAN_DB_USER') ?: null;
$envPass = getenv('INTELLIPLAN_DB_PASS') ?: null;

if ($envDsn && $envUser !== false) {
    define('DB_DSN', $envDsn);
    define('DB_USER', $envUser);
    define('DB_PASS', $envPass);
} else {
    
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'student_prod'); 
    define('DB_USER', 'root');
    define('DB_PASS', 'root'); 
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

    $isApiRequest = function (): bool {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($uri !== '' && strpos($uri, '/lib/api/') !== false) return true;
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        return stripos($accept, 'application/json') !== false;
    };

    $renderDbError = function (PDOException $e) use ($isApiRequest): void {
        http_response_code(500);
        $msg = 'Database connection failed: ' . $e->getMessage();
        if ($isApiRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error' => 'Database connection failed',
                'detail' => $e->getMessage(),
            ]);
        } else {
            echo "Database connection failed: " . htmlspecialchars($e->getMessage());
        }
        exit;
    };

    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Common local setup: MySQL root has an empty password.
        $isDefaultRootRoot = (defined('DB_USER') && DB_USER === 'root' && defined('DB_PASS') && DB_PASS === 'root');
        if ($isDefaultRootRoot) {
            try {
                $pdo = new PDO(DB_DSN, DB_USER, '', $options);
                return $pdo;
            } catch (PDOException $e2) {
                $renderDbError($e2);
            }
        }
        $renderDbError($e);
    }

    // Unreachable (all non-success paths exit), but keeps analyzers happy.
    throw new RuntimeException('Database connection unavailable');
}