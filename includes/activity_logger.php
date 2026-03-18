<?php
// ============================================================
// BCMS — ACTIVITY LOGGER
// Logs user actions to the activity_logs table
// ============================================================

/**
 * Log an activity to the database.
 *
 * @param string      $activity     Human-readable description  e.g. "User logged in"
 * @param string      $type         One of: login | sensor | system | admin
 * @param int|null    $userId       Override user ID (defaults to session user)
 * @param string|null $email        Override email  (defaults to session email)
 */
function logActivity(string $activity, string $type = 'system', ?int $userId = null, ?string $email = null): void {
    try {
        $db = getDB();

        $uid   = $userId ?? ($_SESSION['user_id'] ?? null);
        $email = $email  ?? ($_SESSION['email']   ?? null);
        $ip    = $_SERVER['HTTP_X_FORWARDED_FOR']
                 ?? $_SERVER['REMOTE_ADDR']
                 ?? '0.0.0.0';

        // Sanitize type
        $validTypes = ['login', 'sensor', 'system', 'admin'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'system';
        }

        $stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, email, activity, activity_type, ip_address, created_at)
            VALUES (:uid, :email, :activity, :type, :ip, NOW())
        ");
        $stmt->execute([
            ':uid'      => $uid,
            ':email'    => $email,
            ':activity' => $activity,
            ':type'     => $type,
            ':ip'       => substr($ip, 0, 50),
        ]);
    } catch (PDOException $e) {
        // Fail silently — logging should never crash the app
        error_log('[BCMS ActivityLogger] ' . $e->getMessage());
    }
}

/**
 * Fetch recent activity logs.
 *
 * @param int $limit   Number of rows to return
 * @param int $userId  If set, filter by user
 */
function getRecentActivity(int $limit = 50, ?int $userId = null): array {
    try {
        $db = getDB();
        if ($userId !== null) {
            $stmt = $db->prepare("
                SELECT l.*, u.email as user_email
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.user_id
                WHERE l.user_id = :uid
                ORDER BY l.created_at DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        } else {
            $stmt = $db->prepare("
                SELECT l.*, u.email as user_email
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.user_id
                ORDER BY l.created_at DESC
                LIMIT :lim
            ");
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[BCMS ActivityLogger] getRecentActivity: ' . $e->getMessage());
        return [];
    }
}
