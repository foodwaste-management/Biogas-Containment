<?php
// ============================================================
// BIOGAS CONTAINMENT MONITORING SYSTEM — CONFIG
// Hosted via FTP: ftp.ics-dev.io | phpMyAdmin DB
// ============================================================

// Session bootstrap

//  mark - I removed the Fail connection indicator
//
//
//
//
//
//
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


define('BASE_URL', '/Biogas-Containment');


// Production Credentials
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'u442411629_bcms');
// define('DB_USER', 'u442411629_dev_bcms');
// define('DB_PASS', '4Pf2"3k7p8Nd');

// Local XAMPP Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'u442411629_bcms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ── App Meta ─────────────────────────────────────────────────
define('APP_NAME', 'Biogas Containment Monitoring System');
define('APP_SHORT', 'BCMS');
define('APP_VERSION', '1.0.0');

// ── Methane Thresholds (ppm) ──────────────────────────────────
define('METHANE_SAFE', 1000);   // below = SAFE
define('METHANE_WARNING', 5000);   // below = WARNING, above = LEAK

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── PDO Connection ────────────────────────────────────────────
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show friendly error; never expose credentials
            die('<div style="font-family:monospace;color:#c0392b;padding:20px;">
                 <strong>Database Connection Failed.</strong><br>
                 Please contact your system administrator.<br><br>
                 <small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>
                 </div>');
        }
    }
    return $pdo;
}
