<?php
// includes/config.php

// ── Default settings ──────────────────────────────────────────
// These can be overridden by environment variables, or by
// includes/config_local.php (see below) for settings you don't
// want tracked in git — e.g. a real DB password on a shared server.

$db_host = getenv('PGHOST')     ?: 'localhost';
$db_port = getenv('PGPORT')     ?: '5432';
$db_name = getenv('PGDATABASE') ?: 'pgistanbul';
$db_user = getenv('PGUSER')     ?: 'pgistanbul';
$db_pass = getenv('PGPASSWORD') ?: '';

$site_name    = 'PostgreSQL İstanbul';
$session_name = 'pgist_sess';
$session_ttl  = 86400;

// ── Local overrides ────────────────────────────────────────────
// Create includes/config_local.php (gitignored) to override any of
// the $variables above. It is included here, before the constants
// below are finalized, so anything it sets takes effect.
// See includes/config_local.php.example for the format.

$config_local_path = __DIR__ . '/config_local.php';
if (file_exists($config_local_path)) {
    require $config_local_path;
}

// ── Finalize as constants ────────────────────────────────────────

define('DB_HOST', $db_host);
define('DB_PORT', $db_port);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);

define('SITE_NAME',    $site_name);
define('SESSION_NAME', $session_name);
define('SESSION_TTL',  $session_ttl);

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
