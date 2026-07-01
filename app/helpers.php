<?php
declare(strict_types=1);

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base_path(): string
{
    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(__DIR__ . '/..');

    if (!$docRoot || !$projectRoot) {
        return '/absensi-perpus';
    }

    $docRootNorm = str_replace('\\', '/', strtolower($docRoot));
    $projectNorm = str_replace('\\', '/', strtolower($projectRoot));

    $startsWith = strpos($projectNorm, $docRootNorm) === 0;
    if (!$startsWith) {
        return '/absensi-perpus';
    }

    $relative = substr($projectRoot, strlen($docRoot));
    $relative = str_replace('\\', '/', $relative);
    $relative = trim($relative, '/');

    return $relative === '' ? '' : '/' . $relative;
}

function app_url(string $path = '/'): string
{
    $base = app_base_path();
    $cleanPath = '/' . ltrim($path, '/');

    if ($cleanPath === '/') {
        return $base !== '' ? $base . '/' : '/';
    }

    return ($base !== '' ? $base : '') . $cleanPath;
}

function redirect(string $path): void
{
    if (preg_match('/^https?:\\/\\//i', $path) === 1) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . app_url($path));
    exit;
}

function request_is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
