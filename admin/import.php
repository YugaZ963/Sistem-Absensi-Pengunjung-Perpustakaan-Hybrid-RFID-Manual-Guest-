<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
auth_require_login();

$flashType = '';
$flashMessage = '';
$resultMeta = null;

if (request_is_post()) {
    try {
        if (!isset($_FILES['excel_file']) || !is_array($_FILES['excel_file'])) {
            throw new RuntimeException('File Excel wajib diupload.');
        }

        $file = $_FILES['excel_file'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload file gagal. Kode error: ' . $error);
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            throw new RuntimeException('Ekstensi file harus .xls atau .xlsx');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('File upload tidak valid.');
        }

        $resultMeta = admin_import_students($db, $tmpPath, $extension);
        if (!$resultMeta['ok']) {
            throw new RuntimeException((string) $resultMeta['message']);
        }

        $flashType = 'success';
        $flashMessage = 'Import selesai. Insert: ' . $resultMeta['inserted']
            . ', Update: ' . $resultMeta['updated']
            . ', Skip: ' . $resultMeta['skipped'] . '.';
    } catch (Throwable $exception) {
        $flashType = 'error';
        $flashMessage = $exception->getMessage();
    }
}

$pageTitle = 'Admin - Import Excel';
$activeMenu = 'import';
require __DIR__ . '/_layout_top.php';
?>
<section class="card">
    <h1>Import Excel Mahasiswa</h1>
    <p class="small">Format kolom wajib: NIM | Nama | Prodi | Jenjang | Alamat | Semester | Tahun Akademik</p>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert <?= h($flashType) ?>"><?= h($flashMessage) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label for="excel_file">Pilih file Excel (.xlsx direkomendasikan)</label>
        <input id="excel_file" name="excel_file" type="file" accept=".xls,.xlsx" required>

        <div class="form-actions">
            <button class="btn" type="submit">Upload dan Import</button>
            <a class="btn btn-light" href="<?= h(app_url('admin/import.php')) ?>">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Catatan</h2>
    <ul class="simple-list">
        <li>Jika NIM sudah ada, data akan di-update.</li>
        <li>Jika NIM baru, data akan di-insert.</li>
        <li>RFID otomatis dibuat saat import dengan format `RFID-4HURUFNAMA-NNN`.</li>
        <li>Parser bawaan saat ini menjalankan import `.xlsx`.</li>
        <li>Header dapat dibaca walau tidak berada di baris pertama.</li>
    </ul>
</section>

<?php
$db->close();
require __DIR__ . '/_layout_bottom.php';
