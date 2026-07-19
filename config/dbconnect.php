<?php
// Central PDO connection and session bootstrap for Skill Map.
// Every page/include pulls in this file, then reuses the shared PDO
// handle with prepared statements for all queries.

// Start a session as early as possible so pages can rely on $_SESSION.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Local XAMPP defaults: root with no password on localhost.
// Override via environment variables when deploying elsewhere.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'skill_map_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Return a shared PDO connection to the Skill Map database.
 *
 * The handle is created once per request and reused on subsequent calls.
 * On failure it fails gracefully without leaking credentials.
 */
function skillmap_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Log the real error server-side; never expose credentials or the DSN.
        error_log('Skill Map DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database connection error. Please try again later.');
    }

    return $pdo;
}

// Shared handle for files that expect a ready-to-use $pdo variable.
$pdo = skillmap_db();
