<?php
// ============================================================
// BCMS — LOG READING (User)
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('user');

$db  = getDB();
$uid = currentUserId();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';

    try {
        if ($type === 'gas_usage') {
            $flow = floatval($_POST['flow_rate'] ?? 0);
            $used = floatval($_POST['gas_used']  ?? 0);
            $stmt = $db->prepare("INSERT INTO gas_usage (user_id, flow_rate, gas_used) VALUES (:u,:f,:g)");
            $stmt->execute([':u'=>$uid, ':f'=>$flow, ':g'=>$used]);
            logActivity("Logged gas usage: flow={$flow} L/min, used={$used} L", 'sensor');
            $msg = '✅ Gas usage reading saved.';

        } elseif ($type === 'methane') {
            $ppm    = floatval($_POST['methane_ppm'] ?? 0);
            $status = methaneStatus($ppm);
            $stmt = $db->prepare("INSERT INTO methane_monitoring (user_id, methane_ppm, status) VALUES (:u,:p,:s)");
            $stmt->execute([':u'=>$uid, ':p'=>$ppm, ':s'=>$status]);
            logActivity("Logged methane: {$ppm} ppm — {$status}", 'sensor');
            $msg = "✅ Methane reading saved. Status: <strong>{$status}</strong>";

        } elseif ($type === 'gas_level') {
            $kpa = floatval($_POST['pressure_kpa']   ?? 0);
            $pct = floatval($_POST['gas_percentage'] ?? 0);
            $pct = max(0, min(100, $pct));
            $stmt = $db->prepare("INSERT INTO gas_level (user_id, pressure_kpa, gas_percentage) VALUES (:u,:k,:p)");
            $stmt->execute([':u'=>$uid, ':k'=>$kpa, ':p'=>$pct]);
            logActivity("Logged gas level: {$kpa} kPa, {$pct}%", 'sensor');
            $msg = '✅ Gas level reading saved.';
        } else {
            $err = 'Please select a reading type.';
        }
    } catch (PDOException $e) {
        $err = 'Database error. Please try again.';
        error_log('[BCMS log_reading] ' . $e->getMessage());
    }
}

$pageTitle = 'Log Reading';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">✍️ Log a Sensor Reading</div>
      <div class="topbar-meta">
        <span class="topbar-time" id="clock"></span>
      </div>
    </div>

    <div class="page-body">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

      <!-- Gas Usage -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">📊 Gas Usage</span>
          <span class="badge badge-sensor">Flow & Volume</span>
        </div>
        <div class="panel-body">
          <form method="POST">
            <input type="hidden" name="type" value="gas_usage">
            <div class="sensor-form-grid">
              <div class="form-group">
                <label for="flow_rate">Flow Rate (L/min)</label>
                <input type="number" step="0.01" min="0" id="flow_rate" name="flow_rate" placeholder="e.g. 2.50" required>
              </div>
              <div class="form-group">
                <label for="gas_used">Gas Used (L)</label>
                <input type="number" step="0.01" min="0" id="gas_used" name="gas_used" placeholder="e.g. 12.30" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Gas Usage</button>
          </form>
        </div>
      </div>

      <!-- Methane -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">⚠️ Methane Monitor</span>
          <span class="badge badge-sensor">ppm Levels</span>
        </div>
        <div class="panel-body">
          <p class="text-muted text-sm mb-2">Thresholds — SAFE: &lt;<?= METHANE_SAFE ?> ppm | WARNING: <?= METHANE_SAFE ?>–<?= METHANE_WARNING ?> ppm | LEAK: &gt;<?= METHANE_WARNING ?> ppm</p>
          <form method="POST">
            <input type="hidden" name="type" value="methane">
            <div class="sensor-form-grid">
              <div class="form-group">
                <label for="methane_ppm">Methane Level (ppm)</label>
                <input type="number" step="0.1" min="0" id="methane_ppm" name="methane_ppm" placeholder="e.g. 450" required>
              </div>
              <div class="form-group">
                <label>Auto-detected Status</label>
                <div id="status_preview" style="padding:10px 14px;background:var(--safe-bg);border:1.5px solid var(--safe);border-radius:var(--radius);font-family:var(--font-mono);font-size:.85rem;color:var(--safe);">
                  SAFE
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Methane Reading</button>
          </form>
        </div>
      </div>

      <!-- Gas Level -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">🫙 Gas Level</span>
          <span class="badge badge-sensor">Pressure & Fill</span>
        </div>
        <div class="panel-body">
          <form method="POST">
            <input type="hidden" name="type" value="gas_level">
            <div class="sensor-form-grid">
              <div class="form-group">
                <label for="pressure_kpa">Pressure (kPa)</label>
                <input type="number" step="0.1" min="0" id="pressure_kpa" name="pressure_kpa" placeholder="e.g. 101.3" required>
              </div>
              <div class="form-group">
                <label for="gas_percentage">Tank Fill (%)</label>
                <input type="number" step="0.1" min="0" max="100" id="gas_percentage" name="gas_percentage" placeholder="e.g. 78.5" required>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Gas Level</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
// Live methane status preview
const ppmInput     = document.getElementById('methane_ppm');
const statusDiv    = document.getElementById('status_preview');
const SAFE_THR     = <?= METHANE_SAFE ?>;
const WARNING_THR  = <?= METHANE_WARNING ?>;

ppmInput.addEventListener('input', function() {
  const v = parseFloat(this.value) || 0;
  let status, bg, color;
  if (v < SAFE_THR)      { status='SAFE';    bg='var(--safe-bg)';    color='var(--safe)'; }
  else if (v < WARNING_THR){ status='WARNING'; bg='var(--warning-bg)'; color='var(--warning)'; }
  else                   { status='LEAK';    bg='var(--leak-bg)';    color='var(--leak)'; }
  statusDiv.textContent = status;
  statusDiv.style.background   = bg;
  statusDiv.style.borderColor  = color;
  statusDiv.style.color        = color;
});

function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent = now.toLocaleString('en-PH', { dateStyle:'medium', timeStyle:'short' });
}
updateClock(); setInterval(updateClock, 30000);
</script>

<?php renderFoot(); ?>
