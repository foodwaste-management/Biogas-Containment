<?php
// ============================================================
// BCMS — SIDEBAR PARTIAL
// Include inside any dashboard with $pageTitle and $role set
// ============================================================
$role = $_SESSION['role'] ?? 'user';
$email = $_SESSION['email'] ?? '';
$initials = strtoupper(substr($email, 0, 1));
$roleLabel = ucfirst($role);
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <h2>Biogas<br>Monitoring</h2>
    <p>BCMS <?= APP_VERSION ?></p>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Overview</div>
    <?php navLink('/app/' . $role . '/dashboard.php', '', 'Dashboard'); ?>

    <?php if ($role === 'admin' || $role === 'manager'): ?>
      <div class="nav-section">Sensors</div>
      <?php navLink('/app/' . $role . '/gas_usage.php', '', 'Gas Usage'); ?>
      <?php navLink('/app/' . $role . '/methane.php', '', 'Methane Monitor'); ?>
      <?php navLink('/app/' . $role . '/gas_level.php', '', 'Gas Level'); ?>
    <?php endif; ?>

    <?php if ($role === 'user'): ?>
      <div class="nav-section">Sensors</div>
      <?php navLink('/app/user/log_reading.php', '', 'Log Reading'); ?>
      <?php navLink('/app/user/my_readings.php', '', 'My Readings'); ?>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
      <div class="nav-section">Admin</div>
      <?php navLink('/app/admin/users.php', '', 'Users'); ?>
      <?php navLink('/app/admin/activity_log.php', '', 'Activity Log'); ?>
    <?php endif; ?>

    <?php if ($role === 'manager'): ?>
      <div class="nav-section">Reports</div>
      <?php navLink('/app/manager/reports.php', '', 'Reports'); ?>
      <?php navLink('/app/manager/activity_log.php', '', 'Activity Log'); ?>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-chip-avatar"><?= $initials ?></div>
      <div class="user-chip-info">
        <div class="user-chip-email"><?= e($email) ?></div>
        <div class="user-chip-role"><?= $roleLabel ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-secondary btn-full btn-sm">
      ← Sign Out
    </a>
  </div>
</aside>