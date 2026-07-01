<?php
declare(strict_types=1);

require_once __DIR__ . '/app/attendance_service.php';
require_once __DIR__ . '/app/helpers.php';

function normalize_tab(string $tab): string
{
    $allowed = ['search', 'guest'];
    return in_array($tab, $allowed, true) ? $tab : 'search';
}

$activeTab = normalize_tab((string) ($_GET['tab'] ?? 'search'));
$flashType = '';
$flashMessage = '';
$displayName = '';
$displayDetail = '';
$oldNim = '';
$oldRfid = '';
$oldGuestNama = '';
$oldGuestAlamat = '';
$oldGuestAsal = '';

$db = null;

try {
    $db = db_connect();
} catch (Throwable $exception) {
    $db = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $activeTab = normalize_tab((string) ($_POST['active_tab'] ?? $activeTab));

    if (!$db instanceof mysqli) {
        $flashType = 'error';
        $flashMessage = 'Absensi gagal diproses karena koneksi database belum tersedia.';
    } else {
        try {
            if ($action === 'search_nim_attendance') {
                $nim = trim((string) ($_POST['nim_key'] ?? ''));
                $oldNim = $nim;

                if ($nim === '') {
                    $flashType = 'error';
                    $flashMessage = 'NIM wajib diisi.';
                } else {
                    $student = attendance_find_student_by_nim($db, $nim);
                    if ($student === null) {
                        $flashType = 'error';
                        $flashMessage = 'NIM tidak ditemukan.';
                    } else {
                        attendance_insert_student_visit($db, $student);
                        $flashType = 'success';
                        $flashMessage = 'Absensi dari NIM berhasil disimpan.';
                        $displayName = (string) $student['nama'];
                        $displayDetail = 'NIM ' . $student['nim'] . ' | ' . $student['prodi'] . ' | Semester ' . $student['semester'] . ' | Metode NIM';
                        $oldNim = '';
                    }
                }
            } elseif ($action === 'search_rfid_attendance') {
                $rfid = trim((string) ($_POST['rfid_key'] ?? ''));
                $oldRfid = $rfid;

                if ($rfid === '') {
                    $flashType = 'error';
                    $flashMessage = 'UID RFID wajib diisi.';
                } else {
                    $student = attendance_find_student_by_rfid($db, $rfid);
                    if ($student === null) {
                        $flashType = 'error';
                        $flashMessage = 'Kartu RFID belum terdaftar.';
                    } else {
                        attendance_insert_student_visit($db, $student);
                        $flashType = 'success';
                        $flashMessage = 'Absensi dari RFID berhasil disimpan.';
                        $displayName = (string) $student['nama'];
                        $displayDetail = 'NIM ' . $student['nim'] . ' | ' . $student['prodi'] . ' | Semester ' . $student['semester'] . ' | Metode RFID';
                        $oldRfid = '';
                    }
                }
            } elseif ($action === 'guest_attendance') {
                $nama = trim((string) ($_POST['guest_nama'] ?? ''));
                $alamat = trim((string) ($_POST['guest_alamat'] ?? ''));
                $asalKampus = trim((string) ($_POST['guest_asal_kampus'] ?? ''));

                $oldGuestNama = $nama;
                $oldGuestAlamat = $alamat;
                $oldGuestAsal = $asalKampus;

                if ($nama === '' || $alamat === '') {
                    $flashType = 'error';
                    $flashMessage = 'Nama dan alamat guest wajib diisi.';
                } else {
                    $guestId = attendance_create_guest_visit($db, $nama, $alamat, $asalKampus !== '' ? $asalKampus : null);
                    $flashType = 'success';
                    $flashMessage = 'Absensi guest berhasil disimpan dengan ID ' . $guestId . '.';
                    $displayName = $nama;
                    $displayDetail = 'Guest ID ' . $guestId;
                    $oldGuestNama = '';
                    $oldGuestAlamat = '';
                    $oldGuestAsal = '';
                }
            } else {
                $flashType = 'error';
                $flashMessage = 'Aksi tidak valid.';
            }
        } catch (Throwable $exception) {
            $flashType = 'error';
            $flashMessage = 'Terjadi kesalahan saat memproses data: ' . $exception->getMessage();
        }
    }
}

if ($db instanceof mysqli) {
    $db->close();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Absensi Perpustakaan</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <main class="container">
        <header class="page-head">
            <h1>Absensi Perpustakaan Hybrid</h1>
            <p class="meta">Front Desk Pencarian + Guest/Tamu</p>
            <p class="small"><a href="<?= h(app_url('admin/login.php')) ?>">Login Admin</a></p>
        </header>

        <nav class="tabs" aria-label="Mode absensi">
            <a class="tab <?= $activeTab === 'search' ? 'active' : '' ?>" href="?tab=search">Pencarian</a>
            <a class="tab <?= $activeTab === 'guest' ? 'active' : '' ?>" href="?tab=guest">Guest / Tamu</a>
        </nav>

        <section class="card">
            <?php if ($flashMessage !== ''): ?>
                <div class="alert <?= h($flashType) ?>"><?= h($flashMessage) ?></div>
            <?php endif; ?>

            <?php if ($displayName !== ''): ?>
                <div class="result-box">
                    <p class="result-label">Data terbaca:</p>
                    <p class="result-name"><?= h($displayName) ?></p>
                    <p class="result-detail"><?= h($displayDetail) ?></p>
                    <a class="btn btn-light" href="?tab=<?= h($activeTab) ?>">Reset/Clear</a>
                </div>
            <?php endif; ?>

            <div class="<?= $activeTab === 'search' ? '' : 'hidden' ?>" id="panel-search">
                <h2>Mode Pencarian Mahasiswa</h2>
                <p class="small">NIM dan RFID dipisah agar input lebih jelas di front desk.</p>
                <p class="small">Pastikan reader RFID aktif sebagai keyboard (HID Keyboard / Keyboard Wedge).</p>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="search_nim_attendance">
                    <input type="hidden" name="active_tab" value="search">
                    <label for="nim_key">Input NIM</label>
                    <input id="nim_key" name="nim_key" type="text" value="<?= h($oldNim) ?>" placeholder="Ketik NIM lalu Enter / klik tombol" required>
                    <div class="form-actions">
                        <button type="submit" class="btn">Simpan dari NIM</button>
                    </div>
                </form>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="search_rfid_attendance">
                    <input type="hidden" name="active_tab" value="search">
                    <label for="rfid_key">Input RFID (Tap Kartu)</label>
                    <input id="rfid_key" name="rfid_key" type="text" value="<?= h($oldRfid) ?>" placeholder="Tap kartu RFID di sini (auto-enter)" required>
                    <div class="form-actions">
                        <button type="submit" class="btn">Simpan dari RFID</button>
                        <a class="btn btn-light" href="?tab=search">Reset</a>
                    </div>
                </form>
            </div>

            <div class="<?= $activeTab === 'guest' ? '' : 'hidden' ?>" id="panel-guest">
                <h2>Mode Guest (Tamu Umum)</h2>
                <form method="post" autocomplete="off">
                    <input type="hidden" name="action" value="guest_attendance">
                    <input type="hidden" name="active_tab" value="guest">
                    <label for="guest_nama">Nama</label>
                    <input id="guest_nama" name="guest_nama" type="text" value="<?= h($oldGuestNama) ?>" required>

                    <label for="guest_alamat">Alamat</label>
                    <input id="guest_alamat" name="guest_alamat" type="text" value="<?= h($oldGuestAlamat) ?>" required>

                    <label for="guest_asal_kampus">Asal Kampus (Opsional)</label>
                    <input id="guest_asal_kampus" name="guest_asal_kampus" type="text" value="<?= h($oldGuestAsal) ?>">

                    <div class="form-actions">
                        <button type="submit" class="btn">Simpan Guest</button>
                        <a class="btn btn-light" href="?tab=guest">Reset</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="assets/js/app.js"></script>
</body>
</html>
