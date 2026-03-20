<?php
// ============================================================
// BCMS — USER DASHBOARD
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('user');

$db     = getDB();
$uid    = currentUserId();

// Latest readings for this user
$gasUsage = $db->prepare("SELECT * FROM gas_usage WHERE user_id=:u ORDER BY recorded_at DESC LIMIT 1");
$gasUsage->execute([':u'=>$uid]);
$latestGas = $gasUsage->fetch();

$methane = $db->prepare("SELECT * FROM methane_monitoring WHERE user_id=:u ORDER BY recorded_at DESC LIMIT 1");
$methane->execute([':u'=>$uid]);
$latestMethane = $methane->fetch();

$level = $db->prepare("SELECT * FROM gas_level WHERE user_id=:u ORDER BY recorded_at DESC LIMIT 1");
$level->execute([':u'=>$uid]);
$latestLevel = $level->fetch();

// Count submissions
$countStmt = $db->prepare("SELECT
  (SELECT COUNT(*) FROM gas_usage          WHERE user_id=:u1) as gas_count,
  (SELECT COUNT(*) FROM methane_monitoring WHERE user_id=:u2) as meth_count,
  (SELECT COUNT(*) FROM gas_level          WHERE user_id=:u3) as lvl_count
");
$countStmt->execute([':u1' => $uid, ':u2' => $uid, ':u3' => $uid]);
$counts = $countStmt->fetch();

$pageTitle = 'My Dashboard';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">👤 My Dashboard</div>
      <div class="topbar-meta">
        <span class="topbar-time" id="clock"></span>
        <a href="<?= BASE_URL ?>/app/user/log_reading.php" class="btn btn-primary btn-sm">+ Log Reading</a>
      </div>
    </div>

    <div class="page-body">

      <!-- Welcome banner -->
      <div class="panel" style="background:var(--green-900);border:none;margin-bottom:24px;">
        <div class="panel-body flex items-center justify-between gap-2" style="flex-wrap:wrap;">
          <div>
            <p style="color:var(--green-300);font-size:.78rem;font-family:var(--font-mono);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Welcome back</p>
            <h2 style="font-family:var(--font-serif);font-size:1.5rem;color:#fff;"><?= e(currentEmail()) ?></h2>
            <p style="color:var(--green-300);font-size:.875rem;margin-top:4px;">Log your sensor readings to keep the system updated.</p>
          </div>
          <div style="font-size:3rem;">🫧</div>
        </div>
      </div>

      <!-- Stats -->
      <div class="stats-grid stagger">
        <div class="stat-card fade-up">
          <span class="stat-icon">📊</span>
          <div class="stat-label">Gas Usage Logs</div>
          <div class="stat-value"><?= $counts['gas_count'] ?></div>
          <div class="stat-sub">readings submitted</div>
        </div>
        <div class="stat-card fade-up <?= ($latestMethane && $latestMethane['status']==='LEAK') ? 'danger' : (($latestMethane && $latestMethane['status']==='WARNING') ? 'warn' : '') ?>">
          <span class="stat-icon">⚠️</span>
          <div class="stat-label">Latest Methane</div>
          <div class="stat-value">
            <?= $latestMethane ? number_format($latestMethane['methane_ppm'],0) : '—' ?>
            <span class="unit">ppm</span>
          </div>
          <div class="stat-sub">
            <?php if ($latestMethane): ?>
              <span class="badge badge-<?= strtolower($latestMethane['status']) ?>"><?= $latestMethane['status'] ?></span>
            <?php else: echo 'No readings yet'; endif; ?>
          </div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">🫙</span>
          <div class="stat-label">Tank Level</div>
          <div class="stat-value">
            <?= $latestLevel ? number_format($latestLevel['gas_percentage'],1) : '—' ?>
            <span class="unit">%</span>
          </div>
          <?php if ($latestLevel): ?>
          <div class="gauge-bar mt-1">
            <div class="gauge-fill <?= $latestLevel['gas_percentage']<25?'danger':($latestLevel['gas_percentage']<50?'warn':'') ?>"
                 style="width:<?= min(100,$latestLevel['gas_percentage']) ?>%"></div>
          </div>
          <?php endif; ?>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">💨</span>
          <div class="stat-label">Flow Rate</div>
          <div class="stat-value">
            <?= $latestGas ? number_format($latestGas['flow_rate'],2) : '—' ?>
            <span class="unit">L/min</span>
          </div>
          <div class="stat-sub">Gas used: <?= $latestGas ? number_format($latestGas['gas_used'],2).'L' : 'N/A' ?></div>
        </div>
      </div>

      <!-- Recent readings table -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">📋 My Recent Methane Readings</span>
          <a href="<?= BASE_URL ?>/app/user/my_readings.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="panel-body">
          <?php
          $recent = $db->prepare("SELECT * FROM methane_monitoring WHERE user_id=:u ORDER BY recorded_at DESC LIMIT 8");
          $recent->execute([':u'=>$uid]);
          $rows = $recent->fetchAll();
          ?>
          <?php if ($rows): ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Methane (ppm)</th>
                  <th>Status</th>
                  <th>Recorded At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="mono text-muted text-sm"><?= $r['id'] ?></td>
                  <td class="mono"><?= number_format($r['methane_ppm'],2) ?></td>
                  <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                  <td class="text-muted text-sm"><?= $r['recorded_at'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No readings yet. <a href="<?= BASE_URL ?>/app/user/log_reading.php">Log your first reading →</a></p>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>

<script>
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent = now.toLocaleString('en-PH', {
    dateStyle: 'medium', timeStyle: 'short'
  });
}
updateClock();
setInterval(updateClock, 30000);
</script>

<?php renderFoot(); ?>
