<?php
// ============================================================
// BCMS — ADMIN: GAS LEVEL
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
    $db->prepare("DELETE FROM gas_level WHERE id=:i")->execute([':i' => $id]);
    logActivity("Admin deleted gas level record #$id", 'admin');
  }
}

$rows = $db->query("
    SELECT g.*, u.email
    FROM gas_level g
    LEFT JOIN users u ON g.user_id = u.user_id
    ORDER BY g.recorded_at DESC
")->fetchAll();

$agg = $db->query("SELECT
    AVG(pressure_kpa)   as avg_kpa,
    AVG(gas_percentage) as avg_pct,
    MIN(gas_percentage) as min_pct,
    MAX(pressure_kpa)   as max_kpa
FROM gas_level")->fetch();

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

      <!-- Summary cards -->
      <div class="stats-grid stagger" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Total Records</div>
          <div class="stat-value"><?= count($rows) ?></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Avg Pressure</div>
          <div class="stat-value"><?= number_format($agg['avg_kpa'] ?? 0, 1) ?><span class="unit">kPa</span></div>
        </div>
        <div class="stat-card fade-up">
          <span class="stat-icon"></span>
          <div class="stat-label">Avg Tank Fill</div>
          <div class="stat-value"><?= number_format($agg['avg_pct'] ?? 0, 1) ?><span class="unit">%</span></div>
          <?php $avg = $agg['avg_pct'] ?? 0; ?>
          <div class="gauge-bar mt-1">
            <div class="gauge-fill <?= $avg < 25 ? 'danger' : ($avg < 50 ? 'warn' : '') ?>"
              style="width:<?= min(100, $avg) ?>%">
            </div>
          </div>
        </div>
        <div class="stat-card fade-up <?= ($agg['min_pct'] ?? 100) < 20 ? 'danger' : '' ?>">
          <span class="stat-icon"></span>
          <div class="stat-label">Lowest Fill</div>
          <div class="stat-value"><?= number_format($agg['min_pct'] ?? 0, 1) ?><span class="unit">%</span></div>
          <div class="stat-sub">recorded minimum</div>
        </div>
      </div>

      <!-- Table -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">All Gas Level Readings</span>
        </div>
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
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="text-sm"><?= e($r['email'] ?? 'Deleted User') ?></td>
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
                    <td>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete this record?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">✕</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                  <tr>
                    <td colspan="6" class="text-center text-muted" style="padding:32px">No gas level records yet.</td>
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