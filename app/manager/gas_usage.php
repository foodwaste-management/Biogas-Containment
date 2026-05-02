<?php
// ============================================================
// BCMS — MANAGER: GAS USAGE
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('manager');

$db = getDB();

$rows = $db->query("
    SELECT g.*, u.email
    FROM gas_usage g
    LEFT JOIN users u ON g.user_id = u.user_id
    ORDER BY g.recorded_at DESC
")->fetchAll();

$agg = $db->query("SELECT AVG(flow_rate) as avg_flow, SUM(gas_used) as total_gas, MAX(flow_rate) as max_flow FROM gas_usage")->fetch();

$pageTitle = 'Gas Usage';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Gas Usage Monitor</div>
    </div>

    <div class="page-body">
      <div class="stats-grid stagger" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Total Records</div>
          <div class="stat-value"><?= count($rows) ?></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Avg Flow Rate</div>
          <div class="stat-value"><?= number_format($agg['avg_flow'] ?? 0, 2) ?><span class="unit">L/min</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Peak Flow</div>
          <div class="stat-value"><?= number_format($agg['max_flow'] ?? 0, 2) ?><span class="unit">L/min</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Total Gas Used</div>
          <div class="stat-value"><?= number_format($agg['total_gas'] ?? 0, 1) ?><span class="unit">L</span></div>
        </div>
      </div>

      <div class="panel fade-up">
        <div class="panel-header"><span class="panel-title">All Gas Usage Readings</span></div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Flow Rate (L/min)</th>
                  <th>Gas Used (L)</th>
                  <th>Recorded At</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="text-sm"><?= e($r['email'] ?? 'Deleted User') ?></td>
                    <td class="mono"><?= number_format($r['flow_rate'], 2) ?></td>
                    <td class="mono"><?= number_format($r['gas_used'], 2) ?></td>
                    <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted" style="padding:32px">No records yet.</td>
                  </tr>
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