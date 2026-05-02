<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "foodwaste_db");

$user_id = (int) $_SESSION['user_id'];
$email = $_SESSION['email'] ?? 'user@example.com';

// Fetch Methane Logs
$methane_logs = [];
$r1 = $conn->query("SELECT methane_ppm, status, recorded_at FROM methane_monitoring WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 100");
if ($r1) {
    while ($row = $r1->fetch_assoc()) $methane_logs[] = $row;
}

// Fetch Gas Usage Logs
$usage_logs = [];
$r2 = $conn->query("SELECT flow_rate, gas_used, recorded_at FROM gas_usage WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 100");
if ($r2) {
    while ($row = $r2->fetch_assoc()) $usage_logs[] = $row;
}

// Fetch Gas Level Logs
$level_logs = [];
$r3 = $conn->query("SELECT gas_percentage, recorded_at FROM gas_level WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 100");
if ($r3) {
    while ($row = $r3->fetch_assoc()) $level_logs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Readings - BCMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Base CSS */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f5f0e8; color: #2d3748; min-height: 100vh; }
        
        /* Header */
        .header { background: #ffffff; border-bottom: 1px solid #e2d9c8; padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06); }
        .header-left { display: flex; align-items: center; gap: 10px; }
        .header-left h1 { font-size: 16px; font-weight: 700; color: #1a202c; letter-spacing: 0.01em; }
        .btn-toggle { background: none; border: none; font-size: 22px; cursor: pointer; color: #4a5568; padding: 4px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: background 0.2s; }
        .btn-toggle:hover { background: #f5f0e8; }
        .header-right { display: flex; align-items: center; gap: 14px; }
        .user-chip { font-size: 13px; color: #718096; background: #f5f0e8; border: 1px solid #e2d9c8; padding: 5px 14px; border-radius: 999px; }
        .btn-logout { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; padding: 7px 18px; background: #ffffff; color: #dc2626; border: 1.5px solid #dc2626; border-radius: 6px; cursor: pointer; text-decoration: none; transition: background 0.2s ease, color 0.2s ease, transform 0.15s ease; }
        .btn-logout:hover { background: #dc2626; color: #ffffff; transform: translateY(-1px); }

        /* Sidebar */
        .user-sidebar { position: fixed; top: 0; left: -280px; width: 260px; height: 100vh; background: #ffffff; border-right: 1px solid #e2d9c8; box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08); z-index: 200; transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1); padding: 24px; display: flex; flex-direction: column; }
        .user-sidebar.open { left: 0; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); z-index: 150; display: none; opacity: 0; transition: opacity 0.3s ease; }
        .sidebar-overlay.open { display: block; opacity: 1; }
        .sidebar-close { background: none; border: none; font-size: 20px; cursor: pointer; align-self: flex-end; color: #a0aec0; margin-bottom: 20px; padding: 4px; }
        .sidebar-close:hover { color: #4a5568; }
        .sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
        .sidebar-nav a { text-decoration: none; color: #4a5568; font-weight: 500; font-size: 15px; padding: 12px 16px; border-radius: 8px; transition: background 0.2s, color 0.2s; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: #f0fdf4; color: #16a34a; }

        /* Container & Cards */
        .container { max-width: 1100px; margin: 0 auto; padding: 28px 24px; }
        .card { background: #ffffff; border: 1px solid #e2d9c8; border-radius: 12px; padding: 24px; transition: box-shadow 0.2s ease; margin-bottom: 22px; }
        .card-title { font-size: 14px; font-weight: 700; color: #1a202c; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }

        /* Tabs */
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid #e2d9c8; padding-bottom: 8px; }
        .tab-btn { background: none; border: none; font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 600; color: #718096; padding: 8px 16px; cursor: pointer; border-radius: 6px; transition: background 0.2s, color 0.2s; }
        .tab-btn:hover { background: #f5f0e8; color: #4a5568; }
        .tab-btn.active { background: #f0fdf4; color: #16a34a; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Table */
        .table-wrap { overflow-x: auto; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .data-table th { padding: 12px 16px; color: #a0aec0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2d9c8; }
        .data-table td { padding: 14px 16px; color: #4a5568; border-bottom: 1px solid #f5f0e8; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: #faf8f5; }
        .badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; }
        .badge-safe { background: #f0fdf4; color: #16a34a; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-leak { background: #fef2f2; color: #dc2626; }
        .mono { font-family: monospace; font-size: 14px; }
        .text-muted { color: #a0aec0; }

        @media (max-width: 680px) {
            .header { padding: 12px 16px; }
            .container { padding: 16px; }
            .tabs { overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="user-sidebar" id="user-sidebar">
        <button class="sidebar-close" id="sidebar-close">✕</button>
        <div style="font-weight: 700; font-size: 18px; margin-bottom: 24px; color: #1a202c; padding-left: 10px;">BCMS Menu</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="my_readings.php" class="active">My Readings</a>
        </nav>
    </aside>

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="btn-toggle" id="btn-toggle">☰</button>
            <h1>My Readings History</h1>
        </div>
        <div class="header-right">
            <span class="user-chip"><?php echo htmlspecialchars($email); ?></span>
            <a href="/Biogas-Containment/logout.php" class="btn-logout">Logout</a>
        </div>
    </header>

    <!-- Main -->
    <main class="container">
        <div class="card">
            <div class="card-title">Sensor Activity Logs</div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="openTab('tab-methane', this)">Methane Readings</button>
                <button class="tab-btn" onclick="openTab('tab-usage', this)">Gas Usage</button>
                <button class="tab-btn" onclick="openTab('tab-level', this)">Gas Levels</button>
            </div>

            <!-- Methane Tab -->
            <div id="tab-methane" class="tab-content active">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Recorded At</th>
                                <th>Methane (ppm)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($methane_logs)): ?>
                                <tr><td colspan="3" style="text-align: center;" class="text-muted">No methane readings found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($methane_logs as $log): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $log['recorded_at']; ?></td>
                                        <td class="mono"><?php echo number_format($log['methane_ppm'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $s = strtolower($log['status']);
                                            $c = ($s == 'safe') ? 'safe' : (($s == 'leak') ? 'leak' : 'warning');
                                            echo "<span class='badge badge-$c'>{$log['status']}</span>";
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usage Tab -->
            <div id="tab-usage" class="tab-content">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Recorded At</th>
                                <th>Flow Rate (m³/h)</th>
                                <th>Gas Used (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usage_logs)): ?>
                                <tr><td colspan="3" style="text-align: center;" class="text-muted">No usage logs found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usage_logs as $log): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $log['recorded_at']; ?></td>
                                        <td class="mono"><?php echo number_format($log['flow_rate'], 2); ?></td>
                                        <td class="mono"><?php echo number_format($log['gas_used'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Level Tab -->
            <div id="tab-level" class="tab-content">
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Recorded At</th>
                                <th>Gas Level (%)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($level_logs)): ?>
                                <tr><td colspan="3" style="text-align: center;" class="text-muted">No gas level logs found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($level_logs as $log): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $log['recorded_at']; ?></td>
                                        <td class="mono"><?php echo number_format($log['gas_percentage'], 2); ?>%</td>
                                        <td>
                                            <?php 
                                            $lvl = $log['gas_percentage'];
                                            $c = ($lvl < 20) ? 'leak' : (($lvl < 40) ? 'warning' : 'safe');
                                            $lbl = ($lvl < 20) ? 'CRITICAL' : (($lvl < 40) ? 'LOW' : 'SUFFICIENT');
                                            echo "<span class='badge badge-$c'>$lbl</span>";
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Sidebar logic
        const sidebar = document.getElementById('user-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const btnToggle = document.getElementById('btn-toggle');
        const btnClose = document.getElementById('sidebar-close');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            if (sidebar.classList.contains('open')) {
                overlay.style.display = 'block';
                void overlay.offsetWidth;
                overlay.classList.add('open');
            } else {
                overlay.classList.remove('open');
                setTimeout(() => overlay.style.display = 'none', 300);
            }
        }
        btnToggle.addEventListener('click', toggleSidebar);
        btnClose.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // Tab logic
        function openTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }
    </script>
</body>
</html>
