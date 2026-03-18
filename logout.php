<?php
// ============================================================
// BCMS — LOGOUT
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/activity_logger.php';

if (isLoggedIn()) {
    logActivity('User logged out', 'login');
}

session_unset();
session_destroy();

redirect(BASE_URL . '/index.php?msg=You+have+been+logged+out+successfully.');
