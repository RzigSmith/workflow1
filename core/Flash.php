<?php

/**
 * Simple flash message helper.
 *
 * Usage:
 *   set_flash('error', 'Something went wrong');
 *   $message = get_flash('error');
 */

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function set_flash(string $key, string $message): void
{
    ensure_session_started();
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    ensure_session_started();

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function set_old(array $data): void
{
    ensure_session_started();
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    ensure_session_started();

    if (!isset($_SESSION['old'][$key])) {
        return $default;
    }

    return (string) $_SESSION['old'][$key];
}

function clear_old(): void
{
    ensure_session_started();
    unset($_SESSION['old']);
}
