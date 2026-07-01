<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
auth_require_login();

$flashType = '';
$flashMessage = '';
$selectedId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));
$students = admin_find_students($db, $search);
$selectedStudent = $selectedId > 0 ? admin_get_student_by_id($db, $selectedId) : null;

if (request_is_post()) {
    try {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $rfidUid = trim((string) ($_POST['rfid_uid'] ?? ''));

        if ($studentId <= 0) {
            throw new RuntimeException('Pilih mahasiswa terlebih dahulu.');
        }
        if ($rfidUid === '') {
            throw new RuntimeException('UID RFID wajib diisi.');
        }

        admin_pair_rfid($db, $studentId, $rfidUid);
        $selectedId = $studentId;
        $selectedStudent = admin_get_student_by_id($db, $selectedId);
        $flashType = 'success';
        $flashMessage = 'Pairing RFID berhasil disimpan.';
    } catch (Throwable $exception) {
        $flashType = 'error';
        $flashMessage = 'Pairing gagal: ' . $exception->getMessage();
    }
}

$pageTitle = 'Admin - Pairing RFID';
$activeMenu = 'pairing';
require __DIR__ . '/_layout_top.php';
?>
<section class="card">
    <h1>Pairing RFID</h1>
    <p class="small">Pilih mahasiswa, tap kartu RFID, lalu simpan UID.</p>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert <?= h($flashType) ?>"><?= h($flashMessage) ?></div>
    <?php endif; ?>

    <form method="get" class="inline-form search-form">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari mahasiswa (NIM/Nama)">
        <button class="btn" type="submit">Cari</button>
        <a class="btn btn-light" href="<?= h(app_url('admin/pairing.php')) ?>">Reset</a>
    </form>
</section>

<section class="card">
    <h2>Pilih Mahasiswa</h2>
    <form method="post" autocomplete="off">
        <label for="student_id">Mahasiswa</label>
        <select id="student_id" name="student_id" required>
            <option value="">-- Pilih Mahasiswa --</option>
            <?php foreach ($students as $student): ?>
                <option value="<?= (int) $student['id'] ?>" <?= (int) $student['id'] === $selectedId ? 'selected' : '' ?>>
                    <?= h((string) $student['nim']) ?> - <?= h((string) $student['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="rfid_uid">UID RFID</label>
        <input id="rfid_uid" name="rfid_uid" type="text" placeholder="Tap kartu lalu UID masuk di sini" required>

        <div class="form-actions">
            <button class="btn" type="submit">Simpan Pairing</button>
            <a class="btn btn-light" href="<?= h(app_url('admin/pairing.php')) ?>?student_id=<?= (int) $selectedId ?>">Reset</a>
        </div>
    </form>

    <?php if ($selectedStudent): ?>
        <div class="result-box">
            <p class="result-label">Mahasiswa Terpilih</p>
            <p class="result-name"><?= h((string) $selectedStudent['nama']) ?></p>
            <p class="result-detail">
                NIM <?= h((string) $selectedStudent['nim']) ?>
                | UID Saat Ini: <?= h((string) ($selectedStudent['rfid_uid'] ?: '-')) ?>
            </p>
        </div>
    <?php endif; ?>
</section>

<?php
$db->close();
require __DIR__ . '/_layout_bottom.php';
