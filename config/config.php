<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Database (Hostinger) ───────────────────────────────
define('DB_HOST', 'auth-db1981.hstgr.io');
define('DB_NAME', 'u442411629_bcmsdb');
define('DB_USER', 'u442411629_dev_bcmsdb');
define('DB_PASS', '4Pf2"3k7p8Nd');
define('BASE_URL', '');  

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}