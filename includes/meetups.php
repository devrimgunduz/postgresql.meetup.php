<?php
// includes/meetups.php

require_once __DIR__ . '/config.php';

// ── Archive ───────────────────────────────────────────────────

function auto_archive(): void {
    db()->exec("SELECT auto_archive_meetups()");
}

// ── Translation helpers ───────────────────────────────────────

function get_meetup_translations(int $meetup_id): array {
    $st = db()->prepare(
        "SELECT lang, field, value FROM meetup_translations WHERE meetup_id = ?"
    );
    $st->execute([$meetup_id]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[$row['lang']][$row['field']] = $row['value'];
    }
    return $out;
}

function get_talk_translations(int $talk_id): array {
    $st = db()->prepare(
        "SELECT lang, field, value FROM talk_translations WHERE talk_id = ?"
    );
    $st->execute([$talk_id]);
    $out = [];
    foreach ($st->fetchAll() as $row) {
        $out[$row['lang']][$row['field']] = $row['value'];
    }
    return $out;
}

function save_meetup_translation(int $meetup_id, string $lang, string $field, ?string $value): void {
    if ($value === null || $value === '') {
        db()->prepare(
            "DELETE FROM meetup_translations WHERE meetup_id = ? AND lang = ? AND field = ?"
        )->execute([$meetup_id, $lang, $field]);
    } else {
        db()->prepare(
            "INSERT INTO meetup_translations (meetup_id, lang, field, value)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (meetup_id, lang, field) DO UPDATE SET value = EXCLUDED.value"
        )->execute([$meetup_id, $lang, $field, $value]);
    }
}

function save_talk_translation(int $talk_id, string $lang, string $field, ?string $value): void {
    if ($value === null || $value === '') {
        db()->prepare(
            "DELETE FROM talk_translations WHERE talk_id = ? AND lang = ? AND field = ?"
        )->execute([$talk_id, $lang, $field]);
    } else {
        db()->prepare(
            "INSERT INTO talk_translations (talk_id, lang, field, value)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (talk_id, lang, field) DO UPDATE SET value = EXCLUDED.value"
        )->execute([$talk_id, $lang, $field, $value]);
    }
}

// Get a single translated field with fallback to default lang
function t_meetup(array $translations, string $field, string $lang): string {
    return $translations[$lang][$field]
        ?? $translations[get_default_lang()][$field]
        ?? '';
}

function t_talk(array $translations, string $field, string $lang): string {
    return $translations[$lang][$field]
        ?? $translations[get_default_lang()][$field]
        ?? '';
}

// ── Meetup CRUD ───────────────────────────────────────────────

function get_next_meetup(): ?array {
    auto_archive();
    $st = db()->prepare(
        "SELECT * FROM meetups
         WHERE status = 'published'
         ORDER BY event_date ASC
         LIMIT 1"
    );
    $st->execute();
    $row = $st->fetch();
    if (!$row) return null;
    $row['translations'] = get_meetup_translations($row['id']);
    $row['talks']        = get_talks($row['id']);
    return $row;
}

function get_past_meetups(): array {
    auto_archive();
    $st = db()->prepare(
        "SELECT * FROM meetups WHERE status = 'past' ORDER BY event_date DESC"
    );
    $st->execute();
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['translations'] = get_meetup_translations($r['id']);
        $r['talks']        = get_talks($r['id']);
    }
    return $rows;
}

function get_meetup(int $id): ?array {
    $st = db()->prepare("SELECT * FROM meetups WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) return null;
    $row['translations'] = get_meetup_translations($id);
    $row['talks']        = get_talks($id);
    return $row;
}

function all_meetups(): array {
    $st = db()->prepare("SELECT * FROM meetups ORDER BY created_at DESC");
    $st->execute();
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['translations'] = get_meetup_translations($r['id']);
    }
    return $rows;
}

function save_meetup(array $data, ?int $id = null): int {
    $fields = ['status', 'event_date', 'event_end', 'venue_address', 'venue_map_url', 'registration_url'];
    $vals   = [];
    foreach ($fields as $f) {
        $v      = $data[$f] ?? null;
        $vals[] = ($v === '' || $v === null) ? null : $v;
    }

    if ($id === null) {
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $cols = implode(',', $fields);
        $st   = db()->prepare("INSERT INTO meetups ($cols) VALUES ($placeholders) RETURNING id");
        $st->execute($vals);
        $id = (int)$st->fetchColumn();
    } else {
        $sets = implode(',', array_map(fn($f) => "$f=?", $fields));
        $st   = db()->prepare("UPDATE meetups SET $sets WHERE id=?");
        $vals[] = $id;
        $st->execute($vals);
    }

    // Save translations for each active language
    foreach (get_active_languages() as $lang) {
        $code = $lang['code'];
        foreach (['meetup_title', 'notes'] as $field) {
            $key = "trans_{$code}_{$field}";
            if (!array_key_exists($key, $data)) continue;
            $value = $data[$key];
            save_meetup_translation($id, $code, $field, $value);
        }
    }

    return $id;
}

function delete_meetup(int $id): void {
    db()->prepare("DELETE FROM meetups WHERE id = ?")->execute([$id]);
}

// ── Talk CRUD ─────────────────────────────────────────────────

function get_talks(int $meetup_id): array {
    $st = db()->prepare(
        "SELECT * FROM talks WHERE meetup_id = ? ORDER BY sort_order"
    );
    $st->execute([$meetup_id]);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) {
        $r['translations'] = get_talk_translations($r['id']);
    }
    return $rows;
}

function get_talk(int $id): ?array {
    $st = db()->prepare("SELECT * FROM talks WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) return null;
    $row['translations'] = get_talk_translations($id);
    return $row;
}

function save_talk(array $data, ?int $id = null): int {
    $fields = ['meetup_id', 'sort_order', 'speaker_name', 'speaker_photo_url', 'talk_duration_min', 'slides_url'];
    $vals   = [];
    foreach ($fields as $f) {
        $v      = $data[$f] ?? null;
        $vals[] = ($v === '' || $v === null) ? null : $v;
    }

    if ($id === null) {
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $cols = implode(',', $fields);
        $st   = db()->prepare("INSERT INTO talks ($cols) VALUES ($placeholders) RETURNING id");
        $st->execute($vals);
        $id = (int)$st->fetchColumn();
    } else {
        $sets = implode(',', array_map(fn($f) => "$f=?", $fields));
        $st   = db()->prepare("UPDATE talks SET $sets WHERE id=?");
        $vals[] = $id;
        $st->execute($vals);
    }

    // Save translations
    foreach (get_active_languages() as $lang) {
        $code = $lang['code'];
        foreach (['talk_title', 'talk_abstract', 'speaker_bio'] as $field) {
            $key = "trans_{$code}_{$field}";
            if (!array_key_exists($key, $data)) continue;
            $value = $data[$key];
            save_talk_translation($id, $code, $field, $value);
        }
    }

    return $id;
}

function delete_talk(int $id): void {
    db()->prepare("DELETE FROM talks WHERE id = ?")->execute([$id]);
}

// ── Date formatting ───────────────────────────────────────────

function format_date(string $dt, string $lang, ?string $end = null): string {
    $d = new DateTimeImmutable($dt);
    if ($lang === 'tr') {
        $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran',
                   'Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
        $base = $d->format('j') . ' ' . $months[(int)$d->format('n') - 1]
              . ' ' . $d->format('Y · H:i');
    } else {
        $base = $d->format('F j, Y · H:i');
    }
    if ($end) {
        $e = new DateTimeImmutable($end);
        $base .= ' – ' . $e->format('H:i');
    }
    return $base;
}

// ── Speaker photo upload handling ────────────────────────────

define('UPLOAD_DIR_FS',  __DIR__ . '/../public/uploads/speakers/');
define('UPLOAD_DIR_URL', '/uploads/speakers/');

/**
 * Handle an uploaded speaker photo.
 * Returns the new relative URL path on success, or null if no file was uploaded.
 * Throws Exception on validation failure.
 */
function handle_speaker_photo_upload(array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file selected — leave existing photo untouched
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Photo upload failed (error code ' . $file['error'] . ').');
    }

    $max_bytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_bytes) {
        throw new Exception('Photo must be smaller than 5 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) {
        throw new Exception('Only JPG and PNG images are allowed.');
    }
    $ext = $allowed[$mime];

    if (!is_dir(UPLOAD_DIR_FS)) {
        mkdir(UPLOAD_DIR_FS, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = UPLOAD_DIR_FS . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Could not save uploaded photo.');
    }
    chmod($dest, 0644);

    return UPLOAD_DIR_URL . $filename;
}

/**
 * Delete a speaker photo file from disk given its stored URL path.
 * Safe no-op if the path doesn't point inside the uploads directory
 * (e.g. it's a legacy external URL from before this feature existed).
 */
function delete_speaker_photo(?string $url_path): void {
    if (!$url_path || strpos($url_path, UPLOAD_DIR_URL) !== 0) {
        return;
    }
    $filename = basename($url_path);
    $path     = UPLOAD_DIR_FS . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

// ── Slides upload handling ───────────────────────────────────

define('SLIDES_DIR_FS',  __DIR__ . '/../public/uploads/slides/');
define('SLIDES_DIR_URL', '/uploads/slides/');

/**
 * Handle an uploaded slide deck (PDF only).
 * Returns the new relative URL path on success, or null if no file was uploaded.
 * Throws Exception on validation failure.
 */
function handle_slides_upload(array $file): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // no file selected — leave existing slides untouched
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Slides upload failed (error code ' . $file['error'] . ').');
    }

    $max_bytes = 25 * 1024 * 1024; // 25 MB
    if ($file['size'] > $max_bytes) {
        throw new Exception('Slides file must be smaller than 25 MB.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        throw new Exception('Only PDF files are allowed for slides.');
    }

    if (!is_dir(SLIDES_DIR_FS)) {
        mkdir(SLIDES_DIR_FS, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.pdf';
    $dest     = SLIDES_DIR_FS . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Could not save uploaded slides.');
    }
    chmod($dest, 0644);

    return SLIDES_DIR_URL . $filename;
}

/**
 * Delete a slides file from disk given its stored URL path.
 */
function delete_slides(?string $url_path): void {
    if (!$url_path || strpos($url_path, SLIDES_DIR_URL) !== 0) {
        return;
    }
    $filename = basename($url_path);
    $path     = SLIDES_DIR_FS . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}
