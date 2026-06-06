<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/meetups.php';
require_login();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    delete_meetup((int)$_GET['delete']);
    header('Location: /admin/');
    exit;
}

$meetups  = all_meetups();
$def_lang = get_default_lang();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard – PostgreSQL İstanbul Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<aside class="sidebar">
  <div class="sidebar-logo">🐘 pgistanbul</div>
  <nav class="sidebar-nav">
    <a href="/admin/" class="sn-link active">📋 Meetups</a>
    <a href="/admin/meetup-edit.php" class="sn-link">➕ New Meetup</a>
    <a href="/admin/languages.php" class="sn-link">🌐 Languages</a>
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
    <h1 class="admin-page-title">Meetups</h1>
    <a href="/admin/meetup-edit.php" class="btn btn-primary">+ New Meetup</a>
  </div>
  <div class="admin-content">
    <?php if (empty($meetups)): ?>
      <div class="empty-state-admin">No meetups yet. <a href="/admin/meetup-edit.php">Create one</a>.</div>
    <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr><th>Date</th><th>Venue</th><th>Status</th><th>Talks</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($meetups as $m):
          $venue = $m['translations'][$def_lang]['venue_name']
                ?? (reset($m['translations'])['venue_name'] ?? '—');
          $talks = get_talks($m['id']);
        ?>
        <tr>
          <td><?= !empty($m['event_date'])
            ? h((new DateTimeImmutable($m['event_date']))->format('Y-m-d H:i'))
            : '—' ?></td>
          <td><?= h($venue) ?></td>
          <td><span class="badge badge-<?= h($m['status']) ?>"><?= h($m['status']) ?></span></td>
          <td><?= count($talks) ?></td>
          <td class="actions">
            <a href="/admin/meetup-edit.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
            <a href="/admin/talks.php?meetup_id=<?= $m['id'] ?>" class="btn btn-sm btn-secondary">Talks</a>
            <a href="/admin/?delete=<?= $m['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this meetup and all its talks?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
