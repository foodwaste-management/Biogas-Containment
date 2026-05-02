<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';
requireRole('manager');

$db = getDB();
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$where = '';
$params = [];
if ($start_date && $end_date) {
  $where = 'WHERE DATE(created_at) >= :start AND DATE(created_at) <= :end';
  $params = ['start' => $start_date, 'end' => $end_date];
} elseif ($start_date) {
  $where = 'WHERE DATE(created_at) >= :start';
  $params = ['start' => $start_date];
} elseif ($end_date) {
  $where = 'WHERE DATE(created_at) <= :end';
  $params = ['end' => $end_date];
}

$sql = "SELECT * FROM activity_logs $where ORDER BY created_at DESC";

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $filename = "activity_log_" . date('Ymd_His') . ".csv";
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  $out = fopen('php://output', 'w');
  fputcsv($out, ['User', 'Activity', 'Type', 'IP Address', 'Time']);
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  while ($r = $stmt->fetch()) {
    fputcsv($out, [$r['email'] ?? 'System', $r['activity'], $r['activity_type'], $r['ip_address'], $r['created_at']]);
  }
  fclose($out);
  exit;
}

$stmt = $db->prepare($sql . " LIMIT 100");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = 'Activity Log';
renderHead($pageTitle);
?>
<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">Activity Log</div>
    </div>
    <div class="page-body">
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">Recent Activity</span>
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
                  <th>User</th>
                  <th>Activity</th>
                  <th>Type</th>
                  <th>IP</th>
                  <th>Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logs as $l): ?>
                  <tr>
                    <td class="text-sm"><?= e($l['email'] ?? 'System') ?></td>
                    <td class="text-sm"><?= e($l['activity']) ?></td>
                    <td><span class="badge badge-<?= $l['activity_type'] ?>"><?= $l['activity_type'] ?></span></td>
                    <td class="mono text-sm text-muted"><?= e($l['ip_address'] ?? '') ?></td>
                    <td class="text-sm text-muted"><?= $l['created_at'] ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted" style="padding:32px">No activity yet.</td>
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