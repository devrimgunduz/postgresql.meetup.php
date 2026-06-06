<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/meetups.php';

$lang    = current_lang();
$langs   = get_active_languages();
$meetups = get_past_meetups();
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?> – PostgreSQL İstanbul</title>
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
      <a href="/previous.php?lang=<?= h($lang) ?>" class="nav-link active">
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
  <section class="page-hero">
    <div class="container">
      <h1><?= $lang === 'tr' ? 'Geçmiş Etkinlikler' : 'Past Meetups' ?></h1>
      <p><?= $lang === 'tr'
        ? 'PostgreSQL İstanbul topluluk buluşmalarına bir bakış.'
        : 'A look back at our previous PostgreSQL İstanbul community gatherings.' ?></p>
    </div>
  </section>

  <section class="past-section">
    <div class="container">
      <?php if (empty($meetups)): ?>
        <div class="empty-state">
          <p><?= $lang === 'tr'
            ? 'Henüz geçmiş etkinlik yok.'
            : 'No past meetups yet. Check back soon!' ?></p>
        </div>
      <?php else: ?>
        <div class="past-list">
          <?php foreach ($meetups as $meetup):
            $tr    = $meetup['translations'];
            $title = t_meetup($tr, 'meetup_title', $lang);
            $notes = t_meetup($tr, 'notes', $lang);
          ?>
          <article class="past-card">
            <div class="past-card-header">
              <?php if ($meetup['event_date']): ?>
              <div class="past-date"><?= h(format_date($meetup['event_date'], $lang, $meetup['event_end'] ?? null)) ?></div>
              <?php endif; ?>
              <h2 class="past-venue"><?= h($title ?: '—') ?></h2>
              <?php if ($meetup['venue_address']): ?>
              <div class="past-address">📍 <?= h($meetup['venue_address']) ?>
                <?php if ($meetup['venue_map_url']): ?>
                  <a href="<?= h($meetup['venue_map_url']) ?>" target="_blank" class="map-link">
                    <?= $lang === 'tr' ? 'Harita' : 'Map' ?> ↗
                  </a>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>

            <?php $notes = t_meetup($tr, 'notes', $lang); ?>
            <?php if ($notes): ?>
              <div class="past-notes">
                <strong><?= $lang === 'tr' ? 'Meetup hakkında' : 'About the meetup' ?></strong>
                <p><?= nl2br(h($notes)) ?></p>
              </div>
            <?php endif; ?>
            <?php if (!empty($meetup['talks'])): ?>
            <div class="past-talks">
              <?php foreach ($meetup['talks'] as $talk):
                $ttr   = $talk['translations'];
                $title = t_talk($ttr, 'talk_title', $lang);
              ?>
              <div class="past-talk">
                <div class="past-talk-title"><?= h($title ?: '—') ?></div>
                <div class="past-talk-speaker"><?= h($talk['speaker_name']) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($notes): ?>
              <div class="past-notes"><?= nl2br(h($notes)) ?></div>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>© <?= date('Y') ?> PostgreSQL İstanbul</span>
    <span class="footer-sep">·</span>
    <a href="https://www.postgresql.org" target="_blank">PostgreSQL.org</a>
    <span class="footer-sep">·</span>
    <a href="/?lang=<?= h($lang) ?>"><?= $lang === 'tr' ? 'Ana Sayfa' : 'Home' ?></a>
  </div>
</footer>

</body>
</html>
