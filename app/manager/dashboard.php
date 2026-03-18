<?php
// ============================================================
// BCMS — MANAGER DASHBOARD
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('manager');

$db = getDB();

// Summary stats
$stats = $db->query("SELECT
  (SELECT COUNT(*) FROM users)                                     AS total_users,
  (SELECT COUNT(*) FROM methane_monitoring WHERE status='LEAK')    AS leak_count,
  (SELECT COUNT(*) FROM methane_monitoring WHERE status='WARNING') AS warn_count,
  (SELECT AVG(methane_ppm) FROM methane_monitoring)                AS avg_ppm,
  (SELECT AVG(gas_percentage) FROM gas_level)                      AS avg_level,
  (SELECT SUM(gas_used) FROM gas_usage)                            AS total_gas
")->fetch();

// Latest methane per user
$latestMethane = $db->query("
  SELECT m.*, u.email
  FROM methane_monitoring m
  LEFT JOIN users u ON m.user_id = u.user_id
  ORDER BY m.recorded_at DESC
  LIMIT 10
")->fetchAll();

$pageTitle = 'Manager Dashboard';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📊 Manager Dashboard</div>
      <div class="topbar-meta">
        <span class="topbar-time" id="clock"></span>
        <?php if ($stats['leak_count'] > 0): ?>
        <span class="badge badge-leak">⚡ <?= $stats['leak_count'] ?> Active Leak(s)</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="page-body">

      <!-- Stats grid -->
      <div class="stats-grid stagger">
        <div class="stat-card fade-up">
          <span class="stat-icon">👥</span>
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?= $stats['total_users'] ?></div>
          <div class="stat-sub">registered accounts</div>
        </div>
        <div class="stat-card fade-up <?= $stats['leak_count']>0?'danger':'' ?>">
          <span class="stat-icon">🚨</span>
          <div class="stat-label">Leak Events</div>
          <div class="stat-value"><?= $stats['leak_count'] ?></div>
          <div class="stat-sub">total recorded leaks</div>
        </div>
        <div class="stat-card fade-up <?= $stats['warn_count']>0?'warn':'' ?>">
          <span class="stat-icon">⚠️</span>
          <div class="stat-label">Warnings</div>
          <div class="stat-value"><?= $stats['warn_count'] ?></div>
          <div class="stat-sub">methane warnings</div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">📊</span>
          <div class="stat-label">Avg Methane</div>
          <div class="stat-value"><?= number_format($stats['avg_ppm']??0,0) ?><span class="unit">ppm</span></div>
          <div class="stat-sub">across all readings</div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">🫙</span>
          <div class="stat-label">Avg Tank Level</div>
          <div class="stat-value"><?= number_format($stats['avg_level']??0,1) ?><span class="unit">%</span></div>
          <?php $avg = $stats['avg_level']??0; ?>
          <div class="gauge-bar mt-1"><div class="gauge-fill <?= $avg<25?'danger':($avg<50?'warn':'') ?>" style="width:<?= min(100,$avg) ?>%"></div></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">💨</span>
          <div class="stat-label">Total Gas Used</div>
          <div class="stat-value"><?= number_format($stats['total_gas']??0,1) ?><span class="unit">L</span></div>
          <div class="stat-sub">cumulative volume</div>
        </div>
      </div>

      <!-- Latest methane readings -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">⚠️ Latest Methane Readings (All Users)</span>
          <a href="<?= BASE_URL ?>/app/manager/reports.php" class="btn btn-secondary btn-sm">Full Report →</a>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>User</th><th>Methane (ppm)</th><th>Status</th><th>Recorded At</th></tr>
              </thead>
              <tbody>
                <?php foreach ($latestMethane as $r): ?>
                <tr>
                  <td class="text-sm"><?= e($r['email'] ?? 'Deleted User') ?></td>
                  <td class="mono"><?= number_format($r['methane_ppm'],2) ?></td>
                  <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                  <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$latestMethane): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:32px">No readings yet.</td></tr>
                <?php endif; ?>
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
