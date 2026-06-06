<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/meetups.php';

$lang    = current_lang();
$langs   = get_active_languages();
$meetup  = get_next_meetup();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PostgreSQL İstanbul</title>
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
      <a href="/?lang=<?= h($lang) ?>" class="nav-link active">
        <?= $lang === 'tr' ? 'Ana Sayfa' : 'Home' ?>
      </a>
      <a href="/previous.php?lang=<?= h($lang) ?>" class="nav-link">
        <?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?>
      </a>
      <?php if (count($langs) > 1): ?>
      <div class="lang-switcher">
        <?php foreach ($langs as $l): ?>
          <?php if ($l['code'] !== $lang): ?>
            <a href="?lang=<?= h($l['code']) ?>" class="nav-link lang-switch"><?= h($l['label']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="site-main">
<?php if ($meetup):
  $tr = $meetup['translations'];
  $title = t_meetup($tr, 'meetup_title', $lang);
?>

  <section class="hero">
    <div class="container">
      <div class="hero-label">
        <?= $lang === 'tr' ? 'Sonraki Etkinlik' : 'Next Meetup' ?>
      </div>
      <h1 class="hero-title"><?= h($title ?: 'Coming Soon') ?></h1>

      <div class="meta-row">
        <?php if ($meetup['event_date']): ?>
        <div class="meta-item">
          <span class="meta-icon">📅</span>
          <span><?= h(format_date($meetup['event_date'], $lang, $meetup['event_end'] ?? null)) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($meetup['venue_address']): ?>
        <div class="meta-item">
          <span class="meta-icon">📍</span>
          <span><?= h($meetup['venue_address']) ?></span>
          <?php if ($meetup['venue_map_url']): ?>
            <a href="<?= h($meetup['venue_map_url']) ?>" target="_blank" class="map-link">
              <?= $lang === 'tr' ? 'Harita' : 'Map' ?> ↗
            </a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($meetup['registration_url']): ?>
      <div class="hero-cta">
        <a href="<?= h($meetup['registration_url']) ?>" target="_blank" class="btn-register">
          <?= $lang === 'tr' ? 'Kayıt Ol' : 'Register Now' ?>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($meetup['talks'])): ?>
  <section class="talks-section">
    <div class="container">
      <h2 class="section-heading"><?= $lang === 'tr' ? 'Sunumlar' : 'Talks' ?></h2>
      <div class="talks-grid">
        <?php foreach ($meetup['talks'] as $i => $talk): ?>
        <?php $ttr = $talk['translations']; ?>
        <article class="talk-card">
          <div class="talk-number"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
          <div class="talk-body">
            <h3 class="talk-title"><?= h(t_talk($ttr, 'talk_title', $lang)) ?></h3>
            <?php $abstract = t_talk($ttr, 'talk_abstract', $lang); ?>
            <?php if ($abstract): ?>
              <p class="talk-abstract"><?= h($abstract) ?></p>
            <?php endif; ?>
            <div class="speaker-row">
              <?php if ($talk['speaker_photo_url']): ?>
                <img src="<?= h($talk['speaker_photo_url']) ?>"
                     alt="<?= h($talk['speaker_name']) ?>" class="speaker-photo">
              <?php endif; ?>
              <div class="speaker-info">
                <strong class="speaker-name"><?= h($talk['speaker_name']) ?></strong>
                <?php $bio = t_talk($ttr, 'speaker_bio', $lang); ?>
                <?php if ($bio): ?><p class="speaker-bio"><?= h($bio) ?></p><?php endif; ?>
              </div>
            </div>
            <?php if ($talk['talk_duration_min']): ?>
              <div class="talk-duration">
                ⏱ <?= h($talk['talk_duration_min']) ?> <?= $lang === 'tr' ? 'dak' : 'min' ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php $notes = t_meetup($tr, 'notes', $lang); ?>
  <?php if ($notes): ?>
  <section class="notes-section">
    <div class="container">
      <div class="notes-box">
        <h3><?= $lang === 'tr' ? 'Notlar' : 'Notes' ?></h3>
        <p><?= nl2br(h($notes)) ?></p>
      </div>
    </div>
  </section>
  <?php endif; ?>

<?php else: ?>

  <section class="hero hero--empty">
    <div class="container">
      <div class="elephant-large">🐘</div>
      <h1 class="hero-empty-title">
        <?= $lang === 'tr'
          ? 'Bir sonraki etkinlik için takipte kalın!'
          : 'Stay tuned for the next one!' ?>
      </h1>
      <p class="hero-empty-sub">
        <?= $lang === 'tr'
          ? 'Bir sonraki PostgreSQL İstanbul buluşmasını yakında duyuracağız.'
          : 'We\'ll announce the next PostgreSQL İstanbul meetup soon.' ?>
      </p>
      <div class="social-links">
        <a href="https://twitter.com/pgistanbul" target="_blank" class="social-btn">𝕏 Twitter</a>
        <a href="https://www.meetup.com/postgresql-istanbul/" target="_blank" class="social-btn">Meetup.com</a>
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
