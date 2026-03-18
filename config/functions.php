<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

function logActivity($pdo, $user_id, $email, $activity, $type) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, email, activity, activity_type, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $activity, $type, $_SERVER['REMOTE_ADDR']]);
}