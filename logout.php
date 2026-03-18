<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'User logged out', 'logout');
}

session_unset();
session_destroy();
header('Location: /index.php');
exit;