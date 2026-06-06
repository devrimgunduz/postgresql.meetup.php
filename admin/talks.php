<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/meetups.php';
require_login();

$meetup_id = isset($_GET['meetup_id']) && is_numeric($_GET['meetup_id']) ? (int)$_GET['meetup_id'] : null;
if (!$meetup_id) { header('Location: /admin/'); exit; }

$meetup = get_meetup($meetup_id);
if (!$meetup) { header('Location: /admin/'); exit; }

$langs = get_active_languages();

// Delete talk
if (isset($_GET['delete_talk']) && is_numeric($_GET['delete_talk'])) {
    delete_talk((int)$_GET['delete_talk']);
    header("Location: /admin/talks.php?meetup_id=$meetup_id");
    exit;
}

$edit_id   = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_talk = $edit_id ? get_talk($edit_id) : null;
$is_new    = !$edit_id && isset($_GET['new']);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid  = !empty($_POST['talk_id']) && is_numeric($_POST['talk_id']) ? (int)$_POST['talk_id'] : null;
    $data = [
        'meetup_id'         => $meetup_id,
        'sort_order'        => (int)($_POST['sort_order'] ?? 0),
        'speaker_name'      => $_POST['speaker_name'] ?? '',
        'speaker_photo_url' => $_POST['speaker_photo_url'] ?? '',
        'talk_duration_min' => !empty($_POST['talk_duration_min']) ? (int)$_POST['talk_duration_min'] : null,
    ];
    foreach ($langs as $l) {
        $code = $l['code'];
        foreach (['talk_title', 'talk_abstract', 'speaker_bio'] as $field) {
            $data["trans_{$code}_{$field}"] = $_POST["trans_{$code}_{$field}"] ?? '';
        }
    }
    try {
        save_talk($data, $tid);
        header("Location: /admin/talks.php?meetup_id=$meetup_id&saved=1");
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$talks = get_talks($meetup_id);
$saved_flash = isset($_GET['saved']);

// Get venue name for breadcrumb
$def_lang = get_default_lang();
$venue    = $meetup['translations'][$def_lang]['venue_name']
          ?? ($meetup['translations'][array_key_first($meetup['translations'])]['venue_name'] ?? "Meetup #$meetup_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Talks – Admin</title>
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
    <div>
      <div class="breadcrumb">
        <a href="/admin/">Meetups</a> /
        <a href="/admin/meetup-edit.php?id=<?= $meetup_id ?>"><?= h($venue) ?></a> / Talks
      </div>
      <h1 class="admin-page-title">Manage Talks</h1>
    </div>
    <a href="?meetup_id=<?= $meetup_id ?>&new=1" class="btn btn-primary">+ Add Talk</a>
  </div>

  <div class="admin-content">
    <?php if ($saved_flash): ?>
      <div class="alert alert-success">✓ Talk saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($talks)): ?>
    <table class="admin-table">
      <thead>
        <tr><th>#</th><th>Title (<?= h($def_lang) ?>)</th><th>Speaker</th><th>Duration</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($talks as $t): ?>
        <tr>
          <td><?= h($t['sort_order']) ?></td>
          <td><?= h($t['translations'][$def_lang]['talk_title'] ?? '—') ?></td>
          <td><?= h($t['speaker_name'] ?: '—') ?></td>
          <td><?= $t['talk_duration_min'] ? h($t['talk_duration_min']) . ' min' : '—' ?></td>
          <td class="actions">
            <a href="?meetup_id=<?= $meetup_id ?>&edit=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
            <a href="?meetup_id=<?= $meetup_id ?>&delete_talk=<?= $t['id'] ?>" class="btn btn-sm btn-danger"
               onclick="return confirm('Delete this talk?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <?php if ($is_new || $edit_talk):
      $t   = $edit_talk ?: [];
      $ttr = $edit_talk['translations'] ?? [];
    ?>
    <div class="edit-form-wrap">
      <h2 class="form-section-title"><?= $edit_talk ? 'Edit Talk' : 'Add Talk' ?></h2>
      <form method="POST" class="edit-form">
        <?php if ($edit_talk): ?>
          <input type="hidden" name="talk_id" value="<?= $edit_talk['id'] ?>">
        <?php endif; ?>

        <div class="form-section">
          <h3 class="form-section-title">Speaker</h3>
          <div class="form-row form-row-2">
            <label>Speaker Name
              <input type="text" name="speaker_name" value="<?= h($t['speaker_name'] ?? '') ?>">
            </label>
            <label>Speaker Photo URL
              <input type="url" name="speaker_photo_url" value="<?= h($t['speaker_photo_url'] ?? '') ?>">
            </label>
          </div>
          <div class="form-row form-row-2">
            <label>Duration (minutes)
              <input type="number" name="talk_duration_min" value="<?= h($t['talk_duration_min'] ?? '') ?>">
            </label>
            <label>Sort Order
              <input type="number" name="sort_order" value="<?= h($t['sort_order'] ?? count($talks)) ?>">
            </label>
          </div>
        </div>

        <div class="form-section">
          <h3 class="form-section-title">Translations</h3>
          <div class="lang-tabs">
            <?php foreach ($langs as $i => $l): ?>
              <button type="button" class="lang-tab <?= $i === 0 ? 'active' : '' ?>"
                      onclick="switchLangTab('talk-<?= h($l['code']) ?>', this)">
                <?= h($l['label']) ?>
              </button>
            <?php endforeach; ?>
          </div>
          <?php foreach ($langs as $i => $l):
            $code = $l['code'];
          ?>
          <div class="lang-panel" id="panel-talk-<?= h($code) ?>"
               style="<?= $i > 0 ? 'display:none' : '' ?>">
            <div class="form-row">
              <label>Talk Title
                <input type="text" name="trans_<?= h($code) ?>_talk_title"
                       value="<?= h($ttr[$code]['talk_title'] ?? '') ?>">
              </label>
            </div>
            <div class="form-row">
              <label>Abstract
                <textarea name="trans_<?= h($code) ?>_talk_abstract" rows="4"><?= h($ttr[$code]['talk_abstract'] ?? '') ?></textarea>
              </label>
            </div>
            <div class="form-row">
              <label>Speaker Bio
                <textarea name="trans_<?= h($code) ?>_speaker_bio" rows="3"><?= h($ttr[$code]['speaker_bio'] ?? '') ?></textarea>
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Talk</button>
          <a href="?meetup_id=<?= $meetup_id ?>" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
function switchLangTab(panelId, btn) {
  // Only toggle panels within the same tab group
  const group = btn.closest('.form-section');
  group.querySelectorAll('.lang-panel').forEach(p => p.style.display = 'none');
  group.querySelectorAll('.lang-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + panelId).style.display = 'block';
  btn.classList.add('active');
}
</script>
</body>
</html>
