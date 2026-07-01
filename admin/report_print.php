<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/report_service.php';
auth_require_login();

$filters = report_normalize_filters($_GET);
$rows = report_fetch_rows($db, $filters, null);
$summary = report_fetch_summary($db, $filters);
$db->close();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Absensi</title>
    <style>
        body {
            margin: 18px;
            font-family: Arial, Helvetica, sans-serif;
            color: #1b2530;
            font-size: 12px;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 20px;
        }
        .meta {
            margin: 2px 0;
            color: #475569;
        }
        .tools {
            margin: 14px 0;
        }
        .tools a,
        .tools button {
            border: 1px solid #b9c7d8;
            background: #f6f9fd;
            border-radius: 6px;
            padding: 6px 10px;
            text-decoration: none;
            color: #15365c;
            cursor: pointer;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #cdd9e7;
            padding: 6px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #ecf3fc;
        }
        .summary {
            margin-top: 8px;
            display: flex;
            gap: 12px;
        }
        .summary strong {
            display: block;
            font-size: 16px;
            color: #0f3f85;
        }
        @media print {
            .tools {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Laporan Absensi Pengunjung Perpustakaan</h1>
    <p class="meta">Periode: <?= h((string) $filters['period_label']) ?></p>
    <p class="meta">Status: <?= h(report_status_label((string) $filters['status'])) ?></p>
    <p class="meta">Dicetak: <?= h(date('Y-m-d H:i:s')) ?></p>

    <div class="summary">
        <div>Total Kunjungan<br><strong><?= (int) $summary['total'] ?></strong></div>
        <div>Mahasiswa<br><strong><?= (int) $summary['total_mahasiswa'] ?></strong></div>
        <div>Guest<br><strong><?= (int) $summary['total_guest'] ?></strong></div>
    </div>

    <div class="tools">
        <button type="button" onclick="window.print()">Cetak / Save as PDF</button>
        <a href="<?= h(app_url('admin/rekap.php')) ?>?<?= h(report_export_query($filters)) ?>">Kembali ke Rekap</a>
    </div>

    <table>
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
                    <td colspan="10">Tidak ada data.</td>
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
</body>
</html>
