<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/report_service.php';
auth_require_login();

$filters = report_normalize_filters($_GET);
$options = report_get_filter_options($db);
$summary = report_fetch_summary($db, $filters);
$rows = report_fetch_rows($db, $filters, 2000);
$exportQuery = report_export_query($filters);

$pageTitle = 'Admin - Rekap Absensi';
$activeMenu = 'rekap';
require __DIR__ . '/_layout_top.php';
?>
<section class="card">
    <h1>Rekap Absensi</h1>
    <p class="small">Filter periode, kategori, lalu export hasil ke Excel atau cetak PDF.</p>

    <?php if ($filters['errors'] !== []): ?>
        <div class="alert error"><?= h(implode(' ', $filters['errors'])) ?></div>
    <?php endif; ?>

    <form method="get" class="filter-grid">
        <div>
            <label for="period">Periode</label>
            <select id="period" name="period">
                <option value="daily" <?= $filters['period'] === 'daily' ? 'selected' : '' ?>>Harian</option>
                <option value="weekly" <?= $filters['period'] === 'weekly' ? 'selected' : '' ?>>Mingguan</option>
                <option value="monthly" <?= $filters['period'] === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                <option value="custom" <?= $filters['period'] === 'custom' ? 'selected' : '' ?>>Kustom (Tanggal)</option>
                <option value="semester" <?= $filters['period'] === 'semester' ? 'selected' : '' ?>>Semester</option>
                <option value="academic_year" <?= $filters['period'] === 'academic_year' ? 'selected' : '' ?>>Tahun Akademik</option>
            </select>
        </div>

        <div>
            <label for="ref_date">Tanggal Acuan (Harian/Mingguan)</label>
            <input id="ref_date" name="ref_date" type="date" value="<?= h($filters['ref_date']) ?>">
        </div>

        <div>
            <label for="month">Bulan (Bulanan)</label>
            <input id="month" name="month" type="month" value="<?= h($filters['month']) ?>">
        </div>

        <div>
            <label for="semester">Semester</label>
            <select id="semester" name="semester">
                <option value="1" <?= (int) $filters['semester'] === 1 ? 'selected' : '' ?>>1</option>
                <option value="2" <?= (int) $filters['semester'] === 2 ? 'selected' : '' ?>>2</option>
            </select>
        </div>

        <div>
            <label for="start_date">Tanggal Mulai (Kustom)</label>
            <input id="start_date" name="start_date" type="date" value="<?= h($filters['start_date']) ?>">
        </div>

        <div>
            <label for="end_date">Tanggal Selesai (Kustom)</label>
            <input id="end_date" name="end_date" type="date" value="<?= h($filters['end_date']) ?>">
        </div>

        <div>
            <label for="tahun_akademik">Tahun Akademik</label>
            <input id="tahun_akademik" name="tahun_akademik" type="text" placeholder="2025/2026" value="<?= h($filters['tahun_akademik']) ?>">
        </div>

        <div>
            <label for="status">Status Pengunjung</label>
            <select id="status" name="status">
                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="mahasiswa" <?= $filters['status'] === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                <option value="guest" <?= $filters['status'] === 'guest' ? 'selected' : '' ?>>Guest</option>
            </select>
        </div>

        <div>
            <label for="prodi">Prodi</label>
            <select id="prodi" name="prodi">
                <option value="">Semua Prodi</option>
                <?php foreach ($options['prodi'] as $prodi): ?>
                    <option value="<?= h($prodi) ?>" <?= $filters['prodi'] === $prodi ? 'selected' : '' ?>><?= h($prodi) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="jenjang">Jenjang</label>
            <select id="jenjang" name="jenjang">
                <option value="">Semua Jenjang</option>
                <?php foreach ($options['jenjang'] as $jenjang): ?>
                    <option value="<?= h($jenjang) ?>" <?= $filters['jenjang'] === $jenjang ? 'selected' : '' ?>><?= h($jenjang) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="alamat">Alamat</label>
            <select id="alamat" name="alamat">
                <option value="">Semua Alamat</option>
                <?php foreach ($options['alamat'] as $alamat): ?>
                    <option value="<?= h($alamat) ?>" <?= $filters['alamat'] === $alamat ? 'selected' : '' ?>><?= h($alamat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <button class="btn" type="submit">Terapkan Filter</button>
            <a class="btn btn-light" href="<?= h(app_url('admin/rekap.php')) ?>">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Ringkasan</h2>
    <p class="small">Periode aktif: <strong><?= h($filters['period_label']) ?></strong> | Status: <strong><?= h(report_status_label((string) $filters['status'])) ?></strong></p>
    <div class="summary-grid">
        <article class="summary-box">
            <p class="summary-label">Total Kunjungan</p>
            <p class="summary-value"><?= (int) $summary['total'] ?></p>
        </article>
        <article class="summary-box">
            <p class="summary-label">Mahasiswa</p>
            <p class="summary-value"><?= (int) $summary['total_mahasiswa'] ?></p>
        </article>
        <article class="summary-box">
            <p class="summary-label">Guest</p>
            <p class="summary-value"><?= (int) $summary['total_guest'] ?></p>
        </article>
    </div>
    <div class="form-actions">
        <a class="btn" href="<?= h(app_url('admin/report_export_excel.php')) ?>?<?= h($exportQuery) ?>">Export Excel</a>
        <a class="btn btn-light" target="_blank" href="<?= h(app_url('admin/report_print.php')) ?>?<?= h($exportQuery) ?>">Cetak PDF</a>
    </div>
</section>

<section class="card">
    <h2>Data Rekap (<?= count($rows) ?> baris)</h2>
    <p class="small">Tampilan dibatasi maksimal 2000 baris untuk performa halaman. Export tetap mengambil semua data sesuai filter.</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Status</th>
                    <th>ID Referensi</th>
                    <th>Nama</th>
                    <th>Prodi</th>
                    <th>Jenjang</th>
                    <th>Alamat</th>
                    <th>Tahun Akademik</th>
                    <th>Semester</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []): ?>
                    <tr>
                        <td colspan="10">Tidak ada data untuk filter ini.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= h((string) $row['tanggal']) ?></td>
                        <td><?= h((string) $row['jam']) ?></td>
                        <td><?= h((string) $row['tipe']) ?></td>
                        <td><?= h((string) $row['identitas']) ?></td>
                        <td><?= h((string) $row['nama']) ?></td>
                        <td><?= h((string) $row['prodi']) ?></td>
                        <td><?= h((string) $row['jenjang']) ?></td>
                        <td><?= h((string) $row['alamat']) ?></td>
                        <td><?= h((string) ($row['tahun_akademik'] ?? '-')) ?></td>
                        <td><?= h((string) ($row['semester'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$db->close();
require __DIR__ . '/_layout_bottom.php';
