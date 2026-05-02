<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';
requireRole('manager');

$db = getDB();
$rows = $db->query("SELECT m.*, u.email FROM methane_monitoring m LEFT JOIN users u ON m.user_id=u.user_id ORDER BY m.recorded_at DESC")->fetchAll();
$agg = $db->query("SELECT AVG(methane_ppm) as avg_ppm, MAX(methane_ppm) as max_ppm, SUM(status='LEAK') as leaks, SUM(status='WARNING') as warns, SUM(status='SAFE') as safes FROM methane_monitoring")->fetch();

$pageTitle = 'Methane Monitor';
renderHead($pageTitle);
?>
<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Methane Monitor</div>
      <?php if (($agg['leaks'] ?? 0) > 0): ?>
        <div class="topbar-meta"><span class="badge badge-leak"><?= $agg['leaks'] ?> Leak(s)</span></div>
      <?php endif; ?>
    </div>
    <div class="page-body">
      <div class="stats-grid stagger" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
        <div class="stat-card fade-up"><span class="stat-icon"></span>
          <div class="stat-label">Avg PPM</div>
          <div class="stat-value"><?= number_format($agg['avg_ppm'] ?? 0, 0) ?><span class="unit">ppm</span></div>
        </div>
        <div class="stat-card fade-up"><span class="stat-icon"></span>
          <div class="stat-label">Peak PPM</div>
          <div class="stat-value"><?= number_format($agg['max_ppm'] ?? 0, 0) ?><span class="unit">ppm</span></div>
        </div>
        <div class="stat-card fade-up"><span class="stat-icon"></span>
          <div class="stat-label">SAFE</div>
          <div class="stat-value" style="color:var(--safe)"><?= $agg['safes'] ?? 0 ?></div>
        </div>
        <div class="stat-card fade-up warn"><span class="stat-icon"></span>
          <div class="stat-label">WARNING</div>
          <div class="stat-value" style="color:var(--warning)"><?= $agg['warns'] ?? 0 ?></div>
        </div>
        <div class="stat-card fade-up danger"><span class="stat-icon"></span>
          <div class="stat-label">LEAK</div>
          <div class="stat-value" style="color:var(--leak)"><?= $agg['leaks'] ?? 0 ?></div>
        </div>
      </div>
      <div class="panel fade-up">
        <div class="panel-header"><span class="panel-title">All Methane Readings</span></div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Methane (ppm)</th>
                  <th>Status</th>
                  <th>Recorded At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="text-sm"><?= e($r['email'] ?? '—') ?></td>
                    <td class="mono"><?= number_format($r['methane_ppm'], 2) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted" style="padding:32px">No records.</td>
                  </tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php renderFoot(); ?>