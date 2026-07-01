<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Admin Absensi Perpus';
$activeMenu = $activeMenu ?? '';
$currentUser = auth_user();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('assets/css/admin.css')) ?>">
</head>
<body>
    <div class="admin-wrap">
        <aside class="admin-side">
            <h2>Admin Panel</h2>
            <p class="admin-user"><?= h($currentUser['nama'] ?? 'Admin') ?></p>
            <nav class="admin-nav">
                <a class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>" href="<?= h(app_url('admin/index.php')) ?>">Dashboard</a>
                <a class="<?= $activeMenu === 'students' ? 'active' : '' ?>" href="<?= h(app_url('admin/students.php')) ?>">Mahasiswa</a>
                <a class="<?= $activeMenu === 'import' ? 'active' : '' ?>" href="<?= h(app_url('admin/import.php')) ?>">Import Excel</a>
                <a class="<?= $activeMenu === 'pairing' ? 'active' : '' ?>" href="<?= h(app_url('admin/pairing.php')) ?>">Pairing RFID</a>
                <a class="<?= $activeMenu === 'rekap' ? 'active' : '' ?>" href="<?= h(app_url('admin/rekap.php')) ?>">Rekap Absensi</a>
                <a href="<?= h(app_url('/')) ?>">Front Desk</a>
                <a href="<?= h(app_url('admin/logout.php')) ?>">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
