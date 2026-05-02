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
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = '';
$params = [];
if ($start_date && $end_date) {
  $where = 'WHERE DATE(m.recorded_at) >= :start AND DATE(m.recorded_at) <= :end';
  $params = ['start' => $start_date, 'end' => $end_date];
} elseif ($start_date) {
  $where = 'WHERE DATE(m.recorded_at) >= :start';
  $params = ['start' => $start_date];
} elseif ($end_date) {
  $where = 'WHERE DATE(m.recorded_at) <= :end';
  $params = ['end' => $end_date];
}

function getWhereClause($where, $alias)
{
  return str_replace('m.', $alias . '.', $where);
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $filename = "report_{$tab}_" . date('Ymd_His') . ".csv";
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  $out = fopen('php://output', 'w');

  if ($tab === 'methane') {
    fputcsv($out, ['ID', 'User', 'Methane (ppm)', 'Status', 'Recorded At']);
    $sql = "SELECT m.*, u.email FROM methane_monitoring m LEFT JOIN users u ON m.user_id=u.user_id " . getWhereClause($where, 'm') . " ORDER BY m.recorded_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    while ($r = $stmt->fetch()) {
      fputcsv($out, [$r['id'], $r['email'] ?? '—', $r['methane_ppm'], $r['status'], $r['recorded_at']]);
    }
  } elseif ($tab === 'gas_usage') {
    fputcsv($out, ['ID', 'User', 'Flow Rate (L/min)', 'Gas Used (L)', 'Recorded At']);
    $sql = "SELECT g.*, u.email FROM gas_usage g LEFT JOIN users u ON g.user_id=u.user_id " . getWhereClause($where, 'g') . " ORDER BY g.recorded_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    while ($r = $stmt->fetch()) {
      fputcsv($out, [$r['id'], $r['email'] ?? '—', $r['flow_rate'], $r['gas_used'], $r['recorded_at']]);
    }
  } else {
    fputcsv($out, ['ID', 'User', 'Pressure (kPa)', 'Tank Fill (%)', 'Recorded At']);
    $sql = "SELECT g.*, u.email FROM gas_level g LEFT JOIN users u ON g.user_id=u.user_id " . getWhereClause($where, 'g') . " ORDER BY g.recorded_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    while ($r = $stmt->fetch()) {
      fputcsv($out, [$r['id'], $r['email'] ?? '—', $r['pressure_kpa'], $r['gas_percentage'], $r['recorded_at']]);
    }
  }
  fclose($out);
  exit;
}

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
          <div class="flex gap-2 items-center">
            <form method="GET" class="flex gap-2 items-center" style="margin:0;">
              <input type="hidden" name="tab" value="<?= e($tab) ?>">
              <input type="date" name="start_date" value="<?= e($start_date) ?>" class="form-control btn-sm"
                style="padding:4px 8px;border:1.5px solid var(--border);border-radius:var(--radius);font-family:var(--font-sans);font-size:.8rem;background:var(--surface);">
              <span class="text-sm text-muted">to</span>
              <input type="date" name="end_date" value="<?= e($end_date) ?>" class="form-control btn-sm"
                style="padding:4px 8px;border:1.5px solid var(--border);border-radius:var(--radius);font-family:var(--font-sans);font-size:.8rem;background:var(--surface);">
              <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
            <?php $exportUrl = "?tab=" . urlencode($tab) . "&start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&export=csv"; ?>
            <a href="<?= $exportUrl ?>" class="btn btn-secondary btn-sm">Export CSV</a>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <?php if ($tab === 'methane'): ?>
              <?php
              $sql = "SELECT m.*, u.email FROM methane_monitoring m LEFT JOIN users u ON m.user_id=u.user_id " . getWhereClause($where, 'm') . " ORDER BY m.recorded_at DESC";
              $stmt = $db->prepare($sql);
              $stmt->execute($params);
              $rows = $stmt->fetchAll();
              ?>
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
              <?php
              $sql = "SELECT g.*, u.email FROM gas_usage g LEFT JOIN users u ON g.user_id=u.user_id " . getWhereClause($where, 'g') . " ORDER BY g.recorded_at DESC";
              $stmt = $db->prepare($sql);
              $stmt->execute($params);
              $rows = $stmt->fetchAll();
              ?>
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
              <?php
              $sql = "SELECT g.*, u.email FROM gas_level g LEFT JOIN users u ON g.user_id=u.user_id " . getWhereClause($where, 'g') . " ORDER BY g.recorded_at DESC";
              $stmt = $db->prepare($sql);
              $stmt->execute($params);
              $rows = $stmt->fetchAll();
              ?>
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