<?php


function logEvent(PDO $pdo, ?int $user_id, string $email, string $activity, string $type = 'system'): void {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, email, activity, activity_type, ip_address)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $email, $activity, $type, $_SERVER['REMOTE_ADDR'] ?? 'CLI']);
}
