<?php
// ============================================================
// BCMS — MY READINGS (User)
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('user');

$db  = getDB();
$uid = currentUserId();

$tab = $_GET['tab'] ?? 'methane';

$pageTitle = 'My Readings';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">📋 My Readings</div>
      <div class="topbar-meta">
        <a href="<?= BASE_URL ?>/app/user/log_reading.php" class="btn btn-primary btn-sm">+ Log Reading</a>
      </div>
    </div>

    <div class="page-body">

      <!-- Tabs -->
      <div class="flex gap-2 mb-2" style="flex-wrap:wrap;">
        <?php foreach (['methane'=>'⚠️ Methane','gas_usage'=>'📊 Gas Usage','gas_level'=>'🫙 Gas Level'] as $key=>$label): ?>
        <a href="?tab=<?= $key ?>"
           class="btn <?= $tab===$key?'btn-primary':'btn-secondary' ?> btn-sm">
          <?= $label ?>
        </a>
        <?php endforeach; ?>
      </div>

      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">
            <?= $tab==='methane'?'⚠️ My Methane Readings':($tab==='gas_usage'?'📊 My Gas Usage':'🫙 My Gas Levels') ?>
          </span>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <?php if ($tab === 'methane'): ?>
              <?php $rows = $db->prepare("SELECT * FROM methane_monitoring WHERE user_id=:u ORDER BY recorded_at DESC"); $rows->execute([':u'=>$uid]); $rows = $rows->fetchAll(); ?>
              <table class="data-table">
                <thead><tr><th>#</th><th>Methane (ppm)</th><th>Status</th><th>Recorded At</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="mono"><?= number_format($r['methane_ppm'],2) ?></td>
                    <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                    <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No records found.</td></tr><?php endif; ?>
                </tbody>
              </table>

            <?php elseif ($tab === 'gas_usage'): ?>
              <?php $rows = $db->prepare("SELECT * FROM gas_usage WHERE user_id=:u ORDER BY recorded_at DESC"); $rows->execute([':u'=>$uid]); $rows = $rows->fetchAll(); ?>
              <table class="data-table">
                <thead><tr><th>#</th><th>Flow Rate (L/min)</th><th>Gas Used (L)</th><th>Recorded At</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="mono"><?= number_format($r['flow_rate'],2) ?></td>
                    <td class="mono"><?= number_format($r['gas_used'],2) ?></td>
                    <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No records found.</td></tr><?php endif; ?>
                </tbody>
              </table>

            <?php else: ?>
              <?php $rows = $db->prepare("SELECT * FROM gas_level WHERE user_id=:u ORDER BY recorded_at DESC"); $rows->execute([':u'=>$uid]); $rows = $rows->fetchAll(); ?>
              <table class="data-table">
                <thead><tr><th>#</th><th>Pressure (kPa)</th><th>Tank Fill (%)</th><th>Recorded At</th></tr></thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="mono text-sm text-muted"><?= $r['id'] ?></td>
                    <td class="mono"><?= number_format($r['pressure_kpa'],2) ?></td>
                    <td>
                      <span class="mono"><?= number_format($r['gas_percentage'],1) ?>%</span>
                      <div class="gauge-bar mt-1" style="width:120px">
                        <div class="gauge-fill <?= $r['gas_percentage']<25?'danger':($r['gas_percentage']<50?'warn':'') ?>" style="width:<?= min(100,$r['gas_percentage']) ?>%"></div>
                      </div>
                    </td>
                    <td class="text-sm text-muted"><?= $r['recorded_at'] ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (!$rows): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No records found.</td></tr><?php endif; ?>
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
