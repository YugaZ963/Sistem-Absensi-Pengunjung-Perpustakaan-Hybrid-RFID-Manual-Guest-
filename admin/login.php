<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (auth_user() !== null) {
    $db->close();
    redirect('admin/index.php');
}

$error = '';
$username = '';

if (request_is_post()) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $admin = admin_find_by_username($db, $username);
        if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
            $error = 'Login gagal. Periksa username/password.';
        } else {
            auth_login($admin);
            $db->close();
            redirect('admin/index.php');
        }
    }
}

$db->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin</title>
    <link rel="stylesheet" href="<?= h(app_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= h(app_url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
    <main class="login-card">
        <h1>Login Admin Perpustakaan</h1>
        <p class="small">Default akun: <strong>admin</strong> / <strong>admin123</strong></p>

        <?php if ($error !== ''): ?>
            <div class="alert error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" value="<?= h($username) ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <div class="form-actions">
                <button class="btn" type="submit">Login</button>
                <a class="btn btn-light" href="<?= h(app_url('/')) ?>">Kembali ke Front Desk</a>
            </div>
        </form>
    </main>
</body>
</html>
