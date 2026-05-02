<?php
// ============================================================
// BCMS — GLOBAL HELPER FUNCTIONS
// ============================================================

// ── Auth Helpers ──────────────────────────────────────────────
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/index.php?msg=Please+log+in+to+continue.');
    }
}

function requireRole(string $role): void
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        die(renderAccessDenied($role));
    }
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function currentRole(): string
{
    return $_SESSION['role'] ?? '';
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentEmail(): string
{
    return $_SESSION['email'] ?? '';
}

// ── Methane Status Helper ────────────────────────────────────
function methaneStatus(float $ppm): string
{
    if ($ppm < METHANE_SAFE)
        return 'SAFE';
    if ($ppm < METHANE_WARNING)
        return 'WARNING';
    return 'LEAK';
}

function methaneStatusClass(string $status): string
{
    return match ($status) {
        'SAFE' => 'badge-safe',
        'WARNING' => 'badge-warning',
        'LEAK' => 'badge-leak',
        default => 'badge-safe',
    };
}

// ── XSS ──────────────────────────────────────────────────────
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Access Denied Page ───────────────────────────────────────
function renderAccessDenied(string $role): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <title>Access Denied — BCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
      body{background:#f5f0e8;font-family:"DM Sans",sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
      .box{background:#fff;border:2px solid #b5c4a1;border-radius:12px;padding:48px;text-align:center;max-width:400px;}
      h1{font-family:"DM Serif Display",serif;color:#2d4a1e;margin:0 0 12px;}
      p{color:#5a6b4e;margin:0 0 24px;}
      a{display:inline-block;background:#4a7c3f;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:500;}
      a:hover{background:#2d4a1e;}
    </style></head><body>
    <div class="box">
      <h1>⛔ Access Denied</h1>
      <p>You need <strong>' . e($role) . '</strong> privileges to view this page.</p>
      <a href="' . BASE_URL . '/index.php">Back to Login</a>
    </div></body></html>';
}

// ── Render shared HTML head ───────────────────────────────────
function renderHead(string $title, string $extraCss = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> — <?= APP_SHORT ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap"
            rel="stylesheet">
        <?php if ($extraCss)
            echo $extraCss; ?>
    </head>

    <body class="<?= currentRole() ?>-theme">
        <?php
}

function renderFoot(string $extraJs = ''): void
{
    ?>
        <?php if ($extraJs)
            echo $extraJs; ?>
    </body>

    </html>
    <?php
}

// ── Sidebar nav helper ───────────────────────────────────────
function navLink(string $href, string $icon, string $label): void
{
    $active = (basename($_SERVER['PHP_SELF']) === basename($href)) ? 'active' : '';
    echo '<a href="' . BASE_URL . $href . '" class="nav-link ' . $active . '">'
        . '<span class="nav-icon">' . $icon . '</span>'
        . '<span>' . e($label) . '</span>'
        . '</a>';
}
