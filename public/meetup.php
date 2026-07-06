<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/meetups.php';

$lang  = current_lang();
$langs = get_active_languages();
$id    = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$meetup = $id ? get_public_meetup($id) : null;
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>
  <?php if ($meetup): ?>
    <?= h(t_meetup($meetup['translations'], 'meetup_title', $lang)) ?> – PostgreSQL İstanbul
  <?php else: ?>
    PostgreSQL İstanbul
  <?php endif; ?>
</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="/?lang=<?= h($lang) ?>" class="logo">
      <span class="logo-elephant">🐘</span>
      <span class="logo-text">PostgreSQL<br><strong>İstanbul</strong></span>
    </a>
    <nav class="site-nav">
      <a href="/?lang=<?= h($lang) ?>" class="nav-link">
        <?= $lang === 'tr' ? 'Ana Sayfa' : 'Home' ?>
      </a>
      <a href="/previous.php?lang=<?= h($lang) ?>" class="nav-link">
        <?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?>
      </a>
      <?php if (count($langs) > 1): ?>
      <div class="lang-switcher">
        <?php foreach ($langs as $l): ?>
          <?php if ($l['code'] !== $lang): ?>
            <a href="?id=<?= h($id) ?>&lang=<?= h($l['code']) ?>" class="nav-link lang-switch"><?= h($l['label']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="site-main">
<?php if ($meetup):
  $hero_label = $meetup['status'] === 'past'
    ? ($lang === 'tr' ? 'Geçmiş Etkinlik' : 'Past Meetup')
    : ($lang === 'tr' ? 'Sonraki Etkinlik' : 'Next Meetup');
  require __DIR__ . '/partials/meetup-detail.php';
?>

  <section class="notes-section">
    <div class="container">
      <a href="/previous.php?lang=<?= h($lang) ?>" class="back-link">
        ← <?= $lang === 'tr' ? 'Tüm geçmiş etkinlikler' : 'All past meetups' ?>
      </a>
    </div>
  </section>

<?php else: ?>

  <section class="hero hero--empty">
    <div class="container">
      <div class="elephant-large">🐘</div>
      <h1 class="hero-empty-title">
        <?= $lang === 'tr' ? 'Etkinlik bulunamadı' : 'Meetup not found' ?>
      </h1>
      <p class="hero-empty-sub">
        <?= $lang === 'tr'
          ? 'Aradığınız etkinlik mevcut değil ya da kaldırılmış olabilir.'
          : 'This meetup doesn\'t exist or may have been removed.' ?>
      </p>
      <div class="social-links">
        <a href="/previous.php?lang=<?= h($lang) ?>" class="social-btn">
          <?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?>
        </a>
        <a href="/?lang=<?= h($lang) ?>" class="social-btn">
          <?= $lang === 'tr' ? 'Ana Sayfa' : 'Home' ?>
        </a>
      </div>
    </div>
  </section>

<?php endif; ?>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>© <?= date('Y') ?> PostgreSQL İstanbul</span>
    <span class="footer-sep">·</span>
    <a href="https://www.postgresql.org" target="_blank">PostgreSQL.org</a>
    <span class="footer-sep">·</span>
    <a href="/previous.php?lang=<?= h($lang) ?>">
      <?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?>
    </a>
  </div>
</footer>

</body>
</html>
