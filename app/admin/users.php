<?php
// ============================================================
// BCMS — ADMIN: USER MANAGEMENT
// ============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../includes/activity_logger.php';

requireRole('admin');

$db  = getDB();
$msg = $err = '';

// ── Handle actions ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $email    = trim($_POST['email']    ?? '');
        $pass     = trim($_POST['password'] ?? '');
        $role     = $_POST['role']  ?? 'user';
        $verified = isset($_POST['verified']) ? 1 : 0;
        $validRoles = ['admin','manager','user'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email address.';
        } elseif (strlen($pass) < 6) {
            $err = 'Password must be at least 6 characters.';
        } elseif (!in_array($role, $validRoles, true)) {
            $err = 'Invalid role.';
        } else {
            try {
                $hashed = password_hash($pass, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO users (email,password,role,verified) VALUES(:e,:p,:r,:v)");
                $stmt->execute([':e'=>$email,':p'=>$hashed,':r'=>$role,':v'=>$verified]);
                logActivity("Admin added user: $email ($role)", 'admin');
                $msg = "✅ User <strong>$email</strong> created.";
            } catch (PDOException $e) {
                $err = str_contains($e->getMessage(),'Duplicate') ? 'Email already exists.' : 'Error creating user.';
            }
        }
    }

    if ($action === 'delete') {
        $uid = intval($_POST['uid'] ?? 0);
        if ($uid === currentUserId()) {
            $err = 'You cannot delete your own account.';
        } elseif ($uid > 0) {
            $uRow = $db->prepare("SELECT email FROM users WHERE user_id=:u"); $uRow->execute([':u'=>$uid]); $uRow = $uRow->fetch();
            $db->prepare("DELETE FROM users WHERE user_id=:u")->execute([':u'=>$uid]);
            logActivity("Admin deleted user: " . ($uRow['email']??'#'.$uid), 'admin');
            $msg = '✅ User deleted.';
        }
    }

    if ($action === 'toggle_verify') {
        $uid  = intval($_POST['uid']      ?? 0);
        $curr = intval($_POST['verified'] ?? 0);
        $db->prepare("UPDATE users SET verified=:v WHERE user_id=:u")->execute([':v'=>1-$curr,':u'=>$uid]);
        logActivity("Admin toggled verified for user #$uid", 'admin');
        $msg = '✅ Verification status updated.';
    }
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'User Management';
renderHead($pageTitle);
?>

<div class="layout">
  <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div class="topbar-title">👥 User Management</div>
    </div>

    <div class="page-body">
      <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

      <!-- Add user form -->
      <div class="panel fade-up">
        <div class="panel-header"><span class="panel-title">➕ Add New User</span></div>
        <div class="panel-body">
          <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="sensor-form-grid">
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="user@bcms.io" required>
              </div>
              <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
              </div>
              <div class="form-group">
                <label>Role</label>
                <select name="role">
                  <option value="user">User</option>
                  <option value="manager">Manager</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="form-group">
                <label>Verified</label>
                <div style="display:flex;align-items:center;gap:8px;padding:10px 0;">
                  <input type="checkbox" name="verified" id="verified" value="1" checked style="width:auto;">
                  <label for="verified" style="font-size:.875rem;font-weight:400;text-transform:none;letter-spacing:0;">Account active</label>
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-primary">Create User</button>
          </form>
        </div>
      </div>

      <!-- Users table -->
      <div class="panel fade-up">
        <div class="panel-header">
          <span class="panel-title">Registered Users (<?= count($users) ?>)</span>
        </div>
        <div class="panel-body">
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>#</th><th>Email</th><th>Role</th><th>Verified</th><th>Created</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                  <td class="mono text-sm text-muted"><?= $u['user_id'] ?></td>
                  <td><?= e($u['email']) ?></td>
                  <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                  <td>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action"   value="toggle_verify">
                      <input type="hidden" name="uid"      value="<?= $u['user_id'] ?>">
                      <input type="hidden" name="verified" value="<?= $u['verified'] ?>">
                      <button type="submit" class="badge <?= $u['verified']?'badge-safe':'badge-leak' ?>" style="cursor:pointer;border:none;">
                        <?= $u['verified'] ? '✓ Active' : '✗ Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td class="text-sm text-muted"><?= $u['created_at'] ?></td>
                  <td>
                    <?php if ($u['user_id'] !== currentUserId()): ?>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete <?= e($u['email']) ?>? This cannot be undone.')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="uid"    value="<?= $u['user_id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted text-sm">(you)</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php renderFoot(); ?>
