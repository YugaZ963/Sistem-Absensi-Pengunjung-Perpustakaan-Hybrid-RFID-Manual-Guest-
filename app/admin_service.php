<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function admin_bootstrap(mysqli $db): void
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS admin (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            nama VARCHAR(120) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $countResult = $db->query('SELECT COUNT(*) AS total FROM admin');
    $countRow = $countResult ? $countResult->fetch_assoc() : ['total' => 0];
    $total = (int) ($countRow['total'] ?? 0);

    if ($total === 0) {
        $username = 'admin';
        $nama = 'Admin Perpustakaan';
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $db->prepare('INSERT INTO admin (username, password_hash, nama) VALUES (?, ?, ?)');
        if ($insert) {
            $insert->bind_param('sss', $username, $hash, $nama);
            $insert->execute();
            $insert->close();
        }
    }
}

function admin_find_by_username(mysqli $db, string $username): ?array
{
    $query = $db->prepare('SELECT id, username, nama, password_hash FROM admin WHERE username = ? LIMIT 1');
    if (!$query) {
        throw new RuntimeException('Failed to prepare admin query.');
    }
    $query->bind_param('s', $username);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $row ?: null;
}

function admin_get_total_visits(mysqli $db): int
{
    $result = $db->query('SELECT COUNT(*) AS total FROM absensi');
    $row = $result ? $result->fetch_assoc() : null;
    return (int) ($row['total'] ?? 0);
}

function admin_get_today_visit_summary(mysqli $db, ?string $date = null): array
{
    $date = $date ?: date('Y-m-d');

    $query = $db->prepare(
        'SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN tipe = "mahasiswa" THEN 1 ELSE 0 END) AS total_mahasiswa,
            SUM(CASE WHEN tipe = "guest" THEN 1 ELSE 0 END) AS total_guest
         FROM absensi
         WHERE tanggal = ?'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare today summary query.');
    }

    $query->bind_param('s', $date);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return [
        'date' => $date,
        'total' => (int) ($row['total'] ?? 0),
        'total_mahasiswa' => (int) ($row['total_mahasiswa'] ?? 0),
        'total_guest' => (int) ($row['total_guest'] ?? 0),
    ];
}

function admin_get_student_by_id(mysqli $db, int $id): ?array
{
    $query = $db->prepare(
        'SELECT id, nim, nama, prodi, jenjang, alamat, semester, tahun_akademik, rfid_uid
         FROM mahasiswa
         WHERE id = ?
         LIMIT 1'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare student detail query.');
    }
    $query->bind_param('i', $id);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $row ?: null;
}

function admin_find_students(mysqli $db, string $search = ''): array
{
    $search = trim($search);
    if ($search === '') {
        $result = $db->query(
            'SELECT id, nim, nama, prodi, jenjang, alamat, semester, tahun_akademik, rfid_uid
             FROM mahasiswa
             ORDER BY nama ASC
             LIMIT 500'
        );
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $pattern = '%' . $search . '%';
    $query = $db->prepare(
        'SELECT id, nim, nama, prodi, jenjang, alamat, semester, tahun_akademik, rfid_uid
         FROM mahasiswa
         WHERE nim LIKE ? OR nama LIKE ?
         ORDER BY nama ASC
         LIMIT 500'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare students search query.');
    }

    $query->bind_param('ss', $pattern, $pattern);
    $query->execute();
    $result = $query->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $query->close();

    return $rows;
}

function admin_create_student(mysqli $db, array $data): void
{
    $query = $db->prepare(
        'INSERT INTO mahasiswa (nim, nama, prodi, jenjang, alamat, semester, tahun_akademik, rfid_uid)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare create mahasiswa query.');
    }

    $rfidUid = $data['rfid_uid'] !== '' ? $data['rfid_uid'] : null;
    $query->bind_param(
        'sssssiss',
        $data['nim'],
        $data['nama'],
        $data['prodi'],
        $data['jenjang'],
        $data['alamat'],
        $data['semester'],
        $data['tahun_akademik'],
        $rfidUid
    );
    $query->execute();
    $query->close();
}

function admin_update_student(mysqli $db, int $id, array $data): void
{
    $query = $db->prepare(
        'UPDATE mahasiswa
         SET nim = ?, nama = ?, prodi = ?, jenjang = ?, alamat = ?, semester = ?, tahun_akademik = ?, rfid_uid = ?
         WHERE id = ?'
    );
    if (!$query) {
        throw new RuntimeException('Failed to prepare update mahasiswa query.');
    }

    $rfidUid = $data['rfid_uid'] !== '' ? $data['rfid_uid'] : null;
    $query->bind_param(
        'sssssissi',
        $data['nim'],
        $data['nama'],
        $data['prodi'],
        $data['jenjang'],
        $data['alamat'],
        $data['semester'],
        $data['tahun_akademik'],
        $rfidUid,
        $id
    );
    $query->execute();
    $query->close();
}

function admin_delete_student(mysqli $db, int $id): void
{
    $query = $db->prepare('DELETE FROM mahasiswa WHERE id = ? LIMIT 1');
    if (!$query) {
        throw new RuntimeException('Failed to prepare delete mahasiswa query.');
    }
    $query->bind_param('i', $id);
    $query->execute();
    $query->close();
}

function admin_pair_rfid(mysqli $db, int $studentId, string $rfidUid): void
{
    $query = $db->prepare('UPDATE mahasiswa SET rfid_uid = ? WHERE id = ?');
    if (!$query) {
        throw new RuntimeException('Failed to prepare pairing RFID query.');
    }
    $query->bind_param('si', $rfidUid, $studentId);
    $query->execute();
    $query->close();
}

function admin_validate_student_input(array $input): array
{
    return [
        'nim' => trim((string) ($input['nim'] ?? '')),
        'nama' => trim((string) ($input['nama'] ?? '')),
        'prodi' => trim((string) ($input['prodi'] ?? '')),
        'jenjang' => trim((string) ($input['jenjang'] ?? '')),
        'alamat' => trim((string) ($input['alamat'] ?? '')),
        'semester' => (int) ($input['semester'] ?? 0),
        'tahun_akademik' => trim((string) ($input['tahun_akademik'] ?? '')),
        'rfid_uid' => trim((string) ($input['rfid_uid'] ?? '')),
    ];
}

function admin_student_input_has_required(array $data): bool
{
    return $data['nim'] !== ''
        && $data['nama'] !== ''
        && $data['prodi'] !== ''
        && $data['jenjang'] !== ''
        && $data['alamat'] !== ''
        && $data['semester'] > 0
        && $data['tahun_akademik'] !== '';
}

function admin_import_students(mysqli $db, string $filePath, string $extension): array
{
    if ($extension === 'xls') {
        return [
            'ok' => false,
            'message' => 'File .xls belum didukung parser bawaan. Simpan ulang ke .xlsx lalu upload lagi.',
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
    }

    $rows = admin_parse_xlsx_rows($filePath);
    if (count($rows) < 1) {
        return [
            'ok' => false,
            'message' => 'Data Excel kosong.',
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
    }

    $requiredStudentHeaders = ['nim', 'nama', 'prodi', 'jenjang', 'alamat', 'semester', 'tahun_akademik'];
    $requiredReportHeaders = ['status', 'id_referensi', 'nama', 'prodi', 'jenjang', 'alamat', 'semester', 'tahun_akademik'];

    $headerInfo = admin_detect_header_row($rows, $requiredStudentHeaders, 30);
    $importMode = 'student_template';

    if ($headerInfo === null) {
        $headerInfo = admin_detect_header_row($rows, $requiredReportHeaders, 30);
        $importMode = 'report_template';
    }

    if ($headerInfo === null) {
        return [
            'ok' => false,
            'message' => 'Header kolom tidak sesuai. Gunakan template mahasiswa (NIM, Nama, Prodi, Jenjang, Alamat, Semester, Tahun Akademik) atau file laporan export aplikasi.',
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
    }

    $headerMap = $headerInfo['header_map'];
    $startRowIndex = $headerInfo['row_index'] + 1;

    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $nextRfidIncrement = 1;

    $db->begin_transaction();
    try {
        $nextRfidIncrement = admin_get_next_rfid_increment($db);

        for ($i = $startRowIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if ($importMode === 'report_template') {
                $data = admin_extract_import_row_from_report($row, $headerMap);
            } else {
                $data = admin_extract_import_row_from_student_template($row, $headerMap);
            }

            if ($data === null || !admin_student_input_has_required(array_merge($data, ['rfid_uid' => '']))) {
                $skipped++;
                continue;
            }

            $existsQuery = $db->prepare('SELECT id, rfid_uid FROM mahasiswa WHERE nim = ? LIMIT 1');
            if (!$existsQuery) {
                throw new RuntimeException('Failed to prepare import lookup query.');
            }
            $existsQuery->bind_param('s', $data['nim']);
            $existsQuery->execute();
            $existsResult = $existsQuery->get_result();
            $existing = $existsResult ? $existsResult->fetch_assoc() : null;
            $existsQuery->close();

            if ($existing) {
                $existingRfid = trim((string) ($existing['rfid_uid'] ?? ''));
                $rfidUid = $existingRfid !== ''
                    ? $existingRfid
                    : admin_next_available_rfid_uid($db, (string) $data['nama'], $nextRfidIncrement);

                $update = $db->prepare(
                    'UPDATE mahasiswa
                     SET nama = ?, prodi = ?, jenjang = ?, alamat = ?, semester = ?, tahun_akademik = ?, rfid_uid = ?
                     WHERE nim = ?'
                );
                if (!$update) {
                    throw new RuntimeException('Failed to prepare import update query.');
                }
                $update->bind_param(
                    'ssssisss',
                    $data['nama'],
                    $data['prodi'],
                    $data['jenjang'],
                    $data['alamat'],
                    $data['semester'],
                    $data['tahun_akademik'],
                    $rfidUid,
                    $data['nim']
                );
                $update->execute();
                $update->close();
                $updated++;
            } else {
                $rfidUid = admin_next_available_rfid_uid($db, (string) $data['nama'], $nextRfidIncrement);

                $insert = $db->prepare(
                    'INSERT INTO mahasiswa (nim, nama, prodi, jenjang, alamat, semester, tahun_akademik, rfid_uid)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                if (!$insert) {
                    throw new RuntimeException('Failed to prepare import insert query.');
                }
                $insert->bind_param(
                    'sssssiss',
                    $data['nim'],
                    $data['nama'],
                    $data['prodi'],
                    $data['jenjang'],
                    $data['alamat'],
                    $data['semester'],
                    $data['tahun_akademik'],
                    $rfidUid
                );
                $insert->execute();
                $insert->close();
                $inserted++;
            }
        }

        $db->commit();
    } catch (Throwable $exception) {
        $db->rollback();
        throw $exception;
    }

    if (($inserted + $updated) === 0 && $skipped > 0) {
        return [
            'ok' => false,
            'message' => 'Tidak ada data mahasiswa valid yang bisa diimport dari file tersebut.',
            'inserted' => 0,
            'updated' => 0,
            'skipped' => $skipped,
        ];
    }

    return [
        'ok' => true,
        'message' => 'Import selesai.',
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped,
    ];
}

function admin_normalize_header(string $value): string
{
    $value = strtolower(trim($value));
    $value = str_replace([' ', '-', '/'], '_', $value);
    $value = preg_replace('/_+/', '_', $value) ?: '';
    $aliases = [
        'nim' => 'nim',
        'nama' => 'nama',
        'prodi' => 'prodi',
        'jenjang' => 'jenjang',
        'alamat' => 'alamat',
        'semester' => 'semester',
        'status' => 'status',
        'id_referensi' => 'id_referensi',
        'idreferensi' => 'id_referensi',
        'tahun_akademik' => 'tahun_akademik',
        'thn_akademik' => 'tahun_akademik',
        'tahunakademik' => 'tahun_akademik',
        'ta' => 'tahun_akademik',
    ];

    return $aliases[$value] ?? $value;
}

function admin_parse_xlsx_rows(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException('Gagal membuka file .xlsx.');
    }

    $sharedStrings = [];
    $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedStringsXml !== false) {
        $sx = simplexml_load_string($sharedStringsXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $item) {
                if (isset($item->t)) {
                    $sharedStrings[] = (string) $item->t;
                    continue;
                }

                $textParts = [];
                foreach ($item->r as $run) {
                    $textParts[] = (string) $run->t;
                }
                $sharedStrings[] = implode('', $textParts);
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        throw new RuntimeException('Sheet1 tidak ditemukan pada file .xlsx.');
    }

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        $zip->close();
        throw new RuntimeException('Struktur data sheet tidak valid.');
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $colLetters = preg_replace('/\d+/', '', $ref) ?: '';
            $colIndex = admin_excel_col_to_index($colLetters);
            $value = '';
            $raw = isset($cell->v) ? (string) $cell->v : '';
            $type = (string) $cell['t'];

            if ($type === 's') {
                $sIndex = (int) $raw;
                $value = $sharedStrings[$sIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = admin_read_inline_string($cell);
            } else {
                $value = $raw;
            }

            $line[$colIndex] = trim($value);
        }

        if ($line !== []) {
            ksort($line);
            $maxCol = max(array_keys($line));
            $dense = [];
            $hasValue = false;
            for ($col = 0; $col <= $maxCol; $col++) {
                $cellValue = $line[$col] ?? '';
                if ($cellValue !== '') {
                    $hasValue = true;
                }
                $dense[] = $cellValue;
            }
            if (!$hasValue) {
                continue;
            }
            $rows[] = $dense;
        }
    }

    $zip->close();

    return $rows;
}

function admin_excel_col_to_index(string $letters): int
{
    $letters = strtoupper($letters);
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max(0, $index - 1);
}

function admin_detect_header_row(array $rows, array $requiredHeaders, int $maxScanRows = 30): ?array
{
    $limit = min(count($rows), $maxScanRows);

    for ($rowIndex = 0; $rowIndex < $limit; $rowIndex++) {
        $headerMap = [];
        $header = array_map('admin_normalize_header', (array) $rows[$rowIndex]);
        foreach ($header as $colIndex => $headerName) {
            if ($headerName !== '' && !isset($headerMap[$headerName])) {
                $headerMap[$headerName] = $colIndex;
            }
        }

        $allFound = true;
        foreach ($requiredHeaders as $required) {
            if (!array_key_exists($required, $headerMap)) {
                $allFound = false;
                break;
            }
        }

        if ($allFound) {
            return [
                'row_index' => $rowIndex,
                'header_map' => $headerMap,
            ];
        }
    }

    return null;
}

function admin_extract_import_row_from_student_template(array $row, array $headerMap): ?array
{
    return [
        'nim' => trim((string) ($row[$headerMap['nim']] ?? '')),
        'nama' => trim((string) ($row[$headerMap['nama']] ?? '')),
        'prodi' => trim((string) ($row[$headerMap['prodi']] ?? '')),
        'jenjang' => trim((string) ($row[$headerMap['jenjang']] ?? '')),
        'alamat' => trim((string) ($row[$headerMap['alamat']] ?? '')),
        'semester' => (int) trim((string) ($row[$headerMap['semester']] ?? '0')),
        'tahun_akademik' => trim((string) ($row[$headerMap['tahun_akademik']] ?? '')),
    ];
}

function admin_extract_import_row_from_report(array $row, array $headerMap): ?array
{
    $status = strtolower(trim((string) ($row[$headerMap['status']] ?? '')));
    $nim = trim((string) ($row[$headerMap['id_referensi']] ?? ''));

    if ($status !== 'mahasiswa') {
        return null;
    }

    if ($nim === '' || stripos($nim, 'GST-') === 0) {
        return null;
    }

    return [
        'nim' => $nim,
        'nama' => trim((string) ($row[$headerMap['nama']] ?? '')),
        'prodi' => trim((string) ($row[$headerMap['prodi']] ?? '')),
        'jenjang' => trim((string) ($row[$headerMap['jenjang']] ?? '')),
        'alamat' => trim((string) ($row[$headerMap['alamat']] ?? '')),
        'semester' => (int) trim((string) ($row[$headerMap['semester']] ?? '0')),
        'tahun_akademik' => trim((string) ($row[$headerMap['tahun_akademik']] ?? '')),
    ];
}

function admin_read_inline_string(SimpleXMLElement $cell): string
{
    if (isset($cell->is->t)) {
        return (string) $cell->is->t;
    }

    if (isset($cell->is->r)) {
        $parts = [];
        foreach ($cell->is->r as $run) {
            $parts[] = (string) $run->t;
        }
        return implode('', $parts);
    }

    return '';
}

function admin_get_next_rfid_increment(mysqli $db): int
{
    $result = $db->query('SELECT rfid_uid FROM mahasiswa WHERE rfid_uid IS NOT NULL AND rfid_uid <> ""');
    if (!$result) {
        return 1;
    }

    $maxIncrement = 0;
    while ($row = $result->fetch_assoc()) {
        $rfidUid = strtoupper(trim((string) ($row['rfid_uid'] ?? '')));
        if (preg_match('/-(\d+)$/', $rfidUid, $matches) !== 1) {
            continue;
        }
        $number = (int) $matches[1];
        if ($number > $maxIncrement) {
            $maxIncrement = $number;
        }
    }

    return $maxIncrement + 1;
}

function admin_next_available_rfid_uid(mysqli $db, string $nama, int &$nextIncrement): string
{
    do {
        $candidate = admin_build_rfid_uid($nama, $nextIncrement);
        $nextIncrement++;
    } while (admin_rfid_uid_exists($db, $candidate));

    return $candidate;
}

function admin_build_rfid_uid(string $nama, int $increment): string
{
    $nameToken = admin_build_name_token($nama);
    return sprintf('RFID-%s-%03d', $nameToken, $increment);
}

function admin_build_name_token(string $nama): string
{
    $upper = strtoupper(trim($nama));
    $lettersOnly = preg_replace('/[^A-Z]/', '', $upper);
    if ($lettersOnly === null) {
        $lettersOnly = '';
    }

    $token = substr($lettersOnly, 0, 4);
    if (strlen($token) < 4) {
        $token = str_pad($token, 4, 'X');
    }

    return $token;
}

function admin_rfid_uid_exists(mysqli $db, string $rfidUid): bool
{
    $query = $db->prepare('SELECT id FROM mahasiswa WHERE rfid_uid = ? LIMIT 1');
    if (!$query) {
        return false;
    }
    $query->bind_param('s', $rfidUid);
    $query->execute();
    $result = $query->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $query->close();

    return $row !== null;
}
