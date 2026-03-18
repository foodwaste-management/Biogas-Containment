<?php
// ============================================================
// BCMS — ADMIN DASHBOARD
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('admin');

$db = getDB();

$stats = $db->query("SELECT
  (SELECT COUNT(*) FROM users)                                     AS total_users,
  (SELECT COUNT(*) FROM users WHERE role='manager')               AS managers,
  (SELECT COUNT(*) FROM users WHERE role='user')                  AS regular_users,
  (SELECT COUNT(*) FROM methane_monitoring WHERE status='LEAK')   AS leaks,
  (SELECT COUNT(*) FROM methane_monitoring WHERE status='WARNING')AS warnings,
  (SELECT COUNT(*) FROM gas_usage)                                AS gas_logs,
  (SELECT COUNT(*) FROM gas_level)                                AS level_logs,
  (SELECT COUNT(*) FROM methane_monitoring)                       AS meth_logs,
  (SELECT COUNT(*) FROM activity_logs)                            AS total_logs
")->fetch();

$recentLogs = getRecentActivity(8);
$pageTitle = 'Admin Dashboard';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">🛡️ Admin Dashboard</div>
      <div class="topbar-meta">
        <span class="topbar-time" id="clock"></span>
        <?php if ($stats['leaks'] > 0): ?>
        <span class="badge badge-leak">🚨 <?= $stats['leaks'] ?> Leak(s)</span>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/app/admin/users.php" class="btn btn-primary btn-sm">Manage Users</a>
      </div>
    </div>

    <div class="page-body">

      <!-- Banner -->
      <div class="panel" style="background:linear-gradient(135deg,var(--green-900),var(--green-800));border:none;margin-bottom:24px;">
        <div class="panel-body flex items-center justify-between" style="flex-wrap:wrap;gap:16px;">
          <div>
            <p style="color:var(--green-300);font-size:.75rem;font-family:var(--font-mono);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px;">System Control</p>
            <h2 style="font-family:var(--font-serif);font-size:1.6rem;color:#fff;margin-bottom:6px;">Biogas Monitoring — Admin Panel</h2>
            <p style="color:rgba(255,255,255,.65);font-size:.875rem;">Full access to users, sensor data, and system logs.</p>
          </div>
          <div style="font-size:3.5rem;">🛡️</div>
        </div>
      </div>

      <!-- Stats row 1: Users -->
      <div class="stats-grid stagger">
        <div class="stat-card fade-up">
          <span class="stat-icon">👥</span>
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?= $stats['total_users'] ?></div>
          <div class="stat-sub"><?= $stats['managers'] ?> managers · <?= $stats['regular_users'] ?> users</div>
        </div>
        <div class="stat-card fade-up <?= $stats['leaks']>0?'danger':'' ?>">
          <span class="stat-icon">🚨</span>
          <div class="stat-label">Leak Events</div>
          <div class="stat-value"><?= $stats['leaks'] ?></div>
          <div class="stat-sub">total recorded leaks</div>
        </div>
        <div class="stat-card fade-up <?= $stats['warnings']>0?'warn':'' ?>">
          <span class="stat-icon">⚠️</span>
          <div class="stat-label">Warnings</div>
          <div class="stat-value"><?= $stats['warnings'] ?></div>
          <div class="stat-sub">methane warning events</div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">📋</span>
          <div class="stat-label">Total Logs</div>
          <div class="stat-value"><?= $stats['total_logs'] ?></div>
          <div class="stat-sub">activity log entries</div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">📊</span>
          <div class="stat-label">Gas Usage Entries</div>
          <div class="stat-value"><?= $stats['gas_logs'] ?></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">🔬</span>
          <div class="stat-label">Methane Records</div>
          <div class="stat-value"><?= $stats['meth_logs'] ?></div>
        </div>
      </div>

      <!-- Quick links -->
      <div class="panel fade-up">
        <div class="panel-header"><span class="panel-title">⚡ Quick Actions</span></div>
        <div class="panel-body flex gap-2" style="flex-wrap:wrap;">
          <a href="<?= BASE_URL ?>/app/admin/users.php"       class="btn btn-primary">👥 Manage Users</a>
          <a href="<?= BASE_URL ?>/app/admin/gas_usage.php"   class="btn btn-secondary">📊 Gas Usage</a>
          <a href="<?= BASE_URL ?>/app/admin/methane.php"     class="btn btn-secondary">⚠️ Methane</a>
          <a href="<?= BASE_URL ?>/app/admin/gas_level.php"   class="btn btn-secondary">🫙 Gas Level</a>
          <a href="<?= BASE_URL ?>/app/admin/activity_log.php"class="btn btn-secondary">📝 Activity Log</a>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">📝 Recent Activity</span>
          <a href="<?= BASE_URL ?>/app/admin/activity_log.php" class="btn btn-secondary btn-sm">View All →</a>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead><tr><th>User</th><th>Activity</th><th>Type</th><th>Time</th></tr></thead>
              <tbody>
                <?php foreach ($recentLogs as $l): ?>
                <tr>
                  <td class="text-sm"><?= e($l['email']??'System') ?></td>
                  <td class="text-sm"><?= e($l['activity']) ?></td>
                  <td><span class="badge badge-<?= $l['activity_type'] ?>"><?= $l['activity_type'] ?></span></td>
                  <td class="text-sm text-muted"><?= $l['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'});
}
updateClock(); setInterval(updateClock, 30000);
</script>

<?php renderFoot(); ?>
