<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
auth_require_login();

$flashType = '';
$flashMessage = '';
$search = trim((string) ($_GET['q'] ?? ''));
$editId = (int) ($_GET['edit'] ?? 0);

$form = [
    'nim' => '',
    'nama' => '',
    'prodi' => '',
    'jenjang' => 'S1',
    'alamat' => '',
    'semester' => 1,
    'tahun_akademik' => '',
    'rfid_uid' => '',
];

if (request_is_post()) {
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new RuntimeException('ID mahasiswa tidak valid.');
            }
            admin_delete_student($db, $id);
            $flashType = 'success';
            $flashMessage = 'Data mahasiswa berhasil dihapus.';
        } elseif ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $form = admin_validate_student_input($_POST);
            if (!admin_student_input_has_required($form)) {
                throw new RuntimeException('Field wajib harus diisi lengkap.');
            }

            if ($id > 0) {
                admin_update_student($db, $id, $form);
                $flashType = 'success';
                $flashMessage = 'Data mahasiswa berhasil diperbarui.';
                $editId = $id;
            } else {
                admin_create_student($db, $form);
                $flashType = 'success';
                $flashMessage = 'Data mahasiswa berhasil ditambahkan.';
                $form = [
                    'nim' => '',
                    'nama' => '',
                    'prodi' => '',
                    'jenjang' => 'S1',
                    'alamat' => '',
                    'semester' => 1,
                    'tahun_akademik' => '',
                    'rfid_uid' => '',
                ];
            }
        }
    } catch (Throwable $exception) {
        $flashType = 'error';
        $flashMessage = 'Gagal memproses data: ' . $exception->getMessage();
    }
}

if ($editId > 0 && !request_is_post()) {
    $student = admin_get_student_by_id($db, $editId);
    if ($student) {
        $form = [
            'nim' => (string) $student['nim'],
            'nama' => (string) $student['nama'],
            'prodi' => (string) $student['prodi'],
            'jenjang' => (string) $student['jenjang'],
            'alamat' => (string) $student['alamat'],
            'semester' => (int) $student['semester'],
            'tahun_akademik' => (string) $student['tahun_akademik'],
            'rfid_uid' => (string) ($student['rfid_uid'] ?? ''),
        ];
    } else {
        $editId = 0;
    }
}

$students = admin_find_students($db, $search);

$pageTitle = 'Admin - Mahasiswa';
$activeMenu = 'students';
require __DIR__ . '/_layout_top.php';
?>
<section class="card">
    <h1>Data Mahasiswa</h1>
    <p class="small">Kelola tambah, ubah, hapus, dan cari mahasiswa.</p>

    <?php if ($flashMessage !== ''): ?>
        <div class="alert <?= h($flashType) ?>"><?= h($flashMessage) ?></div>
    <?php endif; ?>

    <form method="get" class="inline-form search-form">
        <input type="text" name="q" value="<?= h($search) ?>" placeholder="Cari NIM atau Nama">
        <button class="btn" type="submit">Cari</button>
        <a class="btn btn-light" href="<?= h(app_url('admin/students.php')) ?>">Reset</a>
    </form>
</section>

<section class="card">
    <h2><?= $editId > 0 ? 'Edit Mahasiswa' : 'Tambah Mahasiswa' ?></h2>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="grid-2">
            <div>
                <label for="nim">NIM</label>
                <input id="nim" name="nim" type="text" value="<?= h((string) $form['nim']) ?>" required>
            </div>
            <div>
                <label for="nama">Nama</label>
                <input id="nama" name="nama" type="text" value="<?= h((string) $form['nama']) ?>" required>
            </div>
            <div>
                <label for="prodi">Prodi</label>
                <input id="prodi" name="prodi" type="text" value="<?= h((string) $form['prodi']) ?>" required>
            </div>
            <div>
                <label for="jenjang">Jenjang</label>
                <select id="jenjang" name="jenjang" required>
                    <option value="S1" <?= (string) $form['jenjang'] === 'S1' ? 'selected' : '' ?>>S1</option>
                    <option value="S2" <?= (string) $form['jenjang'] === 'S2' ? 'selected' : '' ?>>S2</option>
                </select>
            </div>
            <div>
                <label for="semester">Semester</label>
                <input id="semester" name="semester" type="number" min="1" max="14" value="<?= (int) $form['semester'] ?>" required>
            </div>
            <div>
                <label for="tahun_akademik">Tahun Akademik</label>
                <input id="tahun_akademik" name="tahun_akademik" type="text" value="<?= h((string) $form['tahun_akademik']) ?>" placeholder="2025/2026" required>
            </div>
        </div>

        <label for="alamat">Alamat</label>
        <input id="alamat" name="alamat" type="text" value="<?= h((string) $form['alamat']) ?>" required>

        <label for="rfid_uid">RFID UID (opsional)</label>
        <input id="rfid_uid" name="rfid_uid" type="text" value="<?= h((string) $form['rfid_uid']) ?>">

        <div class="form-actions">
            <button class="btn" type="submit"><?= $editId > 0 ? 'Update' : 'Simpan' ?></button>
            <a class="btn btn-light" href="<?= h(app_url('admin/students.php')) ?>">Batal</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Daftar Mahasiswa (<?= count($students) ?>)</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Prodi</th>
                    <th>Jenjang</th>
                    <th>Semester</th>
                    <th>Tahun Akademik</th>
                    <th>RFID UID</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students === []): ?>
                    <tr>
                        <td colspan="8">Belum ada data.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= h((string) $student['nim']) ?></td>
                        <td><?= h((string) $student['nama']) ?></td>
                        <td><?= h((string) $student['prodi']) ?></td>
                        <td><?= h((string) $student['jenjang']) ?></td>
                        <td><?= (int) $student['semester'] ?></td>
                        <td><?= h((string) $student['tahun_akademik']) ?></td>
                        <td><?= h((string) ($student['rfid_uid'] ?? '-')) ?></td>
                        <td class="actions-cell">
                            <a class="btn btn-light" href="<?= h(app_url('admin/students.php')) ?>?edit=<?= (int) $student['id'] ?>&q=<?= urlencode($search) ?>">Edit</a>
                            <a class="btn btn-light" href="<?= h(app_url('admin/pairing.php')) ?>?student_id=<?= (int) $student['id'] ?>">Pair</a>
                            <form method="post" onsubmit="return confirm('Hapus mahasiswa ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$db->close();
require __DIR__ . '/_layout_bottom.php';
