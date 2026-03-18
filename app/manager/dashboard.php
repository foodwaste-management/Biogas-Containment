<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
requireRole('manager');

$totalGas     = $pdo->query("SELECT COUNT(*) FROM gas_usage")->fetchColumn();
$totalMethane = $pdo->query("SELECT COUNT(*) FROM methane_monitoring")->fetchColumn();
$leaks        = $pdo->query("SELECT COUNT(*) FROM methane_monitoring WHERE status='LEAK'")->fetchColumn();
$warnings     = $pdo->query("SELECT COUNT(*) FROM methane_monitoring WHERE status='WARNING'")->fetchColumn();
$logs         = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BCMS — Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* (same CSS vars as user dashboard — paste the full CSS block here) */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root { --green:#2ecc71;--green-dk:#27ae60;--dark:#0f1a12;--card:#172419;--border:#2a3d2d;--text:#d4e6d7;--muted:#6b8f72;--sidebar:#111e14;--warn:#f39c12;--danger:#e74c3c; }
body { font-family:'DM Sans',sans-serif;background:var(--dark);color:var(--text);display:flex;min-height:100vh; }
.sidebar{width:220px;min-height:100vh;background:var(--sidebar);border-right:1px solid var(--border);display:flex;flex-direction:column;padding:24px 0;flex-shrink:0;}
.sidebar-logo{display:flex;align-items:center;gap:10px;padding:0 20px 28px;border-bottom:1px solid var(--border);}
.logo-icon{width:32px;height:32px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.logo-text{font-family:'Space Mono',monospace;font-size:15px;font-weight:700;color:#fff;}
.logo-text span{color:var(--green);}
nav{padding:20px 12px;flex:1;}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--muted);padding:0 8px;margin-bottom:8px;margin-top:16px;}
.nav-link{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:14px;transition:all .15s;margin-bottom:2px;}
.nav-link:hover,.nav-link.active{background:rgba(46,204,113,.08);color:var(--green);}
.nav-link .icon{font-size:16px;width:20px;text-align:center;}
.sidebar-footer{padding:16px 20px;border-top:1px solid var(--border);}
.user-email{color:var(--text);font-size:13px;display:block;margin-bottom:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.logout-btn{display:block;width:100%;padding:8px;background:transparent;border:1px solid var(--border);border-radius:6px;color:var(--muted);font-size:13px;text-align:center;text-decoration:none;transition:all .15s;}
.logout-btn:hover{border-color:var(--danger);color:var(--danger);}
.main{flex:1;padding:32px;overflow-y:auto;}
.page-title{font-family:'Space Mono',monospace;font-size:20px;color:#fff;margin-bottom:4px;}
.page-sub{font-size:13px;color:var(--muted);margin-bottom:28px;}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;}
.card-label{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:10px;}
.card-value{font-family:'Space Mono',monospace;font-size:32px;font-weight:700;color:#fff;}
.card-value.warn{color:var(--warn);}
.card-value.danger{color:var(--danger);}
.section-title{font-size:14px;font-weight:600;color:#fff;margin-bottom:12px;}
.table-wrap{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;}
th{background:rgba(46,204,113,.06);font-size:11px;text-transform:uppercase;letter-spacing:.7px;color:var(--muted);padding:12px 16px;text-align:left;border-bottom:1px solid var(--border);}
td{padding:11px 16px;font-size:13px;border-bottom:1px solid rgba(42,61,45,.5);}
tr:last-child td{border-bottom:none;}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-login{background:rgba(46,204,113,.15);color:var(--green);}
.badge-logout{background:rgba(107,143,114,.15);color:var(--muted);}
.badge-failed{background:rgba(231,76,60,.15);color:var(--danger);}
.badge-other{background:rgba(243,156,18,.15);color:var(--warn);}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">🌿</div>
        <div class="logo-text">BC<span>MS</span></div>
    </div>
    <nav>
        <div class="nav-label">Overview</div>
        <a href="dashboard.php" class="nav-link active"><span class="icon">📊</span> Dashboard</a>
        <a href="reports.php"   class="nav-link"><span class="icon">📋</span> Reports</a>
        <a href="logs.php"      class="nav-link"><span class="icon">📜</span> Activity Logs</a>
    </nav>
    <div class="sidebar-footer">
        <span class="user-email"><?= htmlspecialchars($_SESSION['email']) ?></span>
        <a href="/logout.php" class="logout-btn">Sign out</a>
    </div>
</aside>

<main class="main">
    <div class="page-title">Manager Dashboard</div>
    <div class="page-sub">System overview & monitoring</div>

    <div class="cards">
        <div class="card">
            <div class="card-label">Gas Readings</div>
            <div class="card-value"><?= $totalGas ?></div>
        </div>
        <div class="card">
            <div class="card-label">Methane Readings</div>
            <div class="card-value"><?= $totalMethane ?></div>
        </div>
        <div class="card">
            <div class="card-label">Warnings</div>
            <div class="card-value warn"><?= $warnings ?></div>
        </div>
        <div class="card">
            <div class="card-label">Leaks Detected</div>
            <div class="card-value danger"><?= $leaks ?></div>
        </div>
    </div>

    <div class="section-title">Recent Activity Logs</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Email</th><th>Activity</th><th>Type</th><th>IP</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <?php
                $badgeClass = match($log['activity_type']) {
                    'login'        => 'badge-login',
                    'logout'       => 'badge-logout',
                    'login_failed' => 'badge-failed',
                    default        => 'badge-other',
                };
            ?>
            <tr>
                <td><?= htmlspecialchars($log['email'] ?? '—') ?></td>
                <td><?= htmlspecialchars($log['activity']) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $log['activity_type'] ?></span></td>
                <td><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                <td><?= $log['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>