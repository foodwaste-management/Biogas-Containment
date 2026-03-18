<?php
// ============================================================
// BCMS — ADMIN: ACTIVITY LOG
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('admin');

$db   = getDB();
$logs = getRecentActivity(200);

$pageTitle = 'Activity Log';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📝 Activity Log</div>
      <div class="topbar-meta">
        <span class="text-muted text-sm">Last <?= count($logs) ?> entries</span>
      </div>
    </div>

    <div class="page-body">
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">All System Activity</span>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>#</th><th>User</th><th>Activity</th><th>Type</th><th>IP Address</th><th>Time</th></tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                  <td class="mono text-sm text-muted"><?= $l['id'] ?></td>
                  <td class="text-sm"><?= e($l['email'] ?? 'System') ?></td>
                  <td class="text-sm"><?= e($l['activity']) ?></td>
                  <td><span class="badge badge-<?= $l['activity_type'] ?>"><?= $l['activity_type'] ?></span></td>
                  <td class="mono text-sm text-muted"><?= e($l['ip_address'] ?? '—') ?></td>
                  <td class="text-sm text-muted"><?= $l['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No activity logged yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php renderFoot(); ?>
