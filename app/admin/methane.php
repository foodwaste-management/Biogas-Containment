<?php
// ============================================================
// BCMS — ADMIN: METHANE MONITORING
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('admin');

$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $db->prepare("DELETE FROM methane_monitoring WHERE id=:i")->execute([':i' => $id]);
        logActivity("Admin deleted methane record #$id", 'admin');
    }
}

$rows = $db->query("
    SELECT m.*, u.email
    FROM methane_monitoring m
    LEFT JOIN users u ON m.user_id = u.user_id
    ORDER BY m.recorded_at DESC
")->fetchAll();

$agg = $db->query("SELECT
    AVG(methane_ppm) as avg_ppm,
    MAX(methane_ppm) as max_ppm,
    COUNT(*) as total,
    SUM(status='SAFE') as safe_count,
    SUM(status='WARNING') as warn_count,
    SUM(status='LEAK') as leak_count
FROM methane_monitoring")->fetch();

$pageTitle = 'Methane Monitor';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">⚠️ Methane Monitoring</div>
      <div class="topbar-meta">
        <?php if (($agg['leak_count'] ?? 0) > 0): ?>
          <span class="badge badge-leak">🚨 <?= $agg['leak_count'] ?> Leak(s) on record</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="page-body">

      <!-- Summary cards -->
      <div class="stats-grid stagger" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <div class="stat-card fade-up">
          <span class="stat-icon">📋</span>
          <div class="stat-label">Total Readings</div>
          <div class="stat-value"><?= $agg['total'] ?? 0 ?></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">📊</span>
          <div class="stat-label">Avg Methane</div>
          <div class="stat-value"><?= number_format($agg['avg_ppm'] ?? 0, 0) ?><span class="unit">ppm</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">🔝</span>
          <div class="stat-label">Peak Reading</div>
          <div class="stat-value"><?= number_format($agg['max_ppm'] ?? 0, 0) ?><span class="unit">ppm</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon">✅</span>
          <div class="stat-label">SAFE</div>
          <div class="stat-value" style="color:var(--safe)"><?= $agg['safe_count'] ?? 0 ?></div>
        </div>
        <div class="stat-card fade-up warn">
          <span class="stat-icon">⚠️</span>
          <div class="stat-label">WARNING</div>
          <div class="stat-value" style="color:var(--warning)"><?= $agg['warn_count'] ?? 0 ?></div>
        </div>
        <div class="stat-card fade-up danger">
          <span class="stat-icon">🚨</span>
          <div class="stat-label">LEAK</div>
          <div class="stat-value" style="color:var(--leak)"><?= $agg['leak_count'] ?? 0 ?></div>
        </div>
      </div>

      <!-- Table -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">All Methane Readings</span>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>#</th><th>User</th><th>Methane (ppm)</th><th>Status</th><th>Recorded At</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                  <td class="text-sm"><?= e($r['email'] ?? 'Deleted User') ?></td>
                  <td class="mono"><?= number_format($r['methane_ppm'], 2) ?></td>
                  <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                  <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  <td>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete this methane record?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">✕</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:32px">No methane records yet.</td></tr>
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
