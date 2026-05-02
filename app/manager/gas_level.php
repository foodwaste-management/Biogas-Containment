<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';
requireRole('manager');

$db = getDB();
$rows = $db->query("SELECT g.*, u.email FROM gas_level g LEFT JOIN users u ON g.user_id=u.user_id ORDER BY g.recorded_at DESC")->fetchAll();
$agg = $db->query("SELECT AVG(pressure_kpa) as avg_kpa, AVG(gas_percentage) as avg_pct, MIN(gas_percentage) as min_pct FROM gas_level")->fetch();

$pageTitle = 'Gas Level';
renderHead($pageTitle);
?>
<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Gas Level Monitor</div>
    </div>
    <div class="page-body">
      <div class="stats-grid stagger" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
        <div class="stat-card fade-up"><span class="stat-icon"></span>
          <div class="stat-label">Total Records</div>
          <div class="stat-value"><?= count($rows) ?></div>
        </div>
        <div class="stat-card fade-up"><span class="stat-icon"></span>
          <div class="stat-label">Avg Pressure</div>
          <div class="stat-value"><?= number_format($agg['avg_kpa'] ?? 0, 1) ?><span class="unit">kPa</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Avg Tank Fill</div>
          <div class="stat-value"><?= number_format($agg['avg_pct'] ?? 0, 1) ?><span class="unit">%</span></div>
          <?php $a = $agg['avg_pct'] ?? 0; ?>
          <div class="gauge-bar mt-1">
            <div class="gauge-fill <?= $a < 25 ? 'danger' : ($a < 50 ? 'warn' : '') ?>" style="width:<?= min(100, $a) ?>%"></div>
          </div>
        </div>
        <div class="stat-card fade-up <?= ($agg['min_pct'] ?? 100) < 20 ? 'danger' : '' ?>"><span class="stat-icon"></span>
          <div class="stat-label">Lowest Fill</div>
          <div class="stat-value"><?= number_format($agg['min_pct'] ?? 0, 1) ?><span class="unit">%</span></div>
        </div>
      </div>
      <div class="panel fade-up">
        <div class="panel-header"><span class="panel-title">All Gas Level Readings</span></div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Pressure (kPa)</th>
                  <th>Tank Fill (%)</th>
                  <th>Recorded At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="text-sm"><?= e($r['email'] ?? '—') ?></td>
                    <td class="mono"><?= number_format($r['pressure_kpa'], 2) ?></td>
                    <td>
                      <div class="flex items-center gap-2">
                        <span class="mono"><?= number_format($r['gas_percentage'], 1) ?>%</span>
                        <div class="gauge-bar" style="width:80px;flex-shrink:0">
                          <div
                            class="gauge-fill <?= $r['gas_percentage'] < 25 ? 'danger' : ($r['gas_percentage'] < 50 ? 'warn' : '') ?>"
                            style="width:<?= min(100, $r['gas_percentage']) ?>%"></div>
                        </div>
                      </div>
                    </td>
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