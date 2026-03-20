<?php
// ============================================================
// BCMS — LOGIN PAGE (index.php)
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/includes/activity_logger.php';

// Already logged in → redirect
if (isLoggedIn()) {
    redirect(BASE_URL . '/app/' . $_SESSION['role'] . '/dashboard.php');
}

$error = '';
$msg   = e($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $db   = getDB();
            // ── CHANGE: added display_name to SELECT (was SELECT *) ──
            $stmt = $db->prepare("SELECT user_id, email, display_name, password, role, verified FROM users WHERE email = :email AND verified = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true); // prevent session fixation

                // ── CHANGE: added display_name to session ──
                $_SESSION['user_id']      = $user['user_id'];
                $_SESSION['email']        = $user['email'];
                $_SESSION['display_name'] = $user['display_name']; // ← NEW
                $_SESSION['role']         = $user['role'];

                logActivity('User logged in', 'login', $user['user_id'], $user['email']);
                redirect(BASE_URL . '/app/' . $user['role'] . '/dashboard.php');
            } else {
                $error = 'Invalid email or password.';
                logActivity('Failed login attempt for: ' . $email, 'login', null, $email);
            }
        } catch (PDOException $e) {
            $error = 'A system error occurred. Please try again.';
            error_log('[BCMS Login] ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
  <style>
    .login-left {
      display: none;
      flex: 1;
      background: var(--green-900);
      position: relative;
      overflow: hidden;
      align-items: center;
      justify-content: center;
      padding: 40px;
    }
    @media(min-width:900px){ .login-left{ display:flex; } }

    .login-left-inner { position: relative; z-index: 1; text-align: center; }
    .login-left h2 {
      font-family: var(--font-serif);
      font-size: 2.4rem;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 16px;
    }
    .login-left p { color: var(--green-300); font-size: .95rem; max-width: 340px; line-height: 1.7; }

    .blob {
      position: absolute;
      border-radius: 50%;
      opacity: .12;
      background: var(--green-400);
    }
    .blob-1 { width:300px;height:300px; top:-80px; left:-80px; }
    .blob-2 { width:200px;height:200px; bottom:-50px; right:-50px; }
    .blob-3 { width:120px;height:120px; top:40%; right:20%; }

    .login-features { margin-top: 36px; display:flex; flex-direction:column; gap:12px; }
    .login-feature {
      display:flex; align-items:center; gap:12px;
      background:rgba(255,255,255,.07);
      border-radius:10px; padding:12px 16px; text-align:left;
    }
    .login-feature-icon { font-size:1.4rem; }
    .login-feature-text { color:rgba(255,255,255,.85); font-size:.85rem; line-height:1.4; }
    .login-feature-text strong { color:#fff; display:block; font-size:.9rem; }
  </style>
</head>
<body class="login-page">

  <div class="login-left">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="login-left-inner fade-up">
      <div style="font-size:3.5rem;margin-bottom:16px;">🫧</div>
      <h2>Biogas Containment<br><em>Monitoring System</em></h2>
      <p>Real-time tracking of methane levels, gas flow rates, and containment integrity for safer biogas operations.</p>

      <div class="login-features stagger">
        <div class="login-feature fade-up">
          <span class="login-feature-icon">📊</span>
          <div class="login-feature-text">
            <strong>Gas Usage Monitoring</strong>
            Track flow rate and total gas consumed.
          </div>
        </div>
        <div class="login-feature fade-up">
          <span class="login-feature-icon">⚠️</span>
          <div class="login-feature-text">
            <strong>Methane Leak Detection</strong>
            Automated SAFE / WARNING / LEAK alerts.
          </div>
        </div>
        <div class="login-feature fade-up">
          <span class="login-feature-icon">🫙</span>
          <div class="login-feature-text">
            <strong>Gas Level &amp; Pressure</strong>
            Monitor tank percentage and kPa readings.
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="login-panel">
    <div class="login-card fade-up">
      <div class="login-brand">
        <div class="login-brand-icon">🫧</div>
        <div>
          <h1><?= APP_NAME ?></h1>
          <p><?= APP_SHORT ?> v<?= APP_VERSION ?></p>
        </div>
      </div>

      <h2>Welcome back</h2>
      <p class="subtitle">Sign in to access your monitoring dashboard.</p>

      <?php if ($msg): ?>
        <div class="alert alert-info"><?= $msg ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 placeholder="you@example.com"
                 value="<?= isset($email) ? e($email) : '' ?>"
                 required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-full">
          Sign In →
        </button>
      </form>

      <p style="margin-top:24px;font-size:.78rem;color:var(--text-muted);text-align:center;">
        Default credentials: <span class="mono">admin@bcms.io / password123</span>
      </p>
    </div>
  </div>

</body>
</html>
