<?php
// ============================================================
// BCMS — ADMIN: GAS USAGE
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
    $db->prepare("DELETE FROM gas_usage WHERE id=:i")->execute([':i' => $id]);
    logActivity("Admin deleted gas usage record #$id", 'admin');
  }
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = '';
$params = [];
if ($start_date && $end_date) {
  $where = 'WHERE DATE(g.recorded_at) >= :start AND DATE(g.recorded_at) <= :end';
  $params = ['start' => $start_date, 'end' => $end_date];
} elseif ($start_date) {
  $where = 'WHERE DATE(g.recorded_at) >= :start';
  $params = ['start' => $start_date];
} elseif ($end_date) {
  $where = 'WHERE DATE(g.recorded_at) <= :end';
  $params = ['end' => $end_date];
}

$sql = "SELECT g.*, u.email FROM gas_usage g LEFT JOIN users u ON g.user_id = u.user_id $where ORDER BY g.recorded_at DESC";

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $filename = "admin_gas_usage_" . date('Ymd_His') . ".csv";
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  $out = fopen('php://output', 'w');
  fputcsv($out, ['ID', 'User', 'Flow Rate (L/min)', 'Gas Used (L)', 'Recorded At']);
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  while ($r = $stmt->fetch()) {
    fputcsv($out, [$r['id'], $r['email'] ?? 'Deleted User', $r['flow_rate'], $r['gas_used'], $r['recorded_at']]);
  }
  fclose($out);
  exit;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Aggregates
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

      <!-- Summary cards -->
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

      <!-- Table -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">All Gas Usage Readings</span>
          <div class="flex gap-2 items-center">
            <form method="GET" class="flex gap-2 items-center" style="margin:0;">
              <input type="date" name="start_date" value="<?= e($start_date) ?>" class="form-control btn-sm"
                style="padding:4px 8px;border:1.5px solid var(--border);border-radius:var(--radius);font-family:var(--font-sans);font-size:.8rem;background:var(--surface);">
              <span class="text-sm text-muted">to</span>
              <input type="date" name="end_date" value="<?= e($end_date) ?>" class="form-control btn-sm"
                style="padding:4px 8px;border:1.5px solid var(--border);border-radius:var(--radius);font-family:var(--font-sans);font-size:.8rem;background:var(--surface);">
              <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
            <?php $exportUrl = "?start_date=" . urlencode($start_date) . "&end_date=" . urlencode($end_date) . "&export=csv"; ?>
            <a href="<?= $exportUrl ?>" class="btn btn-secondary btn-sm">Export CSV</a>
          </div>
        </div>
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
                  <th></th>
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
                    <td colspan="6" class="text-center text-muted" style="padding:32px">No gas usage records yet.</td>
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