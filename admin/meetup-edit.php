<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/meetups.php';
require_login();

$id     = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$meetup = $id ? get_meetup($id) : null;
$is_new = $meetup === null;
$langs  = get_active_languages();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'status'           => $_POST['status'] ?? 'draft',
            'event_date'       => !empty($_POST['event_date']) ? $_POST['event_date'] : null,
            'event_end'        => !empty($_POST['event_end']) ? $_POST['event_end'] : null,
            'venue_address'    => $_POST['venue_address'] ?? '',
            'venue_map_url'    => $_POST['venue_map_url'] ?? '',
            'registration_url' => $_POST['registration_url'] ?? '',
        ];
        // Collect all translation fields from POST
        foreach ($langs as $l) {
            $code = $l['code'];
            foreach (['meetup_title', 'notes'] as $field) {
                $key        = "trans_{$code}_{$field}";
                $data[$key] = $_POST[$key] ?? '';
            }
        }
        $saved_id = save_meetup($data, $id);
        if ($is_new) {
            header("Location: /admin/meetup-edit.php?id=$saved_id&saved=1");
            exit;
        }
        $meetup  = get_meetup($saved_id);
        $id      = $saved_id;
        $success = 'Meetup saved.';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$v  = $meetup ?? [];
$tr = $meetup['translations'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $is_new ? 'New Meetup' : 'Edit Meetup' ?> – Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<aside class="sidebar">
  <div class="sidebar-logo">🐘 pgistanbul</div>
  <nav class="sidebar-nav">
    <a href="/admin/" class="sn-link">📋 Meetups</a>
    <a href="/admin/meetup-edit.php" class="sn-link <?= $is_new ? 'active' : '' ?>">➕ New Meetup</a>
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
    <h1 class="admin-page-title"><?= $is_new ? 'New Meetup' : 'Edit Meetup' ?></h1>
    <?php if (!$is_new): ?>
      <a href="/admin/talks.php?meetup_id=<?= $id ?>" class="btn btn-secondary">Manage Talks →</a>
    <?php endif; ?>
  </div>
  <div class="admin-content">

    <?php if (isset($_GET['saved']) || $success): ?>
      <div class="alert alert-success">✓ Meetup saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="edit-form">

      <div class="form-section">
        <h2 class="form-section-title">Status &amp; Date</h2>
        <div class="form-row form-row-2">
          <label>Status
            <select name="status">
              <?php foreach (['draft', 'published', 'past'] as $s): ?>
                <option value="<?= $s ?>" <?= ($v['status'] ?? 'draft') === $s ? 'selected' : '' ?>>
                  <?= ucfirst($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="hint">Only <em>published</em> meetups appear on the homepage.</span>
          </label>
          <label>Event Start
            <input type="datetime-local" name="event_date"
              value="<?= !empty($v['event_date'])
                ? h((new DateTimeImmutable($v['event_date']))->format('Y-m-d\TH:i'))
                : '' ?>">
          </label>
          <label>Event End <span class="hint">(optional)</span>
            <input type="datetime-local" name="event_end"
              value="<?= !empty($v['event_end'])
                ? h((new DateTimeImmutable($v['event_end']))->format('Y-m-d\TH:i'))
                : '' ?>">
          </label>
        </div>
      </div>

      <div class="form-section">
        <h2 class="form-section-title">Venue</h2>
        <div class="form-row form-row-2">
          <label>Address <span class="hint">(not translated — same in all languages)</span>
            <input type="text" name="venue_address" value="<?= h($v['venue_address'] ?? '') ?>">
          </label>
          <label>Map URL
            <input type="url" name="venue_map_url" value="<?= h($v['venue_map_url'] ?? '') ?>">
          </label>
        </div>
      </div>

      <div class="form-section">
        <h2 class="form-section-title">Registration</h2>
        <label>Registration URL
          <input type="url" name="registration_url" value="<?= h($v['registration_url'] ?? '') ?>">
        </label>
      </div>

      <!-- Per-language translation fields -->
      <div class="form-section">
        <h2 class="form-section-title">Translations</h2>
        <div class="lang-tabs">
          <?php foreach ($langs as $i => $l): ?>
            <button type="button" class="lang-tab <?= $i === 0 ? 'active' : '' ?>"
                    onclick="switchLangTab('<?= h($l['code']) ?>', this)">
              <?= h($l['label']) ?>
            </button>
          <?php endforeach; ?>
        </div>
        <?php foreach ($langs as $i => $l):
          $code = $l['code'];
        ?>
        <div class="lang-panel" id="panel-<?= h($code) ?>"
             style="<?= $i > 0 ? 'display:none' : '' ?>">
          <div class="form-row">
            <label>Meetup Title
              <input type="text" name="trans_<?= h($code) ?>_meetup_title"
                     value="<?= h($tr[$code]['meetup_title'] ?? '') ?>">
            </label>
          </div>
          <div class="form-row">
            <label>Notes / Additional Info
              <textarea name="trans_<?= h($code) ?>_notes" rows="5"><?= h($tr[$code]['notes'] ?? '') ?></textarea>
            </label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save Meetup</button>
        <a href="/admin/" class="btn btn-ghost">Cancel</a>
      </div>

    </form>
  </div>
</div>

<script>
function switchLangTab(code, btn) {
  document.querySelectorAll('.lang-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.lang-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + code).style.display = 'block';
  btn.classList.add('active');
}
</script>
</body>
</html>
