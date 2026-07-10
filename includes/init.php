<?php
// Bootstrap file for sessions and shared helpers.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$BASE_URL = 'http://localhost/Kuesioner';

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function requireRole(string $role): void
{
    if (!isLoggedIn() || $_SESSION['user']['role'] !== $role) {
        header('Location: ' . url('login.php'));
        exit;
    }
}

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    global $BASE_URL;
    return rtrim($BASE_URL, '/') . '/' . ltrim($path, '/');
}

