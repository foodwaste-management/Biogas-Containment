<?php
require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = (int) $_SESSION['user_id'];
$email = $_SESSION['email'] ?? 'user@example.com';

// Handle logout
if (isset($_POST['logout'])) {
    $check = $conn->query("SELECT user_id FROM users WHERE user_id = $user_id LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, email, activity, activity_type, ip_address) VALUES (?, ?, 'User logged out', 'login', ?)");
        $stmt->bind_param("iss", $user_id, $email, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        $stmt->close();
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

// Fetch latest methane reading
$methane_level = 20;
$methane_status = 'SAFE';
$r = $conn->query("SELECT methane_ppm, status FROM methane_monitoring WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 1");
if ($r && $row = $r->fetch_assoc()) {
    $methane_level = $row['methane_ppm'];
    $methane_status = $row['status'];
}

// Fetch latest gas level
$gas_left = 65;
$r2 = $conn->query("SELECT gas_percentage FROM gas_level WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 1");
if ($r2 && $row2 = $r2->fetch_assoc()) {
    $gas_left = $row2['gas_percentage'];
}

// Fetch last 10 gas usage entries for chart
$chart_labels = [];
$chart_data = [];
$r3 = $conn->query("SELECT flow_rate, recorded_at FROM gas_usage WHERE user_id = $user_id ORDER BY recorded_at DESC LIMIT 10");
if ($r3) {
    $rows = [];
    while ($row3 = $r3->fetch_assoc())
        $rows[] = $row3;
    $rows = array_reverse($rows);
    foreach ($rows as $row3) {
        $chart_labels[] = date('H:i:s', strtotime($row3['recorded_at']));
        $chart_data[] = $row3['flow_rate'];
    }
}

// Fallback demo data
if (empty($chart_labels)) {
    $now = time();
    for ($i = 9; $i >= 0; $i--) {
        $chart_labels[] = date('H:i:s', $now - $i * 60);
        $chart_data[] = rand(4, 12);
    }
}

// Fetch total gas used for impact stats
$total_gas_used = 0;
$r4 = $conn->query("SELECT SUM(gas_used) as total_gas FROM gas_usage WHERE user_id = $user_id");
if ($r4 && $row4 = $r4->fetch_assoc()) {
    $total_gas_used = (float) $row4['total_gas'];
}

// Fallback demo total gas if 0
if ($total_gas_used == 0 && empty($chart_labels_check)) { // just use demo data when needed
    $total_gas_used = 14500; // 14.5m3 demo data
}

// Calculate Impact Stats
// 1000L (1 m3) biogas ≈ 0.46 kg LPG. LPG costs ~90 PHP/kg.
$lpg_saved_kg = ($total_gas_used / 1000) * 0.46;
$savings_php = $lpg_saved_kg * 90;
// 1000L biogas replacing fossil fuel avoids ~1.25 kg CO2 eq
$co2_saved_kg = ($total_gas_used / 1000) * 1.25;

$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);

// Methane display values
$methane_display = min($methane_level, 100);
$methane_color = $methane_level > 50 ? '#dc2626' : ($methane_level > 20 ? '#d97706' : '#16a34a');
$methane_label = $methane_level > 50 ? '⚠ Leak Detected' : ($methane_level > 20 ? '⚡ Caution' : '✔ Normal');
$methane_class = $methane_level > 50 ? 'status-danger' : ($methane_level > 20 ? 'status-warn' : 'status-safe');

// Gas display values
$gas_color = $gas_left < 20 ? '#dc2626' : ($gas_left < 40 ? '#d97706' : '#16a34a');
$gas_label = $gas_left < 20 ? '⚠ Critical Low' : ($gas_left < 40 ? '⚡ Running Low' : '✔ Sufficient');
$gas_class = $gas_left < 20 ? 'status-danger' : ($gas_left < 40 ? 'status-warn' : 'status-safe');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biogas Containment Monitoring System Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f0e8;
            color: #2d3748;
            min-height: 100vh;
        }

        /* ── Header ── */
        .header {
            background: #ffffff;
            border-bottom: 1px solid #e2d9c8;
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-toggle {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #4a5568;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-toggle:hover {
            background: #f5f0e8;
        }

        /* ── Sidebar ── */
        .user-sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 260px;
            height: 100vh;
            background: #ffffff;
            border-right: 1px solid #e2d9c8;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.08);
            z-index: 200;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 24px;
            display: flex;
            flex-direction: column;
        }

        .user-sidebar.open {
            left: 0;
        }

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            z-index: 150;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.open {
            display: block;
            opacity: 1;
        }

        .sidebar-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            align-self: flex-end;
            color: #a0aec0;
            margin-bottom: 20px;
            padding: 4px;
        }

        .sidebar-close:hover {
            color: #4a5568;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sidebar-nav a {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            font-size: 15px;
            padding: 12px 16px;
            border-radius: 8px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #f0fdf4;
            color: #16a34a;
        }

        .header-left h1 {
            font-size: 16px;
            font-weight: 700;
            color: #1a202c;
            letter-spacing: 0.01em;
        }

        .live-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #16a34a;
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
            animation: blink 1.8s ease-in-out infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-chip {
            font-size: 13px;
            color: #718096;
            background: #f5f0e8;
            border: 1px solid #e2d9c8;
            padding: 5px 14px;
            border-radius: 999px;
        }

        .btn-logout {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 7px 18px;
            background: #ffffff;
            color: #dc2626;
            border: 1.5px solid #dc2626;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.15s ease;
        }

        .btn-logout:hover {
            background: #dc2626;
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ── Container ── */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 24px;
            display: grid;
            gap: 22px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 22px;
        }

        /* ── Cards ── */
        .card {
            background: #ffffff;
            border: 1px solid #e2d9c8;
            border-radius: 12px;
            padding: 24px;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .card-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #a0aec0;
            margin-bottom: 18px;
        }

        /* ── Live badge ── */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 600;
            color: #16a34a;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            padding: 2px 9px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .live-badge-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #16a34a;
            animation: blink 1.2s ease-in-out infinite;
        }

        /* ── Chart meta ── */
        .chart-meta {
            display: flex;
            gap: 28px;
            margin-bottom: 18px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .meta-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #a0aec0;
        }

        .meta-val {
            font-size: 22px;
            font-weight: 700;
            color: #16a34a;
        }

        .meta-val span {
            font-size: 12px;
            font-weight: 400;
            color: #a0aec0;
        }

        #live-timestamp {
            font-size: 13px;
            font-weight: 500;
            color: #4a5568;
            margin-top: 2px;
        }

        .chart-canvas-wrap {
            position: relative;
            height: 210px;
        }

        /* ── SVG Gauge ── */
        .gauge-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            padding: 6px 0 4px;
        }

        .gauge-svg {
            overflow: visible;
        }

        .gauge-track {
            fill: none;
            stroke: #e2d9c8;
            stroke-width: 13;
            stroke-linecap: round;
        }

        .gauge-fill {
            fill: none;
            stroke-width: 13;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.9s cubic-bezier(.4, 0, .2, 1), stroke 0.6s;
        }

        .gauge-center-text {
            font-family: 'Inter', sans-serif;
            font-size: 28px;
            font-weight: 700;
            fill: #1a202c;
            text-anchor: middle;
            dominant-baseline: middle;
        }

        .gauge-unit {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            fill: #a0aec0;
            text-anchor: middle;
        }

        /* ── Status badge ── */
        .gauge-status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 999px;
            border: 1.5px solid;
        }

        .status-safe {
            color: #16a34a;
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .status-warn {
            color: #d97706;
            background: #fffbeb;
            border-color: #fde68a;
        }

        .status-danger {
            color: #dc2626;
            background: #fef2f2;
            border-color: #fecaca;
        }

        /* ── Responsive ── */
        @media (max-width: 680px) {

            .grid-2,
            .grid-3 {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 12px 16px;
            }

            .container {
                padding: 16px;
            }

            .chart-meta {
                gap: 16px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="user-sidebar" id="user-sidebar">
        <button class="sidebar-close" id="sidebar-close">✕</button>
        <div style="font-weight: 700; font-size: 18px; margin-bottom: 24px; color: #1a202c; padding-left: 10px;">BCMS
            Menu</div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="my_readings.php">My Readings</a>
        </nav>
    </aside>

    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="btn-toggle" id="btn-toggle">☰</button>
            <div class="live-dot" style="margin-left: 8px;"></div>
            <h1>Biogas Containment Monitoring System Dashboard</h1>
        </div>
        <div class="header-right">
            <span class="user-chip"><?php echo htmlspecialchars($email); ?></span>
            <form method="POST" style="margin:0">
                <a href="<?= BASE_URL ?>/logout.php" class="btn-logout">Logout</a>
            </form>
        </div>
    </header>

    <!-- Main -->
    <main class="container">

        <!-- Impact Stats -->
        <div class="grid-3" style="margin-bottom: 8px;">
            <div class="card" style="padding: 16px 20px;">
                <div class="card-title" style="margin-bottom: 12px; color: #16a34a;">💰 Money Saved</div>
                <div class="meta-val">₱<?php echo number_format($savings_php, 2) ?></div>
                <div style="font-size: 11px; color: #a0aec0; margin-top: 4px;">Est. savings vs LPG</div>
            </div>
            <div class="card" style="padding: 16px 20px;">
                <div class="card-title" style="margin-bottom: 12px; color: #16a34a;">🔥 LPG Equivalent</div>
                <div class="meta-val"><?php echo number_format($lpg_saved_kg, 1) ?><span> kg</span></div>
                <div style="font-size: 11px; color: #a0aec0; margin-top: 4px;">Fossil fuel offset</div>
            </div>
            <div class="card" style="padding: 16px 20px;">
                <div class="card-title" style="margin-bottom: 12px; color: #16a34a;">🌍 CO₂ Reduced</div>
                <div class="meta-val"><?php echo number_format($co2_saved_kg, 1) ?><span> kg</span></div>
                <div style="font-size: 11px; color: #a0aec0; margin-top: 4px;">Emissions prevented</div>
            </div>
        </div>

        <!-- Real-Time Gas Usage -->
        <div class="card">
            <div class="card-title">
                Real-Time Gas Usage
                <span class="live-badge"><span class="live-badge-dot"></span>Demo</span>
            </div>
            <div class="chart-meta">
                <div class="meta-item">
                    <span class="meta-label">Latest Flow</span>
                    <span class="meta-val" id="latest-val">—<span> m³/h</span></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Last Updated</span>
                    <span id="live-timestamp">—</span>
                </div>
            </div>
            <div class="chart-canvas-wrap">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <!-- Gauge Row -->
        <div class="grid-2">

            <!-- Methane Gauge -->
            <div class="card">
                <div class="card-title">Methane Leak Monitoring</div>
                <div class="gauge-wrap">
                    <svg class="gauge-svg" width="200" height="130" viewBox="0 0 200 120">
                        <path class="gauge-track" d="M 20,110 A 80,80 0 0,1 180,110" />
                        <path class="gauge-fill" id="methane-fill" d="M 20,110 A 80,80 0 0,1 180,110"
                            style="stroke:<?php echo $methane_color ?>" />
                        <text class="gauge-center-text" x="100" y="96"><?php echo $methane_level ?></text>
                        <text class="gauge-unit" x="100" y="114">ppm</text>
                    </svg>
                    <span class="gauge-status <?php echo $methane_class ?>"><?php echo $methane_label ?></span>
                </div>
            </div>

            <!-- Gas Level Gauge -->
            <div class="card">
                <div class="card-title">Gas Remaining in Barrel</div>
                <div class="gauge-wrap">
                    <svg class="gauge-svg" width="200" height="130" viewBox="0 0 200 120">
                        <path class="gauge-track" d="M 20,110 A 80,80 0 0,1 180,110" />
                        <path class="gauge-fill" id="gas-fill" d="M 20,110 A 80,80 0 0,1 180,110"
                            style="stroke:<?php echo $gas_color ?>" />
                        <text class="gauge-center-text" x="100" y="96"><?php echo $gas_left ?></text>
                        <text class="gauge-unit" x="100" y="114">%</text>
                    </svg>
                    <span class="gauge-status <?php echo $gas_class ?>"><?php echo $gas_label ?></span>
                </div>
            </div>

        </div>
    </main>

    <script>
        // ── Sidebar Toggle ────────────────────────────────────────────────
        const sidebar = document.getElementById('user-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const btnToggle = document.getElementById('btn-toggle');
        const btnClose = document.getElementById('sidebar-close');

        function toggleSidebar() {
            sidebar.classList.toggle('open');
            if (sidebar.classList.contains('open')) {
                overlay.style.display = 'block';
                // Trigger reflow
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

        // ── Gauge arcs ────────────────────────────────────────────────────
        (function () {
            const arcLen = Math.PI * 80;

            function setGauge(id, pct) {
                const p = document.getElementById(id);
                p.style.strokeDasharray = arcLen;
                p.style.strokeDashoffset = arcLen - pct * arcLen;
            }

            setGauge('methane-fill', Math.min(<?php echo $methane_display ?>, 100) / 100);
            setGauge('gas-fill', Math.min(Math.max(<?php echo $gas_left ?>, 0), 100) / 100);
        })();

        // ── Chart ─────────────────────────────────────────────────────────
        const labels = <?php echo $chart_labels_json ?>;
        const dataPoints = <?php echo $chart_data_json ?>;

        const ctx = document.getElementById('usageChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 210);
        gradient.addColorStop(0, 'rgba(22,163,74,0.18)');
        gradient.addColorStop(1, 'rgba(22,163,74,0)');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Flow Rate (m³/h)',
                    data: dataPoints,
                    borderColor: '#16a34a',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#16a34a',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    fill: true,
                    tension: 0.4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 350 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#ffffff',
                        borderColor: '#e2d9c8',
                        borderWidth: 1,
                        titleColor: '#a0aec0',
                        bodyColor: '#1a202c',
                        titleFont: { family: 'Inter', size: 11 },
                        bodyFont: { family: 'Inter', size: 13, weight: '600' },
                        callbacks: { label: c => ` ${c.parsed.y} m³/h` }
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#f0ebe0' },
                        ticks: { color: '#a0aec0', font: { family: 'Inter', size: 10 }, maxRotation: 0 }
                    },
                    y: {
                        grid: { color: '#f0ebe0' },
                        ticks: { color: '#a0aec0', font: { family: 'Inter', size: 10 } },
                        beginAtZero: true
                    }
                }
            }
        });

        // ── Live updates every 5s ─────────────────────────────────────────
        function nowStr() {
            return new Date().toLocaleTimeString('en-GB', { hour12: false });
        }

        function updateTimestamp() {
            document.getElementById('live-timestamp').textContent = nowStr();
        }

        function pushLivePoint() {
            const newVal = +(Math.random() * 6 + 4).toFixed(1);
            const nowLabel = nowStr();

            chart.data.labels.push(nowLabel);
            chart.data.datasets[0].data.push(newVal);

            if (chart.data.labels.length > 20) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }

            chart.update();
            document.getElementById('latest-val').innerHTML = newVal + '<span> m³/h</span>';
            updateTimestamp();
        }

        // Init
        const lastVal = dataPoints[dataPoints.length - 1];
        document.getElementById('latest-val').innerHTML = lastVal + '<span> m³/h</span>';
        updateTimestamp();

        setInterval(pushLivePoint, 5000);
    </script>
</body>

</html>