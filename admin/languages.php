<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$success = $error = '';

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    // Don't delete if it's the only active language
    $count = db()->query("SELECT count(*) FROM languages WHERE is_active = TRUE")->fetchColumn();
    $isdef = db()->prepare("SELECT is_default FROM languages WHERE id = ?");
    $isdef->execute([$del]);
    $row = $isdef->fetch();
    if ($row && $row['is_default']) {
        $error = 'Cannot delete the default language. Set another language as default first.';
    } elseif ($count <= 1) {
        $error = 'Cannot delete the only active language.';
    } else {
        db()->prepare("DELETE FROM languages WHERE id = ?")->execute([$del]);
        header('Location: /admin/languages.php');
        exit;
    }
}

// Set default
if (isset($_GET['set_default']) && is_numeric($_GET['set_default'])) {
    db()->exec("UPDATE languages SET is_default = FALSE");
    db()->prepare("UPDATE languages SET is_default = TRUE, is_active = TRUE WHERE id = ?")
        ->execute([(int)$_GET['set_default']]);
    header('Location: /admin/languages.php');
    exit;
}

// Toggle active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tid = (int)$_GET['toggle'];
    $row = db()->prepare("SELECT is_default, is_active FROM languages WHERE id = ?");
    $row->execute([$tid]);
    $lang = $row->fetch();
    if ($lang['is_default']) {
        $error = 'Cannot deactivate the default language.';
    } else {
        db()->prepare("UPDATE languages SET is_active = NOT is_active WHERE id = ?")
            ->execute([$tid]);
        header('Location: /admin/languages.php');
        exit;
    }
}

// Add language
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code  = strtolower(trim($_POST['code'] ?? ''));
    $label = trim($_POST['label'] ?? '');
    $order = (int)($_POST['sort_order'] ?? 99);

    if (strlen($code) < 2 || strlen($code) > 10 || !preg_match('/^[a-z_-]+$/', $code)) {
        $error = 'Code must be 2–10 lowercase letters (e.g. en, tr, de, zh-hans).';
    } elseif (strlen($label) < 1) {
        $error = 'Label is required.';
    } else {
        try {
            db()->prepare(
                "INSERT INTO languages (code, label, sort_order) VALUES (?, ?, ?)"
            )->execute([$code, $label, $order]);
            $success = "Language '$label' ($code) added.";
        } catch (Exception $e) {
            $error = "Language code '$code' already exists.";
        }
    }
}

$languages = db()->query(
    "SELECT * FROM languages ORDER BY sort_order, id"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Languages – Admin</title>
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
    <a href="/admin/languages.php" class="sn-link active">🌐 Languages</a>
    <a href="/admin/users.php" class="sn-link">👤 Users</a>
    <a href="/" target="_blank" class="sn-link">🌐 View Site</a>
  </nav>
  <div class="sidebar-footer">
    Signed in as <strong><?= h($_SESSION['username']) ?></strong><br>
    <a href="/admin/logout.php">Sign out</a>
  </div>
</aside>

<div class="admin-main">
  <div class="admin-topbar">
    <h1 class="admin-page-title">Languages</h1>
  </div>
  <div class="admin-content">

    <?php if ($success): ?>
      <div class="alert alert-success">✓ <?= h($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <table class="admin-table">
      <thead>
        <tr><th>Code</th><th>Label</th><th>Order</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($languages as $l): ?>
        <tr>
          <td><code><?= h($l['code']) ?></code></td>
          <td><?= h($l['label']) ?></td>
          <td><?= h($l['sort_order']) ?></td>
          <td>
            <?php if ($l['is_default']): ?>
              <span class="badge badge-published">default</span>
            <?php elseif ($l['is_active']): ?>
              <span class="badge badge-published">active</span>
            <?php else: ?>
              <span class="badge badge-past">inactive</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <?php if (!$l['is_default']): ?>
              <a href="?set_default=<?= $l['id'] ?>" class="btn btn-sm btn-secondary"
                 onclick="return confirm('Set <?= h($l['label']) ?> as default?')">Set Default</a>
              <a href="?toggle=<?= $l['id'] ?>" class="btn btn-sm btn-secondary">
                <?= $l['is_active'] ? 'Deactivate' : 'Activate' ?>
              </a>
              <a href="?delete=<?= $l['id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Delete <?= h($l['label']) ?>? All translations for this language will be lost.')">Delete</a>
            <?php else: ?>
              <span class="muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="edit-form-wrap">
      <h2 class="form-section-title">Add Language</h2>
      <form method="POST" class="edit-form">
        <div class="form-row form-row-2">
          <label>Language Code
            <input type="text" name="code" placeholder="e.g. de, fr, ar, zh-hans" required>
            <span class="hint">Lowercase letters only. Used in ?lang= URL parameter.</span>
          </label>
          <label>Display Label
            <input type="text" name="label" placeholder="e.g. Deutsch, Français, العربية" required>
          </label>
        </div>
        <div class="form-row">
          <label style="max-width:200px">Sort Order
            <input type="number" name="sort_order" value="<?= count($languages) + 1 ?>">
          </label>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Add Language</button>
        </div>
      </form>
    </div>

  </div>
</div>
</body>
</html>
