<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../app/report_service.php';
require_once __DIR__ . '/../app/xlsx_export.php';
auth_require_login();

$filters = report_normalize_filters($_GET);
$rows = report_fetch_rows($db, $filters, null);
$summary = report_fetch_summary($db, $filters);

$filename = 'laporan_absensi_' . date('Ymd_His') . '.xlsx';
$excelRows = [];
$headers = ['Tanggal', 'Jam', 'Status', 'ID Referensi', 'Nama', 'Prodi', 'Jenjang', 'Alamat', 'Tahun Akademik', 'Semester'];

$excelRows[] = [
    ['value' => 'Laporan Absensi Pengunjung Perpustakaan', 'style' => 1],
];
$excelRows[] = [
    ['value' => 'Periode', 'style' => 2],
    ['value' => (string) $filters['period_label'], 'style' => 3],
];
$excelRows[] = [
    ['value' => 'Status', 'style' => 2],
    ['value' => report_status_label((string) $filters['status']), 'style' => 3],
];
$excelRows[] = [
    ['value' => 'Total Kunjungan', 'style' => 2],
    ['value' => (string) $summary['total'], 'style' => 3, 'type' => 'n'],
];
$excelRows[] = [
    ['value' => 'Total Mahasiswa', 'style' => 2],
    ['value' => (string) $summary['total_mahasiswa'], 'style' => 3, 'type' => 'n'],
];
$excelRows[] = [
    ['value' => 'Total Guest', 'style' => 2],
    ['value' => (string) $summary['total_guest'], 'style' => 3, 'type' => 'n'],
];
$excelRows[] = [];

$headerRow = [];
foreach ($headers as $header) {
    $headerRow[] = ['value' => $header, 'style' => 4];
}
$excelRows[] = $headerRow;

foreach ($rows as $row) {
    $excelRows[] = [
        ['value' => (string) $row['tanggal'], 'style' => 5],
        ['value' => (string) $row['jam'], 'style' => 5],
        ['value' => (string) $row['tipe'], 'style' => 5],
        ['value' => (string) $row['identitas'], 'style' => 5],
        ['value' => (string) $row['nama'], 'style' => 5],
        ['value' => (string) $row['prodi'], 'style' => 5],
        ['value' => (string) $row['jenjang'], 'style' => 5],
        ['value' => (string) $row['alamat'], 'style' => 5],
        ['value' => (string) ($row['tahun_akademik'] ?? '-'), 'style' => 5],
        ['value' => (string) ($row['semester'] ?? '-'), 'style' => 5],
    ];
}

$db->close();
xlsx_download($filename, $excelRows, 'Laporan Absensi', [
    'column_widths' => [
        1 => 14,
        2 => 11,
        3 => 13,
        4 => 16,
        5 => 24,
        6 => 16,
        7 => 10,
        8 => 30,
        9 => 16,
        10 => 10,
    ],
    'merges' => ['A1:J1'],
]);
