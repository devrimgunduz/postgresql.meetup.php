<?php
/**
 * Shared meetup detail renderer.
 * Expects these variables to already be set by the including page:
 *   $meetup      — full meetup array with 'translations' and 'talks'
 *   $lang        — current language code
 *   $hero_label  — text shown above the title (e.g. "Next Meetup" / "Past Meetup")
 */
$tr    = $meetup['translations'];
$title = t_meetup($tr, 'meetup_title', $lang);
?>
<section class="hero">
  <div class="container">
    <div class="hero-label"><?= h($hero_label) ?></div>
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

    <?php if ($meetup['registration_url'] && $meetup['status'] === 'published'): ?>
    <div class="hero-cta">
      <a href="<?= h($meetup['registration_url']) ?>" target="_blank" class="btn-register">
        <?= $lang === 'tr' ? 'Kayıt Ol' : 'Register Now' ?>
      </a>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php $notes = t_meetup($tr, 'notes', $lang); ?>
<?php if ($notes): ?>
<section class="notes-section">
  <div class="container">
    <div class="notes-box">
      <h3><?= $lang === 'tr' ? 'Meetup hakkında' : 'About the meetup' ?></h3>
      <p><?= nl2br(h($notes)) ?></p>
    </div>
  </div>
</section>
<?php endif; ?>

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
          <?php if (!empty($talk['slides_url'])): ?>
            <a href="<?= h($talk['slides_url']) ?>" target="_blank" class="btn-slides">
              📄 <?= $lang === 'tr' ? 'Slaytları İndir' : 'Download Slides' ?>
            </a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
