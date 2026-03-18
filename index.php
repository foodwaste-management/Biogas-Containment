<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';

$error = '';

// Already logged in → redirect
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: /app/$role/dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verified = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        logActivity($pdo, $user['user_id'], $user['email'], 'User logged in', 'login');

        header("Location: /app/{$user['role']}/dashboard.php");
        exit;
    } else {
        logActivity($pdo, null, $email, 'Failed login attempt', 'login_failed');
        $error = "Wrong email/password or account not verified.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BCMS — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --green:    #2ecc71;
    --green-dk: #27ae60;
    --dark:     #0f1a12;
    --card:     #172419;
    --border:   #2a3d2d;
    --text:     #d4e6d7;
    --muted:    #6b8f72;
    --error:    #e74c3c;
}

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--dark);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

/* animated background grid */
body::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(var(--border) 1px, transparent 1px),
        linear-gradient(90deg, var(--border) 1px, transparent 1px);
    background-size: 40px 40px;
    opacity: 0.4;
}

body::after {
    content: '';
    position: absolute;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(46,204,113,0.12) 0%, transparent 70%);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
}

.card {
    position: relative;
    z-index: 1;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 48px 40px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.5);
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 32px;
}

.logo-icon {
    width: 36px; height: 36px;
    background: var(--green);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.logo-text {
    font-family: 'Space Mono', monospace;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.5px;
}

.logo-text span { color: var(--green); }

h2 {
    font-size: 22px;
    font-weight: 500;
    color: #fff;
    margin-bottom: 6px;
}

.subtitle {
    font-size: 13px;
    color: var(--muted);
    margin-bottom: 28px;
}

.form-group { margin-bottom: 16px; }

label {
    display: block;
    font-size: 12px;
    font-weight: 500;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 12px 14px;
    background: var(--dark);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    transition: border-color 0.2s;
    outline: none;
}

input:focus { border-color: var(--green); }

.btn {
    width: 100%;
    padding: 13px;
    background: var(--green);
    color: #0f1a12;
    border: none;
    border-radius: 8px;
    font-family: 'Space Mono', monospace;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: background 0.2s, transform 0.1s;
    margin-top: 8px;
}

.btn:hover  { background: var(--green-dk); }
.btn:active { transform: scale(0.98); }

.error-msg {
    background: rgba(231,76,60,0.1);
    border: 1px solid rgba(231,76,60,0.3);
    color: var(--error);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 18px;
}

.hint {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    font-size: 12px;
    color: var(--muted);
    line-height: 1.8;
}

.hint strong { color: var(--text); }
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-icon">🌿</div>
        <div class="logo-text">BC<span>MS</span></div>
    </div>

    <h2>Welcome back</h2>
    <p class="subtitle">Biogas & Composting Monitoring System</p>

    <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required autocomplete="email">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn">Sign In →</button>
    </form>

    <div class="hint">
        <strong>Test accounts</strong><br>
        admin@example.com &nbsp;|&nbsp; manager@example.com &nbsp;|&nbsp; user@example.com<br>
        Password: <em>password123</em>
    </div>
</div>
</body>
</html>