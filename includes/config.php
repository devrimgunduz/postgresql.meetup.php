<?php
// includes/config.php

define('DB_HOST', getenv('PGHOST')     ?: 'localhost');
define('DB_PORT', getenv('PGPORT')     ?: '5432');
define('DB_NAME', getenv('PGDATABASE') ?: 'pgistanbul');
define('DB_USER', getenv('PGUSER')     ?: 'pgistanbul');
define('DB_PASS', getenv('PGPASSWORD') ?: '');

define('SITE_NAME',    'PostgreSQL İstanbul');
define('SESSION_NAME', 'pgist_sess');
define('SESSION_TTL',  3600);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ── Language helpers ──────────────────────────────────────────

function get_active_languages(): array {
    static $langs = null;
    if ($langs === null) {
        $st = db()->prepare(
            "SELECT code, label, is_default FROM languages
             WHERE is_active = TRUE ORDER BY sort_order, id"
        );
        $st->execute();
        $langs = $st->fetchAll();
    }
    return $langs;
}

function get_default_lang(): string {
    foreach (get_active_languages() as $l) {
        if ($l['is_default']) return $l['code'];
    }
    $langs = get_active_languages();
    return $langs[0]['code'] ?? 'en';
}

function current_lang(): string {
    $langs = array_column(get_active_languages(), 'code');
    $req   = $_GET['lang'] ?? '';
    return in_array($req, $langs, true) ? $req : get_default_lang();
}

function h(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
