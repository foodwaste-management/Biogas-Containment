<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
requireRole('user');

// Fetch latest readings
$gas     = $pdo->query("SELECT * FROM gas_usage        ORDER BY recorded_at DESC LIMIT 5")->fetchAll();
$methane = $pdo->query("SELECT * FROM methane_monitoring ORDER BY recorded_at DESC LIMIT 5")->fetchAll();
$level   = $pdo->query("SELECT * FROM gas_level         ORDER BY recorded_at DESC LIMIT 5")->fetchAll();

// Latest single values for cards
$latestGas     = $gas[0]     ?? null;
$latestMethane = $methane[0] ?? null;
$latestLevel   = $level[0]   ?? null;

$statusColor = ['SAFE' => '#2ecc71', 'WARNING' => '#f39c12', 'LEAK' => '#e74c3c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BCMS — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ── Reset & Variables ─────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --green:  #2ecc71; --green-dk: #27ae60;
    --dark:   #0f1a12; --card:     #172419;
    --border: #2a3d2d; --text:     #d4e6d7;
    --muted:  #6b8f72; --sidebar:  #111e14;
    --warn:   #f39c12; --danger:   #e74c3c;
}
body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); display: flex; min-height: 100vh; }

/* ── Sidebar ───────────────────────────── */
.sidebar {
    width: 220px; min-height: 100vh;
    background: var(--sidebar);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    padding: 24px 0; flex-shrink: 0;
}
.sidebar-logo {
    display: flex; align-items: center; gap: 10px;
    padding: 0 20px 28px;
    border-bottom: 1px solid var(--border);
}
.logo-icon { width: 32px; height: 32px; background: var(--green); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.logo-text { font-family: 'Space Mono', monospace; font-size: 15px; font-weight: 700; color: #fff; }
.logo-text span { color: var(--green); }
nav { padding: 20px 12px; flex: 1; }
.nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 0 8px; margin-bottom: 8px; margin-top: 16px; }
.nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 10px; border-radius: 8px;
    color: var(--muted); text-decoration: none;
    font-size: 14px; transition: all 0.15s;
    margin-bottom: 2px;
}
.nav-link:hover, .nav-link.active { background: rgba(46,204,113,0.08); color: var(--green); }
.nav-link .icon { font-size: 16px; width: 20px; text-align: center; }
.sidebar-footer { padding: 16px 20px; border-top: 1px solid var(--border); }
.user-info { font-size: 12px; color: var(--muted); margin-bottom: 10px; }
.user-email { color: var(--text); font-size: 13px; display: block; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.logout-btn {
    display: block; width: 100%; padding: 8px;
    background: transparent; border: 1px solid var(--border);
    border-radius: 6px; color: var(--muted);
    font-size: 13px; text-align: center;
    text-decoration: none; transition: all 0.15s;
}
.logout-btn:hover { border-color: var(--danger); color: var(--danger); }

/* ── Main ──────────────────────────────── */
.main { flex: 1; padding: 32px; overflow-y: auto; }
.page-title { font-family: 'Space Mono', monospace; font-size: 20px; color: #fff; margin-bottom: 4px; }
.page-sub   { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

/* ── Stat Cards ────────────────────────── */
.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
.card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 12px; padding: 20px;
}
.card-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--muted); margin-bottom: 10px; }
.card-value { font-family: 'Space Mono', monospace; font-size: 28px; font-weight: 700; color: #fff; }
.card-unit  { font-size: 12px; color: var(--muted); margin-top: 4px; }
.status-badge {
    display: inline-block; padding: 3px 10px;
    border-radius: 20px; font-size: 12px; font-weight: 600;
    margin-top: 6px;
}

/* ── Table ─────────────────────────────── */
.section-title { font-size: 14px; font-weight: 600; color: #fff; margin-bottom: 12px; }
.table-wrap { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 24px; }
table { width: 100%; border-collapse: collapse; }
th { background: rgba(46,204,113,0.06); font-size: 11px; text-transform: uppercase; letter-spacing: 0.7px; color: var(--muted); padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
td { padding: 11px 16px; font-size: 13px; border-bottom: 1px solid rgba(42,61,45,0.5); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(46,204,113,0.03); }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🌿</div>
        <div class="logo-text">BC<span>MS</span></div>
    </div>
    <nav>
        <div class="nav-label">Monitor</div>
        <a href="dashboard.php" class="nav-link active"><span class="icon">📊</span> Dashboard</a>
        <a href="gas_usage.php" class="nav-link"><span class="icon">🔥</span> Gas Usage</a>
        <a href="methane.php"   class="nav-link"><span class="icon">⚗️</span> Methane</a>
        <a href="gas_level.php" class="nav-link"><span class="icon">📈</span> Gas Level</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <span class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></span>
            User
        </div>
        <a href="/logout.php" class="logout-btn">Sign out</a>
    </div>
</aside>

<!-- Main Content -->
<main class="main">
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Live biogas system overview</div>

    <!-- Stat Cards -->
    <div class="cards">
        <div class="card">
            <div class="card-label">Gas Flow Rate</div>
            <div class="card-value"><?= $latestGas ? number_format($latestGas['flow_rate'], 1) : '—' ?></div>
            <div class="card-unit">m³/hr</div>
        </div>
        <div class="card">
            <div class="card-label">Gas Used</div>
            <div class="card-value"><?= $latestGas ? number_format($latestGas['gas_used'], 1) : '—' ?></div>
            <div class="card-unit">m³ total</div>
        </div>
        <div class="card">
            <div class="card-label">Methane Level</div>
            <div class="card-value"><?= $latestMethane ? number_format($latestMethane['methane_ppm']) : '—' ?></div>
            <div class="card-unit">ppm</div>
            <?php if ($latestMethane): ?>
            <span class="status-badge" style="background:<?= $statusColor[$latestMethane['status']] ?>22; color:<?= $statusColor[$latestMethane['status']] ?>">
                <?= $latestMethane['status'] ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="card">
            <div class="card-label">Gas Tank Level</div>
            <div class="card-value"><?= $latestLevel ? number_format($latestLevel['gas_percentage'], 1) : '—' ?></div>
            <div class="card-unit">% capacity</div>
        </div>
    </div>

    <!-- Recent Gas Usage -->
    <div class="section-title">Recent Gas Usage</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Flow Rate (m³/hr)</th><th>Gas Used (m³)</th><th>Recorded At</th></tr></thead>
            <tbody>
            <?php foreach ($gas as $row): ?>
            <tr>
                <td><?= number_format($row['flow_rate'], 2) ?></td>
                <td><?= number_format($row['gas_used'], 2) ?></td>
                <td><?= $row['recorded_at'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Methane Status -->
    <div class="section-title">Recent Methane Readings</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Methane (ppm)</th><th>Status</th><th>Recorded At</th></tr></thead>
            <tbody>
            <?php foreach ($methane as $row): ?>
            <tr>
                <td><?= number_format($row['methane_ppm']) ?></td>
                <td>
                    <span class="status-badge" style="background:<?= $statusColor[$row['status']] ?>22;color:<?= $statusColor[$row['status']] ?>">
                        <?= $row['status'] ?>
                    </span>
                </td>
                <td><?= $row['recorded_at'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>