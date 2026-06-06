<?php
// includes/auth.php

require_once __DIR__ . '/config.php';

function session_start_secure(): void {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    session_name(SESSION_NAME);
    if (session_status() === PHP_SESSION_NONE) session_start();
    // Regenerate to avoid fixation
    if (empty($_SESSION['_init'])) {
        session_regenerate_id(true);
        $_SESSION['_init'] = true;
    }
}

function is_logged_in(): bool {
    session_start_secure();
    if (empty($_SESSION['user_id'])) return false;
    if (empty($_SESSION['expires']) || $_SESSION['expires'] < time()) {
        session_destroy();
        return false;
    }
    return true;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
    // Slide expiry
    $_SESSION['expires'] = time() + SESSION_TTL;
}

function attempt_login(string $username, string $password): bool {
    $st = db()->prepare('SELECT id, password FROM users WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !password_verify($password, $row['password'])) return false;
    session_start_secure();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $row['id'];
    $_SESSION['username'] = $username;
    $_SESSION['expires']  = time() + SESSION_TTL;
    return true;
}

function logout(): void {
    session_start_secure();
    session_destroy();
    setcookie(SESSION_NAME, '', time() - 3600, '/');
}
