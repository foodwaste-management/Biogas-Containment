<?php
// ============================================================
// BCMS — MANAGER REPORTS
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('manager');

$db = getDB();
$tab = $_GET['tab'] ?? 'methane';

$pageTitle = 'Reports';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Reports</div>
    </div>

    <div class="page-body">
      <!-- Tabs -->
      <div class="flex gap-2 mb-2" style="flex-wrap:wrap;">
        <?php foreach (['methane' => 'Methane', 'gas_usage' => 'Gas Usage', 'gas_level' => 'Gas Level'] as $k => $l): ?>
          <a href="?tab=<?= $k ?>" class="btn <?= $tab === $k ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $l ?></a>
        <?php endforeach; ?>
      </div>

      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">
            <?= $tab === 'methane' ? 'All Methane Readings' : ($tab === 'gas_usage' ? 'All Gas Usage' : 'All Gas Levels') ?>
          </span>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <?php if ($tab === 'methane'): ?>
              <?php $rows = $db->query("SELECT m.*, u.email FROM methane_monitoring m LEFT JOIN users u ON m.user_id=u.user_id ORDER BY m.recorded_at DESC")->fetchAll(); ?>
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
                </tbody>
              </table>

            <?php elseif ($tab === 'gas_usage'): ?>
              <?php $rows = $db->query("SELECT g.*, u.email FROM gas_usage g LEFT JOIN users u ON g.user_id=u.user_id ORDER BY g.recorded_at DESC")->fetchAll(); ?>
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
                      <td class="text-sm"><?= e($r['email'] ?? '—') ?></td>
                      <td class="mono"><?= number_format($r['flow_rate'], 2) ?></td>
                      <td class="mono"><?= number_format($r['gas_used'], 2) ?></td>
                      <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

            <?php else: ?>
              <?php $rows = $db->query("SELECT g.*, u.email FROM gas_level g LEFT JOIN users u ON g.user_id=u.user_id ORDER BY g.recorded_at DESC")->fetchAll(); ?>
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
                        <span class="mono"><?= number_format($r['gas_percentage'], 1) ?>%</span>
                        <div class="gauge-bar mt-1" style="width:100px">
                          <div
                            class="gauge-fill <?= $r['gas_percentage'] < 25 ? 'danger' : ($r['gas_percentage'] < 50 ? 'warn' : '') ?>"
                            style="width:<?= min(100, $r['gas_percentage']) ?>%"></div>
                        </div>
                      </td>
                      <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php renderFoot(); ?>