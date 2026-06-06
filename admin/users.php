<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$success = $error = '';

// Delete user (can't delete yourself)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== $_SESSION['user_id']) {
        db()->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
    }
    header('Location: /admin/users.php');
    exit;
}

// Add or change password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        if (strlen($u) < 2 || strlen($p) < 8) {
            $error = 'Username must be ≥2 chars and password ≥8 chars.';
        } else {
            try {
                $hash = password_hash($p, PASSWORD_BCRYPT);
                db()->prepare("INSERT INTO users(username,password) VALUES(?,?)")->execute([$u,$hash]);
                $success = "User '$u' created.";
            } catch (Exception $e) {
                $error = 'Username already exists.';
            }
        }
    } elseif ($action === 'change_password') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $p   = $_POST['new_password'] ?? '';
        if (strlen($p) < 8) {
            $error = 'Password must be ≥8 characters.';
        } else {
            $hash = password_hash($p, PASSWORD_BCRYPT);
            db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash,$uid]);
            $success = 'Password updated.';
        }
    }
}

$users = db()->query("SELECT id, username, created_at FROM users ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users – Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<aside class="sidebar">
  <div class="sidebar-logo">🐘 pgistanbul</div>
  <nav class="sidebar-nav">
    <a href="/admin/" class="sn-link">📋 Meetups</a>
    <a href="/admin/meetup-edit.php" class="sn-link">➕ New Meetup</a>
    <a href="/admin/users.php" class="sn-link active">👤 Users</a>
    <a href="/" target="_blank" class="sn-link">🌐 View Site</a>
  </nav>
  <div class="sidebar-footer">
    Signed in as <strong><?= h($_SESSION['username']) ?></strong><br>
    <a href="/admin/logout.php">Sign out</a>
  </div>
</aside>

<div class="admin-main">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Users</h1>
  </div>

  <div class="admin-content">
    <?php if ($success): ?>
      <div class="alert alert-success">✓ <?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <table class="admin-table">
      <thead><tr><th>Username</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= h($u['username']) ?></td>
          <td><?= h((new DateTimeImmutable($u['created_at']))->format('Y-m-d')) ?></td>
          <td class="actions">
            <button class="btn btn-sm btn-secondary"
              onclick="document.getElementById('cpw-<?= $u['id'] ?>').style.display='block'">Change Password</button>
            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete user <?= h($u['username']) ?>?')">Delete</a>
            <?php endif; ?>
          </td>
        </tr>
        <tr id="cpw-<?= $u['id'] ?>" style="display:none">
          <td colspan="3">
            <form method="POST" style="display:flex;gap:.5rem;align-items:center;padding:.5rem 0">
              <input type="hidden" name="action" value="change_password">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="password" name="new_password" placeholder="New password (≥8 chars)" style="flex:1">
              <button type="submit" class="btn btn-sm btn-primary">Update</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="edit-form-wrap">
      <h2 class="form-section-title">Add New User</h2>
      <form method="POST" class="edit-form">
        <input type="hidden" name="action" value="add">
        <div class="form-row form-row-2">
          <label>Username
            <input type="text" name="username" required>
          </label>
          <label>Password
            <input type="password" name="password" placeholder="≥8 characters" required>
          </label>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>

  </div>
</div>

</body>
</html>
