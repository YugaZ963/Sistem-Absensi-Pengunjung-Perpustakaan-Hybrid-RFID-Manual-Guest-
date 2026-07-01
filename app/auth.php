<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function auth_boot(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('absensi_perpus');
        session_start();
    }
}

function auth_login(array $admin): void
{
    auth_boot();
    $_SESSION['admin_user'] = [
        'id' => (int) $admin['id'],
        'username' => (string) $admin['username'],
        'nama' => (string) $admin['nama'],
    ];
}

function auth_logout(): void
{
    auth_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function auth_user(): ?array
{
    auth_boot();
    $user = $_SESSION['admin_user'] ?? null;
    return is_array($user) ? $user : null;
}

function auth_require_login(): void
{
    if (auth_user() === null) {
        redirect('admin/login.php');
    }
}
